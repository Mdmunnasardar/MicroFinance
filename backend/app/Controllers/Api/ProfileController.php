<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class ProfileController
{
    public function show(Request $request): never { JsonResponse::notImplemented('GET /api/profile'); }
    public function update(Request $request): never { JsonResponse::notImplemented('PUT /api/profile'); }
    public function uploadAvatar(Request $request): never { JsonResponse::notImplemented('POST /api/profile/avatar'); }
    public function changePassword(Request $request): never { JsonResponse::notImplemented('PUT /api/profile/password'); }
}