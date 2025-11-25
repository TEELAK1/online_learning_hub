<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

// Fetch all quizzes for courses the student is enrolled in
$query = "
    SELECT 
        q.quiz_id, 
        q.title AS quiz_title, 
        q.description,
        q.time_limit_minutes,
        q.passing_score,
        c.title AS course_title,
        c.course_id,
        (SELECT score FROM quiz_attempts WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY completed_at DESC LIMIT 1) as last_score,
        (SELECT passed FROM quiz_attempts WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY completed_at DESC LIMIT 1) as last_passed,
        (SELECT completed_at FROM quiz_attempts WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY completed_at DESC LIMIT 1) as last_attempt_date
    FROM quizzes q
    JOIN enrollments e ON q.course_id = e.course_id
    JOIN courses c ON q.course_id = c.course_id
    WHERE e.student_id = ? 
    AND e.status IN ('active', 'completed') 
    AND q.status = 'active'
    ORDER BY c.title ASC, q.title ASC
";

$stmt = $db->prepare($query);
$stmt->bind_param("iiii", $student_id, $student_id, $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quizzes - Online Learning Hub</title>
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .quiz-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .quiz-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .quiz-card .card-body {
            flex: 1;
            padding: 1.5rem;
        }
        
        .quiz-status {
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        
        .status-passed {
            background-color: #dcfce7;
            color: var(--success-color);
        }
        
        .status-failed {
            background-color: #fee2e2;
            color: var(--danger-color);
        }
        
        .status-pending {
            background-color: #f3f4f6;
            color: var(--text-secondary);
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="fas fa-clipboard-list me-3"></i>My Quizzes</h1>
                    <p class="mb-0 opacity-75">View and attempt quizzes for your enrolled courses</p>
                </div>
                <a href="student_assessment.php" class="btn btn-light text-primary fw-bold">
                    <i class="fas fa-tasks me-2"></i>View Assessments
                </a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($quiz = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="quiz-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-light text-dark border">
                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quiz['course_title']); ?>
                                    </span>
                                    <?php if ($quiz['last_attempt_date']): ?>
                                        <?php if ($quiz['last_passed']): ?>
                                            <span class="quiz-status status-passed">
                                                <i class="fas fa-check-circle me-1"></i>Passed
                                            </span>
                                        <?php else: ?>
                                            <span class="quiz-status status-failed">
                                                <i class="fas fa-times-circle me-1"></i>Failed
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="quiz-status status-pending">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title mb-2"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h5>
                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
                                    <span><i class="fas fa-hourglass-half me-1"></i><?php echo $quiz['time_limit_minutes']; ?> mins</span>
                                    <span><i class="fas fa-star me-1"></i>Pass: <?php echo $quiz['passing_score']; ?>%</span>
                                </div>
                                
                                <?php if ($quiz['last_attempt_date']): ?>
                                    <div class="mb-3 p-2 bg-light rounded small">
                                        <div class="d-flex justify-content-between">
                                            <span>Last Score:</span>
                                            <strong><?php echo $quiz['last_score']; ?>%</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted mt-1">
                                            <span>Date:</span>
                                            <span><?php echo date('M d, Y', strtotime($quiz['last_attempt_date'])); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-3">
                                <a href="take_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary w-100">
                                    <?php echo $quiz['last_attempt_date'] ? '<i class="fas fa-redo me-2"></i>Retake Quiz' : '<i class="fas fa-play me-2"></i>Start Quiz'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-clipboard-check fa-4x text-muted opacity-50"></i>
                </div>
                <h3>No Quizzes Available</h3>
                <p class="text-muted">You don't have any quizzes available for your enrolled courses yet.</p>
                <a href="../Courses/courses.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>Browse Courses
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
