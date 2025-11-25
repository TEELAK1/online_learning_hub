<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!Auth::isAuthenticated() || !Auth::hasRole('admin')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$message = "";
$success = false;

// Create settings table if not exists
$db->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY, 
    setting_value TEXT, 
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert defaults if empty
$check = $db->query("SELECT COUNT(*) as count FROM system_settings");
if ($check->fetch_assoc()['count'] == 0) {
    $db->query("INSERT INTO system_settings (setting_key, setting_value) VALUES 
        ('site_name', 'Online Learning Hub'), 
        ('admin_email', 'admin@olh.com'), 
        ('maintenance_mode', '0')");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'];
    $admin_email = $_POST['admin_email'];
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';

    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $site_name, $site_name);
    $stmt->execute();

    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('admin_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $admin_email, $admin_email);
    $stmt->execute();

    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $maintenance_mode, $maintenance_mode);
    $stmt->execute();

    $success = true;
    $message = "Settings updated successfully.";
}

// Fetch Settings
$settings = [];
$result = $db->query("SELECT * FROM system_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - OLH Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-back { text-decoration: none; color: #333; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <a href="AdminDashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <?php if ($message): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="mb-0"><i class="fas fa-cogs me-2"></i>System Settings</h4>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Online Learning Hub'); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                    <div class="form-text">If enabled, only admins can access the site.</div>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
