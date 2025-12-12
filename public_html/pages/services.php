<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'services.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'services');
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
  <title>Student Services - PUP Bi�an Campus</title>
  <meta name="description" content="Student Services at PUP Bi�an Campus: registrar, scholarships, guidance, library, and more." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/services.css" />
  <link rel="stylesheet" href="../asset/css/campuslife.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/campuslife.js"></script>
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
    <section class="page-hero">
      <div class="container">
        <div class="page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="page-hero-text">
            <p class="page-hero-label">STUDENT AFFAIRS AND SERVICES</p>
            <h1 class="page-hero-title">Student <span class="page-hero-accent">Services</span></h1>
            <p class="page-hero-description">PUP Biñan’s student services offer academic and personal support to help students succeed in the university.</p>
              <div class="page-hero-buttons">
                <a href="./view-campus-offices.php" class="page-btn-primary">View Campus Offices & Gallery</a>
                <a href="./student_handbook.php" class="page-btn-secondary">Student Handbook</a>
              </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CAMPUS OFFICES SECTION -->
    <section class="svc-offices" id="offices">
      <div class="container">
        <p class="svc-section-label">CAMPUS OFFICES</p>
        <h2 class="svc-section-title">Where to go for help</h2>
        <p class="svc-section-intro">These offices mirror the PUP Sta. Mesa student affairs and services setup, localized for PUP Biñan Campus. Please update contact details with official information.</p>
        <div class="svc-offices-grid">
          <article class="svc-office-card" id="admission-registrar">
            <h3 class="svc-office-title">Admission & Registrar</h3>
            <p class="svc-office-desc">PUPCET and admission, enrollment processing, subject loading, corrections, student records, COR, Form 137/138, TOR, and certifications used for employment or further studies.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">PUPCET/Admission</span>
              <span class="svc-badge">Enrollment</span>
              <span class="svc-badge">TOR & Records</span>
            </div>
          </article>
          <article class="svc-office-card" id="scholarships-grants">
            <h3 class="svc-office-title">Scholarships & Financial Assistance</h3>
            <p class="svc-office-desc">University and external scholarships, LGU and partner grants, allowance programs, and TES or other government-funded financial assistance for qualified students.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">Scholarships</span>
              <span class="svc-badge">Grants</span>
              <span class="svc-badge">TES/Aid</span>
            </div>
          </article>
          <article class="svc-office-card" id="guidance-counseling">
            <h3 class="svc-office-title">Guidance & Counseling</h3>
            <p class="svc-office-desc">Individual and group counseling, referrals, crisis support, and career guidance, as well as wellness and formation activities that promote holistic student development.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">Counseling</span>
              <span class="svc-badge">Career Guidance</span>
              <span class="svc-badge">Well-being</span>
            </div>
          </article>
          <article class="svc-office-card" id="library">
            <h3 class="svc-office-title">Library & Learning Resources</h3>
            <p class="svc-office-desc">Access to books, journals, online databases, and other learning materials, plus research assistance and study spaces that support coursework and thesis writing.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">Books</span>
              <span class="svc-badge">e-Resources</span>
              <span class="svc-badge">Research Help</span>
            </div>
          </article>
          <article class="svc-office-card" id="student-affairs">
            <h3 class="svc-office-title">Student Affairs & Services</h3>
            <p class="svc-office-desc">Recognized student organizations, councils, and campus activities, and helps implement orientation programs, formation activities, discipline, and student leadership development.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">Student Orgs</span>
              <span class="svc-badge">Leadership</span>
              <span class="svc-badge">Campus Life</span>
            </div>
          </article>
          <article class="svc-office-card" id="it-services">
            <h3 class="svc-office-title">IT Services</h3>
            <p class="svc-office-desc">Student accounts and access to PUP SIS, campus portals, official email, and on-campus Wi-Fi, and provides basic technical troubleshooting for students and offices.</p>
            <div class="svc-office-badges">
              <span class="svc-badge">SIS/Portals</span>
              <span class="svc-badge">Email</span>
              <span class="svc-badge">Wi-Fi Support</span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- FORMS BANNER -->
    <section class="svc-forms-banner">
      <div class="container">
        <div class="svc-forms-banner-inner">
          <p class="svc-forms-text">Download request forms, permits, scholarship templates, and other student documents used by PUP Biñan offices.</p>
          <a href="./forms.php" class="svc-btn-primary">Go to Forms</a>
        </div>
      </div>
    </section>

    <!-- FAQ LINK SECTION -->
    <section class="svc-faq-link" id="faqs">
      <div class="container">
        <p class="svc-section-label">QUICK ANSWERS</p>
        <h2 class="svc-section-title">Frequently asked questions</h2>
        <p class="svc-faq-link-description">Have questions? Find answers to common questions about admissions, enrollment, student services, and campus life.</p>
        <div class="svc-faq-link-wrapper">
          <a href="./faq.php" class="svc-faq-link-button">
            <span>View All FAQs</span>
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </section>
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
    /* Student Services Page Styles */
    
    /* Campus Offices Section */
    .svc-offices {
      padding: 4rem 0;
      background: #ffffff;
    }
    
    .svc-section-label {
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted);
      margin: 0 0 0.5rem;
    }
    
    .svc-section-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--ink);
      margin: 0 0 1rem;
    }
    
    .svc-section-intro {
      font-size: 1rem;
      line-height: 1.6;
      color: var(--muted);
      margin: 0 0 2.5rem;
      max-width: 800px;
    }
    
    .svc-offices-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
    }
    
    .svc-office-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.2s ease;
    }
    
    .svc-office-card:hover {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .svc-office-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--ink);
      margin: 0 0 0.75rem;
    }
    
    .svc-office-desc {
      font-size: 0.9375rem;
      line-height: 1.6;
      color: var(--muted);
      margin: 0 0 1rem;
    }
    
    .svc-office-badges {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .svc-badge {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      background: #f9fafb;
      color: var(--maroon);
      font-size: 0.8125rem;
      font-weight: 500;
      border: 1px solid #e5e7eb;
      border-radius: 0.25rem;
    }
    
    /* Forms Banner */
    .svc-forms-banner {
      padding: 2rem 0;
      background: #f9fafb;
    }
    
    .svc-forms-banner-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 2rem;
      padding: 1.5rem;
      border: 2px dashed #d1d5db;
      border-radius: 0.5rem;
      background: #ffffff;
    }
    
    .svc-forms-text {
      font-size: 0.9375rem;
      line-height: 1.6;
      color: var(--ink);
      margin: 0;
      flex: 1;
    }
    
    /* FAQ Link Section */
    .svc-faq-link {
      padding: 4rem 0;
      background: #ffffff;
      text-align: center;
    }
    
    .svc-faq-link-description {
      font-size: 1rem;
      line-height: 1.6;
      color: var(--muted);
      margin: 0 0 2rem;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .svc-faq-link-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .svc-faq-link-button {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      padding: 1rem 2rem;
      background: var(--maroon);
      color: #ffffff;
      font-size: 1rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: 0.5rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(122, 0, 25, 0.2);
    }
    
    .svc-faq-link-button:hover {
      background: var(--maroon-900);
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(122, 0, 25, 0.3);
    }
    
    .svc-faq-link-button i {
      transition: transform 0.3s ease;
    }
    
    .svc-faq-link-button:hover i {
      transform: translateX(4px);
    }
    
    /* Button styles for forms banner and other sections */
    .svc-btn-primary {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      background: var(--maroon);
      color: #ffffff;
      font-weight: 600;
      border-radius: 0.375rem;
      text-decoration: none;
      transition: background 0.2s ease;
    }
    
    .svc-btn-primary:hover {
      background: var(--maroon-900);
    }
    
    /* Responsive Design */
    @media (max-width: 1400px) {
      .svc-offices-grid {
        gap: 1.25rem;
      }
    }
    
    @media (max-width: 1200px) {
      .svc-offices-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
    }
    
    @media (max-width: 768px) {
      .svc-offices-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      
      
      .svc-forms-banner-inner {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
      }
      
      .svc-forms-banner-inner .svc-btn-primary {
        width: 100%;
        text-align: center;
      }
      
      .svc-section-title {
        font-size: 1.75rem;
      }
      
      .svc-offices,
      .svc-faq-link {
        padding: 2.5rem 0;
      }
    }
    
    @media (max-width: 640px) {
      .svc-section-title {
        font-size: 1.5rem;
      }
      
      .svc-section-intro {
        font-size: 0.9375rem;
      }
      
      .svc-offices {
        padding: 2rem 0;
      }
      
      .svc-office-card {
        padding: 1.25rem;
      }
      
      .svc-faq-link {
        padding: 2rem 0;
      }
      
      .svc-faq-link-button {
        padding: 0.875rem 1.5rem;
        font-size: 0.9375rem;
      }
      
      .svc-faq-link-description {
        font-size: 0.9375rem;
        margin-bottom: 1.5rem;
      }
      
      .svc-forms-banner {
        padding: 1.5rem 0;
      }
      
      .svc-forms-banner-inner {
        padding: 1.25rem;
      }
    }
  </style>

</body>
</html>
