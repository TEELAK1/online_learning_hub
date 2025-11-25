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
$message = "";
$success = false;

// Get unit_id from URL
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

// Verify student is enrolled in the course that contains this unit
$enrollmentCheck = $db->prepare("
    SELECT u.title as unit_title, u.description, u.course_id, c.title as course_title
    FROM course_units u 
    JOIN courses c ON u.course_id = c.course_id 
    JOIN enrollments e ON c.course_id = e.course_id 
    WHERE u.unit_id = ? AND e.student_id = ? AND e.status IN ('active', 'completed')
");

if ($enrollmentCheck) {
    $enrollmentCheck->bind_param("ii", $unit_id, $student_id);
    $enrollmentCheck->execute();
    $enrollmentResult = $enrollmentCheck->get_result();
    
    if ($enrollmentResult->num_rows === 0) {
        header("Location: student_dashboard.php");
        exit();
    }
    
    $unit = $enrollmentResult->fetch_assoc();
} else {
    header("Location: student_dashboard.php");
    exit();
}

// Get unit questions
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

// Check if student has already completed this unit
$attemptCheck = $db->prepare("SELECT * FROM unit_attempts WHERE unit_id = ? AND student_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 1");
$previousAttempt = null;
if ($attemptCheck) {
    $attemptCheck->bind_param("ii", $unit_id, $student_id);
    $attemptCheck->execute();
    $attemptResult = $attemptCheck->get_result();
    if ($attemptResult->num_rows > 0) {
        $previousAttempt = $attemptResult->fetch_assoc();
        if ($previousAttempt) {
            $previousAttempt['answers'] = json_decode($previousAttempt['answers'], true);
        }
    }
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $student_answers = $_POST['answers'] ?? [];
    $start_time = $_POST['start_time'] ?? time();
    $time_taken = max(1, ceil((time() - $start_time) / 60)); // Convert to minutes
    
    $correct_count = 0;
    $total_questions = count($questions);
    
    // Calculate score
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        $student_answer = $student_answers[$question_id] ?? [];
        
        if (!is_array($student_answer)) {
            $student_answer = [$student_answer];
        }
        
        // Sort arrays for comparison
        sort($student_answer);
        $correct_answers = $question['correct_answers'];
        sort($correct_answers);
        
        if ($student_answer === $correct_answers) {
            $correct_count++;
        }
    }
    
    $score_percentage = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 2) : 0;
    
    try {
        // Save attempt
        $attemptStmt = $db->prepare("
            INSERT INTO unit_attempts 
            (unit_id, student_id, total_questions, correct_answers, score_percentage, time_taken_minutes, answers, completed_at, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'completed')
        ");
        
        if ($attemptStmt) {
            $answers_json = json_encode($student_answers);
            // Bind: unit_id(i), student_id(i), total_questions(i), correct_answers(i), score_percentage(d), time_taken_minutes(i), answers_json(s)
            $attemptStmt->bind_param("iiidiis", $unit_id, $student_id, $total_questions, $correct_count, $score_percentage, $time_taken, $answers_json);
            
            if ($attemptStmt->execute()) {
                $success = true;
                $message = "Quiz completed successfully! Score: {$score_percentage}%";
                
                // Redirect to results page
                header("Location: unit_quiz_results.php?unit_id={$unit_id}&attempt_id=" . $db->insert_id);
                exit();
            } else {
                $message = "Failed to save quiz results: " . $attemptStmt->error;
            }
        } else {
            $message = "Database error: " . $db->error;
        }
    } catch (Exception $e) {
        $message = "Error submitting quiz: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Quiz - <?php echo htmlspecialchars($unit['unit_title']); ?></title>
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
        
        .quiz-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .question-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 30px;
        }
        
        .question-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .option-item {
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .option-item:hover {
            border-color: var(--primary-color);
            background: #f8f9ff;
        }
        
        .option-item.selected {
            border-color: var(--primary-color);
            background: #e7f3ff;
        }
        
        .quiz-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .media-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .progress-indicator {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .previous-attempt {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Quiz Timer -->
    <div class="quiz-timer">
        <div class="d-flex align-items-center">
            <i class="fas fa-clock me-2 text-primary"></i>
            <span id="timer">00:00</span>
        </div>
    </div>

    <div class="container py-4">
        <!-- Quiz Header -->
        <div class="quiz-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($unit['unit_title']); ?></h1>
                    <p class="mb-0 opacity-75">Course: <?php echo htmlspecialchars($unit['course_title']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="badge bg-light text-dark fs-6">
                        <?php echo count($questions); ?> Questions
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Previous Attempt Notice -->
        <?php if ($previousAttempt): ?>
            <div class="previous-attempt">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Previous Attempt:</strong> You scored <?php echo $previousAttempt['score_percentage']; ?>% 
                        on <?php echo date('M j, Y g:i A', strtotime($previousAttempt['completed_at'])); ?>
                        <br>
                        <small class="text-muted">You can retake this quiz to improve your score.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Unit Description -->
        <?php if (!empty($unit['description'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Unit Overview
                    </h5>
                    <p class="card-text"><?php echo htmlspecialchars($unit['description']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="d-flex justify-content-between align-items-center">
                <span>Quiz Progress</span>
                <span id="progress-text">0 of <?php echo count($questions); ?> answered</span>
            </div>
            <div class="progress mt-2">
                <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Quiz Questions -->
        <?php if (empty($questions)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-question-circle fa-4x text-muted mb-4"></i>
                    <h4>No Questions Available</h4>
                    <p class="text-muted">This unit doesn't have any questions yet. Please check back later.</p>
                    <a href="student_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" id="quizForm">
                <input type="hidden" name="submit_quiz" value="1">
                <input type="hidden" name="start_time" value="<?php echo time(); ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="card question-card">
                        <div class="question-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-0">
                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                    Question <?php echo $index + 1; ?> of <?php echo count($questions); ?>
                                    <span class="badge bg-secondary ms-2"><?php echo $question['points']; ?> pts</span>
                                </h6>
                                <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <p class="fw-semibold mb-4"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <div class="options" data-question-id="<?php echo $question['question_id']; ?>" data-question-type="<?php echo $question['question_type']; ?>">
                                <?php 
                                $options = ['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c'], 'D' => $question['option_d'], 'E' => $question['option_e']];
                                foreach ($options as $letter => $option):
                                    if (!empty($option)):
                                ?>
                                    <div class="option-item" onclick="selectOption(this, '<?php echo $question['question_id']; ?>', '<?php echo $letter; ?>', '<?php echo $question['question_type']; ?>')">
                                        <div class="d-flex align-items-center">
                                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                <input type="checkbox" name="answers[<?php echo $question['question_id']; ?>][]" value="<?php echo $letter; ?>" class="form-check-input me-3">
                                            <?php else: ?>
                                                <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $letter; ?>" class="form-check-input me-3">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo $letter; ?>.</strong> <?php echo htmlspecialchars($option); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirmSubmit()">
                        <i class="fas fa-check-circle me-2"></i>Submit Quiz
                    </button>
                    <div class="mt-3">
                        <a href="student_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let startTime = Date.now();
        let totalQuestions = <?php echo count($questions); ?>;
        
        // Timer functionality
        function updateTimer() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            document.getElementById('timer').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }
        
        setInterval(updateTimer, 1000);
        
        // Option selection
        function selectOption(element, questionId, optionLetter, questionType) {
            const checkbox = element.querySelector('input');
            
            if (questionType === 'multiple_choice') {
                checkbox.checked = !checkbox.checked;
            } else {
                // For single choice, uncheck other options
                const allOptions = element.parentElement.querySelectorAll('.option-item');
                allOptions.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.querySelector('input').checked = false;
                });
                checkbox.checked = true;
            }
            
            // Update visual selection
            if (checkbox.checked) {
                element.classList.add('selected');
            } else {
                element.classList.remove('selected');
            }
            
            updateProgress();
        }
        
        // Update progress
        function updateProgress() {
            const answeredQuestions = new Set();
            
            document.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked').forEach(input => {
                const questionId = input.name.match(/\[(\d+)\]/)[1];
                answeredQuestions.add(questionId);
            });
            
            const progress = (answeredQuestions.size / totalQuestions) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-text').textContent = 
                answeredQuestions.size + ' of ' + totalQuestions + ' answered';
        }
        
        // Confirm submission
        function confirmSubmit() {
            const answeredQuestions = new Set();
            
            document.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked').forEach(input => {
                const questionId = input.name.match(/\[(\d+)\]/)[1];
                answeredQuestions.add(questionId);
            });
            
            if (answeredQuestions.size < totalQuestions) {
                const unanswered = totalQuestions - answeredQuestions.size;
                return confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`);
            }
            
            return confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.');
        }
        
        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            if (document.querySelectorAll('input:checked').length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
