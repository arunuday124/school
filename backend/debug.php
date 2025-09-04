<?php
header('Content-Type: application/json; charset=utf-8');

$result = [
    'time' => date('c'),
    'config_loaded' => false,
    'db' => [
        'host' => null,
        'user' => null,
        'name' => null,
        'connected' => false,
        'error' => null,
        'tables' => [],
    ],
    'uploads' => [
        'path' => null,
        'writable' => false,
        'write_error' => null,
    ],
];

// Load config if exists
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $result['config_loaded'] = true;
}

// Populate known vars
$result['db']['host'] = isset($DB_HOST) ? $DB_HOST : null;
$result['db']['user'] = isset($DB_USER) ? $DB_USER : null;
$result['db']['name'] = isset($DB_NAME) ? $DB_NAME : null;

// Try DB connection
if (!empty($result['db']['host']) && !empty($result['db']['user']) && !empty($result['db']['name'])) {
    $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS ?? '', $DB_NAME);
    if ($mysqli->connect_errno) {
        $result['db']['connected'] = false;
        $result['db']['error'] = $mysqli->connect_error;
    } else {
        $result['db']['connected'] = true;
        $mysqli->set_charset('utf8mb4');
        // try listing tables (safe)
        $res = $mysqli->query("SHOW TABLES");
        if ($res) {
            while ($row = $res->fetch_row()) {
                $result['db']['tables'][] = $row[0];
            }
            $res->free();
        }
    }
} else {
    $result['db']['error'] = 'Missing DB credentials (check backend/config.php)';
}

// Check uploads folder
$uploads = __DIR__ . '/uploads';
$result['uploads']['path'] = $uploads;
if (!is_dir($uploads)) {
    // try to create it
    @mkdir($uploads, 0755, true);
}

if (is_dir($uploads) && is_writable($uploads)) {
    $result['uploads']['writable'] = true;
    $tryFile = $uploads . '/debug_write_' . time() . '.txt';
    $ok = @file_put_contents($tryFile, "debug write " . date('c'));
    if ($ok === false) {
        $result['uploads']['writable'] = false;
        $result['uploads']['write_error'] = 'file_put_contents failed';
    } else {
        // remove the test file
        @unlink($tryFile);
    }
} else {
    $result['uploads']['writable'] = false;
    $result['uploads']['write_error'] = 'directory missing or not writable';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
