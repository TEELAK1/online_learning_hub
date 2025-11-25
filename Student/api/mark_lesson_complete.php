<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!Auth::isAuthenticated() || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit();
}

$lesson_id = (int)$_POST['lesson_id'];

// Find course_id for this lesson
$stmt = $db->prepare("SELECT cl.lesson_id, cl.unit_id, cu.course_id FROM course_lessons cl INNER JOIN course_units cu ON cl.unit_id = cu.unit_id WHERE cl.lesson_id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'DB prepare failed']);
    exit();
}
$stmt->bind_param('i', $lesson_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Lesson not found']);
    exit();
}
$row = $res->fetch_assoc();
$course_id = (int)$row['course_id'];

// Mark lesson progress (insert or update)
$completed = 1;
$insert = $db->prepare("INSERT INTO lesson_progress (student_id, lesson_id, completed, completion_date, last_accessed) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE completed = VALUES(completed), completion_date = IF(VALUES(completed)=1, NOW(), completion_date), last_accessed = NOW()");
if (!$insert) {
    http_response_code(500);
    echo json_encode(['error' => 'DB prepare failed insert']);
    exit();
}
$insert->bind_param('iii', $student_id, $lesson_id, $completed);
$ok = $insert->execute();
if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'DB execute failed']);
    exit();
}

// Recalculate progress for this student in the course
$totalLessonsStmt = $db->prepare("SELECT COUNT(*) AS total FROM course_lessons cl INNER JOIN course_units cu ON cl.unit_id = cu.unit_id WHERE cu.course_id = ?");
$totalLessonsStmt->bind_param('i', $course_id);
$totalLessonsStmt->execute();
$totalLessons = (int)$totalLessonsStmt->get_result()->fetch_assoc()['total'];

$completedLessonsStmt = $db->prepare("SELECT COUNT(lp.progress_id) AS completed FROM lesson_progress lp INNER JOIN course_lessons cl ON lp.lesson_id = cl.lesson_id INNER JOIN course_units cu ON cl.unit_id = cu.unit_id WHERE cu.course_id = ? AND lp.student_id = ? AND lp.completed = 1");
$completedLessonsStmt->bind_param('ii', $course_id, $student_id);
$completedLessonsStmt->execute();
$completedLessons = (int)$completedLessonsStmt->get_result()->fetch_assoc()['completed'];

$progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0.00;

// Update enrollment progress
$status = 'active';
$completion_date = null;
if ($progress >= 100) {
    $status = 'completed';
    $completion_date = date('Y-m-d H:i:s');
}

$updateEnroll = $db->prepare("UPDATE enrollments SET progress_percentage = ?, status = ?, completion_date = CASE WHEN ? = 'completed' THEN NOW() ELSE completion_date END WHERE student_id = ? AND course_id = ?");
$updateEnroll->bind_param('dssii', $progress, $status, $status, $student_id, $course_id);
$updateEnroll->execute();

// Auto-generate certificate if course completed and certificate doesn't exist
$certificate_issued = false;
if ($status === 'completed') {
    $checkCert = $db->prepare("SELECT certificate_id FROM certificates WHERE student_id = ? AND course_id = ?");
    if ($checkCert) {
        $checkCert->bind_param('ii', $student_id, $course_id);
        $checkCert->execute();
        $r = $checkCert->get_result();
        if ($r && $r->num_rows === 0) {
            $certificate_code = 'CERT-' . strtoupper(uniqid());
            $insCert = $db->prepare("INSERT INTO certificates (student_id, course_id, certificate_code, issued_at, status) VALUES (?, ?, ?, NOW(), 'active')");
            if ($insCert) {
                $insCert->bind_param('iis', $student_id, $course_id, $certificate_code);
                if ($insCert->execute()) {
                    $certificate_issued = true;
                }
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'progress' => $progress,
    'total_lessons' => $totalLessons,
    'completed_lessons' => $completedLessons,
    'certificate_issued' => $certificate_issued
]);

exit();

?>
