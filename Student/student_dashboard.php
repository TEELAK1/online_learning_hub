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

// Fetch student statistics
$stats = [];

// Total enrolled courses
$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE student_id = ? AND status IN ('active', 'completed')");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_courses'] = $result->fetch_assoc()['total'];

// Completed courses (assuming completion when progress = 100 or status = 'completed')
$stmt = $db->prepare("SELECT COUNT(*) as completed FROM enrollments WHERE student_id = ? AND (progress_percentage >= 100 OR status = 'completed')");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $stats['completed_courses'] = $result->fetch_assoc()['completed'];
} else {
    $stats['completed_courses'] = 0;
}

// Quiz attempts
$stmt = $db->prepare("SELECT COUNT(*) as attempts FROM quiz_results WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $stats['quiz_attempts'] = $result->fetch_assoc()['attempts'];
} else {
    $stats['quiz_attempts'] = 0;
}

// Average score
$stmt = $db->prepare("SELECT AVG(score) as avg_score FROM quiz_results WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $avg_result = $result->fetch_assoc();
    $stats['avg_score'] = $avg_result['avg_score'] ? round($avg_result['avg_score'], 1) : 0;
} else {
    $stats['avg_score'] = 0;
}

// Fetch enrolled courses with progress
$enrolled_query = "
    SELECT c.*, 
           COALESCE(e.progress_percentage, 0) as progress,
           e.enrollment_date,
           i.name as instructor_name
    FROM courses c 
    JOIN enrollments e ON c.course_id = e.course_id 
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE e.student_id = ? AND e.status IN ('active', 'completed')
    ORDER BY e.enrollment_date DESC
";

$enrolled_stmt = $db->prepare($enrolled_query);
if ($enrolled_stmt) {
    $enrolled_stmt->bind_param("i", $student_id);
    $enrolled_stmt->execute();
    $enrolled_courses = $enrolled_stmt->get_result();
} else {
    $enrolled_courses = null;
}

// Fetch available courses (not enrolled)
$available_query = "
    SELECT c.*, i.name as instructor_name,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count
    FROM courses c 
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE c.course_id NOT IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    ) AND c.status = 'published'
    LIMIT 6
";

$available_stmt = $db->prepare($available_query);
if ($available_stmt) {
    $available_stmt->bind_param("i", $student_id);
    $available_stmt->execute();
    $available_courses = $available_stmt->get_result();
} else {
    // Fallback if prepare fails (shouldn't happen)
    $available_courses = $db->query("SELECT * FROM courses WHERE status = 'published' LIMIT 6");
}

// Recent quiz results
$recent_quiz_query = "
    SELECT qr.*, q.title as quiz_title, c.title as course_title
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.quiz_id
    JOIN courses c ON q.course_id = c.course_id
    WHERE qr.student_id = ?
    ORDER BY qr.taken_at DESC
    LIMIT 5
";

$recent_quiz_stmt = $db->prepare($recent_quiz_query);
if ($recent_quiz_stmt) {
    $recent_quiz_stmt->bind_param("i", $student_id);
    $recent_quiz_stmt->execute();
    $recent_quizzes = $recent_quiz_stmt->get_result();
} else {
    $recent_quizzes = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard - Online Learning Hub</title>
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
    
    .dashboard-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 2rem 0;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      transition: all 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      color: var(--text-secondary);
      font-size: 0.875rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .course-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .course-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar {
      height: 8px;
      border-radius: 4px;
    }
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: var(--text-primary);
    }
    
    .btn-primary {
      background: var(--primary-color);
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1rem;
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
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }
    
    .quiz-result {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 0.5rem;
      border-left: 4px solid var(--primary-color);
    }
    
    .score-badge {
      font-weight: 600;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
    }
    
    .navbar-custom {
      background: white;
      box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
      padding: 1rem 0;
    }
    
    .welcome-section {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    @media (max-width: 768px) {
      .dashboard-header {
        padding: 1.5rem 0;
      }
      
      .stat-number {
        font-size: 2rem;
      }
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
        <div class="dropdown">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($student_name); ?>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
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

  <!-- Welcome Section -->
  <div class="container mt-4">
    <div class="welcome-section">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($student_name); ?>! 👋</h1>
          <p class="text-muted mb-0">Continue your learning journey and achieve your goals.</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="../Courses/courses.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Explore Courses
          </a>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
      <div class="col-lg-3 col-md-6">
        <div class="stat-card text-center">
          <div class="stat-number text-primary"><?php echo $stats['total_courses']; ?></div>
          <div class="stat-label">Enrolled Courses</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card text-center">
          <div class="stat-number text-success"><?php echo $stats['completed_courses']; ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card text-center">
          <div class="stat-number text-warning"><?php echo $stats['quiz_attempts']; ?></div>
          <div class="stat-label">Quiz Attempts</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card text-center">
          <div class="stat-number text-info"><?php echo $stats['avg_score']; ?>%</div>
          <div class="stat-label">Average Score</div>
        </div>
      </div>
    </div>

    <!-- My Courses Section -->
    <div class="row">
      <div class="col-lg-8">
        <h2 class="section-title">
          <i class="fas fa-book-open me-2"></i>My Courses
        </h2>
        
        <?php if ($enrolled_courses && $enrolled_courses->num_rows > 0): ?>
          <div class="row g-4 mb-5">
            <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
              <div class="col-md-6">
                <div class="course-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <h5 class="card-title mb-0"><?php echo htmlspecialchars($course['title']); ?></h5>
                      <span class="badge bg-primary"><?php echo round($course['progress']); ?>%</span>
                    </div>
                    
                    <p class="card-text text-muted mb-3">
                      <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...
                    </p>
                    
                    <div class="progress mb-3" style="height: 8px;">
                      <div class="progress-bar" role="progressbar" 
                           style="width: <?php echo $course['progress']; ?>%"
                           aria-valuenow="<?php echo $course['progress']; ?>" 
                           aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown'); ?>
                      </small>
                      <div class="btn-group" role="group">
                        <?php if ($course['progress'] >= 100): ?>
                          <a href="certificate.php?course_id=<?php echo $course['course_id']; ?>" 
                             class="btn btn-success btn-sm">
                            <i class="fas fa-certificate me-1"></i>Certificate
                          </a>
                        <?php else: ?>
                          <a href="enhanced_course_content.php?course_id=<?php echo $course['course_id']; ?>" 
                             class="btn btn-primary btn-sm">
                            <i class="fas fa-play me-1"></i>Continue Learning
                          </a>
                        <?php endif; ?>
                        <a href="course_overview.php?course_id=<?php echo $course['course_id']; ?>" 
                           class="btn btn-outline-info btn-sm">
                          <i class="fas fa-info-circle me-1"></i>Overview
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            <h4>No Enrolled Courses</h4>
            <p class="text-muted">Start your learning journey by enrolling in a course.</p>
            <a href="../Courses/courses.php" class="btn btn-primary">
              <i class="fas fa-search me-2"></i>Browse Courses
            </a>
          </div>
        <?php endif; ?>
        
        <!-- Available Courses -->
        <h2 class="section-title">
          <i class="fas fa-compass me-2"></i>Discover New Courses
        </h2>
        
        <?php if ($available_courses && $available_courses->num_rows > 0): ?>
          <div class="row g-4">
            <?php while ($course = $available_courses->fetch_assoc()): ?>
              <div class="col-md-6">
                <div class="course-card">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                    <p class="card-text text-muted mb-3">
                      <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <small class="text-muted">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown'); ?>
                      </small>
                      <small class="text-muted">
                        <i class="fas fa-users me-1"></i><?php echo $course['enrolled_count'] ?? 0; ?> students
                      </small>
                    </div>
                    
                    <form method="POST" action="../Courses/courses.php" class="d-inline w-100">
                      <input type="hidden" name="enroll" value="<?php echo $course['course_id']; ?>">
                      <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-plus me-2"></i>Enroll Now
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <p class="text-muted">No new courses available at the moment.</p>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Sidebar -->
      <div class="col-lg-4">
        <!-- Recent Quiz Results -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
              <i class="fas fa-chart-line me-2"></i>Recent Quiz Results
            </h6>
          </div>
          <div class="card-body">
            <?php if ($recent_quizzes && $recent_quizzes->num_rows > 0): ?>
              <?php while ($quiz = $recent_quizzes->fetch_assoc()): ?>
                <div class="quiz-result">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h6 class="mb-1"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h6>
                      <small class="text-muted"><?php echo htmlspecialchars($quiz['course_title']); ?></small>
                    </div>
                    <span class="score-badge <?php 
                      $score = $quiz['score'];
                      if ($score >= 80) echo 'bg-success text-white';
                      elseif ($score >= 60) echo 'bg-warning text-dark';
                      else echo 'bg-danger text-white';
                    ?>">
                      <?php echo round($score); ?>%
                    </span>
                  </div>
                  <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo date('M j, Y', strtotime($quiz['taken_at'])); ?>
                  </small>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="text-center py-3">
                <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No quiz attempts yet</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
          <div class="card-header bg-success text-white">
            <h6 class="mb-0">
              <i class="fas fa-bolt me-2"></i>Quick Actions
            </h6>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="../Courses/courses.php" class="btn btn-outline-primary">
                <i class="fas fa-search me-2"></i>Browse All Courses
              </a>
              <a href="../Materials/student_materials.php" class="btn btn-outline-success">
                <i class="fas fa-download me-2"></i>Download Materials
              </a>
              <a href="../portal1.php" class="btn btn-outline-info">
                <i class="fas fa-comments me-2"></i>Discussion Forum
              </a>
              <a href="messages.php" class="btn btn-outline-primary">
                <i class="fas fa-envelope me-2"></i>Private Chat with Instructor
              </a>
              <a href="my_quizzes.php" class="btn btn-outline-danger">
                <i class="fas fa-clipboard-list me-2"></i>My Quizzes
              </a>
              <a href="certificate.php" class="btn btn-outline-warning">
                <i class="fas fa-certificate me-2"></i>View Certificates
              </a>
            </div>
          </div>
        </div>
        
        <!-- Learning Progress -->
        <div class="card">
          <div class="card-header bg-info text-white">
            <h6 class="mb-0">
              <i class="fas fa-trophy me-2"></i>Learning Progress
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <small>Overall Progress</small>
                <small><?php 
                  $overall_progress = $stats['total_courses'] > 0 ? 
                    round(($stats['completed_courses'] / $stats['total_courses']) * 100) : 0;
                  echo $overall_progress;
                ?>%</small>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-info" role="progressbar" 
                     style="width: <?php echo $overall_progress; ?>%"></div>
              </div>
            </div>
            
            <div class="row text-center">
              <div class="col-6">
                <div class="border-end">
                  <div class="h5 mb-0 text-success"><?php echo $stats['completed_courses']; ?></div>
                  <small class="text-muted">Completed</small>
                </div>
              </div>
              <div class="col-6">
                <div class="h5 mb-0 text-primary"><?php echo $stats['total_courses'] - $stats['completed_courses']; ?></div>
                <small class="text-muted">In Progress</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
      // Animate progress bars
      const progressBars = document.querySelectorAll('.progress-bar');
      progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
          bar.style.transition = 'width 1s ease-in-out';
          bar.style.width = width;
        }, 100);
      });
      
      // Add hover effects to stat cards
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    });
  </script>
</body>
</html>
