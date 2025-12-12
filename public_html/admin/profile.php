<?php
declare(strict_types=1);

$pageTitle = 'My Profile';
$currentSection = 'profile';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

// Get current admin's full details from database
$adminId = get_current_admin_id($conn);
$adminData = null;

if ($adminId) {
    $stmt = $conn->prepare("SELECT id, username, full_name, email, role, created_at, last_login FROM admins WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $adminData = $result->fetch_assoc();
        $stmt->close();
    }
}

// If admin not found, redirect to dashboard
if (!$adminData) {
    add_flash('error', 'Unable to load profile information.');
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2>My Profile</h2>
    <p style="color: var(--muted); margin-bottom: 24px;">
        View your account information and profile details.
    </p>
    
    <div style="display: grid; gap: 24px;">
        <!-- Profile Header -->
        <div style="display: flex; align-items: center; gap: 20px; padding: 24px; background: var(--sidebar-hover); border-radius: var(--radius); border: 1px solid var(--border);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--maroon), var(--maroon-900)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 32px; flex-shrink: 0;">
                <?php echo strtoupper(substr($adminData['full_name'] ?: $adminData['username'], 0, 1)); ?>
            </div>
            <div>
                <h3 style="margin: 0 0 4px; font-size: 24px; color: var(--ink);">
                    <?php echo htmlspecialchars($adminData['full_name'] ?: $adminData['username']); ?>
                </h3>
                <p style="margin: 0; color: var(--muted); font-size: 14px;">
                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $adminData['role']))); ?>
                </p>
            </div>
        </div>
        
        <!-- Profile Information -->
        <div style="display: grid; gap: 20px;">
            <div style="padding: 20px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);">
                <h3 style="margin: 0 0 16px; font-size: 18px; color: var(--ink); border-bottom: 2px solid var(--maroon); padding-bottom: 8px;">
                    Account Information
                </h3>
                <div style="display: grid; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Username
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <?php echo htmlspecialchars($adminData['username']); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Full Name
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <?php echo htmlspecialchars($adminData['full_name'] ?: '—'); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Email Address
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <?php echo htmlspecialchars($adminData['email'] ?: '—'); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Role
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.875rem; background: <?php echo $adminData['role'] === 'super_admin' ? 'var(--maroon)' : 'var(--gold)'; ?>; color: white; font-weight: 600;">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $adminData['role']))); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Activity -->
            <div style="padding: 20px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);">
                <h3 style="margin: 0 0 16px; font-size: 18px; color: var(--ink); border-bottom: 2px solid var(--maroon); padding-bottom: 8px;">
                    Account Activity
                </h3>
                <div style="display: grid; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Account Created
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($adminData['created_at']))); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                            Last Login
                        </label>
                        <div style="font-size: 15px; color: var(--ink); padding: 8px 12px; background: var(--sidebar-hover); border-radius: 4px;">
                            <?php 
                            if ($adminData['last_login']) {
                                echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($adminData['last_login'])));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="dashboard.php" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <?php if (is_super_admin()): ?>
            <a href="admin_users.php" class="btn btn--secondary">
                <i class="fa-solid fa-users"></i>
                Manage Admin Users
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

