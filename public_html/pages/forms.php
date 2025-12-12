<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'forms.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'forms');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Get forms metadata and styles from site_settings
function get_setting_safe(mysqli $conn, string $key, string $default = ''): string {
    $sql = "SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (string)$row['setting_value'] : $default;
}

// Get forms metadata
$metadataJson = get_setting_safe($conn, 'forms_metadata', '{}');
$formsMetadata = json_decode($metadataJson, true) ?: [];

// Scan forms directory
$formsDir = __DIR__ . '/../files/forms';
$allForms = [];
if (is_dir($formsDir)) {
    $files = scandir($formsDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $formsDir . '/' . $file;
        if (is_file($filePath)) {
            $filename = $file;
            $metadata = $formsMetadata[$filename] ?? [
                'title' => pathinfo($filename, PATHINFO_FILENAME),
                'description' => '',
                'category' => 'Other Forms',
                'display_order' => 0
            ];
            $allForms[] = [
                'filename' => $filename,
                'path' => '../files/forms/' . $filename,
                'metadata' => $metadata
            ];
        }
    }
}

// Sort forms by category, then display_order, then title
usort($allForms, function($a, $b) {
    $catA = $a['metadata']['category'] ?? '';
    $catB = $b['metadata']['category'] ?? '';
    if ($catA !== $catB) {
        return strcmp($catA, $catB);
    }
    $orderA = $a['metadata']['display_order'] ?? 0;
    $orderB = $b['metadata']['display_order'] ?? 0;
    if ($orderA !== $orderB) {
        return $orderA <=> $orderB;
    }
    $titleA = $a['metadata']['title'] ?? '';
    $titleB = $b['metadata']['title'] ?? '';
    return strcmp($titleA, $titleB);
});

// Group forms by category
$formsByCategory = [];
foreach ($allForms as $form) {
    $category = $form['metadata']['category'] ?? 'Other Forms';
    if (!isset($formsByCategory[$category])) {
        $formsByCategory[$category] = [];
    }
    $formsByCategory[$category][] = $form;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Downloadable Forms - PUP Bi&ntilde;an Campus</title>
  <meta name="description" content="Downloadable forms and documents for PUP Bi&ntilde;an Campus." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/forms.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script defer src="../asset/js/homepage.js"></script>
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
            <p class="page-hero-label">STUDENT RESOURCES</p>
            <h1 class="page-hero-title">Downloadable <span class="page-hero-accent">Forms</span></h1>
            <p class="page-hero-description">Access and download official forms, templates, and documents used by PUP Biñan Campus offices. Forms are organized by category for easy navigation.</p>
          </div>
        </div>
      </div>
    </section>

     <nav class="breadcrumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="../homepage.php">Home</a></li>
            <li><span>Forms</span></li>
          </ol>
        </nav>

    <section class="section" id="forms-content">
      <div class="container">
        <?php if (!empty($formsByCategory)): ?>
        <?php foreach ($formsByCategory as $category => $forms): ?>
    <div class="forms-header">
        <h2><?php echo htmlspecialchars($category); ?></h2>
    </div>

    <div class="forms-grid" role="list">
        <?php foreach ($forms as $form): ?>
            <article class="card form-card" role="listitem">
                <div class="form-body">
                    <div class="form-copy">
                        <?php
                        $ext = strtolower(pathinfo($form['filename'], PATHINFO_EXTENSION));
                        $link = htmlspecialchars($form['path'], ENT_QUOTES);

                        if ($ext === 'pdf') {
                            // PDFs: open in new tab with bookmarks/overview panel
                            $link .= '#view=FitH&navpanes=1';
                        } elseif ($ext === 'docx') {
                            // DOCX: open in Google Docs Viewer in a new tab
                            $link = 'https://docs.google.com/gview?url=' . urlencode('https://pupbcwebsite.com/files/forms/' . $form['path']) . '&embedded=true';
                        }
                        ?>
                        <h3>
                            <a href="<?php echo $link; ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($form['metadata']['title'], ENT_QUOTES); ?>
                            </a>
                        </h3>

                        <?php if (!empty($form['metadata']['description'])): ?>
                            <p><?php echo htmlspecialchars($form['metadata']['description'], ENT_QUOTES); ?></p>
                        <?php endif; ?>
                    </div>
                    <figure class="form-media">
                        <img src="../images/filesicon.png" alt="<?php echo htmlspecialchars($form['metadata']['title'], ENT_QUOTES); ?>" />
                    </figure>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

        <?php else: ?>
          <div class="forms-header">
            <h2>Forms</h2>
          </div>
          <div class="forms-grid" role="list">
            <p>No forms available at this time. Please check back later.</p>
          </div>
        <?php endif; ?>
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
