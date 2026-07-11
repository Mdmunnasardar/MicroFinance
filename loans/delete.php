<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if loan has payments
    $check = $conn->query("SELECT COUNT(*) FROM loan_payments WHERE loan_id = $id")->fetch_row()[0];
    
    if ($check > 0) {
        // Soft delete - just mark as deleted
        $conn->query("UPDATE loans SET status = 'deleted' WHERE loan_id = $id");
        $_SESSION['message'] = "Loan has been archived (has payments)";
    } else {
        // Hard delete
        $conn->query("DELETE FROM loans WHERE loan_id = $id");
        $_SESSION['message'] = "Loan deleted successfully";
    }
}

header("Location: index.php");
exit();
?>