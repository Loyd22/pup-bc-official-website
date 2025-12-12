<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';
require_once __DIR__ . '/../admin/includes/functions.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'view-campus-offices');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Fetch offices from database
$offices = [];
$result = $conn->query("SELECT id, category, tag, name, description, location, hours, image FROM campus_offices ORDER BY display_order, category, name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Convert id to slug format for backward compatibility
        $row['id'] = strtolower(str_replace([' ', '&'], ['-', ''], $row['category'])) . '-' . strtolower(str_replace([' ', '&'], ['-', ''], $row['tag']));
        // Ensure image path starts with ../
        if (!empty($row['image']) && strpos($row['image'], '../') !== 0 && strpos($row['image'], 'http') !== 0) {
            $row['image'] = '../' . ltrim($row['image'], '/');
        }
        $offices[] = $row;
    }
    $result->free();
}

// Fetch gallery images from database
$galleryImages = [];
$galleryResult = $conn->query("SELECT image_path, alt_text, size_class FROM campus_gallery ORDER BY display_order, id LIMIT 20");
if ($galleryResult) {
    while ($row = $galleryResult->fetch_assoc()) {
        // Ensure image path starts with ../
        if (!empty($row['image_path']) && strpos($row['image_path'], '../') !== 0 && strpos($row['image_path'], 'http') !== 0) {
            $row['image_path'] = '../' . ltrim($row['image_path'], '/');
        }
        $galleryImages[] = $row;
    }
    $galleryResult->free();
}

// Fetch optional campus video URL and normalize to an embeddable format
$campusVideoUrl = trim(get_setting($conn, 'campus_offices_video_url', ''));
if ($campusVideoUrl !== '') {
    $parsedUrl = parse_url($campusVideoUrl);
    $host = $parsedUrl['host'] ?? '';
    $path = $parsedUrl['path'] ?? '';
    $query = $parsedUrl['query'] ?? '';
    $videoId = '';

    if (strpos($host, 'youtu.be') !== false) {
        $videoId = ltrim($path, '/');
    } elseif (strpos($host, 'youtube.com') !== false) {
        if (preg_match('~^/(?:shorts|embed)/([^/?]+)~', (string)$path, $matches)) {
            $videoId = $matches[1];
        }
        if ($videoId === '' && $query) {
            parse_str($query, $queryParams);
            $videoId = $queryParams['v'] ?? '';
        }
    }

    if ($videoId !== '') {
        $campusVideoUrl = 'https://www.youtube.com/embed/' . $videoId;
    }
}

// Get filter from query string
$activeFilter = $_GET['filter'] ?? 'All';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Campus Offices - PUP Biñan Campus</title>
  <meta name="description" content="Campus Offices at PUP Biñan Campus: find offices, locations, and hours for admissions, registrar, scholarships, guidance, library, and more." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>
</head>
<body>

  <!-- Top ribbon (match homepage) -->
  <div class="topbar" role="banner">
    <div class="container topbar-inner">
      <div class="seal" aria-hidden="true">
        <a href="../homepage.php">
          <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
        </a>
      </div>
      <div class="brand" aria-label="Campus name">
        <span class="u">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</span>
        <span class="c">Biñan Campus</span>
      </div>
    </div>
  </div>

  <!-- Header / Nav (same as home structure) -->
  <header>
    <div class="container nav">
      <div class="brand-nav">
        <div class="seal" aria-hidden="true">
          <a href="../homepage.php">
            <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          </a>
        </div>
        <div class="brand" aria-label="Campus name">
          <span class="u">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</span>
          <span class="c">Biñan Campus</span>
          <nav aria-label="Primary" class="menu" id="menu">
            <a href="../homepage.php">Home</a>
            <a href="./about.php">About</a>
            <a href="./programs.php">Academic Programs</a>
            <a href="./admission_guide.php">Admission</a>
            <a class="is-active" href="./services.php">Student Services</a>
            <a href="./campuslife.php">Campus Life</a>
            <a href="./contact.php">Contact Us</a>
          </nav>
        </div>
      </div>
      <form class="search-form" action="../search.php" method="get" role="search" aria-label="Site search">
        <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
        <button type="submit" aria-label="Search">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </form>
      
      <!-- Mobile Menu Toggle -->
      <button id="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false" aria-controls="mobile-nav-panel">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
    
    <!-- Mobile Navigation Panel -->
    <div id="mobile-nav-panel" aria-hidden="true" role="navigation">
      <div class="mobile-nav-header">
        <div class="mobile-nav-logo">
          <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          <div class="mobile-nav-brand">
            <span class="u">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</span>
            <span class="c">Biñan Campus</span>
          </div>
        </div>
      </div>
      <nav class="mobile-nav-menu">
        <!-- Mobile Search Form -->
        <div class="mobile-nav-search">
          <form class="mobile-nav-search-form" action="../search.php" method="get" role="search" aria-label="Site search">
            <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
            <button type="submit" aria-label="Search">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </form>
        </div>
        <a href="../homepage.php" class="mobile-nav-link">Home</a>
        <a href="./about.php" class="mobile-nav-link">About</a>
        <a href="./programs.php" class="mobile-nav-link">Academic Programs</a>
        <a href="./admission_guide.php" class="mobile-nav-link">Admissions</a>
        <a href="./services.php" class="mobile-nav-link is-active">Student Services</a>
        <a href="./campuslife.php" class="mobile-nav-link">Campus Life</a>
        <a href="./contact.php" class="mobile-nav-link">Contact Us</a>
      </nav>
    </div>
  </header>

  <main id="content">
    <!-- HERO SECTION -->
    <!-- HERO SECTION -->
    <section class="campusoffices-page-hero">
      <div class="container">
        <div class="campusoffices-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="campusoffices-page-hero-text">
            <p class="campusoffices-page-hero-label">CAMPUS OFFICES</p>
            <h1 class="campusoffices-page-hero-title">Campus <span class="campusoffices-page-hero-accent">Offices</span></h1>
            <p class="campusoffices-page-hero-description">Find the offices you need, their locations, and operating hours. All offices are here to help you succeed at PUP Biñan.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FILTER ROW -->
    <section class="offices-filter-section">
      <div class="container">
        <div class="offices-filter-row">
          <button class="offices-filter-btn <?php echo $activeFilter === 'All' ? 'active' : ''; ?>" data-filter="All">
            All
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'Admissions & Registrar' ? 'active' : ''; ?>" data-filter="Admissions & Registrar">
            Admissions & Registrar
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'Scholarships & Financial Aid' ? 'active' : ''; ?>" data-filter="Scholarships & Financial Aid">
            Scholarships & Financial Aid
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'Guidance & Counseling' ? 'active' : ''; ?>" data-filter="Guidance & Counseling">
            Guidance & Counseling
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'Library & Learning' ? 'active' : ''; ?>" data-filter="Library & Learning">
            Library & Learning
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'Student Affairs & Services' ? 'active' : ''; ?>" data-filter="Student Affairs & Services">
            Student Affairs & Services
          </button>
          <button class="offices-filter-btn <?php echo $activeFilter === 'IT & Support' ? 'active' : ''; ?>" data-filter="IT & Support">
            IT & Support
          </button>
        </div>
      </div>
    </section>

    <!-- OFFICES GALLERY -->
    <section class="offices-gallery-section">
      <div class="container">
        <div class="offices-gallery-grid">
          <?php foreach ($offices as $office): ?>
            <?php 
            $isVisible = $activeFilter === 'All' || $office['category'] === $activeFilter;
            $displayClass = $isVisible ? '' : 'hidden';
            ?>
            <article class="office-card <?php echo $displayClass; ?>" data-category="<?php echo htmlspecialchars($office['category']); ?>">
              <div class="office-card-image">
                <img src="<?php echo htmlspecialchars($office['image']); ?>" alt="<?php echo htmlspecialchars($office['name']); ?>" />
                <div class="office-card-tag"><?php echo htmlspecialchars($office['tag']); ?></div>
              </div>
              <div class="office-card-body">
                <h3 class="office-card-title"><?php echo htmlspecialchars($office['name']); ?></h3>
                <p class="office-card-description"><?php echo htmlspecialchars($office['description']); ?></p>
                <div class="office-card-info">
                  <div class="office-info-item">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($office['location']); ?></span>
                  </div>
                  <div class="office-info-item">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    <span><?php echo $office['hours']; ?></span>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if (!empty($campusVideoUrl)): ?>
      <section class="campus-video-section">
        <div class="container">
          <div class="campus-video-wrapper">
            <iframe src="<?php echo htmlspecialchars($campusVideoUrl); ?>"
                    title="Campus offices video"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"></iframe>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- CAMPUS GALLERY SECTION -->
    <section class="campus-gallery-section">
      <div class="container">
        <div class="campus-gallery-header">
          <h2 class="campus-gallery-title">Our outstanding academic experience isn't the only aspect that draws students from across the globe.</h2>
          <p class="campus-gallery-description">From modern facilities and state-of-the-art laboratories to vibrant student spaces and scenic campus grounds, PUP Biñan offers visitors numerous opportunities to experience its natural beauty and academic excellence. One visit to campus and you will see why PUP Biñan is committed to providing quality public education.</p>
        </div>
        <div class="campus-gallery-grid">
          <?php if (empty($galleryImages)): ?>
            <p style="grid-column: 1 / -1; text-align: center; color: var(--muted); padding: 2rem;">
              No gallery images available. Please check back soon.
            </p>
          <?php else: ?>
            <?php foreach ($galleryImages as $gallery): ?>
              <?php
              $sizeClass = $gallery['size_class'] ?? 'regular';
              $itemClass = 'gallery-item';
              if ($sizeClass === 'large') {
                  $itemClass .= ' gallery-item-large';
              } elseif ($sizeClass === 'tall') {
                  $itemClass .= ' gallery-item-tall';
              } elseif ($sizeClass === 'wide') {
                  $itemClass .= ' gallery-item-wide';
              }
              ?>
              <div class="<?php echo $itemClass; ?>"
                   data-full="<?php echo htmlspecialchars($gallery['image_path']); ?>"
                   data-alt="<?php echo htmlspecialchars($gallery['alt_text'] ?? 'Campus Image'); ?>"
                   tabindex="0"
                   role="button"
                   aria-label="Open image in larger view: <?php echo htmlspecialchars($gallery['alt_text'] ?? 'Campus Image'); ?>">
                <img src="<?php echo htmlspecialchars($gallery['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($gallery['alt_text'] ?? 'Campus Image'); ?>" />
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <div class="gallery-lightbox" id="galleryLightbox" aria-hidden="true">
      <button type="button" class="gallery-lightbox-close" id="galleryLightboxClose" aria-label="Close image preview">&times;</button>
      <img src="" alt="" id="galleryLightboxImage">
    </div>
  </main>

  <!-- Footer -->
  <footer id="contact">
    <div class="container foot">
      <div class="footer-brand-block">
        <div class="footer-logo">
          <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          <div>
            <p class="campus-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p>
            <p class="campus-sub">Biñan Campus</p>
          </div>
        </div>
        <p class="footer-tagline">Serving the nation through quality public education.</p>
        <p class="stats">Page Views: <?php echo $page_views ?? 0; ?> &#8226; Total Visitors: <?php echo $total_visitors ?? 0; ?></p>
      </div>
      <div class="footer-links">
        <div class="footer-column">
          <h4>Academics</h4>
          <a href="./programs.php">Academic Programs</a>
          <a href="./admission_guide.php">Admissions</a>
          <a href="./services.php">Student Services</a>
          <a href="./forms.php">Downloadable Forms</a>
        </div>
        <div class="footer-column">
          <h4>Campus</h4>
          <a href="./campuslife.php">Campus Life</a>
          <a href="./announcement.php">Announcements</a>
          <a href="./faq.php">FAQs</a>
          <a href="./contact.php">Contact Us</a>
        </div>
        <div class="footer-column">
          <h4>Resources</h4>
          <a href="../homepage.php#calendar">Academic Calendar</a>
          <a href="./about.php#vision-mission">Mission &amp; Vision</a>
          <a href="./about.php#history">Campus History</a>
          <a href="../privacy-policy.php">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="sub container">
      <span>&copy; <span id="year"></span> PUP Biñan Campus. All rights reserved.</span>
    </div>
  </footer>

  <style>
    /* Campus Offices Page Styles */
    
    /* Hero Section */
    .offices-hero {
      padding: 4rem 0;
      background: linear-gradient(135deg, var(--maroon) 0%, rgba(122, 0, 25, 0.3) 50%, #fff7df 100%);
      position: relative;
      overflow: hidden;
    }
    
    .offices-hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top left, rgba(246,199,0,0.2), transparent 60%);
      pointer-events: none;
    }
    
    .offices-hero-inner {
      position: relative;
      z-index: 1;
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
    }
    
    .offices-hero-title {
      font-size: 3rem;
      font-weight: 700;
      line-height: 1.2;
      margin: 0 0 1rem;
      color: #ffffff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .offices-hero-accent {
      color: var(--gold);
    }
    
    .offices-hero-description {
      font-size: 1.125rem;
      line-height: 1.6;
      color: #ffffff;
      margin: 0 0 2rem;
      opacity: 0.95;
    }
    
    /* Filter Section */
    .offices-filter-section {
      padding: 2rem 0;
      background: #ffffff;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .offices-filter-row {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    
    .offices-filter-btn {
      padding: 0.6rem 1.25rem;
      background: #f3f4f6;
      color: var(--maroon);
      font-size: 0.9375rem;
      font-weight: 500;
      border: 1px solid #e5e7eb;
      border-radius: 9999px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .offices-filter-btn:hover {
      background: var(--maroon);
      color: #ffffff;
      border-color: var(--maroon);
    }
    
    .offices-filter-btn.active {
      background: var(--maroon);
      color: #ffffff;
      border-color: var(--maroon);
    }
    
    /* Gallery Section */
    .offices-gallery-section {
      padding: 3rem 0 4rem;
      background: #f9fafb;
    }
    
    .offices-gallery-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }
    
    .office-card {
      background: #ffffff;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      transition: none;
      opacity: 1;
      transform: none;
    }
    
    .office-card.hidden {
      display: none;
    }
    
    
    
    .office-card-image {
      position: relative;
      width: 100%;
      height: 200px;
      overflow: hidden;
      background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-900) 100%);
    }
    
    .office-card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    
   
    
    .office-card-tag {
      position: absolute;
      top: 1rem;
      right: 1rem;
      padding: 0.4rem 0.9rem;
      background: var(--gold);
      color: var(--maroon);
      font-size: 0.8125rem;
      font-weight: 600;
      border-radius: 9999px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .office-card-body {
      padding: 1.5rem;
    }
    
    .office-card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--maroon);
      margin: 0 0 0.75rem;
      line-height: 1.3;
    }
    
    .office-card-description {
      font-size: 0.9375rem;
      line-height: 1.6;
      color: var(--muted);
      margin: 0 0 1.25rem;
    }
    
    .office-card-info {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      padding-top: 1rem;
      border-top: 1px solid #e5e7eb;
    }
    
    .office-info-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      font-size: 0.875rem;
      color: var(--ink);
    }
    
    .office-info-item i {
      color: var(--maroon);
      margin-top: 0.125rem;
      flex-shrink: 0;
    }
    
    .office-info-item span {
      line-height: 1.5;
    }
    
    /* Campus Gallery Section */
    .campus-gallery-section {
      padding: 5rem 0;
      background: #ffffff;
    }
    
    .campus-gallery-header {
      max-width: 900px;
      margin: 0 auto 4rem;
      text-align: center;
    }
    
    .campus-gallery-title {
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1.3;
      color: var(--maroon-900);
      margin: 0 0 1.5rem;
      font-style: italic;
    }
    
    .campus-gallery-description {
      font-size: 1.125rem;
      line-height: 1.8;
      color: var(--ink);
      margin: 0;
      max-width: 100%;
    }
    
    .campus-gallery-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      grid-auto-rows: 280px;
      gap: 1.5rem;
      margin-top: 3rem;
    }
    
    .gallery-item {
      position: relative;
      overflow: hidden;
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      cursor: zoom-in;
      background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-900) 100%);
    }
    
    .gallery-item:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(122, 0, 25, 0.25);
    }
    
    .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.4s ease;
    }
    
    .gallery-item:hover img {
      transform: scale(1.08);
    }

    /* Campus Video */
    .campus-video-section {
      padding: 4rem 0;
      background: #ffffff;
    }

    .campus-video-wrapper {
      position: relative;
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      padding-bottom: 45%;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
      background: #000;
    }

    .campus-video-wrapper iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    /* Lightbox */
    .gallery-lightbox {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      padding: 2rem;
    }

    .gallery-lightbox.is-open {
      display: flex;
    }

    .gallery-lightbox img {
      max-width: min(90vw, 1200px);
      max-height: 85vh;
      border-radius: 0.75rem;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
      object-fit: contain;
    }

    .gallery-lightbox-close {
      position: absolute;
      top: 1.5rem;
      right: 1.5rem;
      background: #ffffff;
      color: var(--maroon);
      border: none;
      border-radius: 9999px;
      width: 42px;
      height: 42px;
      font-size: 1.5rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .gallery-lightbox-close:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.28);
    }

    body.no-scroll {
      overflow: hidden;
    }
    
    /* Varied grid item sizes for masonry effect */
    .gallery-item-large {
      grid-column: span 2;
      grid-row: span 2;
    }
    
    .gallery-item-tall {
      grid-row: span 2;
    }
    
    .gallery-item-wide {
      grid-column: span 2;
    }
    
    /* Responsive Design */
    @media (max-width: 1400px) {
      .offices-gallery-grid {
        gap: 1.5rem;
      }
    }
    
    @media (max-width: 1200px) {
      .offices-gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
      }
      
      .campus-gallery-grid {
        grid-template-columns: repeat(3, 1fr);
        grid-auto-rows: 220px;
        gap: 1.25rem;
      }
      
      .campus-gallery-title {
        font-size: 2rem;
      }
      
      .campus-gallery-description {
        font-size: 1rem;
      }
    }
    
    @media (max-width: 768px) {
      .offices-hero {
        padding: 2.5rem 0;
      }
      
      .offices-hero-title {
        font-size: 2rem;
      }
      
      .offices-hero-description {
        font-size: 0.9375rem;
      }
      
      
      .offices-filter-row {
        gap: 0.5rem;
        flex-wrap: wrap;
      }
      
      .offices-filter-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      
      .offices-gallery-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
      }
      
      .offices-gallery-section {
        padding: 2rem 0;
      }
      
      .campus-gallery-section {
        padding: 3rem 0;
      }
      
      .campus-gallery-header {
        margin-bottom: 2.5rem;
      }
      
      .campus-gallery-title {
        font-size: 1.75rem;
      }
      
      .campus-gallery-description {
        font-size: 0.9375rem;
      }
      
      .campus-gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-auto-rows: 200px;
        gap: 1rem;
      }
      
      .gallery-item-large,
      .gallery-item-tall,
      .gallery-item-wide {
        grid-column: span 1;
        grid-row: span 1;
      }
    }
    
    @media (max-width: 640px) {
      .offices-hero {
        padding: 2rem 0;
      }
      
      .offices-hero-title {
        font-size: 1.75rem;
      }
      
      .offices-hero-description {
        font-size: 0.875rem;
      }
      
      .office-card-image {
        height: 180px;
      }
      
      .office-card-body {
        padding: 1.25rem;
      }
      
      .offices-filter-btn {
        padding: 0.45rem 0.875rem;
        font-size: 0.8125rem;
      }
      
      .campus-gallery-section {
        padding: 2.5rem 0;
      }
      
      .campus-gallery-title {
        font-size: 1.5rem;
      }
      
      .campus-gallery-description {
        font-size: 0.875rem;
      }
      
      .campus-gallery-grid {
        grid-template-columns: 1fr;
        grid-auto-rows: 250px;
        gap: 1rem;
      }
    }
  </style>

  <script>
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.offices-filter-btn');
      const officeCards = document.querySelectorAll('.office-card');
      const galleryItems = document.querySelectorAll('.gallery-item');
      const lightbox = document.getElementById('galleryLightbox');
      const lightboxImg = document.getElementById('galleryLightboxImage');
      const lightboxClose = document.getElementById('galleryLightboxClose');
      
      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');
          
          // Update active state
          filterButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          
          // Filter cards
          officeCards.forEach(card => {
            const category = card.getAttribute('data-category');
            if (filter === 'All' || category === filter) {
              card.classList.remove('hidden');
            } else {
              card.classList.add('hidden');
            }
          });
          
          // Update URL without page reload
          const url = new URL(window.location);
          if (filter === 'All') {
            url.searchParams.delete('filter');
          } else {
            url.searchParams.set('filter', filter);
          }
          window.history.pushState({}, '', url);
        });
      });

      function openLightbox(src, altText) {
        if (!lightbox || !lightboxImg) return;
        lightboxImg.src = src;
        lightboxImg.alt = altText || 'Campus image';
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        if (lightboxClose) {
          lightboxClose.focus();
        }
      }

      function closeLightbox() {
        if (!lightbox || !lightboxImg) return;
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImg.src = '';
        lightboxImg.alt = '';
        document.body.classList.remove('no-scroll');
      }

      galleryItems.forEach(item => {
        const img = item.querySelector('img');
        const src = img ? img.getAttribute('src') : item.getAttribute('data-full');
        const altText = img ? img.getAttribute('alt') : item.getAttribute('data-alt');

        item.addEventListener('click', () => {
          if (src) openLightbox(src, altText);
        });

        item.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (src) openLightbox(src, altText);
          }
        });
      });

      if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
      }

      if (lightbox) {
        lightbox.addEventListener('click', (e) => {
          if (e.target === lightbox) {
            closeLightbox();
          }
        });
      }

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox && lightbox.classList.contains('is-open')) {
          closeLightbox();
        }
      });
    });
  </script>

</body>
</html>

