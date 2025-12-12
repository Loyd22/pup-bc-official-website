<?php
// Lightweight RAG-style FAQ endpoint (no external services)
// - Gathers content from FAQ and key pages
// - Performs simple TF-IDF-like scoring to retrieve best passages
// - Returns an answer, related questions, and sources

header('Content-Type: application/json; charset=UTF-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed']);
  exit;
}

// Read input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$query = trim((string)($data['q'] ?? ''));
if ($query === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Missing q']);
  exit;
}

// Utility: normalize text
function normalize_text(string $text): string {
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $text = strtolower($text);
  // Remove script/style and tags
  $text = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $text);
  $text = strip_tags($text);
  // Collapse whitespace
  $text = preg_replace('/\s+/u', ' ', $text);
  return trim($text);
}

function tokenize(string $text): array {
  $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
  $text = preg_replace('/\s+/u', ' ', $text);
  $parts = preg_split('/\s+/u', trim($text));
  return array_values(array_filter($parts, fn($t) => $t !== ''));
}

// Very small list of stopwords for better matching
$STOP = [
  'the','a','an','and','or','to','of','in','on','for','with','at','by','is','are','was','were','be','as','that','this','it','from','your','you','i','we','they','our','their'
];

function filter_tokens(array $tokens, array $stop): array {
  $stopSet = array_fill_keys($stop, true);
  $out = [];
  foreach ($tokens as $t) {
    if (!isset($stopSet[$t]) && mb_strlen($t, 'UTF-8') >= 2) {
      $out[] = $t;
    }
  }
  return $out;
}

// Load and index content
$root = dirname(__DIR__);
$pagesDir = $root . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;

$pageFiles = [
  'faq.php' => 'FAQs',
  'about.php' => 'About',
  'admission_guide.php' => 'Admissions',
  'programs.php' => 'Academic Programs',
  'services.php' => 'Student Services',
  'campuslife.php' => 'Campus Life',
  'contact.php' => 'Contact',
];

$documents = [];

// Helper: extract FAQ items as {q, a}
function extract_faqs_from_html(string $html): array {
  $out = [];
  if (class_exists('DOMDocument')) {
    $dom = new DOMDocument();
    // Suppress warnings for malformed HTML
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    $items = $xpath->query("//*[@data-faq]//*[contains(concat(' ', normalize-space(@class), ' '), ' faq-item ')]");
    foreach ($items as $node) {
      $qNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' q ')]", $node)->item(0);
      $aNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' a ')]", $node)->item(0);
      $q = $qNode ? trim($qNode->textContent) : '';
      $a = $aNode ? trim($dom->saveHTML($aNode)) : '';
      if ($q !== '' && $a !== '') {
        $out[] = ['q' => $q, 'a' => $a];
      }
    }
  }
  return $out;
}

foreach ($pageFiles as $file => $title) {
  $path = $pagesDir . $file;
  if (!is_file($path)) continue;
  $html = @file_get_contents($path);
  if ($html === false) continue;

  if ($file === 'faq.php') {
    $faqs = extract_faqs_from_html($html);
    foreach ($faqs as $faq) {
      $source = 'pages/' . $file;
      $qText = normalize_text($faq['q']);
      $aText = normalize_text($faq['a']);
      $full = $qText . ' ' . $aText;
      $tokens = filter_tokens(tokenize($full), $STOP);
      $documents[] = [
        'type' => 'faq',
        'title' => $faq['q'],
        'content' => $aText,
        'tokens' => $tokens,
        'source' => $source,
        'anchor' => null,
      ];
    }
  } else {
    // Extract sections by headings to create smaller passages
    $text = normalize_text($html);
    // Split by common headings; fallback to large chunks
    $sections = preg_split('/\b(h1|h2|h3|section|article)\b/i', $html);
    if (!$sections || count($sections) < 2) {
      $sections = [ $html ];
    }
    $idx = 0;
    foreach ($sections as $sec) {
      $secText = normalize_text($sec);
      if ($secText === '') continue;
      $tokens = filter_tokens(tokenize($secText), $STOP);
      if (count($tokens) < 5) continue;
      $documents[] = [
        'type' => 'section',
        'title' => $title . ' Section ' . (++$idx),
        'content' => $secText,
        'tokens' => $tokens,
        'source' => 'pages/' . $file,
        'anchor' => null,
      ];
      if ($idx >= 15) break; // cap per page
    }
  }
}

if (empty($documents)) {
  echo json_encode([
    'answer' => "Sorry, I couldn't access the knowledge base right now.",
    'related' => [],
    'sources' => []
  ]);
  exit;
}

// Build DF for IDF weights
$df = [];
foreach ($documents as $doc) {
  $seen = [];
  foreach ($doc['tokens'] as $t) { $seen[$t] = true; }
  foreach (array_keys($seen) as $t) { $df[$t] = ($df[$t] ?? 0) + 1; }
}
$N = count($documents);

// Score function (TF-IDF cosine-ish with length normalization)
function score_doc(array $qTokens, array $doc, array $df, int $N): float {
  if (empty($doc['tokens'])) return 0.0;
  $tf = [];
  foreach ($doc['tokens'] as $t) { $tf[$t] = ($tf[$t] ?? 0) + 1; }
  $docLen = max(1, count($doc['tokens']));
  $score = 0.0;
  foreach ($qTokens as $t) {
    if (!isset($tf[$t])) continue;
    $idf = log(1 + ($N / max(1, ($df[$t] ?? 1))));
    $score += ($tf[$t] / $docLen) * $idf;
  }
  return $score;
}

$qNorm = normalize_text($query);
$qTokens = filter_tokens(tokenize($qNorm), $STOP);

// If query too short, keep some original words
if (empty($qTokens)) {
  $qTokens = filter_tokens(tokenize(mb_substr($qNorm, 0, 80)), $STOP);
}

// Rank documents
$scored = [];
foreach ($documents as $i => $doc) {
  $s = score_doc($qTokens, $doc, $df, $N);
  // Slight boost for FAQs
  if ($doc['type'] === 'faq') $s *= 1.2;
  $scored[] = ['i' => $i, 'score' => $s];
}
usort($scored, fn($a,$b) => $b['score'] <=> $a['score']);

$top = array_slice($scored, 0, 5);

// Build answer: prefer FAQ if present; otherwise summarize first passage sentence
$answer = '';
$sources = [];
$related = [];

foreach ($top as $rank => $hit) {
  $doc = $documents[$hit['i']];
  if ($rank === 0 && $doc['type'] === 'faq') {
    $answer = '<b>' . htmlspecialchars($doc['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</b><br>' .
              htmlspecialchars($doc['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  $sources[] = [
    'title' => $doc['title'],
    'url' => $doc['source'] . ($doc['anchor'] ? ('#' . $doc['anchor']) : ''),
  ];
}

if ($answer === '' && !empty($top)) {
  $doc = $documents[$top[0]['i']];
  // Take first 400 chars as a summary
  $snippet = mb_substr($doc['content'], 0, 400);
  $answer = htmlspecialchars($snippet, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Related FAQs (top other FAQs)
foreach ($top as $hit) {
  $doc = $documents[$hit['i']];
  if ($doc['type'] === 'faq') {
    $related[] = $doc['title'];
  }
}
$related = array_values(array_unique(array_slice($related, 0, 5)));

echo json_encode([
  'answer' => $answer,
  'related' => $related,
  'sources' => $sources,
]);


