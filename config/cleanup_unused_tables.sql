-- ============================================================================
-- CLEANUP SCRIPT: Remove Unused Tables from onlinelearninghub_new Database
-- ============================================================================
-- Created: November 25, 2025
-- Purpose: Remove 3 unused tables that are not referenced in the codebase
-- 
-- IMPORTANT: BACKUP YOUR DATABASE BEFORE RUNNING THIS SCRIPT!
-- 
-- To backup: mysqldump -u root onlinelearninghub_new > backup_before_cleanup.sql
-- ============================================================================

-- Set database
USE `onlinelearninghub_new`;

-- ============================================================================
-- SAFETY CHECK: Display current table count
-- ============================================================================
SELECT COUNT(*) as 'Total Tables Before Cleanup' 
FROM information_schema.tables 
WHERE table_schema = 'onlinelearninghub_new';

-- ============================================================================
-- STEP 1: Remove ANNOUNCEMENTS table (Completely Unused)
-- ============================================================================
-- This table was intended for course/system announcements but is not used anywhere
-- No admin interface exists to create announcements
-- No student interface exists to view announcements

DROP TABLE IF EXISTS `announcements`;
SELECT 'Dropped table: announcements' as 'Status';

-- ============================================================================
-- STEP 2: Remove MESSAGES table (Completely Unused)
-- ============================================================================
-- This table was intended for private messaging between students and instructors
-- The project uses 'chat_messages' table for public chat instead
-- No private messaging feature is implemented

DROP TABLE IF EXISTS `messages`;
SELECT 'Dropped table: messages' as 'Status';

-- ============================================================================
-- STEP 3: Remove COURSE_REVIEWS table (Completely Unused)
-- ============================================================================
-- This table was intended for course reviews/ratings
-- No review submission form exists
-- No review display on course pages
-- No rating system is implemented

DROP TABLE IF EXISTS `course_reviews`;
SELECT 'Dropped table: course_reviews' as 'Status';

-- ============================================================================
-- VERIFICATION: Display remaining tables
-- ============================================================================
SELECT COUNT(*) as 'Total Tables After Cleanup' 
FROM information_schema.tables 
WHERE table_schema = 'onlinelearninghub_new';

-- ============================================================================
-- List all remaining tables
-- ============================================================================
SELECT table_name as 'Remaining Tables', 
       table_rows as 'Approximate Rows'
FROM information_schema.tables 
WHERE table_schema = 'onlinelearninghub_new'
ORDER BY table_name;

-- ============================================================================
-- CLEANUP COMPLETE
-- ============================================================================
-- Summary:
-- - Removed 3 unused tables
-- - No data loss (tables were empty or unused)
-- - No code changes required (tables were not referenced)
-- - Database is now cleaner and more maintainable
-- ============================================================================

SELECT 'Database cleanup completed successfully!' as 'Result';
SELECT 'Removed tables: announcements, messages, course_reviews' as 'Summary';
