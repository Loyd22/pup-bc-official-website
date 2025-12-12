# Hero Video Component - Quick Reference Guide

## Overview
The hero video component has been separated from `homepage.php` into a reusable component that can be used independently on any page with customizable sizes.

## Files Created
- `components/hero-video.php` - The reusable component
- `asset/css/hero-video.css` - Independent stylesheet for the component
- `components/hero-video-examples.php` - Usage examples

## Basic Usage

```php
<?php
// Set your content
$content = '<div class="container hero-inner">
  <div>
    <h1>Your Title</h1>
    <p>Your description</p>
  </div>
</div>';

// Set video path
$videoPath = '../video/your-video.mp4';

// Optional: Add custom class for sizing
$customClass = 'hero custom-medium';

// Optional: Customize options
$options = [
  'height' => '500px',
  'minHeight' => '400px',
  'brightness' => 0.6,
  'showOverlay' => true,
];

// Include the component
include '../components/hero-video.php';
?>
```

## Pre-defined Size Classes

Add these classes to `$customClass` for quick sizing:

- `custom-compact` - 250px height (smallest)
- `custom-small` - 300px height
- `custom-medium` - 500px height
- `custom-large` - 700px height
- `custom-tall` - 800px height
- `custom-fullscreen` - 100vh (full viewport height)

**Example:**
```php
$customClass = 'hero custom-medium'; // Medium sized hero
```

## Custom Size via Options

You can also set custom sizes using the `$options` array:

```php
$options = [
  'height' => '600px',      // Fixed height
  'minHeight' => '400px',    // Minimum height
  'maxHeight' => '800px',    // Maximum height
  'brightness' => 0.7,       // Video brightness (0.0 to 1.0)
  'showOverlay' => true,     // Show/hide gradient overlay
  'autoplay' => true,        // Autoplay video
  'muted' => true,           // Mute video
  'loop' => true,            // Loop video
];
```

## Custom CSS Styling

You can also add your own CSS classes and style them in `hero-video.css`:

```css
/* In hero-video.css */
.hero-video-section.my-custom-size {
  --hero-video-height: 450px;
  --hero-video-min-height: 350px;
}
```

Then use it:
```php
$customClass = 'hero my-custom-size';
```

## Using in Other Pages

1. **Include the CSS** in your page's `<head>`:
```html
<link rel="stylesheet" href="../asset/css/hero-video.css" />
```

2. **Include the component** where you want the hero section:
```php
<?php
$content = '...'; // Your HTML content
$videoPath = '../video/video.mp4';
$customClass = 'hero custom-medium';
include '../components/hero-video.php';
?>
```

## Notes

- The component maintains backward compatibility with existing `.hero` class styles
- If no video path is provided, only the gradient background will show
- The component automatically detects video format (MP4, WebM, OGG)
- All video attributes (autoplay, muted, loop) are configurable via options

