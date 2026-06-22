<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$sql = "
SELECT 
l.loan_code,
m.full_name,
l.principal_amount,
l.total_paid,
(l.principal_amount - l.total_paid) AS remaining
FROM loans l
LEFT JOIN members m ON l.member_id=m.member_id
WHERE (l.principal_amount - l.total_paid) > 0
AND l.status='active'
ORDER BY remaining DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Overdue Loans</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>🚨 Overdue Loans</h3>

<a href="index.php" class="btn btn-secondary mb-3">Back</a>

<table class="table table-bordered bg-white">

<tr>
<th>Loan</th>
<th>Member</th>
<th>Remaining</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['loan_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td class="text-danger">৳ <?php echo number_format($row['remaining']); ?></td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>