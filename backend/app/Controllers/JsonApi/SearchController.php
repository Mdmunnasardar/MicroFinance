<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class SearchController
{
    public function index(Request $request): never
    {
        JsonResponse::notImplemented('GET /api/search');
    }
}
