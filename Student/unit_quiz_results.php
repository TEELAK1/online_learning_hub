<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is a student
if (!Auth::isAuthenticated() || !Auth::hasRole('student')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];

// Get parameters
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

// Get attempt details
$attemptStmt = $db->prepare("
    SELECT ua.*, u.title as unit_title, u.description, c.title as course_title
    FROM unit_attempts ua
    JOIN course_units u ON ua.unit_id = u.unit_id
    JOIN courses c ON u.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE ua.attempt_id = ? AND ua.student_id = ? AND e.student_id = ?
");

if ($attemptStmt) {
    $attemptStmt->bind_param("iii", $attempt_id, $student_id, $student_id);
    $attemptStmt->execute();
    $attemptResult = $attemptStmt->get_result();
    
    if ($attemptResult->num_rows === 0) {
        header("Location: student_dashboard.php");
        exit();
    }
    
    $attempt = $attemptResult->fetch_assoc();
    $attempt['answers'] = json_decode($attempt['answers'], true);
} else {
    header("Location: student_dashboard.php");
    exit();
}

// Get questions with correct answers
$questions = [];
$questionsStmt = $db->prepare("SELECT * FROM unit_questions WHERE unit_id = ? AND is_active = 1 ORDER BY order_index ASC");
if ($questionsStmt) {
    $questionsStmt->bind_param("i", $unit_id);
    $questionsStmt->execute();
    $questionsResult = $questionsStmt->get_result();
    while ($row = $questionsResult->fetch_assoc()) {
        $row['correct_answers'] = json_decode($row['correct_answers'], true);
        $questions[] = $row;
    }
}

// Calculate detailed results
$detailed_results = [];
foreach ($questions as $question) {
    $question_id = $question['question_id'];
    $student_answer = $attempt['answers'][$question_id] ?? [];
    
    if (!is_array($student_answer)) {
        $student_answer = [$student_answer];
    }
    
    // Sort arrays for comparison
    sort($student_answer);
    $correct_answers = $question['correct_answers'];
    sort($correct_answers);
    
    $is_correct = ($student_answer === $correct_answers);
    
    $detailed_results[] = [
        'question' => $question,
        'student_answer' => $student_answer,
        'is_correct' => $is_correct,
        'points_earned' => $is_correct ? $question['points'] : 0
    ];
}

// Get performance grade
function getGrade($percentage) {
    if ($percentage >= 90) return ['grade' => 'A', 'class' => 'success'];
    if ($percentage >= 80) return ['grade' => 'B', 'class' => 'info'];
    if ($percentage >= 70) return ['grade' => 'C', 'class' => 'warning'];
    if ($percentage >= 60) return ['grade' => 'D', 'class' => 'warning'];
    return ['grade' => 'F', 'class' => 'danger'];
}

$grade_info = getGrade($attempt['score_percentage']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($attempt['unit_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
        }
        
        .results-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .score-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            text-align: center;
            padding: 30px;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            font-weight: bold;
            color: white;
        }
        
        .question-result {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .question-header {
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .question-header.correct {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 4px solid var(--success-color);
        }
        
        .question-header.incorrect {
            background: linear-gradient(135deg, #f8d7da, #f1b0b7);
            border-left: 4px solid var(--danger-color);
        }
        
        .option-item {
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .option-correct {
            background: #d4edda;
            border-left: 4px solid var(--success-color);
        }
        
        .option-student {
            background: #fff3cd;
            border-left: 4px solid var(--warning-color);
        }
        
        .option-student.incorrect {
            background: #f8d7da;
            border-left: 4px solid var(--danger-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Results Header -->
        <div class="results-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">Quiz Results</h1>
                    <p class="mb-1 opacity-75">Unit: <?php echo htmlspecialchars($attempt['unit_title']); ?></p>
                    <p class="mb-0 opacity-75">Course: <?php echo htmlspecialchars($attempt['course_title']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="badge bg-light text-dark fs-6">
                        Completed: <?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Score Summary -->
            <div class="col-lg-4 mb-4">
                <div class="score-card">
                    <div class="score-circle bg-<?php echo $grade_info['class']; ?>">
                        <?php echo $attempt['score_percentage']; ?>%
                    </div>
                    <h3 class="mb-2">Grade: <?php echo $grade_info['grade']; ?></h3>
                    <p class="text-muted mb-4">
                        <?php echo $attempt['correct_answers']; ?> out of <?php echo $attempt['total_questions']; ?> correct
                    </p>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <a href="take_unit_quiz.php?unit_id=<?php echo $unit_id; ?>" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Retake Quiz
                        </a>
                        <a href="student_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics -->
            <div class="col-lg-8 mb-4">
                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                        <h4><?php echo $attempt['time_taken_minutes']; ?> min</h4>
                        <p class="text-muted mb-0">Time Taken</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4><?php echo $attempt['correct_answers']; ?></h4>
                        <p class="text-muted mb-0">Correct Answers</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h4><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></h4>
                        <p class="text-muted mb-0">Incorrect Answers</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h4><?php echo array_sum(array_column($detailed_results, 'points_earned')); ?></h4>
                        <p class="text-muted mb-0">Points Earned</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question by Question Review -->
        <div class="mb-4">
            <h3 class="mb-4">
                <i class="fas fa-list-alt me-2 text-primary"></i>Question Review
            </h3>
            
            <?php foreach ($detailed_results as $index => $result): ?>
                <div class="card question-result">
                    <div class="question-header <?php echo $result['is_correct'] ? 'correct' : 'incorrect'; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                    Question <?php echo $index + 1; ?>
                                    <span class="badge bg-secondary ms-2"><?php echo $result['question']['points']; ?> pts</span>
                                </h6>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($result['question']['question_text']); ?></p>
                            </div>
                            <div class="text-end">
                                <?php if ($result['is_correct']): ?>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                    <div class="small text-success mt-1">Correct</div>
                                <?php else: ?>
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    <div class="small text-danger mt-1">Incorrect</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="options">
                            <?php 
                            $options = ['A' => $result['question']['option_a'], 'B' => $result['question']['option_b'], 'C' => $result['question']['option_c'], 'D' => $result['question']['option_d'], 'E' => $result['question']['option_e']];
                            foreach ($options as $letter => $option):
                                if (!empty($option)):
                                    $is_correct_answer = in_array($letter, $result['question']['correct_answers']);
                                    $is_student_answer = in_array($letter, $result['student_answer']);
                                    
                                    $class = '';
                                    $icon = '';
                                    
                                    if ($is_correct_answer) {
                                        $class = 'option-correct';
                                        $icon = '<i class="fas fa-check-circle text-success ms-2"></i>';
                                    }
                                    
                                    if ($is_student_answer && !$is_correct_answer) {
                                        $class = 'option-student incorrect';
                                        $icon = '<i class="fas fa-times-circle text-danger ms-2"></i>';
                                    } elseif ($is_student_answer && $is_correct_answer) {
                                        $icon = '<i class="fas fa-check-circle text-success ms-2"></i> <small class="text-success">(Your Answer)</small>';
                                    }
                            ?>
                                <div class="option-item <?php echo $class; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo $letter; ?>.</strong> <?php echo htmlspecialchars($option); ?>
                                        </div>
                                        <div>
                                            <?php echo $icon; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <?php if (!empty($result['question']['explanation'])): ?>
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                                    <div>
                                        <small class="text-muted fw-semibold">Explanation:</small>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($result['question']['explanation']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Performance Tips -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-lightbulb me-2 text-warning"></i>Performance Tips
                </h5>
            </div>
            <div class="card-body">
                <?php if ($attempt['score_percentage'] >= 80): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-trophy me-2"></i>
                        <strong>Excellent work!</strong> You've demonstrated a strong understanding of this unit. 
                        Keep up the great work and continue to the next unit.
                    </div>
                <?php elseif ($attempt['score_percentage'] >= 60): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-chart-line me-2"></i>
                        <strong>Good effort!</strong> You have a decent understanding, but there's room for improvement. 
                        Review the explanations above and consider retaking the quiz.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-book-open me-2"></i>
                        <strong>Needs improvement.</strong> Consider reviewing the unit content and materials before retaking the quiz. 
                        Focus on the questions you got wrong and their explanations.
                    </div>
                <?php endif; ?>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Study Suggestions:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Review unit materials</li>
                            <li><i class="fas fa-check text-success me-2"></i>Focus on incorrect answers</li>
                            <li><i class="fas fa-check text-success me-2"></i>Read explanations carefully</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Next Steps:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Retake quiz if needed</li>
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Continue to next unit</li>
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Ask instructor for help</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
