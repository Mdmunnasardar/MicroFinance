<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// GET LOANS WITH MEMBER INFO
$sql = "
SELECT 
l.*,
m.full_name,
m.member_code
FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
ORDER BY l.loan_id DESC
";

$result = $conn->query($sql);

// Calculate statistics
$total_loans = $result->num_rows;
$total_active = 0;
$total_overdue = 0;
$total_closed = 0;
$total_amount = 0;
$total_paid = 0;

$result->data_seek(0);
while($row = $result->fetch_assoc()) {
    if($row['status'] == 'active') $total_active++;
    if($row['status'] == 'overdue') $total_overdue++;
    if($row['status'] == 'closed') $total_closed++;
    $total_amount += $row['total_payable'];
    $total_paid += $row['total_paid'];
}
$result->data_seek(0);

// Get recent activity
$recent_sql = "SELECT COUNT(*) as recent FROM loans WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_result = $conn->query($recent_sql);
$recent = $recent_result->fetch_assoc();
$recent_count = $recent['recent'];

$collection_rate = $total_amount > 0 ? ($total_paid / $total_amount) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ========== COMPLETE STYLING - DEEP BLUE THEME ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #060D1A 0%, #0B1830 30%, #0F2040 50%, #0B1830 70%, #060D1A 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            color: #E8F0FE;
        }
        
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .page-header {
            background: rgba(12, 24, 48, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(79, 140, 255, 0.10);
            border-radius: 20px;
            padding: 28px 40px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.5);
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4F8CFF, #00D4FF, #4F8CFF);
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 1;
        }
        
        .header-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #4F8CFF, #1A56DB);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #FFFFFF;
            box-shadow: 0 0 40px rgba(79, 140, 255, 0.35);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.8; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        
        .header-title {
            font-size: 30px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.5px;
        }
        
        .header-title span {
            background: linear-gradient(135deg, #4F8CFF, #00D4FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header-subtitle {
            font-size: 14px;
            color: #7A9BCB;
            margin-top: 4px;
            display: block;
        }
        
        .header-subtitle i {
            color: #4F8CFF;
        }
        
        .header-right {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4F8CFF, #1A56DB);
            color: #FFFFFF;
            box-shadow: 0 4px 25px rgba(79, 140, 255, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(79, 140, 255, 0.5);
            color: #FFFFFF;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00E676, #00C853);
            color: #060D1A;
            box-shadow: 0 4px 20px rgba(0, 230, 118, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0, 230, 118, 0.4);
            color: #060D1A;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FFD54F, #FFB300);
            color: #060D1A;
            box-shadow: 0 4px 20px rgba(255, 213, 79, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(255, 213, 79, 0.4);
            color: #060D1A;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #FF5252, #D32F2F);
            color: #FFFFFF;
            box-shadow: 0 4px 20px rgba(255, 82, 82, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(255, 82, 82, 0.4);
            color: #FFFFFF;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: #7A9BCB;
            border: 1px solid rgba(79, 140, 255, 0.08);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #E8F0FE;
        }
        
        .btn-sm {
            padding: 5px 12px;
            font-size: 11px;
            border-radius: 6px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        .stat-card {
            background: rgba(12, 24, 48, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 12px;
            padding: 20px 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #4F8CFF, #00D4FF);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            border-color: rgba(79, 140, 255, 0.25);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(79, 140, 255, 0.06);
        }
        
        .stat-card:nth-child(1) .stat-icon { color: #4F8CFF; }
        .stat-card:nth-child(2) .stat-icon { color: #69F0AE; }
        .stat-card:nth-child(3) .stat-icon { color: #FFD54F; }
        .stat-card:nth-child(4) .stat-icon { color: #00D4FF; }
        
        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #7A9BCB;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #E8F0FE;
            margin-top: 4px;
        }
        
        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: 20px;
            margin-top: 6px;
        }
        
        .stat-change.positive {
            background: rgba(0, 230, 118, 0.12);
            color: #69F0AE;
            border: 1px solid rgba(0, 230, 118, 0.08);
        }
        
        .stat-change.negative {
            background: rgba(255, 82, 82, 0.12);
            color: #FF8A80;
            border: 1px solid rgba(255, 82, 82, 0.08);
        }
        
        /* ========== TABLE ========== */
        .table-wrapper {
            background: rgba(10, 20, 40, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 140, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 13px;
            color: #E8F0FE;
            table-layout: fixed;
        }
        
        .table thead {
            background: rgba(79, 140, 255, 0.06);
            border-bottom: 1px solid rgba(79, 140, 255, 0.08);
        }
        
        .table thead th {
            padding: 12px 8px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #7A9BCB;
            white-space: nowrap;
            text-align: left;
            border: none;
            vertical-align: middle;
        }
        
        .table thead th i {
            color: #4F8CFF;
            margin-right: 4px;
            font-size: 10px;
        }
        
        /* FIXED COLUMN WIDTHS */
        .table thead th:nth-child(1) { width: 55px; }
        .table thead th:nth-child(2) { width: 70px; }
        .table thead th:nth-child(3) { width: 160px; }
        .table thead th:nth-child(4) { width: 90px; }
        .table thead th:nth-child(5) { width: 50px; }
        .table thead th:nth-child(6) { width: 90px; }
        .table thead th:nth-child(7) { width: 90px; }
        .table thead th:nth-child(8) { width: 90px; }
        .table thead th:nth-child(9) { width: 85px; }
        .table thead th:nth-child(10) { width: 100px; }
        .table thead th:nth-child(11) { width: 100px; }
        .table thead th:nth-child(12) { width: 110px; }
        
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(79, 140, 255, 0.03);
        }
        
        .table tbody tr:hover {
            background: rgba(79, 140, 255, 0.05);
        }
        
        .table tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border: none;
            word-wrap: break-word;
        }
        
        /* ========== ID BADGE ========== */
        .id-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            background: linear-gradient(135deg, #4F8CFF, #1A56DB);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 12px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(79, 140, 255, 0.35);
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover .id-badge {
            transform: scale(1.05);
            box-shadow: 0 4px 30px rgba(79, 140, 255, 0.5);
        }
        
        /* ========== BADGES ========== */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        
        .badge-success {
            background: rgba(0, 230, 118, 0.15);
            color: #69F0AE;
            border: 1px solid rgba(0, 230, 118, 0.12);
        }
        
        .badge-primary {
            background: rgba(79, 140, 255, 0.15);
            color: #7DB0FF;
            border: 1px solid rgba(79, 140, 255, 0.12);
        }
        
        .badge-danger {
            background: rgba(255, 82, 82, 0.15);
            color: #FF8A80;
            border: 1px solid rgba(255, 82, 82, 0.12);
        }
        
        .badge-warning {
            background: rgba(255, 213, 79, 0.15);
            color: #FFD54F;
            border: 1px solid rgba(255, 213, 79, 0.12);
        }
        
        .badge-dark {
            background: rgba(255, 255, 255, 0.04);
            color: #7A9BCB;
            border: 1px solid rgba(79, 140, 255, 0.06);
        }
        
        /* ========== MEMBER COLUMN - BLUE COLOR ========== */
        .member-name {
            font-weight: 700;
            color: #7DB0FF !important;
            font-size: 14px;
            display: block;
            text-shadow: 0 0 20px rgba(79, 140, 255, 0.15);
            letter-spacing: 0.2px;
        }
        
        .member-code {
            font-size: 11px;
            color: #6B8AAA;
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }
        
        .member-code i {
            color: #4F8CFF;
            margin-right: 4px;
            font-size: 10px;
        }
        
        /* ========== TABLE CELL COLORS - DEEP & VISUAL ========== */
        .table tbody td:first-child {
            color: #7DB0FF !important;
            font-weight: 800;
        }
        
        .table tbody td:nth-child(2) {
            color: #6BA3FF !important;
            font-weight: 700;
            font-size: 14px;
            text-shadow: 0 0 30px rgba(79, 140, 255, 0.15);
        }
        
        .table tbody td:nth-child(4) {
            color: #7DB0FF !important;
            font-weight: 700;
            font-size: 14px;
            text-shadow: 0 0 20px rgba(79, 140, 255, 0.1);
        }
        
        .table tbody td:nth-child(5) {
            color: #00D4FF !important;
            font-weight: 700;
            font-size: 14px;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.15);
        }
        
        .table tbody td:nth-child(6) {
            color: #7DB0FF !important;
            font-weight: 700;
            font-size: 14px;
            text-shadow: 0 0 30px rgba(79, 140, 255, 0.15);
        }
        
        .table tbody td:nth-child(7) {
            color: #69F0AE !important;
            font-weight: 700;
            font-size: 14px;
            text-shadow: 0 0 30px rgba(0, 230, 118, 0.1);
        }
        
        .table tbody td:nth-child(8) {
            color: #B8C8E8 !important;
            font-weight: 600;
        }
        
        .table tbody td:nth-child(10),
        .table tbody td:nth-child(11) {
            color: #8AA8D0 !important;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* ========== BACK BUTTON ========== */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #7A9BCB;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 12px 24px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(79, 140, 255, 0.08);
        }
        
        .back-button:hover {
            color: #7DB0FF;
            background: rgba(79, 140, 255, 0.06);
            transform: translateX(-6px);
            border-color: rgba(79, 140, 255, 0.15);
            box-shadow: 0 0 30px rgba(79, 140, 255, 0.08);
        }
        
        .back-button i {
            font-size: 16px;
            color: #4F8CFF;
            transition: all 0.3s ease;
        }
        
        .back-button:hover i {
            transform: translateX(-4px) scale(1.1);
        }
        
        .back-button .text {
            color: #E8F0FE;
            font-weight: 600;
        }
        
        /* Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
            animation: float 10s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            33% { transform: translateY(-30px) scale(1.05); }
            66% { transform: translateY(20px) scale(0.95); }
        }
        
        .orb-1 {
            width: 500px;
            height: 500px;
            background: rgba(79, 140, 255, 0.10);
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 350px;
            height: 350px;
            background: rgba(0, 212, 255, 0.07);
            bottom: -50px;
            left: -50px;
            animation-delay: -3s;
        }
        
        .orb-3 {
            width: 250px;
            height: 250px;
            background: rgba(79, 140, 255, 0.05);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -5s;
        }
        
        /* Animations */
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .page-header { animation: slideUp 0.8s ease; }
        .stat-card { animation: slideUp 0.8s ease 0.1s both; }
        .table-wrapper { animation: slideUp 0.8s ease 0.2s both; }
        .table tbody tr { animation: slideUp 0.5s ease both; }
        .back-button { animation: slideUp 0.8s ease 0.3s both; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
                flex-direction: column;
                align-items: stretch;
            }
            .header-left {
                flex-direction: column;
                text-align: center;
            }
            .header-title { font-size: 22px; }
            .header-right { justify-content: center; }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }
            .stat-card { padding: 16px; }
            .stat-value { font-size: 22px; }
            
            .table thead th,
            .table tbody td {
                padding: 8px 6px;
                font-size: 11px;
            }
            .table thead th:nth-child(1) { width: 45px; }
            .table thead th:nth-child(2) { width: 60px; }
            .table thead th:nth-child(3) { width: 120px; }
            .table thead th:nth-child(4) { width: 70px; }
            .table thead th:nth-child(5) { width: 40px; }
            .table thead th:nth-child(6) { width: 70px; }
            .table thead th:nth-child(7) { width: 70px; }
            .table thead th:nth-child(8) { width: 70px; }
            .table thead th:nth-child(9) { width: 70px; }
            .table thead th:nth-child(10) { width: 80px; }
            .table thead th:nth-child(11) { width: 80px; }
            .table thead th:nth-child(12) { width: 90px; }
            .id-badge {
                min-width: 28px;
                height: 28px;
                font-size: 10px;
                padding: 0 6px;
            }
            .member-name { font-size: 12px; }
            .back-button { padding: 10px 18px; font-size: 13px; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header-title { font-size: 18px; }
            .btn { padding: 8px 16px; font-size: 12px; }
            
            .table thead th,
            .table tbody td {
                padding: 6px 4px;
                font-size: 10px;
            }
            .id-badge {
                min-width: 24px;
                height: 24px;
                font-size: 9px;
                padding: 0 4px;
            }
            .member-name { font-size: 10px; }
            .member-code { font-size: 8px; }
            .back-button { padding: 8px 14px; font-size: 12px; }
        }
    </style>
</head>
<body>

<!-- Floating Orbs -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="container">

<!-- Page Header -->
<div class="page-header">
    <div class="header-left">
        <div class="header-icon">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <div>
            <div class="header-title">
                <span>Loan</span> Management
                <span class="header-subtitle">
                    <i class="fas fa-users"></i> <?php echo $total_loans; ?> Active Loans
                </span>
            </div>
        </div>
    </div>
    <div class="header-right">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> New Loan
        </a>
        <a href="#" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-label">Total Portfolio</div>
        <div class="stat-value">৳ <?php echo number_format($total_amount, 2); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> <?php echo $total_loans; ?> Total Loans
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">Active Loans</div>
        <div class="stat-value"><?php echo $total_active; ?></div>
        <div class="stat-change positive">
            <i class="fas fa-check"></i> <?php echo $total_loans > 0 ? number_format(($total_active/$total_loans)*100, 1) : 0; ?>% Active
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-label">Overdue Loans</div>
        <div class="stat-value"><?php echo $total_overdue; ?></div>
        <div class="stat-change <?php echo $total_overdue > 0 ? 'negative' : 'positive'; ?>">
            <i class="fas <?php echo $total_overdue > 0 ? 'fa-arrow-up' : 'fa-check'; ?>"></i> 
            <?php echo $total_overdue > 0 ? 'Needs Attention' : 'All Good'; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-label">Collection Rate</div>
        <div class="stat-value"><?php echo number_format($collection_rate, 1); ?>%</div>
        <div class="stat-change positive">
            <i class="fas fa-clock"></i> <?php echo $recent_count; ?> New (7 days)
        </div>
    </div>
</div>

<!-- Table -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> ID</th>
                    <th><i class="fas fa-barcode"></i> Code</th>
                    <th><i class="fas fa-user"></i> Member</th>
                    <th><i class="fas fa-money-bill-wave"></i> Principal</th>
                    <th><i class="fas fa-percent"></i> Rate</th>
                    <th><i class="fas fa-calculator"></i> Total</th>
                    <th><i class="fas fa-check-circle"></i> Paid</th>
                    <th><i class="fas fa-calendar-alt"></i> Installment</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                    <th><i class="fas fa-calendar-day"></i> Disbursement</th>
                    <th><i class="fas fa-calendar-check"></i> Maturity</th>
                    <th><i class="fas fa-cogs"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php 
                    $delay = 0;
                    while($row = $result->fetch_assoc()): 
                        $delay += 0.04;
                    ?>
                        <tr style="animation-delay: <?php echo $delay; ?>s">
                            <!-- ID -->
                            <td>
                                <span class="id-badge">
                                    <i class="fas fa-hashtag" style="font-size: 8px; margin-right: 2px; opacity: 0.6;"></i>
                                    <?php echo $row['loan_id']; ?>
                                </span>
                            </td>
                            
                            <!-- CODE -->
                            <td><?php echo $row['loan_code']; ?></td>
                            
                            <!-- MEMBER -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 1px;">
                                    <span class="member-name"><?php echo $row['full_name']; ?></span>
                                    <span class="member-code">
                                        <i class="fas fa-id-card"></i> <?php echo $row['member_code']; ?>
                                    </span>
                                </div>
                            </td>
                            
                            <!-- PRINCIPAL -->
                            <td>৳ <?php echo number_format($row['principal_amount'], 2); ?></td>
                            
                            <!-- RATE -->
                            <td><?php echo $row['interest_rate']; ?>%</td>
                            
                            <!-- TOTAL -->
                            <td>৳ <?php echo number_format($row['total_payable'], 2); ?></td>
                            
                            <!-- PAID -->
                            <td>৳ <?php echo number_format($row['total_paid'], 2); ?></td>
                            
                            <!-- INSTALLMENT -->
                            <td>৳ <?php echo number_format($row['installment_amount'], 2); ?></td>
                            
                            <!-- STATUS -->
                            <td>
                                <?php
                                $status_config = [
                                    'active' => ['class' => 'badge-success', 'icon' => 'fa-check-circle', 'label' => 'Active'],
                                    'closed' => ['class' => 'badge-primary', 'icon' => 'fa-check-double', 'label' => 'Closed'],
                                    'overdue' => ['class' => 'badge-danger', 'icon' => 'fa-exclamation-triangle', 'label' => 'Overdue'],
                                    'written_off' => ['class' => 'badge-dark', 'icon' => 'fa-times-circle', 'label' => 'Written Off']
                                ];
                                $config = isset($status_config[$row['status']]) ? $status_config[$row['status']] : $status_config['active'];
                                ?>
                                <span class="badge <?php echo $config['class']; ?>">
                                    <i class="fas <?php echo $config['icon']; ?>" style="font-size: 8px;"></i>
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            
                            <!-- DISBURSEMENT -->
                            <td><?php echo date('d M Y', strtotime($row['disbursement_date'])); ?></td>
                            
                            <!-- MATURITY -->
                            <td><?php echo date('d M Y', strtotime($row['maturity_date'])); ?></td>
                            
                            <!-- ACTIONS -->
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <a href="edit.php?id=<?php echo $row['loan_id']; ?>" class="btn btn-warning btn-sm" title="Edit Loan" style="padding: 4px 10px; font-size: 10px;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['loan_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ Are you sure you want to delete this loan?')" title="Delete Loan" style="padding: 4px 10px; font-size: 10px;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <!-- ====== FIXED VIEW BUTTON ====== -->
                                    <a href="view.php?id=<?php echo $row['loan_id']; ?>" class="btn btn-secondary btn-sm" title="View Details" style="padding: 4px 10px; font-size: 10px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 50px 20px;">
                            <i class="fas fa-inbox" style="font-size: 56px; color: #1A3A6A; opacity: 0.3; margin-bottom: 16px; display: block;"></i>
                            <h5 style="color: #E8F0FE;">No Loans Found</h5>
                            <p style="color: #7A9BCB;">Start by creating your first loan application</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top: 16px;">
                                <i class="fas fa-plus-circle"></i> Create First Loan
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Back Button -->
<div style="margin-top: 24px; text-align: center;">
    <a href="../dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        <span class="text">Back to Dashboard</span>
    </a>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats
    document.querySelectorAll('.stat-value').forEach(el => {
        const text = el.textContent;
        const numeric = parseFloat(text.replace(/[^0-9.]/g, ''));
        if (!isNaN(numeric) && numeric > 0) {
            let current = 0;
            const increment = numeric / 40;
            const isCurrency = text.includes('৳');
            const isPercent = text.includes('%');
            const timer = setInterval(() => {
                current += increment;
                if (current >= numeric) { current = numeric; clearInterval(timer); }
                if (isCurrency) {
                    el.textContent = '৳ ' + current.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                } else if (isPercent) {
                    el.textContent = current.toFixed(1) + '%';
                } else {
                    el.textContent = Math.round(current).toLocaleString();
                }
            }, 30);
        }
    });
});
</script>

</body>
</html>