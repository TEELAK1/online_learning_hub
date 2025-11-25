<?php
require_once '../includes/auth.php';

$auth = new Auth();
$error = "";
$success = "";

// Check if user is already logged in
if (Auth::isAuthenticated()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'student':
            header("Location: ../Student/student_dashboard.php");
            break;
        case 'instructor':
            header("Location: ../Instructor/instructor_dashboard.php");
            break;
        case 'admin':
            header("Location: ../Admin/AdminDashboard.php");
            break;
    }
    exit();
}

// Handle registration success message
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = "Registration successful! Please login with your credentials.";
}

// Handle password reset success
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Password reset successful! Please login with your new password.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Online Learning Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2563eb;
      --secondary-color: #1e40af;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    
    .login-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 400px;
      width: 100%;
    }
    
    .login-header {
      background: var(--primary-color);
      color: white;
      padding: 2rem;
      text-align: center;
    }
    
    .form-control {
      border-radius: 8px;
      border: 2px solid #e5e7eb;
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
      padding: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      background: var(--secondary-color);
      transform: translateY(-2px);
    }
    
    .btn-outline-primary {
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
      border-radius: 8px;
      padding: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }
    
    .alert {
      border-radius: 8px;
      border: none;
    }
    
    .back-home {
      position: absolute;
      top: 20px;
      left: 20px;
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    
    .back-home:hover {
      color: #e5e7eb;
    }
  </style>
</head>
<body>
  <a href="../index.php" class="back-home">
    <i class="fas fa-arrow-left me-2"></i>Back to Home
  </a>
  
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="login-card">
          <div class="login-header">
            <i class="fas fa-graduation-cap fa-2x mb-3"></i>
            <h3 class="mb-0">Welcome Back</h3>
            <p class="mb-0 opacity-75">Sign in to your account</p>
          </div>
          
          <div class="p-4">
            <?php if (!empty($success)): ?>
              <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
              </div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label for="email" class="form-label fw-medium">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-envelope text-muted"></i>
                  </span>
                  <input type="email" id="email" name="email" class="form-control border-start-0" 
                         placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
              </div>
              
              <div class="mb-3">
                <label for="password" class="form-label fw-medium">Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-lock text-muted"></i>
                  </span>
                  <input type="password" id="password" name="password" class="form-control border-start-0" 
                         placeholder="Enter your password" required>
                </div>
              </div>
              
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember">
                  <label class="form-check-label text-muted" for="remember">
                    Remember me
                  </label>
                </div>
                <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
              </button>
              
              <div class="text-center">
                <span class="text-muted">Don't have an account? </span>
                <a href="register.php" class="text-decoration-none fw-medium">Sign up</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
