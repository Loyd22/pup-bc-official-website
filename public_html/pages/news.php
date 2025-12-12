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
log_page_view($conn, 'news');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Function to fetch all published news
function fetchAllNews(mysqli $conn): array {
  $sql = "SELECT n.id, n.title, n.summary, n.image_path, n.publish_date, n.body,
                 a.full_name as author_name
            FROM news n
            LEFT JOIN admins a ON n.created_by = a.id
            WHERE n.is_published = 1
            ORDER BY COALESCE(n.publish_date, n.created_at) DESC";

  $result = $conn->query($sql);
  if (!$result) {
    return [];
  }

  $items = [];
  while ($row = $result->fetch_assoc()) {
    $items[] = $row;
  }
  $result->free();

  return $items;
}

// Function to format date
function formatDate(?string $date): string {
  if (!$date) {
    return 'Draft';
  }

  $timestamp = strtotime($date);
  return $timestamp ? date('M j, Y', $timestamp) : $date;
}

// Function to create excerpt
function excerpt(string $text, int $limit = 150): string {
  $clean = trim(strip_tags($text));
  if ($clean === '') {
    return '';
  }

  if (function_exists('mb_strlen')) {
    if (mb_strlen($clean) <= $limit) {
      return $clean;
    }
    return rtrim(mb_substr($clean, 0, $limit - 3)) . '...';
  }

  if (strlen($clean) <= $limit) {
    return $clean;
  }

  return rtrim(substr($clean, 0, $limit - 3)) . '...';
}

$allNews = fetchAllNews($conn);

// Pagination settings - 9 items per page (3 rows x 3 columns)
$itemsPerPage = 9;
$totalItems = count($allNews);
$totalPages = ceil($totalItems / $itemsPerPage);

// Get current page from URL, default to 1
$currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;

// Calculate offset and get items for current page
$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedNews = array_slice($allNews, $offset, $itemsPerPage);

// Calculate display range
$startItem = $offset + 1;
$endItem = min($offset + $itemsPerPage, $totalItems);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>All News - PUP Biñan Campus</title>
  <meta name="description" content="Latest news and updates from PUP Biñan Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/home.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/chatbot.js"></script>
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
        <input type="text" name="q" placeholder="Search..." aria-label="Search">
      </form>
    </div>
  </header>

  <main id="content">
    <section class="section" id="all-news">
      <div class="container">
        <article class="card news-wrapper" aria-labelledby="allNewsHeading">
          <div class="news-section-header">
            <span class="news-label">• ALL NEWS</span>
            <h2 class="news-heading" id="allNewsHeading">All News from PUP Biñan</h2>
            <p class="news-intro">Stay updated with the latest news and announcements from PUP Biñan Campus.</p>
          </div>
          <div class="body">
            <?php if (!empty($paginatedNews)): ?>
              <div class="news-grid news-grid-all">
                <?php foreach ($paginatedNews as $news): ?>
                  <article class="news-card news-card-all">
                    <div class="news-img">
                      <img src="../<?php echo htmlspecialchars($news['image_path'] ?: 'images/pupbackrgound.jpg'); ?>"
                          alt="<?php echo htmlspecialchars($news['title']); ?>">
                    </div>
                    <div class="news-content">
                      <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                      <div class="news-date-category">
                        <span class="news-date"><?php echo htmlspecialchars(formatDate($news['publish_date'])); ?></span>
                        <span class="news-date-separator">•</span>
                        <span class="news-category-inline"><?php 
                          // Extract category from title or use default
                          $category = 'Campus update';
                          $titleLower = strtolower($news['title']);
                          if (strpos($titleLower, 'building') !== false || strpos($titleLower, 'campus') !== false || strpos($titleLower, 'facility') !== false || strpos($titleLower, 'inauguration') !== false) {
                            $category = 'Campus development';
                          } elseif (strpos($titleLower, 'research') !== false || strpos($titleLower, 'innovation') !== false || strpos($titleLower, 'grant') !== false || strpos($titleLower, 'forum') !== false) {
                            $category = 'Research & innovation';
                          } elseif (strpos($titleLower, 'student') !== false || strpos($titleLower, 'leadership') !== false || strpos($titleLower, 'training') !== false) {
                            $category = 'Student affairs';
                          } elseif (strpos($titleLower, 'library') !== false) {
                            $category = 'Library services';
                          } elseif (strpos($titleLower, 'career') !== false || strpos($titleLower, 'webinar') !== false) {
                            $category = 'Career development';
                          } elseif (strpos($titleLower, 'cultural') !== false || strpos($titleLower, 'event') !== false) {
                            $category = 'Campus life';
                          } elseif (strpos($titleLower, 'partnership') !== false || strpos($titleLower, 'visit') !== false || strpos($titleLower, 'house') !== false) {
                            $category = 'Institutional partnership';
                          }
                          echo htmlspecialchars($category);
                        ?></span>
                      </div>
                      <p class="news-summary"><?php echo htmlspecialchars(excerpt($news['summary'], 150)); ?></p>
                      <div class="news-card-footer">
                        <a href="news_detail.php?id=<?php echo (int)$news['id']; ?>" class="news-read-more-btn">Read more</a>
                        <span class="news-footer-tag"><?php 
                          // Footer tag based on category
                          $footerTag = 'Campus update';
                          $titleLower = strtolower($news['title']);
                          if (strpos($titleLower, 'building') !== false || strpos($titleLower, 'inauguration') !== false) {
                            $footerTag = 'Featured';
                          } elseif (strpos($titleLower, 'partnership') !== false || strpos($titleLower, 'visit') !== false) {
                            $footerTag = 'Partnership';
                          } elseif (strpos($titleLower, 'cultural') !== false || strpos($titleLower, 'event') !== false) {
                            $footerTag = 'Campus life';
                          } elseif (strpos($titleLower, 'library') !== false) {
                            $footerTag = 'Student support';
                          } elseif (strpos($titleLower, 'career') !== false || strpos($titleLower, 'webinar') !== false) {
                            $footerTag = 'Career services';
                          }
                          echo htmlspecialchars($footerTag);
                        ?></span>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
              <?php if ($totalPages > 1): ?>
                <div class="news-pagination">
                  <span class="news-pagination-info">Showing <?php echo $startItem; ?>-<?php echo $endItem; ?> of <?php echo $totalItems; ?> news articles.</span>
                  <div class="news-pagination-controls">
                    <?php if ($currentPage > 1): ?>
                      <a href="?page=<?php echo $currentPage - 1; ?>" class="news-pagination-btn">Prev</a>
                    <?php else: ?>
                      <span class="news-pagination-btn" disabled>Prev</span>
                    <?php endif; ?>
                    
                    <?php
                    // Show up to 3 page numbers
                    $startPage = 1;
                    $endPage = min(3, $totalPages);
                    
                    // If current page is beyond first 3 pages, show pages around current
                    if ($currentPage > 2 && $totalPages > 3) {
                      $startPage = $currentPage - 1;
                      $endPage = min($currentPage + 1, $totalPages);
                      
                      // If we're near the end, adjust to show last 3 pages
                      if ($endPage == $totalPages && $totalPages > 3) {
                        $startPage = max(1, $totalPages - 2);
                      }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                      <?php if ($i == $currentPage): ?>
                        <span class="news-pagination-btn news-pagination-active"><?php echo $i; ?></span>
                      <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="news-pagination-btn"><?php echo $i; ?></a>
                      <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                      <a href="?page=<?php echo $currentPage + 1; ?>" class="news-pagination-btn">Next</a>
                    <?php else: ?>
                      <span class="news-pagination-btn" disabled>Next</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <p>No news published yet. Please check back soon.</p>
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
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>

</html>

