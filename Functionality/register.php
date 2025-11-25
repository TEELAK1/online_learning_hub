<?php
require_once '../includes/auth.php';

$message = "";
$success = false;

// Try to initialize Auth system
try {
    $auth = new Auth();
} catch (Exception $e) {
    $message = "System temporarily unavailable. Please try again later.";
    error_log("Auth initialization failed: " . $e->getMessage());
    $auth = null;
}

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } elseif (!$auth) {
        $message = "System temporarily unavailable. Please try again later.";
    } else {
        $result = $auth->register($name, $email, $password, $role);
        
        if ($result['success']) {
            header("Location: login.php?registered=success");
            exit();
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
  <title>Register - Online Learning Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2563eb;
      --secondary-color: #1e40af;
      --success-color: #059669;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 20px 0;
    }
    
    .register-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 450px;
      width: 100%;
    }
    
    .register-header {
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
    
    .form-select {
      border-radius: 8px;
      border: 2px solid #e5e7eb;
      padding: 12px 16px;
      transition: all 0.3s ease;
    }
    
    .form-select:focus {
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
    
    .password-strength {
      height: 4px;
      border-radius: 2px;
      margin-top: 8px;
      transition: all 0.3s ease;
    }
    
    .strength-weak { background: #ef4444; }
    .strength-medium { background: #f59e0b; }
    .strength-strong { background: #10b981; }
    
    .role-card {
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      padding: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
    }
    
    .role-card:hover {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.05);
    }
    
    .role-card.selected {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.1);
    }
    
    .role-card input[type="radio"] {
      display: none;
    }
  </style>
</head>
<body>
  <a href="../index.php" class="back-home">
    <i class="fas fa-arrow-left me-2"></i>Back to Home
  </a>
  
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="register-card">
          <div class="register-header">
            <i class="fas fa-user-plus fa-2x mb-3"></i>
            <h3 class="mb-0">Join Our Platform</h3>
            <p class="mb-0 opacity-75">Create your account to start learning</p>
          </div>
          
          <div class="p-4">
            <?php if (!empty($message)): ?>
              <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="name" class="form-label fw-medium">Full Name</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-user text-muted"></i>
                      </span>
                      <input type="text" id="name" name="name" class="form-control border-start-0" 
                             placeholder="Enter your full name" required minlength="2"
                             value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="email" class="form-label fw-medium">Email Address</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-envelope text-muted"></i>
                      </span>
                      <input type="email" id="email" name="email" class="form-control border-start-0" 
                             placeholder="Enter your email" required
                             value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-lock text-muted"></i>
                      </span>
                      <input type="password" id="password" name="password" class="form-control border-start-0" 
                             placeholder="Create password" required minlength="6">
                    </div>
                    <div class="password-strength" id="strengthBar"></div>
                    <small class="text-muted" id="strengthText">Password must be at least 6 characters</small>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-lock text-muted"></i>
                      </span>
                      <input type="password" id="confirm_password" name="confirm_password" class="form-control border-start-0" 
                             placeholder="Confirm password" required>
                    </div>
                    <small class="text-muted" id="matchText"></small>
                  </div>
                </div>
              </div>
              
              <div class="mb-4">
                <label class="form-label fw-medium">Choose Your Role</label>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="role-card" for="student">
                      <input type="radio" id="student" name="role" value="student" required>
                      <i class="fas fa-graduation-cap fa-2x text-primary mb-2 d-block"></i>
                      <h6 class="mb-1">Student</h6>
                      <small class="text-muted">Learn from expert instructors</small>
                    </label>
                  </div>
                  <div class="col-md-6">
                    <label class="role-card" for="instructor">
                      <input type="radio" id="instructor" name="role" value="instructor" required>
                      <i class="fas fa-chalkboard-teacher fa-2x text-success mb-2 d-block"></i>
                      <h6 class="mb-1">Instructor</h6>
                      <small class="text-muted">Share your knowledge</small>
                    </label>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="terms" required>
                  <label class="form-check-label text-muted" for="terms">
                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                  </label>
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                <i class="fas fa-user-plus me-2"></i>Create Account
              </button>
              
              <div class="text-center">
                <span class="text-muted">Already have an account? </span>
                <a href="login.php" class="text-decoration-none fw-medium">Sign in</a>
              </div>
            </form>
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
      const roleCards = document.querySelectorAll('.role-card');
      
      // Role selection
      roleCards.forEach(card => {
        card.addEventListener('click', function() {
          roleCards.forEach(c => c.classList.remove('selected'));
          this.classList.add('selected');
          this.querySelector('input[type="radio"]').checked = true;
        });
      });
      
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
          matchText.textContent = '✓ Passwords match';
          matchText.className = 'text-success';
        } else {
          matchText.textContent = '✗ Passwords do not match';
          matchText.className = 'text-danger';
        }
        
        updateSubmitButton();
      }
      
      function updateSubmitButton() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const role = document.querySelector('input[name="role"]:checked');
        const terms = document.getElementById('terms').checked;
        
        const isValid = password === confirm && 
                       password.length >= 6 && 
                       name.length >= 2 && 
                       email.length > 0 && 
                       role && 
                       terms;
        
        submitBtn.disabled = !isValid;
      }
      
      // Add event listeners for form validation
      document.getElementById('name').addEventListener('input', updateSubmitButton);
      document.getElementById('email').addEventListener('input', updateSubmitButton);
      document.getElementById('terms').addEventListener('change', updateSubmitButton);
      
      // Initial validation
      updateSubmitButton();
    });
  </script>
</body>
</html>
