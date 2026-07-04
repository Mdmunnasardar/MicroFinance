<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['committee_id']) ? (int)$_GET['committee_id'] : 0;
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($committee_id > 0 && $member_id > 0) {
    // Remove member from committee (set committee_id to NULL)
    $update_sql = "UPDATE members SET committee_id = NULL WHERE member_id = ? AND committee_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $member_id, $committee_id);
    
    if ($stmt->execute()) {
        // Success - redirect back with success message
        header("Location: assign-member.php?committee_id=" . $committee_id . "&success=removed");
        exit();
    } else {
        // Error - redirect back with error message
        header("Location: assign-member.php?committee_id=" . $committee_id . "&error=remove_failed");
        exit();
    }
} else {
    // Invalid parameters - redirect back
    header("Location: assign-member.php?committee_id=" . $committee_id);
    exit();
}
?>