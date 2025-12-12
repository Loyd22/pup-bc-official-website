<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

@mysqli_set_charset($conn, 'utf8mb4');

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

// Log page view for page views analytics
log_page_view($conn, 'news_detail');

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

// Get news ID from URL
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($newsId <= 0) {
  header('Location: news.php');
  exit;
}

// Fetch single news item
function fetchNewsItem(mysqli $conn, int $id): ?array {
  $stmt = $conn->prepare("SELECT n.id, n.title, n.summary, n.body, n.image_path, n.publish_date, n.created_by,
                                 a.full_name as author_name
                          FROM news n
                          LEFT JOIN admins a ON n.created_by = a.id
                          WHERE n.id = ? AND n.is_published = 1");
  if (!$stmt) return null;
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  return $row ?: null;
}

// Format date
function formatDate(?string $date): string {
  if (!$date) return 'Draft';
  $timestamp = strtotime($date);
  return $timestamp ? date('F j, Y', $timestamp) : $date;
}

/**
 * Render rich text safely.
 * - Decodes entities (handles double-encoding)
 * - Strips script/style and all attributes
 * - Allows only basic tags
 */
function renderRichText(?string $value): string {
  if ($value === null || $value === '') return '';

  $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  // remove script/style blocks
  $decoded = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $decoded);

  // remove attributes from opening tags (keep tags)
  $decoded = preg_replace_callback('/<([a-z][a-z0-9]*)(\s+[^>]*)?>/i', function($m){
    $tag = strtolower($m[1]);
    if ($tag === 'a' && preg_match('/href\s*=\s*["\']([^"\']*)["\']/i', $m[2] ?? '', $h)) {
      $href = htmlspecialchars($h[1], ENT_QUOTES, 'UTF-8');
      return '<a href="'.$href.'">';
    }
    return '<'.$tag.'>';
  }, $decoded);

  // allowlist tags
  $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote>';
  $decoded = strip_tags($decoded, $allowed);

  // extra cleanup
  $decoded = preg_replace('/\s+/', ' ', $decoded);
  $decoded = preg_replace('/>\s+</', '><', $decoded);

  return trim($decoded);
}

$newsItem = fetchNewsItem($conn, $newsId);
if (!$newsItem) {
  header('Location: news.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($newsItem['title']); ?> - PUP Biñan Campus</title>
  <meta name="description" content="<?php echo htmlspecialchars(strip_tags($newsItem['summary'])); ?>" />
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
            <a href="./admission_guide.php">Admissions</a>
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
    <section class="section">
      <div class="container">
        <article class="card news-detail">
          <div class="body">
            <?php if (!empty($newsItem['image_path'])): ?>
              <div class="news-detail-img">
                <img src="../<?php echo htmlspecialchars($newsItem['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($newsItem['title']); ?>">
              </div>
            <?php endif; ?>
            
            <div class="news-detail-content">
              <h1 class="news-detail-title"><?php echo htmlspecialchars($newsItem['title']); ?></h1>
              <p class="news-meta">
                <?php echo htmlspecialchars(formatDate($newsItem['publish_date'])); ?>
                <?php if (!empty($newsItem['author_name'])): ?>
                  <span style="margin: 0 0.5rem;">•</span>
                  <span>By <?php echo htmlspecialchars($newsItem['author_name']); ?></span>
                <?php endif; ?>
              </p>
              
              <?php if (!empty($newsItem['summary'])): ?>
                <!-- Render summary as HTML (Case A) -->
                <div class="news-detail-summary">
                  <?php echo renderRichText($newsItem['summary']); ?>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($newsItem['body'])): ?>
                <div class="news-detail-body">
                  <?php echo renderRichText($newsItem['body']); ?>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="news-detail-actions">
              <a href="news.php" class="btn btn--secondary">← Back to All News</a>
            </div>
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
