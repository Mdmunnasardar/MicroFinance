<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($committee_id > 0) {
    $sql = "DELETE FROM committees WHERE committee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $committee_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Committee deleted successfully.";
}

header("Location: index.php");
exit();
?>