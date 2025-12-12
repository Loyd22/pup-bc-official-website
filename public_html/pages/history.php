<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'history');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Default images (fallback if database doesn't have custom images)
$default_images = [
    '../images/earlycampusphoto.jpg',
    '../images/BEED.png',
    '../images/pupsite.jpg',
    '../images/direk.png',
    '../images/BSIT.png'
];

// Get history images from database
$historyImageKeys = ['history_image_1', 'history_image_2', 'history_image_3', 'history_image_4', 'history_image_5'];
$dbImages = get_settings($conn, $historyImageKeys);

$history_items = [
    [
        'text' => 'The Polytechnic University of the Philippines – Biñan (PUP Biñan) traces its beginnings to a Memorandum of Agreement signed by the Municipality of Biñan and the Polytechnic University of the Philippines on 15 September 2009. 
        This partnership established the campus as part of PUP strategy to widen access to affordable, high‑quality public higher education in Laguna.',
        'image' => !empty($dbImages['history_image_1']) ? '../' . $dbImages['history_image_1'] : $default_images[0]
    ],
    
    [
        'text' => 'From the outset, PUP Biñan aligned its offerings with community and regional economic needs, opening programs in information technology, business, education, social sciences, and engineering fields that match Binan growing economy and talent pipeline.',
        'image' => !empty($dbImages['history_image_2']) ? '../' . $dbImages['history_image_2'] : $default_images[1]
    ],
    [
        'text' => 'Throughout the 2010s, PUP Biñan steadily expanded its reach with the combined support of the local government and the University. Upgrades to facilities and student services strengthened the campus role as a community anchored institution serving learners from Binan and nearby cities.',
        'image' => !empty($dbImages['history_image_3']) ? '../' . $dbImages['history_image_3'] : $default_images[2]
    ],
    [
        'text' => 'A major milestone came on 2 February 2024 during the city Araw ng Binan celebration when the local government inaugurated a second PUP Binan site in Barangay Canlalay. The new 2,500 m² facility, acquired for 150 million, houses a four storey academic building with 18 classrooms and four fully functioning laboratories built especially for STEM education.',
        'image' => !empty($dbImages['history_image_4']) ? '../' . $dbImages['history_image_4'] : $default_images[3]
    ],
    [
        'text' => 'Today, PUP Biñan stands as a vital member of the PUP network in Laguna. Anchored by its 2009 founding and marked by the 2024 expansion and the 2025 commencement milestone, the campus exemplifies PUP enduring commitment to accessible, practice oriented public education. 
        It remains focused on widening opportunity, cultivating local talent for regional industry, and delivering transformative education to students of Binan and beyond.',
        'image' => !empty($dbImages['history_image_5']) ? '../' . $dbImages['history_image_5'] : $default_images[4]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>History — PUP Biñan Campus</title>
  <meta name="description"
    content="History of PUP Biñan Campus — from 2009 founding to present day." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/history.css" />
  <link rel="stylesheet" href="../asset/css/history-gallery.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <script defer src="../asset/js/history-gallery.js"></script>
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

  <!-- Header / Nav (same structure and dropdown as home) -->
  <header role="banner" class="header">
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
            <a href="./services.php">Student Services</a>
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
        <a href="./services.php" class="mobile-nav-link">Student Services</a>
        <a href="./campuslife.php" class="mobile-nav-link">Campus Life</a>
        <a href="./contact.php" class="mobile-nav-link">Contact Us</a>
      </nav>
    </div>
  </header>

  <main id="content">
    <!-- HERO SECTION -->
    <section class="history-page-hero">
      <div class="container">
        <div class="history-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="history-page-hero-text">
            <h1 class="history-page-hero-title">History of <span class="history-page-hero-accent">PUP Biñan Campus</span></h1>
            <p class="history-page-hero-description">Explore the milestones and growth of PUP Biñan Campus from 2009 to present.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- HISTORY SECTION -->
    <section class="section" id="history">
      <div class="container history-gallery-container">

        <!-- Left side: Centered text carousel -->
        <div class="history-content">
          <h2><strong style="font-size:32px; font-family:'Times New Roman', Times, serif;">H</strong>ISTORY OF<br><p class="historyp"><strong style="font-size: 32px;">P</strong>OLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p><p class="historyp2"><strong style="font-size: 29PX;"> B</strong>iñan, CAMPUS</p></h2>
          <ul class="history-texts">
            <?php foreach ($history_items as $index => $item): ?>
              <li class="history-text <?php echo $index === 0 ? 'center' : ''; ?>" data-index="<?php echo $index; ?>">
                <span class="history-number"><?php echo $index + 1; ?></span>
                <span class="history-paragraph"><?php echo $item['text']; ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Right side: Gallery -->
        <div class="history-gallery">
          <?php foreach ($history_items as $index => $item): ?>
            <div class="history-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
              <img src="<?php echo $item['image']; ?>" alt="History Image <?php echo $index + 1; ?>">
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
  </main>

  <!-- Footer (same as homepage) -->
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
          <a href="./history.php">Campus History</a>
          <a href="../privacy-policy.php">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="sub container">
      <span>&copy; <span id="year"></span> PUP Biñan Campus. All rights reserved.</span>
    </div>
  </footer>
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>

</html>
