<?php
// Check for logout success message
$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Online Learning Hub - Learn Anything, Anytime, Anywhere</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    
    .hero {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 120px 0;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="1000,100 1000,0 0,100"/></svg>');
      background-size: cover;
    }
    .hero-content {
      position: relative;
      z-index: 1;
    }
    .feature-icon {
      font-size: 3rem;
      color: #667eea;
      margin-bottom: 1rem;
    }
    .course-card {
      transition: all 0.3s ease;
      border: none;
      border-radius: 15px;
      overflow: hidden;
    }
    .course-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    .navbar-custom {
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    }
    .btn-hero {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      border: none;
      padding: 15px 40px;
      font-size: 1.1rem;
      border-radius: 50px;
      transition: all 0.3s ease;
    }
    .btn-hero:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(238, 90, 36, 0.3);
    }
    .section-padding {
      padding: 80px 0;
    }
    .feature-card {
      border: none;
      border-radius: 20px;
      padding: 2rem;
      height: 100%;
      transition: all 0.3s ease;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="#">
        <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active text-dark fw-semibold" href="#">
              <i class="fas fa-home me-1"></i>Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="Courses/courses.php">
              <i class="fas fa-book me-1"></i>Courses
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="Functionality/login.php">
              <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="Functionality/register.php">
              <i class="fas fa-user-plus me-1"></i>Register
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <?php if ($logout_success): ?>
  <!-- Logout Success Alert -->
  <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
    <div class="container">
      <i class="fas fa-check-circle me-2"></i>
      <strong>Logged out successfully!</strong> Thank you for using Online Learning Hub.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Hero Section -->
  <section class="hero">
    <div class="container hero-content">
      <h1 class="display-4 fw-bold mb-4">Learn Anything, Anytime, Anywhere</h1>
      <p class="lead fs-5 mb-4">Join thousands of learners and instructors on our modern learning platform</p>
      <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
        <a href="Courses/courses.php" class="btn btn-hero btn-lg text-white">
          <i class="fas fa-search me-2"></i>Browse Courses
        </a>
        <a href="Functionality/register.php" class="btn btn-outline-light btn-lg">
          <i class="fas fa-rocket me-2"></i>Get Started Free
        </a>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold mb-3">Why Choose Learning Hub?</h2>
        <p class="lead text-muted">Discover what makes our platform the best choice for your learning journey</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-chalkboard-teacher feature-icon"></i>
              <h5 class="card-title">Expert Instructors</h5>
              <p class="card-text text-muted">Learn from experienced professionals and industry leaders who bring real-world expertise to every lesson.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-clock feature-icon"></i>
              <h5 class="card-title">Flexible Learning</h5>
              <p class="card-text text-muted">Study at your own pace with 24/7 access to courses, materials, and interactive content.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-certificate feature-icon"></i>
              <h5 class="card-title">Certifications</h5>
              <p class="card-text text-muted">Earn recognized certificates to showcase your achievements and advance your career.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-users feature-icon"></i>
              <h5 class="card-title">Community</h5>
              <p class="card-text text-muted">Join a Discussion community of learners and Instructor which connect with peers from around the world.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-mobile-alt feature-icon"></i>
              <h5 class="card-title">Mobile Friendly</h5>
              <p class="card-text text-muted">Access your courses anywhere, anytime with our fully responsive mobile-friendly Learning platform.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card feature-card text-center">
            <div class="card-body">
              <i class="fas fa-chart-line feature-icon"></i>
              <h5 class="card-title">Progress Tracking</h5>
              <p class="card-text text-muted">Monitor your learning progress with detailed analytics and personalized recommendations.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Popular Courses -->
  <section class="section-padding">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold mb-3">Popular Courses</h2>
        <p class="lead text-muted">Start your learning journey with our most popular courses</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <div class="card course-card h-100">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400&h=250&fit=crop" class="card-img-top" alt="Web Development Course" style="height: 200px; object-fit: cover;">
              <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-success">Popular</span>
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">Full Stack Web Development</h5>
              <p class="card-text flex-grow-1">Master HTML, CSS, JavaScript, React, Node.js and build complete web applications from scratch.</p>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <small class="text-muted"><i class="fas fa-users me-1"></i>1,234 students</small>
                  <br>
                  <small class="text-muted"><i class="fas fa-star text-warning me-1"></i>4.8 (256 reviews)</small>
                </div>
                <a href="Courses/courses.php" class="btn btn-primary">View Course</a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card course-card h-100">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400&h=250&fit=crop" class="card-img-top" alt="Graphic Design Course" style="height: 200px; object-fit: cover;">
              <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-info">New</span>
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">Graphic Design Masterclass</h5>
              <p class="card-text flex-grow-1">Learn Adobe Creative Suite, design principles, and create stunning visual content for digital and print media.</p>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <small class="text-muted"><i class="fas fa-users me-1"></i>892 students</small>
                  <br>
                  <small class="text-muted"><i class="fas fa-star text-warning me-1"></i>4.7 (189 reviews)</small>
                </div>
                <a href="Courses/courses.php" class="btn btn-primary">View Course</a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card course-card h-100">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop" class="card-img-top" alt="Data Science Course" style="height: 200px; object-fit: cover;">
              <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-warning text-dark">Trending</span>
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">Data Science & Analytics</h5>
              <p class="card-text flex-grow-1">Explore Python, machine learning, data visualization, and statistical analysis to become a data scientist.</p>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <small class="text-muted"><i class="fas fa-users me-1"></i>2,156 students</small>
                  <br>
                  <small class="text-muted"><i class="fas fa-star text-warning me-1"></i>4.9 (412 reviews)</small>
                </div>
                <a href="Courses/courses.php" class="btn btn-primary">View Course</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-5">
        <a href="Courses/courses.php" class="btn btn-outline-primary btn-lg">
          <i class="fas fa-th-large me-2"></i>View All Courses
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-red text-bold py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <h5 class="mb-3">
            <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
          </h5>
          <p class="text-muted">Empowering learners worldwide with quality education and innovative learning experiences.</p>
          <div class="d-flex gap-3">
            <a href="#" class="text-light"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-light"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-light"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6 class="mb-3">Quick Links</h6>
          <ul class="list-unstyled">
            <li><a href="Courses/courses.php" class="text-muted text-decoration-none">Courses</a></li>
            <li><a href="Functionality/register.php" class="text-muted text-decoration-none">Sign Up</a></li>
            <li><a href="Functionality/login.php" class="text-muted text-decoration-none">Login</a></li>
            <li><a href="Info/aboutus.php" class="text-muted text-decoration-none">About Us</a></li>
          </ul>
        </div>
        
        <div class="col-lg-2 col-md-6">
          <h6 class="mb-3">Support</h6>
          <ul class="list-unstyled">
            <li><a href="Info/Contract.php" class="text-muted text-decoration-none">Contact Us</a></li>
            <li><a href="Info/privacy_policy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
            <li><a href="Info/terms_of_services.php" class="text-muted text-decoration-none">Terms of Service</a></li>
          </ul>
        
      </div>
      <hr class="my-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <p class="mb-0 text-muted">&copy; 2025 Online Learning Hub. All rights reserved.</p>
        </div>
      
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add smooth scrolling and animations
    document.addEventListener('DOMContentLoaded', function() {
      // Animate feature cards on scroll
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      };

      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, observerOptions);

      // Observe feature cards
      document.querySelectorAll('.feature-card, .course-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
      });

      // Auto-dismiss alerts after 5 seconds
      setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        });
      }, 5000);
    });
  </script>
</body>
</html>
