<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

final class LoansController
{
    /**
     * GET /api/loans — paginated list with search/status/member filters and stats.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $memberId = (int) $request->query('member_id', 0);

        $where = ' WHERE 1 ';
        $types = '';
        $values = [];

        if ($search !== '') {
            $where .= ' AND (l.loan_code LIKE ? OR m.full_name LIKE ? OR m.member_code LIKE ? OR l.purpose LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'ssss';
            array_push($values, $like, $like, $like, $like);
        }
        if (in_array($status, ['active', 'closed', 'overdue', 'written_off'], true)) {
            $where .= ' AND l.status = ?';
            $types .= 's';
            $values[] = $status;
        }
        if ($memberId > 0) {
            $where .= ' AND l.member_id = ?';
            $types .= 'i';
            $values[] = $memberId;
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

        $listSql = 'SELECT l.*, m.full_name, m.member_code, m.phone, b.branch_name '
                 . 'FROM loans l '
                 . 'LEFT JOIN members m ON l.member_id = m.member_id '
                 . 'LEFT JOIN branches b ON l.branch_id = b.branch_id '
                 . $where
                 . ' ORDER BY l.loan_id DESC LIMIT ? OFFSET ?';
        $stmt = $this->prepare($conn, $listSql, $types . 'ii', [...$values, $perPage, $offset]);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // PHP list stats are unfiltered.
        $stats = $conn->query("SELECT COUNT(*) AS total_loans, "
                            . "SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active_loans, "
                            . "SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) AS overdue_loans, "
                            . "SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) AS closed_loans, "
                            . "COALESCE(SUM(total_payable),0) AS total_portfolio, "
                            . "COALESCE(SUM(total_paid),0) AS total_paid, "
                            . "SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS recent_loans "
                            . 'FROM loans')->fetch_assoc();

        $portfolio = (float) ($stats['total_portfolio'] ?? 0);
        $paid = (float) ($stats['total_paid'] ?? 0);
        $members = $conn->query('SELECT member_id, member_code, full_name, branch_id FROM members ORDER BY full_name')->fetch_all(MYSQLI_ASSOC);

        JsonResponse::success($rows, 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
            'stats' => [
                'total_loans' => (int) ($stats['total_loans'] ?? 0),
                'active_loans' => (int) ($stats['active_loans'] ?? 0),
                'overdue_loans' => (int) ($stats['overdue_loans'] ?? 0),
                'closed_loans' => (int) ($stats['closed_loans'] ?? 0),
                'total_portfolio' => $portfolio,
                'total_paid' => $paid,
                'collection_rate' => $portfolio > 0 ? ($paid / $portfolio) * 100 : 0,
                'recent_loans' => (int) ($stats['recent_loans'] ?? 0),
            ],
            'filters' => ['members' => $members],
        ]);
    }

    /** POST /api/loans — create a loan using the same calculations as add.php. */
    public function store(Request $request): never
    {
        $conn = Database::connection();
        $payload = $request->body();

        $loanCode = trim((string) ($payload['loan_code'] ?? ''));
        $memberId = (int) ($payload['member_id'] ?? 0);
        $principal = (float) ($payload['principal_amount'] ?? 0);
        $rate = (float) ($payload['interest_rate'] ?? 0);
        $interestType = trim((string) ($payload['interest_type'] ?? 'flat'));
        $term = (int) ($payload['loan_term_months'] ?? 0);
        $installmentType = trim((string) ($payload['installment_type'] ?? 'monthly'));
        $disbursementDate = trim((string) ($payload['disbursement_date'] ?? ''));
        $firstInstallmentDate = trim((string) ($payload['first_installment_date'] ?? ''));
        $purpose = trim((string) ($payload['purpose'] ?? ''));

        $this->validateLoan($loanCode, $memberId, $principal, $rate, $interestType, $term, $installmentType, $disbursementDate, $firstInstallmentDate);

        $memberStmt = $conn->prepare('SELECT branch_id FROM members WHERE member_id = ? LIMIT 1');
        $memberStmt->bind_param('i', $memberId);
        $memberStmt->execute();
        $member = $memberStmt->get_result()->fetch_assoc();
        $memberStmt->close();
        if (!$member) {
            JsonResponse::error('MEMBER_NOT_FOUND', 'Selected member was not found.', 422);
        }
        $branchId = (int) $member['branch_id'];

        [$totalPayable, $installmentAmount, $maturityDate] = $this->calculate($principal, $rate, $term, $disbursementDate);

        $sql = 'INSERT INTO loans (loan_code, member_id, branch_id, principal_amount, interest_rate, interest_type, '
             . 'loan_term_months, installment_type, installment_amount, total_payable, total_paid, disbursement_date, '
             . 'first_installment_date, maturity_date, status, purpose) '
             . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 'active', ?)";
        $stmt = $this->prepare($conn, $sql, 'siiddsisddssss', [
            $loanCode, $memberId, $branchId, $principal, $rate, $interestType,
            $term, $installmentType, $installmentAmount, $totalPayable,
            $disbursementDate, $firstInstallmentDate, $maturityDate, $purpose,
        ]);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to create loan: ' . $error, 500);
        }
        $loanId = $stmt->insert_id;
        $stmt->close();

        JsonResponse::success(['loan' => $this->fetchLoan($conn, $loanId)], 201);
    }

    /** GET /api/loans/{id} — loan, member, payments and installments. */
    public function show(Request $request): never
    {
        $loanId = $this->idOrFail($request);
        $conn = Database::connection();
        $loan = $this->fetchLoan($conn, $loanId);
        if (!$loan) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }

        $paymentsStmt = $conn->prepare('SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY payment_date DESC, payment_id DESC');
        $paymentsStmt->bind_param('i', $loanId);
        $paymentsStmt->execute();
        $payments = $paymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $paymentsStmt->close();

        $installments = [];
        // The current DB may not have generated installments for every loan.
        // Keep this optional so the detail endpoint still works for legacy data.
        $check = $conn->query("SHOW TABLES LIKE 'installments'");
        if ($check && $check->num_rows > 0) {
            $instStmt = $conn->prepare('SELECT * FROM installments WHERE loan_id = ? ORDER BY installment_no ASC');
            $instStmt->bind_param('i', $loanId);
            $instStmt->execute();
            $installments = $instStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $instStmt->close();
        }

        JsonResponse::success([
            'loan' => $loan,
            'payments' => $payments,
            'installments' => $installments,
            'summary' => [
                'remaining_balance' => max(0, (float) $loan['total_payable'] - (float) $loan['total_paid']),
                'payment_count' => count($payments),
            ],
        ]);
    }

    /** PUT /api/loans/{id} — edit fields shown in edit.php. */
    public function update(Request $request): never
    {
        $loanId = $this->idOrFail($request);
        $conn = Database::connection();
        $existing = $this->fetchLoan($conn, $loanId);
        if (!$existing) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }

        $payload = $request->body();
        $loanCode = trim((string) ($payload['loan_code'] ?? ''));
        $principal = (float) ($payload['principal_amount'] ?? 0);
        $rate = (float) ($payload['interest_rate'] ?? 0);
        $interestType = trim((string) ($payload['interest_type'] ?? 'flat'));
        $term = (int) ($payload['loan_term_months'] ?? 0);
        $installmentType = trim((string) ($payload['installment_type'] ?? 'monthly'));
        $status = trim((string) ($payload['status'] ?? 'active'));
        $purpose = trim((string) ($payload['purpose'] ?? ''));

        $this->validateLoan(
            $loanCode,
            (int) $existing['member_id'],
            $principal,
            $rate,
            $interestType,
            $term,
            $installmentType,
            (string) $existing['disbursement_date'],
            (string) $existing['first_installment_date'],
        );
        if (!in_array($status, ['active', 'closed', 'overdue', 'written_off'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'Invalid loan status.', 422);
        }

        [$totalPayable, $installmentAmount] = $this->calculate($principal, $rate, $term, (string) $existing['disbursement_date']);

        $stmt = $this->prepare(
            $conn,
            'UPDATE loans SET loan_code=?, principal_amount=?, interest_rate=?, interest_type=?, loan_term_months=?, '
            . 'installment_type=?, status=?, purpose=?, total_payable=?, installment_amount=? WHERE loan_id=?',
            'sddsisssddi',
            [$loanCode, $principal, $rate, $interestType, $term, $installmentType, $status, $purpose, $totalPayable, $installmentAmount, $loanId],
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to update loan: ' . $error, 500);
        }
        $stmt->close();

        JsonResponse::success(['loan' => $this->fetchLoan($conn, $loanId)]);
    }

    /** DELETE /api/loans/{id}. */
    public function destroy(Request $request): never
    {
        $loanId = $this->idOrFail($request);
        $conn = Database::connection();
        if (!$this->fetchLoan($conn, $loanId)) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }

        $paymentsStmt = $conn->prepare('SELECT COUNT(*) AS t FROM loan_payments WHERE loan_id = ?');
        $paymentsStmt->bind_param('i', $loanId);
        $paymentsStmt->execute();
        $paymentCount = (int) ($paymentsStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $paymentsStmt->close();
        if ($paymentCount > 0) {
            JsonResponse::error('LOAN_HAS_PAYMENTS', 'Cannot delete a loan with payment records.', 409);
        }

        $stmt = $conn->prepare('DELETE FROM loans WHERE loan_id = ?');
        $stmt->bind_param('i', $loanId);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to delete loan: ' . $error, 500);
        }
        $stmt->close();
        JsonResponse::success(['loan_id' => $loanId]);
    }

    /** POST /api/loans/{id}/status — focused status update contract. */
    public function updateStatus(Request $request): never
    {
        $loanId = $this->idOrFail($request);
        $status = trim((string) ($request->body()['status'] ?? ''));
        if (!in_array($status, ['active', 'closed', 'overdue', 'written_off'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'status must be active, closed, overdue or written_off.', 422);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('UPDATE loans SET status = ? WHERE loan_id = ?');
        $stmt->bind_param('si', $status, $loanId);
        $stmt->execute();
        if ($stmt->affected_rows === 0 && !$this->fetchLoan($conn, $loanId)) {
            $stmt->close();
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }
        $stmt->close();
        JsonResponse::success(['loan_id' => $loanId, 'status' => $status]);
    }

    /**
     * POST /api/loans/{id}/payments — record a loan payment.
     * The `loan_payments` schema has columns loan_id, member_id, amount,
     * payment_date, note (note column stores the receipt/reference text and
     * any payment_method label).
     */
    public function recordPayment(Request $request): never
    {
        $loanId = $this->idOrFail($request);
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
        $loan = $this->fetchLoan($conn, $loanId);
        if (!$loan) {
            JsonResponse::error('NOT_FOUND', 'Loan not found.', 404);
        }

        $memberId = (int) $loan['member_id'];

        $stmt = $this->prepare(
            $conn,
            'INSERT INTO loan_payments (loan_id, member_id, amount, payment_date, note) VALUES (?, ?, ?, ?, ?)',
            'iidss',
            [$loanId, $memberId, $amount, $paymentDate, $note],
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to record payment: ' . $error, 500);
        }
        $paymentId = $stmt->insert_id;
        $stmt->close();

        // Recalculate loans.total_paid from the sum of all payments (matches
        // the PHP controllers' safe re-calc pattern).
        $recalcStmt = $conn->prepare('SELECT COALESCE(SUM(amount), 0) AS t FROM loan_payments WHERE loan_id = ?');
        $recalcStmt->bind_param('i', $loanId);
        $recalcStmt->execute();
        $total = (float) ($recalcStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $recalcStmt->close();

        $updateStmt = $conn->prepare('UPDATE loans SET total_paid = ? WHERE loan_id = ?');
        $updateStmt->bind_param('di', $total, $loanId);
        $updateStmt->execute();
        $updateStmt->close();

        JsonResponse::success([
            'payment_id' => $paymentId,
            'loan_id' => $loanId,
            'amount' => $amount,
            'total_paid' => $total,
            'remaining_balance' => max(0, (float) $loan['total_payable'] - $total),
        ], 201);
    }

    private function validateLoan(
        string $loanCode,
        int $memberId,
        float $principal,
        float $rate,
        string $interestType,
        int $term,
        string $installmentType,
        string $disbursementDate,
        string $firstInstallmentDate,
    ): void {
        if ($loanCode === '' || $memberId <= 0 || $principal <= 0 || $rate < 0 || $term <= 0 || $disbursementDate === '' || $firstInstallmentDate === '') {
            JsonResponse::error('VALIDATION_ERROR', 'loan_code, member_id, principal_amount, interest_rate, loan_term_months, disbursement_date and first_installment_date are required.', 422);
        }
        if (!in_array($interestType, ['flat', 'reducing_balance'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'Invalid interest type.', 422);
        }
        if (!in_array($installmentType, ['monthly', 'weekly'], true)) {
            JsonResponse::error('VALIDATION_ERROR', 'Invalid installment type.', 422);
        }
    }

    private function calculate(float $principal, float $rate, int $term, string $disbursementDate): array
    {
        // The PHP create/edit controllers apply the same simple formula to both
        // configured interest types; preserve that behavior exactly.
        $totalPayable = $principal + ($principal * $rate / 100);
        $installmentAmount = $term > 0 ? $totalPayable / $term : 0;
        $maturityDate = date('Y-m-d', strtotime($disbursementDate . ' +' . $term . ' months'));
        return [$totalPayable, $installmentAmount, $maturityDate];
    }

    private function idOrFail(Request $request): int
    {
        $loanId = (int) $request->param('id', 0);
        if ($loanId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Loan id is required.', 422);
        }
        return $loanId;
    }

    private function fetchLoan(mysqli $conn, int $loanId): ?array
    {
        $stmt = $conn->prepare('SELECT l.*, m.full_name, m.member_code, m.phone, m.address, b.branch_name '
                             . 'FROM loans l LEFT JOIN members m ON l.member_id = m.member_id '
                             . 'LEFT JOIN branches b ON l.branch_id = b.branch_id '
                             . 'WHERE l.loan_id = ? LIMIT 1');
        $stmt->bind_param('i', $loanId);
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
