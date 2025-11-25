<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is authenticated
if (!Auth::isAuthenticated()) {
    header("Location: Functionality/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = "";
$success = false;

// Get user information based on role
$user_info = [];
if ($user_role === 'student') {
    $stmt = $db->prepare("SELECT * FROM student WHERE student_id = ?");
} elseif ($user_role === 'instructor') {
    $stmt = $db->prepare("SELECT * FROM instructor WHERE instructor_id = ?");
} else {
    $stmt = $db->prepare("SELECT * FROM admin WHERE admin_id = ?");
}

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Profile Update
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        if (empty($name) || empty($email)) {
            $message = "Name and email are required.";
        } else {
            // Check if email is already taken by another user
            $emailCheck = $db->prepare("SELECT COUNT(*) as count FROM (
                SELECT email FROM student WHERE email = ? AND student_id != ?
                UNION ALL
                SELECT email FROM instructor WHERE email = ? AND instructor_id != ?
                UNION ALL
                SELECT email FROM admin WHERE email = ? AND admin_id != ?
            ) as all_emails");
            
            $emailCheck->bind_param("sisisi", $email, $user_id, $email, $user_id, $email, $user_id);
            $emailCheck->execute();
            $emailResult = $emailCheck->get_result();
            $emailExists = $emailResult->fetch_assoc()['count'] > 0;
            
            if ($emailExists) {
                $message = "Email address is already in use.";
            } else {
                // Update user information
                if ($user_role === 'student') {
                    $updateStmt = $db->prepare("UPDATE student SET name = ?, email = ?, phone = ?, bio = ? WHERE student_id = ?");
                    $updateStmt->bind_param("ssssi", $name, $email, $phone, $bio, $user_id);
                } elseif ($user_role === 'instructor') {
                    $updateStmt = $db->prepare("UPDATE instructor SET name = ?, email = ?, phone = ?, bio = ? WHERE instructor_id = ?");
                    $updateStmt->bind_param("ssssi", $name, $email, $phone, $bio, $user_id);
                } else {
                    $updateStmt = $db->prepare("UPDATE admin SET name = ?, email = ? WHERE admin_id = ?");
                    $updateStmt->bind_param("ssi", $name, $email, $user_id);
                }
                
                if ($updateStmt && $updateStmt->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $success = true;
                    $message = "Profile updated successfully!";
                    
                    // Refresh user info
                    $user_info['name'] = $name;
                    $user_info['email'] = $email;
                    $user_info['phone'] = $phone;
                    $user_info['bio'] = $bio;
                } else {
                    $message = "Failed to update profile.";
                }
            }
        }
    }
    
    // Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            if (password_verify($current_password, $user_info['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($user_role === 'student') {
                    $updateStmt = $db->prepare("UPDATE student SET password = ? WHERE student_id = ?");
                } elseif ($user_role === 'instructor') {
                    $updateStmt = $db->prepare("UPDATE instructor SET password = ? WHERE instructor_id = ?");
                } else {
                    $updateStmt = $db->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                }
                
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $hashed_password, $user_id);
                    if ($updateStmt->execute()) {
                        $success = true;
                        $message = "Password changed successfully!";
                    } else {
                        $message = "Failed to change password.";
                    }
                }
            } else {
                $message = "Current password is incorrect.";
            }
        }
    }
    
    // Account Deletion
    if (isset($_POST['delete_account'])) {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        
        if ($confirm_delete === 'DELETE') {
            // Delete user account
            if ($user_role === 'student') {
                $deleteStmt = $db->prepare("DELETE FROM student WHERE student_id = ?");
            } elseif ($user_role === 'instructor') {
                $deleteStmt = $db->prepare("DELETE FROM instructor WHERE instructor_id = ?");
            } else {
                $message = "Admin accounts cannot be deleted from this interface.";
            }
            
            if (isset($deleteStmt) && $deleteStmt) {
                $deleteStmt->bind_param("i", $user_id);
                if ($deleteStmt->execute()) {
                    // Destroy session and redirect
                    session_destroy();
                    header("Location: index.php?message=Account deleted successfully");
                    exit();
                } else {
                    $message = "Failed to delete account.";
                }
            }
        } else {
            $message = "Please type 'DELETE' to confirm account deletion.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Online Learning Hub</title>
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
        
        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 30px;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .nav-pills .nav-link {
            border-radius: 8px;
            margin-bottom: 5px;
        }
        
        .nav-pills .nav-link.active {
            background: var(--primary-color);
        }
        
        .danger-zone {
            border: 2px solid var(--danger-color);
            border-radius: 8px;
            padding: 20px;
            background: #fef2f2;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Settings Header -->
        <div class="settings-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-2">Account Settings</h1>
                    <p class="lead mb-0">Manage your profile, security, and account preferences</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?php echo $user_role; ?>../instructor_dashboard.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Navigation -->
            <div class="col-lg-3">
                <div class="card settings-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Settings Menu</h5>
                        <nav class="nav nav-pills flex-column">
                            <a class="nav-link active" data-bs-toggle="pill" href="#profile">
                                <i class="fas fa-user me-2"></i>Profile Information
                            </a>
                            <a class="nav-link" data-bs-toggle="pill" href="#security">
                                <i class="fas fa-lock me-2"></i>Security & Password
                            </a>
                            <a class="nav-link" data-bs-toggle="pill" href="#preferences">
                                <i class="fas fa-cog me-2"></i>Preferences
                            </a>
                            <a class="nav-link" data-bs-toggle="pill" href="#danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Profile Information -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h4 class="mb-0">
                                    <i class="fas fa-user me-2 text-primary"></i>Profile Information
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label fw-semibold">Full Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label fw-semibold">Email Address *</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($user_role !== 'admin'): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                                           value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Account Type</label>
                                                    <input type="text" class="form-control" value="<?php echo ucfirst($user_role); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="bio" class="form-label fw-semibold">Bio</label>
                                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_info['bio'] ?? ''); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Security & Password -->
                    <div class="tab-pane fade" id="security">
                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h4 class="mb-0">
                                    <i class="fas fa-lock me-2 text-primary"></i>Security & Password
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label fw-semibold">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label fw-semibold">New Password *</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                                       minlength="6" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password *</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                       minlength="6" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Password Requirements:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>At least 6 characters long</li>
                                            <li>Include a mix of letters and numbers</li>
                                            <li>Use special characters for better security</li>
                                        </ul>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning btn-lg">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Preferences -->
                    <div class="tab-pane fade" id="preferences">
                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h4 class="mb-0">
                                    <i class="fas fa-cog me-2 text-primary"></i>Preferences
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Notification Settings</h5>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                                            <label class="form-check-label" for="email_notifications">
                                                Email Notifications
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="course_updates" checked>
                                            <label class="form-check-label" for="course_updates">
                                                Course Updates
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="chat_notifications" checked>
                                            <label class="form-check-label" for="chat_notifications">
                                                Chat Notifications
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Display Settings</h5>
                                        <div class="mb-3">
                                            <label for="theme" class="form-label">Theme</label>
                                            <select class="form-select" id="theme">
                                                <option value="light">Light</option>
                                                <option value="dark">Dark</option>
                                                <option value="auto">Auto</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="language" class="form-label">Language</label>
                                            <select class="form-select" id="language">
                                                <option value="en">English</option>
                                                <option value="es">Spanish</option>
                                                <option value="fr">French</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="tab-pane fade" id="danger">
                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h4 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Danger Zone
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($user_role !== 'admin'): ?>
                                    <div class="danger-zone">
                                        <h5 class="text-danger mb-3">Delete Account</h5>
                                        <p class="mb-3">
                                            <strong>Warning:</strong> This action cannot be undone. This will permanently delete your account 
                                            and remove all associated data including courses, progress, and messages.
                                        </p>
                                        
                                        <form method="POST" onsubmit="return confirmDelete()">
                                            <div class="mb-3">
                                                <label for="confirm_delete" class="form-label fw-semibold">
                                                    Type "DELETE" to confirm:
                                                </label>
                                                <input type="text" class="form-control" id="confirm_delete" name="confirm_delete" 
                                                       placeholder="Type DELETE here" required>
                                            </div>
                                            
                                            <button type="submit" name="delete_account" class="btn btn-danger btn-lg">
                                                <i class="fas fa-trash me-2"></i>Delete My Account
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Admin Account:</strong> Admin accounts cannot be deleted from this interface. 
                                        Please contact system administrator for account management.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Confirm account deletion
        function confirmDelete() {
            return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!');
        }
        
        // Save preferences (placeholder)
        document.querySelector('#preferences .btn-primary').addEventListener('click', function() {
            alert('Preferences saved! (This is a demo - actual saving would require backend implementation)');
        });
    </script>
</body>
</html>
