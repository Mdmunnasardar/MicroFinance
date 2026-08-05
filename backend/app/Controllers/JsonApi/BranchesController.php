<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class BranchesController
{
    /**
     * GET /api/branches — list all branches (active first, then inactive), id + name.
     * Used by the React Edit Profile page to populate the branch dropdown.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();
        $rows = $conn->query(
            'SELECT branch_id, branch_name, branch_code, is_active
             FROM branches
             ORDER BY is_active DESC, branch_name'
        )->fetch_all(MYSQLI_ASSOC);

        $branches = array_map(static function (array $row): array {
            return [
                'branch_id'   => (int) $row['branch_id'],
                'branch_name' => $row['branch_name'],
                'branch_code' => $row['branch_code'],
                'is_active'   => (int) $row['is_active'],
            ];
        }, $rows);

        JsonResponse::success(['branches' => $branches]);
    }
}
