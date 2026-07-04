<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['committee_id']) ? (int)$_GET['committee_id'] : 0;
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

// REPLACE THIS WITH THE ACTUAL ID FROM STEP 3
$unassigned_committee = 1; // CHANGE THIS!

if ($committee_id > 0 && $member_id > 0) {
    // Move member to "Unassigned" committee
    $update_sql = "UPDATE members SET committee_id = ? WHERE member_id = ? AND committee_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("iii", $unassigned_committee, $member_id, $committee_id);
    
    if ($stmt->execute()) {
        header("Location: assign-member.php?committee_id=" . $committee_id . "&success=removed");
        exit();
    } else {
        header("Location: assign-member.php?committee_id=" . $committee_id . "&error=remove_failed");
        exit();
    }
} else {
    header("Location: assign-member.php?committee_id=" . $committee_id);
    exit();
}
?>