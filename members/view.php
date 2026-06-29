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
    <title>Member Profile - MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>
<?php include "../includes/topbar.php"; ?>

<div class="main-content">
    <div class="container mx-auto px-4 py-8">
        
        <!-- Back Button -->
        <a href="index.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-6">
            <i class="fas fa-arrow-left"></i> Back to Members
        </a>

        <!-- Profile Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-3xl font-bold text-indigo-600">
                        <?php echo strtoupper(substr($member['full_name'], 0, 2)); ?>
                    </span>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($member['full_name']); ?></h1>
                    <p class="text-gray-500"><?php echo htmlspecialchars($member['member_code']); ?></p>
                    <div class="flex flex-wrap gap-3 mt-3 justify-center md:justify-start">
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                            <i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($member['phone']); ?>
                        </span>
                        <?php if($member['email']): ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                            <i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($member['email']); ?>
                        </span>
                        <?php endif; ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                            <i class="fas fa-building mr-2"></i> <?php echo htmlspecialchars($member['branch_name'] ?? 'N/A'); ?>
                        </span>
                        <span class="px-3 py-1 <?php echo $member['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?> text-sm rounded-full">
                            <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="edit.php?id=<?php echo $member_id; ?>" 
                       class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-pen"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <p class="text-gray-500 text-sm">Total Loans</p>
                <p class="text-2xl font-bold text-blue-600">$<?php echo number_format($loan_total, 2); ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <p class="text-gray-500 text-sm">Total Paid</p>
                <p class="text-2xl font-bold text-emerald-600">$<?php echo number_format($paid_total, 2); ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <p class="text-gray-500 text-sm">Due Amount</p>
                <p class="text-2xl font-bold text-red-600">$<?php echo number_format($due_total, 2); ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <p class="text-gray-500 text-sm">Total Savings</p>
                <p class="text-2xl font-bold text-indigo-600">$<?php echo number_format($saving_total, 2); ?></p>
            </div>
        </div>

        <!-- Loans Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-hand-holding-usd text-blue-600"></i>
                Loans
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Code</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Amount</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Paid</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Status</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Maturity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($loans->num_rows > 0): ?>
                            <?php while($l = $loans->fetch_assoc()): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3"><?php echo htmlspecialchars($l['loan_code']); ?></td>
                                <td class="py-3">$<?php echo number_format($l['principal_amount'], 2); ?></td>
                                <td class="py-3">$<?php echo number_format($l['total_paid'], 2); ?></td>
                                <td class="py-3">
                                    <span class="px-3 py-1 text-xs rounded-full <?php 
                                        echo $l['status'] == 'active' ? 'bg-emerald-100 text-emerald-700' : 
                                            ($l['status'] == 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'); 
                                    ?>">
                                        <?php echo ucfirst($l['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3"><?php echo date('M d, Y', strtotime($l['maturity_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No loans found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Savings Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-piggy-bank text-emerald-600"></i>
                Savings
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Type</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Balance</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Last Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($savings->num_rows > 0): ?>
                            <?php while($s = $savings->fetch_assoc()): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3"><?php echo ucfirst(htmlspecialchars($s['saving_type'])); ?></td>
                                <td class="py-3 font-semibold text-emerald-600">$<?php echo number_format($s['balance'], 2); ?></td>
                                <td class="py-3"><?php echo date('M d, Y', strtotime($s['last_transaction_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500">No savings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-receipt text-purple-600"></i>
                Loan Payments
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Loan</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Amount</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Date</th>
                            <th class="text-left py-3 text-sm font-semibold text-gray-600">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($payments->num_rows > 0): ?>
                            <?php while($p = $payments->fetch_assoc()): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3"><?php echo htmlspecialchars($p['loan_code']); ?></td>
                                <td class="py-3 font-semibold">$<?php echo number_format($p['amount'], 2); ?></td>
                                <td class="py-3"><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                <td class="py-3 text-gray-500"><?php echo htmlspecialchars($p['note'] ?? '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-500">No payments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>

</body>
</html>