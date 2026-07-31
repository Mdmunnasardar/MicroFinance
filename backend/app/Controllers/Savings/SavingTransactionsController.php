<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$result = $conn->query("
SELECT
st.txn_id,
st.type,
st.amount,
st.balance_after,
st.txn_date,
st.notes,

m.full_name,
m.member_code

FROM savings_transactions st

LEFT JOIN savings s
ON st.saving_id = s.saving_id

LEFT JOIN members m
ON s.member_id = m.member_id

ORDER BY st.txn_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Savings Transactions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}
</style>

</head>

<body>

<div class="container mt-4">

<h3>Savings Transaction History</h3>

<div class="mb-3">

<a href="deposit.php" class="btn btn-success">
+ Deposit
</a>

<a href="withdraw.php" class="btn btn-warning">
Withdraw
</a>

<a href="index.php" class="btn btn-secondary">
Back
</a>

</div>

<table class="table table-bordered bg-white">

<thead>
<tr>
<th>ID</th>
<th>Member</th>
<th>Type</th>
<th>Amount</th>
<th>Balance After</th>
<th>Date</th>
<th>Notes</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>

<td><?php echo $row['txn_id']; ?></td>

<td>
<?php echo $row['full_name']; ?>
<br>
<small><?php echo $row['member_code']; ?></small>
</td>

<td>
<?php
if($row['type']=='deposit'){
    echo '<span class="badge bg-success">Deposit</span>';
}else{
    echo '<span class="badge bg-danger">Withdrawal</span>';
}
?>
</td>

<td>
৳ <?php echo number_format($row['amount'],2); ?>
</td>

<td>
৳ <?php echo number_format($row['balance_after'],2); ?>
</td>

<td>
<?php echo $row['txn_date']; ?>
</td>

<td>
<?php echo $row['notes']; ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</body>
</html>