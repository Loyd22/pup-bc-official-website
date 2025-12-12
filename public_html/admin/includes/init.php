<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/DATAANALYTICS/db.php';
require_once __DIR__ . '/functions.php';
