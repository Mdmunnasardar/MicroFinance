<?php
include "../config/db.php";

$total = $conn->query("SELECT SUM(principal_amount) as t FROM loans")->fetch_assoc()['t'] ?? 0;
$paid = $conn->query("SELECT SUM(total_paid) as t FROM loans")->fetch_assoc()['t'] ?? 0;

$remaining = $total - $paid;
?>

<!DOCTYPE html>
<html>
<head>
<title>Due Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>📊 Due Summary Report</h3>

<div class="card p-3">

<p>Total Loan: <b>৳ <?php echo number_format($total); ?></b></p>
<p>Total Paid: <b>৳ <?php echo number_format($paid); ?></b></p>
<p>Remaining Due: <b class="text-danger">৳ <?php echo number_format($remaining); ?></b></p>

</div>

</div>

</body>
</html>