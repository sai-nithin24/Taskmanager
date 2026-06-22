<?php
// ============================================================
//  tests/TaskModelTest.php
//  Basic unit tests — run with: php tests/TaskModelTest.php
//  (No PHPUnit required; uses a lightweight assert helper)
// ============================================================

// ── Assert helpers ────────────────────────────────────────────
function pass(string $label): void { echo "\033[32m  ✓ {$label}\033[0m\n"; }
function fail(string $label, string $why): void { echo "\033[31m  ✕ {$label}: {$why}\033[0m\n"; }

function assertNull($val, string $label): void  { is_null($val) ? pass($label) : fail($label, "Expected null, got '$val'"); }
function assertNotNull($val, string $label): void { !is_null($val) ? pass($label) : fail($label, 'Expected non-null'); }
function assertMatch(string $pattern, string $val, string $label): void { preg_match($pattern, $val) ? pass($label) : fail($label, "'{$val}' did not match {$pattern}"); }

// ── Validator tests ───────────────────────────────────────────
require_once __DIR__ . '/../src/utils/Validator.php';

echo "\n\033[1m── Validator ────────────────────────────────────────\033[0m\n";

$v = (new Validator())->required('title', '');
$e = $v->errors();
assertNotNull($e['title'] ?? null, 'required: empty string fails');

$v = (new Validator())->required('title', 'hello');
assertNull($v->errors()['title'] ?? null, 'required: non-empty passes');

$v = (new Validator())->minLength('title', 'ab', 3);
assertNotNull($v->errors()['title'] ?? null, 'minLength: 2 chars fails for min=3');

$v = (new Validator())->minLength('title', 'abc', 3);
assertNull($v->errors()['title'] ?? null, 'minLength: 3 chars passes');

$v = (new Validator())->maxLength('title', str_repeat('a', 121), 120);
assertNotNull($v->errors()['title'] ?? null, 'maxLength: 121 chars fails for max=120');

$v = (new Validator())->startsAlphaNum('title', '!bad');
assertNotNull($v->errors()['title'] ?? null, 'startsAlphaNum: leading ! fails');

$v = (new Validator())->startsAlphaNum('title', 'Good title');
assertNull($v->errors()['title'] ?? null, 'startsAlphaNum: leading G passes');

$v = (new Validator())->inList('priority', 'extreme', ['high','medium','low']);
assertNotNull($v->errors()['priority'] ?? null, 'inList: invalid priority fails');

$v = (new Validator())->inList('priority', 'high', ['high','medium','low']);
assertNull($v->errors()['priority'] ?? null, 'inList: valid priority passes');

echo "\n\033[1m── All assertions complete ───────────────────────────\033[0m\n\n";
