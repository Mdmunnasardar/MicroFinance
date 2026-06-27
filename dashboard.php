<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

/* =========================
   CORE STATS
========================= */

$total_members = $conn->query("SELECT COUNT(*) AS t FROM members")->fetch_assoc()['t'] ?? 0;
$active_members = $conn->query("SELECT COUNT(*) AS t FROM members WHERE is_active=1")->fetch_assoc()['t'] ?? 0;
$total_loans = $conn->query("SELECT SUM(principal_amount) AS t FROM loans")->fetch_assoc()['t'] ?? 0;
$total_paid = $conn->query("SELECT SUM(total_paid) AS t FROM loans")->fetch_assoc()['t'] ?? 0;
$total_due = $total_loans - $total_paid;
$total_savings = $conn->query("SELECT SUM(balance) AS t FROM savings")->fetch_assoc()['t'] ?? 0;
$total_collection = $conn->query("SELECT SUM(amount) AS t FROM loan_payments")->fetch_assoc()['t'] ?? 0;

/* =========================
   ADVANCED ANALYTICS
========================= */

$this_month_collection = $conn->query("
SELECT SUM(amount) AS t
FROM loan_payments
WHERE MONTH(payment_date)=MONTH(CURDATE())
AND YEAR(payment_date)=YEAR(CURDATE())
")->fetch_assoc()['t'] ?? 0;

$this_month_loans = $conn->query("
SELECT SUM(principal_amount) AS t
FROM loans
WHERE MONTH(created_at)=MONTH(CURDATE())
AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['t'] ?? 0;

$overdue = $conn->query("
SELECT COUNT(*) AS t
FROM loans
WHERE status='active'
AND maturity_date < CURDATE()
")->fetch_assoc()['t'] ?? 0;

$top = $conn->query("
SELECT m.full_name, m.member_id, SUM(l.principal_amount) AS total
FROM loans l
LEFT JOIN members m ON l.member_id=m.member_id
GROUP BY l.member_id
ORDER BY total DESC
LIMIT 1
")->fetch_assoc();

/* =========================
   LOAN HEALTH
========================= */

$health = ($total_loans > 0) ? ($total_paid / $total_loans) * 100 : 0;

/* =========================
   MONTHLY GRAPH DATA
========================= */

$months = [];
$loanData = [];
$payData = [];

for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i month"));
    $months[] = $m;
    
    $loan = $conn->query("
        SELECT SUM(principal_amount) AS t
        FROM loans
        WHERE DATE_FORMAT(created_at,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;
    
    $pay = $conn->query("
        SELECT SUM(amount) AS t
        FROM loan_payments
        WHERE DATE_FORMAT(payment_date,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;
    
    $loanData[] = $loan;
    $payData[] = $pay;
}

/* =========================
   RECENT TRANSACTIONS
========================= */

$recent_transactions = $conn->query("
SELECT 
    'Payment' as type,
    'Loan Payment' as description,
    m.full_name as member,
    lp.amount,
    lp.payment_date as date,
    'Completed' as status
FROM loan_payments lp
LEFT JOIN loans l ON lp.loan_id = l.loan_id
LEFT JOIN members m ON l.member_id = m.member_id
ORDER BY lp.payment_date DESC
LIMIT 5
");

/* =========================
   RECENT MEMBERS
========================= */

$recent_members = $conn->query("
SELECT member_id, full_name, member_code, created_at
FROM members
ORDER BY created_at DESC
LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroFinance - Dashboard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================
           ROOT VARIABLES
        ======================================== */
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --radius: 16px;
            --shadow: 0 4px 24px rgba(0,0,0,0.06);
            --shadow-hover: 0 8px 32px rgba(0,0,0,0.10);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            overflow-x: hidden;
        }

        /* ========================================
           SIDEBAR
        ======================================== */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--gray-900);
            color: white;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 24px;
        }

        .sidebar-logo .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .sidebar-logo h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .sidebar-logo small {
            font-size: 11px;
            color: var(--gray-400);
            display: block;
            margin-top: -2px;
        }

        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--gray-300);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }

        .sidebar-menu a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
        }

        .sidebar-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 16px;
            margin-top: auto;
        }

        .sidebar-bottom a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--gray-300);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }

        .sidebar-bottom a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        /* ========================================
           TOPBAR
        ======================================== */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            transition: var(--transition);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray-700);
            cursor: pointer;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--gray-50);
            border-radius: 10px;
            padding: 8px 16px;
            gap: 10px;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .search-box i {
            color: var(--gray-400);
            font-size: 14px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            color: var(--gray-700);
            width: 240px;
        }

        .search-box input::placeholder {
            color: var(--gray-400);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            background: var(--gray-50);
            color: var(--gray-600);
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .icon-btn:hover {
            background: var(--gray-200);
        }

        .badge-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid white;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 12px 6px 6px;
            border-radius: 12px;
            transition: var(--transition);
        }

        .profile:hover {
            background: var(--gray-50);
        }

        .profile .avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
        }

        .profile h6 {
            font-size: 13px;
            font-weight: 600;
            margin: 0;
            color: var(--gray-800);
        }

        .profile small {
            font-size: 11px;
            color: var(--gray-500);
            display: block;
            margin-top: -2px;
        }

        /* ========================================
           MAIN CONTENT
        ======================================== */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px 30px 40px;
            min-height: 100vh;
            margin-top: var(--topbar-height);
        }

        /* ========================================
           DASHBOARD TOP
        ======================================== */
        .dashboard-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .welcome-section h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .welcome-section h1 .highlight {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-section p {
            font-size: 14px;
            color: var(--gray-500);
            margin: 4px 0 0;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-quick {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-quick i {
            font-size: 14px;
        }

        .btn-quick-primary {
            background: var(--primary);
            color: white;
        }
        .btn-quick-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
            color: white;
        }

        .btn-quick-success {
            background: var(--success);
            color: white;
        }
        .btn-quick-success:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
            color: white;
        }

        .btn-quick-info {
            background: var(--info);
            color: white;
        }
        .btn-quick-info:hover {
            background: #0891b2;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
            color: white;
        }

        /* ========================================
           OVERDUE ALERT
        ======================================== */
        .overdue-alert {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            padding: 14px 20px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .overdue-alert i {
            color: var(--danger);
            font-size: 20px;
        }

        .overdue-alert span {
            font-size: 14px;
            color: #991b1b;
        }

        .overdue-alert .alert-link {
            color: var(--danger);
            font-weight: 600;
            text-decoration: none;
            margin-left: auto;
        }
        .overdue-alert .alert-link:hover {
            text-decoration: underline;
        }

        /* ========================================
           STATS GRID
        ======================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-icon.teal { background: #ccfbf1; color: #0d9488; }
        .stat-icon.red { background: #fee2e2; color: #dc2626; }
        .stat-icon.gold { background: #fef3c7; color: #d97706; }

        .stat-trend {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .stat-trend.up {
            background: #dcfce7;
            color: #16a34a;
        }
        .stat-trend.down {
            background: #fee2e2;
            color: #dc2626;
        }
        .stat-trend.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-trend i {
            margin-right: 4px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .stat-sub {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 6px;
        }

        .overdue-card .stat-value {
            color: var(--danger);
        }

        /* ========================================
           DASHBOARD GRID
        ======================================== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 22px;
            margin-bottom: 28px;
        }

        .chart-container {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .card-header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-section h3 i {
            color: var(--primary);
        }

        .badge-bg {
            background: var(--gray-100);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .chart-wrapper {
            height: 280px;
            position: relative;
        }

        /* ========================================
           RIGHT PANEL
        ======================================== */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .health-card {
            background: white;
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .health-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 12px;
        }

        .health-header i {
            color: var(--primary);
        }

        .health-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .health-bar {
            width: 100%;
            height: 6px;
            background: var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .health-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--danger), var(--warning), var(--success));
            border-radius: 10px;
            transition: width 1s ease;
        }

        .health-status {
            font-size: 13px;
            color: var(--gray-600);
        }

        .health-status .badge-success {
            color: #16a34a;
            font-weight: 500;
        }
        .health-status .badge-warning {
            color: #d97706;
            font-weight: 500;
        }
        .health-status .badge-danger {
            color: #dc2626;
            font-weight: 500;
        }

        .health-link {
            display: inline-block;
            margin-top: 8px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 13px;
        }
        .health-link:hover {
            text-decoration: underline;
        }

        /* ========================================
           TOP BORROWER
        ======================================== */
        .top-borrower {
            background: white;
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .top-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 16px;
        }

        .top-header i {
            color: #f59e0b;
        }

        .top-member {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .top-member img {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
        }

        .top-member h4 {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
            color: var(--gray-800);
        }

        .top-member .member-id {
            font-size: 12px;
            color: var(--gray-400);
        }

        .top-amount {
            margin-top: 6px;
        }

        .top-amount .label {
            font-size: 11px;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .top-amount .value {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }

        /* ========================================
           BOTTOM GRID
        ======================================== */
        .bottom-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
        }

        .transaction-table {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .table-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h3 i {
            color: var(--primary);
        }

        .view-all {
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .view-all:hover {
            text-decoration: underline;
        }

        .transaction-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-table thead th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-400);
            font-weight: 600;
            border-bottom: 1px solid var(--gray-100);
        }

        .transaction-table tbody td {
            padding: 12px;
            font-size: 13px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-50);
        }

        .badge-type {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-type.payment {
            background: #dcfce7;
            color: #16a34a;
        }
        .badge-type.loan {
            background: #dbeafe;
            color: #2563eb;
        }
        .badge-type.savings {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-status.completed {
            background: #dcfce7;
            color: #16a34a;
        }
        .badge-status.pending {
            background: #fef3c7;
            color: #d97706;
        }
        .badge-status.overdue {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-status i {
            margin-right: 4px;
            font-size: 10px;
        }

        /* ========================================
           RECENT MEMBERS
        ======================================== */
        .recent-members {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .members-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .member-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .member-item:hover {
            background: var(--gray-50);
        }

        .member-item img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
        }

        .member-item .member-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .member-item .member-code {
            font-size: 11px;
            color: var(--gray-400);
        }

        .member-item .member-join {
            margin-left: auto;
            font-size: 11px;
            color: var(--gray-400);
        }

        /* ========================================
           RESPONSIVE
        ======================================== */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .topbar {
                left: 0;
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .search-box input {
                width: 120px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .dashboard-top {
                flex-direction: column;
                align-items: stretch;
            }
            
            .quick-actions {
                justify-content: stretch;
            }
            
            .btn-quick {
                flex: 1;
                justify-content: center;
            }
            
            .profile h6, .profile small {
                display: none;
            }
            
            .transaction-table {
                overflow-x: auto;
            }
            
            .chart-wrapper {
                height: 200px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .topbar-right .icon-btn {
                display: none;
            }
            
            .search-box input {
                width: 80px;
            }
        }

        /* ========================================
           SCROLLBAR
        ======================================== */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* ========================================
           UTILITY
        ======================================== */
        .text-muted {
            color: var(--gray-400);
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

<!-- ========================================
   SIDEBAR
======================================== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <i class="fa-solid fa-building-columns"></i>
        </div>
        <div>
            <h4>MicroFinance</h4>
            <small>Management System</small>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>
        <a href="members/">
            <i class="fa-solid fa-users"></i>
            Members
        </a>
        <a href="committees/">
            <i class="fa-solid fa-layer-group"></i>
            Committees
        </a>
        <a href="loans/">
            <i class="fa-solid fa-money-bill-wave"></i>
            Loans
        </a>
        <a href="installments/">
            <i class="fa-solid fa-credit-card"></i>
            Installments
        </a>
        <a href="savings/">
            <i class="fa-solid fa-piggy-bank"></i>
            Savings
        </a>
        <a href="due_system/">
            <i class="fa-solid fa-clock"></i>
            Due System
        </a>
    </div>
    
    <div class="sidebar-bottom">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</div>

<!-- ========================================
   TOPBAR
======================================== -->
<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search members, loans, payments...">
        </div>
    </div>
    
    <div class="topbar-right">
        <button class="icon-btn">
            <i class="fa-regular fa-sun"></i>
        </button>
        <button class="icon-btn">
            <i class="fa-regular fa-bell"></i>
            <span class="badge-dot"></span>
        </button>
        <div class="profile">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'Admin'); ?>&background=4f46e5&color=fff&size=36" class="avatar">
            <div>
                <h6><?php echo $_SESSION['name'] ?? 'Admin'; ?></h6>
                <small><?php echo ucfirst($_SESSION['role'] ?? 'Admin'); ?></small>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:12px;color:var(--gray-400);"></i>
        </div>
    </div>
</div>

<!-- ========================================
   MAIN CONTENT
======================================== -->
<div class="main-content">
    
    <!-- Top Section -->
    <div class="dashboard-top">
        <div class="welcome-section">
            <h1>Welcome back, <span class="highlight"><?php echo $_SESSION['name'] ?? 'Admin'; ?></span>!</h1>
            <p>Here's what's happening with your microfinance today.</p>
        </div>
        <div class="quick-actions">
            <a href="#" class="btn-quick btn-quick-primary">
                <i class="fa-solid fa-user-plus"></i>
                Add Member
            </a>
            <a href="#" class="btn-quick btn-quick-success">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                Add Loan
            </a>
            <a href="#" class="btn-quick btn-quick-info">
                <i class="fa-solid fa-piggy-bank"></i>
                Add Savings
            </a>
        </div>
    </div>

    <!-- Overdue Alert -->
    <?php if($overdue > 0): ?>
    <div class="overdue-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><strong><?php echo $overdue; ?></strong> overdue loans require immediate attention!</span>
        <a href="due_system/" class="alert-link">View Details →</a>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12%</div>
            </div>
            <div class="stat-value"><?php echo number_format($total_members); ?></div>
            <div class="stat-label">Total Members</div>
            <div class="stat-sub">+18 this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon green"><i class="fa-solid fa-user-check"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8%</div>
            </div>
            <div class="stat-value"><?php echo number_format($active_members); ?></div>
            <div class="stat-label">Active Members</div>
            <div class="stat-sub"><?php echo $total_members > 0 ? round(($active_members/$total_members)*100) : 0; ?>% of total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12.5%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_loans); ?></div>
            <div class="stat-label">Total Loans</div>
            <div class="stat-sub">+12.5% this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon teal"><i class="fa-solid fa-circle-dollar"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 15.3%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_collection); ?></div>
            <div class="stat-label">Total Collection</div>
            <div class="stat-sub">+15.3% this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_paid); ?></div>
            <div class="stat-label">Total Paid</div>
            <div class="stat-sub"><?php echo $total_loans > 0 ? round(($total_paid/$total_loans)*100) : 0; ?>% of total loans</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-trend down"><i class="fa-solid fa-arrow-down"></i> 3.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_due); ?></div>
            <div class="stat-label">Total Due</div>
            <div class="stat-sub"><?php echo $total_loans > 0 ? round(($total_due/$total_loans)*100) : 0; ?>% remaining</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon gold"><i class="fa-solid fa-piggy-bank"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_savings); ?></div>
            <div class="stat-label">Total Savings</div>
            <div class="stat-sub">+8.2% this month</div>
        </div>
        
        <div class="stat-card overdue-card">
            <div class="stat-header">
                <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-trend danger"><i class="fa-solid fa-circle"></i> Alert</div>
            </div>
            <div class="stat-value"><?php echo $overdue; ?></div>
            <div class="stat-label">Overdue Loans</div>
            <div class="stat-sub">Requires attention</div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Chart -->
        <div class="chart-container">
            <div class="card-header-section">
                <h3><i class="fa-solid fa-chart-line"></i> Loans vs Collection</h3>
                <span class="badge-bg">Last 6 Months</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        
        <!-- Right Panel -->
        <div class="right-panel">
            <!-- Loan Health -->
            <div class="health-card">
                <div class="health-header">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Loan Health</span>
                </div>
                <div class="health-value"><?php echo round($health, 1); ?>%</div>
                <div class="health-bar">
                    <div class="health-bar-fill" style="width: <?php echo min($health, 100); ?>%;"></div>
                </div>
                <div class="health-status">
                    <?php if($health >= 70): ?>
                        <span class="badge-success">✅ Your loan portfolio is healthy and performing well.</span>
                    <?php elseif($health >= 50): ?>
                        <span class="badge-warning">⚠️ Moderate health. Some attention needed.</span>
                    <?php else: ?>
                        <span class="badge-danger">🔴 Critical health. Immediate action required.</span>
                    <?php endif; ?>
                    <a href="#" class="health-link">View Details →</a>
                </div>
            </div>
            
            <!-- Top Borrower -->
            <div class="top-borrower">
                <div class="top-header">
                    <i class="fa-solid fa-trophy"></i>
                    <span>Top Borrower</span>
                </div>
                <?php if($top): ?>
                <div class="top-member">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top['full_name']); ?>&background=4f46e5&color=fff&size=52" alt="Avatar">
                    <div>
                        <h4><?php echo $top['full_name']; ?></h4>
                        <span class="member-id">ID: <?php echo $top['member_id'] ?? 'N/A'; ?></span>
                        <div class="top-amount">
                            <span class="label">Total Borrowed</span>
                            <span class="value">$<?php echo number_format($top['total'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted">No borrowers found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">
        <!-- Recent Transactions -->
        <div class="transaction-table">
            <div class="table-header">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Transactions</h3>
                <a href="#" class="view-all">View All →</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_transactions && $recent_transactions->num_rows > 0): ?>
                            <?php while($row = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge-type payment"><?php echo $row['type']; ?></span></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><strong><?php echo $row['member']; ?></strong></td>
                                <td>$<?php echo number_format($row['amount']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td><span class="badge-status completed"><i class="fa-solid fa-check-circle"></i> <?php echo $row['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No transactions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Members -->
        <div class="recent-members">
            <div class="table-header">
                <h3><i class="fa-solid fa-user-plus"></i> Recent Members</h3>
                <a href="#" class="view-all">View All →</a>
            </div>
            <div class="members-list">
                <?php if($recent_members && $recent_members->num_rows > 0): ?>
                    <?php while($row = $recent_members->fetch_assoc()): ?>
                    <div class="member-item">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=random&color=fff&size=36" alt="Avatar">
                        <div>
                            <div class="member-name"><?php echo $row['full_name']; ?></div>
                            <div class="member-code"><?php echo $row['member_code'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="member-join">
                            <small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No members found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ========================================
   SCRIPTS
======================================== -->
<script>
    // Mobile Menu Toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
    });

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });

    // Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($m) {
                    return date('M y', strtotime($m . '-01'));
                }, $months)); ?>,
                datasets: [
                    {
                        label: 'Loans Disbursed',
                        data: <?php echo json_encode($loanData); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5',
                        fill: true,
                    },
                    {
                        label: 'Collection',
                        data: <?php echo json_encode($payData); ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#22c55e',
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: {
                                family: 'Inter',
                                size: 12,
                                weight: '500'
                            }
                        },
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (context.parsed.y !== null) {
                                    label += ': $' + context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            },
                            font: {
                                family: 'Inter',
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    });
</script>

</body>
</html>