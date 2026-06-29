<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$member_id = $_GET['id'];

// Get member info
$member = $conn->query("
    SELECT m.*, c.committee_name, b.branch_name
    FROM members m
    LEFT JOIN committees c ON m.committee_id = c.committee_id
    LEFT JOIN branches b ON m.branch_id = b.branch_id
    WHERE m.member_id = $member_id
")->fetch_assoc();

if (!$member) {
    header("Location: index.php?error=Member not found");
    exit();
}

// Get loans
$loans = $conn->query("SELECT * FROM loans WHERE member_id = $member_id ORDER BY loan_id DESC");

// Get savings
$savings = $conn->query("SELECT * FROM savings WHERE member_id = $member_id");

// Get payments
$payments = $conn->query("
    SELECT lp.*, l.loan_code
    FROM loan_payments lp
    LEFT JOIN loans l ON lp.loan_id = l.loan_id
    WHERE l.member_id = $member_id
    ORDER BY lp.payment_id DESC
");

// Get summary
$loan_total = $conn->query("SELECT COALESCE(SUM(principal_amount),0) as t FROM loans WHERE member_id=$member_id")->fetch_assoc()['t'] ?? 0;
$paid_total = $conn->query("SELECT COALESCE(SUM(total_paid),0) as t FROM loans WHERE member_id=$member_id")->fetch_assoc()['t'] ?? 0;
$due_total = $loan_total - $paid_total;
$saving_total = $conn->query("SELECT COALESCE(SUM(balance),0) as t FROM savings WHERE member_id=$member_id")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Members CSS -->
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<div class="container">

    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Members
    </a>

    <!-- Profile Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center gap-4">
                    <div class="avatar-circle-lg bg-primary text-white">
                        <?php echo strtoupper(substr($member['full_name'], 0, 2)); ?>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h3>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($member['member_code']); ?></p>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark me-2">
                                <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($member['phone']); ?>
                            </span>
                            <?php if ($member['email']): ?>
                                <span class="badge bg-light text-dark me-2">
                                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge <?php echo $member['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="edit.php?id=<?php echo $member_id; ?>" class="btn btn-warning">
                        <i class="fa-solid fa-pen"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Loans</h6>
                    <h4 class="text-primary">$<?php echo number_format($loan_total, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Paid</h6>
                    <h4 class="text-success">$<?php echo number_format($paid_total, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Due Amount</h6>
                    <h4 class="text-danger">$<?php echo number_format($due_total, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Savings</h6>
                    <h4 class="text-info">$<?php echo number_format($saving_total, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa-solid fa-hand-holding-usd text-primary"></i> Loans</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Maturity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($loans->num_rows > 0): ?>
                            <?php while ($l = $loans->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($l['loan_code']); ?></td>
                                    <td>$<?php echo number_format($l['principal_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($l['total_paid'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php
                                        echo $l['status'] == 'active' ? 'bg-success' :
                                            ($l['status'] == 'completed' ? 'bg-info' : 'bg-danger');
                                        ?>">
                                            <?php echo ucfirst($l['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($l['maturity_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No loans found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Savings Table -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa-solid fa-piggy-bank text-success"></i> Savings</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Balance</th>
                            <th>Last Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($savings->num_rows > 0): ?>
                            <?php while ($s = $savings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo ucfirst(htmlspecialchars($s['saving_type'])); ?></td>
                                    <td class="text-success fw-bold">$<?php echo number_format($s['balance'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($s['last_transaction_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No savings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa-solid fa-receipt text-purple"></i> Loan Payments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Loan</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while ($p = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['loan_code']); ?></td>
                                    <td class="fw-bold">$<?php echo number_format($p['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($p['note'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No payments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>