<?php
header('Content-Type: application/json');
try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../models/Database.php';
    require_once __DIR__ . '/../models/TaskModel.php';
    $model = new TaskModel();
    $stats = $model->getStats();
    $all   = $model->getAll('all');
    echo json_encode(['ok'=>true,'stats'=>$stats,'task_count'=>count($all),'sample'=>$all[0]??null], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'type'=>get_class($e),'error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()], JSON_PRETTY_PRINT);
}
