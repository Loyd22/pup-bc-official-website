<?php
include __DIR__ . '/../DATAANALYTICS/db.php';

header('Content-Type: application/json');

// Get month and year from request
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;

if (!$year || !$month || $month < 1 || $month > 12) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid month or year']);
    exit;
}

// Include functions from homepage.php
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

// Calculate valid range: 1 month in past to 10 months in future (12 months total)
$todayYear = (int)date('Y');
$todayMonth = (int)date('m');
$earliestDate = mktime(0, 0, 0, $todayMonth - 1, 1, $todayYear);
$latestDate = mktime(0, 0, 0, $todayMonth + 10, 1, $todayYear);
$requestedDate = mktime(0, 0, 0, $month, 1, $year);

// Clamp requested date to valid range
if ($requestedDate < $earliestDate) {
  $displayYear = (int)date('Y', $earliestDate);
  $displayMonth = (int)date('m', $earliestDate);
} elseif ($requestedDate > $latestDate) {
  $displayYear = (int)date('Y', $latestDate);
  $displayMonth = (int)date('m', $latestDate);
} else {
  $displayYear = $year;
  $displayMonth = $month;
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

// Helper functions for formatting
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

function excerpt(string $text, int $limit = 110): string
{
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

// Build monthly events list HTML
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

// Build calendar HTML
$calendarHtml = buildMonthCalendar($displayYear, $displayMonth, $monthEvents);

// Build events list HTML
$eventsListHtml = buildMonthlyEventsList($monthEvents);

// Return JSON response
echo json_encode([
    'success' => true,
    'calendarHtml' => $calendarHtml,
    'eventsListHtml' => $eventsListHtml,
    'monthName' => date('F Y', mktime(0, 0, 0, $displayMonth, 1, $displayYear)),
    'year' => $displayYear,
    'month' => $displayMonth,
    'canGoPrev' => $canGoPrev,
    'canGoNext' => $canGoNext,
    'prevYear' => $prevYear,
    'prevMonth' => $prevMonth,
    'nextYear' => $nextYear,
    'nextMonth' => $nextMonth
]);

