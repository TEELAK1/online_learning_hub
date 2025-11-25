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
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id === 0) {
    header("Location: instructor_dashboard.php");
    exit();
}

// Verify course ownership
$courseCheck = $db->prepare("
    SELECT c.*, i.name as instructor_name
    FROM courses c
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE c.course_id = ? AND c.instructor_id = ?
");

if (!$courseCheck) {
    die("Database error");
}

$courseCheck->bind_param("ii", $course_id, $instructor_id);
$courseCheck->execute();
$courseResult = $courseCheck->get_result();

if ($courseResult->num_rows === 0) {
    die("Course not found or access denied.");
}

$course = $courseResult->fetch_assoc();

// Get course units with questions (instructor preview - no enrollment required)
$units = [];
$unitsStmt = $db->prepare("
    SELECT 
        cu.unit_id,
        cu.title,
        cu.description,
        cu.created_at,
        COUNT(DISTINCT uq.question_id) as question_count
    FROM course_units cu
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    WHERE cu.course_id = ?
    GROUP BY cu.unit_id
    ORDER BY cu.created_at ASC
");

if ($unitsStmt) {
    $unitsStmt->bind_param("i", $course_id);
    $unitsStmt->execute();
    $result = $unitsStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
}

// Calculate course statistics
$totalUnits = count($units);
$totalQuestions = array_sum(array_column($units, 'question_count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Preview - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .preview-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .preview-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .unit-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .unit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .unit-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .unit-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .unit-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-preview { background: #e3f2fd; color: #1976d2; }
        
        .media-preview {
            width: 60px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .quiz-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat-box {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            min-width: 60px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .progress-sidebar {
            position: sticky;
            top: 20px;
        }
        
        .progress-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3">
                    <div>
                        <a href="instructor_dashboard.php" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="viewcourse.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-cog me-2"></i>Manage Course
                        </a>
                    </div>
                    <div class="preview-badge">
                        <i class="fas fa-eye me-2"></i>Instructor Preview Mode
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Header -->
    <div class="preview-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-6 mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="lead mb-3 opacity-75"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <i class="fas fa-user me-2"></i>
                            <strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?>
                        </div>
                        <div>
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Created:</strong> <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h3 mb-1"><?php echo $totalUnits; ?></div>
                            <small class="opacity-75">Units</small>
                        </div>
                        <div class="col-6">
                            <div class="h3 mb-1"><?php echo $totalQuestions; ?></div>
                            <small class="opacity-75">Questions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-list-ul me-2 text-primary"></i>Course Units
                    </h3>
                    <span class="badge bg-primary fs-6"><?php echo count($units); ?> Units</span>
                </div>

                <?php if (empty($units)): ?>
                    <div class="card unit-card">
                        <div class="card-body empty-state">
                            <i class="fas fa-layer-group fa-4x mb-4"></i>
                            <h4>No Units Available</h4>
                            <p class="mb-4">This course doesn't have any units yet. Students will see this message until you add units.</p>
                            <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Unit
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($units as $index => $unit): ?>
                        <?php
                        $hasQuestions = $unit['question_count'] > 0;
                        ?>
                        
                        <div class="card unit-card">
                            <div class="unit-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="unit-number"><?php echo $index + 1; ?></div>
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($unit['title']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <?php echo htmlspecialchars(substr($unit['description'] ?? '', 0, 100)); ?>
                                                <?php echo strlen($unit['description'] ?? '') > 100 ? '...' : ''; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="unit-status status-preview mb-2">
                                            Preview Mode
                                        </div>
                                        <small class="text-muted">
                                            Created: <?php echo date('M j', strtotime($unit['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Unit Icon -->
                                    <div class="col-md-2">
                                        <div class="media-preview">
                                            <i class="fas fa-book fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Unit Info -->
                                    <div class="col-md-6">
                                        <?php if (!empty($unit['description'])): ?>
                                            <p class="mb-2"><?php echo htmlspecialchars($unit['description']); ?></p>
                                        <?php else: ?>
                                            <p class="mb-2 text-muted">No description available</p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fas fa-book text-muted"></i>
                                            <span class="text-muted">Learning Unit</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Quiz Stats & Actions -->
                                    <div class="col-md-4">
                                        <?php if ($hasQuestions): ?>
                                            <div class="quiz-stats">
                                                <div class="stat-box">
                                                    <div class="fw-bold text-primary"><?php echo $unit['question_count']; ?></div>
                                                    <small class="text-muted">Questions</small>
                                                </div>
                                                <div class="stat-box">
                                                    <div class="fw-bold text-info">0</div>
                                                    <small class="text-muted">Attempts</small>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-primary btn-sm" disabled>
                                                    <i class="fas fa-play me-2"></i>Take Quiz (Preview)
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <i class="fas fa-book-open fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Study Material Only</p>
                                                <small class="text-muted">No quiz available</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Unit Content Access -->
                                <hr>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="fas fa-book me-2 text-primary"></i>Unit Content
                                    </span>
                                    <div class="btn-group" role="group">
                                        <a href="manage_unit_questions.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-cog me-1"></i>Manage
                                        </a>
                                        <a href="create_unit.php?course_id=<?php echo $course_id; ?>&edit_unit=<?php echo $unit['unit_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Progress Sidebar -->
            <div class="col-lg-4">
                <div class="progress-sidebar">
                    <!-- Course Overview -->
                    <div class="progress-card">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>Course Overview
                        </h5>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h4 text-primary mb-1"><?php echo $totalUnits; ?></div>
                                <small class="text-muted">Total Units</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-success mb-1"><?php echo $totalQuestions; ?></div>
                                <small class="text-muted">Total Questions</small>
                            </div>
                        </div>
                    </div>

                    <!-- Student View Simulation -->
                    <div class="progress-card">
                        <h5 class="mb-3">
                            <i class="fas fa-user-graduate me-2 text-info"></i>Student Experience
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Course Progress</small>
                                <small>0%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Students will see their progress here as they complete units and quizzes.
                        </p>
                    </div>

                    <!-- Quick Actions -->
                    <div class="progress-card">
                        <h5 class="mb-3">
                            <i class="fas fa-tools me-2 text-warning"></i>Quick Actions
                        </h5>
                        <div class="d-grid gap-2">
                            <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Add New Unit
                            </a>
                            <a href="viewcourse.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-cog me-2"></i>Manage Course
                            </a>
                            <a href="../Materials/upload_material.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-upload me-2"></i>Upload Materials
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
