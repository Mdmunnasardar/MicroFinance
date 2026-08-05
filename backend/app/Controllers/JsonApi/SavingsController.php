<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

final class SavingsController
{
    /**
     * GET /api/savings — paginated list with search and type filters plus stats.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));

        $where = ' WHERE 1 ';
        $types = '';
        $values = [];

        if ($search !== '') {
            $where .= ' AND (m.full_name LIKE ? OR m.member_code LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'ss';
            array_push($values, $like, $like);
        }
        if (in_array($type, ['individual', 'group'], true)) {
            $where .= ' AND s.saving_type = ?';
            $types .= 's';
            $values[] = $type;
        }

        $countStmt = $this->prepare(
            $conn,
            'SELECT COUNT(*) AS t FROM savings s LEFT JOIN members m ON s.member_id = m.member_id ' . $where,
            $types,
            $values,
        );
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $countStmt->close();

        $listSql = 'SELECT s.saving_id, s.member_id, s.saving_type, s.balance, s.last_transaction_date, s.created_at, '
                 . 'm.full_name, m.member_code '
                 . 'FROM savings s '
                 . 'LEFT JOIN members m ON s.member_id = m.member_id '
                 . $where
                 . ' ORDER BY s.saving_id DESC LIMIT ? OFFSET ?';
        $stmt = $this->prepare($conn, $listSql, $types . 'ii', [...$values, $perPage, $offset]);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Unfiltered stats.
        $stats = $conn->query("SELECT COUNT(*) AS total_accounts, "
                            . "COALESCE(SUM(balance),0) AS total_balance, "
                            . "SUM(CASE WHEN saving_type='individual' THEN 1 ELSE 0 END) AS individual_accounts, "
                            . "SUM(CASE WHEN saving_type='group' THEN 1 ELSE 0 END) AS group_accounts, "
                            . "SUM(CASE WHEN last_transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS recent_accounts "
                            . 'FROM savings')->fetch_assoc();

        $members = $conn->query('SELECT member_id, member_code, full_name FROM members ORDER BY full_name')->fetch_all(MYSQLI_ASSOC);

        JsonResponse::success($rows, 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
            'stats' => [
                'total_accounts' => (int) ($stats['total_accounts'] ?? 0),
                'total_balance' => (float) ($stats['total_balance'] ?? 0),
                'individual_accounts' => (int) ($stats['individual_accounts'] ?? 0),
                'group_accounts' => (int) ($stats['group_accounts'] ?? 0),
                'recent_accounts' => (int) ($stats['recent_accounts'] ?? 0),
            ],
            'filters' => ['members' => $members],
        ]);
    }

    /** POST /api/savings — create a savings account. */
    public function store(Request $request): never
    {
        $conn = Database::connection();
        $payload = $request->body();

        $memberId = (int) ($payload['member_id'] ?? 0);
        $balance = (float) ($payload['balance'] ?? 0);
        $savingType = trim((string) ($payload['saving_type'] ?? 'individual'));
        $lastTransactionDate = trim((string) ($payload['last_transaction_date'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));

        if ($memberId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'member_id is required.', 422);
        }
        if (!in_array($savingType, ['individual', 'group'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'saving_type must be individual or group.', 422);
        }

        $memberStmt = $conn->prepare('SELECT member_id FROM members WHERE member_id = ? LIMIT 1');
        $memberStmt->bind_param('i', $memberId);
        $memberStmt->execute();
        if ($memberStmt->get_result()->num_rows === 0) {
            $memberStmt->close();
            JsonResponse::error('MEMBER_NOT_FOUND', 'Selected member was not found.', 422);
        }
        $memberStmt->close();

        if ($lastTransactionDate === '') {
            $lastTransactionDate = date('Y-m-d');
        }

        $stmt = $this->prepare(
            $conn,
            'INSERT INTO savings (member_id, saving_type, balance, last_transaction_date) VALUES (?, ?, ?, ?)',
            'isds',
            [$memberId, $savingType, $balance, $lastTransactionDate],
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to create savings account: ' . $error, 500);
        }
        $savingId = $stmt->insert_id;
        $stmt->close();

        // If a starting note was provided, record an opening transaction so the
        // history page can show context. The legacy add page accepts a "note"
        // but does not insert a row — we mirror the legacy controller by NOT
        // creating a transactions row here. This matches add.php behavior.
        // (No-op kept for clarity; note is intentionally not stored.)
        unset($note);

        JsonResponse::success(['saving' => $this->fetchSaving($conn, $savingId)], 201);
    }

    /** GET /api/savings/{id} — savings account with member and recent transactions. */
    public function show(Request $request): never
    {
        $savingId = $this->idOrFail($request);
        $conn = Database::connection();
        $saving = $this->fetchSaving($conn, $savingId);
        if (!$saving) {
            JsonResponse::error('NOT_FOUND', 'Savings account not found.', 404);
        }

        $txStmt = $conn->prepare('SELECT st.txn_id, st.type, st.amount, st.balance_after, st.txn_date, st.notes, '
                                . 'u.username AS processed_by_username '
                                . 'FROM savings_transactions st LEFT JOIN users u ON st.processed_by = u.user_id '
                                . 'WHERE st.saving_id = ? ORDER BY st.txn_id DESC LIMIT 25');
        $txStmt->bind_param('i', $savingId);
        $txStmt->execute();
        $transactions = $txStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $txStmt->close();

        JsonResponse::success([
            'saving' => $saving,
            'transactions' => $transactions,
        ]);
    }

    /** PUT /api/savings/{id} — update saving_type / last_transaction_date. */
    public function update(Request $request): never
    {
        $savingId = $this->idOrFail($request);
        $conn = Database::connection();
        $existing = $this->fetchSaving($conn, $savingId);
        if (!$existing) {
            JsonResponse::error('NOT_FOUND', 'Savings account not found.', 404);
        }

        $payload = $request->body();
        $savingType = trim((string) ($payload['saving_type'] ?? $existing['saving_type']));
        $lastTransactionDate = trim((string) ($payload['last_transaction_date'] ?? ''));

        if (!in_array($savingType, ['individual', 'group'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'saving_type must be individual or group.', 422);
        }

        if ($lastTransactionDate === '') {
            $lastTransactionDate = $existing['last_transaction_date'];
        }

        $stmt = $this->prepare(
            $conn,
            'UPDATE savings SET saving_type = ?, last_transaction_date = ? WHERE saving_id = ?',
            'ssi',
            [$savingType, $lastTransactionDate, $savingId],
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to update savings account: ' . $error, 500);
        }
        $stmt->close();

        JsonResponse::success(['saving' => $this->fetchSaving($conn, $savingId)]);
    }

    /** DELETE /api/savings/{id}. Refuses when transactions exist. */
    public function destroy(Request $request): never
    {
        $savingId = $this->idOrFail($request);
        $conn = Database::connection();
        if (!$this->fetchSaving($conn, $savingId)) {
            JsonResponse::error('NOT_FOUND', 'Savings account not found.', 404);
        }

        $txStmt = $conn->prepare('SELECT COUNT(*) AS t FROM savings_transactions WHERE saving_id = ?');
        $txStmt->bind_param('i', $savingId);
        $txStmt->execute();
        $txCount = (int) ($txStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $txStmt->close();
        if ($txCount > 0) {
            JsonResponse::error('SAVING_HAS_TRANSACTIONS', 'Cannot delete a savings account with transactions.', 409);
        }

        $stmt = $conn->prepare('DELETE FROM savings WHERE saving_id = ?');
        $stmt->bind_param('i', $savingId);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to delete savings account: ' . $error, 500);
        }
        $stmt->close();
        JsonResponse::success(['saving_id' => $savingId]);
    }

    /** GET /api/savings/member/{memberId} — savings accounts for one member. */
    public function byMember(Request $request): never
    {
        $memberId = (int) $request->param('memberId', 0);
        if ($memberId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'memberId is required.', 422);
        }
        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT s.saving_id, s.saving_type, s.balance, s.last_transaction_date, s.created_at '
                             . 'FROM savings s WHERE s.member_id = ? ORDER BY s.saving_id DESC');
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r['balance'];
        }

        JsonResponse::success($rows, 200, [
            'member_id' => $memberId,
            'total_balance' => $total,
            'count' => count($rows),
        ]);
    }

    /** POST /api/savings/deposits — record a deposit. */
    public function deposit(Request $request): never
    {
        $conn = Database::connection();
        $payload = $request->body();

        $savingId = (int) ($payload['saving_id'] ?? 0);
        $amount = (float) ($payload['amount'] ?? 0);
        $notes = trim((string) ($payload['notes'] ?? ''));

        if ($savingId <= 0 || $amount <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'saving_id and a positive amount are required.', 422);
        }

        $saving = $this->fetchSaving($conn, $savingId);
        if (!$saving) {
            JsonResponse::error('NOT_FOUND', 'Savings account not found.', 404);
        }

        $newBalance = (float) $saving['balance'] + $amount;
        $processedBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $conn->begin_transaction();
        try {
            $updateStmt = $conn->prepare('UPDATE savings SET balance = ?, last_transaction_date = CURDATE() WHERE saving_id = ?');
            $updateStmt->bind_param('di', $newBalance, $savingId);
            $updateStmt->execute();
            $updateStmt->close();

            $txStmt = $this->prepare(
                $conn,
                'INSERT INTO savings_transactions (saving_id, type, amount, balance_after, processed_by, notes) VALUES (?, ?, ?, ?, ?, ?)',
                'isdsis',
                [$savingId, 'deposit', $amount, $newBalance, $processedBy, $notes],
            );
            $txStmt->execute();
            $txnId = $txStmt->insert_id;
            $txStmt->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            JsonResponse::error('DB_ERROR', 'Failed to record deposit: ' . $e->getMessage(), 500);
        }

        JsonResponse::success([
            'txn_id' => $txnId,
            'saving_id' => $savingId,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $newBalance,
        ], 201);
    }

    /** POST /api/savings/withdrawals — record a withdrawal. */
    public function withdraw(Request $request): never
    {
        $conn = Database::connection();
        $payload = $request->body();

        $savingId = (int) ($payload['saving_id'] ?? 0);
        $amount = (float) ($payload['amount'] ?? 0);
        $notes = trim((string) ($payload['notes'] ?? ''));

        if ($savingId <= 0 || $amount <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'saving_id and a positive amount are required.', 422);
        }

        $saving = $this->fetchSaving($conn, $savingId);
        if (!$saving) {
            JsonResponse::error('NOT_FOUND', 'Savings account not found.', 404);
        }

        if ($amount > (float) $saving['balance']) {
            JsonResponse::error('INSUFFICIENT_BALANCE', 'Insufficient balance.', 422);
        }

        $newBalance = (float) $saving['balance'] - $amount;
        $processedBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $conn->begin_transaction();
        try {
            $updateStmt = $conn->prepare('UPDATE savings SET balance = ?, last_transaction_date = CURDATE() WHERE saving_id = ?');
            $updateStmt->bind_param('di', $newBalance, $savingId);
            $updateStmt->execute();
            $updateStmt->close();

            $txStmt = $this->prepare(
                $conn,
                'INSERT INTO savings_transactions (saving_id, type, amount, balance_after, processed_by, notes) VALUES (?, ?, ?, ?, ?, ?)',
                'isdsis',
                [$savingId, 'withdrawal', $amount, $newBalance, $processedBy, $notes],
            );
            $txStmt->execute();
            $txnId = $txStmt->insert_id;
            $txStmt->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            JsonResponse::error('DB_ERROR', 'Failed to record withdrawal: ' . $e->getMessage(), 500);
        }

        JsonResponse::success([
            'txn_id' => $txnId,
            'saving_id' => $savingId,
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_after' => $newBalance,
        ], 201);
    }

    /** GET /api/savings/transactions — paginated transaction history. */
    public function transactions(Request $request): never
    {
        $conn = Database::connection();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $offset = ($page - 1) * $perPage;
        $type = trim((string) $request->query('type', ''));

        $where = ' WHERE 1 ';
        $types = '';
        $values = [];

        if (in_array($type, ['deposit', 'withdrawal'], true)) {
            $where .= ' AND st.type = ?';
            $types .= 's';
            $values[] = $type;
        }

        $countStmt = $this->prepare(
            $conn,
            'SELECT COUNT(*) AS t FROM savings_transactions st '
            . 'LEFT JOIN savings s ON st.saving_id = s.saving_id '
            . 'LEFT JOIN members m ON s.member_id = m.member_id ' . $where,
            $types,
            $values,
        );
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $countStmt->close();

        $listSql = 'SELECT st.txn_id, st.saving_id, st.type, st.amount, st.balance_after, st.txn_date, st.notes, '
                 . 'm.full_name, m.member_code '
                 . 'FROM savings_transactions st '
                 . 'LEFT JOIN savings s ON st.saving_id = s.saving_id '
                 . 'LEFT JOIN members m ON s.member_id = m.member_id '
                 . $where
                 . ' ORDER BY st.txn_id DESC LIMIT ? OFFSET ?';
        $stmt = $this->prepare($conn, $listSql, $types . 'ii', [...$values, $perPage, $offset]);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stats = $conn->query("SELECT COUNT(*) AS total_txns, "
                            . "SUM(CASE WHEN type='deposit' THEN 1 ELSE 0 END) AS deposit_count, "
                            . "SUM(CASE WHEN type='withdrawal' THEN 1 ELSE 0 END) AS withdrawal_count, "
                            . "COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END),0) AS total_deposits, "
                            . "COALESCE(SUM(CASE WHEN type='withdrawal' THEN amount ELSE 0 END),0) AS total_withdrawals "
                            . 'FROM savings_transactions')->fetch_assoc();

        JsonResponse::success($rows, 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
            'stats' => [
                'total_txns' => (int) ($stats['total_txns'] ?? 0),
                'deposit_count' => (int) ($stats['deposit_count'] ?? 0),
                'withdrawal_count' => (int) ($stats['withdrawal_count'] ?? 0),
                'total_deposits' => (float) ($stats['total_deposits'] ?? 0),
                'total_withdrawals' => (float) ($stats['total_withdrawals'] ?? 0),
            ],
        ]);
    }

    private function idOrFail(Request $request): int
    {
        $savingId = (int) $request->param('id', 0);
        if ($savingId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Saving id is required.', 422);
        }
        return $savingId;
    }

    private function fetchSaving(mysqli $conn, int $savingId): ?array
    {
        $stmt = $conn->prepare('SELECT s.saving_id, s.member_id, s.saving_type, s.balance, s.last_transaction_date, s.created_at, '
                             . 'm.full_name, m.member_code, m.branch_id '
                             . 'FROM savings s LEFT JOIN members m ON s.member_id = m.member_id '
                             . 'WHERE s.saving_id = ? LIMIT 1');
        $stmt->bind_param('i', $savingId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
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