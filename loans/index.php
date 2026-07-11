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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ========== BLUE THEME ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0B1120 0%, #111B30 40%, #162040 70%, #111B30 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            color: #E8F0FE;
            position: relative;
        }
        
        /* Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.10;
            pointer-events: none;
            z-index: 0;
            animation: floatOrb 12s ease-in-out infinite;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -40px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }
        
        .orb-1 {
            width: 500px;
            height: 500px;
            background: rgba(79, 140, 255, 0.08);
            top: -150px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 400px;
            height: 400px;
            background: rgba(0, 212, 255, 0.06);
            bottom: -100px;
            left: -100px;
            animation-delay: -3s;
        }
        
        .orb-3 {
            width: 300px;
            height: 300px;
            background: rgba(79, 140, 255, 0.05);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -5s;
        }
        
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }
        
        /* ========== HEADER ========== */
        .page-header {
            background: rgba(20, 35, 60, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(79, 140, 255, 0.08);
            border-radius: 16px;
            padding: 28px 40px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
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
            animation: shimmerBlue 4s ease infinite;
        }
        
        @keyframes shimmerBlue {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
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
            background: linear-gradient(135deg, #4F8CFF, #2D6CD4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #FFFFFF;
            box-shadow: 0 0 40px rgba(79, 140, 255, 0.2);
            animation: pulseBlue 3s ease-in-out infinite;
        }
        
        @keyframes pulseBlue {
            0%, 100% { box-shadow: 0 0 40px rgba(79, 140, 255, 0.2); }
            50% { box-shadow: 0 0 60px rgba(79, 140, 255, 0.4); }
        }
        
        .header-title {
            font-size: 28px;
            font-weight: 800;
            color: #E8F0FE;
            letter-spacing: -0.5px;
        }
        
        .header-title span {
            background: linear-gradient(135deg, #4F8CFF, #7DB0FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header-subtitle {
            font-size: 13px;
            color: #8AA0C8;
            margin-top: 2px;
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
        
        /* ========== BUTTONS ========== */
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
            letter-spacing: 0.3px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4F8CFF, #2D6CD4);
            color: #FFFFFF;
            box-shadow: 0 4px 20px rgba(79, 140, 255, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(79, 140, 255, 0.35);
            color: #FFFFFF;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00E676, #00C853);
            color: #0B1120;
            box-shadow: 0 4px 20px rgba(0, 230, 118, 0.25);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 230, 118, 0.35);
            color: #0B1120;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FFD54F, #FFB300);
            color: #0B1120;
            box-shadow: 0 4px 20px rgba(255, 213, 79, 0.2);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 213, 79, 0.3);
            color: #0B1120;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #EF5350, #C62828);
            color: #FFFFFF;
            box-shadow: 0 4px 20px rgba(239, 83, 80, 0.25);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(239, 83, 80, 0.35);
            color: #FFFFFF;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: #8AA0C8;
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
        
        /* ========== STATS CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(20, 35, 60, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 12px;
            padding: 20px 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #4F8CFF, #00D4FF);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(79, 140, 255, 0.15);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
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
            background: rgba(79, 140, 255, 0.06);
            border: 1px solid rgba(79, 140, 255, 0.06);
        }
        
        .stat-card:nth-child(1) .stat-icon { color: #4F8CFF; }
        .stat-card:nth-child(2) .stat-icon { color: #00E676; }
        .stat-card:nth-child(3) .stat-icon { color: #FF5252; }
        .stat-card:nth-child(4) .stat-icon { color: #00D4FF; }
        
        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #8AA0C8;
            text-transform: uppercase;
            letter-spacing: 0.6px;
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
            background: rgba(0, 230, 118, 0.10);
            color: #00E676;
            border: 1px solid rgba(0, 230, 118, 0.06);
        }
        
        .stat-change.negative {
            background: rgba(255, 82, 82, 0.10);
            color: #FF5252;
            border: 1px solid rgba(255, 82, 82, 0.06);
        }
        
        /* ========== TABLE ========== */
        .table-wrapper {
            background: rgba(20, 35, 60, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .table-wrapper:hover {
            border-color: rgba(79, 140, 255, 0.10);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            font-size: 13px;
            color: #E8F0FE;
        }
        
        .table thead {
            background: rgba(79, 140, 255, 0.04);
            border-bottom: 1px solid rgba(79, 140, 255, 0.06);
        }
        
        .table thead th {
            padding: 16px 18px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8AA0C8;
            white-space: nowrap;
            text-align: left;
            border: none;
        }
        
        .table thead th i {
            color: #4F8CFF;
            margin-right: 6px;
            font-size: 11px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(79, 140, 255, 0.02);
            cursor: default;
        }
        
        .table tbody tr:hover {
            background: rgba(79, 140, 255, 0.04);
        }
        
        .table tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            border: none;
            color: #8AA0C8;
        }
        
        .table tbody td strong {
            color: #E8F0FE;
        }
        
        /* ========== BADGES ========== */
        .badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-success {
            background: rgba(0, 230, 118, 0.10);
            color: #00E676;
            border: 1px solid rgba(0, 230, 118, 0.10);
        }
        
        .badge-primary {
            background: rgba(79, 140, 255, 0.10);
            color: #7DB0FF;
            border: 1px solid rgba(79, 140, 255, 0.10);
        }
        
        .badge-danger {
            background: rgba(255, 82, 82, 0.10);
            color: #FF5252;
            border: 1px solid rgba(255, 82, 82, 0.10);
        }
        
        .badge-warning {
            background: rgba(255, 215, 0, 0.08);
            color: #FFD54F;
            border: 1px solid rgba(255, 215, 0, 0.08);
        }
        
        .badge-dark {
            background: rgba(255, 255, 255, 0.04);
            color: #8AA0C8;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }
        
        /* ========== ANIMATIONS ========== */
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .page-header { animation: slideUp 0.6s ease; }
        .stat-card { animation: slideUp 0.6s ease 0.05s both; }
        .table-wrapper { animation: slideUp 0.6s ease 0.1s both; }
        .table tbody tr { animation: slideUp 0.5s ease both; }
        
        /* ========== RESPONSIVE ========== */
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
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header-title { font-size: 18px; }
            .btn { padding: 8px 16px; font-size: 12px; }
        }
        
        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(79, 140, 255, 0.02);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4F8CFF, #00D4FF);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #4F8CFF;
        }
        
        /* ========== BACK BUTTON ========== */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #8AA0C8;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(79, 140, 255, 0.06);
        }
        
        .back-button:hover {
            color: #7DB0FF;
            background: rgba(79, 140, 255, 0.06);
            transform: translateX(-4px);
        }
        
        .back-button i {
            font-size: 16px;
            color: #4F8CFF;
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
            <i class="fas fa-arrow-up"></i> <?php echo $total_loans; ?> Loans
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">Active Loans</div>
        <div class="stat-value"><?php echo $total_active; ?></div>
        <div class="stat-change positive">
            <i class="fas fa-check"></i> <?php echo $total_loans > 0 ? number_format(($total_active/$total_loans)*100, 1) : 0; ?>%
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-label">Overdue Loans</div>
        <div class="stat-value"><?php echo $total_overdue; ?></div>
        <div class="stat-change <?php echo $total_overdue > 0 ? 'negative' : 'positive'; ?>">
            <i class="fas <?php echo $total_overdue > 0 ? 'fa-arrow-up' : 'fa-check'; ?>"></i>
            <?php echo $total_overdue > 0 ? 'Attention' : 'All Good'; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-label">Collection Rate</div>
        <div class="stat-value"><?php echo number_format($collection_rate, 1); ?>%</div>
        <div class="stat-change positive">
            <i class="fas fa-clock"></i> <?php echo $recent_count; ?> New
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
                        $delay += 0.05;
                    ?>
                        <tr style="animation-delay: <?php echo $delay; ?>s">
                            <td><strong style="color: #7DB0FF;">#<?php echo $row['loan_id']; ?></strong></td>
                            <td><span style="font-weight: 600; color: #4F8CFF;"><?php echo $row['loan_code']; ?></span></td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 1px;">
                                    <span style="font-weight: 600; color: #E8F0FE;"><?php echo $row['full_name']; ?></span>
                                    <span style="font-size: 10px; color: #8AA0C8;">
                                        <i class="fas fa-id-card" style="color: #4F8CFF;"></i> <?php echo $row['member_code']; ?>
                                    </span>
                                </div>
                            </td>
                            <td style="font-weight: 600; color: #E8F0FE;">৳ <?php echo number_format($row['principal_amount'], 2); ?></td>
                            <td style="color: #00D4FF; font-weight: 500;"><?php echo $row['interest_rate']; ?>%</td>
                            <td style="font-weight: 600; color: #7DB0FF;">৳ <?php echo number_format($row['total_payable'], 2); ?></td>
                            <td style="color: #00E676; font-weight: 500;">৳ <?php echo number_format($row['total_paid'], 2); ?></td>
                            <td style="color: #8AA0C8;">৳ <?php echo number_format($row['installment_amount'], 2); ?></td>
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
                                    <i class="fas <?php echo $config['icon']; ?>"></i>
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            <td style="font-size: 11px; color: #8AA0C8;"><?php echo date('d M Y', strtotime($row['disbursement_date'])); ?></td>
                            <td style="font-size: 11px; color: #8AA0C8;"><?php echo date('d M Y', strtotime($row['maturity_date'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <a href="edit.php?id=<?php echo $row['loan_id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['loan_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="#" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 60px 20px;">
                            <i class="fas fa-inbox" style="font-size: 56px; color: #2D4A7A; opacity: 0.3; margin-bottom: 16px; display: block;"></i>
                            <h5 style="color: #E8F0FE;">No Loans Found</h5>
                            <p style="color: #8AA0C8;">Start by creating your first loan application</p>
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
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stat-value').forEach(el => {
        const text = el.textContent;
        const numeric = parseFloat(text.replace(/[^0-9.]/g, ''));
        if (!isNaN(numeric) && numeric > 0) {
            let current = 0;
            const increment = numeric / 35;
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