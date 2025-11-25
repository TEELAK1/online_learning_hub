<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!Auth::isAuthenticated() || !Auth::hasRole('admin')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();

// Create certificates table if not exists
$db->query("CREATE TABLE IF NOT EXISTS certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    certificate_code VARCHAR(100) UNIQUE,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
)");

// Fetch Certificates
$query = "SELECT c.certificate_id, c.certificate_code, c.issued_at, s.name as student_name, co.title as course_title 
          FROM certificates c 
          JOIN student s ON c.student_id = s.student_id 
          JOIN courses co ON c.course_id = co.course_id 
          ORDER BY c.issued_at DESC";
$certificates = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Certificates - OLH Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 1000px; margin-top: 50px; }
        .card { border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-back { text-decoration: none; color: #333; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <a href="AdminDashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <div class="card">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>Issued Certificates</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Issued At</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Fetch Certificates with Student and Course details
                        $query = "SELECT c.certificate_id, c.certificate_code, c.issued_at, c.status,
                                         s.name as student_name, s.email as student_email,
                                         co.title as course_title 
                                  FROM certificates c 
                                  JOIN student s ON c.student_id = s.student_id 
                                  JOIN courses co ON c.course_id = co.course_id 
                                  ORDER BY c.issued_at DESC";
                        
                        $certificates = $db->query($query);

                        if ($certificates && $certificates->num_rows > 0): 
                            while($cert = $certificates->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border">
                                        <?php echo htmlspecialchars($cert['certificate_code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($cert['student_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($cert['student_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-2">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <span class="fw-medium"><?php echo htmlspecialchars($cert['course_title']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($cert['issued_at'])); ?></td>
                                <td>
                                    <?php if($cert['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($cert['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary" title="Download Certificate">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger ms-1" title="Revoke Certificate" onclick="return confirm('Are you sure you want to revoke this certificate?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-certificate fa-3x mb-3 opacity-50"></i>
                                        <p class="mb-0">No certificates have been issued yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
