-- Fix password_resets table structure
-- This script fixes the expires_at column to prevent automatic updates

-- Step 1: Modify the expires_at column to remove ON UPDATE current_timestamp()
ALTER TABLE `password_resets` 
MODIFY COLUMN `expires_at` timestamp NOT NULL;

-- Step 2: Clean up old/expired tokens (optional but recommended)
DELETE FROM `password_resets` 
WHERE expires_at < NOW() 
   OR used = 1;

-- Step 3: Verify the change
SHOW CREATE TABLE `password_resets`;

-- Expected result should show:
-- `expires_at` timestamp NOT NULL
-- (without ON UPDATE current_timestamp())
