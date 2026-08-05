<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

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

    /**
     * Smart member lookup used by the Collect Payment workflow.
     * Searches members by name, member_code, phone, or national_id.
     * Read-only — does not mutate any state.
     */
    public function search(Request $request): never
    {
        $conn = Database::connection();

        $rawQuery = trim((string) $request->query('q', ''));
        if ($rawQuery === '') {
            JsonResponse::success([], 200, ['total' => 0, 'query' => '']);
        }

        $limit = min(25, max(1, (int) $request->query('limit', 12)));

        // If the query is purely digits, also match the exact numeric member_id
        // (Field Officers often recall a numeric ID by heart).
        $isNumeric = ctype_digit($rawQuery);
        $idValue = $isNumeric ? (int) $rawQuery : 0;

        $like = '%' . $rawQuery . '%';
        $sql = 'SELECT member_id, full_name, member_code, phone, national_id, is_active '
             . 'FROM members '
             . 'WHERE full_name LIKE ? OR member_code LIKE ? OR phone LIKE ? OR national_id LIKE ?';
        $types = 'ssss';
        $values = [$like, $like, $like, $like];

        if ($isNumeric && $idValue > 0) {
            $sql .= ' OR member_id = ?';
            $types .= 'i';
            $values[] = $idValue;
        }

        $sql .= ' ORDER BY (CASE WHEN is_active = 1 THEN 0 ELSE 1 END) ASC, full_name ASC LIMIT ?';
        $types .= 'i';
        $values[] = $limit;

        $stmt = $this->prepare($conn, $sql, $types, $values);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) $row['member_id'],
                'name' => $row['full_name'],
                'code' => $row['member_code'],
                'phone' => $row['phone'],
                'nationalId' => $row['national_id'],
                'isActive' => (int) $row['is_active'] === 1,
            ];
        }
        $stmt->close();

        JsonResponse::success($items, 200, [
            'total' => count($items),
            'query' => $rawQuery,
            'limit' => $limit,
        ]);
    }

    private function prepare(mysqli $conn, string $sql, string $types, array $values): mysqli_stmt
    {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            JsonResponse::error('DB_PREPARE_FAILED', 'Unable to prepare statement.', 500);
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$values);
        }
        return $stmt;
    }
}