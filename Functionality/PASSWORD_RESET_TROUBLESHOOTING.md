# Password Reset - Quick Troubleshooting Guide

## Quick Test URLs

```
Forgot Password:  http://localhost/online_learning_hub/Functionality/forgot_password.php
Login Page:       http://localhost/online_learning_hub/Functionality/login.php
```

## Test Accounts

```
Student:
  Email: tilak@gmail.com
  
Instructor:
  Email: nabinneupane@gmail.com
  
Admin:
  Email: admin@learninghub.com
```

## Common Issues & Solutions

### ❌ "Invalid or expired reset token"

**Possible Causes:**
1. Token is older than 1 hour
2. Token has already been used
3. Token doesn't exist in database

**Solutions:**
1. Request a new password reset
2. Check `password_resets` table:
   ```sql
   SELECT * FROM password_resets 
   WHERE email = 'your@email.com' 
   ORDER BY created_at DESC 
   LIMIT 1;
   ```
3. Verify token hasn't been used: `used = 0`
4. Verify token hasn't expired: `expires_at > NOW()`

### ❌ "Email not found"

**Possible Causes:**
1. Email doesn't exist in any user table
2. Typo in email address
3. Database connection issue

**Solutions:**
1. Verify email exists:
   ```sql
   SELECT 'student' as type, email FROM student WHERE email = 'your@email.com'
   UNION
   SELECT 'instructor' as type, email FROM instructor WHERE email = 'your@email.com'
   UNION
   SELECT 'admin' as type, email FROM admin WHERE email = 'your@email.com';
   ```
2. Check database connection in `config/database.php`
3. Register a new account if needed

### ❌ Redirected to forgot password page

**Possible Causes:**
1. No token in URL
2. Accessing reset_password.php directly

**Solutions:**
1. Always use the link from forgot password page
2. Ensure URL has format: `reset_password.php?token=abc123...`
3. Don't bookmark or manually type the reset URL

### ❌ "Passwords do not match"

**Possible Causes:**
1. Typo in password or confirmation
2. JavaScript validation issue

**Solutions:**
1. Carefully re-enter both passwords
2. Use "Show Password" feature if available
3. Check browser console for JavaScript errors

### ❌ "Failed to reset password"

**Possible Causes:**
1. Database connection lost
2. Table structure mismatch
3. Permission issues

**Solutions:**
1. Check database connection
2. Verify table structure:
   ```sql
   DESCRIBE student;
   DESCRIBE instructor;
   DESCRIBE admin;
   ```
3. Check PHP error logs
4. Verify database user has UPDATE permissions

### ❌ Page shows blank/white screen

**Possible Causes:**
1. PHP syntax error
2. Missing file includes
3. Database connection error

**Solutions:**
1. Enable error display:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Check PHP error log
3. Verify all files exist:
   - `includes/auth.php`
   - `config/database.php`

## Database Checks

### Check if token exists
```sql
SELECT * FROM password_resets 
WHERE token = 'YOUR_TOKEN_HERE';
```

### Check if token is valid
```sql
SELECT 
    email,
    user_type,
    expires_at,
    used,
    CASE 
        WHEN expires_at < NOW() THEN 'EXPIRED'
        WHEN used = 1 THEN 'ALREADY USED'
        ELSE 'VALID'
    END as status
FROM password_resets 
WHERE token = 'YOUR_TOKEN_HERE';
```

### Check recent reset requests
```sql
SELECT 
    email,
    user_type,
    created_at,
    expires_at,
    used
FROM password_resets 
ORDER BY created_at DESC 
LIMIT 10;
```

### Clean up old tokens
```sql
DELETE FROM password_resets 
WHERE expires_at < NOW() 
   OR (used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY));
```

## File Permissions Check

### Windows (XAMPP)
```powershell
# Check if files are readable
Test-Path "c:\xampp\htdocs\online_learning_hub\Functionality\forgot_password.php"
Test-Path "c:\xampp\htdocs\online_learning_hub\Functionality\reset_password.php"
Test-Path "c:\xampp\htdocs\online_learning_hub\includes\auth.php"
```

## PHP Error Checking

### Enable error display (for development only)
Add to top of PHP files:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check PHP error log
```
Location: C:\xampp\php\logs\php_error_log
```

## Browser Console Checks

### Open browser console
- Chrome/Edge: F12 or Ctrl+Shift+I
- Firefox: F12 or Ctrl+Shift+K

### Check for JavaScript errors
Look for red error messages in Console tab

### Check network requests
1. Go to Network tab
2. Submit form
3. Check if request succeeds (200 status)
4. Check response for error messages

## Testing Workflow

### 1. Fresh Test
```
1. Clear browser cache
2. Open forgot_password.php
3. Enter valid email
4. Click "Send Reset Link"
5. Click "Reset Password Now"
6. Enter new password (min 6 chars)
7. Confirm password
8. Click "Reset Password"
9. Click "Go to Login"
10. Login with new password
```

### 2. Token Expiration Test
```
1. Request password reset
2. Wait 61 minutes
3. Try to use reset link
4. Should show "Invalid or expired token"
```

### 3. Token Reuse Test
```
1. Request password reset
2. Reset password successfully
3. Try to use same link again
4. Should show "Invalid or expired token"
```

## Debug Mode

### Enable detailed logging in auth.php
Add to methods:
```php
error_log("DEBUG: Token = " . $token);
error_log("DEBUG: Email = " . $email);
error_log("DEBUG: User Type = " . $userType);
```

### View logs
```
Windows: C:\xampp\apache\logs\error.log
```

## Quick Fixes

### Fix 1: Reset all tokens
```sql
UPDATE password_resets SET used = 1;
```

### Fix 2: Generate new token manually
```sql
INSERT INTO password_resets (email, token, user_type, expires_at, used, created_at)
VALUES ('your@email.com', 'test123', 'student', DATE_ADD(NOW(), INTERVAL 1 HOUR), 0, NOW());
```
Then use: `reset_password.php?token=test123`

### Fix 3: Reset password directly (emergency)
```sql
-- Password: "newpass123"
UPDATE student 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'your@email.com';
```

## Verification Steps

### ✅ Verify token was created
```sql
SELECT COUNT(*) FROM password_resets 
WHERE email = 'your@email.com' 
AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```
Should return: 1

### ✅ Verify token is valid
```sql
SELECT 
    CASE 
        WHEN expires_at > NOW() AND used = 0 THEN 'VALID'
        ELSE 'INVALID'
    END as status
FROM password_resets 
WHERE token = 'YOUR_TOKEN';
```
Should return: VALID

### ✅ Verify password was updated
```sql
SELECT 
    email,
    password,
    updated_at
FROM student 
WHERE email = 'your@email.com';
```
Check if password hash changed and updated_at is recent

## Contact Support

If issues persist:
1. Check all documentation files:
   - `PASSWORD_RESET_FIX_SUMMARY.md`
   - `PASSWORD_RESET_TESTING.md`
   - `PASSWORD_RESET_FLOW.md`

2. Gather information:
   - Error messages
   - PHP error log
   - Browser console errors
   - Database query results

3. Check database structure matches expected schema

## Emergency Password Reset

If all else fails, reset password directly in database:

```php
<?php
// Create a temporary file: emergency_reset.php
require_once 'config/database.php';

$email = 'your@email.com';
$newPassword = 'newpass123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$db = getDB();
$stmt = $db->prepare("UPDATE student SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute()) {
    echo "Password reset successfully!";
} else {
    echo "Error: " . $stmt->error;
}
?>
```

**⚠️ Delete this file immediately after use!**
