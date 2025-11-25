<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in or not instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Check database schema
function checkCourseSchema($db) {
    $result = $db->query("SHOW COLUMNS FROM courses LIKE 'course_id'");
    return $result && $result->num_rows > 0;
}

$hasNewSchema = checkCourseSchema($db);
$courseIdField = $hasNewSchema ? 'course_id' : 'id';
$instructorField = 'instructor_id';

// Fetch courses by this instructor
try {
    $stmt = $db->prepare("SELECT {$courseIdField} as id, title FROM courses WHERE {$instructorField} = ?");
    if (!$stmt) {
        $courses = [];
    } else {
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    $courses = [];
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_id = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($course_id <= 0 || empty($title)) {
        $message = "Please select a course and enter a title.";
    } else {
        try {
            $db->begin_transaction();

            // Insert assessment (stored as a quiz)
            $stmt = $db->prepare("INSERT INTO quizzes (course_id, title, description, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) throw new Exception("Database error: " . $db->error);
            
            $stmt->bind_param("iss", $course_id, $title, $description);

            if (!$stmt->execute()) throw new Exception("Error creating assessment: " . $stmt->error);
            
            $quiz_id = $db->insert_id;
            $stmt->close();

            // Insert questions
            $questions = $_POST['questions'] ?? [];
            $questions_added = 0;

            if (!empty($questions)) {
                $qStmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer, question_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$qStmt) throw new Exception("Prepare failed: " . $db->error);

                foreach ($questions as $q) {
                    $type = $q['type'] ?? 'multiple_choice';
                    $question_text = trim($q['text'] ?? '');
                    $correct_answer = trim($q['correct_answer'] ?? '');
                    
                    // Options only for MCQ
                    $option_a = ($type === 'multiple_choice') ? trim($q['options']['A'] ?? '') : null;
                    $option_b = ($type === 'multiple_choice') ? trim($q['options']['B'] ?? '') : null;
                    $option_c = ($type === 'multiple_choice') ? trim($q['options']['C'] ?? '') : null;
                    $option_d = ($type === 'multiple_choice') ? trim($q['options']['D'] ?? '') : null;

                    if (empty($question_text)) continue;

                    $qStmt->bind_param("isssssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $type);
                    
                    if ($qStmt->execute()) {
                        $questions_added++;
                    }
                }
                $qStmt->close();
            }

            $db->commit();
            $success = true;
            $message = "Assessment '{$title}' created successfully with {$questions_added} questions!";

        } catch (Exception $e) {
            $db->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assessment - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7c3aed; /* Purple for Assessment to differentiate */
            --secondary-color: #6d28d9;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-color);
        }
        
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .assessment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
        }
        
        .question-block {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            border-left: 4px solid var(--primary-color);
        }
        
        .remove-question-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--danger-color);
            background: #fee2e2;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .remove-question-btn:hover {
            background: var(--danger-color);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
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
                <a href="create_quiz.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Quiz
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="assessment-card p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-1"><i class="fas fa-tasks me-2 text-primary"></i>Create Assessment</h1>
                    <p class="text-muted mb-0">Create mixed assessments with short answers, long answers, and MCQs.</p>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="assessmentForm">
            <div class="assessment-card p-4">
                <h5 class="mb-3">Assessment Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Select Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Mid-term Assessment">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <div id="questions-container"></div>

            <div class="text-center mb-5">
                <button type="button" id="add-question-btn" class="btn btn-outline-primary btn-lg w-100 border-dashed">
                    <i class="fas fa-plus me-2"></i>Add Question
                </button>
            </div>

            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5">Create Assessment</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('questions-container');
            const addBtn = document.getElementById('add-question-btn');
            let questionCount = 0;

            function addQuestion() {
                questionCount++;
                const index = questionCount - 1;

                const html = `
                    <div class="question-block" id="q-${index}">
                        <button type="button" class="remove-question-btn" onclick="removeQuestion(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Question ${questionCount}</label>
                                <textarea name="questions[${index}][text]" class="form-control" rows="2" required placeholder="Enter question text..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Question Type</label>
                                <select name="questions[${index}][type]" class="form-select" onchange="toggleOptions(${index}, this.value)">
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="long_answer">Long Answer</option>
                                </select>
                            </div>
                        </div>

                        <div id="options-container-${index}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">A</span>
                                        <input type="text" name="questions[${index}][options][A]" class="form-control" placeholder="Option A">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">B</span>
                                        <input type="text" name="questions[${index}][options][B]" class="form-control" placeholder="Option B">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">C</span>
                                        <input type="text" name="questions[${index}][options][C]" class="form-control" placeholder="Option C">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">D</span>
                                        <input type="text" name="questions[${index}][options][D]" class="form-control" placeholder="Option D">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Correct Answer</label>
                                <select name="questions[${index}][correct_answer]" class="form-select">
                                    <option value="">Select Correct Option</option>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>

                        <div id="model-answer-container-${index}" style="display: none;">
                            <label class="form-label fw-medium">Model Answer / Key Points (Optional)</label>
                            <textarea name="questions[${index}][model_answer]" class="form-control" rows="2" placeholder="Enter the expected answer or key points for evaluation..."></textarea>
                            <small class="text-muted">This will be used as a reference for manual evaluation.</small>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', html);
            }

            window.removeQuestion = function(index) {
                document.getElementById(`q-${index}`).remove();
            };

            window.toggleOptions = function(index, type) {
                const optionsContainer = document.getElementById(`options-container-${index}`);
                const modelContainer = document.getElementById(`model-answer-container-${index}`);
                
                if (type === 'multiple_choice') {
                    optionsContainer.style.display = 'block';
                    modelContainer.style.display = 'none';
                    
                    // Enable required fields for MCQ
                    optionsContainer.querySelectorAll('input, select').forEach(el => el.required = true);
                } else {
                    optionsContainer.style.display = 'none';
                    modelContainer.style.display = 'block';
                    
                    // Disable required fields for MCQ to prevent form validation errors
                    optionsContainer.querySelectorAll('input, select').forEach(el => el.required = false);
                }
            };

            addBtn.addEventListener('click', addQuestion);
            addQuestion(); // Add first question
        });
    </script>
</body>
</html>
