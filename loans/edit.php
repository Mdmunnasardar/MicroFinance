<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

// GET LOAN
$loan = $conn->query("SELECT * FROM loans WHERE loan_id=$id");
$data = $loan->fetch_assoc();

if(isset($_POST['update'])){

    $principal = $_POST['principal_amount'];
    $rate = $_POST['interest_rate'];
    $term = $_POST['loan_term_months'];
    $status = $_POST['status'];

    $total_payable = $principal + ($principal * $rate / 100);
    $installment_amount = $total_payable / $term;

    $sql = "UPDATE loans SET
        principal_amount='$principal',
        interest_rate='$rate',
        loan_term_months='$term',
        status='$status',
        total_payable='$total_payable',
        installment_amount='$installment_amount'
        WHERE loan_id=$id
    ";

    $conn->query($sql);

    header("Location: index.php");
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

<input type="number" name="principal_amount" class="form-control mb-2"
value="<?php echo $data['principal_amount']; ?>" required>

<input type="number" step="0.01" name="interest_rate" class="form-control mb-2"
value="<?php echo $data['interest_rate']; ?>" required>

<input type="number" name="loan_term_months" class="form-control mb-2"
value="<?php echo $data['loan_term_months']; ?>" required>

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