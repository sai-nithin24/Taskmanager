<?php
// ============================================================
//  config/database.php
//  All values come from environment variables.
//  Set them in Render dashboard → Environment tab.
//  For local dev, create a .env file or set them in your shell.
// ============================================================
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'taskboard');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_SSL',     getenv('DB_SSL')     !== 'false');
