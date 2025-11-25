<?php
session_start();
require_once '../config/database.php';

// Check instructor login and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Check database schema
function checkQuizSchema($db) {
    $result = $db->query("SHOW COLUMNS FROM quizzes LIKE 'quiz_id'");
    return $result && $result->num_rows > 0;
}

$hasNewSchema = checkQuizSchema($db);
$quizIdField = $hasNewSchema ? 'quiz_id' : 'id';
$courseIdField = $hasNewSchema ? 'course_id' : 'id';

// Fetch all quizzes for courses created by this instructor
try {
    $sql = "
        SELECT q.{$quizIdField} AS quiz_id, q.title AS quiz_title, q.description, q.time_limit, q.created_at, c.title AS course_title,
               (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.{$quizIdField}) as question_count,
               (SELECT COUNT(*) FROM quiz_results WHERE quiz_id = q.{$quizIdField}) as attempt_count
        FROM quizzes q
        JOIN courses c ON q.course_id = c.{$courseIdField}
        WHERE c.instructor_id = ?
        ORDER BY q.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        $quizzes = [];
    } else {
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quizzes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    $quizzes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Quizzes - Online Learning Hub</title>
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
    
    .quiz-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      margin-bottom: 2rem;
      transition: all 0.3s ease;
    }
    
    .quiz-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
    
    .welcome-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }
    
    .stat-badge {
      background: rgba(37, 99, 235, 0.1);
      color: var(--primary-color);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
    }
    
    .quiz-meta {
      background: #f8fafc;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="../index.php">
        <i class="fas fa-graduation-cap me-2"></i> Online Learning Hub
      </a>
      <div class="d-flex align-items-center">
        <a href="../Instructor/instructor_dashboard.php" class="btn btn-outline-primary me-3">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <div class="dropdown">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($instructor_name); ?>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
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
  <div class="welcome-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="mb-2">Quiz Management 📋</h1>
          <p class="mb-0 opacity-75">View and manage all your course quizzes</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="create_quiz.php" class="btn btn-light btn-lg">
            <i class="fas fa-plus me-2"></i>Create New Quiz
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <?php if (empty($quizzes)): ?>
      <div class="quiz-card">
        <div class="card-body text-center py-5">
          <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
          <h4>No Quizzes Found</h4>
          <p class="text-muted">You haven't created any quizzes yet. Create your first quiz to get started.</p>
          <a href="create_quiz.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Your First Quiz
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($quizzes as $quiz): ?>
          <div class="col-lg-4 col-md-6">
            <div class="quiz-card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="card-title mb-0"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h5>
                  <span class="stat-badge"><?php echo $quiz['question_count']; ?> Q</span>
                </div>
                
                <p class="text-muted mb-3">
                  <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quiz['course_title']); ?>
                </p>
                
                <?php if (!empty($quiz['description'])): ?>
                  <p class="card-text mb-3"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
                
                <div class="quiz-meta">
                  <div class="row text-center">
                    <div class="col-4">
                      <div class="fw-bold text-primary"><?php echo $quiz['question_count']; ?></div>
                      <small class="text-muted">Questions</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-success"><?php echo $quiz['attempt_count']; ?></div>
                      <small class="text-muted">Attempts</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-warning"><?php echo $quiz['time_limit'] ?? 30; ?>m</div>
                      <small class="text-muted">Duration</small>
                    </div>
                  </div>
                </div>
                
                <small class="text-muted">
                  <i class="fas fa-calendar me-1"></i>
                  Created: <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?>
                </small>
              </div>
              
              <div class="card-footer bg-transparent">
                <div class="btn-group w-100" role="group">
                  <a href="../Quiz/quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                     class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-eye me-1"></i>Preview
                  </a>
                  <a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                     class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                  </a>
                  <a href="quiz_results.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                     class="btn btn-outline-success btn-sm">
                    <i class="fas fa-chart-bar me-1"></i>Results
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
