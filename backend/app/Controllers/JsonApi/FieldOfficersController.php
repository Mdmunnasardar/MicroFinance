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

    /**
     * GET /api/field-officers/summary — stats for the currently-authenticated
     * field_officer (Total Committees, Total Members, Today's Collection,
     * Total Collection). Mirrors the legacy ProfileController stat grid.
     */
    public function summary(Request $request): never
    {
        if (empty($_SESSION['user_id'])) {
            JsonResponse::error('UNAUTHORIZED', 'Authentication required.', 401);
        }

        $role = (string) ($_SESSION['role'] ?? '');
        if ($role !== 'field_officer') {
            JsonResponse::error('FORBIDDEN', 'Only field officers can view this summary.', 403);
        }

        $userId = (int) $_SESSION['user_id'];

        $conn = Database::connection();

        // Total Committees / Members owned by this field officer.
        $stmt = $conn->prepare(
            'SELECT COUNT(DISTINCT c.committee_id) AS total_committees,
                    COUNT(DISTINCT m.member_id) AS total_members
             FROM users u
             LEFT JOIN committees c ON u.user_id = c.field_officer_id
             LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
             WHERE u.user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalCommittees = (int) ($row['total_committees'] ?? 0);
        $totalMembers    = (int) ($row['total_members'] ?? 0);

        // Loan payments are tied to a loan → member → committee → field_officer.
        // We aggregate by joining through that chain so the officer sees their
        // own collections regardless of who clicked "record payment".
        $sum = $conn->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN DATE(lp.payment_date) = CURDATE() THEN lp.amount ELSE 0 END), 0) AS today_collection,
                COALESCE(SUM(lp.amount), 0) AS total_collection
             FROM loan_payments lp
             INNER JOIN loans l      ON l.loan_id    = lp.loan_id
             INNER JOIN members m    ON m.member_id  = lp.member_id
             INNER JOIN committees c ON c.committee_id = m.committee_id
             WHERE c.field_officer_id = ?'
        );
        $sum->bind_param('i', $userId);
        $sum->execute();
        $sumRow = $sum->get_result()->fetch_assoc();
        $sum->close();

        JsonResponse::success([
            'summary' => [
                'total_committees' => $totalCommittees,
                'total_members'    => $totalMembers,
                'today_collection' => (float) ($sumRow['today_collection'] ?? 0),
                'total_collection' => (float) ($sumRow['total_collection'] ?? 0),
            ],
        ]);
    }
}
