<?php
// ── CORS ─────────────────────────────────────────────────────
// Allow requests from any Vercel preview URL and the production domain.
$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
];

// Allow all *.vercel.app subdomains + any FRONTEND_URL set in env
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$frontendUrl = getenv('FRONTEND_URL') ?: '';

$isAllowed = in_array($origin, $allowedOrigins, true)
    || ($frontendUrl && $origin === $frontendUrl)
    || preg_match('/^https:\/\/[\w\-]+\.vercel\.app$/', $origin);

if ($isAllowed) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Fallback: allow all during development (remove in strict production)
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: false');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// bootstrap
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

$method = $_SERVER['REQUEST_METHOD'];

// Read JSON body for non-GET requests
$body = [];
if ($method !== 'GET') {
    $raw  = file_get_contents('php://input');
    $body = $raw ? (json_decode($raw, true) ?? []) : [];
}

try {
    // Corrected: Safely initialize database connection inside the try block
    $model = new TaskModel();

    switch ($method) {
        // GET 
        case 'GET':
            if (isset($_GET['stats'])) {
                Response::success($model->getStats());
            } else {
                $filter = $_GET['filter'] ?? 'all';
                Response::success($model->getAll($filter));
            }
            break;

        // POST · Create task
        case 'POST':
            $title    = trim($body['title']    ?? '');
            $priority = trim($body['priority'] ?? 'medium');

            $v = (new Validator())
                ->required('title',    $title)
                ->minLength('title',   $title, 3)
                ->maxLength('title',   $title, 120)
                ->startsAlphaNum('title', $title)
                ->inList('priority', $priority, ['high','medium','low']);

            if (!$v->passes()) {
                Response::error('Validation failed.', 422, $v->errors());
            }

            if ($model->isDuplicate($title)) {
                Response::error('A task with this title already exists.', 409);
            }

            $id   = $model->create($title, $priority);
            $task = $model->findById($id);
            Response::success($task, 'Task created.');
            break;

        // PATCH · Update status 
        case 'PATCH':
            $id     = (int)($body['id']     ?? 0);
            $status = trim($body['status'] ?? '');

            $v = (new Validator())
                ->required('id',     $id)
                ->inList('status', $status, ['pending','completed']);

            if (!$v->passes() || $id < 1) {
                Response::error('Invalid data.', 422, $v->errors());
            }

            if (!$model->findById($id)) {
                Response::error('Task not found.', 404);
            }

            $model->setStatus($id, $status);
            $task = $model->findById($id);
            Response::success($task, 'Task updated.');
            break;

        // DELETE 
        case 'DELETE':
            $id = (int)($body['id'] ?? 0);

            if ($id < 1)                  Response::error('Invalid ID.',       422);
            if (!$model->findById($id))      Response::error('Task not found.',   404);

            $model->delete($id);
            Response::success(null, 'Task deleted.');
            break;

        default:
            Response::error('Method not allowed.', 405);
            break;
    }

} catch (PDOException $e) {
    error_log('[TaskBoard] DB error: ' . $e->getMessage());
    Response::error('Database error. Please verify your connection setup.', 500);
} catch (Throwable $e) {
    error_log('[TaskBoard] Error: ' . $e->getMessage());
    Response::error('Something went wrong.', 500);
}