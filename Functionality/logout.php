<?php
session_start();

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    require_once '../includes/auth.php';
    try {
        $auth = new Auth();
        $auth->logActivity($_SESSION['user_id'], $_SESSION['role'], 'logout');
    } catch (Exception $e) {
        error_log("Logout activity logging failed: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to home page
header("Location: ../index.php?logout=success");
exit();
