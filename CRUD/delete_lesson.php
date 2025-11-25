<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated as instructor
if (!Auth::isAuthenticated() || !Auth::hasRole('instructor')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();

// Check lesson ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Lesson ID missing!");
}
$lesson_id = intval($_GET['id']);

if ($lesson_id <= 0) {
    die("Invalid lesson ID!");
}

// First, fetch lesson info (for redirect or deleting file if needed)
$stmt = $db->prepare("SELECT file_path FROM course_lessons WHERE lesson_id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$result = $stmt->get_result();
$lesson = $result->fetch_assoc();

// Delete attached file if exists
if ($lesson && !empty($lesson['file_path'])) {
    $file_path = "../uploads/" . basename($lesson['file_path']); // Prevent directory traversal
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Delete the lesson
$delete_stmt = $db->prepare("DELETE FROM course_lessons WHERE lesson_id = ?");
if (!$delete_stmt) {
    die("Prepare failed: " . $db->error);
}
$delete_stmt->bind_param("i", $lesson_id);

if ($delete_stmt->execute()) {
    // Redirect back to lessons management
    header("Location: create_lesson.php?msg=deleted");
    exit();
} else {
    echo "Error deleting lesson: " . $delete_stmt->error;
}

$delete_stmt->close();
?>
