<?php
// Exact simulation of the tasks.php bootstrap chain
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../models/Database.php';
    require_once __DIR__ . '/../models/TaskModel.php';

    $model = new TaskModel();
    $stats = $model->getStats();
    $tasks = $model->getAll('all');

    echo json_encode([
        'ok'     => true,
        'stats'  => $stats,
        'count'  => count($tasks),
        'consts' => [
            'DB_HOST'    => DB_HOST,
            'DB_PORT'    => DB_PORT,
            'DB_NAME'    => DB_NAME,
            'DB_USER'    => DB_USER,
            'DB_CHARSET' => DB_CHARSET,
        ],
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'ok'     => false,
        'type'   => 'PDOException',
        'error'  => $e->getMessage(),
        'code'   => $e->getCode(),
        'consts' => [
            'DB_HOST'    => defined('DB_HOST')    ? DB_HOST    : 'UNDEFINED',
            'DB_PORT'    => defined('DB_PORT')    ? DB_PORT    : 'UNDEFINED',
            'DB_NAME'    => defined('DB_NAME')    ? DB_NAME    : 'UNDEFINED',
            'DB_USER'    => defined('DB_USER')    ? DB_USER    : 'UNDEFINED',
            'DB_CHARSET' => defined('DB_CHARSET') ? DB_CHARSET : 'UNDEFINED',
        ],
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'type'  => get_class($e),
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
