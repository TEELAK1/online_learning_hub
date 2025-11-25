<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

// Get enrolled courses and their materials
$coursesStmt = $db->prepare("
    SELECT DISTINCT 
        c.course_id,
        c.title as course_title,
        c.description as course_description,
        i.name as instructor_name
    FROM courses c
    INNER JOIN enrollments e ON c.course_id = e.course_id
    INNER JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.title
");

$coursesStmt->bind_param("i", $student_id);
$coursesStmt->execute();
$courses = $coursesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get materials for each course
foreach ($courses as &$course) {
    // First check if materials table exists, if not create it
    $tableCheck = $db->query("SHOW TABLES LIKE 'materials'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        // Create materials table
        $db->query("CREATE TABLE IF NOT EXISTS materials (
            material_id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            unit_id INT NULL,
            lesson_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT,
            file_type VARCHAR(50),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_course (course_id),
            INDEX idx_unit (unit_id),
            INDEX idx_lesson (lesson_id)
        )");
    }
    
    $materialsStmt = $db->prepare("
        SELECT 
            m.*,
            cu.title as unit_title,
            cl.title as lesson_title
        FROM materials m
        LEFT JOIN course_units cu ON m.unit_id = cu.unit_id
        LEFT JOIN course_lessons cl ON m.lesson_id = cl.lesson_id
        WHERE m.course_id = ?
        ORDER BY cu.title, cl.title, m.uploaded_at DESC
    ");
    
    if ($materialsStmt) {
        $materialsStmt->bind_param("i", $course['course_id']);
        $materialsStmt->execute();
        $course['materials'] = $materialsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $materialsStmt->close();
    } else {
        // If prepare fails, set empty materials array
        $course['materials'] = [];
        error_log("Materials query failed: " . $db->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .material-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .material-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .download-btn {
            background: var(--success-color);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .download-btn:hover {
            background: #047857;
            color: white;
            transform: translateY(-1px);
        }
        
        .file-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .file-pdf { background: #dc2626; }
        .file-doc { background: #2563eb; }
        .file-image { background: #059669; }
        .file-video { background: #7c3aed; }
        .file-default { background: #6b7280; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../Student/student_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($student_name); ?>
                </span>
                <a href="../Student/student_dashboard.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Course Materials</h2>
                        <p class="text-muted mb-0">Download materials from your enrolled courses</p>
                    </div>
                </div>

                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Enrolled Courses</h4>
                        <p class="text-muted">You haven't enrolled in any courses yet.</p>
                        <a href="../Courses/courses.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Browse Courses
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="card material-card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($course['course_title']); ?>
                                </h5>
                                <small class="opacity-75">
                                    <i class="fas fa-user me-1"></i>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <?php if (empty($course['materials'])): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No materials available for this course yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($course['materials'] as $material): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start mb-3">
                                                            <div class="file-icon me-3 <?php 
                                                                $ext = strtolower(pathinfo($material['file_name'], PATHINFO_EXTENSION));
                                                                switch($ext) {
                                                                    case 'pdf': echo 'file-pdf'; break;
                                                                    case 'doc': case 'docx': echo 'file-doc'; break;
                                                                    case 'jpg': case 'jpeg': case 'png': case 'gif': echo 'file-image'; break;
                                                                    case 'mp4': case 'avi': case 'mov': echo 'file-video'; break;
                                                                    default: echo 'file-default';
                                                                }
                                                            ?>">
                                                                <i class="fas fa-file"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($material['title']); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php if ($material['unit_title']): ?>
                                                                        Unit: <?php echo htmlspecialchars($material['unit_title']); ?>
                                                                    <?php endif; ?>
                                                                    <?php if ($material['lesson_title']): ?>
                                                                        <br>Lesson: <?php echo htmlspecialchars($material['lesson_title']); ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($material['description']): ?>
                                                            <p class="small text-muted mb-3"><?php echo htmlspecialchars($material['description']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                <?php if ($material['file_size']): ?>
                                                                    <?php echo number_format($material['file_size'] / 1024, 1); ?> KB
                                                                <?php endif; ?>
                                                            </small>
                                                            <a href="download.php?id=<?php echo $material['material_id']; ?>" 
                                                               class="download-btn">
                                                                <i class="fas fa-download me-1"></i>Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
