<?php
session_start();
require_once '../config/database.php';

// ---- Authentication ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = (int)$_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    header("Location: student_dashboard.php");
    exit();
}

/* ---------------------------------------------------------
   1. GET COURSE INFO
--------------------------------------------------------- */
$course = [];
$courseQuery = "
    SELECT 
        c.*,
        i.name AS instructor_name,
        i.email AS instructor_email,
        COUNT(DISTINCT cu.unit_id) AS total_units,
        COUNT(DISTINCT uq.question_id) AS total_questions
    FROM courses c
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    LEFT JOIN course_units cu ON c.course_id = cu.course_id
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    WHERE c.course_id = ?
    GROUP BY c.course_id
";

$courseStmt = $db->prepare($courseQuery);
if ($courseStmt !== false) {
    $courseStmt->bind_param("i", $course_id);
    $courseStmt->execute();
    $course = $courseStmt->get_result()->fetch_assoc() ?: [];
    $courseStmt->close();
}

/* If no course found -> redirect back */
if (empty($course)) {
    header("Location: student_dashboard.php");
    exit();
}

/* ---------------------------------------------------------
   2. CHECK ENROLLMENT STATUS (Explicit Check)
--------------------------------------------------------- */
$is_enrolled = false;
$enrollment_status = null;
$enrollment_date = null;
$progress_percentage = 0;

$checkEnrollStmt = $db->prepare("SELECT status, enrollment_date, progress_percentage FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1");
if ($checkEnrollStmt) {
    $checkEnrollStmt->bind_param("ii", $student_id, $course_id);
    $checkEnrollStmt->execute();
    $enrollResult = $checkEnrollStmt->get_result();
    if ($row = $enrollResult->fetch_assoc()) {
        $enrollment_status = $row['status'];
        $enrollment_date = $row['enrollment_date'];
        $progress_percentage = (int)$row['progress_percentage'];
        
        // Consider enrolled if status is active or completed
        if (in_array($enrollment_status, ['active', 'completed'])) {
            $is_enrolled = true;
        }
    }
    $checkEnrollStmt->close();
}

/* ---------------------------------------------------------
   3. GET COURSE UNITS WITH STATS
--------------------------------------------------------- */
$units = [];
$unitsQuery = "
    SELECT 
        cu.unit_id,
        cu.course_id,
        cu.title,
        cu.description,
        cu.created_at,
        cu.order_index,
        COUNT(DISTINCT uq.question_id) AS question_count,
        COUNT(DISTINCT m.material_id) AS material_count
    FROM course_units cu
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    LEFT JOIN materials m ON cu.unit_id = m.lesson_id
    WHERE cu.course_id = ?
    GROUP BY cu.unit_id
    ORDER BY cu.order_index ASC
";

$unitsStmt = $db->prepare($unitsQuery);
if ($unitsStmt !== false) {
    $unitsStmt->bind_param("i", $course_id);
    $unitsStmt->execute();
    $units = $unitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $unitsStmt->close();
}

/* ---------------------------------------------------------
   4. GET COURSE MATERIALS
--------------------------------------------------------- */
$materials = [];
$materialsQuery = "
    SELECT 
        m.*,
        cu.title AS unit_title,
        cu.order_index
    FROM materials m
    LEFT JOIN course_units cu ON m.lesson_id = cu.unit_id
    WHERE m.course_id = ?
    ORDER BY cu.order_index ASC, m.uploaded_at DESC
";
$materialsStmt = $db->prepare($materialsQuery);
if ($materialsStmt !== false) {
    $materialsStmt->bind_param("i", $course_id);
    $materialsStmt->execute();
    $materials = $materialsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $materialsStmt->close();
}

/* ---------------------------------------------------------
   5. ENROLLMENT STATS (General)
--------------------------------------------------------- */
$enrollmentStats = ['total_enrolled' => 0, 'avg_progress' => 0];
$statsQuery = "
    SELECT 
        COUNT(*) AS total_enrolled,
        AVG(progress_percentage) AS avg_progress
    FROM enrollments 
    WHERE course_id = ? 
    AND status IN ('active', 'completed')
";
$statsStmt = $db->prepare($statsQuery);
if ($statsStmt !== false) {
    $statsStmt->bind_param("i", $course_id);
    $statsStmt->execute();
    $res = $statsStmt->get_result()->fetch_assoc();
    if ($res) {
        $enrollmentStats['total_enrolled'] = (int)($res['total_enrolled'] ?? 0);
        $enrollmentStats['avg_progress'] = $res['avg_progress'] !== null ? round($res['avg_progress']) : 0;
    }
    $statsStmt->close();
}

/* ---------------------------------------------------------
   6. Helper Data
--------------------------------------------------------- */
$total_units = (int)($course['total_units'] ?? 0);
$total_questions = (int)($course['total_questions'] ?? 0);

// Group materials
$materials_by_unit = [];
foreach ($materials as $m) {
    $unitKey = (int)($m['lesson_id'] ?? 0);
    if (!isset($materials_by_unit[$unitKey])) $materials_by_unit[$unitKey] = [];
    $materials_by_unit[$unitKey][] = $m;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($course['title'] ?? 'Course Overview'); ?> - Course Overview</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        body {
            background: linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }
        .course-hero {
            background: linear-gradient(135deg,var(--primary-color),var(--secondary-color));
            color: #fff;
            padding: 48px;
            border-radius: 12px;
            margin-bottom: 28px;
        }
        .info-card { background:#fff; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.04); margin-bottom:20px; }
        .card-header-custom { padding:16px; border-radius:12px 12px 0 0; background:#f7f7f9; border-bottom:1px solid #eee; }
        .unit-preview { padding:12px; border-radius:8px; border:1px solid #f0f0f0; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="container py-4">

    <!-- Course Hero -->
    <div class="course-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-6 mb-2"><?php echo htmlspecialchars($course['title'] ?? 'Untitled Course'); ?></h1>
                <p class="mb-2 text-light small"><?php echo nl2br(htmlspecialchars(substr($course['description'] ?? '', 0, 220))); ?><?php echo strlen($course['description'] ?? '') > 220 ? '...' : ''; ?></p>

                <div class="d-flex gap-3 align-items-center">
                    <?php if ($is_enrolled): ?>
                        <div class="badge <?php echo $enrollment_status === 'completed' ? 'bg-success' : 'bg-info'; ?> text-white">
                            <?php echo $enrollment_status === 'completed' ? 'Completed' : 'Enrolled'; ?>
                        </div>
                        <div class="text-white-50 small">
                            <i class="fa fa-calendar me-1"></i>
                            Enrolled: <?php echo !empty($enrollment_date) ? date('M j, Y', strtotime($enrollment_date)) : '—'; ?>
                        </div>
                    <?php else: ?>
                        <div class="badge bg-secondary text-white">Not Enrolled</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <?php if ($is_enrolled): ?>
                    <a href="course_content.php?course_id=<?php echo $course_id; ?>" class="btn btn-light mb-2">
                        <i class="fas fa-play me-1"></i> <?php echo $progress_percentage > 0 ? 'Continue Learning' : 'Start Learning'; ?>
                    </a>
                    <div class="text-white-50 small">Progress: <?php echo $progress_percentage; ?>%</div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="../Courses/courses.php">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <button type="submit" name="enroll" class="btn btn-light mb-2">
                            <i class="fas fa-plus me-1"></i> Enroll Now
                        </button>
                    </form>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="<?php echo $is_enrolled ? 'my_courses.php' : '../Courses/courses.php'; ?>" class="btn btn-outline-light btn-sm">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- MAIN -->
        <div class="col-lg-8">
            <!-- Description -->
            <div class="card info-card">
                <div class="card-header-custom"><h5 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i> Course Description</h5></div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($course['description'] ?? 'No description available.')); ?></p>
                </div>
            </div>

            <!-- Course Content Preview -->
            <div class="card info-card">
                <div class="card-header-custom"><h5 class="mb-0"><i class="fas fa-list-ul me-2 text-primary"></i> Course Content</h5></div>
                <div class="card-body">
                    <?php if (empty($units)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-layer-group fa-3x mb-3"></i>
                            <h6>No units available</h6>
                            <p class="small">This course doesn't have any units yet.</p>
                        </div>
                    <?php else: ?>
                        <p class="small text-muted">This course contains <?php echo $total_units; ?> units and <?php echo $total_questions; ?> questions.</p>

                        <?php 
                        // Show first 5 units
                        $preview_units = array_slice($units, 0, 5);
                        foreach ($preview_units as $index => $unit): 
                            $u_qcount = (int)($unit['question_count'] ?? 0);
                            $u_mcount = (int)($unit['material_count'] ?? 0);
                        ?>
                            <div class="unit-preview d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-primary me-3"><?php echo $index + 1; ?></div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($unit['title']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($unit['description'] ?? '', 0, 80)); ?><?php echo strlen($unit['description'] ?? '') > 80 ? '...' : ''; ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    <?php if ($u_mcount > 0): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-paperclip me-1"></i><?php echo $u_mcount; ?> files</span>
                                    <?php endif; ?>

                                    <?php if ($u_qcount > 0): ?>
                                        <span class="badge bg-success"><i class="fas fa-question-circle me-1"></i><?php echo $u_qcount; ?> Q</span>
                                    <?php endif; ?>

                                    <?php if ($is_enrolled): ?>
                                        <a href="course_content.php?course_id=<?php echo $course_id; ?>&unit_id=<?php echo (int)$unit['unit_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">Open</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($total_units > 5): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">And <?php echo max(0, $total_units - 5); ?> more units...
                                <?php if ($is_enrolled): ?><a href="course_content.php?course_id=<?php echo $course_id; ?>"> View All</a><?php endif; ?></small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Materials List -->
            <div class="card info-card">
                <div class="card-header-custom"><h5 class="mb-0"><i class="fas fa-paperclip me-2 text-primary"></i> Materials</h5></div>
                <div class="card-body">
                    <?php if (empty($materials)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                            <h6>No materials found</h6>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($materials as $m): ?>
                                <?php
                                    $unit_id_for = (int)($m['lesson_id'] ?? 0);
                                    $unit_title = $m['unit_title'] ?? ($materials_by_unit[$unit_id_for][0]['unit_title'] ?? '');
                                    $file_type = strtolower($m['file_type'] ?? '');
                                    $file_name = $m['file_name'] ?? ($m['title'] ?? 'file');
                                    $download_link = htmlspecialchars($m['file_path'] ?? '#');
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($m['title'] ?: $file_name); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($unit_title); ?> • <?php echo !empty($m['uploaded_at']) ? date('M j, Y', strtotime($m['uploaded_at'])) : ''; ?></small>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <small class="text-muted me-2"><?php echo strtoupper($file_type ?: 'FILE'); ?></small>
                                        <?php if ($is_enrolled): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $download_link; ?>" target="_blank" rel="noopener">Open</a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-lock"></i> Locked</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instructor -->
            <div class="card info-card">
                <div class="card-header-custom"><h5 class="mb-0"><i class="fas fa-user-tie me-2 text-primary"></i> Instructor</h5></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown'); ?></div>
                            <?php if (!empty($course['instructor_email'])): ?>
                                <div class="small text-muted"><?php echo htmlspecialchars($course['instructor_email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <!-- Stats -->
            <div class="card info-card">
                <div class="card-header-custom"><h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> Course Statistics</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 text-center bg-white rounded">
                                <div class="h5 mb-0"><?php echo $enrollmentStats['total_enrolled']; ?></div>
                                <small class="text-muted">Students</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center bg-white rounded">
                                <div class="h5 mb-0"><?php echo $total_units; ?></div>
                                <small class="text-muted">Units</small>
                            </div>
                        </div>
                        <div class="col-6 mt-2">
                            <div class="p-3 text-center bg-white rounded">
                                <div class="h5 mb-0"><?php echo $total_questions; ?></div>
                                <small class="text-muted">Questions</small>
                            </div>
                        </div>
                        <div class="col-6 mt-2">
                            <div class="p-3 text-center bg-white rounded">
                                <div class="h5 mb-0"><?php echo $enrollmentStats['avg_progress']; ?>%</div>
                                <small class="text-muted">Avg Progress</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card info-card">
                <div class="card-header-custom"><h6 class="mb-0"><i class="fas fa-bolt me-2 text-primary"></i> Quick Actions</h6></div>
                <div class="card-body">
                    <?php if ($is_enrolled): ?>
                        <a href="course_content.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary w-100 mb-2"><i class="fas fa-play me-1"></i> <?php echo $progress_percentage > 0 ? 'Continue Learning' : 'Start Learning'; ?></a>
                        <?php if ($progress_percentage > 0): ?>
                            <a href="course_content.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-success w-100 mb-2"><i class="fas fa-chart-line me-1"></i> View Progress</a>
                        <?php endif; ?>
                        <a href="../chat.php" class="btn btn-outline-info w-100"><i class="fas fa-comments me-1"></i> Join Discussion</a>
                    <?php else: ?>
                        <form method="POST" action="../Courses/courses.php">
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <button type="submit" name="enroll" class="btn btn-success w-100 mb-2"><i class="fas fa-plus me-1"></i> Enroll in Course</button>
                        </form>
                        <a href="../Courses/courses.php" class="btn btn-outline-primary w-100">Browse Courses</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Info -->
            <div class="card info-card">
                <div class="card-body">
                    <h6 class="mb-3">Course Information</h6>
                    <ul class="list-unstyled small mb-0">
                        <li><strong>Created:</strong> <?php echo !empty($course['created_at']) ? date('M j, Y', strtotime($course['created_at'])) : '—'; ?></li>
                        <li><strong>Updated:</strong> <?php echo !empty($course['updated_at']) ? date('M j, Y', strtotime($course['updated_at'])) : (!empty($course['created_at']) ? date('M j, Y', strtotime($course['created_at'])) : '—'); ?></li>
                        <li><strong>Category:</strong> <?php echo htmlspecialchars($course['category'] ?? 'General'); ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
