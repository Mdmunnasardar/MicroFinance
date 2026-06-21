<?php
include "../config/db.php";

$total = $conn->query("SELECT SUM(principal_amount) as t FROM loans")->fetch_assoc()['t'];
$paid = $conn->query("SELECT SUM(total_paid) as t FROM loans")->fetch_assoc()['t'];

$remaining = $total - $paid;
?>

<h3>Due Report</h3>

<ul>
<li>Total Loan: <?php echo $total; ?></li>
<li>Total Paid: <?php echo $paid; ?></li>
<li>Remaining: <?php echo $remaining; ?></li>
</ul>