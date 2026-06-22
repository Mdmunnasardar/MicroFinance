<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$search = "";

$sql = "
SELECT m.*, c.committee_name, b.branch_name
FROM members m
LEFT JOIN committees c ON m.committee_id = c.committee_id
LEFT JOIN branches b ON m.branch_id = b.branch_id
WHERE 1
";

if(isset($_GET['search']) && $_GET['search'] != "") {
    $search = $_GET['search'];

    $sql .= " AND (
        m.full_name LIKE '%$search%'
        OR m.member_code LIKE '%$search%'
        OR m.phone LIKE '%$search%'
        OR m.national_id LIKE '%$search%'
    )";
}

$sql .= " ORDER BY m.member_id DESC";

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

<!-- SEARCH BOX -->
<form method="GET" class="mb-3">

<div class="row">

<div class="col-md-10">
<input type="text" name="search" class="form-control"
placeholder="Search by Name / Code / Phone / NID"
value="<?php echo $search; ?>">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Search</button>
</div>

</div>

</form>

<!-- ACTION -->
<a href="add.php" class="btn btn-success mb-3">+ Add Member</a>
<a href="index.php" class="btn btn-secondary mb-3">Reset</a>

<table class="table table-bordered bg-white">

<tr>
<th>ID</th>
<th>Code</th>
<th>Name</th>
<th>DOB</th>
<th>Phone</th>
<th>Committee</th>
<th>Branch</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>
<tr>

<td><?php echo $row['member_id']; ?></td>
<td><?php echo $row['member_code']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['dob']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['committee_name']; ?></td>
<td><?php echo $row['branch_name']; ?></td>

<td>

<a href="view.php?id=<?php echo $row['member_id']; ?>" class="btn btn-info btn-sm text-white">
View
</a>

<a href="edit.php?id=<?php echo $row['member_id']; ?>" class="btn btn-warning btn-sm">
Edit
</a>

<a href="delete.php?id=<?php echo $row['member_id']; ?>" class="btn btn-danger btn-sm"
onclick="return confirm('Are you sure?')">
Delete
</a>

</td>

</tr>
<?php } ?>

</table>

</div>

</body>
</html>