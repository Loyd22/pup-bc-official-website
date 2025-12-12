<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/email_helper.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if there's already an active (unexpired, unused) reset code
    $checkActive = $conn->prepare("SELECT id FROM admin_password_resets WHERE used = 0 AND expires_at > NOW() LIMIT 1");
    $hasActiveCode = false;
    
    if ($checkActive) {
        $checkActive->execute();
        $result = $checkActive->get_result();
        $hasActiveCode = $result->num_rows > 0;
        $checkActive->close();
    }
    
    if ($hasActiveCode) {
        $error = 'A reset code has already been sent. Please check your email or wait for it to expire (10 minutes).';
    } else {
        // Generate 6-digit code
        $code = (string)random_int(100000, 999999);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now
        
        // Mark any existing codes as used (only one active code at a time)
        $markUsed = $conn->prepare("UPDATE admin_password_resets SET used = 1 WHERE used = 0");
        if ($markUsed) {
            $markUsed->execute();
            $markUsed->close();
        }
        
        // Store the new code
        $insertCode = $conn->prepare("INSERT INTO admin_password_resets (code_hash, expires_at, used) VALUES (?, ?, 0)");
        if ($insertCode) {
            $insertCode->bind_param('ss', $codeHash, $expiresAt);
            $insertCode->execute();
            $insertCode->close();
            
            // Send email with code
            $emailSent = send_password_reset_email($code);
            
            if ($emailSent) {
                // Redirect to verification page
                header('Location: verify_code.php');
                exit;
            } else {
                $error = 'Failed to send verification code. Please check your SMTP configuration in admin/includes/email_config.php and ensure credentials are set correctly.';
                // Delete the code if email failed
                $deleteCode = $conn->prepare("DELETE FROM admin_password_resets WHERE code_hash = ?");
                if ($deleteCode) {
                    $deleteCode->bind_param('s', $codeHash);
                    $deleteCode->execute();
                    $deleteCode->close();
                }
            }
        } else {
            $error = 'Failed to generate verification code. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PUP Biñan Admin</title>
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
    
    <!-- Right Column: Forgot Password Form -->
    <main class="auth-container">
        <section class="auth-card">
            <h1>Forgot Password</h1>
            <p class="auth-card__subtitle">We'll send a verification code to your email to reset your password.</p>
            
            <?php if ($error !== null): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success !== null): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="post" class="form">
                <p style="color: var(--muted); font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
                    Click the button below to receive a 6-digit verification code via email. The code will expire in 10 minutes.
                </p>

                <button type="submit" class="btn btn--primary" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600;">
                    <i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>
                    Send Verification Code
                </button>
            </form>
            
            <div style="margin-top: 24px; text-align: center;">
                <a href="login.php" class="auth-card__footer-link">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 6px;"></i>
                    Back to login
                </a>
            </div>
            
            <div class="auth-card__footer">
                <p style="margin: 0;">For authorized personnel only.</p>
            </div>
        </section>
    </main>
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

