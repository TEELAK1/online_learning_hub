<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated as student
if (!Auth::isAuthenticated() || !Auth::hasRole('student')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer']) && isset($_POST['course_id'])) {
    $answers = $_POST['answer'];
    $course_id = intval($_POST['course_id']);

    $score = 0;
    $total = count($answers);
    $review_html = "";

    // Prevent duplicate submission - need quiz_id instead of course_id
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    if ($quiz_id > 0) {
        $check = $db->prepare("SELECT * FROM quiz_results WHERE student_id = ? AND quiz_id = ?");
        $check->bind_param("ii", $student_id, $quiz_id);
        $check->execute();
        $already = $check->get_result();

        if ($already->num_rows > 0) {
            echo "<div class='alert alert-warning text-center mt-5'>❌ You already submitted this quiz!</div>";
            echo "<div class='text-center'><a href='../Student/student_dashboard.php' class='btn btn-secondary'>🔙 Back to Dashboard</a></div>";
            exit();
        }
    }

    foreach ($answers as $question_id => $selected_option) {
        $stmt = $db->prepare("SELECT question_text as question, option_a, option_b, option_c, option_d, correct_answer as correct_option FROM quiz_questions WHERE question_id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $correct_option = $result['correct_option'];
        if ($selected_option === $correct_option) $score++;

        $review_html .= "<div class='mb-3 border rounded p-3 bg-white shadow-sm'>";
        $review_html .= "<p><strong>Q:</strong> " . htmlspecialchars($result['question']) . "</p>";

        foreach (['A', 'B', 'C', 'D'] as $opt) {
            $text = htmlspecialchars($result['option_' . strtolower($opt)]);
            $classes = "p-2 rounded";

            if ($opt == $correct_option) {
                $classes .= " bg-success text-white fw-bold";
            } elseif ($opt == $selected_option) {
                $classes .= " bg-danger text-white";
            } else {
                $classes .= " bg-light";
            }

            $review_html .= "<div class='$classes mb-1'>$opt. $text</div>";
        }

        $review_html .= "</div>";
    }

    // Save to quiz_results table
    if ($quiz_id > 0) {
        $insert = $db->prepare("INSERT INTO quiz_results (student_id, quiz_id, score, taken_at) VALUES (?, ?, ?, NOW())");
        $insert->bind_param("iid", $student_id, $quiz_id, $score);
        $insert->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
            <h3>🎉 Quiz Completed</h3>
        </div>
        <div class="card-body">
            <h5 class="text-center mb-4">You scored <strong><?= $score ?></strong> out of <strong><?= $total ?></strong></h5>
            <?= $review_html ?>
            <div class="text-center mt-4">
                <a href="../Student/student_dashboard.php" class="btn btn-primary">🔙 Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
