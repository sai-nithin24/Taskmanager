<?php
// Simulate what tasks.php does exactly for PATCH
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/models/Database.php';
require_once __DIR__ . '/../src/models/TaskModel.php';
require_once __DIR__ . '/../src/utils/Validator.php';

// Mimic reading JSON body exactly like tasks.php
$raw  = '{"id":26,"status":"completed"}';
$body = $raw ? (json_decode($raw, true) ?? []) : [];

echo "body: " . json_encode($body) . "\n";

$id     = (int)($body['id']     ?? 0);
$status = trim($body['status'] ?? '');

echo "id=$id, status=$status\n";

$v = (new Validator())
    ->required('id',     $id)
    ->inList('status', $status, ['pending','completed']);

echo "passes: " . ($v->passes() ? 'yes' : 'no') . "\n";
echo "errors: " . json_encode($v->errors()) . "\n";
echo "id < 1: " . ($id < 1 ? 'yes' : 'no') . "\n";

if ($v->passes() && $id >= 1) {
    echo "→ Would proceed to update\n";
} else {
    echo "→ Would return 422 Invalid data\n";
}
