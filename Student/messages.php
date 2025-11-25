<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

// Create messages table if not exists
$db->query("CREATE TABLE IF NOT EXISTS private_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_role ENUM('student', 'instructor') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_role ENUM('student', 'instructor') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (sender_id, sender_role),
    INDEX (receiver_id, receiver_role)
)");

// Fetch instructors from enrolled courses
$instructors_query = "
    SELECT DISTINCT i.instructor_id, i.name, i.email
    FROM instructor i
    JOIN courses c ON i.instructor_id = c.instructor_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.student_id = ? AND e.status IN ('active', 'completed')
";
$stmt = $db->prepare($instructors_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$instructors_result = $stmt->get_result();
$instructors = [];
while ($row = $instructors_result->fetch_assoc()) {
    $instructors[] = $row;
}

$selected_instructor_id = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : 0;
$active_instructor = null;

if ($selected_instructor_id > 0) {
    // Verify this instructor teaches a course the student is enrolled in
    foreach ($instructors as $inst) {
        if ($inst['instructor_id'] == $selected_instructor_id) {
            $active_instructor = $inst;
            break;
        }
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $active_instructor) {
    $message_text = trim($_POST['message']);
    if (!empty($message_text)) {
        $send_stmt = $db->prepare("INSERT INTO private_messages (sender_id, sender_role, receiver_id, receiver_role, message) VALUES (?, 'student', ?, 'instructor', ?)");
        $send_stmt->bind_param("iis", $student_id, $selected_instructor_id, $message_text);
        $send_stmt->execute();
        // Redirect to prevent resubmission
        header("Location: messages.php?instructor_id=" . $selected_instructor_id);
        exit();
    }
}

// Fetch messages if instructor selected
$messages = [];
if ($active_instructor) {
    // Mark messages as read
    $update_read = $db->prepare("UPDATE private_messages SET is_read = 1 WHERE sender_id = ? AND sender_role = 'instructor' AND receiver_id = ? AND receiver_role = 'student'");
    $update_read->bind_param("ii", $selected_instructor_id, $student_id);
    $update_read->execute();

    // Get conversation
    $msg_query = "
        SELECT * FROM private_messages 
        WHERE (sender_id = ? AND sender_role = 'student' AND receiver_id = ? AND receiver_role = 'instructor')
           OR (sender_id = ? AND sender_role = 'instructor' AND receiver_id = ? AND receiver_role = 'student')
        ORDER BY created_at ASC
    ";
    $stmt = $db->prepare($msg_query);
    $stmt->bind_param("iiii", $student_id, $selected_instructor_id, $selected_instructor_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Messages - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; height: 100vh; display: flex; flex-direction: column; }
        .navbar-custom { background: white; box-shadow: 0 2px 4px -1px rgba(0,0,0,0.1); padding: 1rem 0; flex-shrink: 0; }
        .chat-container { flex: 1; display: flex; overflow: hidden; max-width: 1400px; margin: 0 auto; width: 100%; padding: 20px; gap: 20px; }
        .instructors-list { width: 300px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow-y: auto; display: flex; flex-direction: column; }
        .chat-area { flex: 1; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: hidden; }
        .instructor-item { padding: 15px; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s; text-decoration: none; color: inherit; display: block; }
        .instructor-item:hover { background: #f8fafc; }
        .instructor-item.active { background: #eff6ff; border-left: 4px solid #2563eb; }
        .chat-header { padding: 15px 20px; border-bottom: 1px solid #e5e7eb; background: white; }
        .messages-box { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 15px; }
        .message { max-width: 70%; padding: 10px 15px; border-radius: 12px; position: relative; word-wrap: break-word; }
        .message.sent { align-self: flex-end; background: #2563eb; color: white; border-bottom-right-radius: 2px; }
        .message.received { align-self: flex-start; background: white; border: 1px solid #e5e7eb; border-bottom-left-radius: 2px; }
        .message-time { font-size: 0.75rem; margin-top: 5px; opacity: 0.8; text-align: right; }
        .chat-input { padding: 20px; background: white; border-top: 1px solid #e5e7eb; }
        .empty-state { display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280; flex-direction: column; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php"><i class="fas fa-graduation-cap me-2"></i>Online Learning Hub</a>
            <div class="d-flex align-items-center">
                <a href="student_dashboard.php" class="btn btn-outline-primary me-3"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($student_name); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item text-danger" href="../Functionality/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="chat-container">
        <!-- Instructors List -->
        <div class="instructors-list">
            <div class="p-3 border-bottom bg-light">
                <h6 class="mb-0 fw-bold">Your Instructors</h6>
            </div>
            <?php if (empty($instructors)): ?>
                <div class="p-4 text-center text-muted">
                    <small>No instructors found. Enroll in a course to start chatting.</small>
                </div>
            <?php else: ?>
                <?php foreach ($instructors as $inst): ?>
                    <a href="?instructor_id=<?php echo $inst['instructor_id']; ?>" 
                       class="instructor-item <?php echo ($selected_instructor_id == $inst['instructor_id']) ? 'active' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <?php echo strtoupper(substr($inst['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($inst['name']); ?></div>
                                <small class="text-muted">Instructor</small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($active_instructor): ?>
                <div class="chat-header d-flex align-items-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($active_instructor['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($active_instructor['name']); ?></h6>
                        <small class="text-success"><i class="fas fa-circle fa-xs me-1"></i>Available</small>
                    </div>
                </div>

                <div class="messages-box" id="messagesBox">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted my-auto">
                            <i class="far fa-comments fa-3x mb-3"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo ($msg['sender_role'] === 'student') ? 'sent' : 'received'; ?>">
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <div class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="chat-input">
                    <form method="POST">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required autocomplete="off">
                            <button class="btn btn-primary" type="submit" name="send_message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-paper-plane fa-4x mb-3 text-primary opacity-50"></i>
                    <h4>Select an instructor to chat</h4>
                    <p>Choose an instructor from the list to start a private conversation.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        const messagesBox = document.getElementById('messagesBox');
        if (messagesBox) {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }
    </script>
</body>
</html>
