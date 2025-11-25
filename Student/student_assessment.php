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

// Fetch ONLY assessments (quizzes that contain non-MCQ questions like short_answer or long_answer)
// We identify assessments by checking if they contain questions with types other than 'multiple_choice'
// OR if they were created via the assessment tool (which we can infer by the presence of mixed types)
$query = "
    SELECT DISTINCT
        q.quiz_id, 
        q.title AS quiz_title, 
        q.description,
        q.created_at,
        c.title AS course_title,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count,
        (SELECT score FROM quiz_attempts WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY completed_at DESC LIMIT 1) as last_score,
        (SELECT completed_at FROM quiz_attempts WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY completed_at DESC LIMIT 1) as last_attempt_date
    FROM quizzes q
    JOIN enrollments e ON q.course_id = e.course_id
    JOIN courses c ON q.course_id = c.course_id
    JOIN quiz_questions qq ON q.quiz_id = qq.quiz_id
    WHERE e.student_id = ? 
    AND e.status IN ('active', 'completed') 
    AND qq.question_type IN ('short_answer', 'long_answer')
    ORDER BY q.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->bind_param("iii", $student_id, $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assessments - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7c3aed;
            --secondary-color: #6d28d9;
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
        
        .assessment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border-left: 4px solid var(--primary-color);
        }
        
        .assessment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .assessment-card .card-body {
            flex: 1;
            padding: 1.5rem;
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
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
            </a>
            <div class="d-flex align-items-center">
                <a href="my_quizzes.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($student_name); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item text-danger" href="../Functionality/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="mb-2"><i class="fas fa-tasks me-3"></i>My Assessments</h1>
            <p class="mb-0 opacity-75">Complete assessments assigned by your instructors</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($quiz = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="assessment-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-light text-dark border">
                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quiz['course_title']); ?>
                                    </span>
                                    <?php if ($quiz['last_attempt_date']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle me-1"></i>Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title mb-2"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h5>
                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
                                    <span><i class="fas fa-question-circle me-1"></i><?php echo $quiz['question_count']; ?> Questions</span>
                                    <span><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></span>
                                </div>
                                
                                <?php if ($quiz['last_attempt_date']): ?>
                                    <div class="mb-3 p-2 bg-light rounded small">
                                        <div class="d-flex justify-content-between text-muted mt-1">
                                            <span>Submitted:</span>
                                            <span><?php echo date('M d, Y', strtotime($quiz['last_attempt_date'])); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-3">
                                <a href="take_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary w-100">
                                    <?php echo $quiz['last_attempt_date'] ? '<i class="fas fa-eye me-2"></i>View Submission' : '<i class="fas fa-pen-alt me-2"></i>Start Assessment'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-clipboard-list fa-4x text-muted opacity-50"></i>
                </div>
                <h3>No Assessments Available</h3>
                <p class="text-muted">You don't have any pending assessments at the moment.</p>
                <a href="my_quizzes.php" class="btn btn-outline-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
