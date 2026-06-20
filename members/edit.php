<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

// UPDATE
if (isset($_POST['update'])) {

    $member_code = $_POST['member_code'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $guarantor_name = $_POST['guarantor_name'];

    $sql = "UPDATE members SET 
        member_code='$member_code',
        full_name='$full_name',
        phone='$phone',
        dob='$dob',
        address='$address',
        guarantor_name='$guarantor_name'
        WHERE member_id=$id
    ";

    $conn->query($sql);

    header("Location: index.php");
    exit();
}

// GET DATA
$result = $conn->query("SELECT * FROM members WHERE member_id=$id");
$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Member</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Edit Member</h3>

<form method="POST">

<input type="text" name="member_code" class="form-control mb-2"
value="<?php echo $data['member_code']; ?>">

<input type="text" name="full_name" class="form-control mb-2"
value="<?php echo $data['full_name']; ?>">

<input type="text" name="phone" class="form-control mb-2"
value="<?php echo $data['phone']; ?>">

<input type="date" name="dob" class="form-control mb-2"
value="<?php echo $data['dob']; ?>">

<textarea name="address" class="form-control mb-2"><?php echo $data['address']; ?></textarea>

<input type="text" name="guarantor_name" class="form-control mb-2"
value="<?php echo $data['guarantor_name']; ?>">

<button type="submit" name="update" class="btn btn-success">
Update Member
</button>

</form>

</div>

</body>
</html>