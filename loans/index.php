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

// Get recent activity count (last 7 days)
$recent_sql = "SELECT COUNT(*) as recent FROM loans WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_result = $conn->query($recent_sql);
$recent = $recent_result->fetch_assoc();
$recent_count = $recent['recent'];

// Collection rate
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
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/loans.css" rel="stylesheet">
    
    <style>
        /* Critical fallback styles - ensures text is always visible */
        body { 
            background: linear-gradient(135deg, #060A1F 0%, #0F1535 100%); 
            color: #FFFFFF; 
            font-family: 'Inter', sans-serif;
        }
        .container { max-width: 1440px; margin: 0 auto; padding: 0 20px; }
        .stat-value { color: #FFFFFF !important; }
        .stat-label { color: #B8C5E0 !important; }
        .table { color: #FFFFFF !important; }
        .table tbody td { color: #B8C5E0 !important; }
        .table thead th { color: #B8C5E0 !important; }
        .table tbody td strong { color: #FFFFFF !important; }
        .text-muted-custom { color: #B8C5E0 !important; }
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
                Loan Management
                <span class="header-subtitle">
                    <i class="fas fa-users"></i> Total Members: <?php echo $total_loans; ?> Active Loans
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
        <div class="stat-icon">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-label">Total Portfolio</div>
        <div class="stat-value">৳ <?php echo number_format($total_amount, 2); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> <?php echo $total_loans; ?> Total Loans
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-label">Active Loans</div>
        <div class="stat-value"><?php echo $total_active; ?></div>
        <div class="stat-change positive">
            <i class="fas fa-check"></i> <?php echo $total_loans > 0 ? number_format(($total_active/$total_loans)*100, 1) : 0; ?>% Active
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-label">Overdue Loans</div>
        <div class="stat-value"><?php echo $total_overdue; ?></div>
        <div class="stat-change <?php echo $total_overdue > 0 ? 'negative' : 'positive'; ?>">
            <i class="fas <?php echo $total_overdue > 0 ? 'fa-arrow-up' : 'fa-check'; ?>"></i> 
            <?php echo $total_overdue > 0 ? 'Needs Attention' : 'All Good'; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
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
                    <th><i class="fas fa-barcode"></i> Loan Code</th>
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
                            <td>
                                <strong style="color: #A89BFF;">#<?php echo $row['loan_id']; ?></strong>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #A89BFF;">
                                    <?php echo $row['loan_code']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span style="font-weight: 600; color: #FFFFFF;">
                                        <?php echo $row['full_name']; ?>
                                    </span>
                                    <span style="font-size: 11px; color: #8899BB;">
                                        <i class="fas fa-id-card" style="color: #7C6FFF;"></i> 
                                        <?php echo $row['member_code']; ?>
                                    </span>
                                </div>
                            </td>
                            <td style="font-weight: 600; color: #FFFFFF;">
                                ৳ <?php echo number_format($row['principal_amount'], 2); ?>
                            </td>
                            <td style="color: #00D4FF; font-weight: 500;">
                                <?php echo $row['interest_rate']; ?>%
                            </td>
                            <td style="font-weight: 600; color: #A89BFF;">
                                ৳ <?php echo number_format($row['total_payable'], 2); ?>
                            </td>
                            <td style="color: #69F0AE; font-weight: 500;">
                                ৳ <?php echo number_format($row['total_paid'], 2); ?>
                            </td>
                            <td style="color: #B8C5E0;">
                                ৳ <?php echo number_format($row['installment_amount'], 2); ?>
                            </td>
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
                            <td style="font-size: 12px; color: #B8C5E0;">
                                <?php echo date('d M Y', strtotime($row['disbursement_date'])); ?>
                            </td>
                            <td style="font-size: 12px; color: #B8C5E0;">
                                <?php echo date('d M Y', strtotime($row['maturity_date'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <a href="edit.php?id=<?php echo $row['loan_id']; ?>" 
                                       class="btn btn-warning btn-sm"
                                       data-tooltip="Edit Loan">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['loan_id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('⚠️ Are you sure you want to delete this loan?\nThis action cannot be undone!')"
                                       data-tooltip="Delete Loan">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="view.php?id=<?php echo $row['loan_id']; ?>" 
                                       class="btn btn-secondary btn-sm"
                                       data-tooltip="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h5>No Loans Found</h5>
                                <p style="color: #B8C5E0;">Start by creating your first loan application</p>
                                <a href="add.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Create First Loan
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Animate stats counting on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats counting
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
                if (current >= numeric) {
                    current = numeric;
                    clearInterval(timer);
                }
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