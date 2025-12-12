<?php
$basePath = $basePath ?? '';
$page_title = $page_title ?? 'PUP Biñan Campus - Official Website';
$page_description = $page_description ?? 'Polytechnic University of the Philippines - Biñan Campus official website.';
$body_class = isset($body_class) ? trim((string) $body_class) : '';
$activePage = $activePage ?? '';
$siteTitle = $siteTitle ?? 'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES';
$campusName = $campusName ?? 'Bi&ntilde;an Campus';
$logoPath = $logoPath ?? 'images/PUPLogo.png';

if (strpos($logoPath, '../') === 0) {
    $logoPath = substr($logoPath, 3);
}

$logoSrc = $basePath . ltrim($logoPath, '/');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="theme-color" content="#7a0019" />
  <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($basePath); ?>asset/PUPicon.png">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>asset/css/site.css" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>asset/css/home.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script defer src="<?php echo htmlspecialchars($basePath); ?>asset/js/homepage.js"></script>
  <script defer src="<?php echo htmlspecialchars($basePath); ?>asset/js/mobile-nav.js"></script>
</head>

<body class="<?php echo htmlspecialchars($body_class); ?>">
  <div class="topbar" role="banner">
    <div class="container topbar-inner">
      <div class="seal" aria-hidden="true">
        <a href="<?php echo htmlspecialchars($basePath); ?>homepage.php">
          <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="PUP Logo" />
        </a>
      </div>
      <div class="brand" aria-label="Campus name">
        <span class="u"><?php echo htmlspecialchars($siteTitle); ?></span>
        <span class="c"><?php echo $campusName; ?></span>
      </div>
    </div>
  </div>

  <header>
    <div class="container nav">
      <div class="brand-nav">
        <div class="seal" aria-hidden="true">
          <a href="<?php echo htmlspecialchars($basePath); ?>homepage.php">
            <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="PUP Logo" />
          </a>
        </div>

        <div class="brand" aria-label="Campus name">
          <span class="u"><?php echo htmlspecialchars($siteTitle); ?></span>
          <span class="c"><?php echo $campusName; ?></span>

          <nav aria-label="Primary" class="menu" id="menu">
            <a href="<?php echo htmlspecialchars($basePath); ?>homepage.php" <?php echo $activePage === 'home' ? 'class="is-active"' : ''; ?>>Home</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/about.php" <?php echo $activePage === 'about' ? 'class="is-active"' : ''; ?>>About</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/programs.php" <?php echo $activePage === 'programs' ? 'class="is-active"' : ''; ?>>Academic Programs</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/admission_guide.php" <?php echo $activePage === 'admission' ? 'class="is-active"' : ''; ?>>Admission</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/services.php" <?php echo $activePage === 'services' ? 'class="is-active"' : ''; ?>>Student Services</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/campuslife.php" <?php echo $activePage === 'campuslife' ? 'class="is-active"' : ''; ?>>Campus Life</a>
            <a href="<?php echo htmlspecialchars($basePath); ?>pages/contact.php" <?php echo $activePage === 'contact' ? 'class="is-active"' : ''; ?>>Contact Us</a>
          </nav>
        </div>
      </div>

      <form class="search-form" action="<?php echo htmlspecialchars($basePath); ?>search.php" method="get" role="search" aria-label="Site search">
        <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
        <button type="submit" aria-label="Search">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </form>

      <button id="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false" aria-controls="mobile-nav-panel">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <div id="mobile-nav-panel" aria-hidden="true" role="navigation">
      <div class="mobile-nav-header">
        <div class="mobile-nav-logo">
          <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="PUP Logo" />
          <div class="mobile-nav-brand">
            <span class="u"><?php echo htmlspecialchars($siteTitle); ?></span>
            <span class="c"><?php echo $campusName; ?></span>
          </div>
        </div>
      </div>
      <nav class="mobile-nav-menu">
        <div class="mobile-nav-search">
          <form class="mobile-nav-search-form" action="<?php echo htmlspecialchars($basePath); ?>search.php" method="get" role="search" aria-label="Site search">
            <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
            <button type="submit" aria-label="Search">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </form>
        </div>
        <a href="<?php echo htmlspecialchars($basePath); ?>homepage.php" class="mobile-nav-link <?php echo $activePage === 'home' ? 'is-active' : ''; ?>">Home</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/about.php" class="mobile-nav-link <?php echo $activePage === 'about' ? 'is-active' : ''; ?>">About</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/programs.php" class="mobile-nav-link <?php echo $activePage === 'programs' ? 'is-active' : ''; ?>">Academic Programs</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/admission_guide.php" class="mobile-nav-link <?php echo $activePage === 'admission' ? 'is-active' : ''; ?>">Admission</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/services.php" class="mobile-nav-link <?php echo $activePage === 'services' ? 'is-active' : ''; ?>">Student Services</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/campuslife.php" class="mobile-nav-link <?php echo $activePage === 'campuslife' ? 'is-active' : ''; ?>">Campus Life</a>
        <a href="<?php echo htmlspecialchars($basePath); ?>pages/contact.php" class="mobile-nav-link <?php echo $activePage === 'contact' ? 'is-active' : ''; ?>">Contact Us</a>
      </nav>
    </div>
  </header>
