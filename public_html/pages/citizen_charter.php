<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';

// Load settings functions
require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);
log_page_view($conn, 'citizen_charter');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Get citizen charter path from settings
$citizenCharterPath = get_setting($conn, 'citizen_charter_path', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Citizen Charter - PUP Biñan Campus</title>
  <meta name="description" content="Citizen Charter of PUP Biñan Campus - Our commitment to service excellence." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/forms.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <style>
    .citizen-charter-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .citizen-charter-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .citizen-charter-header h1 {
      font-size: 2.5rem;
      color: #7a0019;
      margin-bottom: 0.5rem;
    }
    
    .citizen-charter-header p {
      color: #6b7280;
      font-size: 1.1rem;
    }
    
    .citizen-charter-pdf-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .citizen-charter-pdf-wrapper {
      width: 100%;
      min-height: 600px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .citizen-charter-pdf-wrapper iframe {
      width: 100%;
      height: 800px;
      border: none;
    }
    
    .citizen-charter-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    
    .btn-download {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 12px 24px;
      background: linear-gradient(135deg, #7a0019, #540013);
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(122, 0, 25, 0.3);
    }
    
    .no-charter-message {
      text-align: center;
      padding: 3rem 1rem;
      background: #f9fafb;
      border-radius: 12px;
      border: 2px dashed #e5e7eb;
    }
    
    .no-charter-message i {
      font-size: 4rem;
      color: #9ca3af;
      margin-bottom: 1rem;
    }
    
    .no-charter-message h2 {
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .no-charter-message p {
      color: #6b7280;
    }
    
    @media (max-width: 768px) {
      .citizen-charter-header h1 {
        font-size: 2rem;
      }
      
      .citizen-charter-pdf-wrapper iframe {
        height: 600px;
      }
      
      .citizen-charter-actions {
        flex-direction: column;
      }
      
      .btn-download {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
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
    <section class="page-hero">
      <div class="container">
        <div class="page-hero-inner">
          <div class="page-hero-text">
            <p class="page-hero-label">STUDENT SERVICES</p>
            <h1 class="page-hero-title">Citizen <span class="page-hero-accent">Charter</span></h1>
            <p class="page-hero-description">Our commitment to service excellence and transparency in delivering quality education and student services.</p>
          </div>
        </div>
      </div>
    </section>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="../homepage.php">Home</a></li>
        <li><a href="./services.php">Student Services</a></li>
        <li><span>Citizen Charter</span></li>
      </ol>
    </nav>

    <section class="section">
      <div class="container">
        <div class="citizen-charter-container">
          <?php if (!empty($citizenCharterPath) && file_exists(dirname(__DIR__) . '/' . $citizenCharterPath)): ?>
            <div class="citizen-charter-header">
              <h1>Citizen Charter</h1>
              <p>PUP Biñan Campus Commitment to Service Excellence</p>
            </div>
            
            <div class="citizen-charter-pdf-container">
              <div class="citizen-charter-pdf-wrapper">
                <iframe src="../<?php echo htmlspecialchars($citizenCharterPath); ?>#toolbar=1" type="application/pdf">
                  <p>Your browser does not support PDFs. <a href="../<?php echo htmlspecialchars($citizenCharterPath); ?>" download>Download the Citizen Charter</a> instead.</p>
                </iframe>
              </div>
              
              <div class="citizen-charter-actions" style="margin-top: 1.5rem;">
                <a href="../<?php echo htmlspecialchars($citizenCharterPath); ?>" download class="btn-download">
                  <i class="fa-solid fa-download"></i>
                  Download Citizen Charter
                </a>
                <a href="../<?php echo htmlspecialchars($citizenCharterPath); ?>" target="_blank" class="btn-download" style="background: linear-gradient(135deg, #f3b233, #d79c2d);">
                  <i class="fa-solid fa-external-link-alt"></i>
                  Open in New Tab
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="no-charter-message">
              <i class="fa-solid fa-file-circle-question"></i>
              <h2>Citizen Charter Not Available</h2>
              <p>The Citizen Charter document is currently being prepared. Please check back soon or contact the administration office for more information.</p>
              <a href="./services.php" class="btn-download" style="margin-top: 1.5rem; display: inline-block;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Student Services
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer (same as homepage) -->
  <footer id="contact">
    <div class="container foot">
      <div class="footer-brand-block">
        <div class="footer-logo">
          <img src="../images/PUPLogo.png" alt="PUP Logo" />
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

</body>
</html>
