<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is an instructor
if (!Auth::isAuthenticated() || !Auth::hasRole('instructor')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$message = "";
$success = false;

// Get unit_id from URL
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

// Verify instructor owns this unit's course
$unitCheck = $db->prepare("
    SELECT u.title as unit_title, u.course_id, c.title as course_title 
    FROM course_units u 
    JOIN courses c ON u.course_id = c.course_id 
    WHERE u.unit_id = ? AND c.instructor_id = ?
");

if ($unitCheck) {
    $unitCheck->bind_param("ii", $unit_id, $instructor_id);
    $unitCheck->execute();
    $unitResult = $unitCheck->get_result();
    
    if ($unitResult->num_rows === 0) {
        header("Location: instructor_dashboard.php");
        exit();
    }
    
    $unit = $unitResult->fetch_assoc();
} else {
    header("Location: instructor_dashboard.php");
    exit();
}

// Handle question deletion
if (isset($_GET['delete_question'])) {
    $question_id = (int)$_GET['delete_question'];
    
    $deleteStmt = $db->prepare("DELETE FROM unit_questions WHERE question_id = ? AND unit_id = ?");
    if ($deleteStmt) {
        $deleteStmt->bind_param("ii", $question_id, $unit_id);
        if ($deleteStmt->execute()) {
            $success = true;
            $message = "Question deleted successfully!";
        } else {
            $message = "Failed to delete question.";
        }
    }
}

// Handle form submission for new question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'single_choice';
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $option_e = trim($_POST['option_e'] ?? '');
    $correct_answers = $_POST['correct_answers'] ?? [];
    $explanation = trim($_POST['explanation'] ?? '');
    $points = floatval($_POST['points'] ?? 1.0);
    
    // Validation
    if (empty($question_text)) {
        $message = "Question text is required.";
    } elseif (empty($option_a) || empty($option_b)) {
        $message = "At least two answer options are required.";
    } elseif (empty($correct_answers)) {
        $message = "Please select at least one correct answer.";
    } else {
        try {
            // Create unit_questions table if it doesn't exist
            $createTable = "CREATE TABLE IF NOT EXISTS unit_questions (
                question_id INT AUTO_INCREMENT PRIMARY KEY,
                unit_id INT NOT NULL,
                question_text TEXT NOT NULL,
                question_type ENUM('single_choice', 'multiple_choice', 'true_false') DEFAULT 'single_choice',
                option_a VARCHAR(500) NOT NULL,
                option_b VARCHAR(500) NOT NULL,
                option_c VARCHAR(500) DEFAULT NULL,
                option_d VARCHAR(500) DEFAULT NULL,
                option_e VARCHAR(500) DEFAULT NULL,
                correct_answers JSON NOT NULL,
                explanation TEXT DEFAULT NULL,
                points DECIMAL(5,2) DEFAULT 1.00,
                order_index INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (unit_id) REFERENCES course_units(unit_id) ON DELETE CASCADE,
                INDEX idx_unit (unit_id),
                INDEX idx_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($createTable);
            
            // Get next order index
            $orderStmt = $db->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM unit_questions WHERE unit_id = ?");
            if ($orderStmt) {
                $orderStmt->bind_param("i", $unit_id);
                $orderStmt->execute();
                $order_result = $orderStmt->get_result();
                $next_order = $order_result->fetch_assoc()['next_order'];
            } else {
                $next_order = 1;
            }
            
            // Convert correct answers to JSON
            $correct_answers_json = json_encode(array_values($correct_answers));
            
            // Insert question
            $stmt = $db->prepare("
                INSERT INTO unit_questions 
                (unit_id, question_text, question_type, option_a, option_b, option_c, option_d, option_e, correct_answers, explanation, points, order_index) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("isssssssssdi", $unit_id, $question_text, $question_type, $option_a, $option_b, $option_c, $option_d, $option_e, $correct_answers_json, $explanation, $points, $next_order);
                
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Question added successfully!";
                    // Clear form data
                    $_POST = [];
                } else {
                    $message = "Failed to add question. Please try again.";
                }
            } else {
                $message = "Database error. Please try again.";
            }
        } catch (Exception $e) {
            $message = "Error adding question: " . $e->getMessage();
        }
    }
}

// Get existing questions
$questions = [];
$questionsStmt = $db->prepare("SELECT * FROM unit_questions WHERE unit_id = ? ORDER BY order_index ASC");
if ($questionsStmt) {
    $questionsStmt->bind_param("i", $unit_id);
    $questionsStmt->execute();
    $questionsResult = $questionsStmt->get_result();
    while ($row = $questionsResult->fetch_assoc()) {
        $row['correct_answers'] = json_decode($row['correct_answers'], true);
        $questions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo htmlspecialchars($unit['unit_title']); ?></title>
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
        
        .question-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .question-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .option-item {
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .option-correct {
            background: #d4edda;
            border-left: 4px solid var(--success-color);
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Manage Unit Questions</h1>
                <p class="text-muted mb-0">
                    Unit: <?php echo htmlspecialchars($unit['unit_title']); ?> 
                    <span class="mx-2">•</span> 
                    Course: <?php echo htmlspecialchars($unit['course_title']); ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="create_unit.php?course_id=<?php echo $unit['course_id']; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>Add Another Unit
                </a>
                <a href="manage_course.php?course_id=<?php echo $unit['course_id']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Course
                </a>
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

        <div class="row">
            <!-- Existing Questions -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">
                        <i class="fas fa-question-circle me-2 text-primary"></i>
                        Unit Questions (<?php echo count($questions); ?>)
                    </h4>
                </div>

                <?php if (empty($questions)): ?>
                    <div class="card question-card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Questions Added Yet</h5>
                            <p class="text-muted">Add your first multiple-choice question using the form on the right.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="card question-card">
                            <div class="question-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                            <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                                            <span class="badge bg-secondary ms-2"><?php echo $question['points']; ?> pts</span>
                                        </h6>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item text-danger" href="?unit_id=<?php echo $unit_id; ?>&delete_question=<?php echo $question['question_id']; ?>" onclick="return confirm('Are you sure you want to delete this question?')">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="fw-semibold mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                
                                <div class="options">
                                    <?php 
                                    $options = ['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c'], 'D' => $question['option_d'], 'E' => $question['option_e']];
                                    foreach ($options as $letter => $option):
                                        if (!empty($option)):
                                            $isCorrect = in_array($letter, $question['correct_answers']);
                                    ?>
                                        <div class="option-item <?php echo $isCorrect ? 'option-correct' : ''; ?>">
                                            <strong><?php echo $letter; ?>.</strong> <?php echo htmlspecialchars($option); ?>
                                            <?php if ($isCorrect): ?>
                                                <i class="fas fa-check-circle text-success ms-2"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                                
                                <?php if (!empty($question['explanation'])): ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted fw-semibold">Explanation:</small>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($question['explanation']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Add Question Form -->
            <div class="col-lg-4">
                <div class="card form-card sticky-top" style="top: 20px;">
                    <div class="form-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Add New Question
                        </h5>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="add_question" value="1">
                            
                            <!-- Question Text -->
                            <div class="mb-3">
                                <label for="question_text" class="form-label fw-semibold">Question *</label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" 
                                          placeholder="Enter your question..." required><?php echo htmlspecialchars($_POST['question_text'] ?? ''); ?></textarea>
                            </div>

                            <!-- Question Type -->
                            <div class="mb-3">
                                <label for="question_type" class="form-label fw-semibold">Question Type</label>
                                <select class="form-select" id="question_type" name="question_type" onchange="updateCorrectAnswerOptions()">
                                    <option value="single_choice" <?php echo ($_POST['question_type'] ?? '') === 'single_choice' ? 'selected' : ''; ?>>Single Choice (One Correct Answer)</option>
                                    <option value="multiple_choice" <?php echo ($_POST['question_type'] ?? '') === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice (Multiple Correct Answers)</option>
                                    <option value="true_false" <?php echo ($_POST['question_type'] ?? '') === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                </select>
                            </div>

                            <!-- Answer Options -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Answer Options *</label>
                                
                                <div class="mb-2">
                                    <input type="text" class="form-control" name="option_a" placeholder="Option A" 
                                           value="<?php echo htmlspecialchars($_POST['option_a'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <input type="text" class="form-control" name="option_b" placeholder="Option B" 
                                           value="<?php echo htmlspecialchars($_POST['option_b'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-2" id="option_c_div">
                                    <input type="text" class="form-control" name="option_c" placeholder="Option C (Optional)" 
                                           value="<?php echo htmlspecialchars($_POST['option_c'] ?? ''); ?>">
                                </div>
                                <div class="mb-2" id="option_d_div">
                                    <input type="text" class="form-control" name="option_d" placeholder="Option D (Optional)" 
                                           value="<?php echo htmlspecialchars($_POST['option_d'] ?? ''); ?>">
                                </div>
                                <div class="mb-2" id="option_e_div">
                                    <input type="text" class="form-control" name="option_e" placeholder="Option E (Optional)" 
                                           value="<?php echo htmlspecialchars($_POST['option_e'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Correct Answers -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Correct Answer(s) *</label>
                                <div class="checkbox-group" id="correct_answers_group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="correct_a" name="correct_answers[]" value="A" class="form-check-input">
                                        <label for="correct_a" class="form-check-label">A</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="correct_b" name="correct_answers[]" value="B" class="form-check-input">
                                        <label for="correct_b" class="form-check-label">B</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="correct_c" name="correct_answers[]" value="C" class="form-check-input">
                                        <label for="correct_c" class="form-check-label">C</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="correct_d" name="correct_answers[]" value="D" class="form-check-input">
                                        <label for="correct_d" class="form-check-label">D</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="correct_e" name="correct_answers[]" value="E" class="form-check-input">
                                        <label for="correct_e" class="form-check-label">E</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Points -->
                            <div class="mb-3">
                                <label for="points" class="form-label fw-semibold">Points</label>
                                <input type="number" class="form-control" id="points" name="points" min="0.1" step="0.1" 
                                       value="<?php echo htmlspecialchars($_POST['points'] ?? '1.0'); ?>">
                            </div>

                            <!-- Explanation -->
                            <div class="mb-4">
                                <label for="explanation" class="form-label fw-semibold">Explanation (Optional)</label>
                                <textarea class="form-control" id="explanation" name="explanation" rows="2" 
                                          placeholder="Explain why this is the correct answer..."><?php echo htmlspecialchars($_POST['explanation'] ?? ''); ?></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Add Question
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCorrectAnswerOptions() {
            const questionType = document.getElementById('question_type').value;
            const checkboxes = document.querySelectorAll('#correct_answers_group input[type="checkbox"]');
            
            if (questionType === 'single_choice') {
                // Convert to radio buttons behavior
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            checkboxes.forEach(other => {
                                if (other !== this) other.checked = false;
                            });
                        }
                    });
                });
            } else if (questionType === 'true_false') {
                // Hide options C, D, E and update A, B labels
                document.getElementById('option_c_div').style.display = 'none';
                document.getElementById('option_d_div').style.display = 'none';
                document.getElementById('option_e_div').style.display = 'none';
                
                document.querySelector('input[name="option_a"]').placeholder = 'True';
                document.querySelector('input[name="option_b"]').placeholder = 'False';
                
                // Hide C, D, E checkboxes
                document.querySelector('label[for="correct_c"]').parentElement.style.display = 'none';
                document.querySelector('label[for="correct_d"]').parentElement.style.display = 'none';
                document.querySelector('label[for="correct_e"]').parentElement.style.display = 'none';
            } else {
                // Show all options for multiple choice
                document.getElementById('option_c_div').style.display = 'block';
                document.getElementById('option_d_div').style.display = 'block';
                document.getElementById('option_e_div').style.display = 'block';
                
                document.querySelector('input[name="option_a"]').placeholder = 'Option A';
                document.querySelector('input[name="option_b"]').placeholder = 'Option B';
                
                // Show all checkboxes
                document.querySelector('label[for="correct_c"]').parentElement.style.display = 'flex';
                document.querySelector('label[for="correct_d"]').parentElement.style.display = 'flex';
                document.querySelector('label[for="correct_e"]').parentElement.style.display = 'flex';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCorrectAnswerOptions();
        });
    </script>
</body>
</html>
