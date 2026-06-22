<?php
include "../config/db.php";

$id = $_POST['id'];

$row = $conn->query("SELECT * FROM members WHERE member_id=$id")->fetch_assoc();
?>

<h4><?php echo $row['full_name']; ?></h4>
<p>
Phone: <?php echo $row['phone']; ?><br>
DOB: <?php echo $row['dob']; ?><br>
Code: <?php echo $row['member_code']; ?>
</p>