<?php
/**
 * Security Configuration
 * Define security-related constants and settings
 */

// Environment settings
define('ENVIRONMENT', 'development'); // Change to 'production' in production

// Security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");
    
    // Strict Transport Security (HTTPS only)
    if (ENVIRONMENT === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Session security settings
function setSecureSession() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', ENVIRONMENT === 'production');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Regenerate session ID on login
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
    
    // Set session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: ../Functionality/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Apply security settings
setSecurityHeaders();
setSecureSession();

// Error reporting based on environment
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}
?>
