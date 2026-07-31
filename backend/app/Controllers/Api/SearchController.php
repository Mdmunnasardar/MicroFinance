<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$search_term = "%$query%";
$results = ['members' => [], 'committees' => [], 'officers' => []];

// Search Members
$sql = "SELECT member_id, full_name, member_code FROM members 
        WHERE is_active = 1 AND (full_name LIKE ? OR member_code LIKE ?) LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$results['members'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Search Committees
$sql = "SELECT committee_id, committee_name FROM committees 
        WHERE is_active = 1 AND committee_name LIKE ? LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $search_term);
$stmt->execute();
$results['committees'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Search Officers
$sql = "SELECT user_id, full_name FROM users 
        WHERE role = 'field_officer' AND is_active = 1 AND full_name LIKE ? LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $search_term);
$stmt->execute();
$results['officers'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
?>