<?php
/**
 * Global Site-Wide Search - Titles and Subheadings Only
 * Searches page titles, h1-h3 headings, and key card titles across the site
 */

include __DIR__ . '/../DATAANALYTICS/db.php';
require_once __DIR__ . '/../DATAANALYTICS/page_visits.php';
require_once __DIR__ . '/../includes/search_static_sections.php';
require_once __DIR__ . '/../includes/logo_helper.php';

$page_name = basename(__FILE__, '.php');
$ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');
record_page_visit($conn, $page_name, $ip, $today);

// Get logo path from settings (same as homepage.php)
$logoPath = get_logo_path($conn);

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$queryEscaped = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

// Helper function to get setting
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

// Helper function to highlight keywords
function highlightKeywords($text, $query) {
    if (empty($text) || empty($query)) return htmlspecialchars($text);
    $escapedQuery = preg_quote(htmlspecialchars($query), '/');
    $pattern = '/(' . $escapedQuery . ')/i';
    return preg_replace($pattern, '<mark>$1</mark>', htmlspecialchars($text));
}

// Helper function to normalize URLs - prevent duplicate paths
function normalizeUrl($url, $currentDir = 'pages') {
    // If URL already starts with http://, https://, or absolute path (/), return as-is
    if (preg_match('/^(https?:\/\/|\/)/', $url)) {
        return $url;
    }
    
    // If URL starts with ../, it's going up one level - return as-is
    if (strpos($url, '../') === 0) {
        return $url;
    }
    
    // For relative URLs in the same directory, construct path relative to site root
    // Detect if we're in a subdirectory and adjust accordingly
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // e.g., /pupbcwebsite-main/pupbc-website/pages
    $basePath = dirname($scriptPath); // e.g., /pupbcwebsite-main/pupbc-website
    
    // If the URL is relative (no ../, no http, no /), it's in the same directory as current script
    // Construct path relative to document root
    $cleanUrl = ltrim($url, './');
    
    // If script is in pages/ subdirectory, and URL is a page in pages/, construct correctly
    if (strpos($scriptPath, '/pages') !== false && !strpos($cleanUrl, '../') === 0) {
        // URL is relative to pages/ directory - return as-is (browser will resolve correctly)
        return $cleanUrl;
    }
    
    return $cleanUrl;
}

// ============================================
// SEARCH INDEX: Page URLs, Titles, and Section Headings
// ============================================
$searchIndex = [
    // Homepage
    [
        'page_url' => '../homepage.php',
        'page_title' => 'Home',
        'sections' => [
            ['title' => 'Home', 'url' => '../homepage.php'],
            ['title' => 'News & Announcements', 'url' => '../homepage.php#news'],
            ['title' => 'Campus Calendar', 'url' => '../homepage.php#calendar'],
            ['title' => 'Enrollment Analytics', 'url' => '../homepage.php#enrollment-analytics'],
            ['title' => 'Campus Director', 'url' => '../homepage.php#director'],
        ]
    ],
    
    // About Page
    [
        'page_url' => 'about.php',
        'page_title' => 'About PUP Biñan',
        'sections' => [
            ['title' => 'About PUP Biñan Campus', 'url' => 'about.php'],
            ['title' => 'Campus Overview', 'url' => 'about.php#overview'],
            ['title' => 'Mission & Vision', 'url' => 'about.php#vision-mission'],
            ['title' => 'History', 'url' => 'about.php#history'],
            ['title' => 'Core Values', 'url' => 'about.php#values'],
            ['title' => 'Campus Officials', 'url' => 'about.php'],
        ]
    ],
    
    // Academic Programs
    [
        'page_url' => 'programs.php',
        'page_title' => 'Academic Programs',
        'sections' => [
            ['title' => 'Academic Programs', 'url' => 'programs.php'],
            ['title' => 'Undergraduate Offerings', 'url' => 'programs.php#offerings'],
            ['title' => 'Bachelor of Science in Business Administration Major in Human Resource Management', 'url' => '../programsinfos/BSBA.html'],
            ['title' => 'Bachelor of Science in Information Technology', 'url' => '../programsinfos/BSIT.html'],
            ['title' => 'Bachelor of Secondary Education Major in English', 'url' => '../programsinfos/BSEDEnglish.html'],
            ['title' => 'Bachelor of Secondary Education Major in Social Studies', 'url' => '../programsinfos/BSEDSocialStudies.html'],
            ['title' => 'Bachelor of Elementary Education', 'url' => '../programsinfos/BEED.html'],
            ['title' => 'Bachelor of Science in Computer Engineering', 'url' => '../programsinfos/BSCPE.html'],
            ['title' => 'Bachelor of Science in Industrial Engineering', 'url' => '../programsinfos/BSIE.html'],
            ['title' => 'Bachelor of Science in Psychology', 'url' => '../programsinfos/BSPsychology.html'],
            ['title' => 'Diploma in Computer Engineering Technology', 'url' => '../programsinfos/DCET.html'],
            ['title' => 'Diploma in Information Technology', 'url' => '../programsinfos/DIT.html'],
        ]
    ],
    
    // Admission Guide
    [
        'page_url' => 'admission_guide.php',
        'page_title' => 'Admission Guide',
        'sections' => [
            ['title' => 'Admission Guide', 'url' => 'admission_guide.php'],
            ['title' => 'How to Apply for PUPCET', 'url' => 'admission_guide.php#how-to-apply'],
            ['title' => 'PUPCET Eligibility', 'url' => 'admission_guide.php#eligibility'],
            ['title' => 'Application Documents', 'url' => 'admission_guide.php#requirements'],
            ['title' => 'Prepare your requirements', 'url' => 'admission_guide.php#how-to-apply'],
            ['title' => 'Go to PUP iApply', 'url' => 'admission_guide.php#how-to-apply'],
            ['title' => 'Create your account', 'url' => 'admission_guide.php#how-to-apply'],
            ['title' => 'Fill out the application form', 'url' => 'admission_guide.php#how-to-apply'],
        ]
    ],
    
    // Student Services
    [
        'page_url' => 'services.php',
        'page_title' => 'Student Services',
        'sections' => [
            ['title' => 'Student Services', 'url' => 'services.php'],
            ['title' => 'Enter & Stay in PUP', 'url' => 'services.php'],
            ['title' => 'Well-being & Academic Support', 'url' => 'services.php'],
            ['title' => 'Aid, Life & Leadership', 'url' => 'services.php'],
            ['title' => 'Where to go for help', 'url' => 'services.php#offices'],
            ['title' => 'Admission & Registrar', 'url' => 'services.php#admission-registrar'],
            ['title' => 'Scholarships & Financial Assistance', 'url' => 'services.php#scholarships-grants'],
            ['title' => 'Guidance & Counseling', 'url' => 'services.php#guidance-counseling'],
            ['title' => 'Library & Learning Resources', 'url' => 'services.php#library'],
            ['title' => 'Student Affairs & Services', 'url' => 'services.php#student-affairs'],
            ['title' => 'IT Services', 'url' => 'services.php#it-services'],
            ['title' => 'Frequently asked questions', 'url' => 'services.php#faqs'],
        ]
    ],
    
    // Campus Offices
    [
        'page_url' => 'view-campus-offices.php',
        'page_title' => 'Campus Offices',
        'sections' => [
            ['title' => 'Campus Offices', 'url' => 'view-campus-offices.php'],
        ]
    ],

    // Citizen Charter
    [
        'page_url' => 'citizen_charter.php',
        'page_title' => 'Citizen Charter',
        'sections' => [
            ['title' => 'Citizen Charter', 'url' => 'citizen_charter.php'],
        ]
    ],

    // Student Handbook
    [
        'page_url' => 'student_handbook.php',
        'page_title' => 'Student Handbook',
        'sections' => [
            ['title' => 'Student Handbook', 'url' => 'student_handbook.php'],
        ]
    ],
    
    // Campus Life
    [
        'page_url' => 'campuslife.php',
        'page_title' => 'Campus Life',
        'sections' => [
            ['title' => 'Campus Life', 'url' => 'campuslife.php'],
            ['title' => 'THE UNIVERSITY TRADITIONS', 'url' => 'campuslife.php#traditions'],
            ['title' => 'Freshmen Orientation', 'url' => 'campuslife.php#traditions'],
            ['title' => 'The Iskolar ng Bayan Welcome', 'url' => 'campuslife.php#traditions'],
        ]
    ],
    
    // Announcements
    [
        'page_url' => 'announcement.php',
        'page_title' => 'Announcements & Events',
        'sections' => [
            ['title' => 'Announcements & Events', 'url' => 'announcement.php'],
            ['title' => 'All Announcements & Events', 'url' => 'announcement.php#announcements'],
        ]
    ],
    
    // Events
    [
        'page_url' => 'event.php',
        'page_title' => 'Campus Events',
        'sections' => [
            ['title' => 'Campus Events', 'url' => 'event.php'],
        ]
    ],
    
    // Forms
    [
        'page_url' => 'forms.php',
        'page_title' => 'Downloadable Forms',
        'sections' => [
            ['title' => 'Downloadable Forms', 'url' => 'forms.php'],
        ]
    ],
    
    // FAQ
    [
        'page_url' => 'faq.php',
        'page_title' => 'FAQ',
        'sections' => [
            ['title' => 'Frequently Asked Questions', 'url' => 'faq.php'],
        ]
    ],
    
    // Contact
    [
        'page_url' => 'contact.php',
        'page_title' => 'Contact Us',
        'sections' => [
            ['title' => 'Contact Us', 'url' => 'contact.php'],
        ]
    ],
];

// Add dynamic content from database (announcements, events, news)
if (!empty($query)) {
    $searchTerm = '%' . $query . '%';
    
    // Search Announcements from events table (single source of truth)
    $announcementSql = "SELECT e.id, e.title, e.category, e.start_date
                        FROM events e
                        WHERE e.show_in_announcement = 1 AND e.title LIKE ?
                        ORDER BY e.start_date DESC
                        LIMIT 20";
    
    $stmt = $conn->prepare($announcementSql);
    if ($stmt) {
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $searchIndex[] = [
                'page_url' => 'announcement.php',
                'page_title' => 'Announcements & Events',
                'sections' => [
                    ['title' => $row['title'], 'url' => 'announcement.php']
                ]
            ];
        }
        $stmt->close();
    }
    
    // Search Events (title only)
    $eventSql = "SELECT id, title, category, start_date
                 FROM events
                 WHERE title LIKE ?
                 ORDER BY start_date DESC
                 LIMIT 20";
    
    $stmt = $conn->prepare($eventSql);
    if ($stmt) {
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $searchIndex[] = [
                'page_url' => 'event.php',
                'page_title' => 'Campus Events',
                'sections' => [
                    ['title' => $row['title'], 'url' => 'event.php']
                ]
            ];
        }
        $stmt->close();
    }
    
    // Search News (title only)
    $newsSql = "SELECT id, title, publish_date
                FROM news
                WHERE is_published = 1 AND title LIKE ?
                ORDER BY COALESCE(publish_date, created_at) DESC
                LIMIT 20";
    
    $stmt = $conn->prepare($newsSql);
    if ($stmt) {
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $searchIndex[] = [
                'page_url' => 'news_detail.php?id=' . (int)$row['id'],
                'page_title' => 'News',
                'sections' => [
                    ['title' => $row['title'], 'url' => 'news_detail.php?id=' . (int)$row['id']]
                ]
            ];
        }
        $stmt->close();
    }
    
    // Search Forms (title only)
    $metadataJson = get_setting_safe($conn, 'forms_metadata', '{}');
    $formsMetadata = json_decode($metadataJson, true) ?: [];
    $formsDir = __DIR__ . '/../files/forms';
    
    if (is_dir($formsDir)) {
        $files = scandir($formsDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $metadata = $formsMetadata[$file] ?? [
                'title' => pathinfo($file, PATHINFO_FILENAME),
                'category' => 'Other Forms'
            ];
            
            $titleLower = mb_strtolower($metadata['title']);
            $queryLower = mb_strtolower($query);
            
            if (mb_strpos($titleLower, $queryLower) !== false) {
                $searchIndex[] = [
                    'page_url' => 'forms.php',
                    'page_title' => 'Downloadable Forms',
                    'sections' => [
                        ['title' => $metadata['title'], 'url' => 'forms.php']
                    ]
                ];
            }
        }
    }
}

// ============================================
// ADD STATIC SECTIONS TO SEARCH INDEX
// ============================================
// Add static section titles to the search index so they can be searched
// These are card-level sections that act as distinct content areas
foreach ($STATIC_SECTIONS as $section) {
    // Extract page title from URL (e.g., 'contact.php' -> 'Contact Us')
    $urlParts = explode('#', $section['url']);
    $pageFile = basename($urlParts[0], '.php');
    $pageTitle = ucwords(str_replace('_', ' ', $pageFile));
    
    // Map common page files to their proper titles
    $pageTitleMap = [
        'contact' => 'Contact Us',
        'services' => 'Student Services',
        'about' => 'About PUP Biñan',
        'programs' => 'Academic Programs',
        'admission_guide' => 'Admission Guide',
        'campuslife' => 'Campus Life',
        'announcement' => 'Announcements & Events',
        'event' => 'Campus Events',
        'forms' => 'Downloadable Forms',
        'faq' => 'FAQ',
    ];
    
    $pageTitle = $pageTitleMap[$pageFile] ?? $pageTitle;
    
    // Check if page already exists in searchIndex
    $pageFound = false;
    foreach ($searchIndex as &$page) {
        if ($page['page_url'] === $urlParts[0]) {
            // Add section to existing page
            $page['sections'][] = [
                'title' => $section['title'],
                'url' => $section['url'],
                'snippet' => $section['snippet'],
                'type' => $section['type']
            ];
            $pageFound = true;
            break;
        }
    }
    
    // If page doesn't exist, create new entry
    if (!$pageFound) {
        $searchIndex[] = [
            'page_url' => $urlParts[0],
            'page_title' => $pageTitle,
            'sections' => [
                [
                    'title' => $section['title'],
                    'url' => $section['url'],
                    'snippet' => $section['snippet'],
                    'type' => $section['type']
                ]
            ]
        ];
    }
}

// Perform search
$results = [];
if (!empty($query)) {
    $queryLower = mb_strtolower($query);
    
    foreach ($searchIndex as $page) {
        $pageTitleLower = mb_strtolower($page['page_title']);
        
        // Check if query matches page title
        $pageMatches = mb_strpos($pageTitleLower, $queryLower) !== false;
        
        // Check sections
        foreach ($page['sections'] as $section) {
            $sectionTitleLower = mb_strtolower($section['title']);
            $sectionMatches = mb_strpos($sectionTitleLower, $queryLower) !== false;
            
            // Also check snippet if available (for static sections)
            $snippetMatches = false;
            if (isset($section['snippet']) && !empty($section['snippet'])) {
                $snippetLower = mb_strtolower($section['snippet']);
                $snippetMatches = mb_strpos($snippetLower, $queryLower) !== false;
            }
            
            if ($pageMatches || $sectionMatches || $snippetMatches) {
                $results[] = [
                    'page_title' => $page['page_title'],
                    'section_title' => $section['title'],
                    'url' => $section['url'],
                    'snippet' => $section['snippet'] ?? null,
                    'type' => $section['type'] ?? null
                ];
            }
        }
    }
    
    // Remove duplicates and limit results
    $uniqueResults = [];
    $seen = [];
    foreach ($results as $result) {
        $key = $result['url'] . '|' . $result['section_title'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $uniqueResults[] = $result;
        }
    }
    $results = array_slice($uniqueResults, 0, 50);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $query ? "Search: {$queryEscaped}" : 'Search'; ?> - PUP Biñan Campus</title>
    <meta name="description" content="Site search results" />
    <link rel="icon" type="image/png" href="../asset/PUPicon.png">
    <link rel="stylesheet" href="../asset/css/site.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .search-page {
            padding: 24px 0;
        }

        .search-input {
            display: flex;
            gap: .5rem;
            margin: 12px 0 24px;
        }

        .search-input input {
            flex: 1;
            padding: .6rem .7rem;
            border-radius: .65rem;
            border: 1px solid var(--border);
        }

        .search-input button {
            padding: .6rem 1rem;
            background: var(--maroon);
            color: #fff;
            border: none;
            border-radius: .65rem;
            cursor: pointer;
        }

        .search-input button:hover {
            background: var(--maroon-900);
        }

        .result {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: .9rem;
            margin-bottom: .85rem;
        }

        .result h3 {
            margin: .2rem 0 .4rem;
            font-size: 1.05rem;
        }

        .result h3 a {
            color: var(--maroon);
            text-decoration: none;
        }

        .result h3 a:hover {
            color: var(--maroon-900);
            text-decoration: underline;
        }

        .result-header {
            display: flex;
            align-items: start;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
            flex-wrap: wrap;
        }

        .result-page-title {
            font-weight: 600;
            color: var(--maroon);
            margin-bottom: 0.25rem;
            flex: 1;
            min-width: 200px;
        }

        .result-section-title {
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .result-section-title a {
            color: var(--maroon);
            text-decoration: none;
        }

        .result-section-title a:hover {
            color: var(--maroon-900);
            text-decoration: underline;
        }

        .snippet {
            color: #4b5563;
            margin-top: 0.5rem;
            margin-bottom: 0;
            line-height: 1.6;
        }

        .result mark {
            background: #fff3bf;
            color: inherit;
            border-radius: .15rem;
            padding: 0.1rem 0.2rem;
            font-weight: 500;
        }

        .result-type-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            margin-right: 0.5rem;
        }

        .muted-note {
            color: var(--muted);
            margin-top: .5rem;
            font-style: italic;
        }

        .no-results {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }

        .no-results h2 {
            color: var(--maroon);
            margin-bottom: 0.5rem;
        }
        
        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .search-page {
                padding: 20px 0;
            }
            
            .search-input {
                flex-direction: column;
            }
            
            .search-input input {
                width: 100%;
            }
            
            .result {
                padding: 0.75rem;
            }
        }
        
        @media (max-width: 640px) {
            .search-page {
                padding: 16px 0;
            }
            
            .result h3 {
                font-size: 1rem;
            }
            
            .result {
                padding: 0.65rem;
            }
        }
        
        @media (max-width: 576px) {
            .result h3 {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
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
                </div>
            </div>
            <nav aria-label="Primary" class="menu" id="menu">
                <a href="../homepage.php">Home</a>
                <a href="about.php">About</a>
                <a href="programs.php">Academic Programs</a>
                <a href="admission_guide.php">Admissions</a>
                <a href="services.php">Student Services</a>
                <a href="campuslife.php">Campus Life</a>
                <a href="contact.php">Contact</a>
            </nav>
            <form class="search-form" action="search.php" method="get" role="search" aria-label="Site search">
                <input type="text" name="q" placeholder="Search..." aria-label="Search" autocomplete="off" value="<?php echo $queryEscaped; ?>">
                <button type="submit" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
    </header>
    
    <main class="container search-page" id="content">
        <h1>Search Results<?php echo $query ? ": {$queryEscaped}" : ''; ?></h1>
        
        <div class="search-input">
            <form class="search-form" action="search.php" method="get" role="search" aria-label="Site search">
                <input type="text" name="q" id="q" placeholder="Search..." aria-label="Search" autocomplete="off" value="<?php echo $queryEscaped; ?>">
                <button type="submit" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <?php if (empty($query)): ?>
            <div class="no-results">
                <h2>Enter a search query</h2>
                <p>Type keywords in the search box above to find pages and sections across the PUP Biñan Campus website.</p>
            </div>
        <?php elseif (empty($results)): ?>
            <div class="no-results">
                <h2>No results found</h2>
                <p>No results found for "<strong><?php echo $queryEscaped; ?></strong>". Try different keywords or check your spelling.</p>
            </div>
        <?php else: ?>
            <p class="muted-note">Found <?php echo count($results); ?> result<?php echo count($results) !== 1 ? 's' : ''; ?> for "<strong><?php echo $queryEscaped; ?></strong>"</p>

            <?php foreach ($results as $result): 
                $url = normalizeUrl($result['url'], 'pages');
                $hasSnippet = !empty($result['snippet']);
                $resultType = $result['type'] ?? null;
            ?>
                <article class="result">
                    <div class="result-header">
                        <?php if ($resultType): ?>
                            <span class="result-type-badge" style="background: #666666;"><?php echo htmlspecialchars($resultType); ?></span>
                        <?php endif; ?>
                        <div class="result-page-title"><?php echo highlightKeywords($result['page_title'], $query); ?></div>
                    </div>
                    <h3 class="result-section-title">
                        <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo highlightKeywords($result['section_title'], $query); ?>
                        </a>
                    </h3>
                    <?php if ($hasSnippet): ?>
                        <p class="snippet"><?php echo highlightKeywords($result['snippet'], $query); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <script defer src="../asset/js/homepage.js"></script>
    <script defer src="../asset/js/mobile-nav.js"></script>
</body>
</html>
