<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Edit Loan';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get loan data
$loan = $conn->query("SELECT * FROM loans WHERE loan_id = $id")->fetch_assoc();
if (!$loan) {
    header("Location: index.php");
    exit();
}

// Get members
$members = $conn->query("SELECT member_id, full_name, member_code FROM members ORDER BY full_name");

if (isset($_POST['update'])) {
    $loan_code = sanitize($_POST['loan_code']);
    $principal = (float)$_POST['principal_amount'];
    $rate = (float)$_POST['interest_rate'];
    $interest_type = sanitize($_POST['interest_type']);
    $term = (int)$_POST['loan_term_months'];
    $installment_type = sanitize($_POST['installment_type']);
    $status = sanitize($_POST['status']);
    $purpose = sanitize($_POST['purpose']);
    
    // Recalculate
    if ($interest_type == 'flat') {
        $interest = $principal * ($rate / 100);
        $total_payable = $principal + $interest;
    } else {
        $interest = $principal * ($rate / 100);
        $total_payable = $principal + $interest;
    }
    
    $installment_amount = $term > 0 ? $total_payable / $term : 0;
    
    $sql = "UPDATE loans SET
        loan_code = '$loan_code',
        principal_amount = $principal,
        interest_rate = $rate,
        interest_type = '$interest_type',
        loan_term_months = $term,
        installment_type = '$installment_type',
        status = '$status',
        purpose = '$purpose',
        total_payable = $total_payable,
        installment_amount = $installment_amount
        WHERE loan_id = $id
    ";
    
    if ($conn->query($sql)) {
        header("Location: index.php?success=Loan updated successfully");
        exit();
    } else {
        $error = "Error updating loan: " . $conn->error;
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">

<div class="main-content">
    <div class="loan-form-container">
        <div class="loan-form-card">
            <div class="form-header">
                <h3><i class="fa-solid fa-pen" style="color: #f59e0b;"></i> Edit Loan</h3>
                <p>Update loan details. Changes will affect future calculations.</p>
            </div>
            
            <div class="form-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Loan Code <span class="required">*</span></label>
                            <input type="text" name="loan_code" class="form-control-custom" 
                                   value="<?php echo htmlspecialchars($loan['loan_code']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control-custom">
                                <option value="active" <?php echo $loan['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $loan['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="overdue" <?php echo $loan['status'] == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="closed" <?php echo $loan['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                <option value="written_off" <?php echo $loan['status'] == 'written_off' ? 'selected' : ''; ?>>Written Off</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Principal Amount <span class="required">*</span></label>
                            <input type="number" name="principal_amount" class="form-control-custom" 
                                   value="<?php echo $loan['principal_amount']; ?>" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Interest Rate (%) <span class="required">*</span></label>
                            <input type="number" name="interest_rate" class="form-control-custom" 
                                   value="<?php echo $loan['interest_rate']; ?>" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Interest Type</label>
                            <select name="interest_type" class="form-control-custom">
                                <option value="flat" <?php echo $loan['interest_type'] == 'flat' ? 'selected' : ''; ?>>Flat Rate</option>
                                <option value="reducing_balance" <?php echo $loan['interest_type'] == 'reducing_balance' ? 'selected' : ''; ?>>Reducing Balance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Loan Term (Months) <span class="required">*</span></label>
                            <input type="number" name="loan_term_months" class="form-control-custom" 
                                   value="<?php echo $loan['loan_term_months']; ?>" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Installment Type</label>
                            <select name="installment_type" class="form-control-custom">
                                <option value="monthly" <?php echo $loan['installment_type'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="weekly" <?php echo $loan['installment_type'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Loan Purpose</label>
                            <input type="text" name="purpose" class="form-control-custom" 
                                   value="<?php echo htmlspecialchars($loan['purpose'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Summary -->
                    <div class="loan-summary-box">
                        <div class="summary-item">
                            <div class="label">Total Payable</div>
                            <div class="value primary"><?php echo formatCurrency($loan['total_payable']); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Installment Amount</div>
                            <div class="value success"><?php echo formatCurrency($loan['installment_amount']); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Total Paid</div>
                            <div class="value"><?php echo formatCurrency($loan['total_paid']); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Remaining</div>
                            <div class="value danger"><?php echo formatCurrency($loan['total_payable'] - $loan['total_paid']); ?></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn-cancel">
                            <i class="fa-solid fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="update" class="btn-submit" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fa-solid fa-save"></i> Update Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>