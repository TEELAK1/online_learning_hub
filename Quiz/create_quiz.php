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

// Fetch courses by this instructor for dropdown
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_id = intval($_POST['course_id'] ?? 0);
    $quiz_title = trim($_POST['quiz_title'] ?? '');
    $quiz_description = trim($_POST['quiz_description'] ?? '');
    // Time limit removed as per request, defaulting to 0 (unlimited)
    $time_limit = 0;

    // Basic validation
    if ($course_id <= 0 || empty($quiz_title)) {
        $message = "Please select a course and enter quiz title.";
    } else {
        try {
            $db->begin_transaction();

            // Insert quiz first
            $stmt = $db->prepare("INSERT INTO quizzes (course_id, title, description, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("Database error: " . $db->error);
            }
            
            $stmt->bind_param("iss", $course_id, $quiz_title, $quiz_description);

            if (!$stmt->execute()) {
                throw new Exception("Error creating quiz: " . $stmt->error);
            }
            
            $quiz_id = $db->insert_id;
            $stmt->close();

            // Insert questions
            $questions = $_POST['questions'] ?? [];
            $questions_added = 0;

            if (!empty($questions)) {
                $qStmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer, question_type) VALUES (?, ?, ?, ?, ?, ?, ?, 'multiple_choice')");
                
                if (!$qStmt) {
                    throw new Exception("Prepare failed: " . $db->error);
                }

                foreach ($questions as $q) {
                    $question_text = trim($q['text'] ?? '');
                    $option_a = trim($q['options']['A'] ?? '');
                    $option_b = trim($q['options']['B'] ?? '');
                    $option_c = trim($q['options']['C'] ?? '');
                    $option_d = trim($q['options']['D'] ?? '');
                    $correct_answer = trim($q['correct_answer'] ?? '');

                    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($correct_answer)) {
                        continue; // Skip incomplete questions
                    }

                    $qStmt->bind_param("issssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer);
                    
                    if ($qStmt->execute()) {
                        $questions_added++;
                    }
                }
                $qStmt->close();
            }

            $db->commit();
            $success = true;
            $message = "Quiz '{$quiz_title}' created successfully with {$questions_added} questions!";

        } catch (Exception $e) {
            $db->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - Online Learning Hub</title>
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
        }
        
        .question-block {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .question-block:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 10px 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-secondary {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .question-number {
            background: var(--primary-color);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            margin-right: 0.5rem;
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
                    <i class="fas fa-arrow-left me-2"></i>Dashboard
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($instructor_name); ?>
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

    <div class="container py-4">
        <!-- Page Header -->
        <div class="quiz-card p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-1"><i class="fas fa-plus-circle me-2 text-primary"></i>Create New Quiz</h1>
                    <p class="text-muted mb-0">Create multiple choice quizzes for your students</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="assessment.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-tasks me-2"></i>Create Assessment
                    </a>
                    <a href="view_quizzes.php" class="btn btn-light border">
                        <i class="fas fa-list me-2"></i>View All
                    </a>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="quizForm" novalidate>
            <!-- Quiz Details -->
            <div class="quiz-card p-4">
                <h5 class="mb-3">Quiz Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="course_id" class="form-label fw-medium">Select Course</label>
                        <select id="course_id" name="course_id" class="form-select" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="quiz_title" class="form-label fw-medium">Quiz Title</label>
                        <input type="text" id="quiz_title" name="quiz_title" class="form-control" placeholder="e.g., Introduction to PHP" required />
                    </div>
                    <div class="col-12">
                        <label for="quiz_description" class="form-label fw-medium">Description (Optional)</label>
                        <textarea id="quiz_description" name="quiz_description" class="form-control" rows="2" placeholder="Brief description of the quiz..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Questions Container -->
            <div id="questions-container">
                <!-- Questions will be added here dynamically -->
            </div>

            <!-- Add Question Button -->
            <div class="text-center mb-5">
                <button type="button" id="add-question-btn" class="btn btn-secondary btn-lg w-100 dashed-border">
                    <i class="fas fa-plus me-2"></i>Add Question
                </button>
            </div>

            <!-- Submit Button -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                <button type="button" class="btn btn-light border px-4" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary px-5">Create Quiz</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('questions-container');
            const addBtn = document.getElementById('add-question-btn');
            let questionCount = 0;
            const maxQuestions = 10;

            function addQuestion() {
                if (questionCount >= maxQuestions) {
                    alert('Maximum 10 questions allowed.');
                    return;
                }

                questionCount++;
                const index = questionCount - 1; // 0-based index for array

                const questionHtml = `
                    <div class="question-block" id="question-block-${index}">
                        <button type="button" class="remove-question-btn" onclick="removeQuestion(${index})" title="Remove Question">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <span class="question-number">${questionCount}</span> Question Text
                            </label>
                            <textarea name="questions[${index}][text]" class="form-control" rows="2" placeholder="Enter your question here..." required></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">A</span>
                                    <input type="text" name="questions[${index}][options][A]" class="form-control" placeholder="Option A" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">B</span>
                                    <input type="text" name="questions[${index}][options][B]" class="form-control" placeholder="Option B" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">C</span>
                                    <input type="text" name="questions[${index}][options][C]" class="form-control" placeholder="Option C">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light">D</span>
                                    <input type="text" name="questions[${index}][options][D]" class="form-control" placeholder="Option D">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Correct Answer</label>
                                <select name="questions[${index}][correct_answer]" class="form-select" required>
                                    <option value="">Select Answer</option>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', questionHtml);
                updateQuestionNumbers();
            }

            window.removeQuestion = function(index) {
                const block = document.getElementById(`question-block-${index}`);
                if (block) {
                    block.remove();
                    questionCount--;
                    updateQuestionNumbers();
                }
            };

            function updateQuestionNumbers() {
                const blocks = container.querySelectorAll('.question-block');
                blocks.forEach((block, idx) => {
                    const numberSpan = block.querySelector('.question-number');
                    if (numberSpan) {
                        numberSpan.textContent = idx + 1;
                    }
                });
                
                if (questionCount >= maxQuestions) {
                    addBtn.disabled = true;
                    addBtn.textContent = 'Maximum Questions Reached';
                } else {
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Question';
                }
            }

            // Add first question by default
            addQuestion();

            addBtn.addEventListener('click', addQuestion);
        });
    </script>
</body>
</html>
