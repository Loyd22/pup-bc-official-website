<?php
declare(strict_types=1);

function ensure_page_visits_table(mysqli $conn): bool
{
    static $ensured = false;

    if ($ensured) {
        return true;
    }

    if ($result = $conn->query("SHOW TABLES LIKE 'page_visits'")) {
        $exists = $result->num_rows > 0;
        $result->free();

        if ($exists) {
            $ensured = true;
            return true;
        }
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS page_visits (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            page_name VARCHAR(150) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            visit_date DATE NOT NULL,
            visit_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_page_visits_name_date (page_name, visit_date),
            INDEX idx_page_visits_date (visit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($conn->query($sql) === true) {
        $ensured = true;
        return true;
    }

    return false;
}

function record_page_visit(mysqli $conn, string $pageName, string $ip, string $date): void
{
    if (!ensure_page_visits_table($conn)) {
        return;
    }

    $stmt = $conn->prepare(
        "SELECT 1 FROM page_visits WHERE page_name = ? AND ip_address = ? AND visit_date = ? LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param('sss', $pageName, $ip, $date);
        $stmt->execute();
        $stmt->store_result();
        $alreadyVisited = $stmt->num_rows > 0;
        $stmt->close();

        if ($alreadyVisited) {
            return;
        }
    }

    $insert = $conn->prepare(
        "INSERT INTO page_visits (page_name, ip_address, visit_date) VALUES (?, ?, ?)"
    );

    if ($insert) {
        $insert->bind_param('sss', $pageName, $ip, $date);
        $insert->execute();
        $insert->close();
    }
}

function get_page_visit_count(mysqli $conn, string $pageName): int
{
    if (!ensure_page_visits_table($conn)) {
        return 0;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM page_visits WHERE page_name = ?"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('s', $pageName);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count;
}
