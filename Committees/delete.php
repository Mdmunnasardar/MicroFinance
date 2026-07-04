<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($committee_id > 0) {
    // Check if committee has members
    $check_sql = "SELECT COUNT(*) as count FROM committee_members WHERE committee_id = ? AND is_active = 1";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $committee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        // Has members - just deactivate
        $update_sql = "UPDATE committees SET is_active = 0 WHERE committee_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $committee_id);
        $stmt->execute();
        $_SESSION['message'] = "Committee has active members. It has been deactivated instead.";
    } else {
        // No members - safe to delete
        $delete_sql = "DELETE FROM committees WHERE committee_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $committee_id);
        $stmt->execute();
        $_SESSION['message'] = "Committee deleted successfully.";
    }
}

header("Location: index.php");
exit();
?>