<?php
// Redirect to pages/search.php with query parameters
$query = isset($_GET['q']) ? urlencode($_GET['q']) : '';
if ($query) {
    header('Location: pages/search.php?q=' . $query);
} else {
    header('Location: pages/search.php');
}
exit;
?>









