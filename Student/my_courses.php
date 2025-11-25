<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is authenticated and is a student
if (!Auth::isAuthenticated() || !Auth::hasRole('student')) {
    header("Location: ../Functionality/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

// Get enrolled courses with progress
$enrolled_courses = [];
$stmt = $db->prepare("
    SELECT 
        c.course_id,
        c.title,
        c.description,
        c.instructor_id,
        i.name as instructor_name,
        e.enrollment_date,
        e.progress_percentage,
        e.status,
        COUNT(DISTINCT cu.unit_id) as total_units,
        COUNT(DISTINCT uq.question_id) as total_questions
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
    LEFT JOIN course_units cu ON c.course_id = cu.course_id
    LEFT JOIN unit_questions uq ON cu.unit_id = uq.unit_id AND uq.is_active = 1
    WHERE e.student_id = ? AND e.status IN ('active', 'completed')
    GROUP BY c.course_id
    ORDER BY e.enrollment_date DESC
");

if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $enrolled_courses[] = $row;
    }
}

// Get recent quiz attempts
$recent_attempts = [];
$attemptsStmt = $db->prepare("
    SELECT 
        ua.attempt_id,
        ua.score_percentage,
        ua.completed_at,
        cu.title as unit_title,
        c.title as course_title
    FROM unit_attempts ua
    JOIN course_units cu ON ua.unit_id = cu.unit_id
    JOIN courses c ON cu.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE ua.student_id = ? AND e.student_id = ? AND ua.status = 'completed'
    ORDER BY ua.completed_at DESC
    LIMIT 5
");

if ($attemptsStmt) {
    $attemptsStmt->bind_param("ii", $student_id, $student_id);
    $attemptsStmt->execute();
    $result = $attemptsStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_attempts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Online Learning Hub</title>
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 30px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .course-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .recent-activity {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .grade-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .grade-a { background: #d4edda; color: #155724; }
        .grade-b { background: #cce7ff; color: #004085; }
        .grade-c { background: #fff3cd; color: #856404; }
        .grade-d { background: #f8d7da; color: #721c24; }
        .grade-f { background: #f8d7da; color: #721c24; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-2">My Courses</h1>
                    <p class="lead mb-0">Track your learning progress and explore course content</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="student_dashboard.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Enrolled Courses -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-book-open me-2 text-primary"></i>Enrolled Courses
                    </h3>
                    <a href="../Courses/courses.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Browse More Courses
                    </a>
                </div>

                <?php if (empty($enrolled_courses)): ?>
                    <div class="card course-card">
                        <div class="card-body empty-state">
                            <i class="fas fa-graduation-cap fa-4x mb-4"></i>
                            <h4>No Courses Enrolled</h4>
                            <p class="mb-4">You haven't enrolled in any courses yet. Start your learning journey today!</p>
                            <a href="../Courses/courses.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Browse Available Courses
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="card course-card">
                            <div class="course-header">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <?php
                                        $progress = (float)$course['progress_percentage'];
                                        $progressColor = $progress >= 80 ? 'success' : ($progress >= 50 ? 'warning' : 'primary');
                                        ?>
                                        <div class="progress-ring bg-<?php echo $progressColor; ?>">
                                            <?php echo number_format($progress, 0); ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <p class="mb-3"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 150)); ?><?php echo strlen($course['description'] ?? '') > 150 ? '...' : ''; ?></p>
                                
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <i class="fas fa-list-ul fa-lg text-primary mb-2"></i>
                                        <div class="fw-bold"><?php echo $course['total_units']; ?></div>
                                        <small class="text-muted">Units</small>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-question-circle fa-lg text-success mb-2"></i>
                                        <div class="fw-bold"><?php echo $course['total_questions']; ?></div>
                                        <small class="text-muted">Questions</small>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar fa-lg text-info mb-2"></i>
                                        <div class="fw-bold"><?php echo date('M j', strtotime($course['enrollment_date'])); ?></div>
                                        <small class="text-muted">Enrolled</small>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-chart-line fa-lg text-warning mb-2"></i>
                                        <div class="fw-bold"><?php echo number_format($progress, 0); ?>%</div>
                                        <small class="text-muted">Progress</small>
                                    </div>
                                </div>
                                
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-<?php echo $progressColor; ?>" 
                                         style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="course_content.php?course_id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>Continue Learning
                                    </a>
                                    <a href="course_overview.php?course_id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-info-circle me-2"></i>Course Info
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Quiz Activity -->
                <div class="recent-activity mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2 text-primary"></i>Recent Quiz Results
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_attempts)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No quiz attempts yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_attempts as $attempt): ?>
                                <div class="activity-item">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($attempt['unit_title']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($attempt['course_title']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo date('M j, g:i A', strtotime($attempt['completed_at'])); ?></small>
                                    </div>
                                    <div>
                                        <?php
                                        $score = (float)$attempt['score_percentage'];
                                        $grade = $score >= 90 ? 'A' : ($score >= 80 ? 'B' : ($score >= 70 ? 'C' : ($score >= 60 ? 'D' : 'F')));
                                        $gradeClass = 'grade-' . strtolower($grade);
                                        ?>
                                        <div class="grade-badge <?php echo $gradeClass; ?>">
                                            <?php echo $grade; ?> (<?php echo number_format($score, 0); ?>%)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2 text-warning"></i>Your Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_courses = count($enrolled_courses);
                        $avg_progress = $total_courses > 0 ? array_sum(array_column($enrolled_courses, 'progress_percentage')) / $total_courses : 0;
                        $total_attempts = count($recent_attempts);
                        $avg_score = $total_attempts > 0 ? array_sum(array_column($recent_attempts, 'score_percentage')) / $total_attempts : 0;
                        ?>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <div class="h4 text-primary mb-1"><?php echo $total_courses; ?></div>
                                    <small class="text-muted">Courses</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-success mb-1"><?php echo number_format($avg_progress, 0); ?>%</div>
                                <small class="text-muted">Avg Progress</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <div class="h4 text-info mb-1"><?php echo $total_attempts; ?></div>
                                    <small class="text-muted">Quiz Attempts</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-warning mb-1"><?php echo number_format($avg_score, 0); ?>%</div>
                                <small class="text-muted">Avg Score</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
