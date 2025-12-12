<?php
declare(strict_types=1);

$pageTitle = 'Academic Programs';
$currentSection = 'programs';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

// Define all programs with their keys and display names
$programs = [
    'BSBA' => [
        'name' => 'Bachelor of Science in Business Administration Major in Human Resource Management',
        'short' => 'BSBA',
        'default_image' => 'images/hrss1.jpg'
    ],
    'BSIT' => [
        'name' => 'Bachelor of Science in Information Technology',
        'short' => 'BSIT',
        'default_image' => 'images/ibits1.jpg'
    ],
    'BSEDEnglish' => [
        'name' => 'Bachelor of Secondary Education Major in English',
        'short' => 'BSED English',
        'default_image' => 'images/educ.png'
    ],
    'BSEDSocialStudies' => [
        'name' => 'Bachelor of Secondary Education Major in Social Studies',
        'short' => 'BSED Social Studies',
        'default_image' => 'images/BSEDSS.png'
    ],
    'BEED' => [
        'name' => 'Bachelor of Elementary Education',
        'short' => 'BEED',
        'default_image' => 'images/educ1.jpg'
    ],
    'BSCPE' => [
        'name' => 'Bachelor of Science in Computer Engineering',
        'short' => 'BSCPE',
        'default_image' => 'images/aces1.jpg'
    ],
    'BSIE' => [
        'name' => 'Bachelor of Science in Industrial Engineering',
        'short' => 'BSIE',
        'default_image' => 'images/piie1.jpg'
    ],
    'BSPsychology' => [
        'name' => 'Bachelor of Science in Psychology',
        'short' => 'BS Psychology',
        'default_image' => 'images/psych1.jpg'
    ],
    'DCET' => [
        'name' => 'Diploma in Computer Engineering Technology',
        'short' => 'DCET',
        'default_image' => 'images/pupbackrgound.jpg'
    ],
    'DIT' => [
        'name' => 'Diploma in Information Technology',
        'short' => 'DIT',
        'default_image' => 'images/pupbackrgound.jpg'
    ]
];

// Handle POST requests for updating program images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_program_images') {
    $programImages = [];
    
    foreach ($programs as $key => $program) {
        $fieldName = 'program_image_' . $key;
        $currentImageKey = 'program_image_' . $key . '_current';
        
        // Handle file upload
        $upload = handle_file_upload($fieldName, 'images/uploads', ['png', 'jpg', 'jpeg', 'webp']);
        
        if (isset($upload['error'])) {
            add_flash('error', "Error uploading image for {$program['short']}: " . $upload['error']);
            continue;
        }
        
        if (isset($upload['path'])) {
            // New image uploaded
            $programImages['program_image_' . $key] = $upload['path'];
        } elseif (!empty($_POST[$currentImageKey])) {
            // Keep existing image
            $programImages['program_image_' . $key] = $_POST[$currentImageKey];
        }
    }
    
    if (!empty($programImages)) {
        if (save_settings($conn, $programImages)) {
            add_flash('success', 'Program images updated successfully.');
        } else {
            add_flash('error', 'Unable to save program images.');
        }
    }
    
    header('Location: programs.php');
    exit;
}

// Get current program images from settings
$programImageKeys = array_map(function($key) {
    return 'program_image_' . $key;
}, array_keys($programs));

$currentImages = get_settings($conn, $programImageKeys);

require_once __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2>Academic Program Images</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Manage images for each academic program displayed on the Programs page. 
        <strong>Recommended size: 500x300 px.</strong> The frame size will remain fixed regardless of uploaded image dimensions.
    </p>
    
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="update_program_images">
        
        <?php foreach ($programs as $key => $program): ?>
            <?php 
            $imageKey = 'program_image_' . $key;
            $currentImagePath = $currentImages[$imageKey] ?? '';
            $defaultImagePath = $program['default_image'];
            $displayImagePath = !empty($currentImagePath) ? $currentImagePath : $defaultImagePath;
            ?>
            
            <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 24px; background: #fafafa;">
                <h3 style="margin-top: 0; color: var(--maroon); font-size: 16px; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($program['name']); ?>
                </h3>
                
                <div class="form__group--inline">
                    <div>
                        <label>Current Image</label>
                        <div class="media-preview" style="width: 500px; height: 300px; overflow: hidden; border-radius: 8px; border: 1px solid var(--border); background: #fff; display: flex; align-items: center; justify-content: center;">
                            <?php if ($displayImagePath && file_exists(dirname(__DIR__) . '/' . $displayImagePath)): ?>
                                <img src="../<?php echo htmlspecialchars($displayImagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($program['name']); ?>" 
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
                        <label for="program_image_<?php echo htmlspecialchars($key); ?>">Upload New Image</label>
                        <input type="file" 
                               id="program_image_<?php echo htmlspecialchars($key); ?>" 
                               name="program_image_<?php echo htmlspecialchars($key); ?>" 
                               accept=".png,.jpg,.jpeg,.webp">
                        <input type="hidden" 
                               name="program_image_<?php echo htmlspecialchars($key); ?>_current" 
                               value="<?php echo htmlspecialchars($currentImagePath); ?>">
                        <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                            <strong>Recommended size:</strong> 500x300 px
                        </p>
                        <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">
                            Frame size is fixed at 500x300px. Images will be cropped to fit using object-fit: cover.
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
        
        <button type="submit" class="btn btn--primary">Save All Program Images</button>
    </form>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

