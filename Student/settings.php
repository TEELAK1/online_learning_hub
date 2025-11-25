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
$message = "";
$success = false;

// Create settings table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS user_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'instructor') NOT NULL,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    language VARCHAR(5) DEFAULT 'en',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id, user_type)
)");

// Fetch current student data
$stmt = $db->prepare("SELECT * FROM student WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $theme_preference = $_POST['theme_preference'] ?? 'light';
    $language = $_POST['language'] ?? 'en';
    
    try {
        // Update or insert settings
        $settingsStmt = $db->prepare("
            INSERT INTO user_settings (user_id, user_type, notifications_enabled, email_notifications, theme_preference, language)
            VALUES (?, 'student', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            notifications_enabled = VALUES(notifications_enabled),
            email_notifications = VALUES(email_notifications),
            theme_preference = VALUES(theme_preference),
            language = VALUES(language),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $settingsStmt->bind_param("iisss", $student_id, $notifications_enabled, $email_notifications, $theme_preference, $language);
        
        if ($settingsStmt->execute()) {
            $success = true;
            $message = "Settings updated successfully!";
        } else {
            $message = "Failed to update settings.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch current settings
$settings = [
    'notifications_enabled' => 1,
    'email_notifications' => 1,
    'theme_preference' => 'light',
    'language' => 'en'
];

try {
    $settingsStmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ? AND user_type = 'student'");
    if ($settingsStmt) {
        $settingsStmt->bind_param("i", $student_id);
        $settingsStmt->execute();
        $result = $settingsStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $settings = $row;
        }
    }
} catch (Exception $e) {
    // Settings table might not exist yet
}

if (!$student) {
    die("Student not found");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            
            if (empty($name) || empty($email)) {
                $message = "Name and email are required.";
            } else {
                $update_query = "UPDATE student SET name = ?, email = ?, phone = ?, bio = ?, updated_at = NOW() WHERE " . 
                               (isset($student['student_id']) ? "student_id" : "id") . " = ?";
                $stmt = $db->prepare($update_query);
                $stmt->bind_param("ssssi", $name, $email, $phone, $bio, $student_id);
                
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Profile updated successfully!";
                    $_SESSION['name'] = $name; // Update session
                } else {
                    $message = "Failed to update profile.";
                }
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $message = "All password fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $message = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $message = "New password must be at least 6 characters long.";
            } elseif (!password_verify($current_password, $student['password'])) {
                $message = "Current password is incorrect.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE student SET password = ?, updated_at = NOW() WHERE " . 
                               (isset($student['student_id']) ? "student_id" : "id") . " = ?";
                $stmt = $db->prepare($update_query);
                $stmt->bind_param("si", $hashed_password, $student_id);
                
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Password changed successfully!";
                } else {
                    $message = "Failed to change password.";
                }
            }
            break;
            
        case 'update_preferences':
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $course_updates = isset($_POST['course_updates']) ? 1 : 0;
            $quiz_reminders = isset($_POST['quiz_reminders']) ? 1 : 0;
            $achievement_notifications = isset($_POST['achievement_notifications']) ? 1 : 0;
            
            // Create or update preferences in a simple way
            $pref_query = "UPDATE student SET 
                          email_notifications = ?, 
                          course_updates = ?, 
                          quiz_reminders = ?, 
                          achievement_notifications = ?,
                          updated_at = NOW() 
                          WHERE " . (isset($student['student_id']) ? "student_id" : "id") . " = ?";
            $stmt = $db->prepare($pref_query);
            $stmt->bind_param("iiiii", $email_notifications, $course_updates, $quiz_reminders, $achievement_notifications, $student_id);
            
            if ($stmt->execute()) {
                $success = true;
                $message = "Preferences updated successfully!";
            } else {
                $message = "Failed to update preferences.";
            }
            break;
    }
    
    // Refresh student data after update
    $stmt = $db->prepare("SELECT * FROM student WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
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
        
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
        
        .setting-section {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
        }
        
        .setting-section:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="../index.php">
        <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
      </a>
      <div class="d-flex align-items-center">
        <a href="student_dashboard.php" class="btn btn-outline-primary me-3">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <div class="dropdown">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../Functionality/logout.php">
              <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="settings-container">
    <!-- Page Header -->
    <div class="settings-card">
      <div class="settings-header">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1 class="mb-0"><i class="fas fa-cog me-3"></i>Account Settings</h1>
            <p class="mb-0 opacity-75">Manage your account preferences and security settings</p>
          </div>
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

    <!-- Settings Content -->
    <div class="settings-card">
      <div class="row">
        <!-- Settings Navigation -->
        <div class="col-md-3">
          <div class="nav flex-column nav-pills settings-nav" id="v-pills-tab" role="tablist">
            <button class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" 
                    data-bs-target="#v-pills-profile" type="button" role="tab">
              <i class="fas fa-user me-2"></i>Profile
            </button>
            <button class="nav-link" id="v-pills-security-tab" data-bs-toggle="pill" 
                    data-bs-target="#v-pills-security" type="button" role="tab">
              <i class="fas fa-shield-alt me-2"></i>Security
            </button>
            <button class="nav-link" id="v-pills-preferences-tab" data-bs-toggle="pill" 
                    data-bs-target="#v-pills-preferences" type="button" role="tab">
              <i class="fas fa-sliders-h me-2"></i>Preferences
            </button>
            <button class="nav-link" id="v-pills-notifications-tab" data-bs-toggle="pill" 
                    data-bs-target="#v-pills-notifications" type="button" role="tab">
              <i class="fas fa-bell me-2"></i>Notifications
            </button>
          </div>
        </div>

        <!-- Settings Content -->
        <div class="col-md-9">
          <div class="tab-content p-4" id="v-pills-tabContent">
            
            <!-- Profile Settings -->
            <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
              <h3 class="section-title">Profile Information</h3>
              
              <div class="text-center mb-4">
                <div class="profile-avatar">
                  <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                </div>
                <button class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-camera me-2"></i>Change Photo
                </button>
              </div>

              <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
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
                      <label for="joined" class="form-label fw-medium">Member Since</label>
                      <input type="text" class="form-control" readonly
                             value="<?php echo date('F Y', strtotime($student['created_at'] ?? 'now')); ?>">
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="bio" class="form-label fw-medium">Bio</label>
                  <textarea class="form-control" id="bio" name="bio" rows="4" 
                            placeholder="Tell us about yourself..."><?php echo htmlspecialchars($student['bio'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Save Changes
                </button>
              </form>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
              <h3 class="section-title">Security Settings</h3>
              
              <div class="row">
                <div class="col-md-8">
                  <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                      <label for="current_password" class="form-label fw-medium">Current Password</label>
                      <input type="password" class="form-control" id="current_password" 
                             name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                      <label for="new_password" class="form-label fw-medium">New Password</label>
                      <input type="password" class="form-control" id="new_password" 
                             name="new_password" required minlength="6">
                      <div class="form-text">Password must be at least 6 characters long</div>
                    </div>
                    
                    <div class="mb-3">
                      <label for="confirm_password" class="form-label fw-medium">Confirm New Password</label>
                      <input type="password" class="form-control" id="confirm_password" 
                             name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                      <i class="fas fa-key me-2"></i>Change Password
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Preferences -->
            <div class="tab-pane fade" id="v-pills-preferences" role="tabpanel">
              <h3 class="section-title">Learning Preferences</h3>
              
              <form method="POST">
                <input type="hidden" name="action" value="update_preferences">
                
                <div class="preference-item">
                  <div>
                    <strong>Dark Mode</strong>
                    <div class="text-muted">Use dark theme for better viewing</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="dark_mode">
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Auto-play Videos</strong>
                    <div class="text-muted">Automatically play course videos</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="auto_play_videos" checked>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Progress Tracking</strong>
                    <div class="text-muted">Track your learning progress</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="progress_tracking" checked>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Course Recommendations</strong>
                    <div class="text-muted">Show personalized course suggestions</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="course_recommendations" checked>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Save Preferences
                </button>
              </form>
            </div>

            <!-- Notifications -->
            <div class="tab-pane fade" id="v-pills-notifications" role="tabpanel">
              <h3 class="section-title">Notification Settings</h3>
              
              <form method="POST">
                <input type="hidden" name="action" value="update_preferences">
                
                <div class="preference-item">
                  <div>
                    <strong>Email Notifications</strong>
                    <div class="text-muted">Receive updates via email</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="email_notifications" 
                           <?php echo ($student['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Course Updates</strong>
                    <div class="text-muted">New lessons and announcements</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="course_updates"
                           <?php echo ($student['course_updates'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Quiz Reminders</strong>
                    <div class="text-muted">Reminders for pending quizzes</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="quiz_reminders"
                           <?php echo ($student['quiz_reminders'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <div class="preference-item">
                  <div>
                    <strong>Achievement Notifications</strong>
                    <div class="text-muted">Celebrate your learning milestones</div>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="achievement_notifications"
                           <?php echo ($student['achievement_notifications'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                  </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Save Notification Settings
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
      const newPassword = document.getElementById('new_password');
      const confirmPassword = document.getElementById('confirm_password');
      
      function validatePasswords() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity('Passwords do not match');
        } else {
          confirmPassword.setCustomValidity('');
        }
      }
      
      if (newPassword && confirmPassword) {
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
      }
    });
  </script>
</body>
</html>
