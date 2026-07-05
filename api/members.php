<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT member_id, full_name, member_code, phone, is_active 
        FROM members WHERE is_active = 1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR member_code LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY member_id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
?>