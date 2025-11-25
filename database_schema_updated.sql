-- Updated Online Learning Hub Database Schema
-- Fixed inconsistencies and added missing tables
-- Engine: InnoDB, Proper Primary & Foreign Keys

-- Drop existing database and recreate (for fresh install)
-- DROP DATABASE IF EXISTS olhdata;
-- CREATE DATABASE olhdata CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE olhdata;

-- =========================
-- TABLE: admin (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `admin` (`username`, `email`, `password`, `full_name`) VALUES 
('admin', 'admin@learninghub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- =========================
-- TABLE: student (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `student` (
  `student_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: instructor (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `instructor` (
  `instructor_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `expertise` VARCHAR(255) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`instructor_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: categories (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '#007bff',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO `categories` (`name`, `description`, `icon`, `color`) VALUES 
('Web Development', 'Learn modern web technologies', 'fas fa-code', '#007bff'),
('Data Science', 'Data analysis and machine learning', 'fas fa-chart-bar', '#28a745'),
('Design', 'Graphic and UI/UX design', 'fas fa-paint-brush', '#dc3545'),
('Business', 'Business and entrepreneurship', 'fas fa-briefcase', '#ffc107'),
('Marketing', 'Digital marketing strategies', 'fas fa-bullhorn', '#17a2b8');

-- =========================
-- TABLE: courses (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` INT(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` INT(11) NOT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `duration_hours` INT(11) DEFAULT NULL,
  `difficulty_level` ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `featured` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_id`),
  FOREIGN KEY (`instructor_id`) REFERENCES `instructor`(`instructor_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_featured` (`featured`),
  INDEX `idx_difficulty` (`difficulty_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: course_units (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `course_units` (
  `unit_id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `order_index` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unit_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  INDEX `idx_order` (`order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: course_lessons (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `course_lessons` (
  `lesson_id` INT(11) NOT NULL AUTO_INCREMENT,
  `unit_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `video_url` VARCHAR(500) DEFAULT NULL,
  `youtube_url` VARCHAR(500) DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `external_link` VARCHAR(500) DEFAULT NULL,
  `duration_minutes` INT(11) DEFAULT NULL,
  `order_index` INT(11) DEFAULT 0,
  `is_free` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lesson_id`),
  FOREIGN KEY (`unit_id`) REFERENCES `course_units`(`unit_id`) ON DELETE CASCADE,
  INDEX `idx_order` (`order_index`),
  INDEX `idx_free` (`is_free`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: enrollments (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completion_date` TIMESTAMP NULL DEFAULT NULL,
  `progress_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('active', 'completed', 'dropped') DEFAULT 'active',
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`student_id`, `course_id`),
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_progress` (`progress_percentage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: lesson_progress (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `progress_id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `lesson_id` INT(11) NOT NULL,
  `completed` BOOLEAN DEFAULT FALSE,
  `completion_date` TIMESTAMP NULL DEFAULT NULL,
  `time_spent_minutes` INT(11) DEFAULT 0,
  `last_accessed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_lesson_progress` (`student_id`, `lesson_id`),
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: materials (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `materials` (
  `material_id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `lesson_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `file_name` VARCHAR(200) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) DEFAULT NULL,
  `file_size` INT(11) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`material_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: quizzes (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `quizzes` (
  `quiz_id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `lesson_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `time_limit_minutes` INT(11) DEFAULT NULL,
  `passing_score` DECIMAL(5,2) DEFAULT 70.00,
  `max_attempts` INT(11) DEFAULT 3,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: quiz_questions (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `question_id` INT(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` INT(11) NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
  `option_a` VARCHAR(255) DEFAULT NULL,
  `option_b` VARCHAR(255) DEFAULT NULL,
  `option_c` VARCHAR(255) DEFAULT NULL,
  `option_d` VARCHAR(255) DEFAULT NULL,
  `correct_answer` VARCHAR(255) NOT NULL,
  `explanation` TEXT DEFAULT NULL,
  `points` DECIMAL(5,2) DEFAULT 1.00,
  `order_index` INT(11) DEFAULT 0,
  PRIMARY KEY (`question_id`),
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`quiz_id`) ON DELETE CASCADE,
  INDEX `idx_order` (`order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: quiz_attempts (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `attempt_id` INT(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `score` DECIMAL(5,2) DEFAULT 0.00,
  `total_questions` INT(11) DEFAULT 0,
  `correct_answers` INT(11) DEFAULT 0,
  `time_taken_minutes` INT(11) DEFAULT NULL,
  `passed` BOOLEAN DEFAULT FALSE,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`attempt_id`),
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`quiz_id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE,
  INDEX `idx_score` (`score`),
  INDEX `idx_passed` (`passed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: quiz_results (UPDATED - for backward compatibility)
-- =========================
CREATE TABLE IF NOT EXISTS `quiz_results` (
  `result_id` INT(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `score` DECIMAL(5,2) DEFAULT 0.00,
  `taken_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`result_id`),
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`quiz_id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: contact_messages (UPDATED)
-- =========================
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `contact_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `replied_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`contact_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: notifications (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `user_type` ENUM('student', 'instructor', 'admin') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  INDEX `idx_user` (`user_id`, `user_type`),
  INDEX `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: password_resets (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `user_type` ENUM('student', 'instructor', 'admin') NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- TABLE: system_settings (NEW)
-- =========================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES 
('site_name', 'Online Learning Hub', 'Website name'),
('site_description', 'Professional Online Learning Platform', 'Website description'),
('admin_email', 'admin@learninghub.com', 'Administrator email'),
('maintenance_mode', '0', 'Maintenance mode (0=off, 1=on)'),
('registration_enabled', '1', 'User registration (0=disabled, 1=enabled)'),
('email_verification', '0', 'Email verification required (0=no, 1=yes)');

-- =========================
-- VIEWS FOR REPORTING
-- =========================

-- Course enrollment statistics
CREATE OR REPLACE VIEW `course_stats` AS
SELECT 
    c.course_id,
    c.title,
    c.status,
    i.name AS instructor_name,
    cat.name AS category_name,
    COUNT(e.enrollment_id) AS total_enrollments,
    AVG(e.progress_percentage) AS avg_progress,
    COUNT(CASE WHEN e.status = 'completed' THEN 1 END) AS completed_count
FROM courses c
LEFT JOIN instructor i ON c.instructor_id = i.instructor_id
LEFT JOIN categories cat ON c.category_id = cat.category_id
LEFT JOIN enrollments e ON c.course_id = e.course_id
GROUP BY c.course_id;

-- Student progress overview
CREATE OR REPLACE VIEW `student_progress_overview` AS
SELECT 
    s.student_id,
    s.name,
    s.email,
    COUNT(e.enrollment_id) AS total_courses,
    COUNT(CASE WHEN e.status = 'completed' THEN 1 END) AS completed_courses,
    AVG(e.progress_percentage) AS avg_progress
FROM student s
LEFT JOIN enrollments e ON s.student_id = e.student_id
GROUP BY s.student_id;

-- =========================
-- INDEXES FOR PERFORMANCE
-- =========================

-- Additional indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_courses_instructor_status` ON `courses` (`instructor_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_enrollments_student_status` ON `enrollments` (`student_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_lessons_unit_order` ON `course_lessons` (`unit_id`, `order_index`);
CREATE INDEX IF NOT EXISTS `idx_quiz_attempts_student_quiz` ON `quiz_attempts` (`student_id`, `quiz_id`);
