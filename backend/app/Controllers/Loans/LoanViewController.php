<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get loan ID from URL
$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($loan_id == 0) {
    header("Location: index.php");
    exit();
}

// Get loan details with member info
$sql = "
SELECT 
l.*,
m.full_name,
m.member_code
FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
WHERE l.loan_id = $loan_id
";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$loan = $result->fetch_assoc();

// Status configuration
$status_config = [
    'active' => ['class' => 'badge-success', 'icon' => 'fa-check-circle', 'label' => 'Active'],
    'closed' => ['class' => 'badge-primary', 'icon' => 'fa-check-double', 'label' => 'Closed'],
    'overdue' => ['class' => 'badge-danger', 'icon' => 'fa-exclamation-triangle', 'label' => 'Overdue'],
    'written_off' => ['class' => 'badge-dark', 'icon' => 'fa-times-circle', 'label' => 'Written Off']
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Details - <?php echo $loan['loan_code']; ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Page Header */
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
            font-size: 26px;
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
            font-size: 13px;
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
        
        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #7A9BCB;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(79, 140, 255, 0.08);
        }
        
        .back-button:hover {
            color: #7DB0FF;
            background: rgba(79, 140, 255, 0.06);
            transform: translateX(-6px);
            border-color: rgba(79, 140, 255, 0.15);
        }
        
        .back-button i {
            font-size: 16px;
            color: #4F8CFF;
            transition: all 0.3s ease;
        }
        
        .back-button:hover i {
            transform: translateX(-4px) scale(1.1);
        }
        
        /* ========== LOAN DETAILS ========== */
        .detail-section {
            background: rgba(12, 24, 48, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.7s ease;
        }
        
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #4F8CFF;
            font-size: 18px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        .detail-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 12px;
            padding: 16px 20px;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(79, 140, 255, 0.12);
            transform: translateY(-2px);
        }
        
        .detail-label {
            font-size: 11px;
            font-weight: 600;
            color: #7A9BCB;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }
        
        .detail-label i {
            margin-right: 4px;
            font-size: 12px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #E8F0FE;
        }
        
        .detail-value.blue {
            color: #7DB0FF;
        }
        
        .detail-value.green {
            color: #69F0AE;
        }
        
        .detail-value.cyan {
            color: #00D4FF;
        }
        
        .detail-value.gold {
            color: #FFD54F;
        }
        
        .detail-value.pink {
            color: #FF8A80;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge.active {
            background: rgba(0, 230, 118, 0.15);
            color: #69F0AE;
            border: 1px solid rgba(0, 230, 118, 0.15);
        }
        
        .status-badge.closed {
            background: rgba(79, 140, 255, 0.15);
            color: #7DB0FF;
            border: 1px solid rgba(79, 140, 255, 0.15);
        }
        
        .status-badge.overdue {
            background: rgba(255, 82, 82, 0.15);
            color: #FF8A80;
            border: 1px solid rgba(255, 82, 82, 0.15);
        }
        
        .status-badge.written_off {
            background: rgba(255, 255, 255, 0.05);
            color: #7A9BCB;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .summary-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(79, 140, 255, 0.06);
            border-radius: 12px;
            padding: 16px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(79, 140, 255, 0.12);
            transform: translateY(-3px);
        }
        
        .summary-label {
            font-size: 11px;
            color: #7A9BCB;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 500;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
            margin-top: 4px;
        }
        
        .summary-value.blue { color: #7DB0FF; }
        .summary-value.green { color: #69F0AE; }
        .summary-value.cyan { color: #00D4FF; }
        .summary-value.gold { color: #FFD54F; }
        .summary-value.pink { color: #FF8A80; }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
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
        
        /* ========== PRINT STYLES ========== */
        @media print {
            body {
                background: #FFFFFF !important;
                color: #000000 !important;
                padding: 10px !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-header {
                background: #FFFFFF !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                color: #000000 !important;
            }
            
            .page-header::before {
                display: none !important;
            }
            
            .header-icon {
                background: #4F8CFF !important;
                color: #FFFFFF !important;
                box-shadow: none !important;
            }
            
            .header-title {
                color: #000000 !important;
            }
            
            .header-title span {
                background: none !important;
                -webkit-text-fill-color: #4F8CFF !important;
                color: #4F8CFF !important;
            }
            
            .header-subtitle {
                color: #666666 !important;
            }
            
            .detail-section {
                background: #FFFFFF !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            
            .detail-item {
                background: #F8F9FA !important;
                border: 1px solid #e9ecef !important;
            }
            
            .detail-label {
                color: #6c757d !important;
            }
            
            .detail-value {
                color: #000000 !important;
            }
            
            .detail-value.blue {
                color: #4F8CFF !important;
            }
            
            .detail-value.green {
                color: #28a745 !important;
            }
            
            .detail-value.cyan {
                color: #17a2b8 !important;
            }
            
            .detail-value.gold {
                color: #ffc107 !important;
            }
            
            .detail-value.pink {
                color: #dc3545 !important;
            }
            
            .summary-card {
                background: #F8F9FA !important;
                border: 1px solid #e9ecef !important;
            }
            
            .summary-label {
                color: #6c757d !important;
            }
            
            .summary-value.blue { color: #4F8CFF !important; }
            .summary-value.green { color: #28a745 !important; }
            .summary-value.cyan { color: #17a2b8 !important; }
            .summary-value.gold { color: #ffc107 !important; }
            .summary-value.pink { color: #dc3545 !important; }
            
            .status-badge {
                border: 1px solid #ddd !important;
            }
            
            .status-badge.active {
                background: #d4edda !important;
                color: #155724 !important;
                border-color: #c3e6cb !important;
            }
            
            .status-badge.closed {
                background: #cce5ff !important;
                color: #004085 !important;
                border-color: #b8daff !important;
            }
            
            .status-badge.overdue {
                background: #f8d7da !important;
                color: #721c24 !important;
                border-color: #f5c6cb !important;
            }
            
            .status-badge.written_off {
                background: #e2e3e5 !important;
                color: #383d41 !important;
                border-color: #d6d8db !important;
            }
            
            .section-title {
                color: #000000 !important;
            }
            
            .section-title i {
                color: #4F8CFF !important;
            }
            
            .orb {
                display: none !important;
            }
            
            .back-button {
                display: none !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .btn {
                display: none !important;
            }
            
            .container {
                max-width: 100% !important;
                padding: 0 !important;
            }
            
            .summary-grid {
                grid-template-columns: repeat(4, 1fr) !important;
            }
            
            .detail-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            /* Print header */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #4F8CFF;
            }
            
            .print-header h2 {
                color: #4F8CFF;
                margin: 0;
                font-size: 24px;
            }
            
            .print-header p {
                color: #666;
                margin: 5px 0 0 0;
                font-size: 14px;
            }
        }
        
        /* Print header - hidden by default */
        .print-header {
            display: none;
        }
        
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
            
            .detail-section {
                padding: 20px;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
            .action-buttons {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header-title { font-size: 18px; }
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .detail-value {
                font-size: 14px;
            }
            .summary-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Floating Orbs -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="container">

<!-- Print Header (visible only when printing) -->
<div class="print-header">
    <h2>Loan Management System</h2>
    <p>Loan Details - <?php echo $loan['loan_code']; ?></p>
    <p style="font-size: 12px; color: #999;">Printed on: <?php echo date('d F Y h:i A'); ?></p>
</div>

<!-- Page Header -->
<div class="page-header no-print">
    <div class="header-left">
        <div class="header-icon">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div>
            <div class="header-title">
                <span>Loan</span> Details
                <span class="header-subtitle">
                    <i class="fas fa-barcode"></i> <?php echo $loan['loan_code']; ?>
                </span>
            </div>
        </div>
    </div>
    <div>
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-label">Principal Amount</div>
        <div class="summary-value blue">৳ <?php echo number_format($loan['principal_amount'], 2); ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Total Payable</div>
        <div class="summary-value gold">৳ <?php echo number_format($loan['total_payable'], 2); ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Total Paid</div>
        <div class="summary-value green">৳ <?php echo number_format($loan['total_paid'], 2); ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Remaining Balance</div>
        <div class="summary-value pink">৳ <?php echo number_format($loan['total_payable'] - $loan['total_paid'], 2); ?></div>
    </div>
</div>

<!-- Loan Information -->
<div class="detail-section">
    <div class="section-title">
        <i class="fas fa-info-circle"></i> Loan Information
    </div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-hashtag"></i> Loan ID</div>
            <div class="detail-value">#<?php echo $loan['loan_id']; ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-barcode"></i> Loan Code</div>
            <div class="detail-value blue"><?php echo $loan['loan_code']; ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-info-circle"></i> Status</div>
            <div class="detail-value">
                <span class="status-badge <?php echo strtolower($loan['status']); ?>">
                    <i class="fas <?php echo $status_config[$loan['status']]['icon']; ?>"></i>
                    <?php echo ucfirst($loan['status']); ?>
                </span>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-percent"></i> Interest Rate</div>
            <div class="detail-value cyan"><?php echo $loan['interest_rate']; ?>%</div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-calculator"></i> Interest Type</div>
            <div class="detail-value blue"><?php echo ucfirst(str_replace('_', ' ', $loan['interest_type'])); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-clock"></i> Loan Term</div>
            <div class="detail-value cyan"><?php echo $loan['loan_term_months']; ?> Months</div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-calendar-alt"></i> Installment Type</div>
            <div class="detail-value gold"><?php echo ucfirst($loan['installment_type']); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-money-bill-wave"></i> Installment Amount</div>
            <div class="detail-value gold">৳ <?php echo number_format($loan['installment_amount'], 2); ?></div>
        </div>
    </div>
</div>

<!-- Member Information -->
<div class="detail-section">
    <div class="section-title">
        <i class="fas fa-user"></i> Member Information
    </div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-user"></i> Full Name</div>
            <div class="detail-value blue"><?php echo $loan['full_name']; ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-id-card"></i> Member Code</div>
            <div class="detail-value"><?php echo $loan['member_code']; ?></div>
        </div>
    </div>
</div>

<!-- Dates Information -->
<div class="detail-section">
    <div class="section-title">
        <i class="fas fa-calendar-alt"></i> Dates
    </div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-calendar-day"></i> Disbursement Date</div>
            <div class="detail-value"><?php echo date('d F Y', strtotime($loan['disbursement_date'])); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-calendar-check"></i> Maturity Date</div>
            <div class="detail-value"><?php echo date('d F Y', strtotime($loan['maturity_date'])); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label"><i class="fas fa-calendar-plus"></i> First Installment Date</div>
            <div class="detail-value gold"><?php echo date('d F Y', strtotime($loan['first_installment_date'])); ?></div>
        </div>
    </div>
</div>

<!-- Purpose -->
<?php if(!empty($loan['purpose'])): ?>
<div class="detail-section">
    <div class="section-title">
        <i class="fas fa-info-circle"></i> Purpose
    </div>
    <div class="detail-item" style="padding: 16px 20px;">
        <div class="detail-value"><?php echo $loan['purpose']; ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons no-print">
    <a href="edit.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-warning">
        <i class="fas fa-edit"></i> Edit Loan
    </a>
    <a href="delete.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-danger" onclick="return confirm('⚠️ Are you sure you want to delete this loan?')">
        <i class="fas fa-trash"></i> Delete Loan
    </a>
    <button onclick="printLoanDetails()" class="btn btn-primary">
        <i class="fas fa-print"></i> Print Details
    </button>
</div>

</div>

<script>
function printLoanDetails() {
    window.print();
}

// Also make the button work with onclick directly
document.addEventListener('DOMContentLoaded', function() {
    var printBtn = document.querySelector('button[onclick="printLoanDetails()"]');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            window.print();
        });
    }
});
</script>

</body>
</html>