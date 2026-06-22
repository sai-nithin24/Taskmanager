<?php
// Full bootstrap + connectivity test
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/models/Database.php';
require_once __DIR__ . '/../src/models/TaskModel.php';
require_once __DIR__ . '/../src/utils/Response.php';
require_once __DIR__ . '/../src/utils/Validator.php';

echo "✓ All files loaded\n";

try {
    $pdo = Database::getInstance();
    echo "✓ DB connected\n";

    $model = new TaskModel();

    $stats = $model->getStats();
    echo "✓ getStats() → " . json_encode($stats) . "\n";

    $all = $model->getAll('all');
    echo "✓ getAll('all') → " . count($all) . " tasks\n";

    $pending = $model->getAll('pending');
    echo "✓ getAll('pending') → " . count($pending) . " tasks\n";

    $completed = $model->getAll('completed');
    echo "✓ getAll('completed') → " . count($completed) . " tasks\n";

} catch (PDOException $e) {
    echo "✗ DB ERROR: " . $e->getMessage() . "\n";
} catch (Throwable $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}
