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
log_page_view($conn, 'student_handbook');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Get student handbook path from settings
$studentHandbookPath = get_setting($conn, 'student_handbook_path', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Handbook - PUP Biñan Campus</title>
  <meta name="description" content="Student Handbook of PUP Biñan Campus - Policies, guidelines, and student resources." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/forms.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <style>
    .handbook-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .handbook-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .handbook-header h1 {
      font-size: 2.5rem;
      color: #7a0019;
      margin-bottom: 0.5rem;
    }
    
    .handbook-header p {
      color: #6b7280;
      font-size: 1.1rem;
    }
    
    .handbook-pdf-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .handbook-pdf-wrapper {
      width: 100%;
      min-height: 600px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .handbook-pdf-wrapper iframe {
      width: 100%;
      height: 800px;
      border: none;
    }
    
    .handbook-actions {
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
    
    .no-handbook-message {
      text-align: center;
      padding: 3rem 1rem;
      background: #f9fafb;
      border-radius: 12px;
      border: 2px dashed #e5e7eb;
    }
    
    .no-handbook-message i {
      font-size: 4rem;
      color: #9ca3af;
      margin-bottom: 1rem;
    }
    
    .no-handbook-message h2 {
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .no-handbook-message p {
      color: #6b7280;
    }
    
    @media (max-width: 768px) {
      .handbook-header h1 {
        font-size: 2rem;
      }
      
      .handbook-pdf-wrapper iframe {
        height: 600px;
      }
      
      .handbook-actions {
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
            <span class="c">Bi¤an Campus</span>
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
    <div class="handbook-container">
      <div class="handbook-header">
        <p class="page-hero-label">STUDENT RESOURCES</p>
        <h1>Student Handbook</h1>
        <p>Review student policies, campus guidelines, and services that support your PUP Bi¤an experience.</p>
      </div>

      <?php if ($studentHandbookPath && file_exists(dirname(__DIR__) . '/' . $studentHandbookPath)): ?>
        <div class="handbook-pdf-container">
          <div class="handbook-pdf-wrapper" role="region" aria-label="Student Handbook PDF preview">
            <iframe src="../<?php echo htmlspecialchars($studentHandbookPath); ?>" title="Student Handbook PDF"></iframe>
          </div>
        </div>
        <div class="handbook-actions">
          <a class="btn-download" href="../<?php echo htmlspecialchars($studentHandbookPath); ?>" download>
            <i class="fa-solid fa-download" aria-hidden="true"></i>
            <span>Download Student Handbook</span>
          </a>
          <a class="btn-download" href="../<?php echo htmlspecialchars($studentHandbookPath); ?>" target="_blank" rel="noopener">
            <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
            <span>Open in New Tab</span>
          </a>
        </div>
      <?php else: ?>
        <div class="no-handbook-message">
          <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
          <h2>Student Handbook Not Available</h2>
          <p>The Student Handbook is currently being prepared. Please check back soon or contact the administration office for assistance.</p>
        </div>
      <?php endif; ?>
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
            <p class="campus-sub">Bi¤an Campus</p>
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
      <span>&copy; <span id="year"></span> PUP Bi¤an Campus. All rights reserved.</span>
      <span>For demo/UI purposes only.</span>
    </div>
  </footer>
</body>
</html>

