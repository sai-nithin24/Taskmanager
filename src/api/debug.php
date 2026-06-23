<?php
// TEMPORARY - tests live DB connection from Render's server
// DELETE after confirming DB works
header('Content-Type: application/json');

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'taskboard';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$ssl  = getenv('DB_SSL')  !== 'false';

$result = ['env_vars_loaded' => true, 'host' => $host, 'port' => $port, 'db' => $name];

// Try connection with SSL disabled first (most permissive)
$attempts = [
    'no_ssl'       => [],
    'ssl_no_verify'=> [1014 => false],  // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT
];

foreach ($attempts as $label => $extra) {
    try {
        $opts = array_merge([
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ], $extra);

        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            $user, $pass, $opts
        );
        $ver   = $pdo->query('SELECT VERSION()')->fetchColumn();
        $count = $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
        $result['connection'] = 'SUCCESS';
        $result['method']     = $label;
        $result['mysql_version'] = $ver;
        $result['task_count'] = $count;
        break;
    } catch (PDOException $e) {
        $result['attempt_' . $label] = $e->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
