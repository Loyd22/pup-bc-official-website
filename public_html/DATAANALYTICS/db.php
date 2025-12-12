<?php
$host = "localhost";
$user = "root";
$pass = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$database = "pupbcadmin5";

try {
    $conn = new mysqli($host, $user, $pass, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>