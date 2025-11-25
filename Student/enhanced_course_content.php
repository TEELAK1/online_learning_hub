<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    header("Location: student_dashboard.php");
    exit();
}

// Verify enrollment
$enrollCheck = $db->prepare("
    SELECT c.*, i.name as instructor_name, e.progress_percentage
    FROM courses c
    INNER JOIN enrollments e ON c.course_id = e.course_id
    INNER JOIN instructor i ON c.instructor_id = i.instructor_id
    WHERE c.course_id = ? AND e.student_id = ? AND e.status = 'active'
");

$enrollCheck->bind_param("ii", $course_id, $student_id);
$enrollCheck->execute();
$course = $enrollCheck->get_result()->fetch_assoc();

if (!$course) {
    header("Location: student_dashboard.php");
    exit();
}

// Check if course_units table exists, if not create it and add sample data
$tableCheck = $db->query("SHOW TABLES LIKE 'course_units'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    // Create course_units table
    $db->query("CREATE TABLE IF NOT EXISTS course_units (
        unit_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        order_index INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course (course_id)
    )");
    
    // Don't create sample units - only show instructor-created content
}

// Get course units with lessons and quizzes
$unitsStmt = $db->prepare("
    SELECT 
        cu.unit_id,
        cu.title as unit_title,
        cu.description as unit_description,
        cu.order_index
    FROM course_units cu
    WHERE cu.course_id = ?
    ORDER BY cu.order_index ASC, cu.unit_id ASC
");

if (!$unitsStmt) {
    die("Database error: " . $db->error);
}

$unitsStmt->bind_param("i", $course_id);
$unitsStmt->execute();
$unitRows = $unitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Deduplicate units by unit_id to avoid showing the same unit twice
$units = [];
$seenUnitIds = [];
foreach ($unitRows as $row) {
    if (!in_array($row['unit_id'], $seenUnitIds)) {
        $units[] = $row;
        $seenUnitIds[] = $row['unit_id'];
    }
}

// Get lessons for each unit
foreach ($units as &$unit) {
    // Check if course_lessons table exists
    $lessonsTableCheck = $db->query("SHOW TABLES LIKE 'course_lessons'");
    if (!$lessonsTableCheck || $lessonsTableCheck->num_rows == 0) {
        // Create course_lessons table
        $db->query("CREATE TABLE IF NOT EXISTS course_lessons (
            lesson_id INT AUTO_INCREMENT PRIMARY KEY,
            unit_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            video_url VARCHAR(500),
            youtube_url VARCHAR(500),
            file_path VARCHAR(500),
            duration_minutes INT DEFAULT 0,
            order_index INT DEFAULT 0,
            is_free BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_unit (unit_id)
        )");
        
        // Create lesson_progress table
        $db->query("CREATE TABLE IF NOT EXISTS lesson_progress (
            progress_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            lesson_id INT NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            completion_date TIMESTAMP NULL,
            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            time_spent_minutes INT DEFAULT 0,
            UNIQUE KEY unique_progress (student_id, lesson_id),
            INDEX idx_student (student_id),
            INDEX idx_lesson (lesson_id)
        )");
    }
    
    $lessonsStmt = $db->prepare("
        SELECT 
            cl.lesson_id,
            cl.title as lesson_title,
            cl.content,
            cl.video_url,
            cl.youtube_url,
            cl.file_path,
            cl.duration_minutes,
            cl.is_free,
            (SELECT lp.completed FROM lesson_progress lp WHERE lp.lesson_id = cl.lesson_id AND lp.student_id = ? LIMIT 1) as completed,
            (SELECT lp.completion_date FROM lesson_progress lp WHERE lp.lesson_id = cl.lesson_id AND lp.student_id = ? LIMIT 1) as completion_date
        FROM course_lessons cl
        WHERE cl.unit_id = ?
        ORDER BY cl.order_index ASC, cl.lesson_id ASC
    ");
    
    if ($lessonsStmt) {
        $lessonsStmt->bind_param("iii", $student_id, $student_id, $unit['unit_id']);
        $lessonsStmt->execute();
        $unit['lessons'] = $lessonsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // NEW: For each lesson, try to fetch a lesson-level quiz (if instructor created one attached to a lesson)
        $lessonQuizStmt = $db->prepare("
            SELECT 
                q.quiz_id,
                q.title AS quiz_title,
                q.time_limit_minutes,
                q.passing_score,
                q.max_attempts,
                (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) as question_count,
                (SELECT COUNT(*) FROM quiz_results qr WHERE qr.quiz_id = q.quiz_id AND qr.student_id = ?) as attempt_count,
                (SELECT MAX(qr.score) FROM quiz_results qr WHERE qr.quiz_id = q.quiz_id AND qr.student_id = ?) as best_score
            FROM quizzes q
            WHERE q.lesson_id = ? AND (q.status = 'active' OR q.status IS NULL)
            LIMIT 1
        ");
        
        // loop lessons and attach quiz info if exists
        if ($lessonQuizStmt) {
            foreach ($unit['lessons'] as &$lesson) {
                $lid = $lesson['lesson_id'];
                $lessonQuizStmt->bind_param("iii", $student_id, $student_id, $lid);
                $lessonQuizStmt->execute();
                $quizRow = $lessonQuizStmt->get_result()->fetch_assoc();
                $lesson['lesson_quiz'] = $quizRow ? $quizRow : null;
            }
            $lessonQuizStmt->close();
        } else {
            // If prepare failed, set lesson_quiz to null for safety
            foreach ($unit['lessons'] as &$lesson) {
                $lesson['lesson_quiz'] = null;
            }
        }
        
        $lessonsStmt->close();
    } else {
        $unit['lessons'] = [];
    }
}

// Fetch course-level quizzes ONCE (outside the unit loop)
$courseQuizzes = [];
$quizzesTableCheck = $db->query("SHOW TABLES LIKE 'quizzes'");
if ($quizzesTableCheck && $quizzesTableCheck->num_rows > 0) {
    $quizzesStmt = $db->prepare("
        SELECT DISTINCT
            q.quiz_id,
            q.title as quiz_title,
            q.description as quiz_description,
            q.time_limit_minutes,
            q.passing_score,
            q.max_attempts,
            (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) as question_count,
            (SELECT COUNT(*) FROM quiz_results qr WHERE qr.quiz_id = q.quiz_id AND qr.student_id = ?) as attempt_count,
            (SELECT MAX(score) FROM quiz_results qr WHERE qr.quiz_id = q.quiz_id AND qr.student_id = ?) as best_score
        FROM quizzes q
        WHERE q.lesson_id IS NULL AND q.course_id = ? AND (q.status = 'active' OR q.status IS NULL)
        ORDER BY q.quiz_id ASC
    ");
    
    if ($quizzesStmt) {
        $quizzesStmt->bind_param("iii", $student_id, $student_id, $course_id);
        $quizzesStmt->execute();
        $quizRows = $quizzesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Deduplicate quizzes by quiz_id
        $seenQuizIds = [];
        foreach ($quizRows as $qr) {
            if (!in_array($qr['quiz_id'], $seenQuizIds)) {
                $courseQuizzes[] = $qr;
                $seenQuizIds[] = $qr['quiz_id'];
            }
        }
        $quizzesStmt->close();
    }
} else {
    // Create quizzes table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS quizzes (
        quiz_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        lesson_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        time_limit_minutes INT DEFAULT 0,
        passing_score INT DEFAULT 60,
        max_attempts INT DEFAULT 3,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course (course_id),
        INDEX idx_lesson (lesson_id)
    )");
    
    $db->query("CREATE TABLE IF NOT EXISTS quiz_questions (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'long_answer') DEFAULT 'multiple_choice',
        option_a VARCHAR(255),
        option_b VARCHAR(255),
        option_c VARCHAR(255),
        option_d VARCHAR(255),
        correct_answer VARCHAR(255) NOT NULL,
        order_index INT DEFAULT 0,
        INDEX idx_quiz (quiz_id)
    )");
    
    $db->query("CREATE TABLE IF NOT EXISTS quiz_results (
        result_id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        student_id INT NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_quiz_student (quiz_id, student_id)
    )");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Content</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .course-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .unit-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .unit-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .unit-header:hover {
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%);
        }
        
        .lesson-item {
            border-left: 4px solid var(--primary-color);
            background: white;
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }
        
        .lesson-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }
        
        .lesson-completed {
            border-left-color: var(--success-color);
            background: #f0fdf4;
        }
        
        .quiz-item {
            border-left: 4px solid var(--warning-color);
            background: #fffbeb;
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }
        
        .quiz-item:hover {
            background: #fef3c7;
            transform: translateX(5px);
        }
        
        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-lesson {
            background: var(--primary-color);
            border: none;
            color: white;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .btn-quiz {
            background: var(--warning-color);
            border: none;
            color: white;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .collapse-toggle {
            transition: transform 0.3s ease;
        }
        
        .collapse-toggle.collapsed {
            transform: rotate(-90deg);
        }
    </style>
</head>
<body>
    <!-- Course Header -->
    <div class="course-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="mb-1 opacity-75">
                        <i class="fas fa-user me-2"></i>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                    </p>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                    </div>
                    <small class="opacity-75"><?php echo number_format($course['progress_percentage'], 1); ?>% Complete</small>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="student_dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <?php if (empty($units)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Content Available</h4>
                <p class="text-muted">This course doesn't have any units or lessons yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($units as $index => $unit): ?>
                <div class="unit-card">
                    <div class="unit-header" data-bs-toggle="collapse" data-bs-target="#unit<?php echo $unit['unit_id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($unit['unit_title']); ?>
                                </h5>
                                <?php if ($unit['unit_description']): ?>
                                    <p class="mb-0 opacity-75 small"><?php echo htmlspecialchars($unit['unit_description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-light text-dark me-3">
                                    <?php echo count($unit['lessons']); ?> Lessons
                                </span>
                                <i class="fas fa-chevron-down collapse-toggle <?php echo $index === 0 ? '' : 'collapsed'; ?>"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="collapse <?php echo $index === 0 ? 'show' : ''; ?>" id="unit<?php echo $unit['unit_id']; ?>">
                        <div class="p-3">
                            <!-- Lessons -->
                            <?php if (!empty($unit['lessons'])): ?>
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-play-circle me-2"></i>Lessons
                                </h6>
                                <?php foreach ($unit['lessons'] as $lesson): ?>
                                    <div class="lesson-item <?php echo $lesson['completed'] ? 'lesson-completed' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <?php if ($lesson['completed']): ?>
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-play-circle text-primary me-2"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($lesson['lesson_title']); ?>
                                                </h6>
                                                <div class="d-flex align-items-center text-muted small">
                                                    <?php if ($lesson['duration_minutes']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-clock me-1"></i><?php echo $lesson['duration_minutes']; ?> min
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($lesson['video_url'] || $lesson['youtube_url']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-video me-1"></i>Video
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($lesson['file_path']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-file me-1"></i>File
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($lesson['completed']): ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-check me-1"></i>Completed <?php echo date('M d', strtotime($lesson['completion_date'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="lesson_view.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" 
                                                   class="btn btn-lesson btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>

                                                <!-- NEW: Show lesson-level quiz button if a quiz is attached to this lesson -->
                                                <?php if (!empty($lesson['lesson_quiz'])): 
                                                    $lq = $lesson['lesson_quiz']; ?>
                                                    <?php if ($lq['attempt_count'] >= $lq['max_attempts'] && $lq['best_score'] < $lq['passing_score']): ?>
                                                        <span class="badge bg-danger align-self-center">Max Attempts Reached</span>
                                                    <?php else: ?>
                                                        <a href="../Quiz/take_quiz.php?quiz_id=<?php echo $lq['quiz_id']; ?>" 
                                                           class="btn btn-quiz btn-sm">
                                                            <i class="fas fa-play me-1"></i>
                                                            <?php echo ($lq['attempt_count'] ?? 0) > 0 ? 'Retake' : 'Take Quiz'; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Course-Level Quizzes (displayed once after all units) -->
            <?php if (!empty($courseQuizzes)): ?>
                <div class="unit-card mt-4">
                    <div class="unit-header">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Course Assessments
                        </h5>
                    </div>
                    <div class="p-3">
                        <?php foreach ($courseQuizzes as $quiz): ?>
                            <div class="quiz-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <i class="fas fa-question me-2"></i><?php echo htmlspecialchars($quiz['quiz_title']); ?>
                                        </h6>
                                        <div class="d-flex align-items-center text-muted small">
                                            <span class="me-3">
                                                <i class="fas fa-question me-1"></i><?php echo $quiz['question_count']; ?> Questions
                                            </span>
                                            <?php if ($quiz['time_limit_minutes']): ?>
                                                <span class="me-3">
                                                    <i class="fas fa-clock me-1"></i><?php echo $quiz['time_limit_minutes']; ?> min
                                                </span>
                                            <?php endif; ?>
                                            <span class="me-3">
                                                <i class="fas fa-target me-1"></i>Pass: <?php echo $quiz['passing_score']; ?>%
                                            </span>
                                            <?php if ($quiz['attempt_count'] > 0): ?>
                                                <span class="text-warning">
                                                    <i class="fas fa-trophy me-1"></i>Best: <?php echo number_format($quiz['best_score'], 1); ?>%
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($quiz['quiz_description']): ?>
                                            <p class="mb-0 mt-1 small text-muted"><?php echo htmlspecialchars($quiz['quiz_description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($quiz['attempt_count'] >= $quiz['max_attempts'] && $quiz['best_score'] < $quiz['passing_score']): ?>
                                            <span class="badge bg-danger">Max Attempts Reached</span>
                                        <?php else: ?>
                                            <a href="../Quiz/take_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                               class="btn btn-quiz btn-sm">
                                                <i class="fas fa-play me-1"></i>
                                                <?php echo $quiz['attempt_count'] > 0 ? 'Retake' : 'Start'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle collapse toggle icons
        document.addEventListener('DOMContentLoaded', function() {
            const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
            
            collapseElements.forEach(element => {
                const target = document.querySelector(element.getAttribute('data-bs-target'));
                const icon = element.querySelector('.collapse-toggle');
                
                target.addEventListener('show.bs.collapse', function() {
                    icon.classList.remove('collapsed');
                });
                
                target.addEventListener('hide.bs.collapse', function() {
                    icon.classList.add('collapsed');
                });
            });
        });
    </script>
</body>
</html>
