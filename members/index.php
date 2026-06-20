<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$sql = "
SELECT m.*, c.committee_name, b.branch_name
FROM members m
LEFT JOIN committees c ON m.committee_id = c.committee_id
LEFT JOIN branches b ON m.branch_id = b.branch_id
ORDER BY m.member_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Members</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}
</style>

</head>

<body>

<div class="container mt-4">

<h3>Member List</h3>

<a href="add.php" class="btn btn-primary mb-3">+ Add Member</a>

<table class="table table-bordered bg-white">

<tr>
<th>ID</th>
<th>Code</th>
<th>Name</th>
<th>DOB</th> <!-- ✅ NEW -->
<th>Phone</th>
<th>Committee</th>
<th>Branch</th>
<th>Join Date</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>
<tr>
<td><?php echo $row['member_id']; ?></td>
<td><?php echo $row['member_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>

<!-- ✅ DOB SHOW -->
<td><?php echo $row['dob']; ?></td>

<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['committee_name']; ?></td>
<td><?php echo $row['branch_name']; ?></td>
<td><?php echo $row['join_date']; ?></td>

<td>

<a href="edit.php?id=<?php echo $row['member_id']; ?>" 
   class="btn btn-warning btn-sm">
   Edit
</a>

<a href="delete.php?id=<?php echo $row['member_id']; ?>" 
   class="btn btn-danger btn-sm"
   onclick="return confirm('Are you sure to delete this member?')">
   Delete
</a>

</td>

</tr>
<?php } ?>

</table>

</div>

</body>
</html>