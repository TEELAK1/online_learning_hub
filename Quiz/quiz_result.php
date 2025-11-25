<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$score = isset($_GET['score']) ? (float)$_GET['score'] : 0;

if ($quiz_id <= 0) {
    header("Location: ../Student/student_dashboard.php");
    exit();
}

// Get quiz details
$quizStmt = $db->prepare("
    SELECT 
        q.*,
        c.title as course_title,
        c.course_id,
        i.name as instructor_name
    FROM quizzes q
    INNER JOIN courses c ON q.course_id = c.course_id
    INNER JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE q.quiz_id = ?
");

$quizStmt->bind_param("i", $quiz_id);
$quizStmt->execute();
$quiz = $quizStmt->get_result()->fetch_assoc();

if (!$quiz) {
    header("Location: ../Student/student_dashboard.php");
    exit();
}

// Get attempt statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        MAX(score) as best_score,
        AVG(score) as average_score
    FROM quiz_results 
    WHERE quiz_id = ? AND student_id = ?
");
$statsStmt->bind_param("ii", $quiz_id, $student_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

$passed = $score >= $quiz['passing_score'];
$is_best_score = $score >= $stats['best_score'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .result-header {
            background: linear-gradient(135deg, <?php echo $passed ? 'var(--success-color)' : 'var(--danger-color)'; ?> 0%, <?php echo $passed ? '#10b981' : '#ef4444'; ?> 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .result-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: -2rem auto 2rem;
            max-width: 600px;
            position: relative;
            z-index: 10;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: <?php echo $passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .badge-new-best {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f59e0b;
            z-index: 1000;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Result Header -->
    <div class="result-header">
        <div class="container">
            <i class="fas <?php echo $passed ? 'fa-trophy' : 'fa-times-circle'; ?> fa-4x mb-3"></i>
            <h1 class="mb-2"><?php echo $passed ? 'Congratulations!' : 'Keep Trying!'; ?></h1>
            <p class="mb-0 opacity-75">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></p>
        </div>
    </div>

    <div class="container">
        <!-- Main Result Card -->
        <div class="result-card text-center">
            <div class="score-circle">
                <?php echo number_format($score, 1); ?>%
            </div>
            
            <h3 class="mb-3">
                <?php if ($passed): ?>
                    <span class="text-success">
                        <i class="fas fa-check-circle me-2"></i>You Passed!
                    </span>
                <?php else: ?>
                    <span class="text-danger">
                        <i class="fas fa-times-circle me-2"></i>Not Quite There
                    </span>
                <?php endif; ?>
            </h3>
            
            <p class="text-muted mb-3">
                <?php if ($passed): ?>
                    Great job! You scored above the passing grade of <?php echo $quiz['passing_score']; ?>%.
                <?php else: ?>
                    You need <?php echo $quiz['passing_score']; ?>% to pass. Don't give up - you can try again!
                <?php endif; ?>
            </p>
            
            <?php if ($is_best_score && $stats['total_attempts'] > 1): ?>
                <div class="mb-3">
                    <span class="badge-new-best">
                        <i class="fas fa-star me-2"></i>New Best Score!
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="row text-center mt-4">
                <div class="col-md-4">
                    <h5 class="text-primary"><?php echo number_format($score, 1); ?>%</h5>
                    <small class="text-muted">Your Score</small>
                </div>
                <div class="col-md-4">
                    <h5 class="text-warning"><?php echo $quiz['passing_score']; ?>%</h5>
                    <small class="text-muted">Passing Score</small>
                </div>
                <div class="col-md-4">
                    <h5 class="text-info"><?php echo $stats['total_attempts']; ?></h5>
                    <small class="text-muted">Total Attempts</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Performance Stats -->
                <div class="stats-card">
                    <h5><i class="fas fa-chart-line me-2 text-primary"></i>Your Performance</h5>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Best Score:</strong></p>
                            <p class="text-success mb-3"><?php echo number_format($stats['best_score'], 1); ?>%</p>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><strong>Average Score:</strong></p>
                            <p class="text-info mb-3"><?php echo number_format($stats['average_score'], 1); ?>%</p>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo ($stats['best_score'] / 100) * 100; ?>%"></div>
                    </div>
                    <small class="text-muted">Progress towards mastery</small>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Quiz Info -->
                <div class="stats-card">
                    <h5><i class="fas fa-info-circle me-2 text-primary"></i>Quiz Information</h5>
                    <p class="mb-2">
                        <strong>Course:</strong> <?php echo htmlspecialchars($quiz['course_title']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Instructor:</strong> <?php echo htmlspecialchars($quiz['instructor_name']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Attempts Remaining:</strong> 
                        <?php echo max(0, $quiz['max_attempts'] - $stats['total_attempts']); ?>
                    </p>
                    <?php if ($quiz['time_limit_minutes']): ?>
                        <p class="mb-0">
                            <strong>Time Limit:</strong> <?php echo $quiz['time_limit_minutes']; ?> minutes
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mb-5">
            <?php if (!$passed && $stats['total_attempts'] < $quiz['max_attempts']): ?>
                <a href="take_quiz.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-warning btn-lg me-3">
                    <i class="fas fa-redo me-2"></i>Try Again
                </a>
            <?php endif; ?>
            
            <a href="../Student/enhanced_course_content.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-book me-2"></i>Back to Course
            </a>
            
            <a href="../Student/student_dashboard.php" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($passed): ?>
    <script>
        // Confetti animation for passing
        function createConfetti() {
            const colors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    confetti.animate([
                        { transform: 'translateY(-100vh) rotate(0deg)', opacity: 1 },
                        { transform: 'translateY(100vh) rotate(360deg)', opacity: 0 }
                    ], {
                        duration: 3000,
                        easing: 'linear'
                    }).onfinish = () => confetti.remove();
                }, i * 100);
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
    <?php endif; ?>
</body>
</html>
