<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class NotificationsController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/notifications'); }
    public function markRead(Request $request): never { JsonResponse::notImplemented('POST /api/notifications/{id}/read'); }
}