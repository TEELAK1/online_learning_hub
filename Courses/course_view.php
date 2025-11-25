<?php
// Central course view entry point for students
// Redirects to the enhanced course content page

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../Functionality/login.php');
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    header('Location: ../Student/student_dashboard.php');
    exit();
}

// Using the existing enhanced course content page as the main course view
header('Location: ../Student/enhanced_course_content.php?course_id=' . $course_id);
exit();
