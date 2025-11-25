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

// Check unit ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Unit ID missing!");
}
$unit_id = intval($_GET['id']);

if ($unit_id <= 0) {
    die("Invalid unit ID!");
}

// Delete lessons under this unit first (if not using CASCADE)
$delete_lessons_stmt = $db->prepare("DELETE FROM course_lessons WHERE unit_id = ?");
$delete_lessons_stmt->bind_param("i", $unit_id);
$delete_lessons_stmt->execute();

// Delete the unit
$stmt = $db->prepare("DELETE FROM course_units WHERE unit_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $unit_id);

if ($stmt->execute()) {
    // Redirect back to units management
    header("Location: course_unit.php?msg=deleted");
    exit();
} else {
    echo "Error deleting unit: " . $stmt->error;
}

$stmt->close();
?>
