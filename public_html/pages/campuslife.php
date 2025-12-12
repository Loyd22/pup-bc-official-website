<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'campuslife');
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
  <title>Campus Life - PUP Bi�an Campus</title>
  <meta name="description" content="Campus life stories, student activities, and community highlights for PUP Bi�an Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/campuslife.css" />
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/traditions-carousel.js"></script>
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
            <a class="is-active" href="./campuslife.php">Campus Life</a>
            <a href="./contact.php">Contact Us</a>
          </nav>
        </div>
      </div>
      <form class="search-form" action="../search.php" method="get" role="search" aria-label="Site search">
        <input type="text" name="q" placeholder="Search..." aria-label="Search">
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
        <a href="./campuslife.php" class="mobile-nav-link is-active">Campus Life</a>
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
            <p class="page-hero-label">STUDENT EXPERIENCE</p>
            <h1 class="page-hero-title">Campus <span class="page-hero-accent">Life</span></h1>
            <p class="page-hero-description">Discover the vibrant student experience at PUP Biñan Campus, from organizations and activities to traditions and community engagement.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section campus-life-feature">
      <div class="container">
        <article class="card campus-life-card">
          <div class="campus-life-card-content">
            <div class="campus-life-text">
              <h2>Campus Life at PUP Biñan</h2>
              <p>PUP Biñan Campus in Laguna offers a vibrant campus life where being an “Iskolar ng Bayan” is felt not just inside the classroom but in everyday student experiences. Located in the fast-growing City of Biñan, the campus was established through a partnership between PUP and the local government and now operates in a newly constructed four-storey building designed to support modern teaching, learning, and student activities.</p>
              <p>Students are trained in fields such as information technology, business, education, social sciences, and engineering, with a strong focus on transformative education—helping them gain not only technical skills but also values that prepare them to be responsible and service-oriented professionals in the community.</p>
              <p>Surrounded by a clean, organized, and steadily improving campus environment, students experience a close-knit community where faculty, staff, and local government work together to keep education affordable, relevant, and aligned with the PUP mission of being the “Tanglaw ng Bayan” for the youth of Laguna and nearby provinces.</p>
            </div>
            <div class="campus-life-image">
              <img src="../images/campuslifemainphotojpg.jpg" alt="PUP Biñan Campus Life" />
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="section university-traditions" id="university-traditions">
      <div class="container">
        <h2 class="traditions-main-title">THE UNIVERSITY TRADITIONS</h2>
        
        <?php
        // Function to get images from file system folder (NOT from database)
        // Images are stored INSIDE pupbc-website/images/traditions/ (not outside)
        function get_tradition_images_from_folder(int $traditionId): array {
          // campuslife.php is in pages/, so go up 1 level to pupbc-website/
          // __DIR__ = pupbc-website/pages/
          // dirname(__DIR__) = pupbc-website/
          $baseDir = dirname(__DIR__); // pupbc-website/
          $traditionDir = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'traditions' . DIRECTORY_SEPARATOR . $traditionId;
          
          $images = [];
          if (is_dir($traditionDir)) {
            $files = scandir($traditionDir);
            foreach ($files as $file) {
              if ($file === '.' || $file === '..') continue;
              $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
              if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                // Path relative to campuslife.php: ../images/traditions/{id}/{file}
                // From pages/ -> ../ = pupbc-website/
                $images[] = '../images/traditions/' . $traditionId . '/' . $file;
              }
            }
            sort($images);
          }
          return $images;
        }
        
        // Fetch traditions from database (ONLY text fields - no images)
        // Order by display_order: lower numbers appear first (top), higher numbers appear later (bottom)
        // NULL values are treated as 999999 so they appear at the bottom
        // Use CAST to ensure numeric sorting
        $traditionsQuery = $conn->query("
          SELECT id, subtitle, title, display_order
          FROM traditions
          WHERE is_active = 1
          ORDER BY COALESCE(CAST(display_order AS UNSIGNED), 999999) ASC, id ASC
        ");
        
        if ($traditionsQuery && $traditionsQuery->num_rows > 0):
          $traditionIndex = 0;
          while ($tradition = $traditionsQuery->fetch_assoc()):
            // Get images directly from file system folder (NOT from database)
            $images = get_tradition_images_from_folder((int)$tradition['id']);
            $traditionSlug = 'tradition-' . $tradition['id'];
            $traditionIndex++;
        ?>
          <div class="tradition-section">
            <h3 class="tradition-subtitle"><?php echo htmlspecialchars($tradition['subtitle'], ENT_QUOTES); ?></h3>
            <div class="tradition-divider"></div>
            <?php if (!empty($images)): ?>
              <div class="tradition-carousel-wrapper">
                <div class="tradition-carousel-track" data-tradition="<?php echo htmlspecialchars($traditionSlug, ENT_QUOTES); ?>">
                  <?php foreach ($images as $image): ?>
                    <div class="tradition-image"
                         data-full="<?php echo htmlspecialchars($image, ENT_QUOTES); ?>"
                         data-alt="<?php echo htmlspecialchars($tradition['subtitle'], ENT_QUOTES); ?>"
                         tabindex="0"
                         role="button"
                         aria-label="Open image in larger view: <?php echo htmlspecialchars($tradition['subtitle'], ENT_QUOTES); ?>">
                      <img src="<?php echo htmlspecialchars($image, ENT_QUOTES); ?>" 
                           alt="<?php echo htmlspecialchars($tradition['subtitle'], ENT_QUOTES); ?>" />
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <!-- Show placeholder if no images uploaded yet -->
              <div class="tradition-carousel-wrapper">
                <div class="tradition-carousel-track" data-tradition="<?php echo htmlspecialchars($traditionSlug, ENT_QUOTES); ?>">
                  <div class="tradition-image" style="display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: #6b7280; min-height: 200px;">
                    <div style="text-align: center; padding: 2rem;">
                      <p style="margin: 0; font-size: 0.875rem;">No images uploaded yet</p>
                      <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #9ca3af;">Upload images via admin panel</p>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
            <h4 class="tradition-title"><?php echo htmlspecialchars($tradition['title'], ENT_QUOTES); ?></h4>
          </div>
        <?php
          endwhile;
          $traditionsQuery->free();
        else:
        ?>
          <!-- Fallback content if no traditions in database -->
          <div class="tradition-section">
            <h3 class="tradition-subtitle">Freshmen Orientation</h3>
            <div class="tradition-divider"></div>
            <div class="tradition-carousel-wrapper">
              <div class="tradition-carousel-track" data-tradition="orientation">
                <div class="tradition-image"
                     data-full="../images/event.jpg"
                     data-alt="Freshmen Orientation Event"
                     tabindex="0"
                     role="button"
                     aria-label="Open image in larger view: Freshmen Orientation Event">
                  <img src="../images/event.jpg" alt="Freshmen Orientation Event" />
                </div>
                <div class="tradition-image"
                     data-full="../images/campuslifemainphotojpg.jpg"
                     data-alt="Student Welcome"
                     tabindex="0"
                     role="button"
                     aria-label="Open image in larger view: Student Welcome">
                  <img src="../images/campuslifemainphotojpg.jpg" alt="Student Welcome" />
                </div>
                <div class="tradition-image"
                     data-full="../images/organizationjpg.jpg"
                     data-alt="Orientation Activities"
                     tabindex="0"
                     role="button"
                     aria-label="Open image in larger view: Orientation Activities">
                  <img src="../images/organizationjpg.jpg" alt="Orientation Activities" />
                </div>
              </div>
            </div>
            <h4 class="tradition-title">The Iskolar ng Bayan Welcome</h4>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <div class="tradition-lightbox" id="traditionLightbox" aria-hidden="true">
      <button type="button" class="tradition-lightbox-close" id="traditionLightboxClose" aria-label="Close image preview">&times;</button>
      <img src="" alt="" id="traditionLightboxImage">
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const traditionItems = document.querySelectorAll('.tradition-image');
      const lightbox = document.getElementById('traditionLightbox');
      const lightboxImg = document.getElementById('traditionLightboxImage');
      const lightboxClose = document.getElementById('traditionLightboxClose');

      function openLightbox(src, altText) {
        if (!lightbox || !lightboxImg || !src) return;
        lightboxImg.src = src;
        lightboxImg.alt = altText || 'Tradition image';
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

      traditionItems.forEach(item => {
        const img = item.querySelector('img');
        const src = img ? img.getAttribute('src') : item.getAttribute('data-full');
        const altText = img ? img.getAttribute('alt') : item.getAttribute('data-alt');

        if (!src) {
          return;
        }

        item.addEventListener('click', () => openLightbox(src, altText));
        item.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openLightbox(src, altText);
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
