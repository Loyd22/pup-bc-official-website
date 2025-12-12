<?php
declare(strict_types=1);

$pageTitle = 'Campus Offices';
$currentSection = 'campus_offices';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$editing = null;
$editingId = null;
$editingGallery = null;
$editingGalleryId = null;
$campusVideoUrl = trim(get_setting($conn, 'campus_offices_video_url', ''));

// Save campus offices video URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['campus_video_action'] ?? '') === 'save') {
    $campusVideoUrl = trim($_POST['campus_video_url'] ?? '');
    $saved = set_setting($conn, 'campus_offices_video_url', $campusVideoUrl);

    if ($saved) {
        add_flash('success', 'Campus offices video updated successfully.');
    } else {
        add_flash('error', 'Unable to save the campus offices video. Please try again.');
    }

    header('Location: campus_offices.php');
    exit;
}

// Gallery: Load for edit
if (isset($_GET['gallery_id'])) {
    $galleryId = (int)$_GET['gallery_id'];
    $stmt = $conn->prepare("SELECT * FROM campus_gallery WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $galleryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editingGallery = $result->fetch_assoc();
        $stmt->close();
        
        if (!$editingGallery) {
            add_flash('error', 'Gallery image not found.');
            header('Location: campus_offices.php');
            exit;
        }
        $editingGalleryId = $galleryId;
    }
}

// Gallery: Delete
if (isset($_GET['delete_gallery'])) {
    $deleteGalleryId = (int)$_GET['delete_gallery'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM campus_gallery WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteGalleryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $imagePath = $row['image_path'] ?? null;
        $stmt->close();
        
        // Delete the gallery image
        $stmt = $conn->prepare("DELETE FROM campus_gallery WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteGalleryId);
            $stmt->execute();
            $stmt->close();
            
            // Delete image file if it exists and is in uploads directory
            if (!empty($imagePath) && strpos($imagePath, 'images/uploads/') !== false) {
                $filePath = dirname(__DIR__) . '/' . str_replace('../', '', $imagePath);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            add_flash('success', 'Gallery image deleted successfully.');
        }
    }
    header('Location: campus_offices.php');
    exit;
}

// Gallery: Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gallery_action'])) {
    $galleryId = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : null;
    $altText = trim($_POST['gallery_alt_text'] ?? '');
    $sizeClass = $_POST['gallery_size_class'] ?? 'regular';
    $displayOrder = isset($_POST['gallery_display_order']) ? (int)$_POST['gallery_display_order'] : 0;
    
    // Validate size class
    if (!in_array($sizeClass, ['regular', 'large', 'tall', 'wide'], true)) {
        $sizeClass = 'regular';
    }
    
    // Check image limit (20 max)
    if (!$galleryId) {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM campus_gallery");
        if ($countStmt) {
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalImages = (int)$countRow['total'];
            $countStmt->close();
            
            if ($totalImages >= 20) {
                add_flash('error', 'Maximum of 20 gallery images allowed. Please delete an image before adding a new one.');
                header('Location: campus_offices.php');
                exit;
            }
        }
    }
    
    // Handle image upload
    $imagePath = null;
    if ($galleryId) {
        // Get existing image path
        $stmt = $conn->prepare("SELECT image_path FROM campus_gallery WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $galleryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $imagePath = $row['image_path'] ?? null;
            $stmt->close();
        }
    }
    
    if (isset($_FILES['gallery_image_upload']) && $_FILES['gallery_image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['gallery_image_upload']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($fileExt, $allowedExts, true)) {
            add_flash('error', 'Invalid file type. Only JPG, PNG, and WEBP are allowed.');
            header('Location: campus_offices.php' . ($galleryId ? '?gallery_id='.$galleryId : ''));
            exit;
        }
        
        $fileName = 'gallery_' . time() . '_' . uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['gallery_image_upload']['tmp_name'], $targetPath)) {
            // Delete old image if exists and is in uploads directory
            if (!empty($imagePath) && strpos($imagePath, 'images/uploads/') !== false) {
                $oldPath = dirname(__DIR__) . '/' . str_replace('../', '', $imagePath);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $imagePath = '../images/uploads/' . $fileName;
        } else {
            add_flash('error', 'Failed to upload image.');
            header('Location: campus_offices.php' . ($galleryId ? '?gallery_id='.$galleryId : ''));
            exit;
        }
    }
    
    // If no image uploaded and creating new gallery item, require image
    if (!$imagePath && !$galleryId) {
        add_flash('error', 'Image is required when creating a new gallery item.');
        header('Location: campus_offices.php');
        exit;
    }
    
    $stmt = null;
    try {
        if ($galleryId) {
            // UPDATE
            if ($imagePath) {
                $sql = "UPDATE campus_gallery 
                        SET image_path = ?, alt_text = ?, size_class = ?, display_order = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('sssii', $imagePath, $altText, $sizeClass, $displayOrder, $galleryId);
            } else {
                $sql = "UPDATE campus_gallery 
                        SET alt_text = ?, size_class = ?, display_order = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('ssii', $altText, $sizeClass, $displayOrder, $galleryId);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute UPDATE: ' . $stmt->error);
            }
            add_flash('success', 'Gallery image updated successfully.');
        } else {
            // INSERT
            $sql = "INSERT INTO campus_gallery (image_path, alt_text, size_class, display_order)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare INSERT statement: ' . $conn->error);
            }
            $stmt->bind_param('sssi', $imagePath, $altText, $sizeClass, $displayOrder);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute INSERT: ' . $stmt->error);
            }
            add_flash('success', 'Gallery image added successfully.');
        }
    } catch (Exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        add_flash('error', 'Unable to save gallery image: ' . $e->getMessage());
        header('Location: campus_offices.php' . ($galleryId ? '?gallery_id='.$galleryId : ''));
        exit;
    }
    
    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: campus_offices.php');
    exit;
}

// Load for edit
if (isset($_GET['id'])) {
    $officeId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM campus_offices WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $officeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editing = $result->fetch_assoc();
        $stmt->close();
        
        if (!$editing) {
            add_flash('error', 'Office not found.');
            header('Location: campus_offices.php');
            exit;
        }
        $editingId = $officeId;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image FROM campus_offices WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $imagePath = $row['image'] ?? null;
        $stmt->close();
        
        // Delete the office
        $stmt = $conn->prepare("DELETE FROM campus_offices WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            
            // Delete image file if it exists and is in uploads directory
            if (!empty($imagePath) && strpos($imagePath, 'images/uploads/') !== false) {
                $filePath = dirname(__DIR__) . '/' . $imagePath;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            add_flash('success', 'Office deleted successfully.');
        }
    }
    header('Location: campus_offices.php');
    exit;
}

// Create/Update Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['gallery_action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $category = trim($_POST['category'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
    
    if ($category === '' || $tag === '' || $name === '' || $description === '' || $location === '' || $hours === '') {
        add_flash('error', 'All fields are required.');
        header('Location: campus_offices.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    // Handle image upload
    $imagePath = null;
    if ($id) {
        // Get existing image path
        $stmt = $conn->prepare("SELECT image FROM campus_offices WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $imagePath = $row['image'] ?? null;
            $stmt->close();
        }
    }
    
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($fileExt, $allowedExts, true)) {
            add_flash('error', 'Invalid file type. Only JPG, PNG, and WEBP are allowed.');
            header('Location: campus_offices.php' . ($id ? '?id='.$id : ''));
            exit;
        }
        
        $fileName = 'office_' . time() . '_' . uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $targetPath)) {
            // Delete old image if exists and is in uploads directory
            if (!empty($imagePath) && strpos($imagePath, 'images/uploads/') !== false) {
                $oldPath = dirname(__DIR__) . '/' . $imagePath;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $imagePath = 'images/uploads/' . $fileName;
        } else {
            add_flash('error', 'Failed to upload image.');
            header('Location: campus_offices.php' . ($id ? '?id='.$id : ''));
            exit;
        }
    }
    
    // If no image uploaded and creating new office, use default
    if (!$imagePath && !$id) {
        $imagePath = '../images/offices.jpg';
    }
    
    $stmt = null;
    try {
        if ($id) {
            // UPDATE
            if ($imagePath) {
                $sql = "UPDATE campus_offices 
                        SET category = ?, tag = ?, name = ?, description = ?, location = ?, hours = ?, image = ?, display_order = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('sssssssii', $category, $tag, $name, $description, $location, $hours, $imagePath, $displayOrder, $id);
            } else {
                $sql = "UPDATE campus_offices 
                        SET category = ?, tag = ?, name = ?, description = ?, location = ?, hours = ?, display_order = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('ssssssii', $category, $tag, $name, $description, $location, $hours, $displayOrder, $id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute UPDATE: ' . $stmt->error);
            }
            add_flash('success', 'Office updated successfully.');
        } else {
            // INSERT
            $sql = "INSERT INTO campus_offices (category, tag, name, description, location, hours, image, display_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare INSERT statement: ' . $conn->error);
            }
            $stmt->bind_param('sssssssi', $category, $tag, $name, $description, $location, $hours, $imagePath, $displayOrder);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute INSERT: ' . $stmt->error);
            }
            add_flash('success', 'Office added successfully.');
        }
    } catch (Exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        add_flash('error', 'Unable to save office: ' . $e->getMessage());
        header('Location: campus_offices.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: campus_offices.php');
    exit;
}

// Fetch all offices
$offices = [];
$result = $conn->query("SELECT * FROM campus_offices ORDER BY display_order, category, name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
    $result->free();
}

// Fetch all gallery images
$galleryImages = [];
$galleryResult = $conn->query("SELECT * FROM campus_gallery ORDER BY display_order, id");
if ($galleryResult) {
    while ($row = $galleryResult->fetch_assoc()) {
        $galleryImages[] = $row;
    }
    $galleryResult->free();
}

// Get gallery count
$galleryCount = count($galleryImages);

require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$editingGallery): ?>
<section class="card">
    <h2><?php echo $editing ? 'Edit Office' : 'Add New Office'; ?></h2>
    <?php if ($editing): ?>
        <a class="btn btn--secondary" href="campus_offices.php" style="float: right; margin-top: -2.5rem;">Cancel Edit</a>
    <?php endif; ?>
    <form method="post" action="campus_offices.php" enctype="multipart/form-data" class="form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>
        
        <label for="category">Category <span style="color: red;">*</span></label>
        <input type="text" id="category" name="category" 
               value="<?php echo htmlspecialchars($editing['category'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., Admissions & Registrar" required>
        
        <label for="tag">Tag <span style="color: red;">*</span></label>
        <input type="text" id="tag" name="tag" 
               value="<?php echo htmlspecialchars($editing['tag'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., Admissions, Financial Aid, Support" required>
        <small style="color: var(--muted);">Short label displayed on the office card badge.</small>
        
        <label for="name">Office Name <span style="color: red;">*</span></label>
        <input type="text" id="name" name="name" 
               value="<?php echo htmlspecialchars($editing['name'] ?? '', ENT_QUOTES); ?>" required>
        
        <label for="description">Description <span style="color: red;">*</span></label>
        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($editing['description'] ?? '', ENT_QUOTES); ?></textarea>
        
        <label for="location">Location <span style="color: red;">*</span></label>
        <input type="text" id="location" name="location" 
               value="<?php echo htmlspecialchars($editing['location'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., Ground Floor, Main Building" required>
        
        <label for="hours">Operating Hours <span style="color: red;">*</span></label>
        <textarea id="hours" name="hours" rows="2" required><?php echo htmlspecialchars($editing['hours'] ?? '', ENT_QUOTES); ?></textarea>
        <small style="color: var(--muted);">You can use HTML line breaks (&lt;br&gt;) for multiple lines.</small>
        
        <div class="form__group--inline">
            <div>
                <label>Current Image</label>
                <div class="media-preview">
                    <?php if (!empty($editing['image']) && file_exists(dirname(__DIR__) . '/' . str_replace('../', '', $editing['image']))): ?>
                        <img src="../<?php echo htmlspecialchars(str_replace('../', '', $editing['image'])); ?>" 
                             alt="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" 
                             style="max-width: 300px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                    <?php else: ?>
                        <span>No image uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="image_upload">Upload Image</label>
                <input type="file" id="image_upload" name="image_upload" accept=".png,.jpg,.jpeg,.webp" <?php echo empty($editing) ? 'required' : ''; ?>>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                    Recommended: Landscape image (e.g., 800x600px or larger)
                </p>
                <?php if ($editing): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                        Leave empty to keep current image.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <label for="display_order">Display Order</label>
        <input type="number" id="display_order" name="display_order" 
               value="<?php echo (int)($editing['display_order'] ?? 0); ?>" min="0">
        <small style="color: var(--muted);">Lower numbers appear first. Use 0 for default ordering.</small>
        
        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update Office' : 'Add Office'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="campus_offices.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>
<?php endif; ?>

<?php if (!$editingGallery): ?>
<section class="card">
    <h2>All Offices</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Category</th>
                    <th>Tag</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offices)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted);">No offices found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($offices as $office): ?>
                        <tr>
                            <td><?php echo (int)$office['display_order']; ?></td>
                            <td><?php echo htmlspecialchars($office['category'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($office['tag'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($office['name'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($office['location'], ENT_QUOTES); ?></td>
                            <td>
                                <a href="campus_offices.php?id=<?php echo (int)$office['id']; ?>" class="btn btn--small btn--secondary">Edit</a>
                                <a href="campus_offices.php?delete=<?php echo (int)$office['id']; ?>" 
                                   class="btn btn--small btn--danger" 
                                   onclick="return confirm('Are you sure you want to delete this office?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section class="card">
    <h2>Campus Offices Video</h2>
    <form method="post" action="campus_offices.php" class="form">
        <input type="hidden" name="campus_video_action" value="save">

        <label for="campus_video_url">YouTube Video URL</label>
        <input type="url"
               id="campus_video_url"
               name="campus_video_url"
               value="<?php echo htmlspecialchars($campusVideoUrl, ENT_QUOTES); ?>"
               placeholder="https://www.youtube.com/watch?v=...">
        <small style="color: var(--muted);">Supports YouTube watch or shorts links. Leave blank to remove the video.</small>

        <button type="submit" class="btn btn--primary" style="margin-top: 1rem;">Save Video</button>
    </form>
</section>

<!-- Gallery Management Section -->
<section class="card">
    <h2><?php echo $editingGallery ? 'Edit Gallery Image' : 'Add Gallery Image'; ?></h2>
    <?php if ($editingGallery): ?>
        <a class="btn btn--secondary" href="campus_offices.php" style="float: right; margin-top: -2.5rem;">Cancel Edit</a>
    <?php endif; ?>
    <?php if ($galleryCount >= 20 && !$editingGallery): ?>
        <div class="alert alert--error">
            Maximum of 20 gallery images reached. Please delete an image before adding a new one.
        </div>
    <?php endif; ?>
    <form method="post" action="campus_offices.php" enctype="multipart/form-data" class="form">
        <input type="hidden" name="gallery_action" value="save">
        <?php if ($editingGallery): ?>
            <input type="hidden" name="gallery_id" value="<?php echo (int)$editingGallery['id']; ?>">
        <?php endif; ?>
        
        <label for="gallery_image_upload">Image <span style="color: red;">*</span></label>
        <div class="form__group--inline">
            <div>
                <label>Current Image</label>
                <div class="media-preview">
                    <?php if (!empty($editingGallery['image_path']) && file_exists(dirname(__DIR__) . '/' . str_replace('../', '', $editingGallery['image_path']))): ?>
                        <img src="../<?php echo htmlspecialchars(str_replace('../', '', $editingGallery['image_path'])); ?>" 
                             alt="<?php echo htmlspecialchars($editingGallery['alt_text'] ?? ''); ?>" 
                             style="max-width: 300px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                    <?php else: ?>
                        <span>No image uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <input type="file" id="gallery_image_upload" name="gallery_image_upload" accept=".png,.jpg,.jpeg,.webp" <?php echo (empty($editingGallery) && $galleryCount < 20) ? 'required' : ''; ?>>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                    Recommended: Landscape image (e.g., 800x600px or larger)
                </p>
                <?php if ($editingGallery): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                        Leave empty to keep current image.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <label for="gallery_alt_text">Alt Text</label>
        <input type="text" id="gallery_alt_text" name="gallery_alt_text" 
               value="<?php echo htmlspecialchars($editingGallery['alt_text'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., PUP Biñan Campus Buildings">
        <small style="color: var(--muted);">Description for accessibility and SEO.</small>
        
        <label for="gallery_size_class">Size Class <span style="color: red;">*</span></label>
        <select id="gallery_size_class" name="gallery_size_class" required>
            <option value="regular" <?php echo ($editingGallery['size_class'] ?? 'regular') === 'regular' ? 'selected' : ''; ?>>Regular (1×1)</option>
            <option value="large" <?php echo ($editingGallery['size_class'] ?? '') === 'large' ? 'selected' : ''; ?>>Large (2×2)</option>
            <option value="tall" <?php echo ($editingGallery['size_class'] ?? '') === 'tall' ? 'selected' : ''; ?>>Tall (1×2)</option>
            <option value="wide" <?php echo ($editingGallery['size_class'] ?? '') === 'wide' ? 'selected' : ''; ?>>Wide (2×1)</option>
        </select>
        <small style="color: var(--muted);">Controls how the image appears in the gallery grid layout.</small>
        
        <label for="gallery_display_order">Display Order</label>
        <input type="number" id="gallery_display_order" name="gallery_display_order" 
               value="<?php echo (int)($editingGallery['display_order'] ?? 0); ?>" min="0">
        <small style="color: var(--muted);">Lower numbers appear first. Use 0 for default ordering.</small>
        
        <button type="submit" class="btn btn--primary" <?php echo (!$editingGallery && $galleryCount >= 20) ? 'disabled' : ''; ?>>
            <?php echo $editingGallery ? 'Update Gallery Image' : 'Add Gallery Image'; ?>
        </button>
        <?php if ($editingGallery): ?>
            <a class="btn btn--secondary" href="campus_offices.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<?php if (!$editingGallery): ?>
<section class="card">
    <h2>Gallery Images (<?php echo $galleryCount; ?>/20)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Preview</th>
                    <th>Alt Text</th>
                    <th>Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($galleryImages)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted);">No gallery images found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($galleryImages as $gallery): ?>
                        <tr>
                            <td><?php echo (int)$gallery['display_order']; ?></td>
                            <td>
                                <?php if (!empty($gallery['image_path']) && file_exists(dirname(__DIR__) . '/' . str_replace('../', '', $gallery['image_path']))): ?>
                                    <img src="../<?php echo htmlspecialchars(str_replace('../', '', $gallery['image_path'])); ?>" 
                                         alt="<?php echo htmlspecialchars($gallery['alt_text'] ?? ''); ?>" 
                                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <span style="color: var(--muted);">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($gallery['alt_text'] ?? 'N/A', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($gallery['size_class']), ENT_QUOTES); ?></td>
                            <td>
                                <a href="campus_offices.php?gallery_id=<?php echo (int)$gallery['id']; ?>" class="btn btn--small btn--secondary">Edit</a>
                                <a href="campus_offices.php?delete_gallery=<?php echo (int)$gallery['id']; ?>" 
                                   class="btn btn--small btn--danger" 
                                   onclick="return confirm('Are you sure you want to delete this gallery image?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
