<?php
session_start();
require_once '../config/database.php';

// Simple HTML sanitizer fallback (uses permissive whitelist). If HTMLPurifier
// is available in the environment prefer that instead.
function sanitize_html_for_output($html) {
    if (!$html) return '';

    // Prefer HTMLPurifier if installed
    if (class_exists('HTMLPurifier')) {
        static $purifier = null;
        if ($purifier === null) {
            $cfgClass = 'HTMLPurifier_Config';
            $purifierClass = 'HTMLPurifier';
            if (class_exists($cfgClass) && class_exists($purifierClass)) {
                $config = call_user_func(array($cfgClass, 'createDefault'));
                $config->set('HTML.SafeIframe', true);
                $config->set('URI.SafeIframeRegexp','%^(https?:)?//(www.youtube.com|player.vimeo.com)/%');
                $purifier = new $purifierClass($config);
            } else {
                return '';
            }
        }
        return $purifier->purify($html);
    }

    // Remove script/style blocks
    $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);

    // Remove on* attributes (onclick, onerror, etc.)
    $html = preg_replace('/(<[a-z][^>]*?)\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\')/iu', '$1', $html);

    // Neutralize javascript: URIs in href/src
    $html = preg_replace_callback('/(href|src)=("|\')(.*?)\2/iu', function($m){
        $attr = strtolower($m[1]);
        $val = trim($m[3]);
        if (preg_match('/^\s*javascript:/i', $val)) {
            return $attr.'='.$m[2].'#'.$m[2];
        }
        // Encode the URL to avoid injecting additional attributes
        return $attr.'='.$m[2].htmlspecialchars($val, ENT_QUOTES).''.$m[2];
    }, $html);

    // Whitelist of allowed tags
    $allowed = '<p><a><strong><em><ul><ol><li><br><b><i><u><img><h1><h2><h3><h4><h5><h6><blockquote><iframe>';
    $html = strip_tags($html, $allowed);

    // For img tags, keep only src and alt
    $html = preg_replace_callback('/<img[^>]*>/i', function($m){
        if (preg_match('/src=("|\')(.*?)\1/i', $m[0], $srcm)) {
            $src = $srcm[2];
            if (preg_match('#^(https?:)?//#i', $src) || preg_match('#^data:image/#i', $src)) {
                $alt = '';
                if (preg_match('/alt=("|\')(.*?)\1/i', $m[0], $altm)) $alt = $altm[2];
                return '<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="'.htmlspecialchars($alt, ENT_QUOTES).'"/>';
            }
        }
        return '';
    }, $html);

    // For iframe tags, allow only YouTube/Vimeo by filtering src
    $html = preg_replace_callback('/<iframe[^>]*src=("|\')(.*?)\1[^>]*><\/iframe>/iu', function($m){
        $src = $m[2];
        if (preg_match('#^(https?:)?//(www\.youtube\.com|youtube\.com|player\.vimeo\.com)#i', $src)) {
            return '<iframe src="'.htmlspecialchars($src, ENT_QUOTES).'" frameborder="0" allowfullscreen></iframe>';
        }
        return '';
    }, $html);

    return $html;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

if ($lesson_id <= 0) {
    header("Location: student_dashboard.php");
    exit();
}

// Get lesson details and verify access

// Check if lesson is completed
$lessonStmt = $db->prepare(
    "SELECT 
        cl.*,
        cu.title as unit_title,
        c.title as course_title,
        c.course_id,
        i.name as instructor_name,
        e.enrollment_id
    FROM course_lessons cl
    INNER JOIN course_units cu ON cl.unit_id = cu.unit_id
    INNER JOIN courses c ON cu.course_id = c.course_id
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    INNER JOIN enrollments e ON c.course_id = e.course_id
    WHERE cl.lesson_id = ? AND e.student_id = ? AND e.status = 'active'"
);

if (!$lessonStmt) {
    // prepare failed (schema mismatch or DB issue)
    error_log('lesson_view prepare failed: ' . $db->error);
    header("Location: student_dashboard.php");
    exit();
}

$lessonStmt->bind_param("ii", $lesson_id, $student_id);
if (!$lessonStmt->execute()) {
    error_log('lesson_view execute failed: ' . $lessonStmt->error);
    header("Location: student_dashboard.php");
    exit();
}

$lessonRes = $lessonStmt->get_result();
if (!$lessonRes || $lessonRes->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit();
}
$lesson = $lessonRes->fetch_assoc();
$lessonStmt->close();

// Ensure we record last access (upsert)
$accessStmt = $db->prepare("INSERT INTO lesson_progress (student_id, lesson_id, last_accessed) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_accessed = NOW()");
if ($accessStmt) {
    $accessStmt->bind_param('ii', $student_id, $lesson_id);
    $accessStmt->execute();
    $accessStmt->close();
}

// Check if lesson is completed
$progress = null;
$progressStmt = $db->prepare("SELECT completed, completion_date, time_spent_minutes FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
if ($progressStmt) {
    $progressStmt->bind_param("ii", $student_id, $lesson_id);
    $progressStmt->execute();
    $pRes = $progressStmt->get_result();
    if ($pRes && $pRes->num_rows > 0) {
        $progress = $pRes->fetch_assoc();
    }
    $progressStmt->close();
}

// Fetch unit-level questions (unit questions for the current unit)
$unitQuizzes = [];
if (isset($lesson['unit_id'])) {
    $quizStmt = $db->prepare("SELECT question_id, question_text, explanation, points, question_type FROM unit_questions WHERE unit_id = ? AND is_active = 1 ORDER BY order_index ASC");
    if ($quizStmt) {
        $quizStmt->bind_param('i', $lesson['unit_id']);
        if ($quizStmt->execute()) {
            $qRes = $quizStmt->get_result();
            if ($qRes && $qRes->num_rows > 0) {
                while ($qRow = $qRes->fetch_assoc()) {
                    // For compatibility with display code, rename question_text to title and add description
                    $qRow['title'] = $qRow['question_text'];
                    $qRow['description'] = $qRow['explanation'];
                    $qRow['quiz_id'] = $qRow['question_id'];
                    $unitQuizzes[] = $qRow;
                }
            }
        }
        $quizStmt->close();
    }
}

// Handle lesson completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $completeStmt = $db->prepare("
        UPDATE lesson_progress 
        SET completed = 1, completion_date = NOW() 
        WHERE student_id = ? AND lesson_id = ?
    ");
    $completeStmt->bind_param("ii", $student_id, $lesson_id);
    
    if ($completeStmt->execute()) {
        header("Location: lesson_view.php?lesson_id=$lesson_id&completed=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .lesson-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .lesson-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin-bottom: 2rem;
        }
        
        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }
        
        .lesson-text {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #374151;
        }
        
        .completion-card {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .btn-complete {
            background: var(--success-color);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-complete:hover {
            background: #047857;
            color: white;
        }

        .btn-quiz-header {
            background: #fbbf24;
            border: none;
            color: #1f2937;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-quiz-header:hover {
            background: #f59e0b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
            color: white;
        }

        .quiz-section-highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
            border: 2px solid #f59e0b;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0%, 100% { border-color: #f59e0b; }
            50% { border-color: #d97706; }
        }
    </style>
</head>
<body>
    <!-- Lesson Header -->
    <div class="lesson-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb" class="mb-2">
                        <ol class="breadcrumb text-white-50">
                            <li class="breadcrumb-item">
                                <a href="student_dashboard.php" class="text-white-50">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="enhanced_course_content.php?course_id=<?php echo $lesson['course_id']; ?>" class="text-white-50">
                                    <?php echo htmlspecialchars($lesson['course_title']); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item text-white" aria-current="page">
                                <?php echo htmlspecialchars($lesson['unit_title']); ?>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="mb-2"><?php echo htmlspecialchars($lesson['title']); ?></h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user me-2"></i>Instructor: <?php echo htmlspecialchars($lesson['instructor_name']); ?>
                    </p>
                    <?php if ($lesson['duration_minutes']): ?>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-clock me-2"></i>Duration: <?php echo $lesson['duration_minutes']; ?> minutes
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if (!empty($unitQuizzes)): ?>
                        <div class="mb-2">
                            <a href="#quizSection" class="btn btn-quiz-header">
                                <i class="fas fa-clipboard-check me-2"></i>Take Quiz (<?php echo count($unitQuizzes); ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                    <a href="enhanced_course_content.php?course_id=<?php echo $lesson['course_id']; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Course
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['completed'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>Lesson completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="lesson-content">
            <!-- Video Content -->
            <?php if ($lesson['youtube_url']): ?>
                <div class="video-container">
                    <?php
                    $youtube_id = '';
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $lesson['youtube_url'], $matches)) {
                        $youtube_id = $matches[1];
                    }
                    ?>
                    <iframe src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                </div>
            <?php elseif ($lesson['video_url']): ?>
                <div class="video-container">
                    <video controls>
                        <source src="<?php echo htmlspecialchars($lesson['video_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>

            <!-- Lesson Content -->
            <?php if (!empty($lesson['content'])): ?>
                <div class="lesson-text">
                    <?php echo sanitize_html_for_output($lesson['content']); ?>
                </div>
            <?php endif; ?>

            <!-- File Download -->
            <?php if ($lesson['file_path']): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-file me-2"></i>Lesson Materials</h6>
                    <a href="../Materials/download.php?lesson_id=<?php echo $lesson_id; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-2"></i>Download File
                    </a>
                </div>
            <?php endif; ?>

            <!-- External Link -->
            <?php if ($lesson['external_link']): ?>
                <div class="mt-4 p-3 bg-info bg-opacity-10 rounded">
                    <h6><i class="fas fa-external-link-alt me-2"></i>External Resource</h6>
                    <a href="<?php echo htmlspecialchars($lesson['external_link']); ?>" 
                       target="_blank" 
                       class="btn btn-outline-info btn-sm">
                        <i class="fas fa-external-link-alt me-2"></i>Open Link
                    </a>
                </div>
            <?php endif; ?>

            <!-- Unit Quizzes Dropdown -->
            <?php if (!empty($unitQuizzes)): ?>
                <div id="quizSection" class="mt-4 p-4 bg-warning bg-opacity-15 rounded-lg border border-warning border-opacity-50">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-trophy text-warning" style="font-size: 1.5rem; margin-right: 1rem;"></i>
                        <div>
                            <h5 class="mb-0">Unit Assessment</h5>
                            <small class="text-muted"><?php echo count($unitQuizzes); ?> quiz<?php echo count($unitQuizzes) != 1 ? 'zes' : ''; ?> available</small>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-warning btn-lg dropdown-toggle w-100" type="button" id="quizDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-list me-2"></i>Select a Quiz to Take
                        </button>
                        <ul class="dropdown-menu w-100" aria-labelledby="quizDropdown">
                            <?php foreach ($unitQuizzes as $q): ?>
                                <li>
                                    <a class="dropdown-item py-3" href="take_unit_quiz.php?unit_id=<?php echo $lesson['unit_id']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($q['title']); ?></strong>
                                                <div class="small text-muted">
                                                    <?php if ($q['description']): ?>
                                                        <?php echo htmlspecialchars(substr($q['description'], 0, 80)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <i class="fas fa-star me-1"></i><?php echo $q['points']; ?> points
                                                </div>
                                            </div>
                                            <i class="fas fa-arrow-right text-warning"></i>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completion Section -->
        <?php if ($progress && $progress['completed']): ?>
            <div class="completion-card mb-4">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h4>Lesson Completed!</h4>
                <p class="mb-0">
                    Completed on <?php echo date('F j, Y', strtotime($progress['completion_date'])); ?>
                </p>
            </div>
        <?php else: ?>
            <div class="text-center mb-4">
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_complete" class="btn btn-complete">
                        <i class="fas fa-check me-2"></i>Mark as Complete
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="row mb-4">
            <div class="col-md-6">
                <!-- Previous Lesson Link -->
                <?php
                $prevStmt = $db->prepare("
                    SELECT cl.lesson_id, cl.title 
                    FROM course_lessons cl
                    INNER JOIN course_units cu ON cl.unit_id = cu.unit_id
                    WHERE cu.course_id = ? AND cl.lesson_id < ?
                    ORDER BY cl.lesson_id DESC
                    LIMIT 1
                ");
                $prevStmt->bind_param("ii", $lesson['course_id'], $lesson_id);
                $prevStmt->execute();
                $prevLesson = $prevStmt->get_result()->fetch_assoc();
                ?>
                <?php if ($prevLesson): ?>
                    <a href="lesson_view.php?lesson_id=<?php echo $prevLesson['lesson_id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-chevron-left me-2"></i>Previous: <?php echo htmlspecialchars($prevLesson['title']); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <!-- Next Lesson Link -->
                <?php
                $nextStmt = $db->prepare("
                    SELECT cl.lesson_id, cl.title 
                    FROM course_lessons cl
                    INNER JOIN course_units cu ON cl.unit_id = cu.unit_id
                    WHERE cu.course_id = ? AND cl.lesson_id > ?
                    ORDER BY cl.lesson_id ASC
                    LIMIT 1
                ");
                $nextStmt->bind_param("ii", $lesson['course_id'], $lesson_id);
                $nextStmt->execute();
                $nextLesson = $nextStmt->get_result()->fetch_assoc();
                ?>
                <?php if ($nextLesson): ?>
                    <a href="lesson_view.php?lesson_id=<?php echo $nextLesson['lesson_id']; ?>" 
                       class="btn btn-outline-primary">
                        Next: <?php echo htmlspecialchars($nextLesson['title']); ?><i class="fas fa-chevron-right ms-2"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
