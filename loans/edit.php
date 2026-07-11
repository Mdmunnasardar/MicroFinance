<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];
$data = $conn->query("SELECT * FROM loans WHERE loan_id=$id")->fetch_assoc();

if(isset($_POST['update'])){
    $loan_code = $_POST['loan_code'];
    $principal = $_POST['principal_amount'];
    $rate = $_POST['interest_rate'];
    $term = $_POST['loan_term_months'];
    $status = $_POST['status'];
    $purpose = $_POST['purpose'];
    $interest_type = $_POST['interest_type'];
    $installment_type = $_POST['installment_type'];

    $total_payable = $principal + ($principal * $rate / 100);
    $installment_amount = $total_payable / $term;

    $sql = "UPDATE loans SET
        loan_code='$loan_code',
        principal_amount='$principal',
        interest_rate='$rate',
        interest_type='$interest_type',
        loan_term_months='$term',
        installment_type='$installment_type',
        status='$status',
        purpose='$purpose',
        total_payable='$total_payable',
        installment_amount='$installment_amount'
        WHERE loan_id=$id
    ";

    $conn->query($sql);
    header("Location: index.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Loan</title>
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
                <i class="fas fa-edit"></i>
            </div>
            <span>Edit Loan #<?php echo $data['loan_code']; ?></span>
        </div>
        
        <form method="POST" id="editForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-barcode"></i> Loan Code
                    </label>
                    <input type="text" name="loan_code" class="form-control" 
                           value="<?php echo $data['loan_code']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-money-bill-wave"></i> Principal Amount
                    </label>
                    <input type="number" name="principal_amount" class="form-control" 
                           value="<?php echo $data['principal_amount']; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-percent"></i> Interest Rate
                    </label>
                    <input type="number" step="0.01" name="interest_rate" class="form-control" 
                           value="<?php echo $data['interest_rate']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calculator"></i> Interest Type
                    </label>
                    <select name="interest_type" class="form-control">
                        <option value="flat" <?php if($data['interest_type']=='flat') echo 'selected'; ?>>Flat Rate</option>
                        <option value="reducing_balance" <?php if($data['interest_type']=='reducing_balance') echo 'selected'; ?>>Reducing Balance</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i> Loan Term (Months)
                    </label>
                    <input type="number" name="loan_term_months" class="form-control" 
                           value="<?php echo $data['loan_term_months']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i> Installment Type
                    </label>
                    <select name="installment_type" class="form-control">
                        <option value="monthly" <?php if($data['installment_type']=='monthly') echo 'selected'; ?>>Monthly</option>
                        <option value="weekly" <?php if($data['installment_type']=='weekly') echo 'selected'; ?>>Weekly</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-info-circle"></i> Loan Purpose
                    </label>
                    <input type="text" name="purpose" class="form-control" 
                           value="<?php echo $data['purpose']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-flag"></i> Status
                    </label>
                    <select name="status" class="form-control">
                        <option value="active" <?php if($data['status']=='active') echo 'selected'; ?>>Active</option>
                        <option value="closed" <?php if($data['status']=='closed') echo 'selected'; ?>>Closed</option>
                        <option value="overdue" <?php if($data['status']=='overdue') echo 'selected'; ?>>Overdue</option>
                        <option value="written_off" <?php if($data['status']=='written_off') echo 'selected'; ?>>Written Off</option>
                    </select>
                </div>
            </div>

            <button type="submit" name="update" class="btn btn-primary" 
                    style="width: 100%; padding: 14px; font-size: 16px; margin-top: var(--space-md);">
                <i class="fas fa-save"></i> Update Loan
            </button>
            
            <a href="index.php" class="btn btn-secondary" 
               style="width: 100%; margin-top: var(--space-md); padding: 12px;">
                <i class="fas fa-arrow-left"></i> Back to Loan List
            </a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>