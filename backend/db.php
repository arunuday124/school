<?php

// Load credentials from an untracked config file if present.
// Create `backend/config.php` on the server (do NOT commit it).
// See backend/config.sample.php for the variable names.
if (file_exists(__DIR__ . '/config.php')) {
    // config.php should define: $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME
    require_once __DIR__ . '/config.php';
} else {
    // fallback local defaults (safe for local development only)
    $DB_HOST = '127.0.0.1';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'all_school';
}

header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect error: ' . $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');


function send_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
