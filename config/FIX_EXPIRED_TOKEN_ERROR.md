# URGENT FIX: "Invalid or expired reset token" Error

## Problem Identified

The `password_resets` table has a **critical schema issue**:

```sql
`expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
```

The `ON UPDATE current_timestamp()` clause causes the expiration time to automatically update to the current time whenever the row is modified. This means:
- When you check if a token is valid, the `expires_at` gets updated
- The token immediately appears expired because `expires_at` becomes NOW()
- Users can never successfully reset their password

## Solution

You need to fix the database table structure. Follow these steps:

### Option 1: Using phpMyAdmin (Recommended for beginners)

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`
   - Login (usually no password for XAMPP)

2. **Select your database**
   - Click on `onlinelearninghub_new` in the left sidebar

3. **Open SQL tab**
   - Click the "SQL" tab at the top

4. **Run this SQL command**
   ```sql
   ALTER TABLE `password_resets` 
   MODIFY COLUMN `expires_at` timestamp NOT NULL;
   ```

5. **Click "Go"** to execute

6. **Verify the fix**
   - Click on the `password_resets` table in the left sidebar
   - Click "Structure" tab
   - Check that `expires_at` shows: `timestamp` (without "on update CURRENT_TIMESTAMP")

7. **Clean up old tokens** (optional)
   ```sql
   DELETE FROM `password_resets` 
   WHERE expires_at < NOW() OR used = 1;
   ```

### Option 2: Using MySQL Command Line

1. **Open Command Prompt**
   - Press `Win + R`
   - Type: `cmd`
   - Press Enter

2. **Navigate to MySQL**
   ```cmd
   cd C:\xampp\mysql\bin
   ```

3. **Login to MySQL**
   ```cmd
   mysql -u root -p
   ```
   (Press Enter if no password)

4. **Select database**
   ```sql
   USE onlinelearninghub_new;
   ```

5. **Run the fix**
   ```sql
   ALTER TABLE `password_resets` 
   MODIFY COLUMN `expires_at` timestamp NOT NULL;
   ```

6. **Verify**
   ```sql
   SHOW CREATE TABLE `password_resets`;
   ```

7. **Exit**
   ```sql
   EXIT;
   ```

### Option 3: Using the SQL File

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`

2. **Select database**
   - Click `onlinelearninghub_new`

3. **Import SQL file**
   - Click "Import" tab
   - Click "Choose File"
   - Navigate to: `c:\xampp\htdocs\online_learning_hub\config\fix_password_resets_table.sql`
   - Click "Go"

## Testing After Fix

1. **Clear old tokens**
   ```sql
   DELETE FROM password_resets;
   ```

2. **Test password reset**
   - Go to: `http://localhost/online_learning_hub/Functionality/forgot_password.php`
   - Enter email: `tilak@gmail.com`
   - Click "Send Reset Link"
   - Click "Reset Password Now"
   - Enter new password
   - Click "Reset Password"
   - Should work now! ✅

## Verify the Fix

Run this query to check the table structure:

```sql
SHOW CREATE TABLE `password_resets`;
```

**Before fix (WRONG):**
```sql
`expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**After fix (CORRECT):**
```sql
`expires_at` timestamp NOT NULL
```

## Why This Happened

The original database schema was incorrectly designed. The `ON UPDATE CURRENT_TIMESTAMP` clause is useful for `updated_at` columns that should track when a row was last modified, but it's completely wrong for an expiration timestamp that should remain fixed.

## Additional Debugging

If you still get errors after the fix, check:

### 1. Check if token exists and is valid
```sql
SELECT 
    token,
    email,
    user_type,
    expires_at,
    used,
    created_at,
    CASE 
        WHEN expires_at < NOW() THEN 'EXPIRED'
        WHEN used = 1 THEN 'ALREADY USED'
        ELSE 'VALID'
    END as status,
    TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as minutes_until_expiry
FROM password_resets 
ORDER BY created_at DESC 
LIMIT 5;
```

### 2. Check if email exists
```sql
SELECT 'student' as type, email FROM student WHERE email = 'YOUR_EMAIL'
UNION
SELECT 'instructor' as type, email FROM instructor WHERE email = 'YOUR_EMAIL'
UNION
SELECT 'admin' as type, email FROM admin WHERE email = 'YOUR_EMAIL';
```

### 3. Test token generation
```php
<?php
// Create test file: test_token.php
require_once 'includes/auth.php';

$auth = new Auth();
$result = $auth->generatePasswordResetToken('tilak@gmail.com');

echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<br>Token: " . $result['token'];
    echo "<br><a href='Functionality/reset_password.php?token=" . $result['token'] . "'>Reset Password</a>";
}
?>
```

## Prevention for Future

When creating tables with expiration timestamps:
- ✅ DO: Use plain `timestamp NOT NULL`
- ❌ DON'T: Add `ON UPDATE CURRENT_TIMESTAMP` to expiration fields
- ✅ DO: Set expiration time when creating the record
- ❌ DON'T: Let the database automatically update expiration times

## Summary

**Root Cause:** Database schema error with `ON UPDATE CURRENT_TIMESTAMP`  
**Impact:** All password reset tokens appeared expired immediately  
**Fix:** Remove `ON UPDATE CURRENT_TIMESTAMP` from `expires_at` column  
**Time to Fix:** 2 minutes  
**Difficulty:** Easy  

After applying this fix, your password reset functionality will work perfectly! 🎉
