<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$sql = "
SELECT 
l.loan_id,
l.loan_code,
l.principal_amount,
l.total_paid,
m.full_name,

(l.principal_amount - l.total_paid) AS remaining

FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
ORDER BY remaining DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Due System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>📊 Due List</h3>

<a href="overdue.php" class="btn btn-danger mb-3">Overdue</a>
<a href="report.php" class="btn btn-info mb-3">Report</a>

<table class="table table-bordered bg-white">

<tr>
<th>Loan Code</th>
<th>Member</th>
<th>Loan</th>
<th>Paid</th>
<th>Remaining</th>
<th>Status</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['loan_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>

<td>৳ <?php echo number_format($row['principal_amount']); ?></td>
<td>৳ <?php echo number_format($row['total_paid']); ?></td>

<td><b>৳ <?php echo number_format($row['remaining']); ?></b></td>

<td>
<?php
if($row['remaining'] <= 0){
    echo "<span class='badge bg-success'>Paid</span>";
}
else if($row['remaining'] < 5000){
    echo "<span class='badge bg-warning'>Near Close</span>";
}
else{
    echo "<span class='badge bg-danger'>Due</span>";
}
?>
</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>