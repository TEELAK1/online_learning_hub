<?php
require_once '../includes/auth.php';

$auth = new Auth();
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        $result = $auth->resetPassword($token, $password);
        
        if ($result['success']) {
            $success = true;
            $message = $result['message'];
        } else {
            $message = $result['message'];
        }
    }
}

$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Online Learning Hub</title>
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
    
    .password-strength {
      height: 4px;
      border-radius: 2px;
      margin-top: 8px;
      transition: all 0.3s ease;
    }
    
    .strength-weak { background: #ef4444; }
    .strength-medium { background: #f59e0b; }
    .strength-strong { background: #10b981; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="reset-card">
          <div class="reset-header">
            <i class="fas fa-shield-alt fa-2x mb-3"></i>
            <h3 class="mb-0">Reset Password</h3>
            <p class="mb-0 opacity-75">Enter your new password</p>
          </div>
          
          <div class="p-4">
            <?php if (!empty($message)): ?>
              <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="text-center">
                <a href="login.php?reset=success" class="btn btn-primary">
                  <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                </a>
              </div>
            <?php else: ?>
            <form method="POST" id="resetForm">
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
              
              <div class="mb-3">
                <label for="password" class="form-label fw-medium">New Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-lock text-muted"></i>
                  </span>
                  <input type="password" id="password" name="password" class="form-control border-start-0" 
                         placeholder="Enter new password" required minlength="6">
                </div>
                <div class="password-strength" id="strengthBar"></div>
                <small class="text-muted" id="strengthText">Password must be at least 6 characters</small>
              </div>
              
              <div class="mb-3">
                <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-lock text-muted"></i>
                  </span>
                  <input type="password" id="confirm_password" name="confirm_password" class="form-control border-start-0" 
                         placeholder="Confirm new password" required>
                </div>
                <small class="text-muted" id="matchText"></small>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                <i class="fas fa-key me-2"></i>Reset Password
              </button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
              <a href="login.php" class="text-decoration-none fw-medium">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const passwordInput = document.getElementById('password');
      const confirmInput = document.getElementById('confirm_password');
      const strengthBar = document.getElementById('strengthBar');
      const strengthText = document.getElementById('strengthText');
      const matchText = document.getElementById('matchText');
      const submitBtn = document.getElementById('submitBtn');
      
      function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        return strength;
      }
      
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        
        strengthBar.className = 'password-strength';
        
        if (password.length === 0) {
          strengthText.textContent = 'Password must be at least 6 characters';
        } else if (strength <= 2) {
          strengthBar.classList.add('strength-weak');
          strengthText.textContent = 'Weak password';
        } else if (strength <= 3) {
          strengthBar.classList.add('strength-medium');
          strengthText.textContent = 'Medium password';
        } else {
          strengthBar.classList.add('strength-strong');
          strengthText.textContent = 'Strong password';
        }
        
        checkPasswordMatch();
      });
      
      confirmInput.addEventListener('input', checkPasswordMatch);
      
      function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm.length === 0) {
          matchText.textContent = '';
          matchText.className = 'text-muted';
        } else if (password === confirm) {
          matchText.textContent = 'Passwords match';
          matchText.className = 'text-success';
        } else {
          matchText.textContent = 'Passwords do not match';
          matchText.className = 'text-danger';
        }
        
        submitBtn.disabled = password !== confirm || password.length < 6;
      }
    });
  </script>
</body>
</html>
