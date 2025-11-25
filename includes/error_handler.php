<?php
/**
 * Enhanced Error Handler
 * Provides comprehensive error handling and logging
 */
class ErrorHandler {
    
    private static $logFile = null;
    
    public static function init() {
        self::$logFile = __DIR__ . '/../logs/error.log';
        
        // Ensure logs directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Set custom error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorType = self::getErrorType($severity);
        self::logError($errorType, $message, $file, $line);
        
        // Don't show errors in production
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            return true;
        }
        
        return false;
    }
    
    public static function handleException($exception) {
        self::logError(
            'EXCEPTION',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Show user-friendly error page
        self::showErrorPage(500, 'Something went wrong');
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::logError(
                'FATAL',
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                self::showErrorPage(500, 'Server error occurred');
            }
        }
    }
    
    private static function logError($type, $message, $file, $line, $trace = '') {
        $logEntry = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $type,
            $message,
            $file,
            $line
        );
        
        if ($trace) {
            $logEntry .= "Trace: " . $trace . "\n";
        }
        
        $logEntry .= "Request: " . $_SERVER['REQUEST_URI'] . "\n";
        $logEntry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $logEntry .= str_repeat('-', 80) . "\n";
        
        error_log($logEntry, 3, self::$logFile);
    }
    
    private static function getErrorType($severity) {
        switch ($severity) {
            case E_ERROR:
                return 'ERROR';
            case E_WARNING:
                return 'WARNING';
            case E_PARSE:
                return 'PARSE';
            case E_NOTICE:
                return 'NOTICE';
            case E_CORE_ERROR:
                return 'CORE_ERROR';
            case E_CORE_WARNING:
                return 'CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'COMPILE_WARNING';
            case E_USER_ERROR:
                return 'USER_ERROR';
            case E_USER_WARNING:
                return 'USER_WARNING';
            case E_USER_NOTICE:
                return 'USER_NOTICE';
            default:
                return 'UNKNOWN';
        }
    }
    
    public static function showErrorPage($code, $message) {
        http_response_code($code);
        
        // Include error page template
        include __DIR__ . '/../templates/error.php';
        exit;
    }
    
    public static function logUserAction($action, $details = []) {
        $logEntry = sprintf(
            "[%s] USER_ACTION: %s by user %d (%s)\n",
            date('Y-m-d H:i:s'),
            $action,
            $_SESSION['user_id'] ?? 'unknown',
            $_SESSION['role'] ?? 'unknown'
        );
        
        if (!empty($details)) {
            $logEntry .= "Details: " . json_encode($details) . "\n";
        }
        
        $logEntry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $logEntry .= str_repeat('-', 80) . "\n";
        
        error_log($logEntry, 3, self::$logFile);
    }
}

// Initialize error handler
ErrorHandler::init();
?>
