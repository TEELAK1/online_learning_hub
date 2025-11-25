<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in or not instructor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'instructor') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$instructor_id = (int) $_SESSION['user_id'];
$instructor_name = $_SESSION['name'];

$uploadMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($title === '') {
        $uploadMessage = "❌ Course title is required.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO courses (instructor_id, title, description, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) {
                $uploadMessage = "❌ Prepare failed: " . $db->error;
            } else {
                $stmt->bind_param("iss", $instructor_id, $title, $description);
                if ($stmt->execute()) {
                    $course_id = $db->insert_id;
                    $uploadMessage = "✅ Course '{$title}' created successfully! (ID: {$course_id})";
                } else {
                    $uploadMessage = "❌ Error creating course: " . $stmt->error;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $uploadMessage = "❌ Database error: " . $e->getMessage();
        }
    }
}

// Fetch all courses of this instructor with statistics
$courses = [];
$stats = [
    'total_courses' => 0,
    'total_students' => 0,
    'total_units' => 0,
    'total_questions' => 0
];

try {
    $courses_stmt = $db->prepare("
        SELECT 
            c.course_id as id,
            c.title,
            c.description,
            c.created_at,
            COUNT(DISTINCT cu.unit_id) as unit_count,
            COUNT(DISTINCT uq.question_id) as question_count,
            COUNT(DISTINCT e.student_id) as student_count
        FROM courses c
        LEFT JOIN course_units cu ON c.course_id = cu.course_id
        LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active' or e.status = 'completed'
        WHERE c.instructor_id = ?
        GROUP BY c.course_id
        ORDER BY c.created_at DESC
    ");
    
    if (!$courses_stmt) {
        $uploadMessage .= " (Warning: Could not fetch courses - " . $db->error . ")";
    } else {
        $courses_stmt->bind_param("i", $instructor_id);
        $courses_stmt->execute();
        $result = $courses_stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Ensure all keys exist with default values
            $row['unit_count'] = (int)($row['unit_count'] ?? 0);
            $row['question_count'] = (int)($row['question_count'] ?? 0);
            $row['student_count'] = (int)($row['student_count'] ?? 0);
            
            $courses[] = $row;
            
            // Calculate totals
            $stats['total_units'] += $row['unit_count'];
            $stats['total_questions'] += $row['question_count'];
            $stats['total_students'] += $row['student_count'];
        }
         $stats['total_courses'] = count($courses);
        $courses_stmt->close();
    }
} catch (Exception $e) {
    $uploadMessage .= " (Warning: Could not fetch courses - " . $e->getMessage() . ")";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instructor Dashboard - Online Learning Hub</title>
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
    
    .dashboard-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: none;
      transition: all 0.3s ease;
    }
    
    .dashboard-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
    
    .form-control {
      border-radius: 8px;
      border: 2px solid var(--border-color);
      padding: 12px 16px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
        <div class="dropdown">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($instructor_name); ?>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="../settings.php"><i class="fas fa-user me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="../settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
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
          <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($instructor_name); ?>! 👨‍🏫</h1>
          <p class="mb-0 opacity-75">Manage your courses and inspire your students to learn.</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="../portal1.php" class="btn btn-light btn-lg">
            <i class="fas fa-comments me-2"></i>Join Discussion
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <!-- Dashboard Statistics -->
    <div class="row g-3 mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="dashboard-card text-center p-4">
          <i class="fas fa-book fa-3x text-primary mb-3"></i>
          <h3 class="mb-1"><?php echo $stats['total_courses']; ?></h3>
          <p class="text-muted mb-0">Total Courses</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="dashboard-card text-center p-4">
          <i class="fas fa-users fa-3x text-success mb-3"></i>
          <h3 class="mb-1"><?php echo $stats['total_students']; ?></h3>
          <p class="text-muted mb-0">Total Students</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="dashboard-card text-center p-4">
          <i class="fas fa-list-ul fa-3x text-info mb-3"></i>
          <h3 class="mb-1"><?php echo $stats['total_units']; ?></h3>
          <p class="text-muted mb-0">Total Units</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="dashboard-card text-center p-4">
          <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
          <h3 class="mb-1"><?php echo $stats['total_questions']; ?></h3>
          <p class="text-muted mb-0">Total Questions</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-md-2">
        <a href="create_unit.php" class="btn btn-outline-primary w-100">
          <i class="fas fa-plus-circle me-2"></i>Create Unit
        </a>
      </div>
      <div class="col-md-2">
        <a href="../Materials/upload_material.php" class="btn btn-outline-success w-100">
          <i class="fas fa-folder me-2"></i>Materials
        </a>
      </div>
      <div class="col-md-2">
        <a href="../Quiz/create_quiz.php" class="btn btn-outline-warning w-100">
          <i class="fas fa-list me-2"></i>Create Quiz
        </a>
      </div>
      <div class="col-md-2">
        <a href="../CRUD/create_lesson.php" class="btn btn-outline-info w-100">
          <i class="fas fa-book me-2"></i>Lessons
        </a>
      </div>
      <div class="col-md-2">
        <a href="../portal1.php" class="btn btn-outline-secondary w-100">
          <i class="fas fa-comments me-2"></i>Discussion
        </a>
      </div>
      <div class="col-md-2">
        <a href="messages.php" class="btn btn-outline-primary w-100">
          <i class="fas fa-envelope me-2"></i>Private Chat
        </a>
      </div>
      <div class="col-md-2">
        <a href="../settings.php" class="btn btn-outline-dark w-100">
          <i class="fas fa-cog me-2"></i>Settings
        </a>
      </div>
    </div>

    <!-- Course Creation Form -->
    <div class="dashboard-card mb-5">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Course</h4>
      </div>
      <div class="card-body">
        <?php if ($uploadMessage): ?>
          <div class="alert <?php echo strpos($uploadMessage, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
            <i class="fas <?php echo strpos($uploadMessage, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
            <?php echo $uploadMessage; ?>
          </div>
        <?php endif; ?>
        <form method="POST">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="title" class="form-label fw-medium">Course Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       placeholder="Enter course title" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="category" class="form-label fw-medium">Category</label>
                <select class="form-control" id="category" name="category">
                  <option value="">Select Category</option>
                  <option value="programming">Programming</option>
                  <option value="design">Design</option>
                  <option value="business">Business</option>
                  <option value="marketing">Marketing</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label fw-medium">Course Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"
                      placeholder="Describe what students will learn in this course..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Course
          </button>
        </form>
      </div>
    </div>

    <!-- Existing Courses -->
    <div class="dashboard-card">
      <div class="card-header bg-success text-white">
        <h4 class="mb-0"><i class="fas fa-book me-2"></i>Your Courses</h4>
      </div>
      <div class="card-body">
        <?php if (!empty($courses)): ?>
          <div class="row g-4">
            <?php foreach ($courses as $course): ?>
              <div class="col-lg-4 col-md-6">
                <div class="card h-100 dashboard-card">
                  <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($course['title']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...</p>
                    
                    <!-- Course Statistics -->
                    <div class="row text-center mt-3 mb-3">
                      <div class="col-4">
                        <div class="border-end">
                          <div class="h6 text-info mb-1"><?php echo $course['unit_count']; ?></div>
                          <small class="text-muted">Units</small>
                        </div>
                      </div>
                      <div class="col-4">
                        <div class="border-end">
                          <div class="h6 text-warning mb-1"><?php echo $course['question_count']; ?></div>
                          <small class="text-muted">Questions</small>
                        </div>
                      </div>
                      <div class="col-4">
                        <div class="h6 text-success mb-1"><?php echo $course['student_count']; ?></div>
                        <small class="text-muted">Students</small>
                      </div>
                    </div>
                    
                    <small class="text-muted">
                      <i class="fas fa-calendar me-1"></i>
                      Created: <?php echo date('M j, Y', strtotime($course['created_at'] ?? 'now')); ?>
                    </small>
                  </div>
                  <div class="card-footer bg-transparent">
                    <div class="d-grid gap-2">
                      <div class="btn-group" role="group">
                        <a href="viewcourse.php?course_id=<?php echo $course['id']; ?>" 
                           class="btn btn-primary btn-sm">
                          <i class="fas fa-cog me-1"></i>Manage
                        </a>
                        <a href="preview_course.php?course_id=<?php echo $course['id']; ?>" 
                           class="btn btn-outline-info btn-sm">
                          <i class="fas fa-eye me-1"></i>Preview
                        </a>
                      </div>
                      <div class="btn-group" role="group">
                        <a href="create_unit.php?course_id=<?php echo $course['id']; ?>" 
                           class="btn btn-outline-success btn-sm">
                          <i class="fas fa-plus me-1"></i>Add Unit
                        </a>
                        <a href="../Materials/upload_material.php?course_id=<?php echo $course['id']; ?>" 
                           class="btn btn-outline-warning btn-sm">
                          <i class="fas fa-upload me-1"></i>Materials
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            <h5>No Courses Yet</h5>
            <p class="text-muted">You haven't created any courses yet. Create your first course above!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
