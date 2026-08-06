<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\JsonResponse;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isset($_SESSION['user_id'])) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }
    }
}
