<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ======================
// INSERT LOAN
// ======================
if (isset($_POST['submit'])) {
    $loan_code = $_POST['loan_code'];
    $member_id = $_POST['member_id'];
    $principal = $_POST['principal_amount'];
    $rate = $_POST['interest_rate'];
    $interest_type = $_POST['interest_type'];
    $term = $_POST['loan_term_months'];
    $installment_type = $_POST['installment_type'];
    $disbursement_date = $_POST['disbursement_date'];
    $first_installment_date = $_POST['first_installment_date'];
    $purpose = $_POST['purpose'];

    // ======================
    // AUTO CALCULATION
    // ======================

    if ($interest_type == 'flat') {
        $interest = ($principal * $rate / 100);
        $total_payable = $principal + $interest;
    } else {
        $total_payable = $principal + ($principal * $rate / 100);
    }

    $installment_amount = $total_payable / $term;
    $maturity_date = date('Y-m-d', strtotime($disbursement_date . " + $term months"));

    $member = $conn->query("SELECT branch_id FROM members WHERE member_id=$member_id");
    $m = $member->fetch_assoc();
    $branch_id = $m['branch_id'];

    $sql = "INSERT INTO loans (
        loan_code, member_id, branch_id, principal_amount, interest_rate,
        interest_type, loan_term_months, installment_type, installment_amount,
        total_payable, total_paid, disbursement_date, first_installment_date,
        maturity_date, status, purpose
    ) VALUES (
        '$loan_code', '$member_id', '$branch_id', '$principal', '$rate',
        '$interest_type', '$term', '$installment_type', '$installment_amount',
        '$total_payable', 0, '$disbursement_date', '$first_installment_date',
        '$maturity_date', 'active', '$purpose'
    )";

    $conn->query($sql);
    header("Location: index.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Loan</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CRITICAL: Load custom CSS -->
    <link href="../assets/css/loans.css" rel="stylesheet">
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="container">
    <div class="form-wrapper">
        <div class="form-title">
            <div class="icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <span>Create New Loan</span>
        </div>
        
        <form method="POST" id="loanForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-barcode"></i> Loan Code
                    </label>
                    <input type="text" name="loan_code" class="form-control" 
                           placeholder="e.g., L001" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Select Member
                    </label>
                    <select name="member_id" class="form-control" required>
                        <option value="">Select Member</option>
                        <?php
                        $members = $conn->query("SELECT * FROM members ORDER BY full_name");
                        while($m = $members->fetch_assoc()) {
                        ?>
                        <option value="<?php echo $m['member_id']; ?>">
                            <?php echo $m['full_name']; ?> (<?php echo $m['member_code']; ?>)
                        </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-money-bill-wave"></i> Principal Amount
                    </label>
                    <input type="number" name="principal_amount" class="form-control" 
                           placeholder="Enter principal amount" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-percent"></i> Interest Rate
                    </label>
                    <input type="number" step="0.01" name="interest_rate" class="form-control" 
                           placeholder="Enter interest rate" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calculator"></i> Interest Type
                    </label>
                    <select name="interest_type" class="form-control">
                        <option value="flat">Flat Rate</option>
                        <option value="reducing_balance">Reducing Balance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i> Loan Term (Months)
                    </label>
                    <input type="number" name="loan_term_months" class="form-control" 
                           placeholder="e.g., 12" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i> Installment Type
                    </label>
                    <select name="installment_type" class="form-control">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-day"></i> Disbursement Date
                    </label>
                    <input type="date" name="disbursement_date" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-check"></i> First Installment Date
                    </label>
                    <input type="date" name="first_installment_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-info-circle"></i> Loan Purpose
                    </label>
                    <input type="text" name="purpose" class="form-control" 
                           placeholder="Enter loan purpose">
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-success" 
                    style="width: 100%; padding: 14px; font-size: 16px; margin-top: var(--space-md);">
                <i class="fas fa-save"></i> Create Loan
            </button>
            
            <a href="index.php" class="btn btn-secondary" 
               style="width: 100%; margin-top: var(--space-md); padding: 12px;">
                <i class="fas fa-arrow-left"></i> Back to Loan List
            </a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('loanForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    btn.disabled = true;
});
</script>
</body>
</html>