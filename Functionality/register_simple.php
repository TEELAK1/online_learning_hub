<?php
session_start();

// Simple registration that works with current database
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearninghub_new"; // Updated database name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Check if email already exists
        $check_email = false;
        
        // Check in student table
        $stmt = $conn->prepare("SELECT id FROM student WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $check_email = true;
            }
            $stmt->close();
        }
        
        // Check in instructor table if not found in student
        if (!$check_email) {
            $stmt = $conn->prepare("SELECT id FROM instructor WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $check_email = true;
                }
                $stmt->close();
            }
        }
        
        if ($check_email) {
            $message = "This email address is already registered.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into appropriate table
            if ($role === 'student') {
                $stmt = $conn->prepare("INSERT INTO student (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            } elseif ($role === 'instructor') {
                $stmt = $conn->prepare("INSERT INTO instructor (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            } else {
                $message = "Invalid role selected.";
            }
            
            if (isset($stmt) && $stmt) {
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Registration successful! You can now login.";
                    // Redirect after 2 seconds
                    header("refresh:2;url=login.php?registered=success");
                } else {
                    $message = "Registration failed: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Online Learning Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      font-family: 'Inter', sans-serif;
    }
    
    .register-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 500px;
      width: 100%;
    }
    
    .register-header {
      background: #2563eb;
      color: white;
      padding: 2rem;
      text-align: center;
    }
    
    .form-control, .form-select {
      border-radius: 8px;
      border: 2px solid #e5e7eb;
      padding: 12px 16px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .btn-primary {
      background: #2563eb;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      background: #1e40af;
      transform: translateY(-2px);
    }
    
    .alert {
      border-radius: 8px;
      border: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="register-card">
          <div class="register-header">
            <i class="fas fa-user-plus fa-2x mb-3"></i>
            <h3 class="mb-0">Create Account</h3>
            <p class="mb-0 opacity-75">Join our learning platform</p>
          </div>
          
          <div class="p-4">
            <?php if (!empty($message)): ?>
              <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST">
              <div class="mb-3">
                <label for="name" class="form-label fw-medium">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" 
                       placeholder="Enter your full name" required minlength="2"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
              </div>
              
              <div class="mb-3">
                <label for="email" class="form-label fw-medium">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="Enter your email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Create password" required minlength="6">
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm password" required>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="role" class="form-label fw-medium">Select Role</label>
                <select id="role" name="role" class="form-select" required>
                  <option value="">Choose your role</option>
                  <option value="student" <?php echo ($_POST['role'] ?? '') === 'student' ? 'selected' : ''; ?>>
                    Student - Learn from courses
                  </option>
                  <option value="instructor" <?php echo ($_POST['role'] ?? '') === 'instructor' ? 'selected' : ''; ?>>
                    Instructor - Teach and create courses
                  </option>
                </select>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i>Create Account
              </button>
              
              <div class="text-center">
                <span class="text-muted">Already have an account? </span>
                <a href="login.php" class="text-decoration-none fw-medium">Sign in</a>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Simple client-side validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirm_password');
      
      function validatePasswords() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity('Passwords do not match');
        } else {
          confirmPassword.setCustomValidity('');
        }
      }
      
      password.addEventListener('input', validatePasswords);
      confirmPassword.addEventListener('input', validatePasswords);
    });
  </script>
</body>
</html>
