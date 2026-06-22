<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$member_id = $_GET['id'];

/* ======================
   MEMBER INFO
====================== */
$member = $conn->query("
SELECT m.*, c.committee_name, b.branch_name
FROM members m
LEFT JOIN committees c ON m.committee_id = c.committee_id
LEFT JOIN branches b ON m.branch_id = b.branch_id
WHERE m.member_id = $member_id
")->fetch_assoc();

/* ======================
   LOANS
====================== */
$loans = $conn->query("
SELECT * FROM loans
WHERE member_id = $member_id
ORDER BY loan_id DESC
");

/* ======================
   SAVINGS
====================== */
$savings = $conn->query("
SELECT * FROM savings
WHERE member_id = $member_id
");

/* ======================
   PAYMENTS
====================== */
$payments = $conn->query("
SELECT lp.*
FROM loan_payments lp
LEFT JOIN loans l ON lp.loan_id = l.loan_id
WHERE l.member_id = $member_id
ORDER BY lp.payment_id DESC
");

/* ======================
   SUMMARY
====================== */
$loan_total = $conn->query("
SELECT SUM(principal_amount) AS t FROM loans WHERE member_id=$member_id
")->fetch_assoc()['t'] ?? 0;

$paid_total = $conn->query("
SELECT SUM(total_paid) AS t FROM loans WHERE member_id=$member_id
")->fetch_assoc()['t'] ?? 0;

$due_total = $loan_total - $paid_total;

$saving_total = $conn->query("
SELECT SUM(balance) AS t FROM savings WHERE member_id=$member_id
")->fetch_assoc()['t'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Member Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
    font-family:Segoe UI;
}

.card-box{
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
</style>

</head>

<body>

<div class="container mt-4">

<!-- BACK -->
<a href="index.php" class="btn btn-secondary mb-3">← Back</a>

<!-- MEMBER INFO -->
<div class="card-box mb-3">

<h3><?php echo $member['full_name']; ?></h3>

<p>
<b>Member Code:</b> <?php echo $member['member_code']; ?><br>
<b>Phone:</b> <?php echo $member['phone']; ?><br>
<b>Branch:</b> <?php echo $member['branch_name']; ?><br>
<b>Committee:</b> <?php echo $member['committee_name']; ?><br>
<b>Join Date:</b> <?php echo $member['join_date']; ?>
</p>

</div>

<!-- SUMMARY -->
<div class="row mb-3">

<div class="col-md-4">
<div class="card-box text-center">
<h5>Total Loan</h5>
<h4>৳ <?php echo number_format($loan_total); ?></h4>
</div>
</div>

<div class="col-md-4">
<div class="card-box text-center">
<h5>Total Paid</h5>
<h4>৳ <?php echo number_format($paid_total); ?></h4>
</div>
</div>

<div class="col-md-4">
<div class="card-box text-center">
<h5>Due</h5>
<h4 style="color:red;">৳ <?php echo number_format($due_total); ?></h4>
</div>
</div>

</div>

<div class="row mb-3">

<div class="col-md-4">
<div class="card-box text-center">
<h5>Savings</h5>
<h4 style="color:green;">৳ <?php echo number_format($saving_total); ?></h4>
</div>
</div>

</div>

<!-- LOANS -->
<div class="card-box mb-3">

<h4>Loans</h4>

<table class="table table-bordered">
<tr>
<th>Code</th>
<th>Amount</th>
<th>Paid</th>
<th>Status</th>
</tr>

<?php while($l = $loans->fetch_assoc()) { ?>
<tr>
<td><?php echo $l['loan_code']; ?></td>
<td>৳ <?php echo $l['principal_amount']; ?></td>
<td>৳ <?php echo $l['total_paid']; ?></td>
<td><?php echo $l['status']; ?></td>
</tr>
<?php } ?>

</table>

</div>

<!-- SAVINGS -->
<div class="card-box mb-3">

<h4>Savings</h4>

<table class="table table-bordered">
<tr>
<th>Type</th>
<th>Balance</th>
<th>Last Update</th>
</tr>

<?php while($s = $savings->fetch_assoc()) { ?>
<tr>
<td><?php echo $s['saving_type']; ?></td>
<td>৳ <?php echo $s['balance']; ?></td>
<td><?php echo $s['last_transaction_date']; ?></td>
</tr>
<?php } ?>

</table>

</div>

<!-- PAYMENTS -->
<div class="card-box">

<h4>Loan Payments</h4>

<table class="table table-bordered">
<tr>
<th>Amount</th>
<th>Date</th>
<th>Note</th>
</tr>

<?php while($p = $payments->fetch_assoc()) { ?>
<tr>
<td>৳ <?php echo $p['amount']; ?></td>
<td><?php echo $p['payment_date']; ?></td>
<td><?php echo $p['note']; ?></td>
</tr>
<?php } ?>

</table>

</div>

</div>

</body>
</html>