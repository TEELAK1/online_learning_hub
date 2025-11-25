<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in or not instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Check database schema
function checkCourseSchema($db) {
    $result = $db->query("SHOW COLUMNS FROM courses LIKE 'course_id'");
    return $result && $result->num_rows > 0;
}

$hasNewSchema = checkCourseSchema($db);
$courseIdField = $hasNewSchema ? 'course_id' : 'id';

// Fetch instructor's courses
try {
    $stmt = $db->prepare("SELECT {$courseIdField} as id, title FROM courses WHERE instructor_id = ?");
    if (!$stmt) {
        $courses = [];
    } else {
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    $courses = [];
}

// Get pre-selected course if provided
$selected_course_id = $_GET['course_id'] ?? '';

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_id = intval($_POST['course_id'] ?? 0);
    $file_name = trim($_POST['file_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($course_id <= 0) {
        $message = "Please select a course.";
    } elseif (empty($file_name)) {
        $message = "Please enter a file name.";
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please select a valid file to upload.";
    } else {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Validate file type and size
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['file']['size'];
        $max_size = 50 * 1024 * 1024; // 50MB

        if (!in_array($file_extension, $allowed_types)) {
            $message = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
        } elseif ($file_size > $max_size) {
            $message = "File size too large. Maximum size is 50MB.";
        } else {
            $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                try {
                    // Save to database
                    $stmt = $db->prepare("INSERT INTO materials (course_id, file_name, file_path, description, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
                    if (!$stmt) {
                        $message = "Database error: " . $db->error;
                        unlink($file_path);
                    } else {
                        $stmt->bind_param("isss", $course_id, $file_name, $file_path, $description);

                        if ($stmt->execute()) {
                            $success = true;
                            $message = "File '{$file_name}' uploaded successfully!";
                        } else {
                            $message = "Database error: " . $stmt->error;
                            unlink($file_path);
                        }
                        $stmt->close();
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            } else {
                $message = "Failed to upload file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Material - Online Learning Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    
    .upload-card {
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
    
    .file-drop-zone {
      border: 2px dashed var(--border-color);
      border-radius: 12px;
      padding: 3rem;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .file-drop-zone:hover {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.05);
    }
    
    .file-drop-zone.dragover {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.1);
    }
    
    .welcome-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
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
          <h1 class="mb-2">Upload Course Material 📁</h1>
          <p class="mb-0 opacity-75">Share resources and materials with your students</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="view_material.php" class="btn btn-light btn-lg">
            <i class="fas fa-folder me-2"></i>View All Materials
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

    <?php if (empty($courses)): ?>
      <div class="upload-card">
        <div class="card-body text-center py-5">
          <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
          <h4>No Courses Found</h4>
          <p class="text-muted">You need to create a course first before uploading materials.</p>
          <a href="../Instructor/instructor_dashboard.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Your First Course
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="upload-card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Upload New Material</h4>
        </div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="course_id" class="form-label fw-medium">Select Course</label>
                  <select class="form-select" id="course_id" name="course_id" required>
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $course): ?>
                      <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['title']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="file_name" class="form-label fw-medium">Material Name</label>
                  <input type="text" class="form-control" id="file_name" name="file_name" 
                         placeholder="Enter material name" required>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label fw-medium">Description (Optional)</label>
              <textarea class="form-control" id="description" name="description" rows="3"
                        placeholder="Describe what this material covers..."></textarea>
            </div>

            <div class="mb-4">
              <label class="form-label fw-medium">Select File</label>
              <div class="file-drop-zone" id="fileDropZone">
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <h5>Drag & Drop your file here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <input type="file" class="form-control" id="file" name="file" required style="display: none;">
                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('file').click()">
                  <i class="fas fa-folder-open me-2"></i>Choose File
                </button>
                <div class="mt-3">
                  <small class="text-muted">
                    <strong>Supported formats:</strong> PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, PNG, GIF, MP4, AVI, MOV<br>
                    <strong>Maximum size:</strong> 50MB
                  </small>
                </div>
              </div>
              <div id="fileInfo" class="mt-3" style="display: none;">
                <div class="alert alert-info">
                  <i class="fas fa-file me-2"></i>
                  <span id="fileName"></span>
                  <span class="badge bg-primary ms-2" id="fileSize"></span>
                </div>
              </div>
            </div>

            <div class="text-center">
              <button type="submit" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-upload me-2"></i>Upload Material
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
    document.addEventListener('DOMContentLoaded', function() {
      const fileInput = document.getElementById('file');
      const fileDropZone = document.getElementById('fileDropZone');
      const fileInfo = document.getElementById('fileInfo');
      const fileName = document.getElementById('fileName');
      const fileSize = document.getElementById('fileSize');
      const fileNameInput = document.getElementById('file_name');
      
      // File drop functionality
      fileDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileDropZone.classList.add('dragover');
      });
      
      fileDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileDropZone.classList.remove('dragover');
      });
      
      fileDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        fileDropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          fileInput.files = files;
          displayFileInfo(files[0]);
        }
      });
      
      fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
          displayFileInfo(this.files[0]);
        }
      });
      
      function displayFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.style.display = 'block';
        
        // Auto-fill material name if empty
        if (!fileNameInput.value) {
          const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
          fileNameInput.value = nameWithoutExt;
        }
      }
      
      function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }
    });
  </script>
</body>
</html>
