<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT c.committee_id, c.committee_name, c.meeting_day, c.meeting_time, 
                   b.branch_name, COUNT(m.member_id) as member_count
            FROM committees c
            LEFT JOIN branches b ON c.branch_id = b.branch_id
            LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
            WHERE c.is_active = 1";
    
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (c.committee_name LIKE ? OR b.branch_name LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    $sql .= " GROUP BY c.committee_id ORDER BY c.committee_id DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>