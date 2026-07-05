<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$stats = [
    'total_members' => 0,
    'active_members' => 0,
    'total_committees' => 0,
    'total_savings' => 0
];

try {
    $stats['total_members'] = $conn->query("SELECT COUNT(*) as t FROM members WHERE is_active = 1")->fetch_assoc()['t'] ?? 0;
    $stats['active_members'] = $conn->query("SELECT COUNT(*) as t FROM members WHERE is_active = 1")->fetch_assoc()['t'] ?? 0;
    $stats['total_committees'] = $conn->query("SELECT COUNT(*) as t FROM committees WHERE is_active = 1")->fetch_assoc()['t'] ?? 0;
    $stats['total_savings'] = $conn->query("SELECT COALESCE(SUM(balance), 0) as t FROM savings")->fetch_assoc()['t'] ?? 0;
} catch (Exception $e) {
    // Table doesn't exist yet
}

header('Content-Type: application/json');
echo json_encode($stats);
?>