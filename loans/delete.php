<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];

// Delete the loan
$conn->query("DELETE FROM loans WHERE loan_id=$id");

// Redirect with success message
header("Location: index.php?deleted=1");
exit();
?>