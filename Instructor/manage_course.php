<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is an instructor
if (!Auth::isAuthenticated() || !Auth::hasRole('instructor')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$message = "";
$success = false;

// Get course_id from URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify instructor owns this course
$courseCheck = $db->prepare("SELECT * FROM courses WHERE course_id = ? AND instructor_id = ?");
if ($courseCheck) {
    $courseCheck->bind_param("ii", $course_id, $instructor_id);
    $courseCheck->execute();
    $courseResult = $courseCheck->get_result();
    
    if ($courseResult->num_rows === 0) {
        // If instructor has no access to this course (or course_id is missing),
        // show a list of courses owned by this instructor so they can pick the right one.
        $ownedCourses = [];
        $cstmt = $db->prepare("SELECT course_id, title FROM courses WHERE instructor_id = ? ORDER BY title");
        if ($cstmt) {
            $cstmt->bind_param("i", $instructor_id);
            $cstmt->execute();
            $cres = $cstmt->get_result();
            while ($crow = $cres->fetch_assoc()) {
                $ownedCourses[] = $crow;
            }
            $cstmt->close();
        }

        // Render a small selection page and exit
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Select Course - Manage Course</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container py-5">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Select a Course to Manage</h3>
                    <p class="text-muted">You do not have access to the requested course or no course was specified. Choose one of your courses below:</p>
                    <?php if (empty($ownedCourses)): ?>
                        <div class="alert alert-warning">You don't have any courses yet. <a href="create_course.php">Create a course</a>.</div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($ownedCourses as $oc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($oc['title']); ?>
                                    <a class="btn btn-sm btn-primary" href="manage_course.php?course_id=<?php echo $oc['course_id']; ?>">Manage</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="instructor_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit();
    }

    $course = $courseResult->fetch_assoc();
} else {
    header("Location: instructor_dashboard.php");
    exit();
}

// Handle unit reordering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder_units'])) {
    $unit_orders = $_POST['unit_orders'] ?? [];
    
    foreach ($unit_orders as $unit_id => $order) {
        $updateStmt = $db->prepare("UPDATE course_units SET order_index = ? WHERE unit_id = ? AND course_id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("iii", $order, $unit_id, $course_id);
            $updateStmt->execute();
        }
    }
    
    $success = true;
    $message = "Unit order updated successfully!";
}

// Handle unit deletion
if (isset($_GET['delete_unit'])) {
    $unit_id = (int)$_GET['delete_unit'];
    
    $deleteStmt = $db->prepare("DELETE FROM course_units WHERE unit_id = ? AND course_id = ?");
    if ($deleteStmt) {
        $deleteStmt->bind_param("ii", $unit_id, $course_id);
        if ($deleteStmt->execute()) {
            $success = true;
            $message = "Unit deleted successfully!";
        } else {
            $message = "Failed to delete unit.";
        }
    }
}

// Handle instructor marking course completed / issuing certificates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_course_completed'])) {
    $force = isset($_POST['force_complete']) && $_POST['force_complete'] == '1';

    // Start transaction
    $db->begin_transaction();
    $issued_count = 0;
    try {
        if ($force) {
            // Force all enrollments to completed with 100% progress
            $upd = $db->prepare("UPDATE enrollments SET progress_percentage = 100, status = 'completed', completion_date = NOW() WHERE course_id = ?");
            if ($upd) {
                $upd->bind_param('i', $course_id);
                $upd->execute();
                $upd->close();
            }
        }

        // Find enrollments eligible for certificate (progress >=100 or status completed)
        $eligStmt = $db->prepare("SELECT enrollment_id, student_id FROM enrollments WHERE course_id = ? AND (progress_percentage >= 100 OR status = 'completed')");
        if ($eligStmt) {
            $eligStmt->bind_param('i', $course_id);
            $eligStmt->execute();
            $res = $eligStmt->get_result();

            $checkCert = $db->prepare("SELECT certificate_id FROM certificates WHERE student_id = ? AND course_id = ?");
            $insCert = $db->prepare("INSERT INTO certificates (student_id, course_id, certificate_code, issued_at, status) VALUES (?, ?, ?, NOW(), 'active')");

            while ($row = $res->fetch_assoc()) {
                $student_id_cert = $row['student_id'];
                // Skip if certificate exists
                if ($checkCert) {
                    $checkCert->bind_param('ii', $student_id_cert, $course_id);
                    $checkCert->execute();
                    $exists = $checkCert->get_result()->num_rows > 0;
                } else {
                    $exists = false;
                }

                if (!$exists && $insCert) {
                    $certificate_code = 'CERT-' . strtoupper(uniqid());
                    $insCert->bind_param('iis', $student_id_cert, $course_id, $certificate_code);
                    if ($insCert->execute()) {
                        $issued_count++;
                    }
                }
            }

            if ($checkCert) $checkCert->close();
            if ($insCert) $insCert->close();
            $eligStmt->close();
        }

        $db->commit();
        $success = true;
        $message = "Certificate issuance complete. {$issued_count} certificate(s) generated.";
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error issuing certificates: " . $e->getMessage();
        error_log('manage_course certificate error: ' . $e->getMessage());
    }
}

// Get course units with question counts
$units = [];
$unitsStmt = $db->prepare("
    SELECT u.*, 
           -- Provide fallback media columns in case older schemas don't have them
           NULL AS media_type,
           NULL AS media_url,
           NULL AS media_file_path,
           NULL AS media_description,
           COUNT(q.question_id) as question_count
    FROM course_units u 
    LEFT JOIN unit_questions q ON u.unit_id = q.unit_id AND q.is_active = 1
    WHERE u.course_id = ? 
    GROUP BY u.unit_id 
    ORDER BY u.order_index ASC
");

// Debug flag: show diagnostics when ?debug=1
$show_debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// Get current database name (for debugging)
$dbName = null;
try {
    $tmp = $db->query("SELECT DATABASE() AS dbname");
    if ($tmp) {
        $rowtmp = $tmp->fetch_assoc();
        $dbName = $rowtmp['dbname'] ?? null;
    }
} catch (Exception $e) {
    // ignore
}

if ($unitsStmt) {
    $unitsStmt->bind_param("i", $course_id);
    if ($unitsStmt->execute()) {
        $unitsResult = $unitsStmt->get_result();
        if ($unitsResult) {
            while ($row = $unitsResult->fetch_assoc()) {
                $units[] = $row;
            }
        }
    } else {
        $units_error = $unitsStmt->error;
        error_log("manage_course.php - unitsStmt execute failed: " . $units_error);
    }
} else {
    $units_error = $db->error;
    error_log("manage_course.php - unitsStmt prepare failed: " . $units_error);
}

// ensure debug variables exist
$units_error = $units_error ?? null;
$fetched_units_count = count($units);

// Get course statistics
$stats = [
    'total_units' => count($units),
    'total_questions' => 0,
    'total_enrollments' => 0
];

foreach ($units as $unit) {
    $stats['total_questions'] += $unit['question_count'];
}

// Get enrollment count
$enrollStmt = $db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
if ($enrollStmt) {
    $enrollStmt->bind_param("i", $course_id);
    $enrollStmt->execute();
    $enrollResult = $enrollStmt->get_result();
    $stats['total_enrollments'] = $enrollResult->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
        }
        
        .course-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .unit-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .unit-card:hover {
            transform: translateY(-2px);
        }
        
        .unit-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .media-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .drag-handle {
            cursor: grab;
            color: #6c757d;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .sortable-ghost {
            opacity: 0.5;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Course Header -->
        <div class="course-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="instructor_dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                    <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Course Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-list-ul fa-2x text-primary mb-3"></i>
                            <h3 class="mb-1"><?php echo $stats['total_units']; ?></h3>
                            <p class="text-muted mb-0">Total Units</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-question-circle fa-2x text-success mb-3"></i>
                            <h3 class="mb-1"><?php echo $stats['total_questions']; ?></h3>
                            <p class="text-muted mb-0">Total Questions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-warning mb-3"></i>
                            <h3 class="mb-1"><?php echo $stats['total_enrollments']; ?></h3>
                            <p class="text-muted mb-0">Enrolled Students</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($show_debug) && $show_debug): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Debug Info</h5>
                        <pre style="white-space:pre-wrap;">Database: <?php echo htmlspecialchars($dbName ?? 'unknown'); ?>
Course ID: <?php echo htmlspecialchars($course_id); ?>
Fetched units (count): <?php echo htmlspecialchars($fetched_units_count); ?>
Units query error: <?php echo htmlspecialchars($units_error ?? 'none'); ?>

Raw units array:
<?php echo htmlspecialchars(print_r($units, true)); ?>
                        </pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="fas fa-list-ul me-2 text-primary"></i>Course Units
                </h3>
                <div class="d-flex gap-2">
                    <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Unit
                    </a>
                    <?php if (!empty($units)): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleReorderMode()">
                            <i class="fas fa-sort me-2"></i>Reorder Units
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instructor Course Completion / Certificate Controls -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Course Completion & Certificates</h5>
                    <p class="text-muted">From here you can issue certificates to students who have completed the course. Optionally force-complete the course for all enrolled students (this will set progress to 100% and mark enrollments completed).</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to proceed? This will issue certificates to eligible students.');">
                        <input type="hidden" name="mark_course_completed" value="1">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="force_complete" name="force_complete">
                            <label class="form-check-label" for="force_complete">Force-complete course for all enrolled students</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Issue Certificates</button>
                            <a href="instructor_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                    <?php if (!empty($message)): ?>
                        <div class="mt-3 alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Units List -->
            <?php if (empty($units)): ?>
                <div class="card">
                    <div class="card-body empty-state">
                        <i class="fas fa-list-ul fa-4x mb-4"></i>
                        <h4>No Units Created Yet</h4>
                        <p class="mb-4">Start building your course by creating your first unit. Each unit can contain multiple-choice questions and media content.</p>
                        <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create First Unit
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" id="reorderForm" style="display: none;">
                    <input type="hidden" name="reorder_units" value="1">
                    <div class="d-flex justify-content-end mb-3">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fas fa-save me-2"></i>Save Order
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="cancelReorder()">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </div>
                </form>

                <div id="unitsList">
                    <?php foreach ($units as $index => $unit): ?>
                        <div class="card unit-card" data-unit-id="<?php echo $unit['unit_id']; ?>">
                            <div class="unit-header">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <div class="drag-handle d-none">
                                            <i class="fas fa-grip-vertical fa-lg"></i>
                                        </div>
                                        <span class="unit-number badge bg-primary fs-6"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($unit['title']); ?></h5>
                                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars(substr($unit['description'] ?? '', 0, 100)); ?><?php echo strlen($unit['description'] ?? '') > 100 ? '...' : ''; ?></p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <?php if ($unit['media_type'] !== 'none'): ?>
                                            <div class="media-preview">
                                                <?php if ($unit['media_type'] === 'video'): ?>
                                                    <i class="fas fa-play-circle fa-2x text-primary"></i>
                                                <?php elseif ($unit['media_type'] === 'document'): ?>
                                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                                <?php elseif ($unit['media_type'] === 'slides'): ?>
                                                    <i class="fas fa-file-powerpoint fa-2x text-warning"></i>
                                                <?php elseif ($unit['media_type'] === 'image'): ?>
                                                    <i class="fas fa-image fa-2x text-info"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?php echo ucfirst($unit['media_type']); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">No Media</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-success fs-6 mb-2"><?php echo $unit['question_count']; ?> Questions</span>
                                            <a href="manage_unit_questions.php?unit_id=<?php echo $unit['unit_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Questions">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="manage_unit_questions.php?unit_id=<?php echo $unit['unit_id']; ?>">
                                                    <i class="fas fa-question-circle me-2"></i>Manage Questions
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="?course_id=<?php echo $course_id; ?>&delete_unit=<?php echo $unit['unit_id']; ?>" onclick="return confirm('Are you sure you want to delete this unit and all its questions?')">
                                                    <i class="fas fa-trash me-2"></i>Delete Unit
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden input for reordering -->
                                <input type="hidden" name="unit_orders[<?php echo $unit['unit_id']; ?>]" value="<?php echo $unit['order_index']; ?>" form="reorderForm">
                            </div>
                            
                            <?php if (!empty($unit['description']) || $unit['media_type'] !== 'none'): ?>
                                <div class="card-body">
                                    <?php if (!empty($unit['description'])): ?>
                                        <p class="mb-3"><?php echo htmlspecialchars($unit['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($unit['media_type'] !== 'none'): ?>
                                        <div class="d-flex align-items-center gap-3">
                                            <strong>Media:</strong>
                                            <?php if ($unit['media_type'] === 'video' && !empty($unit['media_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($unit['media_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>View Video
                                                </a>
                                            <?php elseif (!empty($unit['media_file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($unit['media_file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i>Download File
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($unit['media_description'])): ?>
                                                <small class="text-muted">- <?php echo htmlspecialchars($unit['media_description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let sortable = null;
        let isReorderMode = false;
        
        function toggleReorderMode() {
            isReorderMode = !isReorderMode;
            const reorderForm = document.getElementById('reorderForm');
            const dragHandles = document.querySelectorAll('.drag-handle');
            const unitNumbers = document.querySelectorAll('.unit-number');
            
            if (isReorderMode) {
                // Show reorder form and drag handles
                reorderForm.style.display = 'block';
                dragHandles.forEach(handle => handle.classList.remove('d-none'));
                unitNumbers.forEach(number => number.style.display = 'none');
                
                // Initialize sortable
                const unitsList = document.getElementById('unitsList');
                sortable = Sortable.create(unitsList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        updateOrderInputs();
                    }
                });
            } else {
                cancelReorder();
            }
        }
        
        function cancelReorder() {
            isReorderMode = false;
            const reorderForm = document.getElementById('reorderForm');
            const dragHandles = document.querySelectorAll('.drag-handle');
            const unitNumbers = document.querySelectorAll('.unit-number');
            
            // Hide reorder form and drag handles
            reorderForm.style.display = 'none';
            dragHandles.forEach(handle => handle.classList.add('d-none'));
            unitNumbers.forEach(number => number.style.display = 'inline-block');
            
            // Destroy sortable
            if (sortable) {
                sortable.destroy();
                sortable = null;
            }
            
            // Reset to original order
            location.reload();
        }
        
        function updateOrderInputs() {
            const unitCards = document.querySelectorAll('.unit-card');
            unitCards.forEach((card, index) => {
                const unitId = card.dataset.unitId;
                const orderInput = document.querySelector(`input[name="unit_orders[${unitId}]"]`);
                if (orderInput) {
                    orderInput.value = index + 1;
                }
            });
        }
    </script>
</body>
</html>
