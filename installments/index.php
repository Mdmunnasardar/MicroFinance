<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$result = $conn->query("
SELECT lp.*, l.loan_code, m.full_name, m.member_code
FROM loan_payments lp
LEFT JOIN loans l ON lp.loan_id = l.loan_id
LEFT JOIN members m ON lp.member_id = m.member_id
ORDER BY lp.payment_id DESC
LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Installment Quick View</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Recent Payments</h3>

<a href="payment.php" class="btn btn-primary mb-3">Collect New</a>
<a href="payment_list.php" class="btn btn-secondary mb-3">View All</a>

<table class="table table-bordered bg-white">

<tr>
<th>Loan</th>
<th>Member</th>
<th>Amount</th>
<th>Date</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['loan_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td>৳ <?php echo $row['amount']; ?></td>
<td><?php echo $row['payment_date']; ?></td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>