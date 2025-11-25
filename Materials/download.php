<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($material_id <= 0) {
    die("Invalid material ID");
}

// Check if materials table exists
$tableCheck = $db->query("SHOW TABLES LIKE 'materials'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    die("Materials system not available");
}

// Get material information
$stmt = $db->prepare("
    SELECT m.*, c.title as course_title, c.instructor_id
    FROM materials m
    INNER JOIN courses c ON m.course_id = c.course_id
    WHERE m.material_id = ?
");

if (!$stmt) {
    die("Database error: " . $db->error);
}

$stmt->bind_param("i", $material_id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();

if (!$material) {
    die("Material not found");
}

// Check access permissions
$hasAccess = false;

if ($user_role === 'admin') {
    $hasAccess = true;
} elseif ($user_role === 'instructor' && $material['instructor_id'] == $user_id) {
    $hasAccess = true;
} elseif ($user_role === 'student') {
    // Check if student is enrolled in the course
    $enrollStmt = $db->prepare("
        SELECT 1 FROM enrollments 
        WHERE student_id = ? AND course_id = ? AND status = 'active'
    ");
    $enrollStmt->bind_param("ii", $user_id, $material['course_id']);
    $enrollStmt->execute();
    $hasAccess = $enrollStmt->get_result()->num_rows > 0;
}

if (!$hasAccess) {
    die("Access denied");
}

// Construct file path
$uploadDir = '../uploads/materials/';
$filePath = $uploadDir . basename($material['file_path']);

// Check if file exists
if (!file_exists($filePath)) {
    die("File not found on server");
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $material['file_name'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($filePath);
exit();
?>
