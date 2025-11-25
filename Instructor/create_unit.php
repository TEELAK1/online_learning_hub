<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is an instructor
if (!Auth::isAuthenticated() || !Auth::hasRole('instructor')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$message = "";
$success = false;

// Get course_id from URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get instructor's courses for dropdown
$instructor_courses = [];
$coursesStmt = $db->prepare("SELECT course_id, title FROM courses WHERE instructor_id = ? ORDER BY title");
if ($coursesStmt) {
    $coursesStmt->bind_param("i", $instructor_id);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    while ($row = $coursesResult->fetch_assoc()) {
        $instructor_courses[] = $row;
    }
}

// If no courses exist, redirect to create course
if (empty($instructor_courses)) {
    $message = "You need to create a course first before adding units.";
    $success = false;
}

// Verify instructor owns this course if course_id is provided
$course = null;
if ($course_id > 0) {
    $courseCheck = $db->prepare("SELECT title FROM courses WHERE course_id = ? AND instructor_id = ?");
    if ($courseCheck) {
        $courseCheck->bind_param("ii", $course_id, $instructor_id);
        $courseCheck->execute();
        $courseResult = $courseCheck->get_result();
        
        if ($courseResult->num_rows === 0) {
            $message = "Course not found or you don't have permission to add units to this course.";
            $course_id = 0;
        } else {
            $course = $courseResult->fetch_assoc();
        }
    }
}

if (!$course) {
    $course = ['title' => 'Select Course'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $media_type = $_POST['media_type'] ?? 'none';
    $media_url = trim($_POST['media_url'] ?? '');
    $media_description = trim($_POST['media_description'] ?? '');
    
    // Validation
    if ($course_id <= 0) {
        $message = "Please select a course.";
    } elseif (empty($title)) {
        $message = "Unit title is required.";
    } else {
        // Verify instructor owns the selected course
        $courseCheck = $db->prepare("SELECT title FROM courses WHERE course_id = ? AND instructor_id = ?");
        if ($courseCheck) {
            $courseCheck->bind_param("ii", $course_id, $instructor_id);
            $courseCheck->execute();
            $courseResult = $courseCheck->get_result();
            
            if ($courseResult->num_rows === 0) {
                $message = "Invalid course selection.";
            } else {
        try {
            // Get next order index
            $orderStmt = $db->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM course_units WHERE course_id = ?");
            if ($orderStmt) {
                $orderStmt->bind_param("i", $course_id);
                $orderStmt->execute();
                $order_result = $orderStmt->get_result();
                $next_order = $order_result->fetch_assoc()['next_order'];
                
                // Handle file upload if media type is document/slides/image
                $media_file_path = null;
                if (in_array($media_type, ['document', 'slides', 'image']) && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/units/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = [
                        'document' => ['pdf', 'doc', 'docx', 'txt'],
                        'slides' => ['ppt', 'pptx', 'pdf'],
                        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
                    ];
                    
                    if (in_array(strtolower($file_extension), $allowed_extensions[$media_type])) {
                        $filename = uniqid() . '_' . basename($_FILES['media_file']['name']);
                        $media_file_path = $upload_dir . $filename;
                        
                        if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $media_file_path)) {
                            $message = "Failed to upload media file.";
                            $media_file_path = null;
                        }
                    } else {
                        $message = "Invalid file type for selected media type.";
                    }
                }
                
                if (empty($message)) {
                    // Check if the course_units table has the new columns
                    $checkColumns = $db->query("SHOW COLUMNS FROM course_units LIKE 'media_type'");
                    $hasNewColumns = $checkColumns && $checkColumns->num_rows > 0;
                    
                    if ($hasNewColumns) {
                        // Use new schema with media columns
                        $stmt = $db->prepare("INSERT INTO course_units (course_id, title, description, order_index, media_type, media_url, media_file_path, media_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("ississss", $course_id, $title, $description, $next_order, $media_type, $media_url, $media_file_path, $media_description);
                        }
                    } else {
                        // Use old schema without media columns
                        $stmt = $db->prepare("INSERT INTO course_units (course_id, title, description, order_index) VALUES (?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("issi", $course_id, $title, $description, $next_order);
                        }
                    }
                    
                    if ($stmt && $stmt->execute()) {
                        $unit_id = $db->insert_id;
                        $success = true;
                        $message = "Unit created successfully!";
                        
                        // Check if manage_unit_questions.php exists, otherwise redirect to course management
                        if (file_exists('manage_unit_questions.php')) {
                            header("Location: manage_unit_questions.php?unit_id=" . $unit_id);
                        } else {
                            header("Location: instructor_dashboard.php");
                        }
                        exit();
                    } else {
                        $error_info = $stmt ? $stmt->error : $db->error;
                        $message = "Failed to create unit. Database error: " . $error_info;
                    }
                }
            } else {
                $message = "Database error. Please try again.";
            }
        } catch (Exception $e) {
            $message = "Error creating unit: " . $e->getMessage();
        }
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
    <title>Create Unit - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }
        
        .media-options {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .media-options.show {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create New Unit</h1>
                    <p class="text-muted">Course: <?php echo htmlspecialchars($course['title']); ?></p>
                </div>
                <a href="manage_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Course
                </a>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                    <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Create Unit Form -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card form-card">
                        <div class="form-header">
                            <h4 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>Create Unit
                            </h4>
                            <p class="mb-0 opacity-75">Add a new unit to organize your course content</p>
                        </div>
                        
                        <div class="card-body p-4">
                            <?php if (empty($instructor_courses)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>No Courses Found!</strong> You need to create a course first before adding units.
                                    <br><br>
                                    <a href="instructor_dashboard.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Your First Course
                                    </a>
                                </div>
                            <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <!-- Course Selection -->
                                <div class="mb-4">
                                    <label for="course_id" class="form-label fw-semibold">
                                        <i class="fas fa-book me-2 text-primary"></i>Select Course *
                                    </label>
                                    <select class="form-select form-select-lg" id="course_id" name="course_id" required>
                                        <option value="">-- Choose a Course --</option>
                                        <?php foreach ($instructor_courses as $course_option): ?>
                                            <option value="<?php echo $course_option['course_id']; ?>" 
                                                    <?php echo ($course_id == $course_option['course_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course_option['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Unit Title -->
                                <div class="mb-4">
                                    <label for="title" class="form-label fw-semibold">
                                        <i class="fas fa-heading me-2 text-primary"></i>Unit Title *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           placeholder="Enter unit title (e.g., Introduction to Variables)" required>
                                </div>

                                <!-- Unit Description -->
                                <div class="mb-4">
                                    <label for="description" class="form-label fw-semibold">
                                        <i class="fas fa-align-left me-2 text-primary"></i>Unit Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Describe what this unit covers..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>

                                <!-- Media Type -->
                                <div class="mb-4">
                                    <label for="media_type" class="form-label fw-semibold">
                                        <i class="fas fa-photo-video me-2 text-primary"></i>Media Type
                                    </label>
                                    <select class="form-select" id="media_type" name="media_type" onchange="toggleMediaOptions()">
                                        <option value="none">No Media</option>
                                        <option value="video">Video (YouTube/External URL)</option>
                                        <option value="document">Document (PDF, DOC, TXT)</option>
                                        <option value="slides">Presentation Slides (PPT, PDF)</option>
                                        <option value="image">Image (JPG, PNG, GIF)</option>
                                    </select>
                                </div>

                                <!-- Media URL Option -->
                                <div id="media_url_option" class="media-options">
                                    <label for="media_url" class="form-label fw-semibold">
                                        <i class="fas fa-link me-2 text-primary"></i>Media URL
                                    </label>
                                    <input type="url" class="form-control" id="media_url" name="media_url" 
                                           placeholder="Enter YouTube URL or external video link">
                                </div>

                                <!-- Media File Upload Option -->
                                <div id="media_file_option" class="media-options">
                                    <label for="media_file" class="form-label fw-semibold">
                                        <i class="fas fa-upload me-2 text-primary"></i>Upload Media File
                                    </label>
                                    <input type="file" class="form-control" id="media_file" name="media_file" 
                                           accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp">
                                    <div class="form-text">
                                        Supported formats: PDF, DOC, DOCX, TXT, PPT, PPTX, JPG, PNG, GIF, WEBP
                                    </div>
                                </div>

                                <!-- Media Description -->
                                <div id="media_description_option" class="media-options">
                                    <label for="media_description" class="form-label fw-semibold">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>Media Description
                                    </label>
                                    <textarea class="form-control" id="media_description" name="media_description" rows="2" 
                                              placeholder="Brief description of the media content..."></textarea>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="d-flex gap-3 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Create Unit
                                    </button>
                                    <a href="manage_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMediaOptions() {
            const mediaType = document.getElementById('media_type').value;
            const urlOption = document.getElementById('media_url_option');
            const fileOption = document.getElementById('media_file_option');
            const descOption = document.getElementById('media_description_option');
            
            // Hide all options first
            urlOption.classList.remove('show');
            fileOption.classList.remove('show');
            descOption.classList.remove('show');
            
            // Show relevant options based on media type
            if (mediaType === 'video') {
                urlOption.classList.add('show');
                descOption.classList.add('show');
            } else if (mediaType !== 'none') {
                fileOption.classList.add('show');
                descOption.classList.add('show');
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleMediaOptions();
        });
    </script>
</body>
</html>
