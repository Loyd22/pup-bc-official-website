<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';

// Make sure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Auto-logout after 15 minutes of inactivity
$maxIdleSeconds = 15 * 60; // 15 minutes

if (isset($_SESSION['admin_last_activity'])) {
    $idleTime = time() - (int) $_SESSION['admin_last_activity'];

    if ($idleTime > $maxIdleSeconds) {
        // Clear session and cookie
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        // Go straight to login when expired
        header('Location: login.php');
        exit;
    }
}

// Update last activity timestamp for active sessions
$_SESSION['admin_last_activity'] = time();

// If there is no valid session, force login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * Check if current admin has the specified role.
 */
function is_role(string $role): bool
{
    return ($_SESSION['admin_role'] ?? '') === $role;
}

/**
 * Check if current admin is super admin.
 */
function is_super_admin(): bool
{
    return is_role('super_admin');
}

/**
 * Check if current admin is content admin.
 */
function is_content_admin(): bool
{
    return is_role('content_admin');
}

/**
 * Require a specific role, redirect to appropriate page if not authorized.
 */
function require_role(string $requiredRole): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    $currentRole = $_SESSION['admin_role'] ?? 'content_admin';

    if ($currentRole !== $requiredRole) {
        add_flash('error', 'You do not have permission to access this page.');

        // Redirect content admins to news page, super admins to dashboard
        $redirectTo = ($currentRole === 'content_admin') ? 'news.php' : 'dashboard.php';
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Require super admin access, redirect to dashboard if not authorized.
 */
function require_super_admin(): void
{
    require_role('super_admin');
}
