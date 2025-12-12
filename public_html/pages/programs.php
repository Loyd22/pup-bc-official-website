<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';

// Load settings functions
require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'programs.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Get program images from settings
$programImageKeys = [
    'program_image_BSBA',
    'program_image_BSIT',
    'program_image_BSEDEnglish',
    'program_image_BSEDSocialStudies',
    'program_image_BEED',
    'program_image_BSCPE',
    'program_image_BSIE',
    'program_image_BSPsychology',
    'program_image_DCET',
    'program_image_DIT'
];

$programImages = get_settings($conn, $programImageKeys);

// Default images fallback
$defaultImages = [
    'BSBA' => 'images/hrss1.jpg',
    'BSIT' => 'images/ibits1.jpg',
    'BSEDEnglish' => 'images/educ.png',
    'BSEDSocialStudies' => 'images/BSEDSS.png',
    'BEED' => 'images/educ1.jpg',
    'BSCPE' => 'images/aces1.jpg',
    'BSIE' => 'images/piie1.jpg',
    'BSPsychology' => 'images/psych1.jpg',
    'DCET' => 'images/pupbackrgound.jpg',
    'DIT' => 'images/pupbackrgound.jpg'
];

// Function to get program image path
function get_program_image($key, $programImages, $defaultImages) {
    $imageKey = 'program_image_' . $key;
    if (!empty($programImages[$imageKey])) {
        return $programImages[$imageKey];
    }
    return $defaultImages[$key] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Academic Programs - PUP Bi&ntilde;an Campus</title>
  <meta name="description" content="Explore academic programs offered at PUP Bi&ntilde;an Campus. UI-only, static HTML/CSS/JS." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/programs.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const filter = document.getElementById('progFilter');
      if (!filter) return;
      const cards = Array.from(document.querySelectorAll('.prog-card'));
      filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        cards.forEach(card => {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(q) ? '' : 'none';
        });
      });
    });
  </script>
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
        <span class="c">Bi&ntilde;an Campus</span>
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
            <a class="is-active" href="./programs.php">Academic Programs</a>
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
        <a href="./programs.php" class="mobile-nav-link is-active">Academic Programs</a>
        <a href="./admission_guide.php" class="mobile-nav-link">Admissions</a>
        <a href="./services.php" class="mobile-nav-link">Student Services</a>
        <a href="./campuslife.php" class="mobile-nav-link">Campus Life</a>
        <a href="./contact.php" class="mobile-nav-link">Contact Us</a>
      </nav>
    </div>
  </header>
  
    <!-- HERO SECTION -->
    <section class="programs-page-hero">
      <div class="container">
        <div class="programs-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="programs-page-hero-text">
            <p class="programs-page-hero-label">ACADEMICS</p>
            <h1 class="programs-page-hero-title">Academic <span class="programs-page-hero-accent">Programs</span></h1>
            <p class="programs-page-hero-description">Explore our undergraduate offerings designed to deliver accessible, industry-aligned, and values-driven public education.</p>
          </div>
        </div>
      </div>
    </section>

      <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="../homepage.php">Home</a></li>
        <li><span>Programs</span></li>
      </ol>
    </nav>

    <section class="programs-section" id="offerings">
      <div class="container">
        <main id="content">
  
        <div class="courses-heading">
          <h2>Undergraduate Offerings</h2>
        </div>

        <div class="grid cols-3" role="list">
         
          <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSIT.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSIT', $programImages, $defaultImages)); ?>" alt="Students in the Information Technology program collaborating on computers" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Science in Information Technology</h3>
              </div>
            </div>
          </article>
            <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSIE.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSIE', $programImages, $defaultImages)); ?>" alt="Industrial Engineering majors evaluating a production workflow" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Science in Industrial Engineering</h3>
              </div>
            </div>
          </article>
            <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSCPE.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSCPE', $programImages, $defaultImages)); ?>" alt="Computer Engineering students testing hardware prototypes" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Science in Computer Engineering</h3>
              </div>
            </div>
          </article>
              <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSPsychology.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSPsychology', $programImages, $defaultImages)); ?>" alt="Psychology students studying human behavior" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Science in Psychology</h3>
              </div>
            </div>
          </article>
             <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSBA.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSBA', $programImages, $defaultImages)); ?>" alt="Business Administration learners discussing a business plan" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Science in Business Administration Major in Human Resource Management</h3>
              </div>
            </div>
          </article>
          <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSEDEnglish.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSEDEnglish', $programImages, $defaultImages)); ?>" alt="English education majors facilitating a discussion" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Secondary Education Major in English</h3>
              </div>
            </div>
          </article>
        
          <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BSEDSocialStudies.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BSEDSocialStudies', $programImages, $defaultImages)); ?>" alt="Social Studies majors presenting community research" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Secondary Education Major in Social Studies</h3>
              </div>
            </div>
          </article>
          <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/BEED.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('BEED', $programImages, $defaultImages)); ?>" alt="Elementary Education preservice teachers working with children" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Bachelor of Elementary Education</h3>
              </div>
            </div>
          </article>
        
        
      
        </div>
           <div class="courses-heading">
          <h2>Diploma Offerings</h2>
  </div>
        <div class="grid cols-3" role="list">
              <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/DIT.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('DIT', $programImages, $defaultImages)); ?>" alt="Information technology students collaborating on a project" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Diploma in Information Technology</h3>
              </div>
            </div>
          </article>
          <article class="card prog-card" role="listitem">
            <div class="body">
              <figure class="prog-media">
                <a href="../programsinfos/DCET.html">
                  <img src="../<?php echo htmlspecialchars(get_program_image('DCET', $programImages, $defaultImages)); ?>" alt="Computer engineering technology students in a hardware lab" />
                </a>
              </figure>
              <div class="prog-copy">
                <h3>Diploma in Computer Engineering Technology</h3>
              </div>
            </div>
          </article>
      
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
    </div>
  </footer>

</body>
</html>


