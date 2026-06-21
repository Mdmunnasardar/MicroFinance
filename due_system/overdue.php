<?php
session_start();
include "../config/db.php";

$sql = "
SELECT l.*, m.full_name,
(l.principal_amount - l.total_paid) AS remaining
FROM loans l
LEFT JOIN members m ON l.member_id=m.member_id
WHERE (l.principal_amount - l.total_paid) > 0
ORDER BY remaining DESC
";

$result = $conn->query($sql);
?>

<h3>Overdue Loans</h3>

<table border="1" class="table">
<tr>
<th>Loan</th>
<th>Member</th>
<th>Remaining</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['loan_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['remaining']; ?></td>
</tr>
<?php } ?>

</table>