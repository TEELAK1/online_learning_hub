<?php
/**
 * Database Configuration
 * Centralized database connection with error handling and security
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "onlinelearninghub_new";
    private $port = 3306;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database, 
                $this->port
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to UTF-8
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function for getting database connection
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
