<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

/* =========================
   MEMBER LOAN DATA
========================= */
$result = $conn->query("
SELECT 
m.full_name,
COALESCE(SUM(l.principal_amount),0) AS total_loan
FROM members m
LEFT JOIN loans l ON m.member_id = l.member_id
GROUP BY m.member_id
ORDER BY total_loan DESC
LIMIT 10
");

$names = [];
$loans = [];

while($row = $result->fetch_assoc()){
    $names[] = $row['full_name'];
    $loans[] = (float)$row['total_loan'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Member Loan Chart</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:#f4f6f9;
    font-family:Segoe UI;
}
.card-box{
    background:#fff;
    padding:20px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}
</style>
</head>

<body>

<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📊 Member Wise Loan Chart</h3>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card-box">
    <canvas id="loanChart"></canvas>
</div>

</div>

<script>
const ctx = document.getElementById('loanChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($names); ?>,
        datasets: [{
            label: 'Total Loan (৳)',
            data: <?php echo json_encode($loans); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>