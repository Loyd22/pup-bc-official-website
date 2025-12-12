<?php
declare(strict_types=1);

$pageTitle = 'Campus Officials';
$currentSection = 'campus_officials';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$editing = null;
$editingId = null;

// Load for edit
if (isset($_GET['id'])) {
    $officialId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM campus_officials WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $officialId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editing = $result->fetch_assoc();
        $stmt->close();
        
        if (!$editing) {
            add_flash('error', 'Official not found.');
            header('Location: campus_officials.php');
            exit;
        }
        $editingId = $officialId;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Get photo path before deleting
    $stmt = $conn->prepare("SELECT photo_path FROM campus_officials WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $photoPath = $row['photo_path'] ?? null;
        $stmt->close();
        
        // Delete the official
        $stmt = $conn->prepare("DELETE FROM campus_officials WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            
            // Delete photo file if it exists and is in uploads directory
            if (!empty($photoPath) && strpos($photoPath, 'images/uploads/') === 0) {
                $filePath = dirname(__DIR__) . '/' . $photoPath;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            add_flash('success', 'Official deleted successfully.');
        }
    }
    header('Location: campus_officials.php');
    exit;
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $type = $_POST['type'] ?? 'branch_official';
    $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
    
    if ($name === '' || $role === '') {
        add_flash('error', 'Name and role are required.');
        header('Location: campus_officials.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    if (!in_array($type, ['branch_official', 'support_personnel'], true)) {
        $type = 'branch_official';
    }
    
    // Handle image upload
    $photoPath = null;
    if ($id) {
        // Get existing photo path
        $stmt = $conn->prepare("SELECT photo_path FROM campus_officials WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $photoPath = $row['photo_path'] ?? null;
            $stmt->close();
        }
    }
    
    // Handle new image upload
    $imageUpload = handle_file_upload('photo_upload', 'images/uploads', ['png', 'jpg', 'jpeg', 'webp']);
    if (isset($imageUpload['error'])) {
        add_flash('error', $imageUpload['error']);
        header('Location: campus_officials.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    if (isset($imageUpload['path'])) {
        // Delete old photo if it exists and is in uploads directory
        if (!empty($photoPath) && strpos($photoPath, 'images/uploads/') === 0) {
            $oldFilePath = dirname(__DIR__) . '/' . $photoPath;
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $photoPath = $imageUpload['path'];
    }
    
    $stmt = null;
    try {
        if ($id) {
            // UPDATE
            $sql = "UPDATE campus_officials 
                    SET name = ?, role = ?, photo_path = ?, type = ?, display_order = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
            }
            $stmt->bind_param('ssssii', $name, $role, $photoPath, $type, $displayOrder, $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute UPDATE: ' . $stmt->error);
            }
            add_flash('success', 'Official updated successfully.');
        } else {
            // INSERT
            $sql = "INSERT INTO campus_officials (name, role, photo_path, type, display_order)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare INSERT statement: ' . $conn->error);
            }
            $stmt->bind_param('ssssi', $name, $role, $photoPath, $type, $displayOrder);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute INSERT: ' . $stmt->error);
            }
            add_flash('success', 'Official added successfully.');
        }
    } catch (Exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        add_flash('error', 'Unable to save official: ' . $e->getMessage());
        header('Location: campus_officials.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: campus_officials.php');
    exit;
}

// Fetch all officials
$branchOfficials = [];
$supportPersonnel = [];

$result = $conn->query("SELECT * FROM campus_officials ORDER BY type, display_order, name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['type'] === 'branch_official') {
            $branchOfficials[] = $row;
        } else {
            $supportPersonnel[] = $row;
        }
    }
    $result->free();
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2><?php echo $editing ? 'Edit Official' : 'Add New Official'; ?></h2>
    <form method="post" action="campus_officials.php" enctype="multipart/form-data" class="form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>
        
        <label for="type">Type <span style="color: red;">*</span></label>
        <select id="type" name="type" required>
            <option value="branch_official" <?php echo ($editing['type'] ?? 'branch_official') === 'branch_official' ? 'selected' : ''; ?>>Branch Official</option>
            <option value="support_personnel" <?php echo ($editing['type'] ?? 'branch_official') === 'support_personnel' ? 'selected' : ''; ?>>Support Personnel</option>
        </select>
        
        <label for="name">Name <span style="color: red;">*</span></label>
        <input type="text" id="name" name="name" 
               value="<?php echo htmlspecialchars($editing['name'] ?? '', ENT_QUOTES); ?>" required>
        
        <label for="role">Role/Position <span style="color: red;">*</span></label>
        <input type="text" id="role" name="role" 
               value="<?php echo htmlspecialchars($editing['role'] ?? '', ENT_QUOTES); ?>" required>
        
        <div class="form__group--inline">
            <div>
                <label>Current Photo</label>
                <div class="media-preview">
                    <?php if (!empty($editing['photo_path']) && file_exists(dirname(__DIR__) . '/' . $editing['photo_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($editing['photo_path']); ?>" 
                             alt="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" 
                             style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                    <?php else: ?>
                        <span>No photo uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="photo_upload">Upload Photo</label>
                <input type="file" id="photo_upload" name="photo_upload" accept=".png,.jpg,.jpeg,.webp" <?php echo empty($editing) ? 'required' : ''; ?>>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                    Recommended: Square image (e.g., 300x300px or larger)
                </p>
                <?php if ($editing): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                        Leave empty to keep current photo.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <label for="display_order">Display Order</label>
        <input type="number" id="display_order" name="display_order" 
               value="<?php echo (int)($editing['display_order'] ?? 0); ?>" min="0">
        <small style="color: var(--muted);">Lower numbers appear first. Use 0 for default ordering.</small>
        
        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update Official' : 'Add Official'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="campus_officials.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Branch Officials</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($branchOfficials)): ?>
                    <?php foreach ($branchOfficials as $official): ?>
                        <tr>
                            <td>
                                <?php if (!empty($official['photo_path']) && file_exists(dirname(__DIR__) . '/' . $official['photo_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($official['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($official['name']); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <span style="color: var(--muted);">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($official['name']); ?></td>
                            <td><?php echo htmlspecialchars($official['role']); ?></td>
                            <td><?php echo (int)$official['display_order']; ?></td>
                            <td class="table-actions">
                                <a class="btn btn--small" href="campus_officials.php?id=<?php echo (int)$official['id']; ?>">Edit</a>
                                <a class="btn btn--small btn--danger" 
                                   href="campus_officials.php?delete=<?php echo (int)$official['id']; ?>"
                                   onclick="return confirm('Delete this official?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No branch officials added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Support Personnel</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($supportPersonnel)): ?>
                    <?php foreach ($supportPersonnel as $personnel): ?>
                        <tr>
                            <td>
                                <?php if (!empty($personnel['photo_path']) && file_exists(dirname(__DIR__) . '/' . $personnel['photo_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($personnel['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($personnel['name']); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <span style="color: var(--muted);">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($personnel['name']); ?></td>
                            <td><?php echo htmlspecialchars($personnel['role']); ?></td>
                            <td><?php echo (int)$personnel['display_order']; ?></td>
                            <td class="table-actions">
                                <a class="btn btn--small" href="campus_officials.php?id=<?php echo (int)$personnel['id']; ?>">Edit</a>
                                <a class="btn btn--small btn--danger" 
                                   href="campus_officials.php?delete=<?php echo (int)$personnel['id']; ?>"
                                   onclick="return confirm('Delete this personnel?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No support personnel added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

