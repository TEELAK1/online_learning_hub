<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];
$enrolled_courses = [];
$message = "";
$success = false;

// Handle enrollment
if (isset($_GET['enroll'])) {
    $course_id = (int) $_GET['enroll'];
    try {
        // Check if course exists
        $checkStmt = $db->prepare("SELECT course_id FROM courses WHERE course_id = ?");
        if (!$checkStmt) {
            $message = "Database error. Please try again.";
            throw new Exception("Prepare failed");
        }
        $checkStmt->bind_param("i", $course_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Check if already enrolled
            $enrollCheck = $db->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ?");
            if (!$enrollCheck) {
                $message = "Database error. Please try again.";
                throw new Exception("Prepare failed");
            }
            $enrollCheck->bind_param("ii", $student_id, $course_id);
            $enrollCheck->execute();
            
            if ($enrollCheck->get_result()->num_rows === 0) {
                $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param("ii", $student_id, $course_id);
                    if ($stmt->execute()) {
                        $success = true;
                        $message = "Successfully enrolled in the course!";
                    } else {
                        $message = "Failed to enroll. Please try again.";
                    }
                } else {
                    $message = "Database error. Please try again.";
                }
            } else {
                $message = "You are already enrolled in this course.";
            }
        } else {
            $message = "Course not found.";
        }
    } catch (Exception $e) {
        $message = "Enrollment failed. Please try again.";
    }
    
    // On successful enrollment or if already enrolled, redirect to the main course view
    if ($success || $message === "You are already enrolled in this course.") {
        header("Location: course_view.php?course_id=" . $course_id);
        exit();
    }
}

// Get enrolled course IDs
try {
    $stmt = $db->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $enrolled_courses[] = $row['course_id'];
        }
    }
} catch (Exception $e) {
    $enrolled_courses = [];
}

// Fetch all courses with instructor, categories, and unit counts
try {
    $courses_query = "SELECT c.*, 
                            i.name as instructor, 
                            cat.name as category_name, 
                            cat.color as category_color,
                            COUNT(DISTINCT cu.unit_id) as unit_count,
                            COUNT(DISTINCT uq.question_id) as question_count,
                            COUNT(DISTINCT e.student_id) as enrolled_count
                     FROM courses c 
                     LEFT JOIN instructor i ON c.instructor_id = i.instructor_id 
                     LEFT JOIN categories cat ON c.category_id = cat.category_id 
                     LEFT JOIN course_units cu ON c.course_id = cu.course_id
                     LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
                     LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
                     GROUP BY c.course_id
                     ORDER BY c.created_at DESC";
    $result = $db->query($courses_query);
    $courses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Ensure counts are integers
            $row['unit_count'] = (int)($row['unit_count'] ?? 0);
            $row['question_count'] = (int)($row['question_count'] ?? 0);
            $row['enrolled_count'] = (int)($row['enrolled_count'] ?? 0);
            $courses[] = $row;
        }
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
  <title>Courses - Online Learning Hub</title>
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
    
    .hero-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 4rem 0;
      margin-bottom: 3rem;
    }
    
    .course-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      transition: all 0.3s ease;
      overflow: hidden;
      height: 100%;
    }
    
    .course-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .course-thumbnail {
      height: 200px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      position: relative;
      overflow: hidden;
    }
    
    .course-thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .category-badge {
      position: absolute;
      top: 1rem;
      right: 1rem;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
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
    
    .btn-success {
      background: var(--success-color);
      border: none;
      border-radius: 8px;
      padding: 12px 24px;
      font-weight: 500;
    }
    
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 2rem;
      color: var(--text-primary);
    }
    
    .instructor-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--text-secondary);
      font-size: 0.875rem;
    }
    
    .course-stats {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border-color);
    }
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      font-size: 0.875rem;
      color: var(--text-secondary);
    }
    
    .enrolled-badge {
      position: absolute;
      top: 1rem;
      left: 1rem;
      background: var(--success-color);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
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
        <a href="../Student/student_dashboard.php" class="btn btn-outline-primary me-3">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <div class="dropdown">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($student_name); ?>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../Functionality/logout.php">
              <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="display-4 fw-bold mb-3">Explore Courses</h1>
          <p class="lead mb-0">Discover and enroll in courses that match your learning goals</p>
        </div>
        <div class="col-md-4 text-md-end">
          <div class="bg-white bg-opacity-10 rounded-3 p-3">
            <h5 class="mb-1">Total Courses</h5>
            <h2 class="mb-0"><?php echo count($courses); ?></h2>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="container">
    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
      <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show mb-4">
        <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Enrolled Courses -->
    <section class="mb-5">
      <h2 class="section-title">
        <i class="fas fa-check-circle text-success me-2"></i>Your Enrolled Courses
      </h2>
      
      <?php
      $hasEnrolled = false;
      foreach ($courses as $course):
        if (in_array($course['course_id'], $enrolled_courses)):
          $hasEnrolled = true;
      ?>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="course-card">
            <div class="course-thumbnail">
              <?php if ($course['thumbnail']): ?>
                <img src="../uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Course Thumbnail">
              <?php endif; ?>
              <div class="enrolled-badge">
                <i class="fas fa-check me-1"></i>Enrolled
              </div>
              <?php if ($course['category_name']): ?>
                <div class="category-badge" style="color: <?php echo htmlspecialchars($course['category_color']); ?>">
                  <?php echo htmlspecialchars($course['category_name']); ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-body p-4">
              <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($course['title']); ?></h5>
              <p class="card-text text-muted small mb-3"><?php echo substr(htmlspecialchars($course['description'] ?? ''), 0, 100) . (strlen($course['description'] ?? '') > 100 ? '...' : ''); ?></p>
              
              <div class="instructor-info mb-3">
                <i class="fas fa-chalkboard-teacher"></i>
                <span><?php echo htmlspecialchars($course['instructor'] ?? 'Unknown Instructor'); ?></span>
              </div>
              
              <div class="course-stats">
                <div class="stat-item">
                  <i class="fas fa-book"></i>
                  <span><?php echo $course['unit_count']; ?> Units</span>
                </div>
                <div class="stat-item">
                  <i class="fas fa-question-circle"></i>
                  <span><?php echo $course['question_count']; ?> Questions</span>
                </div>
                <div class="stat-item">
                  <i class="fas fa-users"></i>
                  <span><?php echo $course['enrolled_count']; ?> Students</span>
                </div>
              </div>
              
              <div class="d-flex gap-2 mt-3">
                <a href="course_view.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary flex-fill">
                  <i class="fas fa-play me-2"></i>Continue Learning
                </a>
                <a href="../Student/download_materials.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-outline-secondary">
                  <i class="fas fa-download"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endif; endforeach; ?>
      
      <?php if (!$hasEnrolled): ?>
        <div class="text-center py-5">
          <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
          <h4>No Enrolled Courses Yet</h4>
          <p class="text-muted">Start your learning journey by enrolling in a course below.</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Available Courses -->
    <section>
      <h2 class="section-title">
        <i class="fas fa-book-open text-primary me-2"></i>Available Courses
      </h2>
      
      <div class="row">
        <?php foreach ($courses as $course): ?>
          <?php if (!in_array($course['course_id'], $enrolled_courses)): ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="course-card">
                <div class="course-thumbnail">
                  <?php if ($course['thumbnail']): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Course Thumbnail">
                  <?php endif; ?>
                  <?php if ($course['category_name']): ?>
                    <div class="category-badge" style="color: <?php echo htmlspecialchars($course['category_color']); ?>">
                      <?php echo htmlspecialchars($course['category_name']); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="card-body p-4">
                  <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($course['title']); ?></h5>
                  <p class="card-text text-muted small mb-3"><?php echo substr(htmlspecialchars($course['description'] ?? ''), 0, 100) . (strlen($course['description'] ?? '') > 100 ? '...' : ''); ?></p>
                  
                  <div class="instructor-info mb-3">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span><?php echo htmlspecialchars($course['instructor'] ?? 'Unknown Instructor'); ?></span>
                  </div>
                  
                  <div class="course-stats">
                    <div class="stat-item">
                      <i class="fas fa-clock"></i>
                      <span>Self-paced</span>
                    </div>
                    <div class="stat-item">
                      <i class="fas fa-signal"></i>
                      <span>Beginner</span>
                    </div>
                  </div>
                  
                  <div class="d-flex gap-2 mt-3">
                    <a href="courses.php?enroll=<?php echo $course['course_id']; ?>" class="btn btn-primary flex-fill">
                      <i class="fas fa-plus me-2"></i>Enroll Now
                    </a>
                    <a href="#" class="btn btn-outline-secondary">
                      <i class="fas fa-info-circle"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
