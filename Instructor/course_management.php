<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Get selected course
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$selected_course = null;

// Get instructor's courses
$coursesStmt = $db->prepare("
    SELECT 
        c.*,
        COUNT(DISTINCT cu.unit_id) as unit_count,
        COUNT(DISTINCT e.student_id) as student_count
    FROM courses c
    LEFT JOIN course_units cu ON c.course_id = cu.course_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
    WHERE c.instructor_id = ?
    GROUP BY c.course_id
    ORDER BY c.created_at DESC
");

$coursesStmt->bind_param("i", $instructor_id);
$coursesStmt->execute();
$courses = $coursesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get selected course details if course_id is provided
if ($course_id > 0) {
    foreach ($courses as $course) {
        if ($course['course_id'] == $course_id) {
            $selected_course = $course;
            break;
        }
    }
}

// Get units for selected course
$units = [];
if ($selected_course) {
    $unitsStmt = $db->prepare("
        SELECT 
            cu.*,
            COUNT(DISTINCT uq.question_id) as question_count,
            COUNT(DISTINCT m.material_id) as material_count,
            COUNT(DISTINCT ua.student_id) as attempt_count
        FROM course_units cu
        LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
        LEFT JOIN materials m ON cu.unit_id = m.unit_id
        LEFT JOIN unit_attempts ua ON cu.unit_id = ua.unit_id AND ua.status = 'completed'
        WHERE cu.course_id = ?
        GROUP BY cu.unit_id
        ORDER BY cu.created_at ASC
    ");
    
    $unitsStmt->bind_param("i", $course_id);
    $unitsStmt->execute();
    $units = $unitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--light-color);
            color: var(--text-primary);
        }
        
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .management-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
        }
        
        .management-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .course-selector {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .course-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .course-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .course-item.active {
            border-color: var(--success-color);
            background-color: #f0fdf4;
        }
        
        .unit-card {
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .unit-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1);
        }
        
        .stat-box {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="instructor_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i> Online Learning Hub
            </a>
            <div class="d-flex align-items-center">
                <a href="instructor_dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Course Selector -->
        <div class="course-selector">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-2">
                        <i class="fas fa-cogs me-2"></i>Course Management
                    </h2>
                    <p class="mb-0 opacity-75">Select a course to manage its units, content, and settings</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="instructor_dashboard.php" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Create New Course
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Course List -->
            <div class="col-lg-4">
                <div class="management-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>Your Courses
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($courses)): ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open fa-3x mb-3"></i>
                                <h6>No Courses Yet</h6>
                                <p class="small">Create your first course to get started</p>
                                <a href="instructor_dashboard.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Create Course
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <div class="course-item <?php echo $course['course_id'] == $course_id ? 'active' : ''; ?>" 
                                     onclick="selectCourse(<?php echo $course['course_id']; ?>)">
                                    <h6 class="mb-2"><?php echo htmlspecialchars($course['title']); ?></h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-muted">Units</small>
                                            <div class="fw-bold text-primary"><?php echo $course['unit_count']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Students</small>
                                            <div class="fw-bold text-success"><?php echo $course['student_count']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Created</small>
                                            <div class="fw-bold text-info"><?php echo date('M Y', strtotime($course['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Course Management Area -->
            <div class="col-lg-8">
                <?php if ($selected_course): ?>
                    <!-- Course Header -->
                    <div class="management-card mb-4">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($selected_course['title']); ?>
                                </h5>
                                <div class="btn-group">
                                    <a href="viewcourse.php?course_id=<?php echo $course_id; ?>" class="btn btn-light btn-sm">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <a href="preview_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-light btn-sm">
                                        <i class="fas fa-desktop me-1"></i>Preview
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($selected_course['description']); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-number"><?php echo $selected_course['unit_count']; ?></div>
                                        <small class="text-muted">Units</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-number"><?php echo $selected_course['student_count']; ?></div>
                                        <small class="text-muted">Students</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-number"><?php echo count($units); ?></div>
                                        <small class="text-muted">Active Units</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-number"><?php echo date('M Y', strtotime($selected_course['created_at'])); ?></div>
                                        <small class="text-muted">Created</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Actions -->
                    <div class="management-card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>Course Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Add New Unit
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="../Materials/upload_material.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-success w-100">
                                        <i class="fas fa-upload me-2"></i>Upload Materials
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="../Quiz/create_quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-question-circle me-2"></i>Create Quiz
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="course_analytics.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-info w-100">
                                        <i class="fas fa-chart-bar me-2"></i>View Analytics
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Units Management -->
                    <div class="management-card">
                        <div class="card-header bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-list-ul me-2"></i>Course Units
                                </h6>
                                <span class="badge bg-light text-dark"><?php echo count($units); ?> Units</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($units)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-layer-group fa-3x mb-3"></i>
                                    <h6>No Units Created</h6>
                                    <p class="small">Start building your course by creating the first unit</p>
                                    <a href="create_unit.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Unit
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($units as $index => $unit): ?>
                                    <div class="unit-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                                        <?php echo htmlspecialchars($unit['title']); ?>
                                                    </h6>
                                                    <?php if (!empty($unit['description'])): ?>
                                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($unit['description'], 0, 100)); ?>...</p>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        Created: <?php echo date('M j, Y', strtotime($unit['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mb-3">
                                                <div class="col-3">
                                                    <div class="text-center">
                                                        <div class="h6 text-info mb-0"><?php echo $unit['question_count']; ?></div>
                                                        <small class="text-muted">Questions</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-center">
                                                        <div class="h6 text-success mb-0"><?php echo $unit['material_count']; ?></div>
                                                        <small class="text-muted">Materials</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-center">
                                                        <div class="h6 text-warning mb-0"><?php echo $unit['attempt_count']; ?></div>
                                                        <small class="text-muted">Attempts</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-center">
                                                        <div class="h6 text-primary mb-0">
                                                            <?php echo $unit['question_count'] > 0 ? 'Active' : 'Draft'; ?>
                                                        </div>
                                                        <small class="text-muted">Status</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="action-buttons">
                                                <a href="../Quiz/create_quiz.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-question-circle me-1"></i>Add Questions
                                                </a>
                                                <a href="../Materials/upload_material.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-upload me-1"></i>Add Materials
                                                </a>
                                                <a href="edit_unit.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                <a href="../Student/unit_content.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Preview
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No Course Selected -->
                    <div class="management-card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
                                <h5>Select a Course</h5>
                                <p class="text-muted">Choose a course from the left panel to manage its content and settings</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectCourse(courseId) {
            window.location.href = `course_management.php?course_id=${courseId}`;
        }
    </script>
</body>
</html>
