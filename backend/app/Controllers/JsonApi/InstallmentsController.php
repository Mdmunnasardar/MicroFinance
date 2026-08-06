<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

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

    public function index(Request $request): never
    {
        $conn = Database::connection();
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
            . ' ORDER BY i.installment_no ASC, i.installment_id ASC LIMIT ? OFFSET ?',
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

    public function store(Request $request): never
    {
        JsonResponse::notImplemented('POST /api/installments');
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

    public function update(Request $request): never
    {
        $id = $this->id($request);
        if ($id === null) {
            JsonResponse::error('INVALID_ID', 'A valid installment id is required.', 400);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT due_amount, paid_amount FROM installments WHERE installment_id = ? LIMIT 1');
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
        $paidAmount = (float) $paidAmountRaw;
        $dueAmount = (float) $current['due_amount'];
        if ($paidAmount < 0 || $paidAmount > $dueAmount) {
            JsonResponse::error('VALIDATION_ERROR', 'Paid amount must be between zero and the due amount.', 422, ['paidAmount' => 'Must be between 0 and ' . $dueAmount . '.']);
        }

        $status = $paidAmount >= $dueAmount ? 'paid' : 'pending';
        $paidDateRaw = $status === 'paid'
            ? ($request->pickBody(['paidDate', 'paid_date']) ?? date('Y-m-d'))
            : null;
        $paidDate = ($paidDateRaw === null || $paidDateRaw === '') ? null : $paidDateRaw;
        if ($paidDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $paidDate)) {
            JsonResponse::error('VALIDATION_ERROR', 'Paid date must use YYYY-MM-DD format.', 422, ['paidDate' => 'Invalid date.']);
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

        $stmt = $conn->prepare('UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, collected_by = ?, notes = ? WHERE installment_id = ?');
        $stmt->bind_param('dssisi', $paidAmount, $status, $paidDate, $collectorId, $notes, $id);
        $stmt->execute();
        $stmt->close();

        $this->show($request);
    }

    public function destroy(Request $request): never
    {
        $id = $this->id($request);
        if ($id === null) {
            JsonResponse::error('INVALID_ID', 'A valid installment id is required.', 400);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT paid_amount FROM installments WHERE installment_id = ? LIMIT 1');
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

        $stmt = $conn->prepare('DELETE FROM installments WHERE installment_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        JsonResponse::success(['id' => $id, 'deleted' => true]);
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
        return [
            'id' => (int) $row['installment_id'],
            'installmentNo' => (int) $row['installment_no'],
            'loanId' => (int) $row['loan_id'],
            'dueDate' => $row['due_date'] ?? null,
            'dueAmount' => $due,
            'paidAmount' => $paid,
            'balance' => max(0, $due - $paid),
            'lateFine' => $lateFine,
            'status' => (string) $row['status'],
            'paidDate' => $row['paid_date'],
            'notes' => $notes,
            'collector' => $collectorId === null ? null : [
                'id' => $collectorId,
                'name' => $collectorName,
                'username' => $collectorUsername,
            ],
            'loan' => ['id' => (int) $row['loan_id'], 'code' => $row['loan_code']],
            'member' => ['id' => (int) ($row['member_id'] ?? 0), 'name' => $row['full_name'], 'code' => $row['member_code']],
        ];
    }
}