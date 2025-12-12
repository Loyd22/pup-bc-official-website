<?php
require __DIR__ . '/DATAANALYTICS/db.php';
$result = $conn->query('SHOW CREATE TABLE news');
if (!$result) {
    echo 'ERR: ' . $conn->error . "\n";
    exit;
}
$row = $result->fetch_assoc();
echo $row['Create Table'] . "\n";
?>
