<?php
include "../config/db.php";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=members_' . date('Y-m-d') . '.csv');

$output = fopen("php://output", "w");

// Headers
fputcsv($output, ["ID", "Code", "Name", "Email", "Phone", "Branch", "Status", "Join Date"]);

// Data
$result = $conn->query("
    SELECT m.member_id, m.member_code, m.full_name, m.email, m.phone, 
           b.branch_name, m.is_active, m.join_date
    FROM members m
    LEFT JOIN branches b ON m.branch_id = b.branch_id
    ORDER BY m.member_id DESC
");

while($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['member_id'],
        $row['member_code'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['branch_name'] ?? 'N/A',
        $row['is_active'] ? 'Active' : 'Inactive',
        $row['join_date']
    ]);
}

fclose($output);
exit;
?>