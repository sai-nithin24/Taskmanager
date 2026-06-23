<?php
// Reads from environment variables on Render/production.
// Falls back to local XAMPP / Clever Cloud values when running locally.
define('DB_HOST',    getenv('DB_HOST')    ?: 'bwpy7we4haambtflczpo-mysql.services.clever-cloud.com');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'bwpy7we4haambtflczpo');
define('DB_USER',    getenv('DB_USER')    ?: 'uqjm9xz4c4dcoh83');
define('DB_PASS',    getenv('DB_PASS')    ?: 'jiTQX2L5Kz9ddJ6eHxnv');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
