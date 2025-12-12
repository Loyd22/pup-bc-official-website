<?php
declare(strict_types=1);

// Include auth first (before any output) to check authentication
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$pageTitle = 'Site Settings';
$currentSection = 'settings';

$settingKeys = [
    'hero_video_path',
    'about_overview_image'
];

$settings = array_merge(
    array_fill_keys($settingKeys, ''),
    get_settings($conn, $settingKeys)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'hero_video') {
        $heroVideoData = [];

        $heroVideoUpload = handle_file_upload('hero_video_upload', 'videos', ['mp4', 'webm', 'ogg']);
        if (isset($heroVideoUpload['error'])) {
            add_flash('error', $heroVideoUpload['error']);
            header('Location: settings.php');
            exit;
        }

        if (isset($heroVideoUpload['path'])) {
            $heroVideoData['hero_video_path'] = $heroVideoUpload['path'];
        } else {
            // If no new video uploaded, keep existing path
            if (!empty($_POST['hero_video_path_current'])) {
                $heroVideoData['hero_video_path'] = $_POST['hero_video_path_current'];
            }
        }

        if (save_settings($conn, $heroVideoData)) {
            add_flash('success', 'Hero video updated successfully.');
        } else {
            add_flash('error', 'Unable to update hero video.');
        }

        header('Location: settings.php');
        exit;
    } elseif ($action === 'about_overview') {
        $aboutData = [];

        // Handle overview image upload
        $overviewImageUpload = handle_file_upload('about_overview_image_upload', 'images/uploads', ['png', 'jpg', 'jpeg', 'webp']);
        if (isset($overviewImageUpload['path'])) {
            $aboutData['about_overview_image'] = $overviewImageUpload['path'];
        } else {
            // If no new image uploaded, keep existing path
            if (!empty($_POST['about_overview_image_current'])) {
                $aboutData['about_overview_image'] = $_POST['about_overview_image_current'];
            }
        }

        if (save_settings($conn, $aboutData)) {
            add_flash('success', 'Campus overview updated successfully.');
        } else {
            add_flash('error', 'Unable to update campus overview.');
        }

        header('Location: settings.php');
        exit;
    }
}

// Include header AFTER all POST processing (which may redirect)
require_once __DIR__ . '/includes/header.php';

$heroVideoPath = $settings['hero_video_path'] ?? '';
?>

<section class="card">
    <h2>Hero Video</h2>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="hero_video">
        <input type="hidden" name="hero_video_path_current" value="<?php echo htmlspecialchars($heroVideoPath); ?>">
        
        <div class="form__group--inline">
            <div>
                <label>Hero Video (optional - used instead of image if uploaded)</label>
                <div class="media-preview">
                    <?php if ($heroVideoPath): ?>
                        <video src="../<?php echo htmlspecialchars($heroVideoPath); ?>" controls style="max-width: 100%; max-height: 200px; border-radius: 0.5rem;">
                            Your browser does not support the video tag.
                        </video>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--muted);">Current: <?php echo htmlspecialchars($heroVideoPath); ?></p>
                    <?php else: ?>
                        <span>No hero video uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="hero_video_upload">Upload Hero Video</label>
                <input type="file" id="hero_video_upload" name="hero_video_upload" accept=".mp4,.webm,.ogg">
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">Recommended: MP4 format, optimized for web</p>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Save Hero Video</button>
    </form>
</section>

<section class="card">
    <h2>About Page Content</h2>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="about_overview">

        <h3>Campus Overview</h3>
        <div class="form__group--inline">
            <div>
                <label>Current Overview Image</label>
                <div class="media-preview">
                    <?php if (!empty($settings['about_overview_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($settings['about_overview_image']); ?>" alt="Campus Overview">
                    <?php else: ?>
                        <span>No image uploaded. Default: images/pupcollage.jpg</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="about_overview_image_upload">Upload New Overview Image</label>
                <input type="file" id="about_overview_image_upload" name="about_overview_image_upload" accept=".png,.jpg,.jpeg,.webp">
                <input type="hidden" name="about_overview_image_current" value="<?php echo htmlspecialchars($settings['about_overview_image'] ?? ''); ?>">
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Save Campus Overview</button>
    </form>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
