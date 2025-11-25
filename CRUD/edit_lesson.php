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

// Check lesson ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Lesson ID missing in URL. Example: edit_lesson.php?id=1");
}
$lesson_id = intval($_GET['id']);

// Fetch existing lesson
$lesson_sql = "SELECT * FROM course_lessons WHERE id = ?";
$stmt = $conn->prepare($lesson_sql);
if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Lesson not found in course_lessons with ID = $lesson_id");
}
$lesson = $result->fetch_assoc();
$stmt->close();

// Handle form update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $unit_id        = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : NULL;
    $lesson_title   = $_POST['lesson_title'];
    $lesson_content = $_POST['lesson_content'];
    $youtube_url    = !empty($_POST['youtube_url']) ? $_POST['youtube_url'] : NULL;
    $external_link  = !empty($_POST['external_link']) ? $_POST['external_link'] : NULL;

    // File upload (optional)
    $file_path = $lesson['file_path']; // keep old file unless replaced
    if (!empty($_FILES['lesson_file']['name'])) {
        // Security: File upload validation
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'avi', 'mov', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        $dangerous_types = ['php', 'exe', 'sh', 'bat', 'js', 'html', 'htm', 'svg'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        $file_size = $_FILES['lesson_file']['size'];
        $file_ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
        
        // Check file size
        if ($file_size > $max_size) {
            $error = "File size exceeds 50MB limit.";
        }
        // Check for dangerous extensions
        elseif (in_array($file_ext, $dangerous_types)) {
            $error = "File type not allowed for security reasons.";
        }
        // Check if file type is allowed
        elseif (!in_array($file_ext, $allowed_types)) {
            $error = "File type not supported. Allowed: PDF, DOC, PPT, Images, Videos, ZIP.";
        }
        else {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            // Generate secure filename
            $file_name = uniqid() . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $target_file)) {
                $file_path = $file_name;
            } else {
                $error = "Failed to upload file.";
            }
        }
    }

    // Update DB
    $update_sql = "UPDATE course_lessons 
                   SET unit_id = ?, title = ?, content = ?, youtube_url = ?, file_path = ?, external_link = ?
                   WHERE id = ?";
    $stmt = $conn->prepare($update_sql);

    if ($stmt === false) {
        die("❌ Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isssssi",
        $unit_id,
        $lesson_title,
        $lesson_content,
        $youtube_url,
        $file_path,
        $external_link,
        $lesson_id
    );

    if ($stmt->execute()) {
        $success = "✅ Lesson updated successfully!";
        // Refresh lesson data
        $lesson['unit_id']       = $unit_id;
        $lesson['title']         = $lesson_title;
        $lesson['content']       = $lesson_content;
        $lesson['youtube_url']   = $youtube_url;
        $lesson['file_path']     = $file_path;
        $lesson['external_link'] = $external_link;
    } else {
        $error = "❌ Update failed: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Lesson</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#lesson_content',
            plugins: 'lists link image media table',
            toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media',
            menubar: false
        });
    </script>
</head>
<body class="container py-5">

    <h2>Edit Lesson</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success; ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="unit_id" class="form-label">Unit (Optional)</label>
            <input type="number" name="unit_id" id="unit_id" class="form-control"
                   value="<?= htmlspecialchars($lesson['unit_id']); ?>">
        </div>

        <div class="mb-3">
            <label for="lesson_title" class="form-label">Lesson Title</label>
            <input type="text" name="lesson_title" id="lesson_title" class="form-control"
                   value="<?= htmlspecialchars($lesson['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="lesson_content" class="form-label">Lesson Content</label>
            <textarea name="lesson_content" id="lesson_content" rows="5" class="form-control"><?= htmlspecialchars($lesson['content']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="youtube_url" class="form-label">YouTube Video URL (Optional)</label>
            <input type="url" name="youtube_url" id="youtube_url" class="form-control"
                   value="<?= htmlspecialchars($lesson['youtube_url']); ?>">
        </div>

        <div class="mb-3">
            <label for="lesson_file" class="form-label">Upload File (Optional)</label>
            <input type="file" name="lesson_file" id="lesson_file" class="form-control">
            <?php if (!empty($lesson['file_path'])): ?>
                <p class="mt-2">Current File: 
                    <a href="../uploads/<?= htmlspecialchars($lesson['file_path']); ?>" target="_blank">
                        <?= htmlspecialchars($lesson['file_path']); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="external_link" class="form-label">External Resource Link (Optional)</label>
            <input type="url" name="external_link" id="external_link" class="form-control"
                   value="<?= htmlspecialchars($lesson['external_link']); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Update Lesson</button>
        <a href="../Instructor/instructor_dashboard.php" class="btn btn-secondary">Back</a>
    </form>

</body>
</html>

