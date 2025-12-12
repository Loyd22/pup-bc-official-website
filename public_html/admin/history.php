<?php
declare(strict_types=1);

$pageTitle = 'History Images';
$currentSection = 'history';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

// Define history items with their default images and text (text is not editable, only images)
$history_items = [
    [
        'key' => 'history_image_1',
        'description' => 'History Image 1',
        'default_image' => 'images/earlycampusphoto.jpg'
    ],
    [
        'key' => 'history_image_2',
        'description' => 'History Image 2',
        'default_image' => 'images/BEED.png'
    ],
    [
        'key' => 'history_image_3',
        'description' => 'History Image 3',
        'default_image' => 'images/pupsite.jpg'
    ],
    [
        'key' => 'history_image_4',
        'description' => 'History Image 4',
        'default_image' => 'images/direk.png'
    ],
    [
        'key' => 'history_image_5',
        'description' => 'History Image 5',
        'default_image' => 'images/BSIT.png'
    ]
];

// Handle POST requests for updating history images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_history_images') {
    $historyImages = [];
    
    foreach ($history_items as $item) {
        $fieldName = $item['key'];
        $currentImageKey = $item['key'] . '_current';
        
        // Handle file upload
        $upload = handle_file_upload($fieldName, 'images/uploads', ['png', 'jpg', 'jpeg', 'webp']);
        
        if (isset($upload['error'])) {
            add_flash('error', "Error uploading image for {$item['description']}: " . $upload['error']);
            continue;
        }
        
        if (isset($upload['path'])) {
            // New image uploaded - delete old image if it exists in uploads directory
            if (!empty($_POST[$currentImageKey]) && strpos($_POST[$currentImageKey], 'images/uploads/') !== false) {
                $oldPath = dirname(__DIR__) . '/' . $_POST[$currentImageKey];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            // New image uploaded
            $historyImages[$item['key']] = $upload['path'];
        } elseif (!empty($_POST[$currentImageKey])) {
            // Keep existing image
            $historyImages[$item['key']] = $_POST[$currentImageKey];
        }
    }
    
    if (!empty($historyImages)) {
        if (save_settings($conn, $historyImages)) {
            add_flash('success', 'History images updated successfully.');
        } else {
            add_flash('error', 'Unable to save history images.');
        }
    }
    
    header('Location: history.php');
    exit;
}

// Get current history images from settings
$historyImageKeys = array_map(function($item) {
    return $item['key'];
}, $history_items);

$currentImages = get_settings($conn, $historyImageKeys);

require_once __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2>History Page Images</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Manage images displayed on the History page. <strong>Recommended size: 800x600 px (4:3 aspect ratio).</strong> 
        The frame size is fixed and images will automatically adjust to fit using object-fit: cover.
    </p>
    
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="update_history_images">
        
        <?php foreach ($history_items as $index => $item): ?>
            <?php 
            $imageKey = $item['key'];
            $currentImagePath = $currentImages[$imageKey] ?? '';
            $defaultImagePath = $item['default_image'];
            $displayImagePath = !empty($currentImagePath) ? $currentImagePath : $defaultImagePath;
            ?>
            
            <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 24px; background: #fafafa;">
                <h3 style="margin-top: 0; color: var(--maroon); font-size: 16px; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($item['description']); ?>
                </h3>
                
                <div class="form__group--inline">
                    <div>
                        <label>Current Image Preview</label>
                        <div class="media-preview" style="width: 400px; height: 300px; overflow: hidden; border-radius: 8px; border: 1px solid var(--border); background: #fff; display: flex; align-items: center; justify-content: center;">
                            <?php 
                            $fullImagePath = dirname(__DIR__) . '/' . $displayImagePath;
                            if ($displayImagePath && file_exists($fullImagePath)): 
                            ?>
                                <img src="../<?php echo htmlspecialchars($displayImagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($item['description']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span style="color: var(--muted);">No image</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($currentImagePath): ?>
                            <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                                Current: <?php echo htmlspecialchars(basename($currentImagePath)); ?>
                            </p>
                        <?php else: ?>
                            <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                                Using default image
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="<?php echo htmlspecialchars($imageKey); ?>">Upload New Image</label>
                        <input type="file" 
                               id="<?php echo htmlspecialchars($imageKey); ?>" 
                               name="<?php echo htmlspecialchars($imageKey); ?>" 
                               accept=".png,.jpg,.jpeg,.webp">
                        <input type="hidden" 
                               name="<?php echo htmlspecialchars($imageKey); ?>_current" 
                               value="<?php echo htmlspecialchars($currentImagePath); ?>">
                        <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                            <strong>Recommended size:</strong> 800x600 px (4:3 aspect ratio)
                        </p>
                        <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                            Frame size is responsive (max-width: 700px, min-height: 600px on desktop). 
                            Images will be automatically cropped to fit using CSS object-fit: cover.
                        </p>
                        <?php if ($currentImagePath): ?>
                            <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                                Leave empty to keep current image.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <button type="submit" class="btn btn--primary">Save All History Images</button>
    </form>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

