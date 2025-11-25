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
$current_admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $success = false;
    $message = "";

    try {
        if ($action === 'add_admin') {
            $new_name = trim($_POST['name']);
            $new_email = trim($_POST['email']);
            $new_username = trim($_POST['username']);
            $new_password = $_POST['password'];

            if (empty($new_name) || empty($new_email) || empty($new_password) || empty($new_username)) {
                throw new Exception("All fields are required.");
            }

            // Check duplicates
            $stmt = $db->prepare("SELECT admin_id FROM admin WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $new_email, $new_username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email or Username already exists.");
            }
            $stmt->close();

            // Insert
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
        } elseif ($action === 'delete_admin') {
            $id = intval($_POST['id']);
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
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Admins
$admins = $db->query("SELECT * FROM admin ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-main);
        }

        .container {
            max-width: 1200px;
            margin-top: 30px;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: var(--card-bg);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            background-color: #f9fafb;
        }
        
        .btn-back {
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .btn-back:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>

<div class="container mb-5">
    <a href="AdminDashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <?php if (isset($message) && $message): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Manage Administrators</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-plus me-2"></i>Add New Admin
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $admin['admin_id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                            <td class="text-end pe-4">
                                <?php if($admin['admin_id'] != $current_admin_id): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                    <input type="hidden" name="action" value="delete_admin">
                                    <input type="hidden" name="id" value="<?php echo $admin['admin_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Admin">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="badge bg-success">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
