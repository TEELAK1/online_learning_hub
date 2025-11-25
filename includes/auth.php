<?php
/**
 * Authentication Helper Class
 * Handles user authentication, session management, and security
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
            if (!$this->db || $this->db->connect_error) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("Auth constructor error: " . $e->getMessage());
            throw new Exception("Authentication system unavailable");
        }
    }
    
    /**
     * Login user with email and password
     */
    public function login($email, $password) {
        try {
            // Sanitize input
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if tables have the new schema or old schema
            $hasNewSchema = $this->checkTableSchema();
            
            // Check student table first
            if ($hasNewSchema) {
                $stmt = $this->db->prepare("SELECT student_id as id, name, password, status FROM student WHERE email = ? AND status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT id, name, password FROM student WHERE email = ?");
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $this->createSession($user['id'], $user['name'], 'student');
                    if ($this->tableExists('user_activity_log')) {
                        $this->logActivity($user['id'], 'student', 'login');
                    }
                    return ['success' => true, 'role' => 'student', 'redirect' => '../Student/student_dashboard.php'];
                }
            }
            
            // Check instructor table
            if ($hasNewSchema) {
                $stmt = $this->db->prepare("SELECT instructor_id as id, name, password, status FROM instructor WHERE email = ? AND status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT id, name, password FROM instructor WHERE email = ?");
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $this->createSession($user['id'], $user['name'], 'instructor');
                    if ($this->tableExists('user_activity_log')) {
                        $this->logActivity($user['id'], 'instructor', 'login');
                    }
                    return ['success' => true, 'role' => 'instructor', 'redirect' => '../Instructor/instructor_dashboard.php'];
                }
            }
            
            // Check admin table (if it exists)
            if ($this->tableExists('admin')) {
                $stmt = $this->db->prepare("SELECT admin_id as id, full_name as name, password FROM admin WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $this->createSession($user['id'], $user['name'], 'admin');
                        if ($this->tableExists('user_activity_log')) {
                            $this->logActivity($user['id'], 'admin', 'login');
                        }
                        return ['success' => true, 'role' => 'admin', 'redirect' => '../Admin/AdminDashboard.php'];
                    }
                }
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Register new user
     */
    public function register($name, $email, $password, $role) {
        try {
            // Validate inputs
            $name = trim($name);
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            
            if (empty($name) || strlen($name) < 2) {
                return ['success' => false, 'message' => 'Name must be at least 2 characters long'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
            }
            
            if (!in_array($role, ['student', 'instructor'])) {
                return ['success' => false, 'message' => 'Invalid role selected'];
            }
            
            // Check if email already exists
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email address is already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if tables have the new schema or old schema
            $hasNewSchema = $this->checkTableSchema();
            
            // Insert user based on role and schema
            if ($role === 'student') {
                if ($hasNewSchema) {
                    $stmt = $this->db->prepare("INSERT INTO student (name, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                } else {
                    $stmt = $this->db->prepare("INSERT INTO student (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
                }
            } else {
                if ($hasNewSchema) {
                    $stmt = $this->db->prepare("INSERT INTO instructor (name, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                } else {
                    $stmt = $this->db->prepare("INSERT INTO instructor (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
                }
            }
            
            $stmt->bind_param("sss", $name, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                $userId = $this->db->insert_id;
                // Only log activity if the table exists
                if ($this->tableExists('user_activity_log')) {
                    $this->logActivity($userId, $role, 'register');
                }
                return ['success' => true, 'message' => 'Registration successful! You can now login.'];
            }
            
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($userId, $name, $role) {
        session_start();
        session_regenerate_id(true); // Prevent session fixation
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log activity before destroying session
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            $auth = new Auth();
            $auth->logActivity($_SESSION['user_id'], $_SESSION['role'], 'logout');
        }
        
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Check if email exists in any user table
     */
    private function emailExists($email) {
        $tables = ['student', 'instructor'];
        
        // Add admin table only if it exists
        if ($this->tableExists('admin')) {
            $tables[] = 'admin';
        }
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    return true;
                }
            } catch (Exception $e) {
                // Table might not exist, continue to next table
                continue;
            }
        }
        
        return false;
    }
    
    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($email) {
        try {
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email exists and get user type
            $userType = $this->getUserTypeByEmail($email);
            if (!$userType) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $this->db->prepare("INSERT INTO password_resets (email, token, user_type, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $token, $userType, $expiresAt);
            
            if ($stmt->execute()) {
                // In a real application, you would send an email here
                return ['success' => true, 'token' => $token, 'message' => 'Password reset token generated'];
            }
            
            return ['success' => false, 'message' => 'Failed to generate reset token'];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate reset token'];
        }
    }
    
    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword) {
        try {
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
            }
            
            // Verify token
            $stmt = $this->db->prepare("SELECT email, user_type FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            $resetData = $result->fetch_assoc();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password based on user type
            $table = $resetData['user_type'];
            $idField = $table . '_id';
            
            $stmt = $this->db->prepare("UPDATE {$table} SET password = ?, updated_at = NOW() WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $resetData['email']);
            
            if ($stmt->execute()) {
                // Mark token as used
                $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Password reset successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to reset password'];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    /**
     * Get user type by email
     */
    private function getUserTypeByEmail($email) {
        $tables = [
            'student' => 'student',
            'instructor' => 'instructor', 
            'admin' => 'admin'
        ];
        
        foreach ($tables as $table => $type) {
            $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return $type;
            }
        }
        
        return false;
    }
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $userType, $action) {
        try {
            $stmt = $this->db->prepare("INSERT INTO user_activity_log (user_id, user_type, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->bind_param("issss", $userId, $userType, $action, $ipAddress, $userAgent);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        try {
            // Use a simpler query that doesn't require prepared statements
            $result = $this->db->query("SHOW TABLES LIKE '" . $this->db->real_escape_string($tableName) . "'");
            return $result && $result->num_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if tables have new schema (with status column)
     */
    private function checkTableSchema() {
        try {
            // First check if student table exists
            if (!$this->tableExists('student')) {
                return false;
            }
            
            // Check if status column exists in student table
            $result = $this->db->query("SHOW COLUMNS FROM student LIKE 'status'");
            return $result && $result->num_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check session timeout
     */
    public static function checkSessionTimeout($timeoutMinutes = 30) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['last_activity'])) {
            $timeout = $timeoutMinutes * 60; // Convert to seconds
            
            if (time() - $_SESSION['last_activity'] > $timeout) {
                self::logout();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
}
?>
