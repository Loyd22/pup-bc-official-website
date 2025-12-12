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
log_page_view($conn, 'event');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Fetch upcoming events from events table (single source of truth)
// Shows all events that haven't ended yet, including author information
function fetchUpcomingEvents(mysqli $conn, int $limit = 50): array
{
  $sql = "SELECT e.id, e.title, e.description, e.start_date, e.end_date, e.location, e.category, e.author, e.created_by,
                 a.full_name as author_name
          FROM events e
          LEFT JOIN admins a ON e.created_by = a.id
          WHERE e.end_date >= CURDATE() OR e.end_date IS NULL
          ORDER BY e.start_date ASC
          LIMIT ?";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = [];
  while ($row = $result->fetch_assoc()) {
    // Basic validation - only filter out truly empty titles
    $title = trim($row['title'] ?? '');
    if (!empty($title)) {
      $items[] = $row;
    }
  }
  $stmt->close();

  return $items;
}

$upcomingEvents = fetchUpcomingEvents($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Events - PUP Bi�an Campus</title>
  <meta name="description" content="Campus events, schedules, and calendar for PUP Bi�an Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/events.css" />
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
        <span class="c">Bi�an Campus</span>
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
          <span class="c">Bi�an Campus</span>
        </div>
      </div>
      <nav aria-label="Primary" class="menu" id="menu">
        <a href="../homepage.php">Home</a>
        <a href="./about.php">About</a>
        <a href="./programs.php">Academic Programs</a>
        <a href="./admission_guide.php">Admissions</a>
        <a href="./services.php">Student Services</a>
        <a class="is-active" href="./event.php">Events</a>
        <a href="./contact.php">Contact</a>
      </nav>
       <form class="search-form" action="../search.php" method="get" role="search" aria-label="Site search">
  <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
  <button type="submit" aria-label="Search">
    <i class="fa-solid fa-magnifying-glass"></i>
  </button>
</form>
    </div>
    <!--
    <div class="mobile-panel" id="mobilePanel" aria-hidden="true">
      <nav class="mobile-menu" aria-label="Mobile">
        <a href="../homepage.php">Home</a>
        <a href="./about.php">About</a>
        <a href="./programs.php">Academic Programs</a>
        <a href="./admission_guide.php">Admissions</a>
        <a href="./services.php">Student Services</a>
        <a class="is-active" href="./event.php">Events</a>
        <a href="./contact.php">Contact</a>
      </nav>
    </div>
    -->
  </header>

  <main id="content">
    <!-- HERO SECTION -->
    <section class="page-hero">
      <div class="container">
        <div class="page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="page-hero-text">
            <p class="page-hero-label">CAMPUS ACTIVITIES</p>
            <h1 class="page-hero-title">Campus <span class="page-hero-accent">Events</span></h1>
            <p class="page-hero-description">Browse upcoming events, university activities, and academic calendars at PUP Biñan Campus.</p>
          </div>
        </div>
      </div>
    </section>
    
    <section class="hero-old" style="display:none;">
      <div class="container hero-inner">
        <div>
          <h1>Campus <span class="accent">Events</span></h1>
          <p>Browse upcoming events, university activities, and academic calendars.</p>
          
        </div>
        <!--
        <aside class="hero-card" aria-label="Highlights">
          <div class="head">Highlights</div>
          <div class="list">
            <span class="pill"><span class="date">Oct</span> IT Week 2025</span>
            <span class="pill"><span class="date">Sep</span> Research Colloquium</span>
          </div>
        </aside>
        -->
      </div>
    </section>

    <section class="section" id="upcoming">
      <div class="container">
        <h2>Upcoming Events</h2>
        <?php if (!empty($upcomingEvents)): ?>
          <div class="grid cols-3">
            <?php foreach ($upcomingEvents as $event): ?>
              <article class="card">
                <div class="body">
                  <b><?php echo htmlspecialchars($event['title']); ?></b>
                  <br>
                  <small class="muted">
                    <?php echo htmlspecialchars(formatDateRange($event['start_date'], $event['end_date'])); ?>
                    <?php if (!empty($event['location'])): ?>
                      &middot; <?php echo htmlspecialchars($event['location']); ?>
                    <?php endif; ?>
                    <?php if (!empty($event['author_name'])): ?>
                      &middot; By <?php echo htmlspecialchars($event['author_name']); ?>
                    <?php elseif (!empty($event['author'])): ?>
                      &middot; By <?php echo htmlspecialchars($event['author']); ?>
                    <?php endif; ?>
                  </small>
                  <?php if (!empty($event['description'])): ?>
                    <p><?php echo htmlspecialchars(excerpt($event['description'], 100)); ?></p>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>No upcoming events scheduled at this time. Please check back soon.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="section" id="calendar">
      <div class="container">
        <article class="card" aria-labelledby="calHead">
          <div class="toolbar">
            <h3 id="calHead">All Events</h3>
          </div>
          <div class="body">
            <?php if (!empty($upcomingEvents)): ?>
              <div class="annos">
                <?php foreach ($upcomingEvents as $event): ?>
                  <div class="anno">
                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                    <small>
                      <?php echo htmlspecialchars(formatDateRange($event['start_date'], $event['end_date'])); ?>
                      <?php if (!empty($event['location'])): ?>
                        &middot; <?php echo htmlspecialchars($event['location']); ?>
                      <?php endif; ?>
                      <?php if (!empty($event['author_name'])): ?>
                        &middot; By <?php echo htmlspecialchars($event['author_name']); ?>
                      <?php elseif (!empty($event['author'])): ?>
                        &middot; By <?php echo htmlspecialchars($event['author']); ?>
                      <?php endif; ?>
                    </small>
                    <?php if (!empty($event['description'])): ?>
                      <p><?php echo htmlspecialchars(excerpt($event['description'])); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p>No events scheduled at this time.</p>
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

</body>
</html>
