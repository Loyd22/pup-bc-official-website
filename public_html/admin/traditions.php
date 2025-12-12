<?php
declare(strict_types=1);

$pageTitle = 'University Traditions';
$currentSection = 'traditions';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

/**
 * Get images for a tradition from the file system folder
 * Images are stored INSIDE pupbc-website/images/traditions/ (not outside)
 */
function get_tradition_images_from_folder(int $traditionId): array {
    // Admin is in pupbc-website/admin/, so go up 1 level to pupbc-website/
    $baseDir = dirname(__DIR__); // pupbc-website/
    $traditionDir = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'traditions' . DIRECTORY_SEPARATOR . $traditionId;
    
    $images = [];
    if (is_dir($traditionDir)) {
        $files = scandir($traditionDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                // Path relative to admin panel (for preview): ../images/traditions/{id}/{file}
                // This path works from admin/ directory (goes up 1 level to pupbc-website/)
                $images[] = '../images/traditions/' . $traditionId . '/' . $file;
            }
        }
        // Sort by filename for consistent order
        sort($images);
    }
    return $images;
}

/**
 * Delete a specific image file for a tradition
 * Images are stored INSIDE pupbc-website/images/traditions/ (not outside)
 */
function delete_tradition_image(int $traditionId, string $filename): bool {
    $baseDir = dirname(__DIR__); // pupbc-website/
    $imagePath = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'traditions' . DIRECTORY_SEPARATOR . $traditionId . DIRECTORY_SEPARATOR . $filename;
    
    if (file_exists($imagePath)) {
        return @unlink($imagePath);
    }
    return true;
}

$currentAdminId = get_current_admin_id($conn);
$editing = null;

// Load for edit
if (isset($_GET['id'])) {
    $traditionId = (int)$_GET['id'];
    $editing = fetch_tradition_item($conn, $traditionId);
    if (!$editing) {
        add_flash('error', 'Tradition not found.');
        header('Location: traditions.php');
        exit;
    }
    // Get images from file system folder (NOT from database)
    $editing['images'] = get_tradition_images_from_folder($traditionId);
}

function fetch_tradition_item(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM traditions WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row ?: null;
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM traditions WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
        add_flash('success', 'Tradition deleted.');
    }
    header('Location: traditions.php');
    exit;
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $subtitle = trim($_POST['subtitle'] ?? '');
    $title = trim($_POST['title'] ?? '');
    // Handle display_order: always save as integer, default to 0 when empty
    // Lower numbers appear first (top), higher numbers appear later (bottom)
    $displayOrderInput = trim($_POST['display_order'] ?? '');
    $displayOrder = ($displayOrderInput === '' || $displayOrderInput === null) ? 0 : max(0, (int)$displayOrderInput);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($subtitle === '' || $title === '') {
        add_flash('error', 'Subtitle and title are required.');
        header('Location: traditions.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    // First, save/update the tradition record (ONLY text fields - NO images in database)
    $stmt = null;
    $traditionId = $id;
    try {
        // Check if images column exists in database
        $checkColumn = $conn->query("SHOW COLUMNS FROM traditions LIKE 'images'");
        $hasImagesColumn = $checkColumn && $checkColumn->num_rows > 0;
        if ($checkColumn) $checkColumn->free();
        
        if ($id) {
            // UPDATE - Only text fields, NO images
            if ($hasImagesColumn) {
                // Table has images column - set it to empty JSON
                $sql = "UPDATE traditions 
                        SET subtitle = ?, title = ?, images = '[]', display_order = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('ssiii', $subtitle, $title, $displayOrder, $isActive, $id);
            } else {
                // Table doesn't have images column
                $sql = "UPDATE traditions 
                        SET subtitle = ?, title = ?, display_order = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare UPDATE statement: ' . $conn->error);
                }
                $stmt->bind_param('ssiii', $subtitle, $title, $displayOrder, $isActive, $id);
            }
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute UPDATE: ' . $stmt->error);
            }
            $traditionId = $id;
            add_flash('success', 'Tradition updated.');
        } else {
            // INSERT first to get the ID
            if ($hasImagesColumn) {
                $sql = "INSERT INTO traditions (subtitle, title, images, display_order, is_active)
                        VALUES (?, ?, '[]', ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare INSERT statement: ' . $conn->error);
                }
                $stmt->bind_param('ssii', $subtitle, $title, $displayOrder, $isActive);
            } else {
                $sql = "INSERT INTO traditions (subtitle, title, display_order, is_active)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare INSERT statement: ' . $conn->error);
                }
                $stmt->bind_param('ssii', $subtitle, $title, $displayOrder, $isActive);
            }
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute INSERT: ' . $stmt->error);
            }
            $traditionId = $conn->insert_id;
            add_flash('success', 'Tradition created.');
        }
    } catch (Exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        error_log('Tradition save error: ' . $e->getMessage());
        add_flash('error', 'Unable to save the tradition: ' . htmlspecialchars($e->getMessage()));
        header('Location: traditions.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    if ($stmt instanceof mysqli_stmt) $stmt->close();
    
    // Handle existing images removal (if updating)
    if ($id) {
        $existingImages = get_tradition_images_from_folder($id);
        $keptImages = isset($_POST['existing_images']) && is_array($_POST['existing_images']) 
            ? $_POST['existing_images'] 
            : [];
        
        // Delete images that were removed
        foreach ($existingImages as $img) {
            if (!in_array($img, $keptImages)) {
                delete_tradition_image($id, basename($img));
            }
        }
    }
    
    // Now handle NEW file uploads - store images in folders (NOT in database)
    $hasNewUploads = false;
    $uploadedCount = 0;
    
    if (isset($_FILES['tradition_images']) && is_array($_FILES['tradition_images']['name'])) {
        $fileCount = count($_FILES['tradition_images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            // Skip if no file was uploaded for this field
            if ($_FILES['tradition_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $hasNewUploads = true;
            
            // Validate file error
            if ($_FILES['tradition_images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue; // Skip this file but continue with others
            }
            
            // Validate extension
            $extension = strtolower(pathinfo($_FILES['tradition_images']['name'][$i], PATHINFO_EXTENSION));
            $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
            if (!in_array($extension, $allowedExtensions, true)) {
                continue; // Skip invalid files
            }
            
            // Validate file size (5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($_FILES['tradition_images']['size'][$i] > $maxSize) {
                continue; // Skip oversized files
            }
            
            // Prepare upload directory: pupbc-website/images/traditions/{tradition_id}/
            // Store INSIDE pupbc-website/ so it's included when uploading to Hostinger
            $baseDir = dirname(__DIR__); // pupbc-website/
            $targetDir = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'traditions' . DIRECTORY_SEPARATOR . $traditionId;
            
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                continue; // Skip if can't create directory
            }
            
            // Generate unique filename
            $filename = uniqid('img_', true) . '.' . $extension;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
            
            // Move uploaded file to tradition-specific folder (NOT database)
            if (move_uploaded_file($_FILES['tradition_images']['tmp_name'][$i], $targetPath)) {
                $uploadedCount++;
                @chmod($targetPath, 0644);
            }
        }
        
        if ($uploadedCount > 0) {
            add_flash('success', $uploadedCount . ' image(s) uploaded successfully.');
        }
    }
    
    // Count total images (existing + newly uploaded)
    $existingImages = [];
    if ($id) {
        $existingImages = get_tradition_images_from_folder($traditionId);
    }
    
    // Count kept existing images
    $keptExistingImages = [];
    if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
        $keptExistingImages = array_filter(array_map('trim', $_POST['existing_images']), function($path) {
            return $path !== '';
        });
    }
    
    // Count newly uploaded images
    $newlyUploadedCount = 0;
    if (isset($_FILES['tradition_images']) && is_array($_FILES['tradition_images']['name'])) {
        foreach ($_FILES['tradition_images']['name'] as $i => $name) {
            if ($_FILES['tradition_images']['error'][$i] === UPLOAD_ERR_OK && !empty($name)) {
                $newlyUploadedCount++;
            }
        }
    }
    
    $totalImageCount = count($keptExistingImages) + $newlyUploadedCount;
    
    // Require minimum of 3 images
    if ($totalImageCount < 3) {
        if (!$id) {
            // Delete the tradition record if not enough images
            if (isset($traditionId) && $traditionId) {
                $stmt = $conn->prepare("DELETE FROM traditions WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $traditionId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            add_flash('error', 'Minimum of 3 images is required. You currently have ' . $totalImageCount . ' image(s). Please upload more images.');
        } else {
            add_flash('error', 'Minimum of 3 images is required. You currently have ' . $totalImageCount . ' image(s). Please upload more images or keep existing ones.');
        }
        header('Location: traditions.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    header('Location: traditions.php');
    exit;
}

// List items
// Order by display_order: lower numbers appear first (top), higher numbers appear later (bottom)
// NULL values are treated as 999999 so they appear at the bottom
// Use CAST to ensure numeric sorting
$traditionItems = $conn->query("
    SELECT id, subtitle, title, display_order, is_active, updated_at
    FROM traditions
    ORDER BY COALESCE(CAST(display_order AS UNSIGNED), 999999) ASC, id ASC
");

require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2><?php echo $editing ? 'Edit Tradition' : 'New Tradition'; ?></h2>
    <form method="post" action="traditions.php" class="form" id="traditionForm" enctype="multipart/form-data">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>

        <label for="subtitle">Subtitle</label>
        <input type="text" id="subtitle" name="subtitle"
               value="<?php echo htmlspecialchars($editing['subtitle'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., Freshmen Orientation" required>

        <label for="title">Title</label>
        <input type="text" id="title" name="title"
               value="<?php echo htmlspecialchars($editing['title'] ?? '', ENT_QUOTES); ?>" 
               placeholder="e.g., The Iskolar ng Bayan Welcome" required>

        <label>Images (at least one required)</label>
        <div id="imagesContainer">
            <?php 
            $existingImages = $editing['images'] ?? [];
            $imageCount = max(count($existingImages), 1);
            
            // Show existing images with previews
            foreach ($existingImages as $index => $image): 
                $imagePath = str_replace('../', '', $image);
            ?>
                <div class="image-upload-item" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb;">
                    <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image, ENT_QUOTES); ?>">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <img src="../<?php echo htmlspecialchars($imagePath, ENT_QUOTES); ?>" 
                                 alt="Preview" 
                                 style="width: 120px; height: 80px; object-fit: cover; border-radius: 0.25rem; border: 1px solid #d1d5db;">
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                                Existing image: <?php echo htmlspecialchars(basename($imagePath), ENT_QUOTES); ?>
                            </div>
                            <button type="button" class="btn btn--small btn--danger" onclick="removeExistingImage(this)">
                                Remove Image
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- New upload fields - Fixed to 3 fields only -->
            <div id="newUploadsContainer">
                <?php
                for ($i = 1; $i <= 3; $i++):
                ?>
                <div class="image-upload-item" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Upload New Image <?php echo $i; ?></label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="file" name="tradition_images[]" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" class="image-upload-input" style="flex: 1;" onchange="handleImagePreview(this)">
                    </div>
                    <div class="image-preview-container" style="display: none; margin-top: 0.75rem;">
                        <img class="image-preview-thumbnail" src="" alt="Preview" style="max-width: 160px; border-radius: 0.5rem; border: 1px solid #d1d5db; display: block;">
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
            Upload images (PNG, JPG, JPEG, WEBP, GIF). Maximum file size: 5MB per image. <strong style="color: #dc2626;">Minimum of 3 images required.</strong>
        </small>

        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
            <label class="checkbox">
                <input type="checkbox" name="is_active" <?php
                    echo isset($editing['is_active'])
                        ? ($editing['is_active'] ? 'checked' : '')
                        : 'checked'; ?>>
                <span>Active</span>
            </label>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="display_order" style="font-size: 0.875rem; color: #6b7280; margin: 0;">Display order (lower = top, higher = bottom)</label>
                <input type="number" id="display_order" name="display_order"
                       value="<?php echo isset($editing['display_order']) ? (int)$editing['display_order'] : ''; ?>" 
                       min="0" placeholder="0"
                       style="width: 100px; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; border-radius: 1.5rem; background: #f9fafb; font-size: 0.875rem; text-align: center;">
                <small style="font-size: 0.75rem; color: #9ca3af;">e.g., 1 = top, 10 = bottom</small>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update Tradition' : 'Create Tradition'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="traditions.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Existing Traditions</h2>
    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>Order</th>
                <th>Subtitle</th>
                <th>Title</th>
                <th>Images</th>
                <th>Status</th>
                <th>Updated</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($traditionItems && $traditionItems->num_rows > 0): ?>
                <?php while ($row = $traditionItems->fetch_assoc()): 
                    // Get images from file system folder (NOT from database)
                    $images = get_tradition_images_from_folder((int)$row['id']);
                ?>
                    <tr>
                        <td><?php echo (int)$row['display_order']; ?></td>
                        <td><?php echo htmlspecialchars($row['subtitle'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
                        <td>
                            <small><?php echo count($images); ?> image(s)</small>
                        </td>
                        <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['updated_at'])), ENT_QUOTES); ?></td>
                        <td class="table-actions">
                            <a class="btn btn--small" href="traditions.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                            <a class="btn btn--small btn--danger"
                               href="traditions.php?delete=<?php echo (int)$row['id']; ?>"
                               onclick="return confirm('Delete this tradition?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No traditions created yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
// Dynamic field addition/removal functions removed - only 3 fixed fields allowed

function removeExistingImage(btn) {
    if (confirm('Remove this image? It will be deleted when you save the form.')) {
        btn.closest('.image-upload-item').remove();
    }
}

function handleImagePreview(input) {
    const file = input.files && input.files[0];
    const container = input.closest('.image-upload-item').querySelector('.image-preview-container');
    const thumbnail = container ? container.querySelector('.image-preview-thumbnail') : null;
    
    if (!container || !thumbnail) return;
    
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumbnail.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        container.style.display = 'none';
        thumbnail.src = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('traditionForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const subtitle = document.getElementById('subtitle');
        const title = document.getElementById('title');
        
        // Check for existing images
        const existingImages = form.querySelectorAll('input[name="existing_images[]"]');
        // Check for new file uploads
        const fileInputs = form.querySelectorAll('input[name="tradition_images[]"]');
        const hasFiles = Array.from(fileInputs).some(input => input.files && input.files.length > 0);
        const hasExisting = existingImages.length > 0;
        
        // Count total images (existing + new uploads)
        let totalImageCount = existingImages.length;
        fileInputs.forEach(input => {
            if (input.files && input.files.length > 0) {
                totalImageCount++;
            }
        });
        
        if (!subtitle.value.trim()) {
            e.preventDefault();
            alert('Subtitle is required.');
            subtitle.focus();
            return false;
        }
        
        if (!title.value.trim()) {
            e.preventDefault();
            alert('Title is required.');
            title.focus();
            return false;
        }
        
        // Require minimum of 3 images
        if (totalImageCount < 3) {
            e.preventDefault();
            alert('Minimum of 3 images is required. You currently have ' + totalImageCount + ' image(s). Please upload more images or keep existing ones.');
            return false;
        }
        
        // Validate file sizes (5MB limit)
        let hasLargeFile = false;
        fileInputs.forEach(function(input) {
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    hasLargeFile = true;
                }
            }
        });
        
        if (hasLargeFile) {
            e.preventDefault();
            alert('One or more images exceed the 5MB size limit.');
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';

