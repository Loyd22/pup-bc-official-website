<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please provide both username and password.';
    } else {
        $sql = "SELECT id, username, password_hash, full_name, role FROM admins WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();  
            $admin = $result->fetch_assoc();
            $stmt->close();

            if ($admin) {
                $passwordHash = $admin['password_hash'] ?? '';
                $isValid = password_verify($password, $passwordHash);

                if (!$isValid && $passwordHash !== '' && hash_equals($passwordHash, $password)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $rehash = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    if ($rehash) {
                        $rehash->bind_param('si', $newHash, $admin['id']);
                        $rehash->execute();
                        $rehash->close();
                        $passwordHash = $newHash;
                        $isValid = true;
                    }
                }

                if ($isValid) {
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'content_admin';

                    $update = $conn->prepare("UPDATE admins SET last_login = NOW(), password_hash = ? WHERE id = ?");
                    if ($update) {
                        $update->bind_param('si', $passwordHash, $admin['id']);
                        $update->execute();
                        $update->close();
                    }

                    header('Location: dashboard.php');
                    exit;
                }
            }
        }

        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Biñan Admin Login</title>
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
    
    <!-- Right Column: Login Form -->
    <main class="auth-container">
        <section class="auth-card">
            <h1>Admin Login</h1>
            <p class="auth-card__subtitle">Sign in to manage the PUP Biñan website.</p>
            <?php if ($error !== null): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($message = get_flash('success')): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post" class="form">
                <label for="username">USERNAME</label>
                <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter your admin username">

                <label for="password">PASSWORD</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter your password">

                <div class="form__remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Keep me signed in</label>
                </div>

                <div style="margin-top: -8px; margin-bottom: 16px; text-align: right;">
                    <a href="forgot_password.php" class="auth-card__footer-link">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn btn--primary" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600;">
                    Sign in <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>
            
            <div class="auth-card__footer">
                <p style="margin: 0 0 8px;">For authorized personnel only.</p>
                <p style="margin: 0;">Need help? Contact the campus IT office.</p>
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
