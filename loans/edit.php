<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

// GET LOAN
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

    // recalc
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

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Loan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Edit Loan</h3>

<form method="POST">

<!-- Loan Code -->
<input type="text" name="loan_code" class="form-control mb-2"
value="<?php echo $data['loan_code']; ?>" required>

<!-- Principal -->
<input type="number" name="principal_amount" class="form-control mb-2"
value="<?php echo $data['principal_amount']; ?>" required>

<!-- Interest -->
<input type="number" step="0.01" name="interest_rate" class="form-control mb-2"
value="<?php echo $data['interest_rate']; ?>" required>

<!-- Interest Type -->
<select name="interest_type" class="form-control mb-2">
    <option value="flat" <?php if($data['interest_type']=='flat') echo 'selected'; ?>>Flat</option>
    <option value="reducing_balance" <?php if($data['interest_type']=='reducing_balance') echo 'selected'; ?>>Reducing Balance</option>
</select>

<!-- Term -->
<input type="number" name="loan_term_months" class="form-control mb-2"
value="<?php echo $data['loan_term_months']; ?>" required>

<!-- Installment Type -->
<select name="installment_type" class="form-control mb-2">
    <option value="monthly" <?php if($data['installment_type']=='monthly') echo 'selected'; ?>>Monthly</option>
    <option value="weekly" <?php if($data['installment_type']=='weekly') echo 'selected'; ?>>Weekly</option>
</select>

<!-- Purpose -->
<input type="text" name="purpose" class="form-control mb-2"
value="<?php echo $data['purpose']; ?>">

<!-- Status -->
<select name="status" class="form-control mb-2">
    <option value="active" <?php if($data['status']=='active') echo 'selected'; ?>>Active</option>
    <option value="closed" <?php if($data['status']=='closed') echo 'selected'; ?>>Closed</option>
    <option value="overdue" <?php if($data['status']=='overdue') echo 'selected'; ?>>Overdue</option>
    <option value="written_off" <?php if($data['status']=='written_off') echo 'selected'; ?>>Written Off</option>
</select>

<button type="submit" name="update" class="btn btn-success w-100">
Update Loan
</button>

</form>

</div>

</body>
</html>