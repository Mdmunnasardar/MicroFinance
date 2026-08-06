<?php

declare(strict_types=1);

namespace App\Middleware;

final class CorsMiddleware
{
    public static function handle(): void
    {
        $config = require dirname(__DIR__) . '/Config/cors.php';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '' && in_array($origin, $config['allowed_origins'], true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: ' . $config['allowed_methods']);
        header('Access-Control-Allow-Headers: ' . $config['allowed_headers']);
        header('Access-Control-Max-Age: ' . $config['max_age']);

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
