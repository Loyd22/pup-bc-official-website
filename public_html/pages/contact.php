<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'contact.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int) ($row['total'] ?? 0);
  $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Us - PUP Bi&ntilde;an Campus</title>
  <meta name="description" content="Contact information, location map, and inquiry form for PUP Bi&ntilde;an Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/contact.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/map.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>


</head>

<body>

  <div class="topbar" role="banner">
    <div class="container topbar-inner">
      <div class="seal" aria-hidden="true">
        <a href="../homepage.php">
          <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
        </a>
      </div>
      <div class="brand" aria-label="Campus name">
        <span class="u">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</span>
        <span class="c">BiÃ±an Campus</span>
      </div>
    </div>
  </div>

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
          <span class="c">Bi&ntilde;an Campus</span>
          <nav aria-label="Primary" class="menu" id="menu">
            <a href="../homepage.php">Home</a>
            <a href="./about.php">About</a>
            <a href="./programs.php">Academic Programs</a>
            <a href="./admission_guide.php">Admission</a>
            <a href="./services.php">Student Services</a>
            <a href="./campuslife.php">Campus Life</a>
            <a class="is-active" href="./contact.php">Contact Us</a>
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
        <a href="./services.php" class="mobile-nav-link">Student Services</a>
        <a href="./campuslife.php" class="mobile-nav-link">Campus Life</a>
        <a href="./contact.php" class="mobile-nav-link is-active">Contact Us</a>
      </nav>
    </div>
  </header>

    <main id="content">
    <!-- HERO SECTION -->
    <section class="contact-page-hero">
      <div class="container">
        <div class="contact-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="contact-page-hero-text">
            <p class="contact-page-hero-label">GET IN TOUCH</p>
            <h1 class="contact-page-hero-title">Contact <span class="contact-page-hero-accent">Us</span></h1>
            <p class="contact-page-hero-description">Reach out to PUP Biñan Campus by email, phone, or visit our location.</p>
          </div>
        </div>
      </div>
    </section>
    
    <section class="contact-hero-old" style="display:none;">
      <div class="contact-hero-overlay">
        <p class="contact-kicker">We'd love to hear from you</p>
        <h1>Contact Us</h1>
        <p class="contact-sub">Reach the campus by email, phone, or visit our location.</p>
      </div>
    </section>

    <section class="section contact-section">
      <div class="container contact-layout">
        <div class="contact-card map-card map-card-hero" id="locate-us">
          <div class="card-head"><h2>LOCATE US</h2></div>
          <div class="map-wrap">
            <div id="map"></div>
            <div class="focus-dropdown-wrapper">
              <button id="focus-btn" title="Focus on location" class="focus-btn-main">
                <i class="fa-solid fa-crosshairs"></i>
              </button>
              <div id="focus-menu" class="focus-menu">
                <button class="focus-option" data-location="main">
                  <i class="fa-solid fa-map-pin"></i> PUP Binan Campus
                </button>
                <button class="focus-option" data-location="cite">
                  <i class="fa-solid fa-map-pin"></i> PUP Binan - CITE Campus
                </button>
              </div>
            </div>
          </div>
          <p class="map-note">Click the crosshair to focus on a location.</p>
        </div>

        <div class="contact-card info-card info-card-main" id="meet-us">
 
          <ul class="info-list">
                 <div class="card-head"><h2>CONTACT NUMBER: 
                 </h2></div>
            <li><span class="icon"><i class="fa-solid fa-phone"></i></span>(049) 544 0627</li>
                  <div class="card-head"><h2>EMAIL: </h2></div>
            <li><span class="icon"><i class="fa-solid fa-envelope"></i></span><a href="mailto:binan@pup.edu.ph">binan@pup.edu.ph</a></li>
          </ul>
        </div>

        <div class="contact-card form-card social-card" id="social">
          <div class="card-head"><h2>SOCIAL</h2></div>
          <div class="social-links">
            <a class="social-link fb" href="https://www.facebook.com/profile.php?id=100064337101587" target="_blank" rel="noopener">
              <i class="fa-brands fa-facebook-f"></i>
              <span>PUP Biñan Campus</span>
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>

    <footer id="contact">
    <div class="container foot">
      <div class="footer-brand-block">
        <div class="footer-logo">
          <img src="../<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          <div>
            <p class="campus-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p>
            <p class="campus-sub">Bi&ntilde;an Campus</p>
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
      <span>&copy; <span id="year"></span> PUP Bi&ntilde;an Campus. All rights reserved.</span>

    </div>
  </footer>


</body>

</html>








