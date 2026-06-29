<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

// Check if member exists
$check = $conn->query("SELECT member_id FROM members WHERE member_id=$id");
if ($check->num_rows > 0) {
    $sql = "DELETE FROM members WHERE member_id = $id";
    $conn->query($sql);
    header("Location: index.php?success=Member deleted successfully");
} else {
    header("Location: index.php?error=Member not found");
}
exit();
?>