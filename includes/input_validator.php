<?php
/**
 * Input Validation Helper
 * Comprehensive validation and sanitization functions
 */
class InputValidator {
    
    /**
     * Validate and sanitize text input
     */
    public static function validateText($input, $minLength = 1, $maxLength = 255, $required = true) {
        if ($required && empty($input)) {
            return ['valid' => false, 'message' => 'This field is required'];
        }
        
        if (!$required && empty($input)) {
            return ['valid' => true, 'value' => ''];
        }
        
        $input = trim($input);
        
        if (strlen($input) < $minLength) {
            return ['valid' => false, 'message' => "Must be at least {$minLength} characters"];
        }
        
        if (strlen($input) > $maxLength) {
            return ['valid' => false, 'message' => "Must be no more than {$maxLength} characters"];
        }
        
        // Check for potentially dangerous content
        if (self::containsXSS($input)) {
            return ['valid' => false, 'message' => 'Invalid characters detected'];
        }
        
        return ['valid' => true, 'value' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')];
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email, $required = true) {
        if ($required && empty($email)) {
            return ['valid' => false, 'message' => 'Email is required'];
        }
        
        if (!$required && empty($email)) {
            return ['valid' => true, 'value' => ''];
        }
        
        $email = trim($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Please enter a valid email address'];
        }
        
        if (strlen($email) > 254) {
            return ['valid' => false, 'message' => 'Email is too long'];
        }
        
        return ['valid' => true, 'value' => strtolower($email)];
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Password is required'];
        }
        
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        if (strlen($password) > 128) {
            return ['valid' => false, 'message' => 'Password is too long'];
        }
        
        // Check for password strength
        $strength = 0;
        
        // Contains lowercase
        if (preg_match('/[a-z]/', $password)) $strength++;
        // Contains uppercase
        if (preg_match('/[A-Z]/', $password)) $strength++;
        // Contains numbers
        if (preg_match('/\d/', $password)) $strength++;
        // Contains special characters
        if (preg_match('/[^a-zA-Z\d]/', $password)) $strength++;
        
        if ($strength < 3) {
            return ['valid' => false, 'message' => 'Password must contain at least 3 of: lowercase, uppercase, numbers, special characters'];
        }
        
        return ['valid' => true, 'value' => $password];
    }
    
    /**
     * Validate numeric input
     */
    public static function validateNumber($input, $min = null, $max = null, $required = true) {
        if ($required && $input === '') {
            return ['valid' => false, 'message' => 'This field is required'];
        }
        
        if (!$required && $input === '') {
            return ['valid' => true, 'value' => null];
        }
        
        if (!is_numeric($input)) {
            return ['valid' => false, 'message' => 'Must be a valid number'];
        }
        
        $number = (float) $input;
        
        if ($min !== null && $number < $min) {
            return ['valid' => false, 'message' => "Must be at least {$min}"];
        }
        
        if ($max !== null && $number > $max) {
            return ['valid' => false, 'message' => "Must be no more than {$max}"];
        }
        
        return ['valid' => true, 'value' => $number];
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile($file, $allowedTypes = [], $maxSize = 5242880) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valid' => false, 'message' => 'Please select a file'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'message' => 'File is too large'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        return ['valid' => true, 'file' => $file];
    }
    
    /**
     * Check for XSS patterns
     */
    private static function containsXSS($input) {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<\s*\/?\s*(script|iframe|object|embed|form)\s*>/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Remove path components
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent reserved names
        $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (in_array(strtoupper($filename), $reserved)) {
            $filename = 'file_' . $filename;
        }
        
        return $filename;
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url, $required = false) {
        if ($required && empty($url)) {
            return ['valid' => false, 'message' => 'URL is required'];
        }
        
        if (!$required && empty($url)) {
            return ['valid' => true, 'value' => ''];
        }
        
        $url = trim($url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Try adding http:// if missing
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'http://' . $url;
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return ['valid' => false, 'message' => 'Please enter a valid URL'];
                }
            } else {
                return ['valid' => false, 'message' => 'Please enter a valid URL'];
            }
        }
        
        return ['valid' => true, 'value' => $url];
    }
}
?>
