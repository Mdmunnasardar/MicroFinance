<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$sql = "
SELECT c.*, b.branch_name, u.full_name AS officer_name
FROM committees c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN users u ON c.field_officer_id = u.user_id
ORDER BY c.committee_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Committee List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Committee List</h3>

<a href="add.php" class="btn btn-primary mb-3">+ Add Committee</a>

<table class="table table-bordered bg-white">

<tr>
<th>ID</th>
<th>Name</th>
<th>Branch</th>
<th>Officer</th>
<th>Day</th>
<th>Time</th>
<th>Status</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>
<tr>
<td><?php echo $row['committee_id']; ?></td>
<td><?php echo $row['committee_name']; ?></td>
<td><?php echo $row['branch_name']; ?></td>
<td><?php echo $row['officer_name']; ?></td>
<td><?php echo $row['meeting_day']; ?></td>
<td><?php echo $row['meeting_time']; ?></td>
<td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>