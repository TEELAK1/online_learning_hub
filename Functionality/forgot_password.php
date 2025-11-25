<?php
require_once '../includes/auth.php';

$auth = new Auth();
$message = "";
$success = false;
$resetToken = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $result = $auth->generatePasswordResetToken($email);
        
        if ($result['success']) {
            $success = true;
            $resetToken = $result['token'];
            $resetLink = "reset_password.php?token=" . $resetToken;
            $message = "If an account with that email exists, we've sent you a password reset link. For demo purposes, click the button below or use this link: " . $resetLink;
        } else {
            $message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Online Learning Hub</title>
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
    
    .reset-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 400px;
      width: 100%;
    }
    
    .reset-header {
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
    
    .alert {
      border-radius: 8px;
      border: none;
    }
    
    .back-login {
      position: absolute;
      top: 20px;
      left: 20px;
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    
    .back-login:hover {
      color: #e5e7eb;
    }
  </style>
</head>
<body>
  <a href="login.php" class="back-login">
    <i class="fas fa-arrow-left me-2"></i>Back to Login
  </a>
  
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="reset-card">
          <div class="reset-header">
            <i class="fas fa-key fa-2x mb-3"></i>
            <h3 class="mb-0">Forgot Password?</h3>
            <p class="mb-0 opacity-75">Enter your email to reset your password</p>
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
                <label for="email" class="form-label fw-medium">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-envelope text-muted"></i>
                  </span>
                  <input type="email" id="email" name="email" class="form-control border-start-0" 
                         placeholder="Enter your email address" required 
                         value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
              </button>
            </form>
            <?php else: ?>
              <div class="text-center">
                <a href="reset_password.php?token=<?php echo htmlspecialchars($resetToken); ?>" class="btn btn-primary">
                  <i class="fas fa-key me-2"></i>Reset Password Now
                </a>
              </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
              <span class="text-muted">Remember your password? </span>
              <a href="login.php" class="text-decoration-none fw-medium">Sign in</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
