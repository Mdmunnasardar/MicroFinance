<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// INSERT MEMBER
if (isset($_POST['submit'])) {

    $member_code = $_POST['member_code'];
    $full_name = $_POST['full_name'];
    $national_id = $_POST['national_id'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $committee_id = $_POST['committee_id'];
    $branch_id = $_POST['branch_id'];
    $join_date = $_POST['join_date'];
    $guarantor_name = $_POST['guarantor_name'];

    $sql = "INSERT INTO members (
        member_code,
        full_name,
        national_id,
        dob,
        gender,
        phone,
        address,
        committee_id,
        branch_id,
        join_date,
        guarantor_name,
        is_active
    ) VALUES (
        '$member_code',
        '$full_name',
        '$national_id',
        '$dob',
        '$gender',
        '$phone',
        '$address',
        '$committee_id',
        '$branch_id',
        '$join_date',
        '$guarantor_name',
        1
    )";

    $conn->query($sql);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Member</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Add Member</h3>

<form method="POST">

<!-- Member Code -->
<input type="text" name="member_code" class="form-control mb-2" placeholder="Member Code" required>

<!-- Full Name -->
<input type="text" name="full_name" class="form-control mb-2" placeholder="Full Name" required>

<!-- National ID -->
<input type="text" name="national_id" class="form-control mb-2" placeholder="National ID">

<!-- DOB -->
<div class="mb-2">
    <label class="form-label">Date of Birth (DOB)</label>
    <input type="date" name="dob" class="form-control">
</div>

<!-- Gender -->
<select name="gender" class="form-control mb-2">
    <option value="M">Male</option>
    <option value="F">Female</option>
    <option value="Other">Other</option>
</select>

<!-- Phone -->
<input type="text" name="phone" class="form-control mb-2" placeholder="Phone">

<!-- Address -->
<textarea name="address" class="form-control mb-2" placeholder="Address"></textarea>

<!-- Committee -->
<select name="committee_id" class="form-control mb-2" required>
<option value="">Select Committee</option>
<?php
$committees = $conn->query("SELECT * FROM committees");
while($c = $committees->fetch_assoc()) {
?>
<option value="<?php echo $c['committee_id']; ?>">
    <?php echo $c['committee_name']; ?>
</option>
<?php } ?>
</select>

<!-- Branch -->
<select name="branch_id" class="form-control mb-2" required>
<option value="">Select Branch</option>
<?php
$branches = $conn->query("SELECT * FROM branches");
while($b = $branches->fetch_assoc()) {
?>
<option value="<?php echo $b['branch_id']; ?>">
    <?php echo $b['branch_name']; ?>
</option>
<?php } ?>
</select>

<!-- Join Date -->
<input type="date" name="join_date" class="form-control mb-2" required>

<!-- Guarantor -->
<input type="text" name="guarantor_name" class="form-control mb-2" placeholder="Guarantor Name">

<!-- Submit -->
<button type="submit" name="submit" class="btn btn-success w-100">
Save Member
</button>

</form>

</div>

</body>
</html>