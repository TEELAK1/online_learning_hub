<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$message = "";
$success = false;

// Get current student data
$stmt = $db->prepare("SELECT * FROM student WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email)) {
        $message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        try {
            // Check if email is already taken by another user
            $emailCheck = $db->prepare("SELECT student_id FROM student WHERE email = ? AND student_id != ?");
            $emailCheck->bind_param("si", $email, $student_id);
            $emailCheck->execute();
            if ($emailCheck->get_result()->num_rows > 0) {
                $message = "Email address is already taken.";
            } else {
                // Update basic info
                $updateStmt = $db->prepare("UPDATE student SET name = ?, email = ?, phone = ?, address = ? WHERE student_id = ?");
                $updateStmt->bind_param("ssssi", $name, $email, $phone, $address, $student_id);
                
                if ($updateStmt->execute()) {
                    $_SESSION['name'] = $name; // Update session
                    
                    // Handle password change if requested
                    if (!empty($current_password) && !empty($new_password)) {
                        if ($new_password !== $confirm_password) {
                            $message = "New passwords do not match.";
                        } elseif (strlen($new_password) < 6) {
                            $message = "New password must be at least 6 characters long.";
                        } elseif (password_verify($current_password, $student['password'])) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $passwordStmt = $db->prepare("UPDATE student SET password = ? WHERE student_id = ?");
                            $passwordStmt->bind_param("si", $hashed_password, $student_id);
                            
                            if ($passwordStmt->execute()) {
                                $success = true;
                                $message = "Profile and password updated successfully!";
                            } else {
                                $message = "Profile updated but password change failed.";
                            }
                        } else {
                            $message = "Current password is incorrect.";
                        }
                    } else {
                        $success = true;
                        $message = "Profile updated successfully!";
                    }
                    
                    // Refresh student data
                    $stmt->execute();
                    $student = $stmt->get_result()->fetch_assoc();
                } else {
                    $message = "Failed to update profile.";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Online Learning Hub</title>
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
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px 12px 0 0;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
        
        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="student_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i> Online Learning Hub
            </a>
            <div class="d-flex align-items-center">
                <a href="student_dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p class="mb-0 opacity-75">Student Profile</p>
                    </div>
                    
                    <!-- Profile Form -->
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> mb-4">
                                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label fw-medium">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-medium">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label fw-medium">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="address" class="form-label fw-medium">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" 
                                               value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">
                                <i class="fas fa-lock me-2 text-warning"></i>Change Password
                            </h5>
                            <p class="text-muted mb-3">Leave password fields empty if you don't want to change your password.</p>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label fw-medium">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label fw-medium">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label fw-medium">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="student_dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
