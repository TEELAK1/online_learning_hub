<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is authenticated
if (!Auth::isAuthenticated()) {
    header("Location: Functionality/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];

// Create chat_messages table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_role ENUM('student', 'instructor', 'admin') NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$db->query($createTable);

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, sender_name, sender_role, message) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $user_name, $user_role, $message);
            $stmt->execute();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: chat.php");
    exit();
}

// Handle message deletion (for admins and message owners)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    if ($user_role === 'admin') {
        // Admins can delete any message
        $stmt = $db->prepare("UPDATE chat_messages SET is_deleted = TRUE WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
    } else {
        // Users can only delete their own messages
        $stmt = $db->prepare("UPDATE chat_messages SET is_deleted = TRUE WHERE message_id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $message_id, $user_id);
    }
    
    if ($stmt) {
        $stmt->execute();
    }
    
    header("Location: chat.php");
    exit();
}

// Get recent messages
$messages = [];
$stmt = $db->prepare("SELECT * FROM chat_messages WHERE is_deleted = FALSE ORDER BY timestamp DESC LIMIT 50");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Reverse to show oldest first
$messages = array_reverse($messages);

// Get online users count (simplified - just count recent activity)
$onlineStmt = $db->prepare("SELECT COUNT(DISTINCT sender_id) as count FROM chat_messages WHERE timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$onlineCount = 0;
if ($onlineStmt) {
    $onlineStmt->execute();
    $result = $onlineStmt->get_result();
    $onlineCount = $result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Chat - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: white;
            max-height: 60vh;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 12px;
            max-width: 80%;
            position: relative;
        }
        
        .message.own {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .message.other {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        
        .sender-name {
            font-weight: 600;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .role-student { background: #e3f2fd; color: #1976d2; }
        .role-instructor { background: #f3e5f5; color: #7b1fa2; }
        .role-admin { background: #ffebee; color: #c62828; }
        
        .message-time {
            opacity: 0.7;
            font-size: 0.75rem;
        }
        
        .message-text {
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .chat-input {
            background: white;
            padding: 20px;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 12px 12px;
        }
        
        .online-indicator {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .online-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 38, 38, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .message:hover .delete-btn {
            opacity: 1;
        }
        
        .empty-chat {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">
                            <i class="fas fa-comments me-2"></i>Discussion Chat
                        </h3>
                        <p class="mb-0 opacity-75">Connect with students and instructors</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="online-indicator">
                            <div class="online-dot"></div>
                            <?php echo $onlineCount; ?> active
                        </div>
                        <a href="<?php 
                            switch($user_role) {
                                case 'student':
                                    echo 'Student/student_dashboard.php';
                                    break;
                                case 'instructor':
                                    echo 'Instructor/instructor_dashboard.php';
                                    break;
                                case 'admin':
                                    echo 'Admin/AdminDashboard.php';
                                    break;
                                default:
                                    echo 'index.php';
                            }
                        ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments fa-4x mb-3"></i>
                        <h5>Start the Conversation!</h5>
                        <p>Be the first to send a message in this discussion.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'own' : 'other'; ?>">
                            <?php if ($user_role === 'admin' || $message['sender_id'] == $user_id): ?>
                                <button class="delete-btn" onclick="deleteMessage(<?php echo $message['message_id']; ?>)" title="Delete message">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                            
                            <div class="message-header">
                                <div>
                                    <span class="sender-name"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                    <span class="role-badge role-<?php echo $message['sender_role']; ?>">
                                        <?php echo ucfirst($message['sender_role']); ?>
                                    </span>
                                </div>
                                <span class="message-time">
                                    <?php echo date('M j, g:i A', strtotime($message['timestamp'])); ?>
                                </span>
                            </div>
                            <div class="message-text">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="typing-indicator" id="typingIndicator">
                    Someone is typing...
                </div>
            </div>

            <!-- Chat Input -->
            <div class="chat-input">
                <form method="POST" id="chatForm">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg" name="message" id="messageInput" 
                               placeholder="Type your message..." maxlength="500" required>
                        <button type="submit" name="send_message" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Be respectful and follow community guidelines. Messages are visible to all users.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Scroll to bottom on page load
        scrollToBottom();
        
        // Auto-refresh messages every 10 seconds
        setInterval(function() {
            location.reload();
        }, 10000);
        
        // Delete message function
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                window.location.href = 'chat.php?delete=' + messageId;
            }
        }
        
        // Focus on input
        document.getElementById('messageInput').focus();
        
        // Handle form submission
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            const input = document.getElementById('messageInput');
            if (input.value.trim() === '') {
                e.preventDefault();
                return false;
            }
        });
        
        // Character counter
        const messageInput = document.getElementById('messageInput');
        messageInput.addEventListener('input', function() {
            const remaining = 500 - this.value.length;
            if (remaining < 50) {
                this.style.borderColor = remaining < 10 ? '#dc2626' : '#d97706';
            } else {
                this.style.borderColor = '';
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to send message
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.getElementById('chatForm').submit();
            }
            
            // Escape to clear input
            if (e.key === 'Escape') {
                document.getElementById('messageInput').value = '';
            }
        });
    </script>
</body>
</html>
