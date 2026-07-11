<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Loan Details';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$loan = $conn->query("
    SELECT l.*, m.full_name, m.member_code, m.phone, m.email,
           m.address, m.member_id
    FROM loans l
    LEFT JOIN members m ON l.member_id = m.member_id
    WHERE l.loan_id = $id
")->fetch_assoc();

if (!$loan) {
    header("Location: index.php");
    exit();
}

// Get installments
$installments = $conn->query("
    SELECT * FROM loan_installments 
    WHERE loan_id = $id 
    ORDER BY installment_number
");

// Get payments
$payments = $conn->query("
    SELECT * FROM loan_payments 
    WHERE loan_id = $id 
    ORDER BY payment_date DESC
    LIMIT 10
");

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>
.loan-detail-header {
    background: white;
    border-radius: 16px;
    padding: 24px 32px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
}

.loan-detail-header .loan-id {
    font-size: 14px;
    color: #64748b;
}

.loan-detail-header .loan-id strong {
    color: #4f46e5;
}

.loan-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.loan-detail-grid .detail-item {
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
}

.loan-detail-grid .detail-item .label {
    font-size: 11px;
    text-transform: uppercase;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.loan-detail-grid .detail-item .value {
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
    margin-top: 4px;
}

.installment-table {
    width: 100%;
    border-collapse: collapse;
}

.installment-table th {
    background: #f8fafc;
    padding: 10px 14px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
}

.installment-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.status-badge.paid {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.pending {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.overdue {
    background: #fee2e2;
    color: #dc2626;
}
</style>

<div class="main-content">
    <div class="loan-dashboard">
        
        <!-- Header -->
        <div class="dashboard-top">
            <div class="welcome-section">
                <h1>Loan Details</h1>
                <p>Complete overview of loan <?php echo $loan['loan_code']; ?></p>
            </div>
            <div class="quick-actions">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn-quick btn-quick-info">
                    <i class="fa-solid fa-pen"></i> Edit
                </a>
                <a href="payment.php?id=<?php echo $id; ?>" class="btn-quick btn-quick-success">
                    <i class="fa-solid fa-money-bill-transfer"></i> Record Payment
                </a>
                <a href="index.php" class="btn-quick" style="background: #f1f5f9; color: #64748b;">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <!-- Loan Info Card -->
        <div class="loan-detail-header">
            <div class="loan-id">
                <i class="fa-regular fa-file-lines"></i> Loan ID: <strong>#<?php echo $loan['loan_code']; ?></strong>
                <span class="status-badge <?php echo $loan['status']; ?>" style="margin-left: 12px;">
                    <i class="fa-solid fa-circle"></i> <?php echo ucfirst($loan['status']); ?>
                </span>
            </div>
            
            <div class="loan-detail-grid">
                <div class="detail-item">
                    <div class="label">Member</div>
                    <div class="value"><?php echo htmlspecialchars($loan['full_name']); ?></div>
                    <div style="font-size: 13px; color: #94a3b8;"><?php echo $loan['member_code']; ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Principal Amount</div>
                    <div class="value" style="color: #4f46e5;"><?php echo formatCurrency($loan['principal_amount']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Interest Rate</div>
                    <div class="value"><?php echo $loan['interest_rate']; ?>%</div>
                    <div style="font-size: 13px; color: #94a3b8;"><?php echo ucfirst(str_replace('_', ' ', $loan['interest_type'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Payable</div>
                    <div class="value"><?php echo formatCurrency($loan['total_payable']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Paid</div>
                    <div class="value" style="color: #22c55e;"><?php echo formatCurrency($loan['total_paid']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Remaining</div>
                    <div class="value" style="color: <?php echo ($loan['total_payable'] - $loan['total_paid']) > 0 ? '#ef4444' : '#22c55e'; ?>;">
                        <?php echo formatCurrency($loan['total_payable'] - $loan['total_paid']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="label">Installment</div>
                    <div class="value"><?php echo formatCurrency($loan['installment_amount']); ?></div>
                    <div style="font-size: 13px; color: #94a3b8;"><?php echo ucfirst($loan['installment_type']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Maturity Date</div>
                    <div class="value"><?php echo date('M d, Y', strtotime($loan['maturity_date'])); ?></div>
                </div>
            </div>
            
            <?php if ($loan['purpose']): ?>
            <div style="margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 10px;">
                <span style="font-size: 12px; color: #64748b;">Purpose:</span>
                <span style="font-size: 14px; color: #0f172a;"><?php echo htmlspecialchars($loan['purpose']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Installments -->
        <div class="loan-list-container" style="margin-bottom: 24px;">
            <div class="loan-list-header">
                <div class="header-left">
                    <h4><i class="fa-solid fa-list-check"></i> Installment Schedule</h4>
                    <span class="total-count"><?php echo $installments->num_rows; ?> installments</span>
                </div>
            </div>
            <div class="loan-table-wrap">
                <table class="installment-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($installments && $installments->num_rows > 0): ?>
                            <?php while($row = $installments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['installment_number']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['status']; ?>">
                                        <i class="fa-solid fa-circle"></i>
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending' || $row['status'] == 'overdue'): ?>
                                        <a href="payment.php?id=<?php echo $id; ?>&installment=<?php echo $row['installment_number']; ?>" 
                                           class="action-btn payment" title="Pay this installment">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    No installments found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="loan-list-container">
            <div class="loan-list-header">
                <div class="header-left">
                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Payment History</h4>
                    <span class="total-count"><?php echo $payments->num_rows; ?> payments</span>
                </div>
            </div>
            <div class="loan-table-wrap">
                <table class="installment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments && $payments->num_rows > 0): ?>
                            <?php while($row = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                <td style="color: #22c55e; font-weight: 600;"><?php echo formatCurrency($row['amount']); ?></td>
                                <td><?php echo ucfirst($row['payment_method'] ?? 'Cash'); ?></td>
                                <td><?php echo $row['reference'] ?? 'N/A'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    No payments recorded yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<?php include "../includes/footer.php"; ?>