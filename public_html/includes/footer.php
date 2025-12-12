<?php
$basePath = $basePath ?? '';
$logoPath = $logoPath ?? 'images/PUPLogo.png';

if (strpos($logoPath, '../') === 0) {
    $logoPath = substr($logoPath, 3);
}

$footerLogo = $basePath . ltrim($logoPath, '/');
$pageViews = isset($page_views) ? (int) $page_views : 0;
$totalVisitors = isset($total_visitors) ? (int) $total_visitors : 0;
?>
  <footer id="contact">
    <div class="container foot">
      <div class="footer-brand-block">
        <div class="footer-logo">
          <img src="<?php echo htmlspecialchars($footerLogo); ?>" alt="PUP Logo" />
          <div>
            <p class="campus-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p>
            <p class="campus-sub">Bi&ntilde;an Campus</p>
          </div>
        </div>
        <p class="footer-tagline">Serving The Nation Through Quality Public Education.</p>
        <p class="stats">Page Views: <?php echo $pageViews; ?> &#8226; Total Visitors: <?php echo $totalVisitors; ?></p>
      </div>
      <div class="footer-links">
        <div class="footer-column">
          <h4>Academics</h4>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/programs.php">Academic Programs</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/admission_guide.php">Admission</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/services.php">Student Services</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/forms.php">Downloadable Forms</a>
        </div>
        <div class="footer-column">
          <h4>Campus</h4>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/campuslife.php">Campus Life</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/announcement.php">Announcements</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/faq.php">FAQs</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/contact.php">Contact Us</a>
        </div>
        <div class="footer-column">
          <h4>Resources</h4>
          <a href="<?php echo htmlspecialchars($basePath); ?>homepage.php#calendar">Academic Calendar</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/about.php#vision-mission">Mission &amp; Vision</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>pages/about.php#history">Campus History</a>
          <a href="<?php echo htmlspecialchars($basePath); ?>privacy-policy.php">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="sub container">
      <span>&copy; <span id="year"></span> PUP Bi&ntilde;an Campus. All rights reserved.</span>
    </div>
  </footer>
</body>

</html>
