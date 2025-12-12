<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'campus_officials.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'campus_officials');
$page_views = get_page_visit_count($conn, $page_name);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Load campus officials from database
$branchOfficials = [];
$supportPersonnel = [];

// Check if table exists
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'campus_officials'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
    $checkTable->free();
    
    // Fetch branch officials
    $branchResult = $conn->query("SELECT * FROM campus_officials WHERE type = 'branch_official' ORDER BY display_order, name");
    if ($branchResult) {
        while ($row = $branchResult->fetch_assoc()) {
            $branchOfficials[] = [
                'name' => $row['name'],
                'role' => $row['role'],
                'photo' => !empty($row['photo_path']) ? '../' . $row['photo_path'] : '../images/PUPLogo.png'
            ];
        }
        $branchResult->free();
    }
    
    // Fetch support personnel
    $supportResult = $conn->query("SELECT * FROM campus_officials WHERE type = 'support_personnel' ORDER BY display_order, name");
    if ($supportResult) {
        while ($row = $supportResult->fetch_assoc()) {
            $supportPersonnel[] = [
                'name' => $row['name'],
                'role' => $row['role'],
                'photo' => !empty($row['photo_path']) ? '../' . $row['photo_path'] : '../images/PUPLogo.png'
            ];
        }
        $supportResult->free();
    }
} else {
    // Fallback to default data if table doesn't exist
    $branchOfficials = [
        ['name' => 'Margarita T. Sevilla, Ph. D.', 'role' => 'Campus Director', 'photo' => '../images/Sevilla, Margarita.jpg'],
        ['name' => 'Archie C. Arevalo, LPT, MA', 'role' => 'Head of Academic Programs', 'photo' => '../images/AREVALO, ARCHIE.jpg'],
        ['name' => 'Cheryl Joyce D. Jurado, LPT, MEM', 'role' => 'Head of Student Affairs and Services', 'photo' => '../images/JURADO, CHERYL JOYCE D..jpg'],
        ['name' => 'Ma. Gemalyn S. Austria, MEM', 'role' => 'Head of Admission and Registration', 'photo' => '../images/Maam Gem.png'],
        ['name' => 'Manalo David B. Rivera', 'role' => 'Collecting and Disturbing Officer', 'photo' => '../images/RIVERA, MANOLO DAVID.jpg'],
        ['name' => 'Genino P. Abelida, Jr., LPT', 'role' => 'Administrative Officer', 'photo' => '../images/Sir Abelida.png'],
        ['name' => 'Rhod Phillip Corro, LPT, MBA', 'role' => 'Research and Extension Coordinator', 'photo' => '../images/Sir Corro.png'],
    ];
    
    $supportPersonnel = [
        ['name' => 'Jerwin A. Bismar', 'role' => 'Guidance Advocate', 'photo' => '../images/Sir Jerwin.png'],
        ['name' => 'Francheska Louise M. Bernardo, RL, MUS', 'role' => 'Campus Librarian', 'photo' => '../images/BERNARDO_FRANCHESCA LOUISE-PUP FLAG.JPG'],
        ['name' => 'Noemi Apostol', 'role' => 'Sports Coordinator', 'photo' => '../images/Maam Apostol.png'],
        ['name' => 'Widonna B. Cuenca', 'role' => 'Admission and Registration Staff', 'photo' => '../images/CUENCA, WIDONNA.jpg'],
        ['name' => 'Engr. Jhun Jhun B. Maravilla', 'role' => 'IT Coordinator/ Laboratory Technician', 'photo' => '../images/MARAVILLA, JHUN JHUN B..jpg'],
        ['name' => 'Engr. Aaron A. Atienza', 'role' => 'Student Records Officer/NSTP Coordinator', 'photo' => '../images/ATIENZA, AARON A..jpg'],
        ['name' => 'Nestleson H. Alagon', 'role' => 'Administrative Aide', 'photo' => '../images/Sir Alagon.png'],
        ['name' => 'Mary Jane G. Malonzo, LPT', 'role' => 'Admission and Registration Staff', 'photo' => '../images/MALONZO, JANE.jpg'],
        ['name' => 'Kaira Marie D. Formento, RL, MUS', 'role' => 'Campus Librarian', 'photo' => '../images/MAAM KAI.jpeg'],
        ['name' => 'Rochelle Anne Masangkay, RN', 'role' => 'Nurse', 'photo' => '../images/MASANGKAY, ROCHELL.jpg'],
        ['name' => 'Paul Vincent A. Vierneza, RN', 'role' => 'Nurse', 'photo' => '../images/PUPLogo.png'],
        ['name' => 'Romina Concepcion', 'role' => 'Nursing Aide', 'photo' => '../images/ROMINA.jpg'],
    ];
}

$branchHighlight = $branchOfficials[0] ?? null;
$branchGrid = array_slice($branchOfficials, 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Campus Officials - PUP Bi&ntilde;an Campus</title>
  <meta name="description" content="Directory layout for PUP Bi&ntilde;an Campus officials and unit heads." />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/about.css" />
  <link rel="stylesheet" href="../asset/css/campusofficial.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
        <span class="c">Bi&ntilde;an Campus</span>
      </div>
    </div>
  </div>

  <header role="banner" class="header">
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
            <a class="is-active" href="./about.php">About</a>
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
    </div>
  </header>

  <main id="content">
    <!-- HERO SECTION -->
    <section class="page-hero">
      <div class="container">
        <div class="page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="page-hero-text">
            <h1 class="page-hero-title">THE BRANCH <span class="page-hero-accent">OFFICIALS</span></h1>
           
        </div>
      </div>
    </section>

    <section class="section campusoff-structure" id="uis-officials" aria-label="Campus officials preview">
      <div class="campus-container">
      
        <div class="campusoff-panels">
          <article class="campusoff-panel">
  

            <?php if ($branchHighlight): ?>
              <div class="campusoff-highlight" role="listitem">
                <figure class="campusoff-photo">
                  <img src="<?php echo htmlspecialchars($branchHighlight['photo']); ?>"
                    alt="<?php echo htmlspecialchars($branchHighlight['name']); ?>">
                </figure>
                <p class="campusoff-name"><?php echo htmlspecialchars($branchHighlight['name']); ?></p>
                <p class="campusoff-role"><?php echo htmlspecialchars($branchHighlight['role']); ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($branchGrid)): ?>
              <div class="campusoff-grid" role="list">
                <?php foreach ($branchGrid as $official): ?>
                  <div class="campusoff-slot" role="listitem">
                    <figure class="campusoff-photo">
                      <img src="<?php echo htmlspecialchars($official['photo']); ?>"
                        alt="<?php echo htmlspecialchars($official['name']); ?>">
                    </figure>
                    <p class="campusoff-name"><?php echo htmlspecialchars($official['name']); ?></p>
                    <p class="campusoff-role"><?php echo htmlspecialchars($official['role']); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="campusoff-empty">Add more branch officials to the <code>$branchOfficials</code> array to display
                them here.</p>
            <?php endif; ?>
           
           <div class="personel"> 

              <h3>Support Personnel</h3>
</div>
            <?php if (!empty($supportPersonnel)): ?>
              <div class="campusoff-grid support" role="list">
                <?php foreach ($supportPersonnel as $staff): ?>
                  <div class="campusoff-slot" role="listitem">
                    <figure class="campusoff-photo">
                      <img src="<?php echo htmlspecialchars($staff['photo']); ?>"
                        alt="<?php echo htmlspecialchars($staff['name']); ?>">
                    </figure>
                    <p class="campusoff-name"><?php echo htmlspecialchars($staff['name']); ?></p>
                    <p class="campusoff-role"><?php echo htmlspecialchars($staff['role']); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="campusoff-empty">Populate the <code>$supportPersonnel</code> array to show support staff in this
                section.</p>
            <?php endif; ?>
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
