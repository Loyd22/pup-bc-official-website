<?php
/**
 * Hero Video Component
 * 
 * Reusable hero video section that can be included in any page.
 * 
 * @param string $videoPath - Path to the video file
 * @param string $customClass - Additional CSS class for custom styling
 * @param array $options - Additional options:
 *   - height: Custom height (e.g., '500px', '60vh', 'auto')
 *   - minHeight: Minimum height (e.g., '400px')
 *   - maxHeight: Maximum height (e.g., '800px')
 *   - brightness: Video brightness filter (0.0 to 1.0, default: 0.6)
 *   - showOverlay: Show gradient overlay (default: true)
 *   - autoplay: Autoplay video (default: true)
 *   - muted: Mute video (default: true)
 *   - loop: Loop video (default: true)
 */

// Default options
$defaultOptions = [
  'height' => 'auto',
  'minHeight' => '400px',
  'maxHeight' => 'none',
  'brightness' => 0.6,
  'showOverlay' => true,
  'autoplay' => true,
  'muted' => true,
  'loop' => true,
];

// Merge with provided options
$opts = array_merge($defaultOptions, $options ?? []);

// Determine video MIME type
$mimeType = 'video/mp4'; // default
if (!empty($videoPath)) {
  $videoExt = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
  if ($videoExt === 'mp4') $mimeType = 'video/mp4';
  elseif ($videoExt === 'webm') $mimeType = 'video/webm';
  elseif ($videoExt === 'ogg') $mimeType = 'video/ogg';
}

// Build CSS custom properties for inline styling
$styleVars = [];
if ($opts['height'] !== 'auto') {
  $styleVars[] = '--hero-video-height: ' . $opts['height'];
}
if ($opts['minHeight'] !== '400px') {
  $styleVars[] = '--hero-video-min-height: ' . $opts['minHeight'];
}
if ($opts['maxHeight'] !== 'none') {
  $styleVars[] = '--hero-video-max-height: ' . $opts['maxHeight'];
}
if ($opts['brightness'] != 0.6) {
  $styleVars[] = '--hero-video-brightness: ' . $opts['brightness'];
}

$styleAttr = !empty($styleVars) ? ' style="' . implode('; ', $styleVars) . '"' : '';
$classAttr = 'hero-video-section' . (!empty($customClass) ? ' ' . htmlspecialchars($customClass) : '');
?>

<section class="<?php echo htmlspecialchars($classAttr); ?>"<?php echo $styleAttr; ?>>
  <?php if (!empty($videoPath)): ?>
    <video class="hero-video-element" 
           <?php echo $opts['autoplay'] ? 'autoplay' : ''; ?>
           <?php echo $opts['muted'] ? 'muted' : ''; ?>
           <?php echo $opts['loop'] ? 'loop' : ''; ?>
           playsinline>
      <source src="<?php echo htmlspecialchars($videoPath); ?>" type="<?php echo htmlspecialchars($mimeType); ?>">
      Your browser does not support the video tag.
    </video>
  <?php endif; ?>
  
  <?php if ($opts['showOverlay']): ?>
    <div class="hero-video-overlay"></div>
  <?php endif; ?>
  
  <?php if (!empty($content)): ?>
    <div class="hero-video-content">
      <?php echo $content; ?>
    </div>
  <?php endif; ?>
</section>

