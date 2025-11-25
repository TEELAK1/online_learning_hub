<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$material_id = $_GET['id'] ?? 0;

try {
    // Get material info
    $stmt = $db->prepare("SELECT * FROM materials WHERE material_id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Material not found");
    }
    
    $material = $result->fetch_assoc();
    $file_path = '../uploads/' . $material['file_path'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        die("File not found on server");
    }
    
    // Get file info
    $file_name = $material['file_name'];
    $file_size = filesize($file_path);
    $file_type = mime_content_type($file_path);
    
    // Set headers for download
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read file and output
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    die("Error downloading file: " . $e->getMessage());
}
?>
