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

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0);

if ($course_id === 0) {
    header("Location: instructor_dashboard.php");
    exit();
}

// Fetch course details and verify ownership
$course_stmt = $db->prepare("SELECT course_id, title, description, created_at, updated_at FROM courses WHERE course_id = ? AND instructor_id = ?");
if (!$course_stmt) {
    die("Database error: " . $db->error);
}

$course_stmt->bind_param("ii", $course_id, $instructor_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows === 0) {
    die("Course not found.");
}
$course = $course_result->fetch_assoc();

// Fetch units under this course with question counts
$units_stmt = $db->prepare("
    SELECT 
        cu.unit_id, 
        cu.title, 
        cu.description, 
        cu.created_at,
        COUNT(uq.question_id) as question_count
    FROM course_units cu
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    WHERE cu.course_id = ?
    GROUP BY cu.unit_id
    ORDER BY cu.created_at ASC
");

if (!$units_stmt) {
    die("Database error: " . $db->error);
}

$units_stmt->bind_param("i", $course_id);
$units_stmt->execute();
$units_result = $units_stmt->get_result();

// Get course statistics
$stats_stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT cu.unit_id) as total_units,
        COUNT(DISTINCT uq.question_id) as total_questions,
        COUNT(DISTINCT e.student_id) as total_students
    FROM course_units cu
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    LEFT JOIN enrollments e ON cu.course_id = e.course_id AND e.status = 'active'
    WHERE cu.course_id = ?
");

$stats = ['total_units' => 0, 'total_questions' => 0, 'total_students' => 0];
if ($stats_stmt) {
    $stats_stmt->bind_param("i", $course_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
}
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
        
        .course-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .unit-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        
        .media-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Navigation -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="instructor_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <a href="preview_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-info">
                <i class="fas fa-eye me-2"></i>Preview as Student
            </a>
        </div>
        
        <!-- Course Header -->
        <div class="course-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-6 mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="lead mb-0 opacity-75"><?php echo htmlspecialchars($course['description']); ?></p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex flex-column gap-2">
                        <a href="manage_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-light btn-lg">
                            <i class="fas fa-edit me-2"></i>Manage Course
                        </a>
                        <a href="instructor_dashboard.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-list-ul fa-3x text-primary mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_units']; ?></h3>
                        <p class="text-muted mb-0">Units</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle fa-3x text-success mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_questions']; ?></h3>
                        <p class="text-muted mb-0">Questions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_students']; ?></h3>
                        <p class="text-muted mb-0">Students</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Units -->
        <div id="units" class="d-flex justify-content-between align-items-center mb-4">
            <h3>
                <i class="fas fa-book-open me-2 text-primary"></i>Course Units
            </h3>
            <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Unit
            </a>
        </div>

        <?php if ($units_result->num_rows > 0): ?>
            <?php $unit_number = 1; ?>
            <?php while ($unit = $units_result->fetch_assoc()): ?>
                <div class="card unit-card">
                    <div class="unit-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge bg-primary fs-6 me-3"><?php echo $unit_number++; ?></div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($unit['title']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars(substr($unit['description'] ?? '', 0, 100)); ?><?php echo strlen($unit['description'] ?? '') > 100 ? '...' : ''; ?></p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success"><?php echo $unit['question_count']; ?> Questions</span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="manage_unit_questions.php?unit_id=<?php echo $unit['unit_id']; ?>">
                                            <i class="fas fa-question-circle me-2"></i>Manage Questions
                                        </a></li>
                                        <li><a class="dropdown-item" href="create_unit.php?course_id=<?php echo $course_id; ?>">
                                            <i class="fas fa-edit me-2"></i>Edit Unit
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="media-icon bg-light">
                                    <i class="fas fa-book fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <?php if (!empty($unit['description'])): ?>
                                    <p class="mb-2"><?php echo htmlspecialchars($unit['description']); ?></p>
                                <?php else: ?>
                                    <p class="mb-2 text-muted">No description available</p>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-question-circle text-muted"></i>
                                    <span class="text-muted"><?php echo $unit['question_count']; ?> Questions Available</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <small class="text-muted">Created: <?php echo date('M j, Y', strtotime($unit['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card unit-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-layer-group fa-4x text-muted mb-4"></i>
                    <h4>No Units Created Yet</h4>
                    <p class="text-muted mb-4">Start building your course by creating your first unit.</p>
                    <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Create First Unit
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

