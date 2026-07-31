<?php
include "../config/db.php";

$id = $_GET['id'];

$member = $conn->query("SELECT full_name FROM members WHERE member_id=$id")->fetch_assoc();

$total = $conn->query("
SELECT SUM(balance) AS t FROM savings WHERE member_id=$id
")->fetch_assoc()['t'];
?>

<h3><?php echo $member['full_name']; ?> Savings</h3>

<h2>Total: ৳ <?php echo $total; ?></h2>