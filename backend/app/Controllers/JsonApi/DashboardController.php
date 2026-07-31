<?php

declare(strict_types=1);

namespace App\Controllers\JsonApi;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;

final class DashboardController
{
    public function stats(Request $request): never
    {
        $conn = Database::connection();

        $totalMembers = (int) ($conn->query('SELECT COUNT(*) AS t FROM members')->fetch_assoc()['t'] ?? 0);
        $activeMembers = (int) ($conn->query('SELECT COUNT(*) AS t FROM members WHERE is_active = 1')->fetch_assoc()['t'] ?? 0);
        $totalLoans = (float) ($conn->query('SELECT SUM(principal_amount) AS t FROM loans')->fetch_assoc()['t'] ?? 0);
        $totalPaid = (float) ($conn->query('SELECT SUM(total_paid) AS t FROM loans')->fetch_assoc()['t'] ?? 0);
        $totalDue = $totalLoans - $totalPaid;
        $totalSavings = (float) ($conn->query('SELECT SUM(balance) AS t FROM savings')->fetch_assoc()['t'] ?? 0);
        $totalCollection = (float) ($conn->query('SELECT SUM(amount) AS t FROM loan_payments')->fetch_assoc()['t'] ?? 0);

        $overdue = (int) ($conn->query(
            "SELECT COUNT(*) AS t FROM loans WHERE status = 'active' AND maturity_date < CURDATE()"
        )->fetch_assoc()['t'] ?? 0);

        $topRow = $conn->query(
            'SELECT m.full_name, m.member_id, SUM(l.principal_amount) AS total '
            . 'FROM loans l LEFT JOIN members m ON l.member_id = m.member_id '
            . 'GROUP BY l.member_id ORDER BY total DESC LIMIT 1'
        )->fetch_assoc();
        $topBorrower = $topRow
            ? ['member_id' => (int) $topRow['member_id'], 'full_name' => $topRow['full_name'], 'total' => (float) $topRow['total']]
            : null;

        $months = [];
        $loanData = [];
        $paymentData = [];
        $chartLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i month"));
            $months[] = $m;
            $chartLabels[] = date('M y', strtotime($m . '-01'));

            $loanData[] = (float) ($conn->query(
                "SELECT SUM(principal_amount) AS t FROM loans WHERE DATE_FORMAT(created_at, '%Y-%m') = '$m'"
            )->fetch_assoc()['t'] ?? 0);

            $paymentData[] = (float) ($conn->query(
                "SELECT SUM(amount) AS t FROM loan_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$m'"
            )->fetch_assoc()['t'] ?? 0);
        }

        $recentTransactions = [];
        $txnResult = $conn->query(
            "SELECT 'Payment' AS type, 'Loan Payment' AS description, m.full_name AS member, "
            . 'lp.amount, lp.payment_date AS date, \'Completed\' AS status '
            . 'FROM loan_payments lp '
            . 'LEFT JOIN loans l ON lp.loan_id = l.loan_id '
            . 'LEFT JOIN members m ON l.member_id = m.member_id '
            . 'ORDER BY lp.payment_date DESC LIMIT 5'
        );
        while ($row = $txnResult->fetch_assoc()) {
            $recentTransactions[] = [
                'type' => $row['type'],
                'description' => $row['description'],
                'member' => $row['member'],
                'amount' => (float) $row['amount'],
                'date' => $row['date'],
                'status' => $row['status'],
            ];
        }

        $recentMembers = [];
        $memberResult = $conn->query(
            'SELECT member_id, full_name, member_code, created_at FROM members ORDER BY created_at DESC LIMIT 5'
        );
        while ($row = $memberResult->fetch_assoc()) {
            $recentMembers[] = [
                'id' => (int) $row['member_id'],
                'full_name' => $row['full_name'],
                'code' => $row['member_code'],
                'joined_at' => $row['created_at'],
            ];
        }

        $health = $totalLoans > 0 ? ($totalPaid / $totalLoans) * 100 : 0.0;

        JsonResponse::success([
            'user' => [
                'id' => (int) $_SESSION['user_id'],
                'name' => $_SESSION['name'] ?? '',
                'role' => $_SESSION['role'] ?? '',
            ],
            'stats' => [
                ['key' => 'total_members', 'label' => 'Total Members', 'value' => $totalMembers, 'icon' => 'users', 'tone' => 'blue', 'trend' => 'up', 'delta' => '12%', 'sub' => '+18 this month', 'formatted' => number_format($totalMembers)],
                ['key' => 'active_members', 'label' => 'Active Members', 'value' => $activeMembers, 'icon' => 'user-check', 'tone' => 'green', 'trend' => 'up', 'delta' => '8%', 'sub' => ($totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100) : 0) . '% of total', 'formatted' => number_format($activeMembers)],
                ['key' => 'total_loans', 'label' => 'Total Loans', 'value' => $totalLoans, 'icon' => 'money-bill-wave', 'tone' => 'purple', 'trend' => 'up', 'delta' => '12.5%', 'sub' => '+12.5% this month', 'formatted' => '$' . number_format($totalLoans)],
                ['key' => 'total_collection', 'label' => 'Total Collection', 'value' => $totalCollection, 'icon' => 'circle-dollar', 'tone' => 'teal', 'trend' => 'up', 'delta' => '15.3%', 'sub' => '+15.3% this month', 'formatted' => '$' . number_format($totalCollection)],
                ['key' => 'total_paid', 'label' => 'Total Paid', 'value' => $totalPaid, 'icon' => 'check-circle', 'tone' => 'green', 'trend' => 'up', 'delta' => '8.2%', 'sub' => ($totalLoans > 0 ? round(($totalPaid / $totalLoans) * 100) : 0) . '% of total loans', 'formatted' => '$' . number_format($totalPaid)],
                ['key' => 'total_due', 'label' => 'Total Due', 'value' => $totalDue, 'icon' => 'clock', 'tone' => 'red', 'trend' => 'down', 'delta' => '3.2%', 'sub' => ($totalLoans > 0 ? round(($totalDue / $totalLoans) * 100) : 0) . '% remaining', 'formatted' => '$' . number_format($totalDue)],
                ['key' => 'total_savings', 'label' => 'Total Savings', 'value' => $totalSavings, 'icon' => 'piggy-bank', 'tone' => 'gold', 'trend' => 'up', 'delta' => '8.2%', 'sub' => '+8.2% this month', 'formatted' => '$' . number_format($totalSavings)],
                ['key' => 'overdue_loans', 'label' => 'Overdue Loans', 'value' => $overdue, 'icon' => 'triangle-exclamation', 'tone' => 'red', 'trend' => 'danger', 'delta' => 'Alert', 'sub' => 'Requires attention', 'formatted' => (string) $overdue],
            ],
            'top_borrower' => $topBorrower,
            'health' => $health,
            'chart' => [
                'labels' => $chartLabels,
                'loan_data' => $loanData,
                'payment_data' => $paymentData,
            ],
            'recent_transactions' => $recentTransactions,
            'recent_members' => $recentMembers,
            'overdue_alert' => ['count' => $overdue],
        ]);
    }
}