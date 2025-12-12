<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'faq.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'faq');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FAQs - PUP Biñan Campus</title>
  <meta name="description" content="Frequently Asked Questions for PUP Biñan Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/faq.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <script defer src="../asset/js/faq.js"></script>
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
        <span class="c">Biñan Campus</span>
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
    <section class="faq-page-hero">
      <div class="container">
        <div class="faq-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="faq-page-hero-text">
            <p class="faq-page-hero-label">HELP & SUPPORT</p>
            <h1 class="faq-page-hero-title">Frequently Asked <span class="faq-page-hero-accent">Questions</span></h1>
            <p class="faq-page-hero-description">Find quick answers to common questions about admissions, enrollment, student services, and campus life at PUP Biñan Campus.</p>
          </div>
        </div>
      </div>
    </section>

   <section class="faq-container">
  <div class="faq-item">
   <button class="faq-question" aria-expanded="false">
    <p>Where can I see official announcements and advisories?</p>
    <i class="fa-solid fa-chevron-down faq-icon"></i>
</button>

    <div class="faq-answer">
      <p>
        Official announcements, enrollment advisories, and schedules are posted on the PUP website (Announcements / Students section) and the official campus Facebook pages. Always refer to these channels for verified information.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>How do I request a COR, TOR, diploma, or other student records?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        Requests for Certificate of Registration (COR), Transcript of Records (TOR), diplomas, and certifications are processed through the Online Document Request System (ODRS) and the Office of the University Registrar / Campus Registrar. Submit your request via ODRS and follow the posted instructions for payment and claiming of documents.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>How do I follow up on my document request or records concern?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        For follow-ups on ODRS requests and student records, coordinate with the Registrar's Office indicated in your ODRS transaction, or contact the university / campus through the official channels listed on the website.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>How can I apply for scholarships or financial assistance (including TES and other grants)?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        Scholarships and financial assistance are handled by the Scholarship and Financial Assistance Services (SFAS). Watch for official scholarship calls on the website and campus social media, then submit requirements and application forms following the guidelines and deadlines.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>Who can I approach for admission, enrollment, or general student concerns?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        For admission and enrollment concerns, coordinate with the Admission Services Office / Registrar's Office or submit a ticket through SINTA (Student Support), which handles queries on admission, enrollment, and library processes.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>Who can I approach for personal, guidance, or counseling needs?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        For personal, family, or psychosocial concerns, you may visit or contact the Guidance and Counseling Office of your campus. They provide counseling and support services to students.
      </p>
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" aria-expanded="false">
      <p>What online services should students know about?</p>
      <i class="fa-solid fa-chevron-down faq-icon"></i>
    </button>
    <div class="faq-answer">
      <p>
        Students commonly use the following systems: SINTA (student support and FAQs), ODRS (online document requests), iApply (applications), and SIS for Students (class list, grades, and other student records). Links to these are listed under Online Services on the website.
      </p>
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
