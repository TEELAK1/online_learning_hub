<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$host = "localhost:3306";
$user = "root";
$pass = "";
$db   = "onlinelearninghub_new";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure course_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Course ID missing in URL. Example: manage_lessons.php?course_id=1");
}
$course_id = intval($_GET['id']);

// Fetch course info
$course_sql = "SELECT * FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$course_result = $stmt->get_result();
if ($course_result->num_rows === 0) {
    die("❌ Course not found with ID = $course_id");
}
$course = $course_result->fetch_assoc();
$stmt->close();

// Fetch lessons for this course
$lessons_sql = "SELECT * FROM course_lessons WHERE id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($lessons_sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$lessons_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Lessons - <?= htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

    <h2>📘 Manage Lessons for Course: <?= htmlspecialchars($course['title']); ?></h2>

    <a href="create_lesson.php?course_id=<?= $course_id; ?>" class="btn btn-success mb-3">➕ Add New Lesson</a>
    <a href="../Instructor/dashboard.php" class="btn btn-secondary mb-3">⬅️ Back to Dashboard</a>

    <?php if ($lessons_result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Content Preview</th>
                    <th>YouTube</th>
                    <th>File</th>
                    <th>External Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $lesson['id']; ?></td>
                    <td><?= htmlspecialchars($lesson['title']); ?></td>
                    <td><?= substr(strip_tags($lesson['content']), 0, 50) . "..."; ?></td>
                    <td>
                        <?php if (!empty($lesson['youtube_url'])): ?>
                            <a href="<?= htmlspecialchars($lesson['youtube_url']); ?>" target="_blank">🎥 Watch</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($lesson['file_path'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($lesson['file_path']); ?>" target="_blank">📂 File</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($lesson['external_link'])): ?>
                            <a href="<?= htmlspecialchars($lesson['external_link']); ?>" target="_blank">🔗 Link</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_lesson.php?id=<?= $lesson['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                        <a href="delete_lesson.php?id=<?= $lesson['id']; ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this lesson?');">🗑 Delete</a>
                        <a href="../Instructor/view_lesson.php?id=<?= $lesson['id']; ?>" class="btn btn-info btn-sm">👁 Preview</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No lessons found for this course.</div>
    <?php endif; ?>

</body>
</html>

