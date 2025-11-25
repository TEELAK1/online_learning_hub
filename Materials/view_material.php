<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

// Check database schema
function checkInstructorSchema($db) {
    $result = $db->query("SHOW COLUMNS FROM instructor LIKE 'instructor_id'");
    return $result && $result->num_rows > 0;
}

$hasNewSchema = checkInstructorSchema($db);
$idField = $hasNewSchema ? 'instructor_id' : 'id';
$courseIdField = $hasNewSchema ? 'course_id' : 'id';

// Fetch courses and their materials
try {
    $stmt = $db->prepare("SELECT {$courseIdField} as id, title, description FROM courses WHERE instructor_id = ?");
    if (!$stmt) {
        $courses = [];
    } else {
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];
        
        while ($course = $result->fetch_assoc()) {
            // Fetch materials for each course
            $materialStmt = $db->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY uploaded_at DESC");
            if ($materialStmt) {
                $materialStmt->bind_param("i", $course['id']);
                $materialStmt->execute();
                $materialResult = $materialStmt->get_result();
                $course['materials'] = $materialResult->fetch_all(MYSQLI_ASSOC);
                $materialStmt->close();
            } else {
                $course['materials'] = [];
            }
            $courses[] = $course;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Materials - Online Learning Hub</title>
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
    
    .material-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      margin-bottom: 2rem;
      transition: all 0.3s ease;
    }
    
    .material-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.15);
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
    
    .file-icon {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
    }
    
    .file-pdf { background: #dc2626; }
    .file-doc { background: #2563eb; }
    .file-img { background: #059669; }
    .file-video { background: #d97706; }
    .file-default { background: var(--text-secondary); }
    
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
          <h1 class="mb-2">Course Materials 📚</h1>
          <p class="mb-0 opacity-75">Manage and organize your course materials</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="upload_material.php" class="btn btn-light btn-lg">
            <i class="fas fa-upload me-2"></i>Upload New Material
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <?php if (empty($courses)): ?>
      <div class="material-card">
        <div class="card-body text-center py-5">
          <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
          <h4>No Courses Found</h4>
          <p class="text-muted">You haven't created any courses yet. Create a course first to upload materials.</p>
          <a href="../Instructor/instructor_dashboard.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Your First Course
          </a>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($courses as $course): ?>
        <div class="material-card">
          <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="mb-0"><?php echo htmlspecialchars($course['title']); ?></h4>
                <small class="opacity-75"><?php echo htmlspecialchars($course['description'] ?? ''); ?></small>
              </div>
              <div class="col-md-4 text-md-end">
                <a href="upload_material.php?course_id=<?php echo $course['id']; ?>" class="btn btn-light btn-sm">
                  <i class="fas fa-plus me-2"></i>Add Material
                </a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <?php if (!empty($course['materials'])): ?>
              <div class="row g-4">
                <?php foreach ($course['materials'] as $material): ?>
                  <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                      <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                          <div class="file-icon <?php 
                            $ext = strtolower(pathinfo($material['file_name'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['pdf'])) echo 'file-pdf';
                            elseif (in_array($ext, ['doc', 'docx'])) echo 'file-doc';
                            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) echo 'file-img';
                            elseif (in_array($ext, ['mp4', 'avi', 'mov'])) echo 'file-video';
                            else echo 'file-default';
                          ?> me-3">
                            <i class="fas <?php 
                              if (in_array($ext, ['pdf'])) echo 'fa-file-pdf';
                              elseif (in_array($ext, ['doc', 'docx'])) echo 'fa-file-word';
                              elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) echo 'fa-file-image';
                              elseif (in_array($ext, ['mp4', 'avi', 'mov'])) echo 'fa-file-video';
                              else echo 'fa-file';
                            ?>"></i>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($material['file_name']); ?></h6>
                            <small class="text-muted">
                              <i class="fas fa-calendar me-1"></i>
                              <?php echo date('M j, Y', strtotime($material['uploaded_at'])); ?>
                            </small>
                          </div>
                        </div>
                        <div class="d-grid">
                          <a href="download_material.php?id=<?php echo $material['material_id']; ?>" 
                             class="btn btn-outline-primary btn-sm" download>
                            <i class="fas fa-download me-2"></i>Download
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5>No Materials Yet</h5>
                <p class="text-muted">No materials have been uploaded for this course.</p>
                <a href="upload_material.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                  <i class="fas fa-upload me-2"></i>Upload First Material
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
