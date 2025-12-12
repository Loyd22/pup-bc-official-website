<?php
declare(strict_types=1);

/**
 * Format a date string for display
 */
function formatDate(?string $date): string
{
  if (!$date) {
    return 'Draft';
  }

  $timestamp = strtotime($date);
  return $timestamp ? date('M j, Y', $timestamp) : $date;
}

/**
 * Format a date range for display
 */
function formatDateRange(?string $startDate, ?string $endDate): string
{
  if (!$startDate) {
    return 'Date TBA';
  }

  $start = strtotime($startDate);
  if (!$start) {
    return $startDate;
  }

  if (!$endDate || $endDate === $startDate) {
    return date('M j, Y', $start);
  }

  $end = strtotime($endDate);
  if (!$end) {
    return date('M j, Y', $start);
  }

  // Same month
  if (date('Y-m', $start) === date('Y-m', $end)) {
    return date('M j', $start) . '-' . date('j, Y', $end);
  }

  // Different months
  return date('M j, Y', $start) . ' - ' . date('M j, Y', $end);
}

/**
 * Create an excerpt from text
 */
function excerpt(string $text, int $limit = 110): string
{
  $clean = trim(strip_tags($text));
  if ($clean === '') {
    return '';
  }

  // Fix special characters like &ntilde; to ñ
  $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

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

/**
 * Fetch announcements from events table (single source of truth)
 * Shows events marked for announcements
 */
function fetchAnnouncementsFromEvents(mysqli $conn, int $limit = 100, bool $forHomepage = false): array
{
  // For homepage: require BOTH show_in_announcement = 1 AND show_on_homepage = 1
  // For announcement.php page: only require show_in_announcement = 1
  $whereClause = $forHomepage 
    ? "WHERE e.show_in_announcement = 1 AND e.show_on_homepage = 1"
    : "WHERE e.show_in_announcement = 1";
  
  $orderClause = "ORDER BY e.start_date DESC";

  $sql = "SELECT e.id, e.title, e.description, e.start_date, e.end_date, e.location, e.category, e.author, e.created_by,
                 COALESCE(a.full_name, e.author, e.category) as display_source,
                 a.full_name as author_name
          FROM events e
          LEFT JOIN admins a ON e.created_by = a.id
          $whereClause
          $orderClause
          LIMIT ?";

  $stmt = $conn->prepare($sql);
  $items = [];
  if ($stmt) {
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      // Basic validation - only filter out truly empty titles
      $title = trim($row['title'] ?? '');
      if (empty($title)) {
        continue;
      }
      $items[] = $row;
    }
    $stmt->close();
  }

  return $items;
}

/**
 * Fetch distinct author values from events table for filter pills
 */
function fetchDistinctAuthors(mysqli $conn): array
{
  $sql = "SELECT DISTINCT COALESCE(a.full_name, e.author) as author_display
          FROM events e
          LEFT JOIN admins a ON e.created_by = a.id
          WHERE e.show_in_announcement = 1 
            AND (a.full_name IS NOT NULL OR (e.author IS NOT NULL AND e.author != ''))
          ORDER BY author_display ASC";
  
  $result = $conn->query($sql);
  $authors = [];
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      if (!empty($row['author_display'])) {
        $authors[] = $row['author_display'];
      }
    }
    $result->free();
  }
  
  return $authors;
}

