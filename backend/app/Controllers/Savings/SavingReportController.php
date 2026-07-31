<?php
include "../config/db.php";

$total = $conn->query("SELECT SUM(balance) AS t FROM savings")->fetch_assoc()['t'];
$count = $conn->query("SELECT COUNT(*) AS t FROM savings")->fetch_assoc()['t'];
?>

<h3>Savings Report</h3>

<ul>
<li>Total Deposits: ৳ <?php echo $total; ?></li>
<li>Total Transactions: <?php echo $count; ?></li>
</ul>