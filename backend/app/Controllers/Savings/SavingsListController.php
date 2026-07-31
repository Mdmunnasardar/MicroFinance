<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$result = $conn->query("
SELECT
s.saving_id,
s.saving_type,
s.balance,
s.last_transaction_date,

m.full_name,
m.member_code

FROM savings s
LEFT JOIN members m
ON s.member_id = m.member_id

ORDER BY s.saving_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Savings Accounts</title>

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

<h3 class="mb-3">💰 Savings Accounts</h3>

<!-- ACTION BUTTONS -->
<div class="mb-3">

<a href="add.php" class="btn btn-primary">+ Add Account</a>
<a href="deposit.php" class="btn btn-success">Deposit</a>
<a href="withdraw.php" class="btn btn-warning">Withdraw</a>
<a href="transactions.php" class="btn btn-info text-white">Transactions</a>

</div>

<!-- TABLE -->
<div class="card-box">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Member</th>
<th>Type</th>
<th>Balance</th>
<th>Last Transaction</th>
</tr>

</thead>

<tbody>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>

<td><?php echo $row['saving_id']; ?></td>

<td>
<b><?php echo $row['full_name']; ?></b><br>
<small><?php echo $row['member_code']; ?></small>
</td>

<td>
<?php echo ucfirst($row['saving_type']); ?>
</td>

<td>
<b>৳ <?php echo number_format($row['balance'],2); ?></b>
</td>

<td>
<?php echo $row['last_transaction_date']; ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>
</html>