<?php
/**
 * Example: How to use the Hero Video Component in other pages
 * 
 * This file demonstrates different ways to use the hero video component.
 * Copy the examples below into your PHP pages.
 */

// ============================================
// EXAMPLE 1: Basic Usage (Same as homepage)
// ============================================
/*
$content = '<div class="container hero-inner">
  <div>
    <h1>Your Page Title</h1>
    <p>Your page description here.</p>
  </div>
</div>';

$videoPath = '../video/homepagevid.mp4'; // or get from settings
$customClass = 'hero';
$options = [
  'height' => 'auto',
  'minHeight' => '400px',
  'brightness' => 0.6,
  'showOverlay' => true,
  'autoplay' => true,
  'muted' => true,
  'loop' => true,
];

include '../components/hero-video.php';
*/

// ============================================
// EXAMPLE 2: Custom Small Size
// ============================================
/*
$content = '<div class="container hero-inner">
  <h1>Small Hero Section</h1>
</div>';

$videoPath = '../video/small-hero.mp4';
$customClass = 'hero custom-small'; // Use custom-small class
$options = [
  'height' => '300px',
  'minHeight' => '250px',
  'brightness' => 0.7,
];

include '../components/hero-video.php';
*/

// ============================================
// EXAMPLE 3: Fullscreen Hero
// ============================================
/*
$content = '<div class="container hero-inner">
  <h1>Fullscreen Hero</h1>
  <p>This hero takes up the full viewport height.</p>
</div>';

$videoPath = '../video/fullscreen.mp4';
$customClass = 'hero custom-fullscreen';
$options = [
  'height' => '100vh',
  'minHeight' => '500px',
  'brightness' => 0.5,
];

include '../components/hero-video.php';
*/

// ============================================
// EXAMPLE 4: Medium Size with Custom Brightness
// ============================================
/*
$content = '<div class="container hero-inner">
  <h1>Medium Hero</h1>
</div>';

$videoPath = '../video/medium-hero.mp4';
$customClass = 'hero custom-medium';
$options = [
  'height' => '500px',
  'minHeight' => '400px',
  'maxHeight' => '600px',
  'brightness' => 0.8, // Lighter video
];

include '../components/hero-video.php';
*/

// ============================================
// EXAMPLE 5: Without Video (Background Only)
// ============================================
/*
$content = '<div class="container hero-inner">
  <h1>No Video Hero</h1>
  <p>This hero uses only the gradient background.</p>
</div>';

$videoPath = ''; // Empty = no video
$customClass = 'hero';
$options = [
  'height' => '400px',
  'showOverlay' => true,
];

include '../components/hero-video.php';
*/

// ============================================
// EXAMPLE 6: Video Without Overlay
// ============================================
/*
$content = '<div class="container hero-inner">
  <h1>Clear Video</h1>
  <p>Video without gradient overlay.</p>
</div>';

$videoPath = '../video/clear.mp4';
$customClass = 'hero';
$options = [
  'showOverlay' => false, // No overlay
  'brightness' => 0.9,    // Brighter video
];

include '../components/hero-video.php';
*/

?>

