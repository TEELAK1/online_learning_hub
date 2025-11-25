# CRITICAL FIX: "Invalid or expired reset token" Error - SOLVED

## 🔴 THE PROBLEM

You're getting "Invalid or expired reset token" because of a **database schema bug**:

```sql
-- WRONG (current):
`expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()

-- CORRECT (needed):
`expires_at` timestamp NOT NULL
```

The `ON UPDATE current_timestamp()` causes the expiration time to reset to NOW() every time the row is read or modified, making ALL tokens appear expired immediately!

---

## ✅ THE SOLUTION (Choose ONE method)

### 🚀 METHOD 1: Automatic Fix (EASIEST - Recommended)

1. **Open your browser**
2. **Go to**: `http://localhost/online_learning_hub/config/fix_database.php`
3. **Click through the fix** - it will automatically detect and fix the issue
4. **Delete the file** `fix_database.php` after running (security)

**Time: 30 seconds** ⏱️

---

### 💻 METHOD 2: phpMyAdmin (Easy)

1. **Open phpMyAdmin**: `http://localhost/phpmyadmin`
2. **Select database**: Click `onlinelearninghub_new` in left sidebar
3. **Click SQL tab** at the top
4. **Paste this command**:
   ```sql
   ALTER TABLE `password_resets` 
   MODIFY COLUMN `expires_at` timestamp NOT NULL;
   ```
5. **Click "Go"**
6. **Done!** ✅

**Time: 1 minute** ⏱️

---

### 🖥️ METHOD 3: MySQL Command Line (Advanced)

```cmd
cd C:\xampp\mysql\bin
mysql -u root -p
USE onlinelearninghub_new;
ALTER TABLE `password_resets` MODIFY COLUMN `expires_at` timestamp NOT NULL;
EXIT;
```

**Time: 2 minutes** ⏱️

---

## 🧪 TESTING AFTER FIX

1. **Clear old tokens** (optional but recommended):
   ```sql
   DELETE FROM password_resets;
   ```

2. **Test the flow**:
   - Go to: `http://localhost/online_learning_hub/Functionality/forgot_password.php`
   - Enter: `tilak@gmail.com`
   - Click "Send Reset Link"
   - Click "Reset Password Now"
   - Enter new password (min 6 characters)
   - Click "Reset Password"
   - **Should work!** ✅

---

## 🔍 VERIFY THE FIX

Run this in phpMyAdmin SQL tab:

```sql
SHOW CREATE TABLE `password_resets`;
```

**You should see**:
```sql
`expires_at` timestamp NOT NULL
```

**NOT**:
```sql
`expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

---

## 📊 CHECK TOKEN STATUS

To see your current tokens and their status:

```sql
SELECT 
    email,
    user_type,
    created_at,
    expires_at,
    used,
    CASE 
        WHEN expires_at < NOW() THEN '❌ EXPIRED'
        WHEN used = 1 THEN '✅ USED'
        ELSE '🟢 VALID'
    END as status,
    TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as minutes_remaining
FROM password_resets 
ORDER BY created_at DESC;
```

---

## 🎯 WHY THIS HAPPENED

The original database schema had a design flaw:

| Column Purpose | Correct Definition | Wrong Definition |
|----------------|-------------------|------------------|
| **created_at** (when record created) | `timestamp DEFAULT CURRENT_TIMESTAMP` | ✅ Correct |
| **updated_at** (when record modified) | `timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | ✅ Correct for this purpose |
| **expires_at** (fixed expiration time) | `timestamp NOT NULL` | ❌ Should NOT have ON UPDATE |

The `expires_at` should be **set once** when the token is created and **never change**. The `ON UPDATE CURRENT_TIMESTAMP` was incorrectly applied to this column.

---

## 📝 COMPLETE FLOW AFTER FIX

```
1. User requests reset
   ↓
2. Token generated with expires_at = NOW() + 1 hour
   ↓
3. Token stored in database (expires_at = 2025-11-25 22:00:00)
   ↓
4. User clicks reset link (5 minutes later)
   ↓
5. System checks: expires_at (22:00:00) > NOW() (21:05:00) ✅
   ↓
6. Token is VALID - user can reset password
   ↓
7. Password updated, token marked as used
   ↓
8. User can login with new password ✅
```

**BEFORE FIX** (broken):
```
5. System checks token
   ↓ (ON UPDATE triggers)
   expires_at automatically changes to NOW()
   ↓
   expires_at (21:05:00) > NOW() (21:05:00) ❌
   ↓
   Token appears EXPIRED immediately
```

---

## 🛠️ FILES CREATED FOR YOU

1. **`config/fix_database.php`** - Automatic fix script (run once, then delete)
2. **`config/fix_password_resets_table.sql`** - Manual SQL fix
3. **`config/FIX_EXPIRED_TOKEN_ERROR.md`** - Detailed fix guide

---

## ⚡ QUICK REFERENCE

| Issue | Solution |
|-------|----------|
| "Invalid or expired reset token" | Fix database schema (see methods above) |
| "Email not found" | Use valid email from database |
| "Passwords do not match" | Re-enter passwords carefully |
| "Failed to reset password" | Check database connection |

---

## 🔐 SECURITY NOTES

After fixing:
- ✅ Tokens expire after 1 hour (correctly)
- ✅ Tokens can only be used once
- ✅ Passwords are hashed with bcrypt
- ✅ SQL injection prevented (prepared statements)
- ✅ XSS prevented (output escaping)

---

## 📞 STILL HAVING ISSUES?

1. **Check database connection**:
   - XAMPP MySQL is running
   - Database name is correct: `onlinelearninghub_new`

2. **Check PHP errors**:
   - Location: `C:\xampp\php\logs\php_error_log`

3. **Check Apache errors**:
   - Location: `C:\xampp\apache\logs\error.log`

4. **Verify table exists**:
   ```sql
   SHOW TABLES LIKE 'password_resets';
   ```

5. **Check user exists**:
   ```sql
   SELECT email FROM student WHERE email = 'tilak@gmail.com';
   ```

---

## 🎉 SUCCESS CHECKLIST

After applying the fix, you should be able to:

- ✅ Request password reset
- ✅ Receive reset token
- ✅ Click reset link with token in URL
- ✅ Enter new password
- ✅ Successfully reset password
- ✅ Login with new password
- ✅ Old password no longer works

---

## ⚠️ IMPORTANT

**After running `fix_database.php`, DELETE IT immediately for security!**

The file contains database access code and should not be left on your server.

---

## 📚 Additional Documentation

- `Functionality/PASSWORD_RESET_FIX_SUMMARY.md` - Complete fix summary
- `Functionality/PASSWORD_RESET_TESTING.md` - Testing guide
- `Functionality/PASSWORD_RESET_FLOW.md` - Visual flow diagrams
- `Functionality/PASSWORD_RESET_TROUBLESHOOTING.md` - Troubleshooting guide

---

## Summary

**Problem**: Database schema bug with `ON UPDATE CURRENT_TIMESTAMP`  
**Impact**: All tokens appeared expired immediately  
**Fix**: Remove `ON UPDATE` clause from `expires_at` column  
**Time to Fix**: 30 seconds - 2 minutes  
**Difficulty**: Easy  
**Status**: ✅ SOLVED

Your password reset functionality will work perfectly after this fix! 🚀
