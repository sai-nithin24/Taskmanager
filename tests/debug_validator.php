<?php
require_once __DIR__ . '/../src/utils/Validator.php';

// Simulate exactly what tasks.php does on PATCH
$raw  = '{"id":26,"status":"completed"}';
$body = json_decode($raw, true) ?? [];

$id     = (int)($body['id']     ?? 0);
$status = trim($body['status'] ?? '');

echo "id value: " . var_export($id, true) . "\n";
echo "id type: " . gettype($id) . "\n";
echo "status value: " . var_export($status, true) . "\n";

$v = (new Validator())
    ->required('id',     $id)
    ->inList('status', $status, ['pending','completed']);

echo "passes: " . ($v->passes() ? 'true' : 'false') . "\n";
echo "errors: " . json_encode($v->errors()) . "\n";

// Debug the required check step by step
$strVal = trim((string)$id);
echo "\nDebug required('id', $id):\n";
echo "  strVal = " . var_export($strVal, true) . "\n";
echo "  strVal === '' → " . ($strVal === '' ? 'true' : 'false') . "\n";
echo "  strVal === '0' → " . ($strVal === '0' ? 'true' : 'false') . "\n";
echo "  isset(\$id) → " . (isset($id) ? 'true' : 'false') . "\n";
