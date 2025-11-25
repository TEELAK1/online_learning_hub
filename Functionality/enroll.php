<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();

$student_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'] ?? null;

// Validate course_id is a positive integer
if ($course_id && filter_var($course_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {

    // Check if already enrolled
    $check = $db->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ?");
    if (!$check) {
        die("Prepare failed: " . $db->error);
    }

    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("ii", $student_id, $course_id);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    }
    $check->close();
}

header("Location: ../Courses/course_view.php?course_id=" . (int)$course_id);
exit();
