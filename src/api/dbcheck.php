<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT  => 10,
    ]);

    $ver   = $pdo->query('SELECT VERSION()')->fetchColumn();
    $count = $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();

    echo json_encode([
        'ok'      => true,
        'version' => $ver,
        'tasks'   => $count,
        'host'    => DB_HOST,
        'port'    => DB_PORT,
        'db'      => DB_NAME,
        'user'    => DB_USER,
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
        'code'  => $e->getCode(),
        'host'  => DB_HOST,
        'port'  => DB_PORT,
        'db'    => DB_NAME,
        'user'  => DB_USER,
    ], JSON_PRETTY_PRINT);
}
