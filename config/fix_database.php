<?php
/**
 * Automatic Database Fix for Password Reset Token Issue
 * 
 * This script fixes the password_resets table structure
 * Run this file once, then delete it for security
 */

// Prevent running in production
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('This script can only be run on localhost for security reasons.');
}

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Fix - Password Reset</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2563eb; }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Password Reset Database Fix</h1>";

try {
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<div class='info'><strong>Step 1:</strong> Checking current table structure...</div>";
    
    // Check current structure
    $result = $db->query("SHOW CREATE TABLE password_resets");
    if ($result) {
        $row = $result->fetch_assoc();
        $createTable = $row['Create Table'];
        
        echo "<pre>" . htmlspecialchars($createTable) . "</pre>";
        
        if (strpos($createTable, 'ON UPDATE CURRENT_TIMESTAMP') !== false || 
            strpos($createTable, 'ON UPDATE current_timestamp()') !== false) {
            echo "<div class='warning'><strong>⚠️ Issue Found:</strong> The expires_at column has ON UPDATE CURRENT_TIMESTAMP which causes tokens to appear expired.</div>";
            
            echo "<div class='info'><strong>Step 2:</strong> Fixing the table structure...</div>";
            
            // Fix the table structure
            $fixQuery = "ALTER TABLE `password_resets` MODIFY COLUMN `expires_at` timestamp NOT NULL";
            
            if ($db->query($fixQuery)) {
                echo "<div class='success'><strong>✅ Success!</strong> Table structure has been fixed.</div>";
                
                // Verify the fix
                echo "<div class='info'><strong>Step 3:</strong> Verifying the fix...</div>";
                $result = $db->query("SHOW CREATE TABLE password_resets");
                $row = $result->fetch_assoc();
                $newCreateTable = $row['Create Table'];
                
                if (strpos($newCreateTable, 'ON UPDATE') === false) {
                    echo "<div class='success'><strong>✅ Verified!</strong> The ON UPDATE clause has been removed.</div>";
                    
                    echo "<pre>" . htmlspecialchars($newCreateTable) . "</pre>";
                    
                    // Clean up old tokens
                    echo "<div class='info'><strong>Step 4:</strong> Cleaning up old/expired tokens...</div>";
                    $cleanupQuery = "DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1";
                    $db->query($cleanupQuery);
                    $deleted = $db->affected_rows;
                    
                    echo "<div class='success'><strong>✅ Cleanup Complete!</strong> Removed $deleted old/expired tokens.</div>";
                    
                    echo "<div class='success'>
                        <h3>🎉 All Done!</h3>
                        <p>Your password reset functionality should now work correctly.</p>
                        <p><strong>Next Steps:</strong></p>
                        <ol>
                            <li>Test the password reset: <a href='../Functionality/forgot_password.php'>Forgot Password</a></li>
                            <li><strong>Delete this file (fix_database.php) for security!</strong></li>
                        </ol>
                    </div>";
                    
                } else {
                    echo "<div class='error'><strong>❌ Verification Failed:</strong> ON UPDATE clause still present. Please run the SQL manually.</div>";
                }
                
            } else {
                throw new Exception("Failed to alter table: " . $db->error);
            }
            
        } else {
            echo "<div class='success'><strong>✅ No Issues Found!</strong> The table structure is already correct.</div>";
            echo "<div class='info'>The expires_at column does not have ON UPDATE CURRENT_TIMESTAMP.</div>";
            
            // Show current tokens
            echo "<div class='info'><strong>Current Tokens:</strong></div>";
            $tokensResult = $db->query("SELECT 
                email, 
                user_type, 
                created_at, 
                expires_at, 
                used,
                CASE 
                    WHEN expires_at < NOW() THEN 'EXPIRED'
                    WHEN used = 1 THEN 'USED'
                    ELSE 'VALID'
                END as status
                FROM password_resets 
                ORDER BY created_at DESC 
                LIMIT 10");
            
            if ($tokensResult && $tokensResult->num_rows > 0) {
                echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
                echo "<tr><th>Email</th><th>Type</th><th>Created</th><th>Expires</th><th>Status</th></tr>";
                while ($token = $tokensResult->fetch_assoc()) {
                    $statusClass = $token['status'] === 'VALID' ? 'success' : 'error';
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($token['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($token['user_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($token['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($token['expires_at']) . "</td>";
                    echo "<td><span class='$statusClass'>" . htmlspecialchars($token['status']) . "</span></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No tokens found in database.</p>";
            }
        }
        
    } else {
        throw new Exception("Could not check table structure");
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='warning'>
        <strong>Manual Fix Required:</strong>
        <p>Please run this SQL command in phpMyAdmin:</p>
        <pre>ALTER TABLE `password_resets` MODIFY COLUMN `expires_at` timestamp NOT NULL;</pre>
    </div>";
}

echo "
    <div class='info'>
        <h3>📚 Additional Resources</h3>
        <ul>
            <li><a href='../Functionality/forgot_password.php'>Test Password Reset</a></li>
            <li><a href='FIX_EXPIRED_TOKEN_ERROR.md'>View Detailed Fix Guide</a></li>
            <li><a href='../Functionality/PASSWORD_RESET_TROUBLESHOOTING.md'>Troubleshooting Guide</a></li>
        </ul>
    </div>
    
    <div class='warning'>
        <strong>⚠️ IMPORTANT SECURITY NOTICE:</strong>
        <p>Delete this file (fix_database.php) after running it to prevent unauthorized access!</p>
    </div>
    
    </div>
</body>
</html>";
?>
