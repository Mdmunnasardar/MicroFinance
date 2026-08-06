<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class ProfileController
{
    private const MAX_AVATAR_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * GET /api/profile — return the authenticated user's profile.
     */
    public function show(Request $request): never
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare(
            'SELECT u.user_id, u.username, u.full_name, u.role, u.phone, u.branch_id, b.branch_name, u.avatar, u.is_active, u.created_at
             FROM users u
             LEFT JOIN branches b ON b.branch_id = u.branch_id
             WHERE u.user_id = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            JsonResponse::error('NOT_FOUND', 'User no longer exists.', 404);
        }

        JsonResponse::success([
            'user' => [
                'id'         => (int) $row['user_id'],
                'username'   => $row['username'],
                // `name` mirrors AuthController::session for React AuthContext compatibility.
                // `full_name` is preserved for the Profile/Edit pages.
                'name'       => $row['full_name'],
                'full_name'  => $row['full_name'],
                'role'       => $row['role'],
                'phone'      => $row['phone'] ?? null,
                'branch_id'  => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
                'branch_name'=> $row['branch_name'] ?? null,
                'avatar'     => $row['avatar'] ?? null,
                'is_active'  => (int) $row['is_active'],
                'created_at' => $row['created_at'],
            ],
        ]);
    }

    /**
     * PUT /api/profile — update editable profile fields.
     * Only full_name, phone, branch_id are writable. All other fields are ignored.
     */
    public function update(Request $request): never
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        $fullName = trim((string) $request->input('full_name', ''));
        $phone    = trim((string) $request->input('phone', ''));
        $branchId = $request->input('branch_id', null);

        $fieldErrors = [];
        if ($fullName === '') {
            $fieldErrors['full_name'] = 'required';
        } elseif (mb_strlen($fullName) > 100) {
            $fieldErrors['full_name'] = 'max_length';
        }
        if ($phone !== '' && mb_strlen($phone) > 15) {
            $fieldErrors['phone'] = 'max_length';
        }

        $branchIdInt = null;
        if ($branchId !== null && $branchId !== '') {
            if (!is_numeric($branchId) || (int) $branchId <= 0) {
                $fieldErrors['branch_id'] = 'invalid';
            } else {
                $branchIdInt = (int) $branchId;
            }
        }

        if ($branchIdInt !== null) {
            $conn = Database::connection();
            $check = $conn->prepare('SELECT branch_id FROM branches WHERE branch_id = ? LIMIT 1');
            $check->bind_param('i', $branchIdInt);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$exists) {
                $fieldErrors['branch_id'] = 'not_found';
            }
        }

        if ($fieldErrors !== []) {
            JsonResponse::error('VALIDATION_ERROR', 'Some fields are invalid.', 422, ['fields' => $fieldErrors]);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, branch_id = ? WHERE user_id = ?');
        $phoneParam = $phone === '' ? null : $phone;
        $branchParam = $branchIdInt;
        $stmt->bind_param('ssii', $fullName, $phoneParam, $branchParam, $userId);
        $stmt->execute();
        $stmt->close();

        // Re-read fresh profile so the client gets the canonical shape.
        $this->show($request);
    }

    /**
     * POST /api/profile/avatar — upload a profile picture.
     * Multipart form-data with field name "avatar".
     */
    public function uploadAvatar(Request $request): never
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar']) || (int) $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            JsonResponse::error('VALIDATION_ERROR', 'Avatar file is required.', 422, [
                'fields' => ['avatar' => 'required'],
            ]);
        }

        $file = $_FILES['avatar'];
        $size = (int) $file['size'];
        if ($size <= 0 || $size > self::MAX_AVATAR_BYTES) {
            JsonResponse::error('VALIDATION_ERROR', 'Avatar must be 5 MB or smaller.', 422, [
                'fields' => ['avatar' => 'file_too_large'],
            ]);
        }

        $declaredType = strtolower((string) ($file['type'] ?? ''));
        if (!in_array($declaredType, self::ALLOWED_AVATAR_TYPES, true)) {
            JsonResponse::error('VALIDATION_ERROR', 'Avatar must be a JPEG, PNG, GIF, or WebP image.', 422, [
                'fields' => ['avatar' => 'invalid_type'],
            ]);
        }

        // Defense-in-depth: confirm the file is actually an image.
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            JsonResponse::error('VALIDATION_ERROR', 'Avatar is not a valid image.', 422, [
                'fields' => ['avatar' => 'invalid_type'],
            ]);
        }

        $uploadDir = dirname(__DIR__, 4) . '/uploads/avatars';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            JsonResponse::error('INTERNAL_SERVER_ERROR', 'Could not create upload directory.', 500);
        }

        $originalName = basename((string) ($file['name'] ?? 'avatar'));
        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'avatar';
        $newName = time() . '_' . $safeBase;
        $dest = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            JsonResponse::error('INTERNAL_SERVER_ERROR', 'Failed to save uploaded file.', 500);
        }

        $conn = Database::connection();

        // Capture the previous avatar (if any) so we can delete the file best-effort.
        $prev = null;
        $lookup = $conn->prepare('SELECT avatar FROM users WHERE user_id = ? LIMIT 1');
        $lookup->bind_param('i', $userId);
        $lookup->execute();
        $prevRow = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if ($prevRow && !empty($prevRow['avatar'])) {
            $prev = $prevRow['avatar'];
        }

        $upd = $conn->prepare('UPDATE users SET avatar = ? WHERE user_id = ?');
        $upd->bind_param('si', $newName, $userId);
        $upd->execute();
        $upd->close();

        if ($prev !== null && $prev !== $newName) {
            $prevPath = $uploadDir . '/' . basename($prev);
            if (is_file($prevPath)) {
                @unlink($prevPath);
            }
        }

        JsonResponse::success([
            'avatar' => $newName,
        ]);
    }

    /**
     * PUT /api/profile/password — change the authenticated user's password.
     * Body: { current_password, new_password, confirm_password } (JSON).
     */
    public function changePassword(Request $request): never
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        $fieldErrors = [];
        if ($current === '') $fieldErrors['current_password'] = 'required';
        if ($new === '')     $fieldErrors['new_password']     = 'required';
        if ($confirm === '') $fieldErrors['confirm_password'] = 'required';

        if ($fieldErrors !== []) {
            JsonResponse::error('VALIDATION_ERROR', 'All three password fields are required.', 422, ['fields' => $fieldErrors]);
        }

        if (strlen($new) < 6) {
            JsonResponse::error('VALIDATION_ERROR', 'New password must be at least 6 characters.', 422, [
                'fields' => ['new_password' => 'min_length'],
            ]);
        }

        if ($new !== $confirm) {
            JsonResponse::error('VALIDATION_ERROR', 'New password and confirmation do not match.', 422, [
                'fields' => ['confirm_password' => 'mismatch'],
            ]);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            JsonResponse::error('NOT_FOUND', 'User no longer exists.', 404);
        }

        if (!password_verify($current, (string) $row['password_hash'])) {
            JsonResponse::error('VALIDATION_ERROR', 'Current password is incorrect.', 422, [
                'fields' => ['current_password' => 'incorrect'],
            ]);
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $upd->bind_param('si', $newHash, $userId);
        $upd->execute();
        $upd->close();

        JsonResponse::success(['changed' => true]);
    }
}
