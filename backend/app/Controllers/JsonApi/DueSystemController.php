<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

final class DueSystemController
{
    /**
     * GET /api/due-system — Due List with the same columns and status
     * bucketing as due_system/index.php (Paid / Near Close / Due).
     * Adds stats and a `meta` payload mirroring the rest of the modules.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $request->query('search', ''));

        $where = ' WHERE 1 ';
        $types = '';
        $values = [];
        if ($search !== '') {
            $where .= ' AND (l.loan_code LIKE ? OR m.full_name LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'ss';
            array_push($values, $like, $like);
        }

        $countStmt = $this->prepare(
            $conn,
            'SELECT COUNT(*) AS t FROM loans l LEFT JOIN members m ON l.member_id = m.member_id ' . $where,
            $types,
            $values,
        );
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $countStmt->close();

        $sql = 'SELECT l.loan_id, l.loan_code, l.principal_amount, l.total_paid, l.status, '
             . 'm.full_name, m.member_code, '
             . '(l.principal_amount - l.total_paid) AS remaining '
             . 'FROM loans l LEFT JOIN members m ON l.member_id = m.member_id '
             . $where
             . ' ORDER BY remaining DESC LIMIT ? OFFSET ?';
        $stmt = $this->prepare($conn, $sql, $types . 'ii', [...$values, $perPage, $offset]);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stats = $conn->query("SELECT COUNT(*) AS total_loans, "
                            . "SUM(CASE WHEN (principal_amount - total_paid) <= 0 THEN 1 ELSE 0 END) AS paid_count, "
                            . "SUM(CASE WHEN (principal_amount - total_paid) > 0 AND (principal_amount - total_paid) < 5000 THEN 1 ELSE 0 END) AS near_close_count, "
                            . "SUM(CASE WHEN (principal_amount - total_paid) >= 5000 THEN 1 ELSE 0 END) AS due_count, "
                            . "COALESCE(SUM(principal_amount - total_paid),0) AS total_remaining "
                            . 'FROM loans')->fetch_assoc();

        JsonResponse::success($this->decorate($rows), 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
            'stats' => [
                'total_loans' => (int) ($stats['total_loans'] ?? 0),
                'paid_count' => (int) ($stats['paid_count'] ?? 0),
                'near_close_count' => (int) ($stats['near_close_count'] ?? 0),
                'due_count' => (int) ($stats['due_count'] ?? 0),
                'total_remaining' => (float) ($stats['total_remaining'] ?? 0),
            ],
        ]);
    }

    /** GET /api/due-system/{id} — single loan remaining. */
    public function show(Request $request): never
    {
        $loanId = (int) $request->param('id', 0);
        if ($loanId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Loan id is required.', 422);
        }
        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT l.loan_id, l.loan_code, l.principal_amount, l.total_paid, l.status, '
                             . 'm.full_name, m.member_code, '
                             . '(l.principal_amount - l.total_paid) AS remaining '
                             . 'FROM loans l LEFT JOIN members m ON l.member_id = m.member_id '
                             . 'WHERE l.loan_id = ? LIMIT 1');
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }
        JsonResponse::success($this->decorate([$row])[0]);
    }

    /**
     * POST /api/due-system/{id}/collect — record a payment against a due loan.
     * Mirrors LoansController::recordPayment so the Due System page can post
     * a collection without leaving its own module URL.
     */
    public function collect(Request $request): never
    {
        $loanId = (int) $request->param('id', 0);
        if ($loanId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Loan id is required.', 422);
        }

        $payload = $request->body();
        $amount = (float) ($payload['amount'] ?? 0);
        $paymentDate = trim((string) ($payload['payment_date'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));

        if ($amount <= 0 || $paymentDate === '') {
            JsonResponse::error('VALIDATION_ERROR', 'amount and payment_date are required.', 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
            JsonResponse::error('VALIDATION_ERROR', 'payment_date must use YYYY-MM-DD format.', 422);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT loan_id, member_id, total_payable, total_paid FROM loans WHERE loan_id = ? LIMIT 1');
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$loan) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }

        $memberId = (int) $loan['member_id'];

        $conn->begin_transaction();
        try {
            $insertStmt = $this->prepare(
                $conn,
                'INSERT INTO loan_payments (loan_id, member_id, amount, payment_date, note) VALUES (?, ?, ?, ?, ?)',
                'iidss',
                [$loanId, $memberId, $amount, $paymentDate, $note],
            );
            $insertStmt->execute();
            $paymentId = $insertStmt->insert_id;
            $insertStmt->close();

            $recalcStmt = $conn->prepare('SELECT COALESCE(SUM(amount), 0) AS t FROM loan_payments WHERE loan_id = ?');
            $recalcStmt->bind_param('i', $loanId);
            $recalcStmt->execute();
            $total = (float) ($recalcStmt->get_result()->fetch_assoc()['t'] ?? 0);
            $recalcStmt->close();

            $updateStmt = $conn->prepare('UPDATE loans SET total_paid = ? WHERE loan_id = ?');
            $updateStmt->bind_param('di', $total, $loanId);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            JsonResponse::error('DB_ERROR', 'Failed to record collection: ' . $e->getMessage(), 500);
        }

        JsonResponse::success([
            'payment_id' => $paymentId,
            'loan_id' => $loanId,
            'amount' => $amount,
            'total_paid' => $total,
            'remaining_balance' => max(0, (float) $loan['total_payable'] - $total),
        ], 201);
    }

    /**
     * GET /api/due-system/overdue — active loans with remaining > 0. Mirrors
     * due_system/overdue.php.
     */
    public function overdue(Request $request): never
    {
        $conn = Database::connection();

        $sql = 'SELECT l.loan_id, l.loan_code, l.principal_amount, l.total_paid, l.status, '
             . 'm.full_name, m.member_code, '
             . '(l.principal_amount - l.total_paid) AS remaining '
             . 'FROM loans l LEFT JOIN members m ON l.member_id = m.member_id '
             . "WHERE (l.principal_amount - l.total_paid) > 0 AND l.status = 'active' "
             . 'ORDER BY remaining DESC';
        $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r['remaining'];
        }

        JsonResponse::success($rows, 200, [
            'count' => count($rows),
            'total_overdue' => $total,
        ]);
    }

    /**
     * GET /api/due-system/report — summary report. Mirrors due_system/report.php.
     */
    public function report(Request $request): never
    {
        $conn = Database::connection();
        $row = $conn->query("SELECT COALESCE(SUM(principal_amount),0) AS total, "
                          . "COALESCE(SUM(total_paid),0) AS paid "
                          . 'FROM loans')->fetch_assoc();
        $total = (float) ($row['total'] ?? 0);
        $paid = (float) ($row['paid'] ?? 0);
        $remaining = $total - $paid;

        JsonResponse::success([
            'total_loan' => $total,
            'total_paid' => $paid,
            'remaining_due' => max(0, $remaining),
        ]);
    }

    /**
     * Decorate rows with the same status bucket as due_system/index.php:
     *   remaining <= 0 → "paid"
     *   remaining < 5000 → "near_close"
     *   else → "due"
     */
    private function decorate(array $rows): array
    {
        foreach ($rows as &$row) {
            $remaining = (float) $row['remaining'];
            if ($remaining <= 0) {
                $row['due_status'] = 'paid';
            } elseif ($remaining < 5000) {
                $row['due_status'] = 'near_close';
            } else {
                $row['due_status'] = 'due';
            }
        }
        return $rows;
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