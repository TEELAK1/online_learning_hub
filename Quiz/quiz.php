<?php
session_start();

$host = "localhost:3306";
$user = "root";
$pass = "";
$db   = "onlinelearninghub";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['course_id'])) {
    echo "Course ID not found.";
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id = intval($_GET['course_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Course Quiz</h3>
        </div>
        <div class="card-body">
            <?php
            // Check if quiz was already attempted
            $check = $conn->prepare("SELECT * FROM quiz_results WHERE student_id = ? AND course_id = ?");
            $check->bind_param("ii", $student_id, $course_id);
            $check->execute();
            $already = $check->get_result();

            if ($already->num_rows > 0) {
                echo "<div class='alert alert-info'>You have already taken this quiz. You cannot retake it.</div>";
                echo "<a href='student_dashboard.php' class='btn btn-secondary'>Back to Dashboard</a>";
            } else {
                // Fetch quiz questions
                $stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo "<form method='post' action='submit_quiz.php'>";
                    $q_no = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='mb-4'>";
                        echo "<p><strong>Q{$q_no}:</strong> " . htmlspecialchars($row['question']) . "</p>";

                        foreach (['A', 'B', 'C', 'D'] as $opt) {
                            $option_text = htmlspecialchars($row['option_' . strtolower($opt)]);
                            echo "
                                <div class='form-check'>
                                    <input class='form-check-input' type='radio' name='answer[{$row['id']}]' value='$opt' required>
                                    <label class='form-check-label'>$opt. $option_text</label>
                                </div>";
                        }

                        echo "</div>";
                        $q_no++;
                    }
                    echo "<input type='hidden' name='course_id' value='$course_id'>";
                    echo "<button type='submit' class='btn btn-success'>Submit Quiz</button>";
                    echo "</form>";
                } else {
                    echo "<div class='alert alert-warning'>No quiz available for this course.</div>";
                }
            }
            ?>
        </div>
    </div>
</div>
</body>
</html>
