<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'admission_guide.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'admission_guide');
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
  <title>Admission Guide - PUP Bi�an Campus</title>
  <meta name="description" content="Step-by-step admission guide on how to apply to PUP Bi�an Campus. UI-only, static HTML/CSS/JS." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/admissions.css" />
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
            <a class="is-active" href="./admission_guide.php">Admission</a>
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
        <a href="./admission_guide.php" class="mobile-nav-link is-active">Admissions</a>
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
          <!-- Left Side: Text Content -->
          <div class="page-hero-text">
            <p class="page-hero-label">ADMISSIONS</p>
            <h1 class="page-hero-title">Admission <span class="page-hero-accent">Guide</span></h1>
            <p class="page-hero-description">Follow these steps to apply to the Polytechnic University of the Philippines — Biñan Campus. This page summarizes the process, requirements, and key dates.</p>
          </div>
        </div>
      </div>
    </section>
    
    <section class="hero-old" style="display:none;">
      <div class="container hero-inner">
        <div>

        <!--
        <aside class="hero-card" aria-label="Application tips">
          <div class="head">Before You Start</div>
          <div class="list">
            <span class="pill"><span class="date">Tip</span> Prepare clear scans of your documents.</span>
            <span class="pill"><span class="date">Tip</span> Use a personal, active email address.</span>
            <span class="pill"><span class="date">Tip</span> Check program-specific requirements.</span>
          </div>
        </aside>
        -->
      </div>
    </section>

    <!-- HOW TO APPLY SECTION -->
    <section class="pup-how-to-apply">
      <div class="pup-hta-header">
        <div class="pup-hta-title-block">
          <span class="pup-hta-subtitle">PUP College Entrance Test</span>
          <h2>How to Apply for PUPCET</h2>
        </div>
      </div>

      <div class="apply-step step-1">
        <div class="step-pill">
          <span class="step-number">1</span>
          <span class="step-text">Prepare your requirements</span>
        </div>
        <p>
          Scan your <strong>2×2 ID photo with name tag</strong> and your
          <strong>Grade 11 report card</strong> (with complete name, LRN, and GWA for both semesters)
          and save them as <strong>JPEG, max 300 KB</strong> per file.
        </p>
      </div>

      <div class="apply-step step-2">
        <div class="step-pill">
          <span class="step-number">2</span>
          <span class="step-text">Go to PUP iApply</span>
        </div>
        <p>
          Visit
          <a href="https://www.pup.edu.ph/iapply/pupcet" target="_blank" rel="noopener">
            https://www.pup.edu.ph/iapply/pupcet
          </a>
          and read the details, then click <strong>Apply Now</strong>.
        </p>
      </div>

      <div class="apply-step step-3">
        <div class="step-pill">
          <span class="step-number">3</span>
          <span class="step-text">Create your account</span>
        </div>
        <p>
          Click <strong>Register Here</strong>, accept the service agreement, select
          <strong>PUPCET</strong>, answer the prequalification questions, and complete the
          registration form using your correct name, date of birth, and active email.
        </p>
      </div>

      <div class="apply-step step-4">
        <div class="step-pill">
          <span class="step-number">4</span>
          <span class="step-text">Fill out the application form</span>
        </div>
        <p>
          Sign in to PUP iApply, open <strong>Application Form</strong>, enter all required
          information, type the digital security code and your full name as digital
          signature, then check the confirmation box and click
          <strong>Finalize Application</strong>.
        </p>
      </div>

      <div class="apply-step step-5">
        <div class="step-pill">
          <span class="step-number">5</span>
          <span class="step-text">Print your ePermit</span>
        </div>
        <p>
          After about <strong>6 to 20 working days</strong>, sign in again and click
          <strong>Print ePermit</strong>. Download and print your ePermit in color and
          bring it on your examination date.
        </p>
      </div>
    </section>

    <!-- QUALIFICATIONS AND APPLICATION INFO -->
    <section class="pup-qualifications-info">
      <div class="pup-qi-header">
        <div class="pup-qi-title-block">
          <span class="pup-qi-subtitle">PUPCET Eligibility</span>
          <h2>Who are qualified to take the PUP College Entrance Test (PUPCET)?</h2>
        </div>
      </div>

      <div class="qualification-item">
        <div class="qualification-pill">
          <span class="qualification-icon">✓</span>
          <span class="qualification-label">Qualified to apply are:</span>
        </div>
        <div class="qualification-content">
          <ul class="qualification-list">
            <li>A Grade 12 student expected to graduate at the end of AY 2024-2025; and those who graduated from K-12 pilot schools and have not enrolled in any technical/diploma/degree programs after graduation with a GWA of not lower than 82%</li>
            <li>Passer of PEPT/ALS or NFEA & E Program following DepEd regulations and therefore certified eligible for admission to college/tertiary level</li>
          </ul>
        </div>
      </div>

      <div class="qualification-item">
        <div class="qualification-pill">
          <span class="qualification-icon">📍</span>
          <span class="qualification-label">Where to apply?</span>
        </div>
        <div class="qualification-content">
          <ul class="qualification-list">
            <li>All PUPCET applicants must apply online using <strong>PUP iApply</strong> (read step-by-step procedure)</li>
            <li>An applicant is allowed to apply and take the PUPCET in only <strong>one (1) PUP Branch/Campus</strong>, and only once this academic year</li>
            <li><strong>Multiple application</strong> will make the applicant's PUPCET result null and void</li>
            <li>PUPCET application is <strong>non-transferrable</strong></li>
          </ul>
        </div>
      </div>
    </section>

    <!-- REQUIREMENTS -->
    <section class="pup-requirements">
      <div class="pup-req-header">
        <div class="pup-req-title-block">
          <span class="pup-req-subtitle">Application Documents</span>
          <h2>General Requirements</h2>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">📄</span>
          <span class="requirement-text">Completed application form (via iApply)</span>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">📋</span>
          <span class="requirement-text">PSA Birth Certificate</span>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">📷</span>
          <span class="requirement-text">Recent 2x2 ID photo (white background)</span>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">🆔</span>
          <span class="requirement-text">Valid ID (student/school ID or government ID)</span>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">📜</span>
          <span class="requirement-text">Good Moral Certificate</span>
        </div>
      </div>

      <div class="requirement-item">
        <div class="requirement-pill">
          <span class="requirement-icon">📊</span>
          <span class="requirement-text">Form 138/Report Card (for incoming freshmen) or TOR/Grades (for transferees)</span>
        </div>
      </div>
    </section>

    <!-- TRANSFER ADMISSION GUIDELINES -->
    <section class="pup-transfer-admission">
      <div class="pup-ta-header">
        <div class="pup-ta-title-block">
          <span class="pup-ta-subtitle">Transfer Students</span>
          <h2>Guidelines for Transfer Admission</h2>
        </div>
      </div>

      <div class="transfer-category">
        <div class="transfer-category-header">
          <h3>Within PUP System</h3>
        </div>
        <div class="transfer-content">
          <p class="transfer-intro">A student seeking transfer from a PUP branch/campus to another branch/campus within the PUP System may be admitted depending on the availability of slots. Also, the student must:</p>
          <ul class="transfer-list">
            <li>Have a commendation from the Director or Dean where he/she came from;</li>
            <li>Have completed at least two (2) semesters;</li>
            <li>Have no failing grade, dropped and withdrawn mark in any academic subject;</li>
            <li>Have met the college academic course/program requirements;</li>
            <li>Have submitted the following requirement to the Admission Services:</li>
          </ul>
          <ol class="transfer-requirements-list">
            <li>Transfer Credential/Honorable Dismissal</li>
            <li>Certification of Grades/Transcript of Records for evaluation purposes</li>
            <li>PSA Birth Certificate (Original)</li>
            <li>2x2 Picture with nametag (First Name, Middle Name, Last Name)</li>
            <li>Certification of Good Moral Character with school dry seal</li>
          </ol>
        </div>
      </div>

      <div class="transfer-category">
        <div class="transfer-category-header">
          <h3>From another College or University</h3>
        </div>
        <div class="transfer-content">
          <p class="transfer-intro">A student seeking transfer from another school or university to PUP may be admitted, subject to the availability of slots and upon approval of the University President or his duly authorized representative. Also, the student must:</p>
          <ul class="transfer-list">
            <li>Have completed two (2) semesters or one (1) year</li>
            <li>Have a weighted average of 2.0 or better with no failed, dropped or withdrawn subjects;</li>
            <li>Have met the college academic program/course requirements; and</li>
            <li>Have submitted the abovementioned admission credentials</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ENROLLMENT PROCEDURE -->
    <section class="pup-enrollment-procedure">
      <div class="pup-ep-header">
        <div class="pup-ep-title-block">
          <span class="pup-ep-subtitle">Enrollment Process</span>
          <h2>Enrollment Procedure</h2>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 1</span>
          <h3 class="enrollment-step-title">Submission of admission credentials at the Admission and Registration Services Section</h3>
        </div>
        <div class="enrollment-step-content">
          <p class="requirements-label">Requirements:</p>
          <ul class="enrollment-requirements-list">
            <li>Original High School Card (Grade 12 Card) or Certification of Grades signed by Principal or Registrar</li>
            <li>Original or Certified True Copy of Grade 10 and 11 Card</li>
            <li>Original PSA Birth Certificate</li>
            <li>Certification of Good Moral Character</li>
            <li>Duly signed Waiver/Certification/Undertaking</li>
            <li>SAR Form with 2x2 picture</li>
            <li>Route and Approval Slip</li>
            <li>Long Brown Envelope</li>
          </ul>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 2</span>
          <h3 class="enrollment-step-title">College Interview and Tagging of Program</h3>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 3</span>
          <h3 class="enrollment-step-title">Medical Examination</h3>
        </div>
        <div class="enrollment-step-content">
          <p class="requirements-label">Requirements:</p>
          <ul class="enrollment-requirements-list">
            <li>Chest X-Ray result Film/Print-out (not more than 6 months from the scheduled enrollment)</li>
            <li>Duly accomplished Health Information Form for Students</li>
          </ul>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 4</span>
          <h3 class="enrollment-step-title">Printing of Certificate of Registration</h3>
        </div>
        <div class="enrollment-step-content">
          <p class="requirements-label">Requirements:</p>
          <ul class="enrollment-requirements-list">
            <li>Duly signed and approved Route and Approval Slip</li>
          </ul>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 5</span>
          <h3 class="enrollment-step-title">Printing and Claiming of Identification Card</h3>
        </div>
        <div class="enrollment-step-content">
          <p class="requirements-label">Requirements:</p>
          <ul class="enrollment-requirements-list">
            <li>Duly signed and approved Certificate of Registration</li>
          </ul>
        </div>
      </div>

      <div class="enrollment-step-item">
        <div class="enrollment-step-header">
          <span class="enrollment-step-number">STEP 6</span>
          <h3 class="enrollment-step-title">(FOR ENTRANCE SCHOLARS ONLY): Recording of Entrance Qualification at the Office of the Scholarship and Financial Assistance</h3>
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

</body>
</html>
