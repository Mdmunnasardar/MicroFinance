<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class CommitteesController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/committees'); }
    public function store(Request $request): never { JsonResponse::notImplemented('POST /api/committees'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/committees/{id}'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/committees/{id}'); }
    public function destroy(Request $request): never { JsonResponse::notImplemented('DELETE /api/committees/{id}'); }
    public function members(Request $request): never { JsonResponse::notImplemented('GET /api/committees/{id}/members'); }
    public function addMember(Request $request): never { JsonResponse::notImplemented('POST /api/committees/{id}/members'); }
    public function removeMember(Request $request): never { JsonResponse::notImplemented('DELETE /api/committees/{id}/members/{memberId}'); }
}