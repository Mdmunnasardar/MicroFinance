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
     * Member lookup by name, member_code, phone, national_id, or numeric
     * member_id. Also matches by loan_code, in which case the matching loan
     * is returned in `matchedLoan` so the UI can deep-link to it.
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
        $sql = 'SELECT DISTINCT m.member_id, m.full_name, m.member_code, m.phone, m.national_id, m.is_active '
             . 'FROM members m '
             . 'WHERE m.full_name LIKE ? OR m.member_code LIKE ? OR m.phone LIKE ? OR m.national_id LIKE ?';
        $types = 'ssss';
        $values = [$like, $like, $like, $like];

        if ($isNumeric && $idValue > 0) {
            $sql .= ' OR m.member_id = ?';
            $types .= 'i';
            $values[] = $idValue;
        }

        $sql .= ' OR EXISTS (SELECT 1 FROM loans l WHERE l.member_id = m.member_id AND l.loan_code LIKE ?)';
        $types .= 's';
        $values[] = $like;

        $sql .= ' ORDER BY (CASE WHEN m.is_active = 1 THEN 0 ELSE 1 END) ASC, m.full_name ASC LIMIT ?';
        $types .= 'i';
        $values[] = $limit;

        $stmt = $this->prepare($conn, $sql, $types, $values);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        $memberIds = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) $row['member_id'],
                'name' => $row['full_name'],
                'code' => $row['member_code'],
                'phone' => $row['phone'],
                'nationalId' => $row['national_id'],
                'isActive' => (int) $row['is_active'] === 1,
            ];
            $memberIds[] = (int) $row['member_id'];
        }
        $stmt->close();

        $loanHint = [];
        if ($memberIds !== []) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $hintTypes = str_repeat('i', count($memberIds));
            $hintSql = "SELECT loan_id, loan_code, member_id, status "
                     . "FROM loans "
                     . "WHERE member_id IN ($placeholders) AND loan_code LIKE ? "
                     . "ORDER BY (CASE WHEN status = 'active' THEN 0 ELSE 1 END) ASC, loan_id DESC";
            $hintValues = [...$memberIds, $like];
            $hintTypes .= 's';
            $hintStmt = $this->prepare($conn, $hintSql, $hintTypes, $hintValues);
            $hintStmt->execute();
            $hintRes = $hintStmt->get_result();
            while ($h = $hintRes->fetch_assoc()) {
                $loanHint[(int) $h['member_id']] = [
                    'id' => (int) $h['loan_id'],
                    'code' => $h['loan_code'],
                    'status' => $h['status'],
                ];
            }
            $hintStmt->close();
        }

        foreach ($items as $idx => $item) {
            $items[$idx]['matchedLoan'] = $loanHint[$item['id']] ?? null;
        }

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