<?php
include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../DATAANALYTICS/page_views.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, 'about.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

record_page_visit($conn, $page_name, $ip, $today);

// Log page view for page views analytics
log_page_view($conn, 'about');
$page_views = get_page_visit_count($conn, $page_name);

$total_visitors = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM visitors")) {
  $row = $result->fetch_assoc();
  $total_visitors = (int)($row['total'] ?? 0);
  $result->free();
}

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$history_items = [
    [
        'text' => 'The Polytechnic University of the Philippines – Biñan (PUP Biñan) traces its beginnings to a Memorandum of Agreement signed by the Municipality of Biñan and the Polytechnic University of the Philippines on 15 September 2009. 
        This partnership established the campus as part of PUP’s strategy to widen access to affordable, high‑quality public higher education in Laguna.',
        'image' => '../images/earlycampusphoto.jpg'
    ],
    
    [
        'text' => 'From the outset, PUP Biñan aligned its offerings with community and regional economic needs, opening programs in information technology, business, education, social sciences, and engineering—fields that match Biñan’s growing economy and talent pipeline.',
        'image' => '../images/BEED.png'
    ],
    [
        'text' => 'Throughout the 2010s, PUP Biñan steadily expanded its reach with the combined support of the local government and the University. Upgrades to facilities and student services strengthened the campus’s role as a community‑anchored institution serving learners from Biñan and nearby cities.',
        'image' => '../images/pupsite.jpg'
    ],
    [
        'text' => 'A major milestone came on 2 February 2024—during the city’s Araw ng Biñan celebration—when the local government inaugurated a second PUP Biñan site in Barangay Canlalay. The new 2,500 m² facility, acquired for ₱150 million, houses a four‑storey academic building with 18 classrooms and four fully‑functioning laboratories built especially for STEM education.',
        'image' => '../images/direk.png'
    ],
    [
        'text' => 'Today, PUP Biñan stands as a vital member of the PUP network in Laguna. Anchored by its 2009 founding and marked by the 2024 expansion and the 2025 commencement milestone, the campus exemplifies PUP’s enduring commitment to accessible, practice‑oriented public education. 
        It remains focused on widening opportunity, cultivating local talent for regional industry, and delivering transformative education to students of Biñan and beyond.',
        'image' => '../images/BSIT.png'
    ]
];


// Fetch About page settings
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

$aboutSettingKeys = [
  'about_overview_image',
  'about_overview_content',
  'about_mission_image',
  'about_vision_image',
  'about_history_content',
  'about_values_integrity',
  'about_values_excellence',
  'about_values_service',
  'about_values_innovation',
  'about_strategic_goals_image'
];

$aboutSettings = fetchSettings($conn, $aboutSettingKeys);

// Set defaults if not set (add ../ prefix if not already present)
$overviewImagePath = !empty($aboutSettings['about_overview_image']) ? $aboutSettings['about_overview_image'] : 'images/pupcollage.jpg';
$overviewImage = (strpos($overviewImagePath, '../') === 0) ? $overviewImagePath : '../' . $overviewImagePath;

$missionImagePath = !empty($aboutSettings['about_mission_image']) ? $aboutSettings['about_mission_image'] : 'images/pupmission.jpg';
$missionImage = (strpos($missionImagePath, '../') === 0) ? $missionImagePath : '../' . $missionImagePath;

$visionImagePath = !empty($aboutSettings['about_vision_image']) ? $aboutSettings['about_vision_image'] : 'images/pupvision.jpg';
$visionImage = (strpos($visionImagePath, '../') === 0) ? $visionImagePath : '../' . $visionImagePath;

$strategicGoalsImagePath = !empty($aboutSettings['about_strategic_goals_image']) ? $aboutSettings['about_strategic_goals_image'] : 'images/strategicgoals.jpg';
$strategicGoalsImage = (strpos($strategicGoalsImagePath, '../') === 0) ? $strategicGoalsImagePath : '../' . $strategicGoalsImagePath;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>About — PUP Biñan Campus</title>
  <meta name="description"
    content="About PUP Biñan Campus — history, vision, mission, values, and leadership." />
  <meta name="theme-color" content="#7a0019" /> <!-- note: we're inside /pages so use ../ -->
  <link rel="icon" type="image/png" href="../asset/PUPicon.png">
  <link rel="stylesheet" href="../asset/css/site.css" />
  <link rel="stylesheet" href="../asset/css/about.css" />
  <link rel="stylesheet" href="../asset/css/history-gallery.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <script defer src="../asset/js/homepage.js"></script>
  <script defer src="../asset/js/mobile-nav.js"></script>
  <script defer src="../asset/js/history-gallery.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

  <style>
  /* Local styles for About page selector */
  .about-select-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .about-select {
    padding: .55rem .7rem;
    border: 1px solid var(--border);
    border-radius: .65rem;
    font: inherit;
  }

  /* Strategic Goals image card: remove inner padding and clip to rounded corners */
  
</style>
</head>

<body>
  <!-- Top ribbon (match homepage) -->
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

  <!-- Header / Nav (same structure and dropdown as home) -->
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
        <span class="c">Biñan Campus</span>
    

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
        <a href="./about.php" class="mobile-nav-link is-active">About</a>
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
    <section class="about-page-hero">
      
      <div class="container">
        <div class="about-page-hero-inner">
          <!-- Left Side: Text Content -->
          <div class="about-page-hero-text">
            <h1 class="about-page-hero-title">About <span class="about-page-hero-accent">PUP Biñan Campus</span></h1>
            <p class="about-page-hero-description">Learn about our history, vision, mission, goals, and the values that guide our PUP community.</p>
          </div>
        </div>
      </div>
    </section> <!-- Quick dropdown to jump within About page -->

 
    <!-- <section class="section" aria-label="About section selector">      <div class="container about-select-wrap">        <label for="aboutJump"><b>Jump to:</b></label>        <select id="aboutJump" class="about-select" aria-label="Jump to About section">          <option value="">Select a section…</option>          <option value="#overview">Campus Overview</option>          <option value="#vision-mission">Mission &amp; Vision</option>          <option value="#history">History</option>          <option value="#values">Core Values</option>        </select>      </div>    </section> -->
    <!-- OVERVIEW + IMAGE (cards & grid like homepage sections) -->
    <section class="section" id="overview">
      <div class="container overview-container">


        
      
        <article class="card overview-card">

        

        

          <!-- LEFT: Text Content -->
          <div class="body">
            

            <div class="ovv-section">
              <div class="overview-image">
                <img src="<?php echo htmlspecialchars($overviewImage); ?>" alt="PUP Biñan campus collaboration collage" />
              </div>
            </div>

            <?php if (!empty($aboutSettings['about_overview_content'])): ?>
              <?php echo $aboutSettings['about_overview_content']; ?>
            <?php else: ?>


              <div class="overview-psection">
                <div class="col1">
                  <p class="pupbc-vline">PUPBC</p>
                </div>


                <div class="col2">
                  <p>Is a local campus of the Polytechnic University of the Philippines (PUP), established to serve the students of Biñan City and nearby communities.
                    Since its founding in 2009 through a partnership between the Municipality of Biñan and PUP,
                    the campus has grown into a vibrant learning environment offering programs in
                    information technology, education, social sciences, business, and engineering.</p>


                  <p>Is part of one of the country’s largest state university systems,
                    with more than twenty campuses across Luzon, including Metro Manila, Central Luzon, and Southern Luzon.
                    According to official information as of October 2023, the system-wide enrollment was approximately 84,897 students.
                    The campus provides inclusive and affordable public higher education,
                    preparing graduates to contribute meaningfully to their communities <sup><a href="https://rmbretrobuild.com/polytechnic-university-of-the-philippines-pup/">[1]</a><a href="https://www.foi.gov.ph/requests/student-population-academic-year-2023-2024/">[2]</a></sup>.</p>

                  <p>In February 2024, during the city’s Araw ng Biñan celebration, Mayor Walfredo Dimaguila Jr. inaugurated PUP Biñan’s 
                    second campus, located in Barangay Canlalay, the College of Information Technology and Engineering (CITE). The four-storey,
                     2,500 m² facility, acquired by the City of Biñan, houses 18 classrooms and seven laboratories designed primarily for 
                     Information Technology and Engineering programs. The expansion highlights Biñan City’s commitment to making quality higher education accessible, 
                     ensuring every deserving student has the opportunity to study in a modern academic environment.<sup><a href="https://pia.gov.ph/news/pup-opens-2nd-campus-in-binan-laguna/">[PIA-Laguna, 2024]</a></sup>.</p>

                  <p>The Biñan LGU and PUP continue to collaborate on future expansions, including enhanced digital infrastructure and new laboratories to strengthen research and innovation capabilities.</p>

                  <p>With dedicated faculty, modern facilities, and a student-focused environment,
                    PUP Biñan continues to provide transformative education and life-changing experiences,
                    empowering students to achieve professional success and become responsible citizens contributing to society.</p>
                </div>
              </div>
          </div>

          <hr class="divider">



          <div class="acadoffer-section">
  <div class="acadoffer-text">
    <h3>Academic Offerings</h3>
    <p>
      PUP Biñan provides a range of academic offerings in technology, business, and applied sciences,
      designed to prepare students for professional success. Current programs include:
    </p>

    <!-- Undergraduate -->
    <h5>Undergraduate Degrees:</h5>
    <ul>
      <li>Bachelor of Science in Information Technology</li>
      <li>Bachelor of Science in Computer Engineering</li>
      <li>Bachelor of Science Industrial Engineering</li>
      <li>Bachelor of Science in Business Administration</li>
      <li>Bachelor of Science in Psychology</li>
      <li>Bachelor in Elementary Education</li>
      <li>Bachelor in Secondary Education Major in English</li>
      <li>Bachelor in Secondary Education Major in Social Studies</li>
    </ul>

    <!-- Diploma -->
    <h5>Diploma Programs (Ladderized):</h5>
    <p style="font-size: 15px; font-family: 'Times New Roman', Times, serif; margin:0; margin-top:10px; color:#333; font-weight:100;">
      These ladderized programs allow students to earn industry-recognized diplomas while continuing toward a bachelor’s degree.
    </p>
    <ul>
      <li>Diploma in Computer Engineering Technology</li>
      <li>Diploma in Information Technology</li>
    </ul>
  </div>
</div>

          <hr class="divider">

          <h3>Mandate, Research & Community Linkages</h3>
          <p>Beyond classrooms, PUP upholds a system-wide mandate that champions public service, 
            research, and extension programs anchored on strategic goals to intensify research, 
            strengthen sustainable and impactful extension programs, and expand local–international partnerships. 
            These principles shape PUP Biñan’s commitment to community engagement and meaningful linkages, 
            ensuring that education extends beyond academic walls to create real-world impact.<sup><a href="#ov-ref-1">[1]</a></sup>.

          </p>

          <h3>Student Support & Opportunities</h3>
          <p>
           PUP Biñan provides comprehensive support to ensure students succeed academically, 
           professionally, and personally. The City's Iskolar ng Biñan program covers tuition 
           and miscellaneous fees for qualified scholars enrolled in state universities and colleges, 
           and provides an additional allowance of ₱10,000 per semester, helping students focus on 
           their studies without financial burden.<sup><a href="https://www.binan.gov.ph/iskolar-ng-binan-inb-recipients/">[inb]</a></sup>.
          </p>

          <p>
            Beyond scholarships, students benefit from PUP’s career support programs. The <em>ARCDO</em> and <em>PUP JobPOST</em> portal connect learners to internships (OJT), career talks, and job fairs in partnership with local and industry organizations <sup><a href="https://www.pup.edu.ph/studentservices/arcdo/">[3]</a></sup>.
            Guidance services, mentorship, and student organizations further enhance personal growth and leadership skills, fostering well-rounded graduates ready to contribute to their communities.
          </p>

          <hr class="divider">

          <section class="section mission" id="vision-mission">
             <div class="logooncenter">
              <img src="../images/PUPLogo.png" alt="PUP LOGO" />
            </div>
            <div class="container split">

           
           
              <div class="body"> <img src="<?php echo htmlspecialchars($visionImage); ?>" alt="PUP Biñan Vision" /> </div>
  <div class="body"> <img src="<?php echo htmlspecialchars($missionImage); ?>" alt="PUP Biñan Mission" />   </div>
            </div>

    

      <hr class="dividermvg">

      
      <div class="goals">
          <div class="goalsimage">
            <img src="../images/strategicgoals.jpg"alt="Strategic Goals">
          </div>
    
      </div>

                
              <div class="binangoals-section">
              <div class="pupbcgoals">
  
    <hr class="dividerbcg">
 
    <div class="core-values-header">CORE VALUES</div>
       <hr class="dividerbcg">
 
        
          
          
          <div class="core-value-item">
            <span class="core-value-letter">I</span>
            <div class="core-value-content">
              <h3>INTEGRITY AND ACCOUNTABILITY</h3>
              
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">N</span>
            <div class="core-value-content">
              <h3>NATIONALISM</h3>
             
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">S</span>
            <div class="core-value-content">
              <h3>SENSE OF SERVICE</h3>
              
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">P</span>
            <div class="core-value-content">
              <h3>PASSION FOR LEARNING AND INNOVATION</h3>
              
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">I</span>
            <div class="core-value-content">
              <h3>INCLUSIVITY</h3>
             
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">R</span>
            <div class="core-value-content">
              <h3>RESPECT FOR HUMAN RIGHTS AND THE ENVIRONMENT</h3>
              
            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">E</span>
            <div class="core-value-content">
              <h3>EXCELLENCE</h3>

            </div>
          </div>
          
          <div class="core-value-item">
            <span class="core-value-letter">D</span>
            <div class="core-value-content">
              <h3>DEMOCRACY</h3>
          
   
          </div>
        </div>
 

                    
                   <div><h1>PUP Biñan campus goals</h1></div>


      <hr class="dividerbcg">
                   <div class="goal-item">
    <span class="goal-num">I</span>
    <p>Offer a globally-focused research oriented and internationally competitive curricula in the diploma and undergraduated programs.</p>
  </div>

  <div class="goal-item">
    <span class="goal-num">II</span>
    <p>Ensure conductive and productive learning environment by providing state-of-the-art physical facilities and other learning resources.</p>
  </div>

  <div class="goal-item">
    <span class="goal-num">III</span>
    <p>Develop and implement holistic student development programs.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">IV</span>
    <p>Continue to hone expertise of both teaching and non-teaching staff to ensure excellent, effective, and relevant learning process.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">V</span>
    <p>Inculcate appropriate values necessary to build God-fearing, humana, disciplined, nationalist, and democratic society.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">VI</span>
    <p>Pursue excellence in research and production of tecnologies for commercialization and livelihood improvement.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">VII</span>
    <p>Strengthen networks and partnerships for meaningful and relevant to project.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">VIII</span>
    <p>Participate in community development through research-based extension programs, timely and impactful community outreach activities, and other meaningful societal engagement.</p>
  </div>

   <div class="goal-item">
    <span class="goal-num">IX</span>
    <p>Tap potential students, faculty, administrative staff, and other stakeholders to formulate and implement institutional development programs.</p>
  </div>

  
    <hr class="dividerbcg">
 
                    
</div>
          

                </div>
                 
          </section>


        <?php endif; ?>
        <hr>
        


        </article>

         <div class="overview-quicklinks">
          <div class="quicklinks-card">
            <div class="quicklink-logo">
              <img src="../images/PUPLogo.png" alt="PUP Biñan Logo" />
            </div>
            <h4 class="quicklink-heading">Quick Links</h4> 
            <ul class="quicklink-list">
              <li><a href="./campus_officials.php" class="quicklink-item">Campus Officials</a></li>
              <li><a href="./history.php" class="quicklink-item">History</a></li>
              <li><a href="./puphymn.php" class="quicklink-item">PUP Hymn</a></li>
              <li><a href="./citizen_charter.php" class="quicklink-item">Citizen Charter</a></li>
            </ul>
          
          </div>
        </div>
        

        <!-- RIGHT: Quick Links (Outside Card) -->
       
      </div>
      
    </section>
    <!-- STRATEGIC GOALS (image replacement) -->
    
      <!-- <div id="visitor-stats" style="margin-top:20px; padding:10px; border-top:1px solid #ddd;">
    <p>Visitors Today: <span id="today">0</span></p>
    <p>Visitors This Week: <span id="week">0</span></p>
    <p>Visitors This Month: <span id="month">0</span></p>
    <p>Total Visitors: <span id="total">0</span></p>
  </div>

<div style="width:100%; max-width:600px; margin:20px auto; padding:0 1rem;">
  <canvas id="visitorsChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch("../DATAANALYTICS/visitors_chart.php")
  .then(res => res.json())
  .then(data => {
    const labels = data.map(item => item.day);
    const values = data.map(item => item.visits);

 const ctx = document.getElementById("visitorsChart").getContext("2d");

     const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "#f3b233");   // top color
    gradient.addColorStop(0.4, "#540013"); // middle color
    gradient.addColorStop(0.9, "#ffffffff");   // bottom color  

    new Chart(document.getElementById("visitorsChart"), {
      type: "bar", // you can change to 'bar'
      data: {
        labels: labels,
        datasets: [{
          label: "Visitors",
          data: values,
          borderColor: "#f3b233",
          backgroundColor: gradient,
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true }
        },
        scales: {
          x: { title: { display: true, text: "Date" }},
          y: { title: { display: true, text: "Number of Visitors" }, beginAtZero: true }
        }
      }
    });
  })
  .catch(err => console.error("Chart error:", err));
</script>

<script>
fetch("../DATAANALYTICS/visitors.php")
  .then(res => res.json())
  .then(data => {
    document.getElementById("today").textContent = data.today;
    document.getElementById("week").textContent  = data.week;
    document.getElementById("month").textContent = data.month;
    document.getElementById("total").textContent = data.total;
  })
  .catch(err => console.error("Visitor stats error:", err));
</script> -->

    <!-- CAMPUS LEADERSHIP    <section class="section" id="leadership">      <div class="container">        <article class="card">          <div class="body">            <h2>Campus Leadership</h2>            <p>List your campus director and unit heads here (names, positions, optional photos and contacts).</p>            <ul>              <li><b>Campus Director:</b> Name</li>              <li><b>Academic Affairs:</b> Name</li>              <li><b>Student Affairs:</b> Name</li>              <li><b>Registrar:</b> Name</li>            </ul>          </div>        </article>      </div>    </section> -->
    </main>
    <!-- Footer (same as homepage) -->
    <footer id="contact">
      <div class="container foot">
        <div class="footer-brand-block">
          <div class="footer-logo">
            <img src="../images/PUPLogo.png" alt="PUP Logo" />
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
      // Dropdown jump behavior for About page      (function () {        const sel = document.getElementById('aboutJump');        if (!sel) return;        sel.addEventListener('change', function () {          const val = sel.value;          if (!val) return;          const target = document.querySelector(val);          if (target && typeof target.scrollIntoView === 'function') {            target.scrollIntoView({ behavior: 'smooth', block: 'start' });          } else {            location.hash = val;          }          sel.selectedIndex = 0;        });      })();    
      
      // Initialize Leaflet Map for Campus Location
      document.addEventListener('DOMContentLoaded', function() {
        const mapElement = document.getElementById('campus-map');
        if (mapElement) {
          // PUP Biñan Campus coordinates
          const campusCoords = [14.3145, 121.0642]; // Biñan, Laguna
          
          // Initialize map
          const map = L.map('campus-map').setView(campusCoords, 15);
          
          // Add tile layer
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
          }).addTo(map);
          
          // Add marker for campus
          L.marker(campusCoords).addTo(map)
            .bindPopup('<strong>PUP Biñan Campus</strong><br>Biñan, Laguna, Philippines')
            .openPopup();
        }
      });
    </script>
</body>

</html>
