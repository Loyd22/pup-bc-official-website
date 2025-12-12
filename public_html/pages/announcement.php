<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/announcement_helpers.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'announcement');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Helper functions moved to includes/announcement_helpers.php

// Fetch all announcements from events table (single source of truth)
// Only show items where show_in_announcement = 1
function fetchAdminAnnouncements(mysqli $conn, int $limit = 100): array
{
  return fetchAnnouncementsFromEvents($conn, $limit, false);
}

// Fetch announcements from Admin → Announcements
$allItems = fetchAdminAnnouncements($conn);

// Fetch distinct authors for filter pills
$distinctAuthors = fetchDistinctAuthors($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Announcements - PUP Biñan Campus</title>
  <meta name="description" content="All announcements and events for PUP Biñan Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/home.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/chatbot.js"></script>
</head>
<body class="announcement-page">

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
            <a href="./admission_guide.php">Admissions</a>
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
          <!-- Left Side: Text Content -->
          <div class="page-hero-text">
            <p class="page-hero-label">CAMPUS UPDATES</p>
            <h1 class="page-hero-title">Announcements <span class="page-hero-accent">&amp; Events</span></h1>
            <p class="page-hero-description">Stay updated with the latest announcements and upcoming campus events at PUP Biñan Campus.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="announcements">
      <div class="container">
        <article class="announcements-page-card" aria-labelledby="annoHeading">
          <div class="announcements-page-header">
            <span class="announcements-page-label">CAMPUS UPDATES</span>
            <h2 id="annoHeading">All Announcements &amp; Events</h2>
            <p class="announcements-page-subtitle">Stay updated with the latest campus announcements, academic reminders, and upcoming activities.</p>
          </div>
          
          <div class="announcements-filters">
            <button class="filter-pill active" data-filter="all">All</button>
            <?php foreach ($distinctAuthors as $author): ?>
              <button class="filter-pill" data-filter="<?php echo htmlspecialchars($author); ?>"><?php echo htmlspecialchars($author); ?></button>
            <?php endforeach; ?>
          </div>
          
          <div class="announcements-page-list">
            <?php if (!empty($allItems)): ?>
              <?php foreach ($allItems as $item): ?>
                <?php
                $author = $item['author'] ?? '';
                $authorName = $item['author_name'] ?? ($item['author'] ?? '');
                // Use the same logic as fetchDistinctAuthors for consistency
                $authorDisplay = !empty($authorName) ? $authorName : $author;
                $displaySource = !empty($authorName) ? $authorName : strtoupper(str_replace('_', ' ', $item['display_source'] ?? ''));
                $categoryDisplay = strtoupper(str_replace('_', ' ', $item['category'] ?? ''));
                ?>
                <div class="announcement-page-card" id="ann-<?php echo htmlspecialchars($item['id']); ?>" data-author="<?php echo htmlspecialchars($authorDisplay); ?>">
                  <div class="announcement-page-card-accent"></div>
                  <div class="announcement-page-card-content">
                    <div class="announcement-page-card-badges">
                      <span class="announcement-page-badge-category"><?php echo htmlspecialchars($categoryDisplay); ?></span>
                    </div>
                    <h3 class="announcement-page-card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <div class="announcement-page-card-meta">
                      <span class="announcement-page-card-date">
                        Event: <?php echo htmlspecialchars(formatDate($item['start_date'])); ?>
                        <?php if (!empty($item['location'])): ?>
                          &middot; <?php echo htmlspecialchars($item['location']); ?>
                        <?php endif; ?>
                      </span>
                    </div>
                    <?php if (!empty($item['description'])): ?>
                      <?php
                      // Show full description (not truncated) - render as HTML since it comes from rich text editor
                      // Allow safe HTML tags for formatting
                      $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><div><span><blockquote>';
                      $fullDescription = strip_tags($item['description'], $allowedTags);
                      ?>
                      <div class="announcement-page-card-description"><?php echo $fullDescription; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($displaySource)): ?>
                      <p class="announcement-page-card-author">Posted by: <?php echo htmlspecialchars($displaySource); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="announcements-page-empty">No announcements at the moment.</p>
            <?php endif; ?>
          </div>
        </article>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const filterPills = document.querySelectorAll('.filter-pill');
  const announcementCards = document.querySelectorAll('.announcement-page-card');
  
  filterPills.forEach(pill => {
    pill.addEventListener('click', function() {
      // Update active state
      filterPills.forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      
      const filter = this.getAttribute('data-filter');
      
      // Filter cards by author
      announcementCards.forEach(card => {
        const cardAuthor = card.getAttribute('data-author');
        if (filter === 'all' || cardAuthor === filter) {
          card.classList.remove('filtered-out');
        } else {
          card.classList.add('filtered-out');
        }
      });
    });
  });
  
  // Handle anchor scrolling when page loads with hash
  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const targetElement = document.getElementById(hash);
    if (targetElement) {
      setTimeout(() => {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Highlight the target element permanently
        targetElement.style.transition = 'box-shadow 0.3s ease';
        targetElement.style.boxShadow = '0 0 0 4px rgba(122, 0, 25, 0.3)';
        targetElement.classList.add('announcement-highlighted');
      }, 100);
    }
  }
});
</script>

</body>
</html>

