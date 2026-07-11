<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Record Payment';
$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$loan = $conn->query("
    SELECT l.*, m.full_name, m.member_code 
    FROM loans l 
    LEFT JOIN members m ON l.member_id = m.member_id 
    WHERE l.loan_id = $loan_id
")->fetch_assoc();

if (!$loan) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $amount = (float)$_POST['amount'];
    $payment_date = sanitize($_POST['payment_date']);
    $method = sanitize($_POST['payment_method']);
    $reference = sanitize($_POST['reference'] ?? '');
    $installment_number = isset($_POST['installment_number']) ? (int)$_POST['installment_number'] : 0;
    
    // Insert payment
    $sql = "INSERT INTO loan_payments (loan_id, amount, payment_date, payment_method, reference)
            VALUES ($loan_id, $amount, '$payment_date', '$method', '$reference')";
    
    if ($conn->query($sql)) {
        // Update loan total paid
        $conn->query("UPDATE loans SET total_paid = total_paid + $amount WHERE loan_id = $loan_id");
        
        // Update installment status
        if ($installment_number > 0) {
            $conn->query("UPDATE loan_installments SET status = 'paid' 
                         WHERE loan_id = $loan_id AND installment_number = $installment_number");
        }
        
        header("Location: view.php?id=$loan_id&success=Payment recorded successfully");
        exit();
    } else {
        $error = "Error recording payment: " . $conn->error;
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">

<div class="main-content">
    <div class="loan-form-container" style="max-width: 600px;">
        <div class="loan-form-card">
            <div class="form-header">
                <h3><i class="fa-solid fa-money-bill-transfer" style="color: #22c55e;"></i> Record Payment</h3>
                <p>Loan: <?php echo $loan['loan_code']; ?> - <?php echo htmlspecialchars($loan['full_name']); ?></p>
            </div>
            
            <div class="form-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 12px;">
                    <div>
                        <div style="font-size: 12px; color: #94a3b8;">Total Payable</div>
                        <div style="font-size: 18px; font-weight: 700; color: #0f172a;"><?php echo formatCurrency($loan['total_payable']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #94a3b8;">Total Paid</div>
                        <div style="font-size: 18px; font-weight: 700; color: #22c55e;"><?php echo formatCurrency($loan['total_paid']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #94a3b8;">Remaining</div>
                        <div style="font-size: 18px; font-weight: 700; color: #ef4444;"><?php echo formatCurrency($loan['total_payable'] - $loan['total_paid']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #94a3b8;">Status</div>
                        <div><span class="status-badge <?php echo $loan['status']; ?>"><?php echo ucfirst($loan['status']); ?></span></div>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Payment Amount <span class="required">*</span></label>
                        <input type="number" name="amount" class="form-control-custom" 
                               placeholder="0.00" step="0.01" required 
                               max="<?php echo $loan['total_payable'] - $loan['total_paid']; ?>">
                        <span class="form-hint">Maximum: <?php echo formatCurrency($loan['total_payable'] - $loan['total_paid']); ?></span>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Payment Date <span class="required">*</span></label>
                        <input type="date" name="payment_date" class="form-control-custom" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control-custom">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="check">Check</option>
                            <option value="savings">From Savings</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Reference/Note</label>
                        <input type="text" name="reference" class="form-control-custom" 
                               placeholder="Receipt #, transaction ID, or note">
                    </div>
                    
                    <input type="hidden" name="installment_number" value="<?php echo isset($_GET['installment']) ? (int)$_GET['installment'] : 0; ?>">
                    
                    <div class="form-actions">
                        <a href="view.php?id=<?php echo $loan_id; ?>" class="btn-cancel">
                            <i class="fa-solid fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="submit" class="btn-submit" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                            <i class="fa-solid fa-check"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>