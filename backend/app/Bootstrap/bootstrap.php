<?php

declare(strict_types=1);

use App\Helpers\JsonResponse;
use App\Helpers\Logger;
use App\Middleware\CorsMiddleware;

ob_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception): never {
    Logger::error($exception);
    JsonResponse::error('INTERNAL_SERVER_ERROR', 'An unexpected server error occurred.', 500);
});

CorsMiddleware::handle();
