<?php
// ============================================================
//  src/models/Database.php
//  PDO singleton — supports SSL for Aiven MySQL (PHP 8.2)
// ============================================================

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // SSL required for Aiven and most managed cloud DBs
            // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = 1014
            // Using numeric constant for PHP 8.2 compatibility
            if (DB_SSL) {
                $options[1014] = false; // MYSQL_ATTR_SSL_VERIFY_SERVER_CERT
            }

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$instance;
    }
}
