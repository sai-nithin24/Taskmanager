<?php
// Full HTTP round-trip tests using PHP's built-in streams
// Run: php tests/http_test.php  (XAMPP must be running)

define('BASE', 'http://localhost/taskboard/src/api/tasks.php');

function api(string $method, array $params = [], array $body = []): array {
    $url = BASE . ($params ? '?' . http_build_query($params) : '');
    $ctx = stream_context_create(['http' => [
        'method'  => $method,
        'header'  => "Content-Type: application/json\r\nAccept: application/json",
        'content' => $body ? json_encode($body) : null,
        'ignore_errors' => true,
    ]]);
    $res  = file_get_contents($url, false, $ctx);
    $data = json_decode($res, true);
    return $data ?? ['success' => false, 'message' => 'JSON parse error: ' . $res];
}

function ok(string $label, bool $cond, $detail = ''): void {
    echo ($cond ? "\033[32m  ✓\033[0m" : "\033[31m  ✕\033[0m") . " $label" . ($detail ? " → $detail" : '') . "\n";
}

echo "\n\033[1m── HTTP Live Tests ──────────────────────────────────\033[0m\n";

// ── GET stats ─────────────────────────────────────────────────
$r = api('GET', ['stats' => 1]);
ok('GET stats success',            $r['success'] === true);
ok('GET stats has total',          isset($r['data']['total']));
ok('GET stats has pending',        isset($r['data']['pending']));
ok('GET stats has completed',      isset($r['data']['completed']));
ok('GET stats has pct',            isset($r['data']['pct']));
ok('GET stats pct is numeric',     is_numeric($r['data']['pct'] ?? null));

// ── GET all tasks ─────────────────────────────────────────────
$r = api('GET', ['filter' => 'all']);
ok('GET filter=all success',       $r['success'] === true);
ok('GET filter=all returns array', is_array($r['data'] ?? null));
$taskCount = count($r['data'] ?? []);
ok('GET filter=all has tasks',     $taskCount > 0,  "$taskCount tasks");

// ── GET pending ───────────────────────────────────────────────
$r = api('GET', ['filter' => 'pending']);
ok('GET filter=pending success',   $r['success'] === true);

// ── GET completed ─────────────────────────────────────────────
$r = api('GET', ['filter' => 'completed']);
ok('GET filter=completed success', $r['success'] === true);

// ── POST create valid task ────────────────────────────────────
$title = 'Http test task ' . time();
$r = api('POST', [], ['title' => $title, 'priority' => 'high']);
ok('POST create success',          $r['success'] === true, $r['message'] ?? '');
ok('POST returns task id',         isset($r['data']['id']));
$newId = $r['data']['id'] ?? null;

// ── POST create duplicate ─────────────────────────────────────
$r2 = api('POST', [], ['title' => $title, 'priority' => 'low']);
ok('POST duplicate rejected 409',  $r2['success'] === false && ($r2['message'] ?? '') !== '');

// ── POST create too short ─────────────────────────────────────
$r3 = api('POST', [], ['title' => 'ab', 'priority' => 'medium']);
ok('POST short title rejected',    $r3['success'] === false);

// ── POST create empty title ───────────────────────────────────
$r4 = api('POST', [], ['title' => '', 'priority' => 'medium']);
ok('POST empty title rejected',    $r4['success'] === false);

// ── PATCH complete task ───────────────────────────────────────
if ($newId) {
    $r = api('PATCH', [], ['id' => $newId, 'status' => 'completed']);
    ok('PATCH complete success',       $r['success'] === true, $r['message'] ?? '');
    ok('PATCH returns updated status', ($r['data']['status'] ?? '') === 'completed');

    // ── PATCH reopen task ─────────────────────────────────────
    $r = api('PATCH', [], ['id' => $newId, 'status' => 'pending']);
    ok('PATCH reopen success',         $r['success'] === true);

    // ── PATCH invalid status ──────────────────────────────────
    $r = api('PATCH', [], ['id' => $newId, 'status' => 'unknown']);
    ok('PATCH bad status rejected',    $r['success'] === false);

    // ── PATCH missing id ──────────────────────────────────────
    $r = api('PATCH', [], ['status' => 'completed']);
    ok('PATCH missing id rejected',    $r['success'] === false);

    // ── DELETE task ───────────────────────────────────────────
    $r = api('DELETE', [], ['id' => $newId]);
    ok('DELETE success',               $r['success'] === true, $r['message'] ?? '');

    // ── DELETE again (404) ────────────────────────────────────
    $r = api('DELETE', [], ['id' => $newId]);
    ok('DELETE again → 404',           $r['success'] === false);
} else {
    echo "  - Skipping PATCH/DELETE (no task created)\n";
}

// ── DELETE invalid id ─────────────────────────────────────────
$r = api('DELETE', [], ['id' => 0]);
ok('DELETE id=0 rejected',         $r['success'] === false);

echo "\n\033[1m── All HTTP tests complete ──────────────────────────\033[0m\n\n";
