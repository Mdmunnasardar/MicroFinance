<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;
use DateTime;

final class InstallmentsController
{
    private const INSTALLMENT_SELECT =
        'i.installment_id, i.installment_no, i.loan_id, i.due_date, i.due_amount, '
        . 'i.paid_amount, i.status, i.paid_date, i.late_fine, i.collected_by, i.notes, '
        . 'l.loan_code, l.member_id, m.full_name, m.member_code, '
        . 'u.full_name AS collector_name, u.username AS collector_username';
    
    private const INSTALLMENT_JOIN =
        ' FROM installments i '
        . 'LEFT JOIN loans l ON i.loan_id = l.loan_id '
        . 'LEFT JOIN members m ON l.member_id = m.member_id '
        . 'LEFT JOIN users u ON i.collected_by = u.user_id';

    /**
     * GET /api/installments - List all installments with auto-generation
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();
        
        // Check if member_id is provided - auto-generate missing installments
        $memberId = (int) $request->query('member_id', 0);
        
        if ($memberId > 0) {
            // Check if member exists
            $memberCheck = $conn->prepare('SELECT member_id FROM members WHERE member_id = ? LIMIT 1');
            $memberCheck->bind_param('i', $memberId);
            $memberCheck->execute();
            $memberResult = $memberCheck->get_result()->fetch_assoc();
            $memberCheck->close();
            
            if ($memberResult) {
                // Auto-generate missing installments for this member
                $this->autoGenerateMemberInstallments($conn, $memberId);
            }
        }
        
        // Build filters and get installments
        [$where, $types, $values] = $this->filters($request);

        $countStmt = $this->prepare($conn, 'SELECT COUNT(*) AS total' . self::INSTALLMENT_JOIN . $where, $types, $values);
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $countStmt->close();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $offset = ($page - 1) * $perPage;
        $listTypes = $types . 'ii';
        $listValues = [...$values, $perPage, $offset];
        
        $stmt = $this->prepare(
            $conn,
            'SELECT ' . self::INSTALLMENT_SELECT . self::INSTALLMENT_JOIN . $where
            . ' ORDER BY i.due_date ASC, i.installment_no ASC LIMIT ? OFFSET ?',
            $listTypes,
            $listValues,
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $this->mapRow($row);
        }
        $stmt->close();

        JsonResponse::success($items, 200, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ]);
    }

    /**
     * Auto-generate missing installments for a member
     */
    private function autoGenerateMemberInstallments(mysqli $conn, int $memberId): void
    {
        // Get active loans without installments
        $stmt = $conn->prepare('
            SELECT l.loan_id, l.total_payable, l.loan_term_months, 
                   l.first_installment_date, l.installment_type,
                   l.loan_code, l.status
            FROM loans l
            LEFT JOIN installments i ON l.loan_id = i.loan_id
            WHERE l.member_id = ? 
            AND l.status = "active"
            AND i.installment_id IS NULL
        ');
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($loans as $loan) {
            $this->generateInstallments(
                $conn,
                (int) $loan['loan_id'],
                (float) $loan['total_payable'],
                (int) $loan['loan_term_months'],
                $loan['first_installment_date'],
                $loan['installment_type'] ?? 'monthly'
            );
        }
    }

    public function today(Request $request): never
    {
        $conn = Database::connection();
        $today = date('Y-m-d');
        
        [$where, $types, $values] = $this->filters($request);
        
        $dateFilter = ' DATE(i.due_date) = ? AND i.status != "paid" ';
        
        if ($where) {
            $where = $where . ' AND ' . $dateFilter;
            $types .= 's';
            $values[] = $today;
        } else {
            $where = ' WHERE ' . $dateFilter;
            $types = 's';
            $values = [$today];
        }
        
        $stmt = $this->prepare(
            $conn,
            'SELECT ' . self::INSTALLMENT_SELECT . self::INSTALLMENT_JOIN . $where
            . ' ORDER BY i.due_date ASC, i.installment_no ASC',
            $types,
            $values
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $this->mapRow($row);
        }
        $stmt->close();
        
        JsonResponse::success($items);
    }

    public function overdue(Request $request): never
    {
        $conn = Database::connection();
        $today = date('Y-m-d');
        
        [$where, $types, $values] = $this->filters($request);
        
        $dateFilter = ' DATE(i.due_date) < ? AND i.status != "paid" ';
        
        if ($where) {
            $where = $where . ' AND ' . $dateFilter;
            $types .= 's';
            $values[] = $today;
        } else {
            $where = ' WHERE ' . $dateFilter;
            $types = 's';
            $values = [$today];
        }
        
        $stmt = $this->prepare(
            $conn,
            'SELECT ' . self::INSTALLMENT_SELECT . self::INSTALLMENT_JOIN . $where
            . ' ORDER BY i.due_date ASC, i.installment_no ASC',
            $types,
            $values
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $this->mapRow($row);
        }
        $stmt->close();
        
        JsonResponse::success($items);
    }

    public function show(Request $request): never
    {
        $id = $this->id($request);
        if ($id === null) {
            JsonResponse::error('INVALID_ID', 'A valid installment id is required.', 400);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT ' . self::INSTALLMENT_SELECT . self::INSTALLMENT_JOIN . ' WHERE i.installment_id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            JsonResponse::error('NOT_FOUND', 'Installment not found.', 404);
        }

        JsonResponse::success($this->mapRow($row));
    }

    /**
     * PUT /api/installments/{id} - COLLECT PAYMENT (FIXED)
     */
    public function update(Request $request): never
    {
        $id = $this->id($request);
        if ($id === null) {
            JsonResponse::error('INVALID_ID', 'A valid installment id is required.', 400);
        }

        $conn = Database::connection();
        
        $stmt = $conn->prepare('
            SELECT i.due_amount, i.paid_amount, i.due_date, i.loan_id, 
                   l.total_payable, l.total_paid AS loan_paid
            FROM installments i
            LEFT JOIN loans l ON i.loan_id = l.loan_id
            WHERE i.installment_id = ? LIMIT 1
        ');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$current) {
            JsonResponse::error('NOT_FOUND', 'Installment not found.', 404);
        }

        $paidAmountRaw = $request->pickBody(['paidAmount', 'paid_amount']);
        if ($paidAmountRaw === null) {
            JsonResponse::error('VALIDATION_ERROR', 'Paid amount is required.', 422, ['paidAmount' => 'Required.']);
        }
        
        $newPayment = (float) $paidAmountRaw;
        $currentPaid = (float) $current['paid_amount'];
        $dueAmount = (float) $current['due_amount'];
        $remaining = $dueAmount - $currentPaid;

        if ($newPayment <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Paid amount must be greater than zero.', 422, ['paidAmount' => 'Must be greater than zero.']);
        }
        
        if ($newPayment > $remaining) {
            JsonResponse::error('VALIDATION_ERROR', 
                'Payment amount cannot exceed remaining balance.', 
                422, 
                ['paidAmount' => 'Maximum amount is ' . number_format($remaining, 2) . '.']
            );
        }

        $newTotalPaid = $currentPaid + $newPayment;

        if ($newTotalPaid >= $dueAmount) {
            $status = 'paid';
        } elseif ($newTotalPaid > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }

        $paidDateRaw = $request->pickBody(['paidDate', 'paid_date']);
        if ($paidDateRaw === null || $paidDateRaw === '') {
            $paidDate = date('Y-m-d');
        } else {
            $paidDate = $paidDateRaw;
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $paidDate)) {
            JsonResponse::error('VALIDATION_ERROR', 'Paid date must use YYYY-MM-DD format.', 422, ['paidDate' => 'Invalid date.']);
        }
        
        if (strtotime($paidDate) > strtotime(date('Y-m-d'))) {
            JsonResponse::error('VALIDATION_ERROR', 'Payment date cannot be in the future.', 422, ['paidDate' => 'Cannot be future date.']);
        }

        $collectorId = null;
        $collectorRaw = $request->pickBody(['collectedBy', 'collected_by', 'collectorId']);
        if ($collectorRaw !== null && $collectorRaw !== '' && !(is_array($collectorRaw) || is_object($collectorRaw))) {
            $collectorId = filter_var($collectorRaw, FILTER_VALIDATE_INT);
            if ($collectorId === false || $collectorId === null || $collectorId <= 0) {
                $collectorId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            }
        }
        if ($collectorId === null && isset($_SESSION['user_id'])) {
            $collectorId = (int) $_SESSION['user_id'];
        }

        $notes = null;
        $notesRaw = $request->pickBody(['notes', 'note']);
        if ($notesRaw !== null && $notesRaw !== '' && !(is_array($notesRaw) || is_object($notesRaw))) {
            $notes = trim((string) $notesRaw);
            if (strlen($notes) > 1000) {
                $notes = substr($notes, 0, 1000);
            }
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare('
                UPDATE installments 
                SET paid_amount = ?, 
                    status = ?, 
                    paid_date = ?, 
                    collected_by = ?, 
                    notes = ? 
                WHERE installment_id = ?
            ');
            $stmt->bind_param('dssisi', $newTotalPaid, $status, $paidDate, $collectorId, $notes, $id);
            $stmt->execute();
            $stmt->close();

            $loanId = (int) $current['loan_id'];
            $loanPaid = (float) ($current['loan_paid'] ?? 0);
            $newLoanPaid = $loanPaid + $newPayment;
            
            $stmt = $conn->prepare('UPDATE loans SET total_paid = ? WHERE loan_id = ?');
            $stmt->bind_param('di', $newLoanPaid, $loanId);
            $stmt->execute();
            $stmt->close();

            $this->updateLoanStatus($conn, $loanId);

            $conn->commit();

            $this->show($request);

        } catch (\Exception $e) {
            $conn->rollback();
            JsonResponse::error('PAYMENT_FAILED', 'Payment processing failed: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request): never
    {
        $id = $this->id($request);
        if ($id === null) {
            JsonResponse::error('INVALID_ID', 'A valid installment id is required.', 400);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT paid_amount, loan_id FROM installments WHERE installment_id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            JsonResponse::error('NOT_FOUND', 'Installment not found.', 404);
        }
        
        if ((float) $row['paid_amount'] > 0) {
            JsonResponse::error('INSTALLMENT_PAID', 'Paid installments cannot be deleted.', 409);
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('DELETE FROM installments WHERE installment_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            
            $loanId = (int) $row['loan_id'];
            $this->updateLoanStatus($conn, $loanId);
            
            $conn->commit();
            JsonResponse::success(['id' => $id, 'deleted' => true]);
            
        } catch (\Exception $e) {
            $conn->rollback();
            JsonResponse::error('DELETE_FAILED', 'Failed to delete installment.', 500);
        }
    }

    public function store(Request $request): never
    {
        JsonResponse::error('NOT_IMPLEMENTED', 'POST /api/installments is not implemented. Installments are generated automatically when a loan is created.', 501);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate installments for a loan (FIXED - This actually creates the installments)
     */
    private function generateInstallments(
        mysqli $conn, 
        int $loanId, 
        float $totalAmount, 
        int $installmentCount, 
        string $firstInstallmentDate,
        string $installmentType
    ): void {
        // Check if installments already exist for this loan
        $checkStmt = $conn->prepare('SELECT COUNT(*) as count FROM installments WHERE loan_id = ?');
        $checkStmt->bind_param('i', $loanId);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ((int) $result['count'] > 0) {
            // Installments already exist, don't regenerate
            return;
        }
        
        $installmentAmount = $totalAmount / $installmentCount;
        $currentDate = new DateTime($firstInstallmentDate);
        
        $stmt = $conn->prepare('
            INSERT INTO installments (
                loan_id, 
                installment_no, 
                due_date, 
                due_amount, 
                paid_amount, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, 0, "pending", NOW())
        ');
        
        for ($i = 1; $i <= $installmentCount; $i++) {
            if ($i > 1) {
                if ($installmentType === 'weekly') {
                    $currentDate->modify('+1 week');
                } else {
                    $currentDate->modify('+1 month');
                }
            }
            $dueDate = $currentDate->format('Y-m-d');
            
            if ($i === $installmentCount) {
                $totalGenerated = $installmentAmount * ($installmentCount - 1);
                $amount = round($totalAmount - $totalGenerated, 2);
            } else {
                $amount = round($installmentAmount, 2);
            }
            
            $stmt->bind_param('iisd', $loanId, $i, $dueDate, $amount);
            $stmt->execute();
        }
        
        $stmt->close();
    }

    private function updateLoanStatus(mysqli $conn, int $loanId): void
    {
        $stmt = $conn->prepare('
            SELECT status FROM installments WHERE loan_id = ?
        ');
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $allPaid = true;
        $hasInstallments = false;
        while ($row = $result->fetch_assoc()) {
            $hasInstallments = true;
            if ($row['status'] !== 'paid') {
                $allPaid = false;
                break;
            }
        }
        $stmt->close();
        
        if ($hasInstallments && $allPaid) {
            $stmt = $conn->prepare('UPDATE loans SET status = "closed" WHERE loan_id = ?');
            $stmt->bind_param('i', $loanId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function filters(Request $request): array
    {
        $clauses = [];
        $types = '';
        $values = [];
        
        $status = (string) $request->query('status', '');
        if (in_array($status, ['pending', 'paid', 'partial', 'overdue'], true)) {
            $clauses[] = 'i.status = ?';
            $types .= 's';
            $values[] = $status;
        }
        
        $loanId = filter_var($request->query('loan_id'), FILTER_VALIDATE_INT);
        if ($loanId !== false && $loanId !== null && $loanId > 0) {
            $clauses[] = 'i.loan_id = ?';
            $types .= 'i';
            $values[] = $loanId;
        }
        
        $memberId = filter_var($request->query('member_id'), FILTER_VALIDATE_INT);
        if ($memberId !== false && $memberId !== null && $memberId > 0) {
            $clauses[] = 'l.member_id = ?';
            $types .= 'i';
            $values[] = $memberId;
        }
        
        $query = trim((string) $request->query('q', ''));
        if ($query !== '') {
            $like = '%' . $query . '%';
            $clauses[] = '(l.loan_code LIKE ? OR m.full_name LIKE ? OR m.member_code LIKE ?)';
            $types .= 'sss';
            array_push($values, $like, $like, $like);
        }
        
        return [$clauses ? ' WHERE ' . implode(' AND ', $clauses) : '', $types, $values];
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

    private function id(Request $request): ?int
    {
        $id = filter_var($request->param('id'), FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }

    private function mapRow(array $row): array
    {
        $due = (float) $row['due_amount'];
        $paid = (float) $row['paid_amount'];
        $collectorId = isset($row['collected_by']) && $row['collected_by'] !== null ? (int) $row['collected_by'] : null;
        $collectorName = $row['collector_name'] ?? null;
        $collectorUsername = $row['collector_username'] ?? null;
        $notes = $row['notes'] ?? null;
        $lateFine = isset($row['late_fine']) && $row['late_fine'] !== null ? (float) $row['late_fine'] : 0.0;
        
        $status = $row['status'];
        if ($status === 'pending' && $paid > 0 && $paid < $due) {
            $status = 'partial';
        }
        if ($status !== 'paid' && $paid >= $due) {
            $status = 'paid';
        }
        
        return [
            'id' => (int) $row['installment_id'],
            'installmentNo' => (int) $row['installment_no'],
            'loanId' => (int) $row['loan_id'],
            'dueDate' => $row['due_date'] ?? null,
            'dueAmount' => $due,
            'paidAmount' => $paid,
            'balance' => max(0, $due - $paid),
            'lateFine' => $lateFine,
            'status' => $status,
            'paidDate' => $row['paid_date'],
            'notes' => $notes,
            'collector' => $collectorId === null ? null : [
                'id' => $collectorId,
                'name' => $collectorName,
                'username' => $collectorUsername,
            ],
            'loan' => [
                'id' => (int) $row['loan_id'], 
                'code' => $row['loan_code']
            ],
            'member' => [
                'id' => (int) ($row['member_id'] ?? 0), 
                'name' => $row['full_name'], 
                'code' => $row['member_code']
            ],
        ];
    }
}