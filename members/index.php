<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

/* ======================
   FILTER
====================== */
$search = $_GET['search'] ?? "";
$branch = $_GET['branch'] ?? "";

/* ======================
   STATS
====================== */
$total_members = $conn->query("SELECT COUNT(*) as t FROM members")->fetch_assoc()['t'] ?? 0;

$active_members = $conn->query("SELECT COUNT(*) as t FROM members WHERE is_active=1")->fetch_assoc()['t'] ?? 0;

/* ======================
   QUERY
====================== */
$sql = "
SELECT m.*, c.committee_name, b.branch_name
FROM members m
LEFT JOIN committees c ON m.committee_id = c.committee_id
LEFT JOIN branches b ON m.branch_id = b.branch_id
WHERE 1
";

if($search != ""){
    $sql .= " AND (
        m.full_name LIKE '%$search%'
        OR m.member_code LIKE '%$search%'
        OR m.phone LIKE '%$search%'
        OR m.national_id LIKE '%$search%'
    )";
}

if($branch != ""){
    $sql .= " AND m.branch_id='$branch'";
}

$sql .= " ORDER BY m.member_id DESC";

$result = $conn->query($sql);

$branches = $conn->query("SELECT * FROM branches");
?>

<!DOCTYPE html>
<html>
<head>
<title>Members</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
body{background:#f4f6f9;font-family:Segoe UI;}
.card-box{background:#fff;padding:15px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.05);}
</style>
</head>

<body>

<div class="container mt-4">

<!-- HEADER -->
<div class="d-flex justify-content-between mb-3">

<h3>Members System</h3>

<div>
    <a href="loan_chart.php" class="btn btn-dark me-2">
        📊 Loan Graph
    </a>

    <a href="add.php" class="btn btn-success">
        + Add Member
    </a>
</div>

</div>

<!-- STATS -->
<div class="row mb-3">

<div class="col-md-6">
<div class="card-box text-center">
<h5>Total Members</h5>
<h3><?php echo $total_members; ?></h3>
</div>
</div>

<div class="col-md-6">
<div class="card-box text-center">
<h5>Active Members</h5>
<h3><?php echo $active_members; ?></h3>
</div>
</div>

</div>

<!-- FILTER -->
<div class="card-box mb-3">

<form method="GET">
<div class="row">

<div class="col-md-5">
<input type="text" name="search" class="form-control"
placeholder="Search name / code / phone / NID"
value="<?php echo $search; ?>">
</div>

<div class="col-md-4">
<select name="branch" class="form-control">
<option value="">All Branch</option>
<?php while($b = $branches->fetch_assoc()){ ?>
<option value="<?php echo $b['branch_id']; ?>"
<?php if($branch==$b['branch_id']) echo "selected"; ?>>
<?php echo $b['branch_name']; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-primary w-100">Filter</button>
</div>

</div>
</form>

</div>

<!-- EXPORT -->
<a href="export_csv.php" class="btn btn-success mb-2">Export CSV</a>

<!-- TABLE -->
<div class="card-box">

<table class="table table-hover">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Phone</th>
<th>Branch</th>
<th width="220">Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['member_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['branch_name']; ?></td>

<td>

<!-- VIEW PROFILE -->
<a href="view.php?id=<?php echo $row['member_id']; ?>" class="btn btn-info btn-sm">
View
</a>

<!-- EDIT -->
<a href="edit.php?id=<?php echo $row['member_id']; ?>" class="btn btn-warning btn-sm">
Edit
</a>

<!-- DELETE -->
<a href="delete.php?id=<?php echo $row['member_id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this member?')">
Delete
</a>

<!-- QUICK VIEW -->
<button class="btn btn-secondary btn-sm viewBtn"
data-id="<?php echo $row['member_id']; ?>">
Quick
</button>

</td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<!-- MODAL -->
<div class="modal fade" id="viewModal">
<div class="modal-dialog">
<div class="modal-content p-3" id="modalData"></div>
</div>
</div>

<script>
$(".viewBtn").click(function(){
var id = $(this).data("id");

$.post("quick_view.php", {id:id}, function(data){
$("#modalData").html(data);
$("#viewModal").modal("show");
});
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>