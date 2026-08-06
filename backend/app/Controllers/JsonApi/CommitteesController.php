<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;
use mysqli_stmt;

final class CommitteesController
{
    /**
     * GET /api/committees — list with stats and filters.
     * Mirrors backend/app/Controllers/Committees/CommitteesListController.php.
     * Filters: search, branch_id, status, meeting_day.
     */
    public function index(Request $request): never
    {
        $conn = Database::connection();

        $search = trim((string) $request->query('search', ''));
        $branchFilter = trim((string) $request->query('branch_id', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $dayFilter = trim((string) $request->query('meeting_day', ''));

        // Build dynamic WHERE for the listing query (matches PHP lines 18-46)
        $where = ' WHERE 1 ';
        $types = '';
        $values = [];

        if ($search !== '') {
            $where .= ' AND c.committee_name LIKE ?';
            $types .= 's';
            $values[] = '%' . $search . '%';
        }
        if ($branchFilter !== '') {
            $where .= ' AND c.branch_id = ?';
            $types .= 'i';
            $values[] = (int) $branchFilter;
        }
        if ($statusFilter !== '' && ($statusFilter === '0' || $statusFilter === '1')) {
            $where .= ' AND c.is_active = ?';
            $types .= 'i';
            $values[] = (int) $statusFilter;
        }
        if ($dayFilter !== '') {
            $where .= ' AND c.meeting_day = ?';
            $types .= 's';
            $values[] = $dayFilter;
        }

        // Main query mirrors CommitteesListController lines 48-61:
        // committee + branch + officer + member count.
        $listSql = 'SELECT c.committee_id, c.committee_name, c.branch_id, c.field_officer_id, '
                 . '       c.meeting_day, c.meeting_time, c.formed_date, c.is_active, c.created_at, '
                 . '       b.branch_name, '
                 . '       u.full_name AS officer_name, '
                 . '       COUNT(m.member_id) AS member_count '
                 . 'FROM committees c '
                 . 'LEFT JOIN branches b ON c.branch_id = b.branch_id '
                 . 'LEFT JOIN users u ON c.field_officer_id = u.user_id '
                 . 'LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1 '
                 . $where
                 . ' GROUP BY c.committee_id ORDER BY c.committee_id DESC';

        $stmt = $this->prepare($conn, $listSql, $types, $values);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Stats — unfiltered, same as PHP lines 71-78.
        $totalCommittees = (int) ($conn->query('SELECT COUNT(*) AS t FROM committees')->fetch_assoc()['t'] ?? 0);
        $activeCommittees = (int) ($conn->query("SELECT COUNT(*) AS t FROM committees WHERE is_active = 1")->fetch_assoc()['t'] ?? 0);
        $inactiveCommittees = (int) ($conn->query("SELECT COUNT(*) AS t FROM committees WHERE is_active = 0")->fetch_assoc()['t'] ?? 0);
        $totalMembers = (int) ($conn->query("SELECT COUNT(*) AS t FROM members WHERE is_active = 1")->fetch_assoc()['t'] ?? 0);

        // Filter dropdowns
        $branches = $conn->query('SELECT branch_id, branch_name FROM branches ORDER BY branch_name')->fetch_all(MYSQLI_ASSOC);
        $officers = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'field_officer' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

        JsonResponse::success($rows, 200, [
            'stats' => [
                'total_committees' => $totalCommittees,
                'active_committees' => $activeCommittees,
                'inactive_committees' => $inactiveCommittees,
                'total_members' => $totalMembers,
            ],
            'filters' => [
                'branches' => $branches,
                'officers' => $officers,
            ],
        ]);
    }

    /**
     * POST /api/committees — create a committee.
     * Mirrors backend/app/Controllers/Committees/CommitteeCreateController.php.
     * PHP creates with is_active=1 hardcoded; we follow the same rule.
     */
    public function store(Request $request): never
    {
        $conn = Database::connection();

        $payload = $request->body();
        $name = trim((string) ($payload['committee_name'] ?? ''));
        $branchId = $this->nullableInt($payload['branch_id'] ?? null);
        $officerId = $this->nullableInt($payload['field_officer_id'] ?? null);
        $day = trim((string) ($payload['meeting_day'] ?? ''));
        $time = trim((string) ($payload['meeting_time'] ?? ''));
        $formedDate = trim((string) ($payload['formed_date'] ?? ''));

        if ($name === '' || $branchId === null || $officerId === null || $day === '' || $time === '' || $formedDate === '') {
            JsonResponse::error(
                'VALIDATION_ERROR',
                'committee_name, branch_id, field_officer_id, meeting_day, meeting_time and formed_date are required.',
                422,
            );
        }

        // PHP CommitteeCreateController hardcodes is_active = 1.
        $sql = 'INSERT INTO committees (committee_name, branch_id, field_officer_id, meeting_day, meeting_time, formed_date, is_active) '
             . 'VALUES (?, ?, ?, ?, ?, ?, 1)';
        // Field order:
        //   1 committee_name   s
        //   2 branch_id        i
        //   3 field_officer_id i
        //   4 meeting_day      s
        //   5 meeting_time     s
        //   6 formed_date      s
        $types = 'siisss';
        $values = [$name, $branchId, $officerId, $day, $time, $formedDate];

        $stmt = $this->prepare($conn, $sql, $types, $values);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to create committee: ' . $err, 500);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        // Return the freshly-created row in the same shape as show().
        $row = $this->fetchCommitteeRow($conn, $newId);

        JsonResponse::success(['committee' => $row], 201);
    }

    /**
     * GET /api/committees/{id} — committee detail with stats and members.
     * Mirrors backend/app/Controllers/Committees/CommitteeViewController.php.
     */
    public function show(Request $request): never
    {
        $committeeId = $this->idOrFail($request);

        $conn = Database::connection();

        $committee = $this->fetchCommitteeRow($conn, $committeeId);
        if (!$committee) {
            JsonResponse::error('NOT_FOUND', 'Committee not found.', 404);
        }

        // Members list (active members on this committee).
        $membersStmt = $conn->prepare('SELECT * FROM members WHERE committee_id = ? AND is_active = 1 ORDER BY full_name');
        $membersStmt->bind_param('i', $committeeId);
        $membersStmt->execute();
        $members = $membersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $membersStmt->close();

        // Aggregate stats — savings & loans for this committee's active members.
        $statsStmt = $conn->prepare('SELECT (SELECT COALESCE(SUM(balance), 0) FROM savings s '
                                   . 'JOIN members m ON s.member_id = m.member_id '
                                   . 'WHERE m.committee_id = ?) AS total_savings, '
                                   . '(SELECT COUNT(*) FROM loans l '
                                   . 'JOIN members m ON l.member_id = m.member_id '
                                   . 'WHERE m.committee_id = ?) AS total_loans');
        $statsStmt->bind_param('ii', $committeeId, $committeeId);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        $statsStmt->close();

        JsonResponse::success([
            'committee' => $committee,
            'members' => $members,
            'stats' => [
                'total_savings' => (float) ($stats['total_savings'] ?? 0),
                'total_loans' => (int) ($stats['total_loans'] ?? 0),
            ],
        ]);
    }

    /**
     * PUT /api/committees/{id} — update a committee.
     * Mirrors backend/app/Controllers/Committees/CommitteeUpdateController.php
     * (PHP has a buggy types string 'siissiii' — we use 'siisssii' to match
     * the actual field types so date/time fields don't get truncated).
     */
    public function update(Request $request): never
    {
        $committeeId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT committee_id FROM committees WHERE committee_id = ? LIMIT 1');
        $exists->bind_param('i', $committeeId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Committee not found.', 404);
        }
        $exists->close();

        $payload = $request->body();
        $name = trim((string) ($payload['committee_name'] ?? ''));
        $branchId = $this->nullableInt($payload['branch_id'] ?? null);
        $officerId = $this->nullableInt($payload['field_officer_id'] ?? null);
        $day = trim((string) ($payload['meeting_day'] ?? ''));
        $time = trim((string) ($payload['meeting_time'] ?? ''));
        $formedDate = trim((string) ($payload['formed_date'] ?? ''));

        if ($name === '' || $branchId === null || $officerId === null || $day === '' || $time === '' || $formedDate === '') {
            JsonResponse::error(
                'VALIDATION_ERROR',
                'committee_name, branch_id, field_officer_id, meeting_day, meeting_time and formed_date are required.',
                422,
            );
        }

        $isActive = !empty($payload['is_active']) ? 1 : 0;

        $sql = 'UPDATE committees SET committee_name = ?, branch_id = ?, field_officer_id = ?, '
             . 'meeting_day = ?, meeting_time = ?, formed_date = ?, is_active = ? '
             . 'WHERE committee_id = ?';
        // Field order:
        //   1 committee_name   s
        //   2 branch_id        i
        //   3 field_officer_id i
        //   4 meeting_day      s
        //   5 meeting_time     s
        //   6 formed_date      s
        //   7 is_active        i
        //   8 committee_id (W) i
        $types = 'siisssii';
        $values = [$name, $branchId, $officerId, $day, $time, $formedDate, $isActive, $committeeId];

        $stmt = $this->prepare($conn, $sql, $types, $values);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            JsonResponse::error('DB_ERROR', 'Failed to update committee: ' . $err, 500);
        }
        $stmt->close();

        JsonResponse::success(['committee_id' => $committeeId]);
    }

    /**
     * DELETE /api/committees/{id} — delete a committee.
     * Mirrors backend/app/Controllers/Committees/CommitteeDeleteController.php.
     * PHP blindly deletes; we add a safety check to refuse if active members
     * are still assigned (caller can clear them first via removeMember).
     */
    public function destroy(Request $request): never
    {
        $committeeId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT committee_id FROM committees WHERE committee_id = ? LIMIT 1');
        $exists->bind_param('i', $committeeId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Committee not found.', 404);
        }
        $exists->close();

        // Safety check — refuse to delete a committee with active members.
        $membersStmt = $conn->prepare('SELECT COUNT(*) AS t FROM members WHERE committee_id = ? AND is_active = 1');
        $membersStmt->bind_param('i', $committeeId);
        $membersStmt->execute();
        $memberCount = (int) ($membersStmt->get_result()->fetch_assoc()['t'] ?? 0);
        $membersStmt->close();

        if ($memberCount > 0) {
            JsonResponse::error(
                'COMMITTEE_HAS_MEMBERS',
                'Cannot delete committee with active members. Remove members first.',
                409,
            );
        }

        $del = $conn->prepare('DELETE FROM committees WHERE committee_id = ?');
        $del->bind_param('i', $committeeId);
        $del->execute();
        $del->close();

        JsonResponse::success(['committee_id' => $committeeId]);
    }

    /**
     * GET /api/committees/{id}/members — current + available members.
     * Mirrors backend/app/Controllers/Committees/CommitteeAssignMemberController.php
     * lines 72-94.
     * "Available" = active members not assigned to this committee and not in the
     * unassigned bucket (committee_id = 1).
     */
    public function members(Request $request): never
    {
        $committeeId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT committee_id FROM committees WHERE committee_id = ? LIMIT 1');
        $exists->bind_param('i', $committeeId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Committee not found.', 404);
        }
        $exists->close();

        // Current members
        $currentStmt = $conn->prepare('SELECT * FROM members WHERE committee_id = ? AND is_active = 1 ORDER BY full_name');
        $currentStmt->bind_param('i', $committeeId);
        $currentStmt->execute();
        $current = $currentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $currentStmt->close();

        // Available members — active and not in the unassigned bucket (id=1)
        // or in this committee.
        $unassignedId = 1;
        $availableStmt = $conn->prepare('SELECT * FROM members '
                                       . 'WHERE is_active = 1 AND committee_id NOT IN (?, ?) '
                                       . 'ORDER BY full_name');
        $availableStmt->bind_param('ii', $unassignedId, $committeeId);
        $availableStmt->execute();
        $available = $availableStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $availableStmt->close();

        JsonResponse::success([
            'current' => $current,
            'available' => $available,
        ]);
    }

    /**
     * POST /api/committees/{id}/members — assign one or multiple members.
     * Mirrors backend/app/Controllers/Committees/CommitteeAssignMemberController.php
     * lines 44-70. Accepts either a single member_id or member_ids[] payload.
     */
    public function addMember(Request $request): never
    {
        $committeeId = $this->idOrFail($request);

        $conn = Database::connection();

        $exists = $conn->prepare('SELECT committee_id FROM committees WHERE committee_id = ? LIMIT 1');
        $exists->bind_param('i', $committeeId);
        $exists->execute();
        if (!$exists->get_result()->fetch_assoc()) {
            $exists->close();
            JsonResponse::error('NOT_FOUND', 'Committee not found.', 404);
        }
        $exists->close();

        $payload = $request->body();
        $memberIds = [];

        if (isset($payload['member_id']) && $payload['member_id'] !== '' && $payload['member_id'] !== null) {
            $memberIds[] = (int) $payload['member_id'];
        } elseif (isset($payload['member_ids']) && is_array($payload['member_ids'])) {
            foreach ($payload['member_ids'] as $mid) {
                $intVal = (int) $mid;
                if ($intVal > 0) $memberIds[] = $intVal;
            }
        }

        if ($memberIds === []) {
            JsonResponse::error('VALIDATION_ERROR', 'member_id or member_ids is required.', 422);
        }

        $assigned = 0;
        $update = $conn->prepare('UPDATE members SET committee_id = ? WHERE member_id = ?');
        foreach ($memberIds as $memberId) {
            $update->bind_param('ii', $committeeId, $memberId);
            if ($update->execute()) {
                $assigned++;
            }
        }
        $update->close();

        JsonResponse::success([
            'committee_id' => $committeeId,
            'assigned' => $assigned,
        ]);
    }

    /**
     * DELETE /api/committees/{id}/members/{memberId} — remove a member.
     * Mirrors backend/app/Controllers/Committees/CommitteeAssignMemberController.php
     * lines 28-42: set committee_id back to the unassigned bucket (id=1).
     */
    public function removeMember(Request $request): never
    {
        $committeeId = $this->idOrFail($request);
        $memberId = (int) $request->param('memberId', 0);
        if ($memberId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Member id is required.', 422);
        }

        $conn = Database::connection();

        $unassignedId = 1;
        // Only update if the member is currently on this committee — guards
        // against accidentally reassigning a member who belongs elsewhere.
        $stmt = $conn->prepare('UPDATE members SET committee_id = ? WHERE member_id = ? AND committee_id = ?');
        $stmt->bind_param('iii', $unassignedId, $memberId, $committeeId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        JsonResponse::success([
            'committee_id' => $committeeId,
            'member_id' => $memberId,
            'removed' => $affected > 0,
        ]);
    }

    private function idOrFail(Request $request): int
    {
        $committeeId = (int) $request->param('id', 0);
        if ($committeeId <= 0) {
            JsonResponse::error('VALIDATION_ERROR', 'Committee id is required.', 422);
        }
        return $committeeId;
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

    /**
     * Shared committee row loader with branch + officer joins.
     */
    private function fetchCommitteeRow(mysqli $conn, int $committeeId): ?array
    {
        $stmt = $conn->prepare('SELECT c.committee_id, c.committee_name, c.branch_id, c.field_officer_id, '
                              . 'c.meeting_day, c.meeting_time, c.formed_date, c.is_active, c.created_at, '
                              . 'b.branch_name, '
                              . 'u.full_name AS officer_name, '
                              . '(SELECT COUNT(*) FROM members m WHERE m.committee_id = c.committee_id AND m.is_active = 1) AS member_count '
                              . 'FROM committees c '
                              . 'LEFT JOIN branches b ON c.branch_id = b.branch_id '
                              . 'LEFT JOIN users u ON c.field_officer_id = u.user_id '
                              . 'WHERE c.committee_id = ? LIMIT 1');
        $stmt->bind_param('i', $committeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}