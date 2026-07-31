<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class InstallmentsController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/installments'); }
    public function store(Request $request): never { JsonResponse::notImplemented('POST /api/installments'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/installments/{id}'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/installments/{id}'); }
    public function destroy(Request $request): never { JsonResponse::notImplemented('DELETE /api/installments/{id}'); }
}