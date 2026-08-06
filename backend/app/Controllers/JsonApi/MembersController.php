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
    /**
     * GET /api/members — list with stats, pagination and filters.
     * Mirrors backend/app/Controllers/Members/MembersListController.php
     * but uses prepared statements and adds pagination + stats in one payload.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $offset = ($page - 1) * $perPage;

        $search = trim((string) $request->query('search', ''));
        $branch = trim((string) $request->query('branch', ''));
        $status = trim((string) $request->query('status', ''));

        // Build dynamic WHERE for the listing query
        $where = ' WHERE 1 ';
        $types = '';
        $values = [];
        if ($search !== '') {
            $where .= ' AND (m.full_name LIKE ? OR m.member_code LIKE ? OR m.phone LIKE ? OR m.national_id LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'ssss';
            array_push($values, $like, $like, $like, $like);
        }
        if ($branch !== '') {
            $where .= ' AND m.branch_id = ?';
            $types .= 'i';
            $values[] = (int) $branch;
        }
        if ($status !== '' && ($status === '0' || $status === '1')) {
            $where .= ' AND m.is_active = ?';
            $types .= 'i';
            $values[] = (int) $status;
        }

        // Count total (with same filters)
        $countSql = 'SELECT COUNT(*) AS t FROM members m ' . $where;
        $countStmt = $this->prepare($conn, $countSql, $types, $values);
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $countStmt->close();

        // Fetch page
        $listSql = 'SELECT m.*, c.committee_name, b.branch_name '
                 . 'FROM members m '
                 . 'LEFT JOIN committees c ON m.committee_id = c.committee_id '
                 . 'LEFT JOIN branches b ON m.branch_id = b.branch_id '
                 . $where
                 . ' ORDER BY m.member_id DESC LIMIT ? OFFSET ?';
        $listTypes = $types . 'ii';
        $listValues = [...$values, $perPage, $offset];
        $listStmt = $this->prepare($conn, $listSql, $listTypes, $listValues);
        $listStmt->execute();
        $rows = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $listStmt->close();

        // Stats (independent of filters — same as PHP index.php lines 16-19)
        $totalMembers = (int) ($conn->query('SELECT COUNT(*) AS t FROM members')->fetch_assoc()['t'] ?? 0);
        $activeMembers = (int) ($conn->query('SELECT COUNT(*) AS t FROM members WHERE is_active=1')->fetch_assoc()['t'] ?? 0);
        $inactiveMembers = (int) ($conn->query('SELECT COUNT(*) AS t FROM members WHERE is_active=0')->fetch_assoc()['t'] ?? 0);
        $totalLoans = (float) ($conn->query('SELECT COALESCE(SUM(principal_amount),0) AS t FROM loans')->fetch_assoc()['t'] ?? 0);

        // Branch list for filter dropdown
        $branches = $conn->query('SELECT branch_id, branch_name FROM branches ORDER BY branch_name')->fetch_all(MYSQLI_ASSOC);
        $committees = $conn->query('SELECT committee_id, committee_name FROM committees ORDER BY committee_name')->fetch_all(MYSQLI_ASSOC);

        $lastPage = (int) max(1, ceil($total / $perPage));

        JsonResponse::success($rows, 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'stats' => [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'inactive_members' => $inactiveMembers,
                'total_loans' => $totalLoans,
            ],
            'filters' => [
                'branches' => $branches,
                'committees' => $committees,
            ],
        ]);
    }

    /**
     * POST /api/members — create a member.
     * Mirrors backend/app/Controllers/Members/MemberCreateController.php.
     */
    public function store(Request $request): never
    {
        $conn = Database::connection();

        $payload = $request->body();
        $memberCode = trim((string) ($payload['member_code'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $joinDate = trim((string) ($payload['join_date'] ?? ''));

        if ($memberCode === '' || $fullName === '' || $phone === '' || $joinDate === '') {
            JsonResponse::error('VALIDATION_ERROR', 'full_name, member_code, phone and join_date are required.', 422);
        }

        $committeeId = $this->nullableInt($payload['committee_id'] ?? null);
        $branchId = $this->nullableInt($payload['branch_id'] ?? null);

        // committee_id and branch_id are NOT NULL columns in the members table.
        // Reject empty selections with a clear validation error instead of an
        // opaque 500 from a strict-mode SQL failure.
        if ($committeeId === null) {
            JsonResponse::error('VALIDATION_ERROR', 'committee_id is required.', 422, ['fields' => ['committee_id' => 'required']]);
        }
        if ($branchId === null) {
            JsonResponse::error('VALIDATION_ERROR', 'branch_id is required.', 422, ['fields' => ['branch_id' => 'required']]);
        }

        $isActive = !empty($payload['is_active']) ? 1 : 0;

        $sql = 'INSERT INTO members (member_code, full_name, phone, dob, address, national_id, guarantor_name, guarantor_phone, committee_id, branch_id, join_date, is_active) '
             . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        // Field order:
        //   1 member_code     s
        //   2 full_name       s
        //   3 phone           s
        //   4 dob             s
        //   5 address         s
        //   6 national_id     s
        //   7 guarantor_name  s
        //   8 guarantor_phone s
        //   9 committee_id    i (nullable)
        //  10 branch_id       i (nullable)
        //  11 join_date       s
        //  12 is_active       i
        $types = 'ssssssssiisi';
        $values = [
            $memberCode,
            $fullName,
            $phone,
            (string) ($payload['dob'] ?? ''),
            (string) ($payload['address'] ?? ''),
            (string) ($payload['national_id'] ?? ''),
            (string) ($payload['guarantor_name'] ?? ''),
            (string) ($payload['guarantor_phone'] ?? ''),
            $committeeId,
            $branchId,
            $joinDate,
            $isActive,
        ];

        $stmt = $this->prepare($conn, $sql, $types, $values);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to create member: ' . $err, 500);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        // Return the freshly-created row in the same shape as show()
        $fetchStmt = $conn->prepare('SELECT m.*, c.committee_name, b.branch_name FROM members m LEFT JOIN committees c ON m.committee_id = c.committee_id LEFT JOIN branches b ON m.branch_id = b.branch_id WHERE m.member_id = ? LIMIT 1');
        $fetchStmt->bind_param('i', $newId);
        $fetchStmt->execute();
        $row = $fetchStmt->get_result()->fetch_assoc();
        $fetchStmt->close();

        JsonResponse::success(['member' => $row], 201);
    }

    /**
     * PUT /api/members/{id} — update a member.
     * Mirrors backend/app/Controllers/Members/MemberUpdateController.php.
     */
    public function update(Request $request): never
    {
        $memberId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT member_id FROM members WHERE member_id = ? LIMIT 1');
        $exists->bind_param('i', $memberId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Member not found.', 404);
        }
        $exists->close();

        $payload = $request->body();
        $memberCode = trim((string) ($payload['member_code'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $joinDate = trim((string) ($payload['join_date'] ?? ''));

        if ($memberCode === '' || $fullName === '' || $phone === '' || $joinDate === '') {
            JsonResponse::error('VALIDATION_ERROR', 'full_name, member_code, phone and join_date are required.', 422);
        }

        $committeeId = $this->nullableInt($payload['committee_id'] ?? null);
        $branchId = $this->nullableInt($payload['branch_id'] ?? null);

        if ($committeeId === null) {
            JsonResponse::error('VALIDATION_ERROR', 'committee_id is required.', 422, ['fields' => ['committee_id' => 'required']]);
        }
        if ($branchId === null) {
            JsonResponse::error('VALIDATION_ERROR', 'branch_id is required.', 422, ['fields' => ['branch_id' => 'required']]);
        }

        $isActive = !empty($payload['is_active']) ? 1 : 0;

        $sql = 'UPDATE members SET member_code=?, full_name=?, phone=?, dob=?, address=?, national_id=?, guarantor_name=?, guarantor_phone=?, committee_id=?, branch_id=?, join_date=?, is_active=? WHERE member_id=?';
        // Field order matches store() above; final WHERE member_id=? is an 'i'.
        $types = 'ssssssssiisii';
        $values = [
            $memberCode,
            $fullName,
            $phone,
            (string) ($payload['dob'] ?? ''),
            (string) ($payload['address'] ?? ''),
            (string) ($payload['national_id'] ?? ''),
            (string) ($payload['guarantor_name'] ?? ''),
            (string) ($payload['guarantor_phone'] ?? ''),
            $committeeId,
            $branchId,
            $joinDate,
            $isActive,
            $memberId,
        ];

        $stmt = $this->prepare($conn, $sql, $types, $values);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to update member: ' . $err, 500);
        }
        $stmt->close();

        JsonResponse::success(['member_id' => $memberId]);
    }

    /**
     * DELETE /api/members/{id} — delete a member.
     * Mirrors backend/app/Controllers/Members/MemberDeleteController.php.
     * Adds a safety guard: members with existing loans cannot be deleted.
     */
    public function destroy(Request $request): never
    {
        $memberId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT member_id FROM members WHERE member_id = ? LIMIT 1');
        $exists->bind_param('i', $memberId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Member not found.', 404);
        }
        $exists->close();

        // Safety guard — refuse to delete a member with existing loans.
        $loans = $conn->prepare('SELECT 1 FROM loans WHERE member_id = ? LIMIT 1');
        $loans->bind_param('i', $memberId);
        $loans->execute();
        if ($loans->get_result()->fetch_assoc()) {
            $loans->close();
            JsonResponse::error('MEMBER_HAS_LOANS', 'Cannot delete member with existing loans.', 409);
        }
        $loans->close();

        $del = $conn->prepare('DELETE FROM members WHERE member_id = ?');
        $del->bind_param('i', $memberId);
        $del->execute();
        $del->close();

        JsonResponse::success(['member_id' => $memberId]);
    }

    public function transactions(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/transactions'); }
    public function loans(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/loans'); }
    public function savings(Request $request): never { JsonResponse::notImplemented('GET /api/members/{id}/savings'); }

    /**
     * Single member view — mirrors backend/app/Controllers/Members/MemberViewController.php
     * so the React MemberProfilePage can render the same data as members/view.php.
     * Returns member + committee/branch + loans + savings + payments + summary.
     */
    public function show(Request $request): never
    {
        $memberId = (int) $request->param('id', 0);
        if ($memberId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Member id is required.', 422);
        }

        $conn = Database::connection();

        $stmt = $conn->prepare('SELECT m.*, c.committee_name, b.branch_name
                                FROM members m
                                LEFT JOIN committees c ON m.committee_id = c.committee_id
                                LEFT JOIN branches b ON m.branch_id = b.branch_id
                                WHERE m.member_id = ? LIMIT 1');
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$member) {
            JsonResponse::error('NOT_FOUND', 'Member not found.', 404);
        }

        $loansStmt = $conn->prepare('SELECT * FROM loans WHERE member_id = ? ORDER BY loan_id DESC');
        $loansStmt->bind_param('i', $memberId);
        $loansStmt->execute();
        $loans = $loansStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $loansStmt->close();

        $savingsStmt = $conn->prepare('SELECT * FROM savings WHERE member_id = ?');
        $savingsStmt->bind_param('i', $memberId);
        $savingsStmt->execute();
        $savings = $savingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $savingsStmt->close();

        $paymentsStmt = $conn->prepare('SELECT lp.*, l.loan_code
                                        FROM loan_payments lp
                                        LEFT JOIN loans l ON lp.loan_id = l.loan_id
                                        WHERE l.member_id = ?
                                        ORDER BY lp.payment_id DESC');
        $paymentsStmt->bind_param('i', $memberId);
        $paymentsStmt->execute();
        $payments = $paymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $paymentsStmt->close();

        $loanTotalStmt = $conn->prepare('SELECT COALESCE(SUM(principal_amount),0) AS t FROM loans WHERE member_id = ?');
        $loanTotalStmt->bind_param('i', $memberId);
        $loanTotalStmt->execute();
        $loanTotal = (float) ($loanTotalStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $loanTotalStmt->close();

        $paidTotalStmt = $conn->prepare('SELECT COALESCE(SUM(total_paid),0) AS t FROM loans WHERE member_id = ?');
        $paidTotalStmt->bind_param('i', $memberId);
        $paidTotalStmt->execute();
        $paidTotal = (float) ($paidTotalStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $paidTotalStmt->close();

        $savingTotalStmt = $conn->prepare('SELECT COALESCE(SUM(balance),0) AS t FROM savings WHERE member_id = ?');
        $savingTotalStmt->bind_param('i', $memberId);
        $savingTotalStmt->execute();
        $savingTotal = (float) ($savingTotalStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $savingTotalStmt->close();

        JsonResponse::success([
            'member' => $member,
            'loans' => $loans,
            'savings' => $savings,
            'payments' => $payments,
            'summary' => [
                'loan_total' => $loanTotal,
                'paid_total' => $paidTotal,
                'due_total' => $loanTotal - $paidTotal,
                'saving_total' => $savingTotal,
            ],
        ]);
    }

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

    private function idOrFail(Request $request): int
    {
        $memberId = (int) $request->param('id', 0);
        if ($memberId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Member id is required.', 422);
        }
        return $memberId;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === '0') return null;
        return (int) $value;
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