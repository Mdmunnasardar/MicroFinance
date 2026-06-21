<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

$conn->query("DELETE FROM loan_payments WHERE payment_id=$id");

header("Location: index.php");
exit();
?>