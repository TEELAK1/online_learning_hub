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

if ($quiz_id <= 0) {
    header("Location: ../Student/student_dashboard.php");
    exit();
}

// Get quiz details and verify access
$quizStmt = $db->prepare("
    SELECT 
        q.*,
        c.title as course_title,
        c.course_id,
        i.name as instructor_name,
        e.enrollment_id
    FROM quizzes q
    INNER JOIN courses c ON q.course_id = c.course_id
    INNER JOIN instructor i ON c.instructor_id = i.instructor_id
    INNER JOIN enrollments e ON c.course_id = e.course_id
    WHERE q.quiz_id = ? AND e.student_id = ? AND e.status = 'active' AND q.status = 'active'
");

$quizStmt->bind_param("ii", $quiz_id, $student_id);
$quizStmt->execute();
$quiz = $quizStmt->get_result()->fetch_assoc();

if (!$quiz) {
    header("Location: ../Student/student_dashboard.php");
    exit();
}

// Check attempt count
$attemptStmt = $db->prepare("
    SELECT COUNT(*) as attempt_count, MAX(score) as best_score
    FROM quiz_results 
    WHERE quiz_id = ? AND student_id = ?
");
$attemptStmt->bind_param("ii", $quiz_id, $student_id);
$attemptStmt->execute();
$attemptData = $attemptStmt->get_result()->fetch_assoc();

$attempt_count = $attemptData['attempt_count'];
$best_score = $attemptData['best_score'] ?? 0;

// Check if max attempts reached
if ($attempt_count >= $quiz['max_attempts'] && $best_score < $quiz['passing_score']) {
    $max_attempts_reached = true;
} else {
    $max_attempts_reached = false;
}

// Get quiz questions
$questionsStmt = $db->prepare("
    SELECT * FROM quiz_questions 
    WHERE quiz_id = ? 
    ORDER BY order_index ASC, question_id ASC
");
$questionsStmt->bind_param("i", $quiz_id);
$questionsStmt->execute();
$questions = $questionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    if ($max_attempts_reached) {
        $error = "Maximum attempts reached for this quiz.";
    } else {
        $answers = $_POST['answers'] ?? [];
        $score = 0;
        $total_questions = count($questions);
        
        foreach ($questions as $question) {
            $student_answer = $answers[$question['question_id']] ?? '';
            if (strtoupper($student_answer) === strtoupper($question['correct_answer'])) {
                $score++;
            }
        }
        
        $score_percentage = $total_questions > 0 ? ($score / $total_questions) * 100 : 0;
        
        // Save quiz result
        $resultStmt = $db->prepare("
            INSERT INTO quiz_results (quiz_id, student_id, score, taken_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $resultStmt->bind_param("iid", $quiz_id, $student_id, $score_percentage);
        
        if ($resultStmt->execute()) {
            header("Location: quiz_result.php?quiz_id=$quiz_id&score=" . urlencode($score_percentage));
            exit();
        } else {
            $error = "Failed to save quiz results. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .quiz-header {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f59e0b 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .quiz-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .question-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .option-label {
            cursor: pointer;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .option-label:hover {
            background-color: #f3f4f6;
        }
        
        .option-label.selected {
            background-color: #dbeafe;
            border-color: var(--primary-color);
        }
        
        .timer-card {
            position: sticky;
            top: 20px;
            background: var(--danger-color);
            color: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }
        
        .progress-indicator {
            background: #f3f4f6;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar-custom {
            background: var(--primary-color);
            height: 100%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Quiz Header -->
    <div class="quiz-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb" class="mb-2">
                        <ol class="breadcrumb text-white-50">
                            <li class="breadcrumb-item">
                                <a href="../Student/student_dashboard.php" class="text-white-50">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="../Student/enhanced_course_content.php?course_id=<?php echo $quiz['course_id']; ?>" class="text-white-50">
                                    <?php echo htmlspecialchars($quiz['course_title']); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item text-white" aria-current="page">Quiz</li>
                        </ol>
                    </nav>
                    <h1 class="mb-2"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user me-2"></i>Instructor: <?php echo htmlspecialchars($quiz['instructor_name']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../Student/enhanced_course_content.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Course
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($max_attempts_reached): ?>
            <div class="quiz-card text-center">
                <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                <h3 class="text-danger">Maximum Attempts Reached</h3>
                <p class="text-muted">You have reached the maximum number of attempts (<?php echo $quiz['max_attempts']; ?>) for this quiz.</p>
                <p class="text-muted">Your best score: <?php echo number_format($best_score, 1); ?>%</p>
                <a href="../Student/enhanced_course_content.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Course
                </a>
            </div>
        <?php elseif (empty($questions)): ?>
            <div class="quiz-card text-center">
                <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">No Questions Available</h3>
                <p class="text-muted">This quiz doesn't have any questions yet.</p>
                <a href="../Student/enhanced_course_content.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Course
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <!-- Quiz Info -->
                    <div class="quiz-card">
                        <h4><i class="fas fa-info-circle me-2 text-primary"></i>Quiz Information</h4>
                        <?php if ($quiz['description']): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Questions:</strong> <?php echo count($questions); ?>
                            </div>
                            <?php if ($quiz['time_limit_minutes']): ?>
                                <div class="col-md-3">
                                    <strong>Time Limit:</strong> <?php echo $quiz['time_limit_minutes']; ?> min
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%
                            </div>
                            <div class="col-md-3">
                                <strong>Attempts:</strong> <?php echo $attempt_count; ?>/<?php echo $quiz['max_attempts']; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quiz Form -->
                    <form method="POST" id="quizForm">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question-card">
                                <h5 class="mb-3">
                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </h5>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <div class="row">
                                        <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                                            <?php $option_text = $question['option_' . strtolower($option)]; ?>
                                            <?php if ($option_text): ?>
                                                <div class="col-md-6 mb-2">
                                                    <label class="option-label w-100" for="q<?php echo $question['question_id']; ?>_<?php echo $option; ?>">
                                                        <input type="radio" 
                                                               name="answers[<?php echo $question['question_id']; ?>]" 
                                                               value="<?php echo $option; ?>"
                                                               id="q<?php echo $question['question_id']; ?>_<?php echo $option; ?>"
                                                               class="me-2" required>
                                                        <strong><?php echo $option; ?>.</strong> <?php echo htmlspecialchars($option_text); ?>
                                                    </label>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="option-label w-100" for="q<?php echo $question['question_id']; ?>_true">
                                                <input type="radio" 
                                                       name="answers[<?php echo $question['question_id']; ?>]" 
                                                       value="True"
                                                       id="q<?php echo $question['question_id']; ?>_true"
                                                       class="me-2" required>
                                                <strong>True</strong>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="option-label w-100" for="q<?php echo $question['question_id']; ?>_false">
                                                <input type="radio" 
                                                       name="answers[<?php echo $question['question_id']; ?>]" 
                                                       value="False"
                                                       id="q<?php echo $question['question_id']; ?>_false"
                                                       class="me-2" required>
                                                <strong>False</strong>
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center">
                            <button type="submit" name="submit_quiz" class="btn btn-warning btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Quiz
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <!-- Timer (if applicable) -->
                    <?php if ($quiz['time_limit_minutes']): ?>
                        <div class="timer-card mb-4">
                            <h5><i class="fas fa-clock me-2"></i>Time Remaining</h5>
                            <div id="timer" class="h3 mb-0"><?php echo $quiz['time_limit_minutes']; ?>:00</div>
                        </div>
                    <?php endif; ?>

                    <!-- Progress -->
                    <div class="quiz-card">
                        <h6><i class="fas fa-chart-line me-2"></i>Progress</h6>
                        <div class="progress-indicator mb-2">
                            <div class="progress-bar-custom" id="progressBar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">
                            <span id="answeredCount">0</span> of <?php echo count($questions); ?> questions answered
                        </small>
                    </div>

                    <!-- Previous Attempts -->
                    <?php if ($attempt_count > 0): ?>
                        <div class="quiz-card">
                            <h6><i class="fas fa-history me-2"></i>Previous Attempts</h6>
                            <p class="mb-1">Attempts: <?php echo $attempt_count; ?>/<?php echo $quiz['max_attempts']; ?></p>
                            <p class="mb-0">Best Score: <strong><?php echo number_format($best_score, 1); ?>%</strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('quizForm');
            const progressBar = document.getElementById('progressBar');
            const answeredCount = document.getElementById('answeredCount');
            const totalQuestions = <?php echo count($questions); ?>;
            
            // Handle option selection styling
            const radioInputs = document.querySelectorAll('input[type="radio"]');
            radioInputs.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remove selected class from all options in this question
                    const questionName = this.name;
                    const questionOptions = document.querySelectorAll(`input[name="${questionName}"]`);
                    questionOptions.forEach(option => {
                        option.closest('.option-label').classList.remove('selected');
                    });
                    
                    // Add selected class to chosen option
                    this.closest('.option-label').classList.add('selected');
                    
                    // Update progress
                    updateProgress();
                });
            });
            
            function updateProgress() {
                const answeredQuestions = new Set();
                radioInputs.forEach(radio => {
                    if (radio.checked) {
                        answeredQuestions.add(radio.name);
                    }
                });
                
                const answered = answeredQuestions.size;
                const percentage = (answered / totalQuestions) * 100;
                
                progressBar.style.width = percentage + '%';
                answeredCount.textContent = answered;
            }
            
            // Timer functionality
            <?php if ($quiz['time_limit_minutes']): ?>
                let timeLeft = <?php echo $quiz['time_limit_minutes'] * 60; ?>; // Convert to seconds
                const timerElement = document.getElementById('timer');
                
                const timer = setInterval(function() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    
                    timerElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        alert('Time is up! The quiz will be submitted automatically.');
                        form.submit();
                    }
                    
                    timeLeft--;
                }, 1000);
            <?php endif; ?>
            
            // Form submission confirmation
            form.addEventListener('submit', function(e) {
                const answeredQuestions = new Set();
                radioInputs.forEach(radio => {
                    if (radio.checked) {
                        answeredQuestions.add(radio.name);
                    }
                });
                
                if (answeredQuestions.size < totalQuestions) {
                    const unanswered = totalQuestions - answeredQuestions.size;
                    if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>
