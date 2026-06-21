<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// GET LOANS WITH MEMBER INFO
$sql = "
SELECT 
l.*,
m.full_name,
m.member_code
FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
ORDER BY l.loan_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Loans</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}
</style>
</head>

<body>

<div class="container mt-4">

<h3>Loan List</h3>

<a href="add.php" class="btn btn-primary mb-3">+ Add Loan</a>

<table class="table table-bordered bg-white">

<thead>
<tr>
    <th>ID</th>
    <th>Loan Code</th>
    <th>Member</th>
    <th>Principal</th>
    <th>Interest %</th>
    <th>Total Payable</th>
    <th>Paid</th>
    <th>Installment</th>
    <th>Status</th>
    <th>Disbursement</th>
    <th>Maturity</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row['loan_id']; ?></td>
    <td><?php echo $row['loan_code']; ?></td>

    <td>
        <?php echo $row['full_name']; ?>
        <br>
        <small><?php echo $row['member_code']; ?></small>
    </td>

    <td>৳ <?php echo number_format($row['principal_amount'],2); ?></td>
    <td><?php echo $row['interest_rate']; ?>%</td>

    <td>৳ <?php echo number_format($row['total_payable'],2); ?></td>

    <td>৳ <?php echo number_format($row['total_paid'],2); ?></td>

    <td>৳ <?php echo number_format($row['installment_amount'],2); ?></td>

    <td>
        <?php
        if($row['status']=='active'){
            echo "<span class='badge bg-success'>Active</span>";
        }elseif($row['status']=='closed'){
            echo "<span class='badge bg-primary'>Closed</span>";
        }elseif($row['status']=='overdue'){
            echo "<span class='badge bg-danger'>Overdue</span>";
        }else{
            echo "<span class='badge bg-dark'>Written Off</span>";
        }
        ?>
    </td>

    <td><?php echo $row['disbursement_date']; ?></td>
    <td><?php echo $row['maturity_date']; ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</body>
</html>