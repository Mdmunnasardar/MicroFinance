<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// INSERT PAYMENT
if(isset($_POST['submit'])){

    $loan_id = $_POST['loan_id'];
    $amount = $_POST['amount'];
    $date = $_POST['payment_date'];
    $note = $_POST['note'];

    // get member + update loan
    $loan = $conn->query("SELECT member_id, total_paid FROM loans WHERE loan_id=$loan_id");
    $l = $loan->fetch_assoc();

    $member_id = $l['member_id'];
    $new_paid = $l['total_paid'] + $amount;

    // insert payment
    $conn->query("
        INSERT INTO loan_payments (loan_id, member_id, amount, payment_date, note)
        VALUES ('$loan_id','$member_id','$amount','$date','$note')
    ");

    // update loan
    $conn->query("
        UPDATE loans 
        SET total_paid='$new_paid'
        WHERE loan_id=$loan_id
    ");

    header("Location: ../loans/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan Payment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Collect Installment</h3>

<form method="POST">

<!-- Loan -->
<select name="loan_id" class="form-control mb-2" required>
<option value="">Select Loan</option>

<?php
$loans = $conn->query("
SELECT l.loan_id, l.loan_code, m.full_name
FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
WHERE l.status='active'
");

while($l = $loans->fetch_assoc()){
?>
<option value="<?php echo $l['loan_id']; ?>">
    <?php echo $l['loan_code']; ?> - <?php echo $l['full_name']; ?>
</option>
<?php } ?>

</select>

<!-- Amount -->
<input type="number" name="amount" class="form-control mb-2" placeholder="Payment Amount" required>

<!-- Date -->
<input type="date" name="payment_date" class="form-control mb-2" required>

<!-- Note -->
<input type="text" name="note" class="form-control mb-2" placeholder="Note (optional)">

<button class="btn btn-success w-100" name="submit">
Collect Payment
</button>

</form>

</div>

</body>
</html>