<?php
class Response
{
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK'): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    public static function error(string $message, int $code = 400, array $errors = []): void
    {
        self::json(['success' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}
