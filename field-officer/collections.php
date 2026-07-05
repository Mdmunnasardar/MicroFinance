<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'field_officer') {
    header("Location: ../login.php");
    exit();
}

$officer_id = $_SESSION['user_id'];

// Get officer name
$sql = "SELECT full_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$officer = $stmt->get_result()->fetch_assoc();

// Handle payment collection
if (isset($_POST['collect_payment'])) {
    $loan_id = (int)$_POST['loan_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    
    $insert_sql = "INSERT INTO loan_payments (loan_id, amount, payment_date, collected_by) 
                   VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("idsi", $loan_id, $amount, $payment_date, $officer_id);
    $stmt->execute();
    
    $update_sql = "UPDATE loans SET total_paid = total_paid + ? WHERE loan_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("di", $amount, $loan_id);
    $stmt->execute();
    
    header("Location: collections.php?success=1");
    exit();
}

// Get members with due payments
$members_sql = "
SELECT DISTINCT m.member_id, m.full_name, m.member_code, m.phone
FROM members m
JOIN committees c ON m.committee_id = c.committee_id
JOIN loans l ON m.member_id = l.member_id
WHERE c.field_officer_id = ? 
  AND l.status = 'active' 
  AND l.next_due_date <= CURDATE()
ORDER BY m.full_name
";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$members = $stmt->get_result();

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <div class="page-header">
        <div class="header-left">
            <div class="header-icon warning">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <h1 class="header-title">Collect Payments</h1>
                <p class="header-subtitle"><?php echo htmlspecialchars($officer['full_name']); ?></p>
            </div>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-success-bg border-l-4 border-success text-success-dark p-4 rounded-xl mb-6">
        <i class="fas fa-check-circle mr-2"></i> Payment collected successfully!
    </div>
    <?php endif; ?>

    <?php if ($members->num_rows > 0): ?>
    <div class="grid grid-cols-3 gap-6">
        <?php while($member = $members->fetch_assoc()): ?>
        <div class="detail-section">
            <div class="flex items-center gap-3 mb-3">
                <div class="avatar" style="width: 48px; height: 48px; font-size: 16px;">
                    <?php 
                    $initial = strtoupper(substr($member['full_name'], 0, 2));
                    if (strpos($member['full_name'], ' ') !== false) {
                        $names = explode(' ', $member['full_name']);
                        $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                    }
                    echo $initial;
                    ?>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($member['full_name']); ?></h4>
                    <p class="text-sm text-gray-500"><?php echo $member['member_code']; ?></p>
                </div>
            </div>

            <?php
            $loan_sql = "
            SELECT l.loan_id, l.principal_amount, l.total_paid,
                   l.principal_amount - l.total_paid as due_amount,
                   l.next_due_date
            FROM loans l
            WHERE l.member_id = ? AND l.status = 'active'
            ORDER BY l.next_due_date ASC
            LIMIT 1
            ";
            $stmt = $conn->prepare($loan_sql);
            $stmt->bind_param("i", $member['member_id']);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            
            if ($loan):
            ?>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Due Amount:</span>
                    <span class="font-bold text-danger">$<?php echo number_format($loan['due_amount'], 2); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Due Date:</span>
                    <span><?php echo date('d M Y', strtotime($loan['next_due_date'])); ?></span>
                </div>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                    <input type="hidden" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
                    <div class="flex gap-2">
                        <input type="number" name="amount" class="form-control" 
                               placeholder="Amount" step="0.01" required
                               value="<?php echo number_format($loan['due_amount'], 2); ?>">
                        <button type="submit" name="collect_payment" class="btn btn-success">
                            <i class="fas fa-hand-holding-usd"></i> Collect
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-check-circle text-success"></i></div>
        <h3 class="empty-title">No Pending Collections</h3>
        <p class="empty-description">All members are up to date with their payments! 🎉</p>
    </div>
    <?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>