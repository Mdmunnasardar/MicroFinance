<?php
include "../config/db.php";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=members.csv');

$output = fopen("php://output", "w");
fputcsv($output, ["ID","Name","Phone","Branch"]);

$result = $conn->query("
SELECT m.member_id,m.full_name,m.phone,b.branch_name
FROM members m
LEFT JOIN branches b ON m.branch_id=b.branch_id
");

while($row = $result->fetch_assoc()){
    fputcsv($output, $row);
}

fclose($output);
exit;
?>