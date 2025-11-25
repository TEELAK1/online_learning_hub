<?php
/**
 * Security Helper Class
 * Provides security functions for XSS prevention, CSRF protection, and input validation
 */
class SecurityHelper {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate strong password
     */
    public static function validatePassword($password) {
        // At least 8 characters, one uppercase, one lowercase, one number, one special character
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Sanitize filename for upload
     */
    public static function sanitizeFilename($filename) {
        // Remove path components and sanitize
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
    
    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Rate limiting helper
     */
    public static function checkRateLimit($action, $limit = 5, $window = 300) {
        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = ['count' => 0, 'start' => time()];
        }
        
        $data = $_SESSION['rate_limit'][$key];
        
        // Reset window if expired
        if (time() - $data['start'] > $window) {
            $_SESSION['rate_limit'][$key] = ['count' => 1, 'start' => time()];
            return true;
        }
        
        // Check limit
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $_SESSION['rate_limit'][$key]['count']++;
        return true;
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        // Log to file (in production, use proper logging system)
        error_log('SECURITY: ' . json_encode($logEntry));
    }
}
?>
