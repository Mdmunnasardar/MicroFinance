<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class MembersController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/members'); }
    public function store(Request $request): never { JsonResponse::notImplemented('POST /api/members'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/members/{id}'); }
    public function destroy(Request $request): never { JsonResponse::notImplemented('DELETE /api/members/{id}'); }
    public function transactions(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/transactions'); }
    public function loans(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/loans'); }
    public function savings(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/savings'); }
}