<?php
include "../config/db.php";

$result = $conn->query("
SELECT s.*, m.full_name, m.member_code
FROM savings s
LEFT JOIN members m ON s.member_id = m.member_id
ORDER BY s.id DESC
");
?>

<h3>Savings History</h3>

<a href="add.php" class="btn btn-primary">+ Add Savings</a>

<table class="table table-bordered">

<tr>
<th>Member</th>
<th>Amount</th>
<th>Date</th>
<th>Note</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['full_name']; ?> (<?php echo $row['member_code']; ?>)</td>
<td>৳ <?php echo $row['balance']; ?></td>
<td><?php echo $row['date']; ?></td>
<td><?php echo $row['note']; ?></td>
</tr>

<?php } ?>

</table>