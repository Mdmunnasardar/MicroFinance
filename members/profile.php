<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$member_id = $_GET['id'] ?? 0;

/* =====================
   MEMBER INFO
===================== */
$member = $conn->query("
SELECT * FROM members WHERE member_id=$member_id
")->fetch_assoc();

if(!$member){
    echo "Member not found";
    exit();
}

/* =====================
   LOANS
===================== */
$loans = $conn->query("
SELECT 
l.*,
(l.principal_amount - l.total_paid) AS remaining
FROM loans l
WHERE l.member_id=$member_id
ORDER BY l.loan_id DESC
");

/* =====================
   TOTAL SUMMARY
===================== */
$summary = $conn->query("
SELECT 
SUM(principal_amount) as total_loan,
SUM(total_paid) as total_paid
FROM loans
WHERE member_id=$member_id
")->fetch_assoc();

$total_loan = $summary['total_loan'] ?? 0;
$total_paid = $summary['total_paid'] ?? 0;
$due = $total_loan - $total_paid;
?>

<!DOCTYPE html>
<html>
<head>
<title>Member Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<!-- BACK -->
<a href="index.php" class="btn btn-secondary mb-3">⬅ Back</a>

<!-- MEMBER INFO -->
<div class="card p-3 mb-3">
    <h3><?php echo $member['full_name']; ?></h3>
    <p>
        <b>Code:</b> <?php echo $member['member_code']; ?> <br>
        <b>Phone:</b> <?php echo $member['phone']; ?> <br>
        <b>DOB:</b> <?php echo $member['dob']; ?> <br>
    </p>
</div>

<!-- SUMMARY -->
<div class="row mb-3">

<div class="col-md-4">
    <div class="card p-3 text-center">
        <h5>Total Loan</h5>
        <h4>৳ <?php echo number_format($total_loan); ?></h4>
    </div>
</div>

<div class="col-md-4">
    <div class="card p-3 text-center">
        <h5>Total Paid</h5>
        <h4>৳ <?php echo number_format($total_paid); ?></h4>
    </div>
</div>

<div class="col-md-4">
    <div class="card p-3 text-center text-danger">
        <h5>Total Due</h5>
        <h4>৳ <?php echo number_format($due); ?></h4>
    </div>
</div>

</div>

<!-- LOAN LIST -->
<div class="card p-3">

<h4>Loan History</h4>

<table class="table table-bordered">

<tr>
<th>Loan Code</th>
<th>Principal</th>
<th>Paid</th>
<th>Remaining</th>
<th>Status</th>
</tr>

<?php while($row = $loans->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['loan_code']; ?></td>

<td>৳ <?php echo number_format($row['principal_amount']); ?></td>
<td>৳ <?php echo number_format($row['total_paid']); ?></td>
<td>৳ <?php echo number_format($row['remaining']); ?></td>

<td>
<?php
if($row['remaining'] <= 0){
    echo "<span class='badge bg-success'>Paid</span>";
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

</div>

</body>
</html>