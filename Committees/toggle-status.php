<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? (int)$_GET['status'] : 1;

if ($committee_id > 0) {
    $sql = "UPDATE committees SET is_active = ? WHERE committee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $status, $committee_id);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>