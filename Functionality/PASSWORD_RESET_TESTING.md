# Password Reset Functionality - Testing Guide

## Issues Fixed

### 1. **Token Not Passed to Reset Password Page**
   - **Problem**: The forgot password page generated a token but didn't pass it to the reset password page
   - **Fix**: Updated `forgot_password.php` to include the token in the URL parameter when redirecting to `reset_password.php`

### 2. **Missing Token Validation**
   - **Problem**: `reset_password.php` could be accessed without a token
   - **Fix**: Added validation to check if token exists in URL, redirects to forgot password page if missing

### 3. **Database Column Compatibility**
   - **Problem**: The `resetPassword()` method tried to update `updated_at` column which might not exist in all tables
   - **Fix**: Added dynamic column checking in `auth.php` to only update `updated_at` if it exists

### 4. **Token Variable Scope Issue**
   - **Problem**: The token wasn't accessible in the HTML section of forgot_password.php
   - **Fix**: Stored token in a separate variable `$resetToken` for use in the view

## How to Test

### Step 1: Access Forgot Password Page
1. Navigate to: `http://localhost/online_learning_hub/Functionality/forgot_password.php`
2. You should see a clean form asking for your email address

### Step 2: Request Password Reset
1. Enter a valid email address that exists in your database:
   - Student: `tilak@gmail.com`
   - Instructor: `nabinneupane@gmail.com`
   - Admin: `admin@learninghub.com`
2. Click "Send Reset Link"
3. You should see a success message with a "Reset Password Now" button

### Step 3: Reset Your Password
1. Click the "Reset Password Now" button
2. You should be redirected to the reset password page with the token in the URL
3. Enter a new password (minimum 6 characters)
4. Confirm the password
5. Click "Reset Password"
6. You should see a success message

### Step 4: Verify Password Change
1. Click "Go to Login"
2. Try logging in with your old password - it should fail
3. Try logging in with your new password - it should succeed

## Expected Behavior

### Forgot Password Page
- ✅ Shows email input field
- ✅ Validates email format
- ✅ Generates secure token
- ✅ Displays success message with reset link
- ✅ Shows "Reset Password Now" button with token in URL

### Reset Password Page
- ✅ Redirects to forgot password if no token provided
- ✅ Shows password input fields
- ✅ Validates password length (minimum 6 characters)
- ✅ Checks if passwords match
- ✅ Shows password strength indicator
- ✅ Disables submit button if passwords don't match
- ✅ Updates password in database
- ✅ Marks token as used
- ✅ Shows success message with login link

### Database Changes
- ✅ Token stored in `password_resets` table
- ✅ Token expires after 1 hour
- ✅ Token marked as used after successful reset
- ✅ Password updated in appropriate user table (student/instructor/admin)
- ✅ Handles tables with or without `updated_at` column

## Security Features

1. **Token Security**
   - Tokens are 64-character random hex strings
   - Tokens expire after 1 hour
   - Tokens can only be used once
   - Tokens are validated before password reset

2. **Password Security**
   - Passwords are hashed using `password_hash()` with bcrypt
   - Minimum password length enforced (6 characters)
   - Password confirmation required

3. **SQL Injection Prevention**
   - All queries use prepared statements
   - User input is sanitized and validated

4. **XSS Prevention**
   - All output is escaped using `htmlspecialchars()`

## Troubleshooting

### Issue: "Invalid or expired reset token"
- **Cause**: Token has expired (older than 1 hour) or already been used
- **Solution**: Request a new password reset link

### Issue: "Email not found"
- **Cause**: The email address doesn't exist in any user table
- **Solution**: Verify the email address or register a new account

### Issue: "Failed to reset password"
- **Cause**: Database connection issue or table structure mismatch
- **Solution**: Check database connection and verify table structure

### Issue: Redirected to forgot password page
- **Cause**: No token in URL or invalid token format
- **Solution**: Use the link from the forgot password page

## Database Schema

### password_resets Table
```sql
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_type` enum('student','instructor','admin') NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`)
);
```

## Files Modified

1. **Functionality/forgot_password.php**
   - Added `$resetToken` variable to store token
   - Updated success message to include reset link
   - Fixed reset password button to include token parameter

2. **Functionality/reset_password.php**
   - Added token validation at the top
   - Added redirect if no token provided
   - Improved error handling

3. **includes/auth.php**
   - Updated `resetPassword()` method to check for `updated_at` column
   - Added dynamic SQL query building based on column availability
   - Improved error handling and logging

## Next Steps (Optional Enhancements)

1. **Email Integration**
   - Integrate with PHPMailer or similar library
   - Send actual email with reset link instead of showing token on screen
   - Use email templates for better formatting

2. **Rate Limiting**
   - Limit password reset requests per IP address
   - Prevent brute force attacks

3. **Token Cleanup**
   - Add cron job to delete expired tokens
   - Keep database clean

4. **User Notifications**
   - Send email notification when password is changed
   - Alert user of suspicious activity
