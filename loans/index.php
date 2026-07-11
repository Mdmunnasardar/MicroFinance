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

$result->data_seek(0);
while($row = $result->fetch_assoc()) {
    if($row['status'] == 'active') $total_active++;
    if($row['status'] == 'overdue') $total_overdue++;
    if($row['status'] == 'closed') $total_closed++;
    $total_amount += $row['total_payable'];
}
$result->data_seek(0);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="loans.css" rel="stylesheet">
</head>
<body>

<div class="container">

<!-- Page Header -->
<div class="page-header">
    <h3>
        <i class="fas fa-hand-holding-usd"></i>
        Loan Management
    </h3>
    <div class="header-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Loan
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Loans</div>
        <div class="stat-value"><?php echo $total_loans; ?></div>
    </div>
    <div class="stat-card" style="border-left: 4px solid var(--success-color);">
        <div class="stat-label">Active Loans</div>
        <div class="stat-value" style="color: var(--success-color);"><?php echo $total_active; ?></div>
    </div>
    <div class="stat-card" style="border-left: 4px solid var(--danger-color);">
        <div class="stat-label">Overdue Loans</div>
        <div class="stat-value" style="color: var(--danger-color);"><?php echo $total_overdue; ?></div>
    </div>
    <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
        <div class="stat-label">Total Portfolio</div>
        <div class="stat-value" style="color: var(--primary-color);">৳ <?php echo number_format($total_amount, 2); ?></div>
    </div>
</div>

<!-- Table -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag"></i> ID</th>
                <th><i class="fas fa-barcode"></i> Loan Code</th>
                <th><i class="fas fa-user"></i> Member</th>
                <th><i class="fas fa-money-bill-wave"></i> Principal</th>
                <th><i class="fas fa-percent"></i> Interest</th>
                <th><i class="fas fa-calculator"></i> Total Payable</th>
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
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $row['loan_id']; ?></strong></td>
                        <td>
                            <span style="font-weight: 600; color: var(--primary-color);">
                                <?php echo $row['loan_code']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="member-info">
                                <span class="member-name"><?php echo $row['full_name']; ?></span>
                                <span class="member-code"><?php echo $row['member_code']; ?></span>
                            </div>
                        </td>
                        <td class="amount">৳ <?php echo number_format($row['principal_amount'], 2); ?></td>
                        <td><?php echo $row['interest_rate']; ?>%</td>
                        <td class="amount amount-primary">৳ <?php echo number_format($row['total_payable'], 2); ?></td>
                        <td class="amount" style="color: var(--success-color);">৳ <?php echo number_format($row['total_paid'], 2); ?></td>
                        <td>৳ <?php echo number_format($row['installment_amount'], 2); ?></td>
                        <td>
                            <?php
                            $status_badges = [
                                'active' => 'badge-success',
                                'closed' => 'badge-primary',
                                'overdue' => 'badge-danger',
                                'written_off' => 'badge-dark'
                            ];
                            $status_icons = [
                                'active' => 'fa-check-circle',
                                'closed' => 'fa-check-double',
                                'overdue' => 'fa-exclamation-triangle',
                                'written_off' => 'fa-times-circle'
                            ];
                            $badge_class = isset($status_badges[$row['status']]) ? $status_badges[$row['status']] : 'badge-dark';
                            $icon_class = isset($status_icons[$row['status']]) ? $status_icons[$row['status']] : 'fa-circle';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <i class="fas <?php echo $icon_class; ?>"></i>
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['disbursement_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['maturity_date'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $row['loan_id']; ?>" 
                                   class="btn btn-warning btn-sm" 
                                   data-tooltip="Edit Loan">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete.php?id=<?php echo $row['loan_id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this loan? This action cannot be undone!')"
                                   data-tooltip="Delete Loan">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                        <h5 style="color: var(--gray-color);">No loans found</h5>
                        <p style="color: #94a3b8; margin-bottom: 0;">Click "Add New Loan" to create your first loan</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>