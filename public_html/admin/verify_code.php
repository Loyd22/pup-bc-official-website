<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if ($code === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Invalid code format. Please enter a 6-digit number.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Get ALL unused reset records (to check all possible codes)
        // First try to get unexpired codes, then check all if needed
        $getCode = $conn->prepare("SELECT id, code_hash, expires_at, created_at FROM admin_password_resets WHERE used = 0 ORDER BY created_at DESC");
        
        if ($getCode) {
            $getCode->execute();
            $codeResult = $getCode->get_result();
            $codeData = null;
            $isValid = false;
            $foundExpired = false;
            $hasCodes = false;
            
            // Check each code until we find a match
            while ($row = $codeResult->fetch_assoc()) {
                $hasCodes = true;
                // Check if code hasn't expired (using PHP time for consistency)
                $expiresAt = strtotime($row['expires_at']);
                $now = time();
                
                if ($expiresAt > $now) {
                    // Verify the code against this hash
                    if (password_verify($code, $row['code_hash'])) {
                        $codeData = $row;
                        $isValid = true;
                        break; // Found valid code, stop searching
                    }
                } else {
                    $foundExpired = true;
                }
            }
            $getCode->close();
            
            // Check if no codes were found at all
            if (!$hasCodes) {
                $error = 'No valid reset code found. Please request a new code.';
            } elseif ($isValid && $codeData) {
                // Get the superadmin account (assuming username is 'admin' or role is 'super_admin')
                // Since there's only one superadmin, we'll find it by role or username
                $getAdmin = $conn->prepare("SELECT id FROM admins WHERE role = 'super_admin' OR username = 'admin' LIMIT 1");
                $adminId = null;
                
                if ($getAdmin) {
                    $getAdmin->execute();
                    $adminResult = $getAdmin->get_result();
                    $adminData = $adminResult->fetch_assoc();
                    $getAdmin->close();
                    
                    if ($adminData) {
                        $adminId = (int)$adminData['id'];
                    }
                }
                
                if ($adminId === null) {
                    $error = 'Superadmin account not found.';
                } else {
                    // Hash the new password
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update the password
                    $updatePassword = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    if ($updatePassword) {
                        $updatePassword->bind_param('si', $newPasswordHash, $adminId);
                        $updatePassword->execute();
                        $updatePassword->close();
                        
                        // Mark the reset code as used
                        $markUsed = $conn->prepare("UPDATE admin_password_resets SET used = 1 WHERE id = ?");
                        if ($markUsed) {
                            $markUsed->bind_param('i', $codeData['id']);
                            $markUsed->execute();
                            $markUsed->close();
                        }
                        
                        // Redirect to login with success message
                        add_flash('success', 'Password has been reset. Please sign in with your new password.');
                        header('Location: login.php');
                        exit;
                    }
                }
            } else {
                // Code was found but didn't match or expired
                if ($foundExpired) {
                    $error = 'The reset code has expired. Please request a new code.';
                } else {
                    $error = 'Invalid code. Please check your email and enter the correct 6-digit code.';
                }
            }
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PUP Biñan Admin</title>
    <link rel="stylesheet" href="../asset/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="auth-body">
    <!-- Left Column: Branding -->
    <div class="auth-branding">
        <div>
            <div class="auth-branding__logo">
                <img src="../images/PUPLogo.png" alt="PUP Logo">
            </div>
            <h2 class="auth-branding__title">PUP BIÑAN</h2>
            <p class="auth-branding__subtitle">Campus Administration Panel</p>
            <div class="auth-branding__secure">
                <span class="auth-branding__secure-dot"></span>
                <span>SECURE ACCESS ONLY</span>
            </div>
        </div>
        <div class="auth-branding__footer">
            © <?php echo date('Y'); ?> Polytechnic University of the Philippines – Biñan Campus
        </div>
    </div>
    
    <!-- Right Column: Reset Password Form -->
    <main class="auth-container">
        <section class="auth-card">
            <h1>Reset Password</h1>
            <p class="auth-card__subtitle">Enter the verification code sent to your email and choose a new password.</p>
            
            <?php if ($error !== null): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success !== null): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="post" class="form">
                <label for="code">VERIFICATION CODE</label>
                <input 
                    type="text" 
                    name="code" 
                    id="code" 
                    required 
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    placeholder="000000"
                    style="text-align: center; font-size: 1.75rem; letter-spacing: 0.5em; font-weight: 600; padding: 16px;"
                >
                <small style="color: var(--muted); font-size: 0.875rem; margin-top: -8px; display: block; margin-bottom: 8px;">
                    Enter the 6-digit code sent to your email
                </small>

                <label for="new_password">NEW PASSWORD</label>
                <input 
                    type="password" 
                    name="new_password" 
                    id="new_password" 
                    required 
                    autocomplete="new-password"
                    minlength="8"
                    placeholder="Enter new password (min. 8 characters)"
                >

                <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirm_password" 
                    required 
                    autocomplete="new-password"
                    minlength="8"
                    placeholder="Confirm new password"
                >

                <button type="submit" class="btn btn--primary" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600; margin-top: 8px;">
                    <i class="fa-solid fa-key" style="margin-right: 8px;"></i>
                    Reset Password
                </button>
            </form>
            
            <div style="margin-top: 24px; text-align: center; font-size: 14px;">
                <a href="forgot_password.php" class="auth-card__footer-link">
                    Request a new code
                </a>
                <span style="color: var(--muted); margin: 0 8px;">•</span>
                <a href="login.php" class="auth-card__footer-link">
                    Back to login
                </a>
            </div>
            
            <div class="auth-card__footer">
                <p style="margin: 0;">For authorized personnel only.</p>
            </div>
        </section>
    </main>
    
    <script>
        // Auto-focus code input and format on input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('code');
            if (codeInput) {
                codeInput.focus();
                
                // Only allow numbers
                codeInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length > 6) {
                        this.value = this.value.slice(0, 6);
                    }
                });
            }
            
            // Password match validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            function validatePasswords() {
                if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
            
            if (newPasswordInput && confirmPasswordInput) {
                newPasswordInput.addEventListener('input', validatePasswords);
                confirmPasswordInput.addEventListener('input', validatePasswords);
            }
        });
    </script>
<script>
    // Check if user is on mobile device and redirect
    (function() {
        function checkMobile() {
            if (window.innerWidth < 768) {
                alert('The admin panel is not available on mobile. Please use a laptop or desktop.');
                window.location.href = '/';
            }
        }
        
        // Check on page load
        checkMobile();
        
        // Check on window resize
        window.addEventListener('resize', checkMobile);
    })();
</script>
</body>
</html>

