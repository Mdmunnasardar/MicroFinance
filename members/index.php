<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

/* ======================
   FILTERS
====================== */
$search = $_GET['search'] ?? "";
$branch = $_GET['branch'] ?? "";

/* ======================
   STATS
====================== */
$total_members = $conn->query("SELECT COUNT(*) as t FROM members")->fetch_assoc()['t'] ?? 0;

$active_members = $conn->query("
SELECT COUNT(*) as t FROM members WHERE is_active=1
")->fetch_assoc()['t'] ?? 0;

/* ======================
   MAIN QUERY
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

/* ======================
   BRANCH LIST
====================== */
$branches = $conn->query("SELECT * FROM branches");
?>

<!DOCTYPE html>
<html>
<head>
<title>Members</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
body{background:#f4f6f9;}
.card-box{background:#fff;padding:15px;border-radius:10px;}
</style>
</head>

<body>

<div class="container mt-4">

<h3>Members</h3>

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
<form method="GET" class="mb-3">
<div class="row">

<div class="col-md-4">
<input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo $search; ?>">
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

<div class="col-md-4">
<button class="btn btn-primary w-100">Filter</button>
</div>

</div>
</form>

<!-- EXPORT -->
<a href="export_csv.php" class="btn btn-success mb-2">Export CSV</a>

<!-- TABLE -->
<table class="table table-bordered bg-white">

<tr>
<th>ID</th>
<th>Name</th>
<th>Phone</th>
<th>Branch</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['member_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['branch_name']; ?></td>

<td>

<button class="btn btn-info btn-sm viewBtn"
data-id="<?php echo $row['member_id']; ?>">
Quick View
</button>

</td>

</tr>

<?php } ?>

</table>

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

$.ajax({
url:"quick_view.php",
type:"POST",
data:{id:id},
success:function(data){
$("#modalData").html(data);
$("#viewModal").modal("show");
}
});

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>