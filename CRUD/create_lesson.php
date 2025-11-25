<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Check database schema
function checkUnitSchema($db) {
    $result = $db->query("SHOW TABLES LIKE 'course_units'");
    return $result && $result->num_rows > 0;
}

$hasUnitsTable = checkUnitSchema($db);

$message = "";
$success = false;

// Fetch courses and units for dropdown
$courses = [];
$units = [];

try {
    // Fetch instructor's courses - use correct column names
    $stmt = $db->prepare("SELECT course_id as id, title FROM courses WHERE instructor_id = ? ORDER BY title");
    if ($stmt) {
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Fetch units if table exists - use correct column names
if ($hasUnitsTable) {
        $stmt = $db->prepare("
            SELECT cu.unit_id as id, cu.title as unit_name, c.title as course_title 
            FROM course_units cu 
            JOIN courses c ON cu.course_id = c.course_id 
            WHERE c.instructor_id = ? 
            ORDER BY c.title, cu.title
        ");
        if ($stmt) {
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
    
    // Debug: If no units found, try a simpler query
    if (empty($units) && $hasUnitsTable) {
        $debugStmt = $db->prepare("SELECT unit_id as id, title as unit_name, course_id FROM course_units ORDER BY title");
        if ($debugStmt) {
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            while ($row = $debugResult->fetch_assoc()) {
                // Get course title for each unit
                $courseStmt = $db->prepare("SELECT title FROM courses WHERE course_id = ?");
                if ($courseStmt) {
                    $courseStmt->bind_param("i", $row['course_id']);
                    $courseStmt->execute();
                    $courseResult = $courseStmt->get_result();
                    $courseData = $courseResult->fetch_assoc();
                    $row['course_title'] = $courseData['title'] ?? 'Unknown Course';
                    $courseStmt->close();
                }
                $units[] = $row;
            }
            $debugStmt->close();
        }
    }
} catch (Exception $e) {
    $message = "Error loading data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $lesson_title = trim($_POST['lesson_title'] ?? '');
    $lesson_content = trim($_POST['lesson_content'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $external_link = trim($_POST['external_link'] ?? '');
    $lesson_order = intval($_POST['lesson_order'] ?? 1);

    // Validation
    if (!$hasUnitsTable) {
        $message = "Course units table not found. Please create the database structure first.";
    } elseif ($unit_id <= 0) {
        $message = "Please select a valid unit.";
    } elseif (empty($lesson_title)) {
        $message = "Lesson title is required.";
    } else {
        try {
            // Verify unit exists and belongs to instructor
            $stmt = $db->prepare("
                SELECT cu.unit_id, cu.course_id 
                FROM course_units cu
                JOIN courses c ON cu.course_id = c.course_id
                WHERE cu.unit_id = ? AND c.instructor_id = ?
            ");

            if (!$stmt) {
                $message = "Database error: " . $db->error;
            } else {
                $stmt->bind_param("ii", $unit_id, $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $message = "Selected unit does not exist or you don't have permission.";
                } else {
                    $unit_data = $result->fetch_assoc();
                    $course_id = $unit_data['course_id'];
                    
                    // Handle file upload if provided
                    $file_path = null;
                    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['size'] > 0) {
                        // Security: File upload validation
                        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'avi', 'mov', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
                        $dangerous_types = ['php', 'exe', 'sh', 'bat', 'js', 'html', 'htm', 'svg'];
                        $max_size = 50 * 1024 * 1024; // 50MB
                        
                        $file_size = $_FILES['lesson_file']['size'];
                        $file_ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
                        
                        // Check file size
                        if ($file_size > $max_size) {
                            $message = "File size exceeds 50MB limit.";
                        }
                        // Check for dangerous extensions
                        elseif (in_array($file_ext, $dangerous_types)) {
                            $message = "File type not allowed for security reasons.";
                        }
                        // Check if file type is allowed
                        elseif (!in_array($file_ext, $allowed_types)) {
                            $message = "File type not supported. Allowed: PDF, DOC, PPT, Images, Videos, ZIP.";
                        }
                        else {
                            $upload_dir = '../uploads/lessons/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            // Generate secure filename
                            $file_name = uniqid() . '_' . time() . '.' . $file_ext;
                            $file_path = $upload_dir . $file_name;
                            
                            if (!move_uploaded_file($_FILES['lesson_file']['tmp_name'], $file_path)) {
                                $file_path = null;
                                $message = "Failed to upload file.";
                            }
                        }
                    }
                    
                    // INSERT LESSON
                    $sql = "
                        INSERT INTO course_lessons 
                        (unit_id, title, content, youtube_url, external_link, file_path, order_index, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ";

                    $stmt = $db->prepare($sql);

                    if (!$stmt) {
                        $message = "Database error: " . $db->error;
                    } else {
                        // Correct binding: i = int, s = string
                        $stmt->bind_param(
                            "isssssi",
                            $unit_id,
                            $lesson_title,
                            $lesson_content,
                            $youtube_url,
                            $external_link,
                            $file_path,
                            $lesson_order
                        );

                        if ($stmt->execute()) {
                            $success = true;
                            $message = "Lesson '{$lesson_title}' created successfully!";
                            $_POST = [];
                        } else {
                            $message = "Error creating lesson: " . $stmt->error;
                        }

                        $stmt->close();
                    }
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lesson - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/ghqsafb8e1cx5ea44pg8qp2bq6s1ooqpnqqq7j77n714a420/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--light-color);
            color: var(--text-primary);
        }
        
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .lesson-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .content-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .file-upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .file-upload-zone:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>Online Learning Hub
            </a>
            <div class="d-flex align-items-center">
                <a href="../Instructor/instructor_dashboard.php" class="btn btn-outline-primary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($instructor_name); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../Functionality/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Create New Lesson 📖</h1>
                    <p class="mb-0 opacity-75">Add engaging content to your course units</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../Instructor/create_unit.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Create Unit First
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$hasUnitsTable): ?>
            <div class="lesson-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                    <h4>Database Setup Required</h4>
                    <p class="text-muted">The course units table is not found. Please run the database schema update first.</p>
                    <a href="../setup.php" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Setup Database
                    </a>
                </div>
            </div>
        <?php elseif (empty($units)): ?>
            <div class="lesson-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                    <h4>No Units Found</h4>
                    <p class="text-muted">You need to create course units first before adding lessons.</p>
                    <a href="../Instructor/create_unit.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Your First Unit
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Lesson Creation Form -->
            <div class="lesson-card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-book-open me-2"></i>Create New Lesson</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="lessonForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_id" class="form-label fw-medium">Select Unit</label>
                                    <select class="form-select" id="unit_id" name="unit_id" required>
                                        <option value="">-- Choose Unit --</option>
                                        <?php foreach ($units as $unit): ?>
                                            <option value="<?php echo $unit['id']; ?>" <?php echo (isset($_POST['unit_id']) && $_POST['unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($unit['course_title'] . ' - ' . $unit['unit_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lesson_order" class="form-label fw-medium">Lesson Order</label>
                                    <input type="number" class="form-control" id="lesson_order" name="lesson_order" 
                                           value="<?php echo htmlspecialchars($_POST['lesson_order'] ?? '1'); ?>" min="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="lesson_title" class="form-label fw-medium">Lesson Title</label>
                            <input type="text" class="form-control" id="lesson_title" name="lesson_title" 
                                   placeholder="Enter lesson title" 
                                   value="<?php echo htmlspecialchars($_POST['lesson_title'] ?? ''); ?>" required>
                        </div>

                        <div class="content-section">
                            <label for="lesson_content" class="form-label fw-medium mb-3">Lesson Content</label>
                            <textarea class="form-control" id="lesson_content" name="lesson_content" rows="10"
                                      placeholder="Enter your lesson content here..."><?php echo htmlspecialchars($_POST['lesson_content'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="youtube_url" class="form-label fw-medium">YouTube Video URL (Optional)</label>
                                    <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                           placeholder="https://www.youtube.com/watch?v=..."
                                           value="<?php echo htmlspecialchars($_POST['youtube_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="external_link" class="form-label fw-medium">External Link (Optional)</label>
                                    <input type="url" class="form-control" id="external_link" name="external_link" 
                                           placeholder="https://example.com/resource"
                                           value="<?php echo htmlspecialchars($_POST['external_link'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Lesson File (Optional)</label>
                            <div class="file-upload-zone">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-2">Drag & drop a file here or click to browse</p>
                                <input type="file" class="form-control" id="lesson_file" name="lesson_file" style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('lesson_file').click()">
                                    <i class="fas fa-folder-open me-2"></i>Choose File
                                </button>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Supported: PDF, DOC, PPT, Images, Videos (Max 50MB)
                                    </small>
                                </div>
                            </div>
                            <div id="fileInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-file me-2"></i>
                                    <span id="fileName"></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-save me-2"></i>Create Lesson
                            </button>
                            <a href="../Instructor/instructor_dashboard.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#lesson_content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter ' +
                     'alignright alignjustify | bullist numlist outdent indent | ' +
                     'removeformat | help',
            content_style: 'body { font-family: Inter, Arial, sans-serif; font-size: 14px }'
        });

        // File upload handling
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('lesson_file');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileName.textContent = this.files[0].name;
                    fileInfo.style.display = 'block';
                } else {
                    fileInfo.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>