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

// Get completed courses and certificates
$certificatesStmt = $db->prepare("
    SELECT 
        c.course_id,
        c.title as course_title,
        c.description as course_description,
        i.name as instructor_name,
        e.completion_date,
        e.progress_percentage,
        cert.certificate_code,
        cert.issued_at,
        cert.status as cert_status
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    INNER JOIN instructor i ON c.instructor_id = i.instructor_id
    LEFT JOIN certificates cert ON e.student_id = cert.student_id AND e.course_id = cert.course_id
    WHERE e.student_id = ? AND (e.progress_percentage >= 100 OR e.status = 'completed')
    ORDER BY e.completion_date DESC
");

$certificatesStmt->bind_param("i", $student_id);
$certificatesStmt->execute();
$completedCourses = $certificatesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate certificate if not exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_certificate'])) {
    $course_id = (int)$_POST['course_id'];
    
    // Check if certificate already exists
    $checkStmt = $db->prepare("SELECT certificate_id FROM certificates WHERE student_id = ? AND course_id = ?");
    $checkStmt->bind_param("ii", $student_id, $course_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        // Generate unique certificate code
        $certificate_code = 'CERT-' . strtoupper(uniqid());
        
        $insertStmt = $db->prepare("
            INSERT INTO certificates (student_id, course_id, certificate_code, issued_at, status)
            VALUES (?, ?, ?, NOW(), 'active')
        ");
        $insertStmt->bind_param("iis", $student_id, $course_id, $certificate_code);
        
        if ($insertStmt->execute()) {
            header("Location: certificates.php?generated=success");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .certificate-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
        }
        
        .certificate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .certificate-badge {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--warning-color) 0%, #f59e0b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .btn-certificate {
            background: var(--warning-color);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn-certificate:hover {
            background: #b45309;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="student_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i> Online Learning Hub
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($student_name); ?>
                </span>
                <a href="student_dashboard.php" class="btn btn-light btn-sm">
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
                        <h2 class="mb-1">My Certificates</h2>
                        <p class="text-muted mb-0">View and download your course completion certificates</p>
                    </div>
                </div>

                <?php if (isset($_GET['generated']) && $_GET['generated'] === 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Certificate generated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($completedCourses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Certificates Available</h4>
                        <p class="text-muted">Complete courses to earn certificates.</p>
                        <a href="../Courses/courses.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Browse Courses
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($completedCourses as $course): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card certificate-card h-100">
                                    <div class="card-body text-center">
                                        <div class="certificate-badge mx-auto mb-3">
                                            <i class="fas fa-certificate"></i>
                                        </div>
                                        
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($course['course_title']); ?></h5>
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                        </p>
                                        
                                        <div class="mb-3">
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                            </div>
                                            <small class="text-success fw-bold"><?php echo number_format($course['progress_percentage'], 1); ?>% Complete</small>
                                        </div>
                                        
                                        <?php if ($course['completion_date']): ?>
                                            <p class="small text-muted mb-3">
                                                <i class="fas fa-calendar me-1"></i>Completed: <?php echo date('M d, Y', strtotime($course['completion_date'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($course['certificate_code']): ?>
                                            <div class="mb-3">
                                                <span class="badge bg-success mb-2">Certificate Issued</span>
                                                <p class="small text-muted mb-0">Code: <?php echo htmlspecialchars($course['certificate_code']); ?></p>
                                                <p class="small text-muted">Issued: <?php echo date('M d, Y', strtotime($course['issued_at'])); ?></p>
                                            </div>
                                            <a href="download_certificate.php?code=<?php echo urlencode($course['certificate_code']); ?>" 
                                               class="btn btn-certificate">
                                                <i class="fas fa-download me-2"></i>Download Certificate
                                            </a>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                <button type="submit" name="generate_certificate" class="btn btn-certificate">
                                                    <i class="fas fa-certificate me-2"></i>Generate Certificate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
