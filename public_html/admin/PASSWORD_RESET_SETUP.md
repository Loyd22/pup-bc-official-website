# Password Reset Feature Setup Guide

## Overview
This feature adds a secure "Forgot password?" functionality for the superadmin account. When requested, a 6-digit verification code is sent via email, which must be entered along with a new password to complete the reset.

## Files Created/Modified

### ✅ Created Files:

1. **`database/admin_password_resets.sql`**
   - Database table schema for storing reset codes
   - Columns: `id`, `code_hash`, `expires_at`, `used`, `created_at`
   - Indexes for efficient queries

2. **`admin/includes/email_config.php`**
   - SMTP email configuration
   - Contains placeholder values that need to be replaced with real credentials
   - Email address: `johnloydviray22@gmail.com` (fixed for superadmin)

3. **`admin/includes/email_helper.php`**
   - Email sending functions
   - Supports PHPMailer (if installed) or falls back to PHP `mail()`
   - Functions: `send_password_reset_email()`, `send_password_reset_email_fallback()`, `get_email_config()`

4. **`admin/forgot_password.php`**
   - Page to request password reset code
   - Simple form with "Send Verification Code" button
   - Rate limiting: Prevents sending new code if active code exists

5. **`admin/verify_code.php`**
   - Page to verify code and reset password
   - Form with: code input, new password, confirm password
   - Validates code, updates password, redirects to login

### ✅ Modified Files:

1. **`admin/login.php`**
   - Added "Forgot password?" link under password field
   - Styled to match existing design (maroon color, left-aligned)

## Setup Instructions

### Step 1: Create Database Table

Run the SQL file to create the `admin_password_resets` table:

```sql
-- Execute this SQL in your database
SOURCE database/admin_password_resets.sql;
-- OR copy and paste the contents into your MySQL client
```

Or manually run:
```sql
CREATE TABLE IF NOT EXISTS `admin_password_resets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed 6-digit code using password_hash',
  `expires_at` DATETIME NOT NULL COMMENT 'Code expiration time (10 minutes from generation)',
  `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if code has been used, 0 if still valid',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_expires_used` (`expires_at`, `used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset codes for superadmin account';
```

### Step 2: Configure Email Settings

Edit `admin/includes/email_config.php` and replace the placeholder values:

```php
'smtp_host' => 'smtp.gmail.com',        // Your SMTP host
'smtp_port' => 587,                     // Your SMTP port
'smtp_username' => 'your-email@gmail.com',  // Your SMTP username
'smtp_password' => 'your-app-password',     // Your SMTP app password
'smtp_encryption' => 'tls',             // 'tls' or 'ssl'
```

#### For Gmail:
1. Enable 2-Step Verification on your Google account
2. Generate an App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "PUPBC Admin Password Reset"
   - Copy the generated 16-character password
   - Use this as your `smtp_password` (not your regular Gmail password)

#### For Other Email Providers:
- **Outlook/Hotmail**: smtp-mail.outlook.com, port 587, TLS
- **Yahoo**: smtp.mail.yahoo.com, port 587, TLS
- **Custom SMTP**: Check your provider's documentation

### Step 3: Install PHPMailer (Optional but Recommended)

PHPMailer provides better email delivery and error handling. If not installed, the system will fall back to PHP's `mail()` function.

**Option A: Using Composer (Recommended)**
```bash
cd pupbc-website
composer require phpmailer/phpmailer
```

**Option B: Manual Installation**
1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
2. Extract to `vendor/phpmailer/phpmailer/`
3. The system will auto-detect it

## Password Reset Flow

### Step 1: Request Reset (`forgot_password.php`)
1. User clicks "Forgot password?" link on login page
2. User clicks "Send Verification Code" button
3. System checks if active code exists:
   - If yes → Show error: "A reset code has already been sent..."
   - If no → Continue
4. Generate 6-digit code: `random_int(100000, 999999)`
5. Hash code: `password_hash($code, PASSWORD_DEFAULT)`
6. Store in database:
   - Mark any existing codes as `used = 1`
   - Insert new code with `expires_at = NOW() + 10 minutes`
   - Set `used = 0`
7. Send email with code via `send_password_reset_email()`
8. Redirect to `verify_code.php`

### Step 2: Verify Code and Reset Password (`verify_code.php`)
1. User enters:
   - 6-digit verification code
   - New password (min. 8 characters)
   - Confirm password
2. System validates:
   - All fields filled
   - Code is 6 digits
   - Password length >= 8 characters
   - Passwords match
3. Get most recent unused reset code from database
4. Check:
   - Code exists
   - `used = 0`
   - `expires_at > NOW()`
   - Code matches using `password_verify()`
5. If valid:
   - Find superadmin account (by `role = 'super_admin'` OR `username = 'admin'`)
   - Hash new password: `password_hash($newPassword, PASSWORD_DEFAULT)`
   - Update `admins.password_hash`
   - Mark reset code as `used = 1`
   - Redirect to `login.php` with success message
6. If invalid:
   - Show error message
   - Allow retry

## Security Features

✅ **Code Security:**
- Codes hashed with `password_hash()` (never stored plain text)
- 10-minute expiration
- One-time use only
- Only one active code at a time

✅ **Rate Limiting:**
- Cannot request new code if active code exists
- Prevents spam/abuse

✅ **Password Security:**
- Minimum 8 characters required
- Passwords hashed with `password_hash()`
- Uses `password_verify()` for login (existing behavior preserved)

✅ **Database Security:**
- All queries use prepared statements
- SQL injection protection
- Proper indexing for performance

✅ **Email Security:**
- Code sent to fixed superadmin email
- No email field in form (prevents email enumeration)
- SMTP authentication required

## Testing Checklist

- [ ] Database table created successfully
- [ ] SMTP credentials configured
- [ ] PHPMailer installed (optional)
- [ ] "Forgot password?" link appears on login page
- [ ] Can request reset code
- [ ] Email received with code
- [ ] Code verification works
- [ ] Invalid code shows error
- [ ] Expired code shows error
- [ ] Password reset works
- [ ] Can login with new password
- [ ] Rate limiting works (can't request new code if active exists)
- [ ] Password validation works (min 8 chars, must match)

## Troubleshooting

### Email Not Sending:
1. Check SMTP credentials in `email_config.php`
2. Verify firewall allows SMTP connections
3. Check PHP error logs for email errors
4. Test with PHPMailer if using fallback `mail()` function

### Code Not Working:
1. Check code hasn't expired (10 minutes)
2. Verify code hasn't been used already
3. Check if active code exists (rate limiting)
4. Request a new code if needed

### Database Errors:
1. Verify `admin_password_resets` table exists
2. Check database connection in `db.php`
3. Ensure MySQL user has INSERT/UPDATE/SELECT permissions

### Superadmin Not Found:
- The system queries for `role = 'super_admin'` OR `username = 'admin'`
- If your admin table doesn't have a `role` column, it will find by username
- Ensure at least one admin account exists

## Notes

- **Only works for superadmin** - The email is hardcoded to `johnloydviray22@gmail.com`
- **Rate limiting** - Only one active reset code at a time
- **Existing login preserved** - All existing login logic remains unchanged
- **Password hashing** - Uses `password_hash()` and `password_verify()` (secure)
- Codes are automatically cleaned up (expired codes can be manually deleted)

