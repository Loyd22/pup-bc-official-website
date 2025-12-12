<?php
include __DIR__ . '/DATAANALYTICS/db.php';
require_once __DIR__ . '/DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/DATAANALYTICS/page_views.php';
require_once __DIR__ . '/includes/announcement_helpers.php';

$ip = $_SERVER['REMOTE_ADDR'];
$sql = "INSERT INTO visitors (ip_address) VALUES (?)";
$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param("s", $ip);
  $stmt->execute();
  $stmt->close();
}

// Record homepage visit for analytics
$today = date('Y-m-d');
record_page_visit($conn, 'homepage.php', $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'homepage');

// Get page views and total visitors for footer
$page_name = 'homepage.php';
$page_views = get_page_visit_count($conn, $page_name);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}


$settingDefaults = [
  'site_title' => 'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES',
  'campus_name' => 'Biñan Campus',
  'hero_heading' => 'Serving the Nation through Quality Public Education',
  'hero_text' => 'Welcome to the PUP Biñan Campus homepage - your hub for announcements, admissions, academic programs, student services, and campus life.',
  'logo_path' => 'images/PUPLogo.png',
  'hero_image_path' => '',
  'hero_video_path' => '',
  'footer_about' => 'PUP Biñan Campus is part of the country\'s largest state university system, committed to accessible and excellent public higher education.',
  'footer_address' => "Sto. Tomas, Biñan, Laguna\nPhilippines 4024",
  'footer_email' => 'info.binan@pup.edu.ph',
  'footer_phone' => '(xxx) xxx xxxx'
];

function fetchSettings(mysqli $conn, array $keys): array
{
  if (empty($keys)) {
    return [];
  }

  $placeholders = implode(',', array_fill(0, count($keys), '?'));
  $types = str_repeat('s', count($keys));
  $sql = "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param($types, ...$keys);
  $stmt->execute();
  $result = $stmt->get_result();

  $settings = [];
  while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
  }
  $stmt->close();

  return $settings;
}

function renderRichText(?string $value): string
{
  if ($value === null || $value === '') {
    return '';
  }

  $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  $decoded = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $decoded);
  $decoded = preg_replace('/\sstyle=("|\').*?\1/i', '', $decoded);
  $decoded = preg_replace('/\son[a-z]+\s*=\s*("|\').*?\1/i', '', $decoded);

  $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a>';
  return trim(strip_tags($decoded, $allowedTags));
}

function fetchSocialLinks(mysqli $conn): array
{
  if ($check = $conn->query("SHOW TABLES LIKE 'social_links'")) {
    if ($check->num_rows === 0) {
      $check->free();
      return [];
    }
    $check->free();
  } else {
    return [];
  }

  $sql = "SELECT label, url
            FROM social_links
            ORDER BY created_at ASC, id ASC";

  $result = $conn->query($sql);
  if (!$result) {
    return [];
  }

  $links = [];
  while ($row = $result->fetch_assoc()) {
    $label = trim((string) ($row['label'] ?? ''));
    $url = trim((string) ($row['url'] ?? ''));
    if ($label !== '' && $url !== '') {
      $links[] = [
        'label' => $label,
        'url' => $url
      ];
    }
  }
  $result->free();

  return $links;
}

// Fetch announcements from events table (single source of truth)
// Only shows events marked for homepage display
function fetchAnnouncements(mysqli $conn, int $limit = 3): array
{
  return fetchAnnouncementsFromEvents($conn, $limit, true);
}

function fetchEventsForHomepage(mysqli $conn, int $limit = 10): array
{
  $sql = "SELECT id, title, description, start_date, end_date, location
            FROM events
            WHERE show_on_homepage = 1 
              AND (start_date >= CURDATE() OR end_date >= CURDATE())
            ORDER BY start_date ASC
            LIMIT ?";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $items;
}

function fetchEventsForAnnouncements(mysqli $conn, int $limit = 10): array
{
  $sql = "SELECT id, title, description, start_date, end_date, location
            FROM events
            WHERE show_in_announcement = 1 AND start_date >= CURDATE()
            ORDER BY start_date ASC
            LIMIT ?";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $items;
}

function fetchEventsForMonth(mysqli $conn, int $year, int $month): array
{
  $startDate = sprintf('%04d-%02d-01', $year, $month);
  $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
  $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

  $sql = "SELECT id, title, description, start_date, end_date, location, category, show_on_homepage
            FROM events
            WHERE (start_date <= ? AND end_date >= ?)
               OR (start_date >= ? AND start_date <= ?)
            ORDER BY start_date ASC";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('ssss', $endDate, $startDate, $startDate, $endDate);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $items;
}

function buildMonthCalendar(int $year, int $month, array $events): string
{
  $today = date('Y-m-d');
  $currentYear = (int)date('Y');
  $currentMonth = (int)date('m');
  $currentDay = (int)date('d');

  // Get first day of month and number of days
  $firstDay = mktime(0, 0, 0, $month, 1, $year);
  $daysInMonth = date('t', $firstDay);
  $dayOfWeek = date('w', $firstDay); // 0 = Sunday, 6 = Saturday

  // Organize events by day
  $eventsByDay = [];
  foreach ($events as $event) {
    $eventStart = strtotime($event['start_date']);
    $eventEnd = strtotime($event['end_date'] ?: $event['start_date']);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
      $dayTimestamp = mktime(0, 0, 0, $month, $day, $year);
      if ($dayTimestamp >= $eventStart && $dayTimestamp <= $eventEnd) {
        $dayKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
        if (!isset($eventsByDay[$dayKey])) {
          $eventsByDay[$dayKey] = [];
        }
        $eventsByDay[$dayKey][] = $event;
      }
    }
  }

  // Day names
  $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  
  $html = '<div class="calendar-grid">';
  $html .= '<div class="calendar-header">';
  foreach ($dayNames as $dayName) {
    $html .= '<div class="calendar-day-name">' . htmlspecialchars($dayName) . '</div>';
  }
  $html .= '</div>';
  $html .= '<div class="calendar-days">';

  // Empty cells for days before month starts
  for ($i = 0; $i < $dayOfWeek; $i++) {
    $html .= '<div class="calendar-day calendar-day-empty"></div>';
  }

  // Days of the month
  for ($day = 1; $day <= $daysInMonth; $day++) {
    $dayKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $dayDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $isToday = ($year == $currentYear && $month == $currentMonth && $day == $currentDay);
    $hasEvents = isset($eventsByDay[$dayKey]) && !empty($eventsByDay[$dayKey]);

    $classes = ['calendar-day'];
    if ($isToday) {
      $classes[] = 'calendar-day-today';
    }
    if ($hasEvents) {
      $classes[] = 'calendar-day-has-events';
    }

    $html .= '<div class="' . implode(' ', $classes) . '">';
    $html .= '<div class="calendar-day-number">' . $day . '</div>';
    
    if ($hasEvents) {
      $html .= '<div class="calendar-day-events">';
      foreach ($eventsByDay[$dayKey] as $event) {
        $title = htmlspecialchars($event['title']);
        if (mb_strlen($title) > 20) {
          $title = mb_substr($title, 0, 17) . '...';
        }
        $categoryClass = 'cat-' . strtolower(str_replace(' & ', '-', str_replace(' ', '-', $event['category'] ?? 'Events')));
        $eventId = isset($event['id']) ? (int)$event['id'] : 0;
        $html .= '<a href="pages/announcement.php#ann-' . $eventId . '" class="calendar-event-link">';
        $html .= '<div class="calendar-event ' . htmlspecialchars($categoryClass) . '" title="' . htmlspecialchars($event['title']) . '">' . $title . '</div>';
        $html .= '</a>';
      }
      $html .= '</div>';
    }
    
    $html .= '</div>';
  }

  // Fill remaining cells to complete the grid (if needed)
  $totalCells = $dayOfWeek + $daysInMonth;
  $remainingCells = 7 - ($totalCells % 7);
  if ($remainingCells < 7) {
    for ($i = 0; $i < $remainingCells; $i++) {
      $html .= '<div class="calendar-day calendar-day-empty"></div>';
    }
  }

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}

// formatDateRange moved to includes/announcement_helpers.php

function formatEventDateForHighlight(?string $startDate): string
{
  if (!$startDate) {
    return '';
  }
  
  $timestamp = strtotime($startDate);
  if (!$timestamp) {
    return '';
  }
  
  $day = date('j', $timestamp);
  $dayName = date('D', $timestamp);
  
  return $day . ' (' . strtoupper(substr($dayName, 0, 3)) . ')';
}

function getEventCategoryClass(?string $category): string
{
  if (!$category) {
    return 'cat-default';
  }
  
  $categoryLower = strtolower($category);
  
  // Map categories to CSS classes
  if (strpos($categoryLower, 'midterm') !== false || strpos($categoryLower, 'exam') !== false) {
    return 'cat-exam';
  } elseif (strpos($categoryLower, 'holiday') !== false || strpos($categoryLower, 'no class') !== false) {
    return 'cat-holiday';
  } elseif (strpos($categoryLower, 'academic') !== false) {
    return 'cat-academic';
  } elseif (strpos($categoryLower, 'campus') !== false || strpos($categoryLower, 'life') !== false) {
    return 'cat-campus';
  }
  
  return 'cat-default';
}

function buildMonthlyEventsList(array $events): string
{
  $highlightEvents = array_slice($events, 0, 5);
  
  if (empty($highlightEvents)) {
    return '<div class="event-highlight-item"><p class="no-events-message">No events scheduled for this month.</p></div>';
  }
  
  $html = '';
  foreach ($highlightEvents as $event) {
    $eventDate = formatEventDateForHighlight($event['start_date']);
    $categoryClass = getEventCategoryClass($event['category']);
    
    $html .= '<div class="event-highlight-item">';
    $html .= '<div class="event-date-box ' . htmlspecialchars($categoryClass) . '">';
    $html .= htmlspecialchars($eventDate);
    $html .= '</div>';
    $html .= '<div class="event-highlight-content">';
    $html .= '<h4 class="event-highlight-title">' . htmlspecialchars($event['title']) . '</h4>';
    
    if (!empty($event['location'])) {
      $html .= '<p class="event-highlight-location">' . htmlspecialchars($event['location']) . '</p>';
    }
    
    if (!empty($event['description'])) {
      $html .= '<p class="event-highlight-description">' . htmlspecialchars(excerpt($event['description'], 80)) . '</p>';
    }
    
    $html .= '<span class="event-category-tag ' . htmlspecialchars($categoryClass) . '">';
    $html .= htmlspecialchars($event['category'] ?: 'Event');
    $html .= '</span>';
    $html .= '</div>';
    $html .= '</div>';
  }
  
  return $html;
}

function fetchNews(mysqli $conn, int $limit = 3): array
{
  $sql = "SELECT n.id, n.title, n.summary, n.image_path, n.publish_date, n.created_by,
                 a.full_name as author_name
            FROM news n
            LEFT JOIN admins a ON n.created_by = a.id
            WHERE n.is_published = 1
            ORDER BY COALESCE(n.publish_date, n.created_at) DESC
            LIMIT ?";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $items;
}

function fetchMedia(mysqli $conn, string $type, int $limit): array
{
  $sql = "SELECT id, title, description, file_path, video_url, uploaded_at
            FROM media_library
            WHERE media_type = ?
            ORDER BY uploaded_at DESC
            LIMIT ?";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('si', $type, $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $items;
}

// formatDate and excerpt moved to includes/announcement_helpers.php

function buildVideoEmbed(?string $url): ?string
{
  if (!$url) {
    return null;
  }

  if (preg_match('~youtu\.be/([A-Za-z0-9_-]{11})~', $url, $matches)) {
    return 'https://www.youtube.com/embed/' . $matches[1];
  }

  if (preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]{11})~', $url, $matches)) {
    return 'https://www.youtube.com/embed/' . $matches[1];
  }

  if (preg_match('~vimeo\.com/(\d+)~', $url, $matches)) {
    return 'https://player.vimeo.com/video/' . $matches[1];
  }

  return null;
}

$settings = array_merge(
  $settingDefaults,
  fetchSettings($conn, array_keys($settingDefaults))
);

$logoPath = $settings['logo_path'] ?: 'images/pupbcheaderbg.png';
// Video is stored INSIDE pupbc-website/videos/ (not outside)
$heroVideoPath = !empty($settings['hero_video_path']) ? $settings['hero_video_path'] : 'videos/homepagevid.mp4';

// Fetch announcements from events table (single source of truth)
$announcements = fetchAnnouncements($conn, 3);

// Get current date
$todayYear = (int)date('Y');
$todayMonth = (int)date('m');

// Get requested month/year from URL, default to current month
$requestedYear = isset($_GET['year']) ? (int)$_GET['year'] : $todayYear;
$requestedMonth = isset($_GET['month']) ? (int)$_GET['month'] : $todayMonth;

// Validate month range (1-12)
if ($requestedMonth < 1 || $requestedMonth > 12) {
  $requestedMonth = $todayMonth;
  $requestedYear = $todayYear;
}

// Calculate valid range: 1 month in past to 10 months in future (12 months total)
$earliestDate = mktime(0, 0, 0, $todayMonth - 1, 1, $todayYear);
$latestDate = mktime(0, 0, 0, $todayMonth + 10, 1, $todayYear);
$requestedDate = mktime(0, 0, 0, $requestedMonth, 1, $requestedYear);

// Clamp requested date to valid range
if ($requestedDate < $earliestDate) {
  $displayYear = (int)date('Y', $earliestDate);
  $displayMonth = (int)date('m', $earliestDate);
} elseif ($requestedDate > $latestDate) {
  $displayYear = (int)date('Y', $latestDate);
  $displayMonth = (int)date('m', $latestDate);
} else {
  $displayYear = $requestedYear;
  $displayMonth = $requestedMonth;
}

// Calculate prev/next months
$prevDate = mktime(0, 0, 0, $displayMonth - 1, 1, $displayYear);
$nextDate = mktime(0, 0, 0, $displayMonth + 1, 1, $displayYear);

$canGoPrev = $prevDate >= $earliestDate;
$canGoNext = $nextDate <= $latestDate;

$prevYear = $canGoPrev ? (int)date('Y', $prevDate) : null;
$prevMonth = $canGoPrev ? (int)date('m', $prevDate) : null;
$nextYear = $canGoNext ? (int)date('Y', $nextDate) : null;
$nextMonth = $canGoNext ? (int)date('m', $nextDate) : null;

// Fetch events for the displayed month
$monthEvents = fetchEventsForMonth($conn, $displayYear, $displayMonth);
$newsItems = fetchNews($conn);
$mediaImages = fetchMedia($conn, 'image', 3);
$mediaVideos = fetchMedia($conn, 'video', 1);
$socialLinks = fetchSocialLinks($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PUP Biñan Campus - Official Website</title>
  <meta name="description"
    content="Polytechnic University of the Philippines - Biñan Campus. UI-only, static HTML/CSS/JS." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="stylesheet" href="asset/css/site.css" />
  <link rel="stylesheet" href="asset/css/home.css" />
  <link rel="stylesheet" href="asset/css/hero-video.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="asset/js/homepage.js"></script>
  <script defer src="asset/js/mobile-nav.js"></script>
  <link rel="icon" type="image/png" href="asset/PUPicon.png">
  <link rel="canonical" href="https://pupbc.site/">


</head>

<body class="home" data-wp-base="">
  <header> <!-- Top row: logo + campus text (inside header) -->
    <div class="topbar" role="banner">
      <div class="container topbar-inner">
        <div class="seal" aria-hidden="true"> <a href="homepage.php"> <img
              src="<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          </a> </div>
        <div class="brand" aria-label="Campus name">
          <spanx class="u"><?php echo htmlspecialchars($settings['site_title']); ?></span>
            <span class="c"><?php echo htmlspecialchars($settings['campus_name']); ?></span>
        </div>
      </div>
    </div> <!-- Navigation bar with logo + university name -->
    <div class="container nav">
      <div class="brand-nav">
        <div class="seal" aria-hidden="true"> <a href="homepage.php"> <img
              src="<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          </a> </div>
        <div class="brand" aria-label="Campus name"> <span
            class="u"><?php echo htmlspecialchars($settings['site_title']); ?></span>
          <span class="c"><?php echo htmlspecialchars($settings['campus_name']); ?></span>
          <nav aria-label="Primary" class="menu" id="menu"> <a class="is-active" href="#">HOME</a>
        <a href="pages/about.php">About</a>
        <a href="pages/programs.php">Academic Programs</a> <a href="pages/admission_guide.php">Admission</a>
        <a href="pages/services.php">Student Services</a> <a href="pages/campuslife.php">Campus Life</a> <a
          href="pages/contact.php">Contact Us</a>
      </nav>
        </div>
      </div>
      
    <form class="search-form" action="search.php" method="get" role="search" aria-label="Site search">
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
          <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="PUP Logo" />
          <div class="mobile-nav-brand">
            <span class="u"><?php echo htmlspecialchars($settings['site_title']); ?></span>
            <span class="c"><?php echo htmlspecialchars($settings['campus_name']); ?></span>
          </div>
        </div>
      </div>
      <nav class="mobile-nav-menu">
        <!-- Mobile Search Form -->
        <div class="mobile-nav-search">
          <form class="mobile-nav-search-form" action="search.php" method="get" role="search" aria-label="Site search">
            <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
            <button type="submit" aria-label="Search">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </form>
        </div>
        <a href="homepage.php" class="mobile-nav-link is-active">Home</a>
        <a href="pages/about.php" class="mobile-nav-link">About</a>
        <a href="pages/programs.php" class="mobile-nav-link">Academic Programs</a>
        <a href="pages/admission_guide.php" class="mobile-nav-link">Admission</a>
        <a href="pages/services.php" class="mobile-nav-link">Student Services</a>
        <a href="pages/campuslife.php" class="mobile-nav-link">Campus Life</a>
        <a href="pages/contact.php" class="mobile-nav-link">Contact Us</a>
      </nav>
    </div>
  </header>
  <main id="content"> <!-- HERO -->
    <?php
    // Include hero video component with centered text
    $heroContent = '<div class="container hero-inner hero-text-center">
      <div>
        <h1>Maligayang pagdating sa Politeknikong Unibersidad ng Pilipinas – Biñan.</h1>
      </div>
    </div>';
    
    $heroOptions = [
      'height' => 'auto',
      'minHeight' => '570px',
      'maxHeight' => 'none',
      'brightness' => 0.7, // Slightly darker for text readability
      'showOverlay' => true, // Show subtle overlay for text readability
      'autoplay' => true,
      'muted' => true,
      'loop' => true,
    ];
    
    $content = $heroContent;
    $videoPath = !empty($heroVideoPath) ? $heroVideoPath : '';
    $customClass = 'hero'; // Remove video-only class since we have text now
    $options = $heroOptions;
    include 'components/hero-video.php';
    ?>
    <section class="section" id="news" data-wp-cat="news" data-wp-count="3">
      <div class="container">
        <article class="card news-wrapper" aria-labelledby="newsHeading">
          <div class="news-section-header">
            <span class="news-label">• CAMPUS NEWS</span>
            <h2 class="news-heading" id="newsHeading">News</h2>
            <p class="news-intro">Stories, highlights, and achievements from PUP Biñan Campus.</p>
          </div>
          <div class="body">
            <div class="news-grid">
              <?php if (!empty($newsItems)): ?>
                <?php foreach ($newsItems as $news): ?>
                  <article class="news-card">
                    <div class="news-img">
                      <img src="<?php echo htmlspecialchars($news['image_path'] ?: 'images/pupbackrgound.jpg'); ?>"
                          alt="<?php echo htmlspecialchars($news['title']); ?>">
                    </div>
                    <div class="news-content">
                      <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                      <p class="news-summary"><?php echo htmlspecialchars(excerpt($news['summary'], 120)); ?></p>
                      <div class="news-meta-footer">
                        <span class="news-date">
                          Posted: <?php echo htmlspecialchars(formatDate($news['publish_date'])); ?>
                          <?php if (!empty($news['author_name'])): ?>
                            <span style="margin: 0 0.25rem;">•</span>
                            <span>By <?php echo htmlspecialchars($news['author_name']); ?></span>
                          <?php endif; ?>
                        </span>
                        <div class="news-actions">
                          <a href="pages/news_detail.php?id=<?php echo (int)$news['id']; ?>" class="news-read-more-btn">Read more</a>
                          <span class="news-category-tag">
                          <?php
                            // Enhanced automatic category detection with smart keyword matching
                            $category = 'News';

                            // Combine title + summary for wider keyword matching
                            $text = strtolower(
                              trim(($news['title'] ?? '') . ' ' . ($news['summary'] ?? ''))
                            );
                            
                            // Normalize text: remove extra spaces, punctuation
                            $text = preg_replace('/[^\w\s]/', ' ', $text);
                            $text = preg_replace('/\s+/', ' ', $text);

                            // Map of categories with weighted keywords (higher weight = more specific)
                            // Format: ['keyword' => weight] or ['phrase keyword' => weight]
                            $categoryMap = [
                              'Graduation & Commencement' => [
                                'graduation' => 10,
                                'commencement' => 10,
                                'graduate' => 8,
                                'graduates' => 8,
                                'commencement exercises' => 15,
                                'graduation ceremony' => 15,
                                'graduation rites' => 15,
                                'marching order' => 12,
                                'valedictorian' => 12,
                                'salutatorian' => 12,
                                'cum laude' => 10,
                                'magna cum laude' => 12,
                                'summa cum laude' => 12,
                                'honor graduate' => 10,
                                'diploma' => 8,
                                'completer' => 8,
                                'completion' => 7
                              ],
                              'Campus development' => [
                                'building' => 8,
                                'campus' => 5,
                                'facility' => 8,
                                'facilities' => 8,
                                'inauguration' => 12,
                                'inaugurated' => 12,
                                'renovation' => 10,
                                'renovated' => 10,
                                'upgrade' => 8,
                                'upgraded' => 8,
                                'construction' => 10,
                                'constructed' => 10,
                                'extension' => 9,
                                'expanded' => 9,
                                'expansion' => 9,
                                'improvement' => 7,
                                'improved' => 7,
                                'groundbreaking' => 12,
                                'ground breaking' => 12,
                                'infrastructure' => 10,
                                'laboratory' => 9,
                                'laboratories' => 9,
                                'lab' => 7,
                                'classroom' => 7,
                                'library' => 8,
                                'gymnasium' => 9,
                                'gym' => 7,
                                'dormitory' => 9,
                                'canteen' => 8
                              ],
                              'Research & innovation' => [
                                'research' => 9,
                                'researcher' => 9,
                                'researchers' => 9,
                                'innovation' => 10,
                                'innovative' => 9,
                                'grant' => 8,
                                'research grant' => 12,
                                'forum' => 7,
                                'journal' => 9,
                                'publication' => 9,
                                'published' => 8,
                                'conference' => 9,
                                'study' => 6,
                                'studies' => 6,
                                'prototype' => 10,
                                'thesis' => 9,
                                'dissertation' => 9,
                                'capstone' => 9,
                                'capstone project' => 11,
                                'paper' => 7,
                                'research paper' => 11,
                                'patent' => 10,
                                'invention' => 10,
                                'discovery' => 9
                              ],
                              'Student affairs' => [
                                'student' => 4,
                                'students' => 4,
                                'student affairs' => 12,
                                'leadership' => 8,
                                'leader' => 7,
                                'leaders' => 7,
                                'training' => 7,
                                'organization' => 8,
                                'organizations' => 8,
                                'org' => 6,
                                'council' => 8,
                                'student council' => 12,
                                'ssc' => 10,
                                'club' => 7,
                                'clubs' => 7,
                                'election' => 9,
                                'elected' => 8,
                                'officer' => 7,
                                'officers' => 7,
                                'organization activity' => 11,
                                'extracurricular' => 9,
                                'student government' => 12
                              ],
                              'Awards & recognition' => [
                                'award' => 8,
                                'awards' => 8,
                                'awarded' => 8,
                                'recognition' => 8,
                                'recognized' => 8,
                                'topnotcher' => 12,
                                'topnotch' => 10,
                                'top notcher' => 12,
                                'top notch' => 10,
                                'champion' => 9,
                                'champions' => 9,
                                'winner' => 8,
                                'winners' => 8,
                                'won' => 7,
                                'medal' => 9,
                                'medals' => 9,
                                'medalist' => 9,
                                'rank' => 7,
                                'ranking' => 7,
                                'honor' => 8,
                                'honors' => 8,
                                'honored' => 8,
                                'outstanding' => 9,
                                'achievement' => 8,
                                'achievements' => 8,
                                'achieved' => 7,
                                'excellence' => 9,
                                'excellent' => 8,
                                'best' => 6,
                                'first place' => 10,
                                'championship' => 10
                              ],
                              'Events & activities' => [
                                'seminar' => 9,
                                'seminars' => 9,
                                'webinar' => 9,
                                'webinars' => 9,
                                'orientation' => 9,
                                'program' => 6,
                                'programs' => 6,
                                'activity' => 6,
                                'activities' => 6,
                                'celebration' => 9,
                                'celebrate' => 8,
                                'festival' => 9,
                                'festivals' => 9,
                                'fair' => 8,
                                'workshop' => 9,
                                'workshops' => 9,
                                'camp' => 8,
                                'bootcamp' => 10,
                                'boot camp' => 10,
                                'conference' => 9,
                                'symposium' => 9,
                                'competition' => 8,
                                'contest' => 8,
                                'tournament' => 9
                              ],
                              'Academic updates' => [
                                'enrollment' => 10,
                                'enrolment' => 10,
                                'enroll' => 9,
                                'enrol' => 9,
                                'schedule' => 7,
                                'schedules' => 7,
                                'scheduled' => 7,
                                'classes' => 7,
                                'class' => 6,
                                'class suspension' => 12,
                                'suspended classes' => 12,
                                'academic' => 6,
                                'academics' => 6,
                                'midterm' => 9,
                                'mid term' => 9,
                                'midterms' => 9,
                                'finals' => 9,
                                'final exam' => 10,
                                'final examination' => 10,
                                'exam' => 7,
                                'examination' => 7,
                                'examinations' => 7,
                                'calendar' => 8,
                                'academic calendar' => 11,
                                'curriculum' => 8,
                                'syllabus' => 7,
                                'course' => 6,
                                'courses' => 6,
                                'subject' => 6,
                                'subjects' => 6
                              ],
                              'Partnerships & linkages' => [
                                'mou' => 12,
                                'memorandum of understanding' => 15,
                                'moa' => 12,
                                'memorandum of agreement' => 15,
                                'partnership' => 10,
                                'partners' => 9,
                                'partnered' => 9,
                                'agreement' => 9,
                                'signed' => 7,
                                'industry partner' => 12,
                                'collaboration' => 10,
                                'collaborate' => 9,
                                'collaborating' => 9,
                                'linkage' => 10,
                                'linkages' => 10,
                                'alliance' => 9,
                                'tie up' => 10,
                                'tie-up' => 10
                              ],
                              'Scholarships & opportunities' => [
                                'scholarship' => 10,
                                'scholarships' => 10,
                                'scholar' => 8,
                                'scholars' => 8,
                                'bursary' => 10,
                                'financial assistance' => 11,
                                'financial aid' => 11,
                                'grant-in-aid' => 12,
                                'grant in aid' => 12,
                                'internship' => 9,
                                'internships' => 9,
                                'intern' => 7,
                                'interns' => 7,
                                'application' => 7,
                                'applications' => 7,
                                'apply' => 6,
                                'opportunity' => 7,
                                'opportunities' => 7,
                                'call for applicants' => 12,
                                'open for application' => 11,
                                'now accepting' => 9
                              ],
                              'Safety & advisories' => [
                                'advisory' => 10,
                                'advisories' => 10,
                                'safety' => 9,
                                'safe' => 7,
                                'guidelines' => 9,
                                'guideline' => 8,
                                'reminder' => 8,
                                'reminders' => 8,
                                'suspension' => 8,
                                'suspended' => 8,
                                'typhoon' => 12,
                                'typhoons' => 12,
                                'storm' => 10,
                                'storms' => 10,
                                'earthquake' => 12,
                                'earthquakes' => 12,
                                'covid' => 11,
                                'covid-19' => 12,
                                'coronavirus' => 11,
                                'pandemic' => 10,
                                'health' => 7,
                                'health protocols' => 12,
                                'protocol' => 8,
                                'protocols' => 8,
                                'class suspension' => 12,
                                'emergency' => 9,
                                'evacuation' => 11,
                                'alert' => 8,
                                'warning' => 9
                              ]
                            ];

                            // Smart category detection with scoring system
                            $categoryScores = [];
                            
                            foreach ($categoryMap as $cat => $keywords) {
                              $score = 0;
                              foreach ($keywords as $keyword => $weight) {
                                // Use word boundaries for better matching
                                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                                if (preg_match($pattern, $text)) {
                                  $score += $weight;
                                }
                              }
                              if ($score > 0) {
                                $categoryScores[$cat] = $score;
                              }
                            }
                            
                            // Get category with highest score, default to 'News' if no match
                            if (!empty($categoryScores)) {
                              arsort($categoryScores);
                              $category = array_key_first($categoryScores);
                            }

                            echo htmlspecialchars($category);
                          ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <p>No news published yet. Please check back soon.</p>
              <?php endif; ?>
            </div>
            <div class="news-load-more-wrapper">
              <a href="pages/news.php" class="news-load-more-btn">See all News</a>
            </div>
          </div>
        </article>
      </div>
    </section> <!-- ANNOUNCEMENTS + EVENTS -->
    <section class="section announcements-calendar-section" id="announcements">
      <div class="container announcements-calendar-container">
        <!-- Left Column: Campus Announcements -->
        <div class="announcements-column">
          <div class="announcements-header">
            <span class="announcements-label">CAMPUS ANNOUNCEMENTS</span>
            <h2 class="announcements-title">Latest updates from PUP Biñan</h2>
            <p class="announcements-subtitle">Important advisories, student affairs, and academic-related notices for the campus community.</p>
          </div>
          
          <div class="announcements-list">
            <?php if (!empty($announcements)): ?>
              <?php foreach ($announcements as $announcement): ?>
                <article class="announcement-card">
                  <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                  <div class="announcement-meta">
                    <span class="announcement-date">
                      Posted <?php echo htmlspecialchars(formatDate($announcement['start_date'])); ?>
                      <?php if (!empty($announcement['author_name'])): ?>
                        <span style="margin: 0 0.25rem;">•</span>
                        <span>By <?php echo htmlspecialchars($announcement['author_name']); ?></span>
                      <?php endif; ?>
                    </span>
                    <?php if (!empty($announcement['category'])): ?>
                      <span class="announcement-source">
                        <?php 
                        // Format category for display
                        $categoryDisplay = strtoupper(str_replace('_', ' ', $announcement['category']));
                        echo htmlspecialchars($categoryDisplay);
                        ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="announcement-category-tag">
                    <?php 
                    $categoryTag = '';
                    if (!empty($announcement['category'])) {
                      $categoryTag = strtoupper(str_replace('_', ' ', $announcement['category']));
                    } else {
                      $categoryTag = 'ANNOUNCEMENT';
                    }
                    echo htmlspecialchars($categoryTag);
                    ?>
                  </div>
                  <p class="announcement-body"><?php echo htmlspecialchars(excerpt($announcement['description'] ?? '', 200)); ?></p>
                  <a href="pages/announcement.php#ann-<?php echo (int)$announcement['id']; ?>" class="announcement-link">
                    Read full announcement &rsaquo;
                  </a>
                  <?php if (!empty($announcement['category'])): ?>
                    <div class="announcement-footer-tag">
                      <?php
                      // Add contextual tags based on category
                      $footerTag = '';
                      $categoryLower = strtolower($announcement['category']);
                      if (strpos($categoryLower, 'student') !== false) {
                        $footerTag = 'Applies to all year levels';
                      } elseif (strpos($categoryLower, 'holiday') !== false || strpos($categoryLower, 'bonifacio') !== false) {
                        $footerTag = 'Special non-working holiday';
                      } elseif (strpos($categoryLower, 'enrollment') !== false) {
                        $footerTag = 'With downloadable checklist';
                      } elseif (strpos($categoryLower, 'midterm') !== false || strpos($categoryLower, 'exam') !== false) {
                        $footerTag = 'Applies to all year levels';
                      }
                      if ($footerTag) {
                        echo htmlspecialchars($footerTag);
                      }
                      ?>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="announcement-card">
                <p>No announcements at the moment. Please check back soon.</p>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="announcements-footer">
            <a href="pages/announcement.php" class="announcements-view-all">See all announcements &rsaquo;</a>
          </div>
        </div>
        
        <!-- Right Column: Calendar Grid -->
        <div class="calendar-column">
          <article class="card calendar-card">
            <div class="toolbar">
              <h3 id="calendarHeading"><?php echo htmlspecialchars(date('F Y', mktime(0, 0, 0, $displayMonth, 1, $displayYear))); ?></h3>
            </div>
            <div class="body">
              <div class="calendar-navigation">
                <?php if ($canGoPrev): ?>
                  <button type="button" class="calendar-nav-btn calendar-nav-prev" data-year="<?php echo $prevYear; ?>" data-month="<?php echo $prevMonth; ?>" aria-label="Previous month">
                    &larr; Prev
                  </button>
                <?php else: ?>
                  <span class="calendar-nav-btn calendar-nav-prev calendar-nav-disabled" aria-label="No previous month available">
                    &larr; Prev
                  </span>
                <?php endif; ?>
                
                <?php if ($canGoNext): ?>
                  <button type="button" class="calendar-nav-btn calendar-nav-next" data-year="<?php echo $nextYear; ?>" data-month="<?php echo $nextMonth; ?>" aria-label="Next month">
                    Next &rarr;
                  </button>
                <?php else: ?>
                  <span class="calendar-nav-btn calendar-nav-next calendar-nav-disabled" aria-label="No next month available">
                    Next &rarr;
                  </span>
                <?php endif; ?>
              </div>
              <div id="calendar-container" data-current-year="<?php echo $displayYear; ?>" data-current-month="<?php echo $displayMonth; ?>">
                <?php echo buildMonthCalendar($displayYear, $displayMonth, $monthEvents); ?>
              </div>
              <div class="calendar-legend">
                <div class="legend-item legend-filter" data-filter="all" data-category="all">
                  <span class="legend-dot legend-dot-all"></span>
                  <span class="legend-label">All</span>
                </div>
                <div class="legend-item legend-filter" data-filter="events" data-category="cat-events">
                  <span class="legend-dot legend-dot-events"></span>
                  <span class="legend-label">Events</span>
                </div>
                <div class="legend-item legend-filter" data-filter="academics" data-category="cat-academics">
                  <span class="legend-dot legend-dot-academics"></span>
                  <span class="legend-label">Academics</span>
                </div>
                <div class="legend-item legend-filter" data-filter="alerts" data-category="cat-alerts-safety">
                  <span class="legend-dot legend-dot-alerts"></span>
                  <span class="legend-label">Alerts & Safety</span>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
    
    <!-- Hidden Full Calendar Section -->
    <section class="section full-calendar-section" id="fullCalendarSection" style="display: none;">
      <div class="container">
        <article class="card" id="fullCalendarCard" aria-labelledby="fullCalendarHeading">
          <div class="toolbar">
            <h3 id="fullCalendarHeading"><?php echo htmlspecialchars(date('F Y', mktime(0, 0, 0, $displayMonth, 1, $displayYear))); ?></h3>
          </div>
          <div class="body">
            <div class="calendar-navigation">
              <?php if ($canGoPrev): ?>
                <button type="button" class="calendar-nav-btn calendar-nav-prev" data-year="<?php echo $prevYear; ?>" data-month="<?php echo $prevMonth; ?>" aria-label="Previous month">
                  &larr; Prev
                </button>
              <?php else: ?>
                <span class="calendar-nav-btn calendar-nav-prev calendar-nav-disabled" aria-label="No previous month available">
                  &larr; Prev
                </span>
              <?php endif; ?>
              
              <?php if ($canGoNext): ?>
                <button type="button" class="calendar-nav-btn calendar-nav-next" data-year="<?php echo $nextYear; ?>" data-month="<?php echo $nextMonth; ?>" aria-label="Next month">
                  Next &rarr;
                </button>
              <?php else: ?>
                <span class="calendar-nav-btn calendar-nav-next calendar-nav-disabled" aria-label="No next month available">
                  Next &rarr;
                </span>
              <?php endif; ?>
            </div>
            <div id="full-calendar-container" data-current-year="<?php echo $displayYear; ?>" data-current-month="<?php echo $displayMonth; ?>">
              <?php echo buildMonthCalendar($displayYear, $displayMonth, $monthEvents); ?>
            </div>
          </div>
        </article>
      </div>
    </section>
    <?php /* Media Highlights section commented out
    <?php if (!empty($mediaImages) || !empty($mediaVideos)): ?>
      <section class="section" id="media">
        <div class="container">
          <article class="card" aria-labelledby="mediaHeading">
            <h3 id="mediaHeading">Media Highlights</h3>
            <div class="body">
              <div class="news-grid">
                <?php foreach ($mediaImages as $image): ?>
                  <div class="news">
                    <div class="img"><img src="<?php echo htmlspecialchars($image['file_path']); ?>"
                        alt="<?php echo htmlspecialchars($image['title']); ?>"></div>
                    <div class="txt">
                      <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                      <?php if (!empty($image['description'])): ?>
                        <p><?php echo htmlspecialchars(excerpt($image['description'])); ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php foreach ($mediaVideos as $video): ?>
                  <div class="news">
                    <div class="img">
                      <?php $embed = buildVideoEmbed($video['video_url']); ?>
                      <?php if ($embed): ?>
                        <iframe src="<?php echo htmlspecialchars($embed); ?>"
                          title="<?php echo htmlspecialchars($video['title']); ?>"
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                          allowfullscreen></iframe>
                      <?php else: ?>
                        <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" rel="noopener">Watch
                          video</a>
                      <?php endif; ?>
                    </div>
                    <div class="txt">
                      <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                      <?php if (!empty($video['description'])): ?>
                        <p><?php echo htmlspecialchars(excerpt($video['description'])); ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </article>
        </div>
      </section>
    <?php endif; ?>
    */ ?>
    
    <?php
    // Enrollment analytics are hidden on the public homepage; admin view still has the charts.
    $showAnalyticsOnHomepage = false;
    $chartsSection = '';

    if ($showAnalyticsOnHomepage) {
        $latestUploadStmt = $conn->prepare("SELECT id, uploaded_at FROM csv_uploads WHERE status = 'completed' ORDER BY uploaded_at DESC LIMIT 1");
        if ($latestUploadStmt) {
            $latestUploadStmt->execute();
            $result = $latestUploadStmt->get_result();
            $latestUpload = $result->fetch_assoc();
            $latestUploadStmt->close();
            
            if ($latestUpload) {
                $csvId = $latestUpload['id'];
                // Get the base URL for API calls - homepage.php is at root, so api/ is relative
                // Use relative path since homepage.php is at the root level
                $apiUrl = 'api/csv_data.php';
                
                $chartsSection = '<section class="section" id="enrollment-analytics">';
                $chartsSection .= '<div class="container">';
                $chartsSection .= '<article class="card" aria-labelledby="analyticsHeading">';
                $chartsSection .= '<h3 id="analyticsHeading">Enrollment Analytics</h3>';
                $chartsSection .= '<div class="body" id="charts-container">';
                $chartsSection .= '<div class="charts-grid">';
                
                // Chart containers
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Total Enrollment by Course</h4>';
                $chartsSection .= '<canvas id="chartEnrollmentByCourse"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Year-wise Enrollment Breakdown</h4>';
                $chartsSection .= '<canvas id="chartYearBreakdown"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Overall Gender Distribution</h4>';
                $chartsSection .= '<canvas id="chartGenderDistribution"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Gender Comparison by Course</h4>';
                $chartsSection .= '<canvas id="chartGenderByCourse"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Total Enrollment by Year Level</h4>';
                $chartsSection .= '<canvas id="chartEnrollmentByYear"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '<div class="chart-wrapper">';
                $chartsSection .= '<h4>Top 10 Courses by Enrollment</h4>';
                $chartsSection .= '<canvas id="chartTopCourses"></canvas>';
                $chartsSection .= '</div>';
                
                $chartsSection .= '</div>';
                $chartsSection .= '</div>';
                $chartsSection .= '</article>';
                $chartsSection .= '</div>';
                $chartsSection .= '</section>';
                
                // Add Chart.js and initialization script
                $chartsSection .= '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';
                $chartsSection .= '<script src="asset/js/enrollment-charts.js"></script>';
                $chartsSection .= '<script>';
                $chartsSection .= 'window.CSV_API_URL = "' . htmlspecialchars($apiUrl) . '";';
                $chartsSection .= 'window.CSV_ID = ' . $csvId . ';';
                $chartsSection .= '(function() {';
                $chartsSection .= '  function initCharts() {';
                $chartsSection .= '    if (typeof Chart === "undefined") {';
                $chartsSection .= '      console.error("Chart.js not loaded yet, retrying...");';
                $chartsSection .= '      setTimeout(initCharts, 100);';
                $chartsSection .= '      return;';
                $chartsSection .= '    }';
                $chartsSection .= '    if (typeof initEnrollmentCharts === "function") {';
                $chartsSection .= '      console.log("Initializing charts with CSV ID: ' . $csvId . '");';
                $chartsSection .= '      initEnrollmentCharts(window.CSV_ID, window.CSV_API_URL);';
                $chartsSection .= '    } else {';
                $chartsSection .= '      console.error("initEnrollmentCharts function not found");';
                $chartsSection .= '    }';
                $chartsSection .= '  }';
                $chartsSection .= '  if (document.readyState === "loading") {';
                $chartsSection .= '    document.addEventListener("DOMContentLoaded", initCharts);';
                $chartsSection .= '  } else {';
                $chartsSection .= '    setTimeout(initCharts, 100);';
                $chartsSection .= '  }';
                $chartsSection .= '})();';
                $chartsSection .= '</script>';
            }
        }
    }

    echo $chartsSection;
    ?>
  </main>
  <footer id="contact">
    <div class="container foot">
      <div class="footer-brand-block">
        <div class="footer-logo">
          <img src="images/PUPLogo.png" alt="PUP Logo" />
          <div>
            <p class="campus-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p>
            <p class="campus-sub">Biñan Campus</p>
          </div>
        </div>
        <p class="footer-tagline">Serving The Nation Through Quality Public Education.</p>
        <p class="stats">Page Views: <?php echo $page_views ?? 0; ?> &#8226; Total Visitors: <?php echo $total_visitors ?? 0; ?></p>
      </div>
      <div class="footer-links">
        <div class="footer-column">
          <h4>Academics</h4>
          <a href="pages/programs.php">Academic Programs</a>
          <a href="pages/admission_guide.php">Admission</a>
          <a href="pages/services.php">Student Services</a>
          <a href="pages/forms.php">Downloadable Forms</a>
        </div>
        <div class="footer-column">
          <h4>Campus</h4>
          <a href="pages/campuslife.php">Campus Life</a>
          <a href="pages/announcement.php">Announcements</a>
          <a href="pages/faq.php">FAQs</a>
          <a href="pages/contact.php">Contact Us</a>
        </div>
        <div class="footer-column">
          <h4>Resources</h4>
          <a href="homepage.php#calendar">Academic Calendar</a>
          <a href="pages/about.php#vision-mission">Mission &amp; Vision</a>
          <a href="pages/about.php#history">Campus History</a>
          <a href="privacy-policy.php">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="sub container">
      <span>&copy; <span id="year"></span> PUP Biñan Campus. All rights reserved.</span>
    </div>
  </footer>
</body>

</html>
