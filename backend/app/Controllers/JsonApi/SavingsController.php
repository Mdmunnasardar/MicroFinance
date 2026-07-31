<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class SavingsController
{
    public function index(Request $request): never { JsonResponse::notImplemented('GET /api/savings'); }
    public function store(Request $request): never { JsonResponse::notImplemented('POST /api/savings'); }
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/savings/{id}'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/savings/{id}'); }
    public function destroy(Request $request): never { JsonResponse::notImplemented('DELETE /api/savings/{id}'); }
    public function byMember(Request $request): never { JsonResponse::notImplemented('GET /api/savings/member/{memberId}'); }
    public function deposit(Request $request): never { JsonResponse::notImplemented('POST /api/savings/deposits'); }
    public function withdraw(Request $request): never { JsonResponse::notImplemented('POST /api/savings/withdrawals'); }
    public function transactions(Request $request): never { JsonResponse::notImplemented('GET /api/savings/transactions'); }
}