<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!Auth::isAuthenticated() || !Auth::hasRole('admin')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$admin_name = $_SESSION['name'];
$current_admin_id = $_SESSION['user_id'];

// Handle POST Operations (Delete & Add)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $success = false;
    $message = "";

    try {
        if ($action === 'add_admin') {
            $new_name = trim($_POST['name']);
            $new_email = trim($_POST['email']);
            $new_username = trim($_POST['username']);
            $new_password = $_POST['password'];

            // Basic validation
            if (empty($new_name) || empty($new_email) || empty($new_password) || empty($new_username)) {
                throw new Exception("All fields are required.");
            }

            // Check if email or username exists
            $stmt = $db->prepare("SELECT admin_id FROM admin WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $new_email, $new_username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email or Username already exists.");
            }
            $stmt->close();

            // Insert new admin
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admin (full_name, email, username, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $new_name, $new_email, $new_username, $hashed_password);
            
            if ($stmt->execute()) {
                $success = true;
                $message = "New admin added successfully.";
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            $stmt->close();

        } elseif (isset($_POST['id'])) {
            $id = intval($_POST['id']);

            if ($action === 'delete_course') {
                // Delete enrollments first
                $stmt = $db->prepare("DELETE FROM enrollments WHERE course_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                // Delete course
                $stmt = $db->prepare("DELETE FROM courses WHERE course_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Course deleted successfully.";
                }
                $stmt->close();
            } elseif ($action === 'delete_student') {
                // Delete enrollments first
                $stmt = $db->prepare("DELETE FROM enrollments WHERE student_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                // Delete student
                $stmt = $db->prepare("DELETE FROM student WHERE student_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Student deleted successfully.";
                }
                $stmt->close();
            } elseif ($action === 'delete_instructor') {
                // 1. Get all courses by this instructor
                $stmt = $db->prepare("SELECT course_id FROM courses WHERE instructor_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $course_ids = [];
                while ($row = $result->fetch_assoc()) {
                    $course_ids[] = $row['course_id'];
                }
                $stmt->close();

                // 2. Delete enrollments for these courses
                if (!empty($course_ids)) {
                    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
                    $types = str_repeat('i', count($course_ids));
                    $stmt = $db->prepare("DELETE FROM enrollments WHERE course_id IN ($placeholders)");
                    $stmt->bind_param($types, ...$course_ids);
                    $stmt->execute();
                    $stmt->close();
                }

                // 3. Delete courses
                $stmt = $db->prepare("DELETE FROM courses WHERE instructor_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                // 4. Delete instructor
                $stmt = $db->prepare("DELETE FROM instructor WHERE instructor_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Instructor deleted successfully.";
                }
                $stmt->close();
            } elseif ($action === 'delete_message') {
                $stmt = $db->prepare("DELETE FROM contact_messages WHERE contact_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Message deleted successfully.";
                }
                $stmt->close();
            } elseif ($action === 'delete_discussion_message') {
                $stmt = $db->prepare("DELETE FROM chat_messages WHERE message_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Chat message deleted successfully.";
                }
                $stmt->close();
            } elseif ($action === 'delete_admin') {
                if ($id === $current_admin_id) {
                    throw new Exception("You cannot delete your own account.");
                }
                $stmt = $db->prepare("DELETE FROM admin WHERE admin_id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Admin deleted successfully.";
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }

    // Redirect to avoid resubmission
    header("Location: AdminDashboard.php?message=" . urlencode($message) . "&success=" . ($success ? 1 : 0));
    exit();
}

// Fetch Statistics
$stats = [];
$stats['students'] = $db->query("SELECT COUNT(*) as count FROM student")->fetch_assoc()['count'];
$stats['instructors'] = $db->query("SELECT COUNT(*) as count FROM instructor")->fetch_assoc()['count'];
$stats['courses'] = $db->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$stats['messages'] = $db->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$stats['admins'] = $db->query("SELECT COUNT(*) as count FROM admin")->fetch_assoc()['count'];

// Fetch Recent Data
// Create chat_messages table if it doesn't exist (to prevent errors if portal1.php hasn't been visited)
$db->query("CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_role ENUM('student', 'instructor', 'admin') NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Fetch Recent Data
$recent_students = $db->query("SELECT * FROM student ORDER BY created_at DESC LIMIT 5");
$recent_courses = $db->query("SELECT c.*, i.name as instructor_name FROM courses c LEFT JOIN instructor i ON c.instructor_id = i.instructor_id ORDER BY c.created_at DESC LIMIT 5");
$contact_messages = $db->query("SELECT * FROM contact_messages ORDER BY sent_at DESC");
$discussion_messages = $db->query("SELECT * FROM chat_messages WHERE is_deleted = FALSE ORDER BY timestamp DESC LIMIT 50");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-bg: #f8f9fa;
            --dark-bg: #111827;
            --card-light: #ffffff;
            --card-dark: #1f2937;
            --text-light: #1f2937;
            --text-dark: #f9fafb;
            --border-light: #e5e7eb;
            --border-dark: #374151;
        }

        [data-theme="dark"] {
            --bg-body: var(--dark-bg);
            --bg-card: var(--card-dark);
            --text-main: var(--text-dark);
            --border-color: var(--border-dark);
        }

        [data-theme="light"] {
            --bg-body: var(--light-bg);
            --bg-card: var(--card-light);
            --text-main: var(--text-light);
            --border-color: var(--border-light);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding-top: 20px;
            z-index: 1000;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 15px 25px;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
            flex-grow: 1;
            overflow-y: auto;
        }

        .sidebar-menu li {
            padding: 10px 25px;
            transition: 0.3s;
            cursor: pointer;
            opacity: 0.8;
        }

        .sidebar-menu li:hover, .sidebar-menu li.active {
            background: rgba(255, 255, 255, 0.1);
            opacity: 1;
            border-left: 4px solid white;
        }

        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            width: 100%;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-footer a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.8;
            transition: 0.3s;
        }

        .sidebar-footer a:hover {
            opacity: 1;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: margin-left 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex-grow: 1;
        }

        .topbar {
            background: var(--bg-card);
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .stats-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            border: 1px solid var(--border-color);
            cursor: pointer;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .table {
            color: var(--text-main);
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid var(--border-color);
            color: var(--text-main);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table td {
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
        }

        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .theme-toggle {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .theme-toggle:hover {
            transform: rotate(15deg);
        }

        .footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-main);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body data-theme="light">

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i> OLH Admin
        </div>
        <ul class="sidebar-menu">
            <li class="active" onclick="showSection('dashboard', this)">
                <a href="#"><i class="fas fa-th-large"></i> Dashboard</a>
            </li>
            <li>
                <a href="manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
            </li>
             <li>
                <a href="../portal1.php"><i class="fas fa-comments me-2"></i> Join Discussion</a>
            </li>
            <li>
                <a href="system_settings.php"><i class="fas fa-cogs"></i> System Settings</a>
            </li>
            <li>
                <a href="certificates.php"><i class="fas fa-certificate"></i> Certificates</a>
            </li>
           
        </ul>
        <div class="sidebar-footer">
            <a href="../Functionality/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Topbar -->
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <button class="btn btn-light d-md-none me-3" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Dashboard Overview</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=random" class="rounded-circle" width="40" height="40">
                        <div class="d-none d-md-block">
                            <small class="d-block text-muted">Admin</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($admin_name); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert <?php echo (isset($_GET['success']) && $_GET['success'] == 1) ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section">
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3 class="fw-bold"><?php echo $stats['students']; ?></h3>
                            <p class="text-muted mb-0">Total Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3 class="fw-bold"><?php echo $stats['instructors']; ?></h3>
                            <p class="text-muted mb-0">Total Instructors</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3 class="fw-bold"><?php echo $stats['courses']; ?></h3>
                            <p class="text-muted mb-0">Total Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="table-card mt-4">
                            <h5 class="mb-3">System Health</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Server Load</span>
                                    <span class="text-success">Optimal</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 25%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Database Status</span>
                                    <span class="text-success">Connected</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Storage Usage</span>
                                    <span class="text-warning">45%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 45%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Recent System Activities -->
                        <div class="table-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Recent System Activities</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User / Entity</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recent_students->data_seek(0);
                                        while($student = $recent_students->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($student['name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info bg-opacity-10 text-info">New Student</span></td>
                                            <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                            <td><span class="badge bg-success">Active</span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php 
                                        $recent_courses->data_seek(0);
                                        while($course = $recent_courses->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-2">
                                                        <i class="fas fa-book"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                        <small class="text-muted">by <?php echo htmlspecialchars($course['instructor_name']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-warning bg-opacity-10 text-warning">New Course</span></td>
                                            <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                            <td><span class="badge bg-success">Published</span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Instructors List (Moved from Sidebar) -->
                        <div class="table-card mt-4">
                            <h5 class="mb-4">All Instructors</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $all_instructors = $db->query("SELECT * FROM instructor");
                                        while($inst = $all_instructors->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>#<?php echo $inst['instructor_id']; ?></td>
                                            <td><?php echo htmlspecialchars($inst['name']); ?></td>
                                            <td><?php echo htmlspecialchars($inst['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($inst['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will delete all their courses too.');">
                                                    <input type="hidden" name="action" value="delete_instructor">
                                                    <input type="hidden" name="id" value="<?php echo $inst['instructor_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Students List (Moved from Sidebar) -->
                        <div class="table-card mt-4">
                            <h5 class="mb-4">All Students</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $all_students = $db->query("SELECT * FROM student");
                                        while($stu = $all_students->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>#<?php echo $stu['student_id']; ?></td>
                                            <td><?php echo htmlspecialchars($stu['name']); ?></td>
                                            <td><?php echo htmlspecialchars($stu['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($stu['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="action" value="delete_student">
                                                    <input type="hidden" name="id" value="<?php echo $stu['student_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Courses List (Moved from Sidebar) -->
                        <div class="table-card mt-4">
                            <h5 class="mb-4">All Courses</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Instructor</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $all_courses = $db->query("SELECT c.*, i.name as instructor_name FROM courses c LEFT JOIN instructor i ON c.instructor_id = i.instructor_id");
                                        while($course = $all_courses->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>#<?php echo $course['course_id']; ?></td>
                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="action" value="delete_course">
                                                    <input type="hidden" name="id" value="<?php echo $course['course_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        
                    <div class="col-lg-4">
                       
                        
                        
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings-section" class="content-section d-none">
                <div class="table-card">
                    <h5 class="mb-4">System Settings</h5>
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" value="Online Learning Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" class="form-control" value="admin@olh.com">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="maintenanceMode">
                            <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Certificates Section -->
            <div id="certificates-section" class="content-section d-none">
                <div class="table-card">
                    <h5 class="mb-4">Issued Certificates</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Certificate management module coming soon.
                    </div>
                </div>
            </div>

        </div>
<!-- Contact Messages (Moved from Sidebar) -->
                        <div class="table-card mt-4">
                            <h5 class="mb-4">Contact Messages</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($contact_messages && $contact_messages->num_rows > 0):
                                            while($msg = $contact_messages->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($msg['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($msg['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                            <td>
                                                <?php if($msg['status'] == 'replied'): ?>
                                                    <span class="badge bg-success">Replied</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($msg['sent_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" title="View Message" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $msg['contact_id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this message?');">
                                                    <input type="hidden" name="action" value="delete_message">
                                                    <input type="hidden" name="id" value="<?php echo $msg['contact_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>

                                                <!-- Message Modal -->
                                                <div class="modal fade" id="messageModal<?php echo $msg['contact_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Message from <?php echo htmlspecialchars($msg['name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($msg['email']); ?></p>
                                                                <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($msg['sent_at'])); ?></p>
                                                                <hr>
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="btn btn-primary">
                                                                    <i class="fas fa-reply me-2"></i>Reply via Email
                                                                </a>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No contact messages found.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

        <!-- Footer -->
        <footer class="footer mt-4">
            <p>&copy; <?php echo date('Y'); ?> Online Learning Hub. All rights reserved.</p>
        </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function showSection(sectionId, element) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(el => el.classList.add('d-none'));
            // Show selected section
            document.getElementById(sectionId + '-section').classList.remove('d-none');
            
            // Update active menu item
            document.querySelectorAll('.sidebar-menu li').forEach(el => el.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }

            // Update title
            const titles = {
                'dashboard': 'Dashboard Overview',
                'settings': 'System Settings',
                'certificates': 'Manage Certificates'
            };
            document.querySelector('.topbar h4').textContent = titles[sectionId] || 'Dashboard';
        }

        // Theme Toggle Logic
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.querySelector('.theme-toggle i');
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        // Initialize Theme
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        });
    </script>
</body>
</html>
