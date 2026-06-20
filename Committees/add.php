<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// insert data
if (isset($_POST['submit'])) {

    $name = $_POST['committee_name'];
    $branch_id = $_POST['branch_id'];
    $officer_id = $_POST['field_officer_id'];
    $day = $_POST['meeting_day'];
    $time = $_POST['meeting_time'];
    $date = $_POST['formed_date'];

    $sql = "INSERT INTO committees (
        committee_name,
        branch_id,
        field_officer_id,
        meeting_day,
        meeting_time,
        formed_date,
        is_active
    ) VALUES (
        '$name',
        '$branch_id',
        '$officer_id',
        '$day',
        '$time',
        '$date',
        1
    )";

    $conn->query($sql);

    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Committee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<h3>Add Committee</h3>

<form method="POST">

<input type="text" name="committee_name" class="form-control mb-2" placeholder="Committee Name" required>

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

<select name="field_officer_id" class="form-control mb-2" required>
<option value="">Select Field Officer</option>

<?php
$officers = $conn->query("SELECT * FROM users WHERE role='field_officer'");
while($o = $officers->fetch_assoc()) {
?>
<option value="<?php echo $o['user_id']; ?>">
    <?php echo $o['full_name']; ?>
</option>
<?php } ?>

</select>

<select name="meeting_day" class="form-control mb-2" required>
<option>Sun</option>
<option>Mon</option>
<option>Tue</option>
<option>Wed</option>
<option>Thu</option>
<option>Fri</option>
<option>Sat</option>
</select>

<input type="time" name="meeting_time" class="form-control mb-2" required>

<input type="date" name="formed_date" class="form-control mb-2" required>

<button type="submit" name="submit" class="btn btn-success">
Save Committee
</button>

</form>

</div>

</body>
</html>