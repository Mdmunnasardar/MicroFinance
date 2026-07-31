<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class DueSystemController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/due-system'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/due-system/{id}'); }
    public function collect(Request $request): never { JsonResponse::notImplemented('POST /api/due-system/{id}/collect'); }
}