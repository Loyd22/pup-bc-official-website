<?php
declare(strict_types=1);

$pageTitle = 'Admin Users';
$currentSection = 'admin_users';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$currentAdminId = get_current_admin_id($conn);
$editing = null;

// Load for edit
if (isset($_GET['id'])) {
    $adminId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, username, full_name, email, role FROM admins WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editing = $result->fetch_assoc();
        $stmt->close();
        
        if (!$editing) {
            add_flash('error', 'Admin user not found.');
            header('Location: admin_users.php');
            exit;
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Prevent self-deletion
    if ($deleteId === $currentAdminId) {
        add_flash('error', 'You cannot delete your own account.');
        header('Location: admin_users.php');
        exit;
    }
    
    // Prevent deletion of super admin
    $checkRoleStmt = $conn->prepare("SELECT role FROM admins WHERE id = ?");
    if ($checkRoleStmt) {
        $checkRoleStmt->bind_param('i', $deleteId);
        $checkRoleStmt->execute();
        $roleResult = $checkRoleStmt->get_result();
        if ($roleRow = $roleResult->fetch_assoc()) {
            if ($roleRow['role'] === 'super_admin') {
                $checkRoleStmt->close();
                add_flash('error', 'Super Admin cannot be deleted. There must always be exactly one Super Admin.');
                header('Location: admin_users.php');
                exit;
            }
        }
        $checkRoleStmt->close();
    }
    
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
        add_flash('success', 'Admin user deleted.');
    }
    header('Location: admin_users.php');
    exit;
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'content_admin');
    
    // If editing a super admin, preserve their role (cannot change super admin role)
    $isEditingSuperAdmin = false;
    if ($id) {
        $checkRoleStmt = $conn->prepare("SELECT role FROM admins WHERE id = ?");
        if ($checkRoleStmt) {
            $checkRoleStmt->bind_param('i', $id);
            $checkRoleStmt->execute();
            $roleResult = $checkRoleStmt->get_result();
            if ($roleRow = $roleResult->fetch_assoc()) {
                if ($roleRow['role'] === 'super_admin') {
                    $isEditingSuperAdmin = true;
                    $role = 'super_admin'; // Force super_admin role, ignore submitted value
                }
            }
            $checkRoleStmt->close();
        }
    }
    
    // Validate role (only if not editing super admin)
    if (!$isEditingSuperAdmin && !in_array($role, ['super_admin', 'content_admin'], true)) {
        $role = 'content_admin';
    }
    
    // Prevent creating new super admin if one already exists
    if (!$id && $role === 'super_admin') {
        $checkSuperAdminStmt = $conn->prepare("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin'");
        if ($checkSuperAdminStmt) {
            $checkSuperAdminStmt->execute();
            $superAdminResult = $checkSuperAdminStmt->get_result();
            if ($superAdminRow = $superAdminResult->fetch_assoc()) {
                if ((int)$superAdminRow['count'] > 0) {
                    $checkSuperAdminStmt->close();
                    add_flash('error', 'Cannot create Super Admin. There can only be one Super Admin. Please edit the existing Super Admin instead.');
                    header('Location: admin_users.php');
                    exit;
                }
            }
            $checkSuperAdminStmt->close();
        }
    }
    
    if ($username === '' || $fullName === '') {
        add_flash('error', 'Username and full name are required.');
        header('Location: admin_users.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    // Check username uniqueness (excluding current record if editing)
    $checkSql = "SELECT id FROM admins WHERE username = ?" . ($id ? " AND id != ?" : "");
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        if ($id) {
            $checkStmt->bind_param('si', $username, $id);
        } else {
            $checkStmt->bind_param('s', $username);
        }
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            add_flash('error', 'Username already exists.');
            header('Location: admin_users.php' . ($id ? '?id='.$id : ''));
            exit;
        }
        $checkStmt->close();
    }
    
    $stmt = null;
    try {
        if ($id) {
            // UPDATE
            if ($password !== '') {
                // Update password if provided
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE admins SET username = ?, password_hash = ?, full_name = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('sssssi', $username, $passwordHash, $fullName, $email, $role, $id);
                }
            } else {
                // Don't update password if not provided
                $sql = "UPDATE admins SET username = ?, full_name = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssssi', $username, $fullName, $email, $role, $id);
                }
            }
            
            if ($stmt && $stmt->execute()) {
                add_flash('success', 'Admin user updated.');
            } else {
                throw new mysqli_sql_exception("Failed to update admin user.");
            }
        } else {
            // INSERT
            if ($password === '') {
                add_flash('error', 'Password is required when creating a new admin.');
                header('Location: admin_users.php');
                exit;
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO admins (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sssss', $username, $passwordHash, $fullName, $email, $role);
                if ($stmt->execute()) {
                    add_flash('success', 'Admin user created.');
                } else {
                    throw new mysqli_sql_exception("Failed to create admin user.");
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        add_flash('error', 'Unable to save the admin user. Please try again.');
        header('Location: admin_users.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: admin_users.php');
    exit;
}

// List all admins
$admins = $conn->query("
    SELECT id, username, full_name, email, role, last_login, created_at
    FROM admins
    ORDER BY created_at DESC
");

// Check if super admin exists (for UI purposes)
$superAdminExists = false;
$superAdminCheck = $conn->query("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin'");
if ($superAdminCheck) {
    $superAdminRow = $superAdminCheck->fetch_assoc();
    $superAdminExists = (int)$superAdminRow['count'] > 0;
    $superAdminCheck->free();
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2><?php echo $editing ? 'Edit Admin User' : 'Create Admin User'; ?></h2>
    <form method="post" action="admin_users.php" class="form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>

        <label for="username">Username <span style="color: red;">*</span></label>
        <input type="text" id="username" name="username"
               value="<?php echo htmlspecialchars($editing['username'] ?? '', ENT_QUOTES); ?>" required>

        <label for="password">Password <?php echo $editing ? '(leave blank to keep current)' : ''; ?> <span style="color: red;"><?php echo $editing ? '' : '*'; ?></span></label>
        <input type="password" id="password" name="password"
               <?php echo $editing ? '' : 'required'; ?>
               placeholder="<?php echo $editing ? 'Leave blank to keep current password' : 'Enter password'; ?>">

        <label for="full_name">Full Name <span style="color: red;">*</span></label>
        <input type="text" id="full_name" name="full_name"
               value="<?php echo htmlspecialchars($editing['full_name'] ?? '', ENT_QUOTES); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?php echo htmlspecialchars($editing['email'] ?? '', ENT_QUOTES); ?>">

        <?php 
        $isSuperAdmin = isset($editing['role']) && $editing['role'] === 'super_admin';
        if ($isSuperAdmin): 
            // Hide role field for super admin, show readonly display instead
        ?>
            <label>Role</label>
            <input type="text" value="Super Admin" readonly 
                   style="background-color: #f5f5f5; cursor: not-allowed; color: var(--maroon); font-weight: 600;">
            <input type="hidden" name="role" value="super_admin">
            <small style="color: var(--muted);">Super Admin role cannot be changed.</small>
        <?php else: ?>
            <label for="role">Role <span style="color: red;">*</span></label>
            <select id="role" name="role" required>
                <option value="content_admin" <?php echo ($editing['role'] ?? 'content_admin') === 'content_admin' ? 'selected' : ''; ?>>Content Admin</option>
                <?php if (!$superAdminExists || (isset($editing['role']) && $editing['role'] === 'super_admin')): ?>
                    <option value="super_admin" <?php echo ($editing['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                <?php else: ?>
                    <option value="super_admin" disabled>Super Admin (Already exists - only one allowed)</option>
                <?php endif; ?>
            </select>
            <?php if ($superAdminExists && !isset($editing['role'])): ?>
                <small style="color: var(--muted);">Super Admin already exists. Only one Super Admin is allowed.</small>
            <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update Admin User' : 'Create Admin User'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="admin_users.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Existing Admin Users</h2>
    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Last Login</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($admins && $admins->num_rows > 0): ?>
                <?php while ($row = $admins->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?: '—', ENT_QUOTES); ?></td>
                        <td>
                            <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.85rem; background: <?php echo $row['role'] === 'super_admin' ? '#7a0019' : '#f3b233'; ?>; color: white;">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['role'])), ENT_QUOTES); ?>
                            </span>
                        </td>
                        <td><?php echo $row['last_login']
                                ? htmlspecialchars(date('M j, Y g:i A', strtotime($row['last_login'])), ENT_QUOTES)
                                : 'Never'; ?></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['created_at'])), ENT_QUOTES); ?></td>
                        <td class="table-actions">
                            <a class="btn btn--small" href="admin_users.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                            <?php if ($row['id'] != $currentAdminId && $row['role'] !== 'super_admin'): ?>
                                <a class="btn btn--small btn--danger"
                                   href="admin_users.php?delete=<?php echo (int)$row['id']; ?>"
                                   onclick="return confirm('Delete this admin user? This action cannot be undone.');">Delete</a>
                            <?php elseif ($row['id'] == $currentAdminId): ?>
                                <span style="color: var(--muted); font-size: 0.85rem;">Current user</span>
                            <?php elseif ($row['role'] === 'super_admin'): ?>
                                <span style="color: var(--muted); font-size: 0.85rem;">Cannot delete Super Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No admin users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

