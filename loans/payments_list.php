<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// GET ALL PAYMENTS
$sql = "
SELECT 
lp.*,
l.loan_code,
m.full_name,
m.member_code
FROM loan_payments lp
LEFT JOIN loans l ON lp.loan_id = l.loan_id
LEFT JOIN members m ON lp.member_id = m.member_id
ORDER BY lp.payment_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loan Payments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}
</style>
</head>

<body>

<div class="container mt-4">

<h3>Installment Payment History</h3>

<a href="payment.php" class="btn btn-primary mb-3">
+ Collect Payment
</a>

<table class="table table-bordered bg-white">

<thead>
<tr>
    <th>ID</th>
    <th>Loan Code</th>
    <th>Member</th>
    <th>Amount</th>
    <th>Payment Date</th>
    <th>Note</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row['payment_id']; ?></td>

    <td><?php echo $row['loan_code']; ?></td>

    <td>
        <?php echo $row['full_name']; ?>
        <br>
        <small><?php echo $row['member_code']; ?></small>
    </td>

    <td>৳ <?php echo number_format($row['amount'],2); ?></td>

    <td><?php echo $row['payment_date']; ?></td>

    <td><?php echo $row['note']; ?></td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</body>
</html>