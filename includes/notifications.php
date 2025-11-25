<?php
class NotificationSystem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->createNotificationTable();
    }
    
    private function createNotificationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_type ENUM('student', 'instructor', 'admin') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            action_url VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id, user_type),
            INDEX idx_unread (user_id, is_read)
        )";
        
        $this->db->query($sql);
    }
    
    public function createNotification($user_id, $user_type, $title, $message, $type = 'info', $action_url = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, type, action_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->bind_param("isssss", $user_id, $user_type, $title, $message, $type, $action_url) && $stmt->execute();
    }
    
    public function getNotifications($user_id, $user_type, $limit = 10, $unread_only = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND user_type = ?";
        if ($unread_only) {
            $sql .= " AND is_read = FALSE";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $user_id, $user_type, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUnreadCount($user_id, $user_type) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND user_type = ? AND is_read = FALSE
        ");
        
        $stmt->bind_param("is", $user_id, $user_type);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return (int)$result['count'];
    }
    
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE notification_id = ? AND user_id = ?
        ");
        
        return $stmt->bind_param("ii", $notification_id, $user_id) && $stmt->execute();
    }
    
    public function markAllAsRead($user_id, $user_type) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND user_type = ?
        ");
        
        return $stmt->bind_param("is", $user_id, $user_type) && $stmt->execute();
    }
    
    public function deleteNotification($notification_id, $user_id) {
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE notification_id = ? AND user_id = ?
        ");
        
        return $stmt->bind_param("ii", $notification_id, $user_id) && $stmt->execute();
    }
    
    // Helper methods for common notifications
    public function notifyEnrollment($student_id, $course_title) {
        return $this->createNotification(
            $student_id,
            'student',
            'Course Enrollment Successful',
            "You have successfully enrolled in '{$course_title}'. Start learning now!",
            'success',
            'student_dashboard.php'
        );
    }
    
    public function notifyQuizCompletion($student_id, $unit_title, $score) {
        $type = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'error');
        return $this->createNotification(
            $student_id,
            'student',
            'Quiz Completed',
            "You scored {$score}% on the '{$unit_title}' quiz.",
            $type,
            'student_dashboard.php'
        );
    }
    
    public function notifyNewStudent($instructor_id, $student_name, $course_title) {
        return $this->createNotification(
            $instructor_id,
            'instructor',
            'New Student Enrollment',
            "{$student_name} has enrolled in your course '{$course_title}'.",
            'info',
            'instructor_dashboard.php'
        );
    }
    
    public function notifyNewMessage($user_id, $user_type, $sender_name) {
        return $this->createNotification(
            $user_id,
            $user_type,
            'New Message',
            "You have a new message from {$sender_name}.",
            'info',
            '../Chat system/' . $user_type . '_chat.php'
        );
    }
}

// Notification API endpoint
if (isset($_GET['action']) && isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    
    $notifications = new NotificationSystem(getDB());
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['role'];
    
    switch ($_GET['action']) {
        case 'get_notifications':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $result = $notifications->getNotifications($user_id, $user_type, $limit, $unread_only);
            echo json_encode(['notifications' => $result]);
            break;
            
        case 'get_unread_count':
            $count = $notifications->getUnreadCount($user_id, $user_type);
            echo json_encode(['unread_count' => $count]);
            break;
            
        case 'mark_read':
            if (isset($_POST['notification_id'])) {
                $notification_id = (int)$_POST['notification_id'];
                $success = $notifications->markAsRead($notification_id, $user_id);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Missing notification_id']);
            }
            break;
            
        case 'mark_all_read':
            $success = $notifications->markAllAsRead($user_id, $user_type);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            if (isset($_POST['notification_id'])) {
                $notification_id = (int)$_POST['notification_id'];
                $success = $notifications->deleteNotification($notification_id, $user_id);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Missing notification_id']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit();
}
?>
