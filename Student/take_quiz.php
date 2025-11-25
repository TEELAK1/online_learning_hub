<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!Auth::isAuthenticated() || !isset($_SESSION['user_id'])) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quiz_id <= 0) {
    header("Location: student_dashboard.php");
    exit();
}

// Fetch quiz details
$quizStmt = $db->prepare(
    "SELECT q.*, c.title as course_title, c.course_id 
     FROM quizzes q
     INNER JOIN courses c ON q.course_id = c.course_id
     WHERE q.quiz_id = ?"
);
if (!$quizStmt) {
    error_log('Quiz prepare failed: ' . $db->error);
    header("Location: student_dashboard.php");
    exit();
}
$quizStmt->bind_param('i', $quiz_id);
if (!$quizStmt->execute()) {
    error_log('Quiz execute failed: ' . $quizStmt->error);
    header("Location: student_dashboard.php");
    exit();
}
$quizRes = $quizStmt->get_result();
if (!$quizRes || $quizRes->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit();
}
$quiz = $quizRes->fetch_assoc();
$quizStmt->close();

// Verify enrollment
$enrollStmt = $db->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ? AND status IN ('active', 'completed')");
$enrollStmt->bind_param('ii', $student_id, $quiz['course_id']);
$enrollStmt->execute();
if ($enrollStmt->get_result()->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit();
}
$enrollStmt->close();

// Fetch questions for this quiz
$questionsStmt = $db->prepare(
    "SELECT * FROM quiz_questions 
     WHERE quiz_id = ? 
     ORDER BY question_id ASC"
);
if (!$questionsStmt) {
    error_log('Questions prepare failed: ' . $db->error);
    header("Location: student_dashboard.php");
    exit();
}
$questionsStmt->bind_param('i', $quiz_id);
$questionsStmt->execute();
$qRes = $questionsStmt->get_result();
$questions = [];
while ($q = $qRes->fetch_assoc()) {
    $questions[] = $q;
}
$questionsStmt->close();

if (empty($questions)) {
    // If no questions, show message instead of redirecting
    $no_questions = true;
} else {
    $no_questions = false;
}

// Handle quiz submission
$submitted = false;
$score = 0;
$total_questions = count($questions);
$correct_count = 0;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz']) && !$no_questions) {
    $submitted = true;
    
    // Calculate score
    foreach ($questions as $q) {
        $qid = 'question_' . $q['question_id'];
        $student_answer = isset($_POST[$qid]) ? trim($_POST[$qid]) : '';
        $correct_answer = $q['correct_answer'];
        $is_correct = false;
        
        // Simple string comparison for now, can be enhanced based on question type
        if (strcasecmp($student_answer, $correct_answer) === 0) {
            $is_correct = true;
            $correct_count++;
        }
        
        $results[$q['question_id']] = [
            'is_correct' => $is_correct,
            'student_answer' => htmlspecialchars($student_answer),
            'correct_answer' => htmlspecialchars($correct_answer),
            'explanation' => '' // Add explanation column if available in DB
        ];
    }
    
    $score_percentage = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 2) : 0;
    $passing_score = isset($quiz['passing_score']) ? $quiz['passing_score'] : 60; // Default passing score
    $passed = $score_percentage >= $passing_score;
    
    // Record attempt
    // Check if table has 'score' or 'score_percentage' column. Assuming 'score' based on previous code.
    // Also check for 'total_questions', 'correct_answers', 'passed'.
    $attemptStmt = $db->prepare(
        "INSERT INTO quiz_attempts (quiz_id, student_id, score, total_questions, correct_answers, passed, completed_at) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    if ($attemptStmt) {
        $passed_int = $passed ? 1 : 0;
        $attemptStmt->bind_param('iidiii', $quiz_id, $student_id, $score_percentage, $total_questions, $correct_count, $passed_int);
        $attemptStmt->execute();
        $attemptStmt->close();
    }
}

// Fetch previous attempts
$attemptsStmt = $db->prepare(
    "SELECT score, total_questions, correct_answers, passed, completed_at 
     FROM quiz_attempts 
     WHERE quiz_id = ? AND student_id = ? 
     ORDER BY completed_at DESC 
     LIMIT 5"
);
$previous_attempts = [];
if ($attemptsStmt) {
    $attemptsStmt->bind_param('ii', $quiz_id, $student_id);
    $attemptsStmt->execute();
    $aRes = $attemptsStmt->get_result();
    while ($att = $aRes->fetch_assoc()) {
        $previous_attempts[] = $att;
    }
    $attemptsStmt->close();
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
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
        }

        .quiz-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .quiz-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .question-item {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }

        .question-item.answered {
            border-left-color: var(--success-color);
        }

        .option-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-item:hover {
            border-color: var(--primary-color);
            background: #f0f7ff;
        }

        .option-item input[type="radio"],
        .option-item input[type="checkbox"] {
            margin-right: 0.75rem;
        }

        .option-item input[type="radio"]:checked + label,
        .option-item input[type="checkbox"]:checked + label {
            font-weight: 600;
            color: var(--primary-color);
        }

        .short-answer {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-family: monospace;
        }

        .results-summary {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .results-summary.failed {
            background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
        }

        .result-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #e5e7eb;
        }

        .result-item.correct {
            border-left-color: var(--success-color);
        }

        .result-item.incorrect {
            border-left-color: var(--danger-color);
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-submit:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .attempt-history {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .attempt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .attempt-row:last-child {
            border-bottom: none;
        }

        .badge-passed {
            background: var(--success-color);
            color: white;
        }

        .badge-failed {
            background: var(--danger-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Quiz Header -->
    <div class="quiz-header">
        <div class="container">
            <a href="my_quizzes.php" class="btn btn-light btn-sm mb-3">
                <i class="fas fa-arrow-left me-2"></i>Back to My Quizzes
            </a>
            <h1 class="mb-2"><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($quiz['course_title']); ?></p>
            <?php if ($quiz['description']): ?>
                <p class="mb-0 opacity-75 small mt-2"><?php echo htmlspecialchars($quiz['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if ($no_questions): ?>
            <div class="quiz-card text-center">
                <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                <h4>No Questions Available</h4>
                <p class="text-muted">This quiz doesn't have any questions yet. Please check back later.</p>
            </div>
        <?php elseif (!$submitted): ?>
            <!-- Quiz Form -->
            <div class="quiz-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Quiz Questions</h4>
                    <div>
                        <span class="badge bg-primary"><?php echo count($questions); ?> Questions</span>
                        <?php if (isset($quiz['time_limit']) && $quiz['time_limit'] > 0): ?>
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-hourglass-end me-1"></i><?php echo $quiz['time_limit']; ?> min
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6><?php echo ($index + 1) . '. ' . htmlspecialchars($q['question']); ?></h6>
                            </div>

                            <?php 
                            $q_type = isset($q['question_type']) ? $q['question_type'] : 'multiple_choice';
                            
                            if ($q_type === 'multiple_choice'): ?>
                                <div class="options">
                                    <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $letter => $field): ?>
                                        <?php if (!empty($q[$field])): ?>
                                            <label class="option-item">
                                                <input type="radio" name="question_<?php echo $q['question_id']; ?>" value="<?php echo $letter; ?>" required>
                                                <label><?php echo htmlspecialchars($q[$field]); ?></label>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($q_type === 'true_false'): ?>
                                <div class="options">
                                    <label class="option-item">
                                        <input type="radio" name="question_<?php echo $q['question_id']; ?>" value="true" required>
                                        <label>True</label>
                                    </label>
                                    <label class="option-item">
                                        <input type="radio" name="question_<?php echo $q['question_id']; ?>" value="false" required>
                                        <label>False</label>
                                    </label>
                                </div>

                            <?php else: // short_answer or long_answer ?>
                                <input type="text" 
                                       name="question_<?php echo $q['question_id']; ?>" 
                                       class="short-answer" 
                                       placeholder="Enter your answer..."
                                       required>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit_quiz" class="btn btn-submit">
                            <i class="fas fa-check-circle me-2"></i>Submit Quiz
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- Results -->
            <div class="results-summary <?php echo !$passed ? 'failed' : ''; ?>">
                <div class="mb-3">
                    <?php if ($passed): ?>
                        <i class="fas fa-trophy fa-4x mb-3"></i>
                        <h2>Congratulations!</h2>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle fa-4x mb-3"></i>
                        <h2>Quiz Completed</h2>
                    <?php endif; ?>
                </div>
                <p class="lead mb-0">Your Score: <strong><?php echo $score_percentage; ?>%</strong></p>
                <p class="mb-0">Passing Score: <?php echo isset($quiz['passing_score']) ? $quiz['passing_score'] : 60; ?>%</p>
                <div class="mt-3">
                    <span class="badge <?php echo $passed ? 'bg-light text-success' : 'bg-light text-danger'; ?> p-2">
                        <?php echo $passed ? '✓ PASSED' : '✗ FAILED'; ?>
                    </span>
                </div>
            </div>

            <!-- Answer Review -->
            <div class="quiz-card">
                <h4 class="mb-3"><i class="fas fa-list-check me-2"></i>Answer Review</h4>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="result-item <?php echo $results[$q['question_id']]['is_correct'] ? 'correct' : 'incorrect'; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">Question <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?></h6>
                                <small class="text-muted">
                                    <?php if ($results[$q['question_id']]['is_correct']): ?>
                                        <i class="fas fa-check text-success me-1"></i>Correct
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger me-1"></i>Incorrect
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="mb-2">
                                <strong>Your Answer:</strong>
                                <div class="text-muted"><?php echo $results[$q['question_id']]['student_answer']; ?></div>
                            </div>
                            <?php if (!$results[$q['question_id']]['is_correct']): ?>
                                <div class="mb-2">
                                    <strong>Correct Answer:</strong>
                                    <div class="text-success"><?php echo $results[$q['question_id']]['correct_answer']; ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Attempt History -->
            <?php if (!empty($previous_attempts)): ?>
                <div class="attempt-history">
                    <h5 class="mb-3"><i class="fas fa-history me-2"></i>Previous Attempts</h5>
                    <?php foreach ($previous_attempts as $att): ?>
                        <div class="attempt-row">
                            <div>
                                <div class="fw-semibold"><?php echo date('M d, Y H:i', strtotime($att['completed_at'])); ?></div>
                                <small class="text-muted"><?php echo $att['correct_answers']; ?>/<?php echo $att['total_questions']; ?> correct</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-0"><?php echo $att['score']; ?>%</div>
                                <span class="badge <?php echo $att['passed'] ? 'badge-passed' : 'badge-failed'; ?>">
                                    <?php echo $att['passed'] ? 'PASSED' : 'FAILED'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="text-center mb-4">
                <a href="my_quizzes.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Quizzes
                </a>
                <a href="take_quiz.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>Retake Quiz
                </a>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
