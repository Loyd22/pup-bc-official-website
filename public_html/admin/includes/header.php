<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Admin Panel';
$currentSection = $currentSection ?? '';
$adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | PUP Biñan Admin</title>
    <link rel="icon" type="image/png" href="../asset/PUPicon.png">
    <link rel="stylesheet" href="../asset/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="../asset/vendors/tinymce/js/tinymce/tinymce.min.js"></script>
    <script src="../asset/js/admin-form-confirm.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof tinymce === 'undefined') {
                return;
            }

            tinymce.init({
                selector: 'textarea.js-editor',
                height: 280,
                menubar: false,
                plugins: 'lists link table autoresize',
                toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link table | removeformat',
                branding: false,
                convert_urls: false
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('jsSidebarToggle');
            if (!toggle) return;
            
            // Create sidebar overlay if it doesn't exist
            var overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                document.body.appendChild(overlay);
            }
            
            // Toggle sidebar
            toggle.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-open');
            });
            
            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function () {
                document.body.classList.remove('sidebar-open');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992) {
                    var sidebar = document.querySelector('.sidebar');
                    var isClickInsideSidebar = sidebar && sidebar.contains(e.target);
                    var isClickOnToggle = toggle && toggle.contains(e.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && document.body.classList.contains('sidebar-open')) {
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // User menu dropdown toggle
            var userMenuTrigger = document.getElementById('userMenuTrigger');
            var userMenuDropdown = document.getElementById('userMenuDropdown');
            
            if (userMenuTrigger && userMenuDropdown) {
                userMenuTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var isOpen = userMenuDropdown.classList.toggle('is-open');
                    userMenuTrigger.classList.toggle('active', isOpen);
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuTrigger.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                        userMenuDropdown.classList.remove('is-open');
                        userMenuTrigger.classList.remove('active');
                    }
                });
                
                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && userMenuDropdown.classList.contains('is-open')) {
                        userMenuDropdown.classList.remove('is-open');
                        userMenuTrigger.classList.remove('active');
                    }
                });
            }
        });
    </script>
</head>
<body>
<div class="admin-shell">
    <div class="sidebar-overlay"></div>
    <aside class="sidebar">
        <div class="sidebar__brand">
            <div class="sidebar__logo" aria-hidden="true">
                <img src="../images/PUPLogo.png" alt="PUP Logo" style="width: 28px; height: 28px; object-fit: contain;">
            </div>
            <span>PUP Biñan Admin</span>
        </div>
        <nav class="sidebar__nav">
            <a href="dashboard.php" class="sidebar__link <?php echo $currentSection === 'dashboard' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 12l9-9 9 9v9a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9z"></path></svg></span>
                <span>Dashboard</span>
            </a>
            <a href="announcements.php" class="sidebar__link <?php echo $currentSection === 'announcements' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM7 11h2v2H7v-2zm4 0h2v2h-2v-2zm4 0h2v2h-2v-2z"></path></svg></span>
                <span>Announcements</span>
            </a>
            <a href="news.php" class="sidebar__link <?php echo $currentSection === 'news' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v14a2 2 0 0 1-2 2H4z"></path><path d="M7 8h10M7 12h10M7 16h6" stroke="#fff" stroke-width="2"/></svg></span>
                <span>News</span>
            </a>
            <?php if (is_super_admin()): ?>
            <a href="forms.php" class="sidebar__link <?php echo $currentSection === 'forms' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"></path></svg></span>
                <span>Forms</span>
            </a>
            <a href="programs.php" class="sidebar__link <?php echo $currentSection === 'programs' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 16l-5 2.72L7 16v-3.73L12 15l5-2.73V16z"></path></svg></span>
                <span>Academic Programs</span>
            </a>
            <a href="csv_analytics.php" class="sidebar__link <?php echo $currentSection === 'csv_analytics' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2z"></path></svg></span>
                <span>CSV Analytics</span>
            </a>
            <a href="traditions.php" class="sidebar__link <?php echo $currentSection === 'traditions' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"></path></svg></span>
                <span>University Traditions</span>
            </a>
            <a href="history.php" class="sidebar__link <?php echo $currentSection === 'history' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"></path></svg></span>
                <span>History Images</span>
            </a>
            <a href="campus_officials.php" class="sidebar__link <?php echo $currentSection === 'campus_officials' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg></span>
                <span>Campus Officials</span>
            </a>
            <a href="campus_offices.php" class="sidebar__link <?php echo $currentSection === 'campus_offices' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm-1 16v-5h2v5h-2zm0-7h2v2h-2v-2z"></path></svg></span>
                <span>Campus Offices</span>
            </a>
            <a href="admin_users.php" class="sidebar__link <?php echo $currentSection === 'admin_users' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"></path></svg></span>
                <span>Admin Users</span>
            </a>
            <a href="settings.php" class="sidebar__link <?php echo $currentSection === 'settings' ? 'is-active' : ''; ?>">
                <span class="sidebar__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm8.94 4a7.94 7.94 0 0 0-.34-2l2.1-1.64-2-3.46-2.48.5a8.07 8.07 0 0 0-1.74-1l-.38-2.51H9.9l-.38 2.51a8.07 8.07 0 0 0-1.74 1l-2.48-.5-2 3.46L5.4 10a7.94 7.94 0 0 0 0 4l-2.1 1.64 2 3.46 2.48-.5a8.07 8.07 0 0 0 1.74 1l.38 2.51h4.2l.38-2.51a8.07 8.07 0 0 0 1.74-1l2.48.5 2-3.46L20.6 14c.23-.64.34-1.31.34-2z"></path></svg></span>
                <span>Site Settings</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar__spacer"></div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <button class="topbar__toggle" id="jsSidebarToggle" aria-label="Toggle navigation">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"></path></svg>
            </button>
            <div class="topbar__title">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>
            <div class="topbar__user" id="userMenuTrigger">
                <div class="topbar__user-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($adminName); ?></span>
                <i class="fa-solid fa-chevron-down topbar__user-chevron"></i>
            </div>
            <div class="user-menu-dropdown" id="userMenuDropdown">
                <div class="user-menu-header">
                    <div class="user-menu-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                    <div class="user-menu-info">
                        <div class="user-menu-name"><?php echo htmlspecialchars($adminName); ?></div>
                        <div class="user-menu-role"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Administrator'); ?></div>
                    </div>
                </div>
                <div class="user-menu-divider"></div>
                <a href="profile.php" class="user-menu-item">
                    <i class="fa-solid fa-user"></i>
                    <span>View Profile</span>
                </a>
                <a href="logout.php" class="user-menu-item user-menu-item--logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>
        <main class="admin-main">
            <?php if ($message = get_flash('success')): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($message = get_flash('error')): ?>
                <div class="alert alert--error"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
