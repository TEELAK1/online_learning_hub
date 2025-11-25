<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is a student
if (!Auth::isAuthenticated() || !Auth::hasRole('student')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id === 0) {
    header("Location: student_dashboard.php");
    exit();
}

// Verify student is enrolled in this course
$enrollmentCheck = $db->prepare("
    SELECT c.title as course_title, e.enrollment_date
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE c.course_id = ? AND e.student_id = ? AND e.status = 'active'
");

if (!$enrollmentCheck) {
    die("Database error");
}

$enrollmentCheck->bind_param("ii", $course_id, $student_id);
$enrollmentCheck->execute();
$courseResult = $enrollmentCheck->get_result();

if ($courseResult->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit();
}

$course = $courseResult->fetch_assoc();

// Get course materials
$materials = [];
$materialsStmt = $db->prepare("
    SELECT 
        m.*,
        cu.title as unit_title
    FROM materials m
    LEFT JOIN course_units cu ON m.unit_id = cu.unit_id
    WHERE m.course_id = ?
    ORDER BY m.upload_date DESC
");

if ($materialsStmt) {
    $materialsStmt->bind_param("i", $course_id);
    $materialsStmt->execute();
    $result = $materialsStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - <?php echo htmlspecialchars($course['course_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .materials-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .material-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .material-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .file-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .file-pdf { background: #fef2f2; color: #dc2626; }
        .file-doc { background: #eff6ff; color: #2563eb; }
        .file-image { background: #f0fdf4; color: #059669; }
        .file-video { background: #fef3c7; color: #d97706; }
        .file-default { background: #f3f4f6; color: #6b7280; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="materials-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-6 mb-2">Course Materials</h1>
                    <p class="lead mb-0 opacity-75"><?php echo htmlspecialchars($course['course_title']); ?></p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="student_dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php if (empty($materials)): ?>
                    <div class="card material-card">
                        <div class="card-body empty-state">
                            <i class="fas fa-folder-open fa-4x mb-4"></i>
                            <h4>No Materials Available</h4>
                            <p class="mb-0">This course doesn't have any downloadable materials yet. Check back later for updates.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($materials as $material): ?>
                            <?php
                            $fileExtension = strtolower(pathinfo($material['file_name'], PATHINFO_EXTENSION));
                            $iconClass = 'file-default';
                            $iconName = 'fas fa-file';
                            
                            switch ($fileExtension) {
                                case 'pdf':
                                    $iconClass = 'file-pdf';
                                    $iconName = 'fas fa-file-pdf';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $iconClass = 'file-doc';
                                    $iconName = 'fas fa-file-word';
                                    break;
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                case 'gif':
                                    $iconClass = 'file-image';
                                    $iconName = 'fas fa-file-image';
                                    break;
                                case 'mp4':
                                case 'avi':
                                case 'mov':
                                    $iconClass = 'file-video';
                                    $iconName = 'fas fa-file-video';
                                    break;
                                case 'ppt':
                                case 'pptx':
                                    $iconClass = 'file-doc';
                                    $iconName = 'fas fa-file-powerpoint';
                                    break;
                            }
                            ?>
                            
                            <div class="col-md-6 col-lg-4">
                                <div class="card material-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <div class="file-icon <?php echo $iconClass; ?>">
                                                <i class="<?php echo $iconName; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-2"><?php echo htmlspecialchars($material['title'] ?? $material['file_name']); ?></h6>
                                                
                                                <?php if (!empty($material['description'])): ?>
                                                    <p class="card-text text-muted small mb-2">
                                                        <?php echo htmlspecialchars(substr($material['description'], 0, 100)); ?>
                                                        <?php echo strlen($material['description']) > 100 ? '...' : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <small class="text-muted">
                                                        <?php if (!empty($material['unit_title'])): ?>
                                                            <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($material['unit_title']); ?>
                                                        <?php else: ?>
                                                            <i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($material['upload_date'])); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <?php if (isset($material['file_size'])): ?>
                                                            <?php echo number_format($material['file_size'] / 1024, 1); ?> KB
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="d-grid">
                                                    <a href="../Materials/<?php echo htmlspecialchars($material['file_path']); ?>" 
                                                       class="btn btn-primary btn-sm" 
                                                       download="<?php echo htmlspecialchars($material['file_name']); ?>">
                                                        <i class="fas fa-download me-2"></i>Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tools me-2 text-primary"></i>Quick Actions
                        </h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="course_content.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-book me-2"></i>View Course Content
                            </a>
                            <a href="course_overview.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-info-circle me-2"></i>Course Overview
                            </a>
                            <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-home me-2"></i>Dashboard
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
