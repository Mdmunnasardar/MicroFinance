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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Management System</title>
    <!-- Required Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - CRITICAL: This MUST be loaded -->
    <link href="../assets/css/loans.css" rel="stylesheet">
    
    <!-- Fallback: If CSS doesn't load, use inline critical styles -->
    <style>
        /* Critical fallback styles */
        body { background: #0A0E27; color: #fff; font-family: 'Inter', sans-serif; }
        .container { max-width: 1440px; margin: 0 auto; padding: 0 20px; }
        .page-header { 
            background: rgba(255,255,255,0.08); 
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 32px 48px;
            margin-bottom: 32px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 24px 32px;
        }
        .table-wrapper {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            overflow: hidden;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6C63FF 0%, #4A42CC 100%);
            color: #fff;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        <div class="stat-change negative">
            <i class="fas fa-arrow-up"></i> Needs Attention
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-label">Collection Rate</div>
        <div class="stat-value"><?php echo $total_amount > 0 ? number_format(($total_paid/$total_amount)*100, 1) : 0; ?>%</div>
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
                                <strong style="color: var(--primary-light, #8B83FF);">#<?php echo $row['loan_id']; ?></strong>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary-light, #8B83FF);">
                                    <?php echo $row['loan_code']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span style="font-weight: 600; color: var(--white, #FFFFFF);">
                                        <?php echo $row['full_name']; ?>
                                    </span>
                                    <span style="font-size: 11px; color: var(--gray, #6B7A9C);">
                                        <i class="fas fa-id-card"></i> <?php echo $row['member_code']; ?>
                                    </span>
                                </div>
                            </td>
                            <td style="font-weight: 600; color: var(--white, #FFFFFF);">
                                ৳ <?php echo number_format($row['principal_amount'], 2); ?>
                            </td>
                            <td style="color: var(--secondary, #00D4FF);">
                                <?php echo $row['interest_rate']; ?>%
                            </td>
                            <td style="font-weight: 600; color: var(--primary-light, #8B83FF);">
                                ৳ <?php echo number_format($row['total_payable'], 2); ?>
                            </td>
                            <td style="color: var(--success, #00E676);">
                                ৳ <?php echo number_format($row['total_paid'], 2); ?>
                            </td>
                            <td style="color: var(--gray-light, #A8B5D1);">
                                ৳ <?php echo number_format($row['installment_amount'], 2); ?>
                            </td>
                            <td>
                                <?php
                                $status_config = [
                                    'active' => ['class' => 'badge-success', 'icon' => 'fa-check-circle'],
                                    'closed' => ['class' => 'badge-primary', 'icon' => 'fa-check-double'],
                                    'overdue' => ['class' => 'badge-danger', 'icon' => 'fa-exclamation-triangle'],
                                    'written_off' => ['class' => 'badge-dark', 'icon' => 'fa-times-circle']
                                ];
                                $config = isset($status_config[$row['status']]) ? $status_config[$row['status']] : $status_config['active'];
                                ?>
                                <span class="badge <?php echo $config['class']; ?>">
                                    <i class="fas <?php echo $config['icon']; ?>"></i>
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: var(--gray-light, #A8B5D1);">
                                <?php echo date('d M Y', strtotime($row['disbursement_date'])); ?>
                            </td>
                            <td style="font-size: 12px; color: var(--gray-light, #A8B5D1);">
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
                                <p>Start by creating your first loan application</p>
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
            const timer = setInterval(() => {
                current += increment;
                if (current >= numeric) {
                    current = numeric;
                    clearInterval(timer);
                }
                if (isCurrency) {
                    el.textContent = '৳ ' + current.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
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