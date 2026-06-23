<?php
// TEMPORARY debug endpoint - shows what env vars Render sees
// DELETE THIS FILE after confirming DB works
header('Content-Type: application/json');

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$ssl  = getenv('DB_SSL');
// Never show password - just whether it's set
$passSet = getenv('DB_PASS') !== false && getenv('DB_PASS') !== '' ? 'SET' : 'NOT SET';

echo json_encode([
    'env' => [
        'DB_HOST' => $host ?: 'NOT SET - using fallback: localhost',
        'DB_PORT' => $port ?: 'NOT SET - using fallback: 3306',
        'DB_NAME' => $name ?: 'NOT SET - using fallback: taskboard',
        'DB_USER' => $user ?: 'NOT SET - using fallback: root',
        'DB_PASS' => $passSet,
        'DB_SSL'  => $ssl  ?: 'NOT SET',
    ],
    'php_version' => PHP_VERSION,
], JSON_PRETTY_PRINT);
