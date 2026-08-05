<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class FieldOfficersController
{
    /**
     * GET /api/field-officers — list field officers.
     * Visible to admins (all officers) and branch_managers (officers in their branch).
     * Returns 403 for any other role.
     */
    public function index(Request $request): never
    {
        if (empty($_SESSION['user_id'])) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        $role = (string) ($_SESSION['role'] ?? '');
        if ($role !== 'admin' && $role !== 'branch_manager') {
            JsonResponse::error('FORBIDDEN', 'You do not have permission to view field officers.', 403);
        }

        $conn = Database::connection();

        if ($role === 'admin') {
            $sql = 'SELECT u.user_id, u.username, u.full_name, u.phone, u.branch_id, b.branch_name, u.is_active
                    FROM users u
                    LEFT JOIN branches b ON b.branch_id = u.branch_id
                    WHERE u.role = ?
                    ORDER BY u.full_name';
            $stmt = $conn->prepare($sql);
            $r = 'field_officer';
            $stmt->bind_param('s', $r);
        } else {
            $branchId = (int) ($_SESSION['branch_id'] ?? 0);
            if ($branchId <= 0) {
                JsonResponse::error('FORBIDDEN', 'Your account is not assigned to a branch.', 403);
            }
            $sql = 'SELECT u.user_id, u.username, u.full_name, u.phone, u.branch_id, b.branch_name, u.is_active
                    FROM users u
                    LEFT JOIN branches b ON b.branch_id = u.branch_id
                    WHERE u.role = ? AND u.branch_id = ?
                    ORDER BY u.full_name';
            $stmt = $conn->prepare($sql);
            $r = 'field_officer';
            $stmt->bind_param('si', $r, $branchId);
        }

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $officers = array_map(static function (array $row): array {
            return [
                'user_id'     => (int) $row['user_id'],
                'username'    => $row['username'],
                'full_name'   => $row['full_name'],
                'phone'       => $row['phone'] ?? null,
                'branch_id'   => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
                'branch_name' => $row['branch_name'] ?? null,
                'is_active'   => (int) $row['is_active'],
            ];
        }, $rows);

        JsonResponse::success(['field_officers' => $officers]);
    }
}
