<?php

declare(strict_types=1);

namespace App\Helpers;

final class JsonResponse
{
    public static function success(mixed $data = null, int $status = 200, array $meta = []): never
    {
        self::send([
            'success' => true,
            'data' => $data,
            'meta' => $meta ?: (object) [],
        ], $status);
    }

    public static function error(string $code, string $message, int $status, array $details = []): never
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        self::send(['success' => false, 'error' => $error], $status);
    }

    public static function notImplemented(string $resource = 'This endpoint'): never
    {
        self::error('NOT_IMPLEMENTED', $resource . ' has not been migrated to the JSON API yet.', 501);
    }

    private static function send(array $payload, int $status): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}
