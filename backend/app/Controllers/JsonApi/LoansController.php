<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class LoansController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/loans'); }
    public function store(Request $request): never { JsonResponse::notImplemented('POST /api/loans'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/loans/{id}'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/loans/{id}'); }
    public function destroy(Request $request): never { JsonResponse::notImplemented('DELETE /api/loans/{id}'); }
    public function updateStatus(Request $request): never { JsonResponse::notImplemented('POST /api/loans/{id}/status'); }
}