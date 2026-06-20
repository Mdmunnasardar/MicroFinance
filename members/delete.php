<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

$sql = "DELETE FROM members WHERE member_id = $id";
$conn->query($sql);

header("Location: index.php");
exit();
?>