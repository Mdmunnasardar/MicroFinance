<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

/* =========================
   CORE STATS
========================= */

$total_members = $conn->query("SELECT COUNT(*) AS t FROM members")->fetch_assoc()['t'] ?? 0;

$active_members = $conn->query("SELECT COUNT(*) AS t FROM members WHERE is_active=1")->fetch_assoc()['t'] ?? 0;

$total_loans = $conn->query("SELECT SUM(principal_amount) AS t FROM loans")->fetch_assoc()['t'] ?? 0;

$total_paid = $conn->query("SELECT SUM(total_paid) AS t FROM loans")->fetch_assoc()['t'] ?? 0;

$total_due = $total_loans - $total_paid;

$total_savings = $conn->query("SELECT SUM(balance) AS t FROM savings")->fetch_assoc()['t'] ?? 0;

$total_collection = $conn->query("SELECT SUM(amount) AS t FROM loan_payments")->fetch_assoc()['t'] ?? 0;

/* =========================
   ADVANCED ANALYTICS
========================= */

$this_month_collection = $conn->query("
SELECT SUM(amount) AS t
FROM loan_payments
WHERE MONTH(payment_date)=MONTH(CURDATE())
AND YEAR(payment_date)=YEAR(CURDATE())
")->fetch_assoc()['t'] ?? 0;

$this_month_loans = $conn->query("
SELECT SUM(principal_amount) AS t
FROM loans
WHERE MONTH(created_at)=MONTH(CURDATE())
AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['t'] ?? 0;

$overdue = $conn->query("
SELECT COUNT(*) AS t
FROM loans
WHERE status='active'
AND maturity_date < CURDATE()
")->fetch_assoc()['t'] ?? 0;

$top = $conn->query("
SELECT m.full_name, SUM(l.principal_amount) AS total
FROM loans l
LEFT JOIN members m ON l.member_id=m.member_id
GROUP BY l.member_id
ORDER BY total DESC
LIMIT 1
")->fetch_assoc();

/* =========================
   LOAN HEALTH
========================= */

$health = ($total_loans > 0) ? ($total_paid / $total_loans) * 100 : 0;

/* =========================
   MONTHLY GRAPH DATA
========================= */

$months = [];
$loanData = [];
$payData = [];

for ($i = 5; $i >= 0; $i--) {

    $m = date('Y-m', strtotime("-$i month"));
    $months[] = $m;

    $loan = $conn->query("
        SELECT SUM(principal_amount) AS t
        FROM loans
        WHERE DATE_FORMAT(created_at,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;

    $pay = $conn->query("
        SELECT SUM(amount) AS t
        FROM loan_payments
        WHERE DATE_FORMAT(payment_date,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;

    $loanData[] = $loan;
    $payData[] = $pay;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bank Dashboard Pro</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:#f4f7fb;
    font-family:Segoe UI;
}

/* SIDEBAR */
.sidebar{
    width:250px;
    height:100vh;
    position:fixed;
    background:#0f172a;
    color:white;
    padding:20px;
}

.sidebar a{
    display:block;
    color:#fff;
    padding:10px;
    text-decoration:none;
    border-radius:6px;
    margin-bottom:5px;
}

.sidebar a:hover{
    background:#1e293b;
}

/* MAIN */
.main{
    margin-left:270px;
    padding:20px;
}

/* CARD */
.card-box{
    background:#fff;
    padding:16px;
    border-radius:14px;
    box-shadow:0 8px 18px rgba(0,0,0,0.06);
    transition:0.2s;
}

.card-box:hover{
    transform:translateY(-2px);
}

.title{
    font-size:12px;
    color:#6b7280;
}

.value{
    font-size:22px;
    font-weight:600;
}

/* ALERT */
.alert-strip{
    background:#fee2e2;
    padding:10px 15px;
    border-radius:10px;
    color:#991b1b;
    margin-bottom:15px;
    font-weight:500;
}

/* SMALL PANEL */
.panel{
    background:#fff;
    padding:15px;
    border-radius:14px;
    box-shadow:0 5px 12px rgba(0,0,0,0.05);
}
</style>
</head>

<body>
<!-- SIDEBAR -->
<div class="sidebar">
<h3>🏦 MICROFINANCE</h3>

<a href="dashboard.php">Dashboard</a>

<!-- ADD THIS -->
<a href="committees/">Committees</a>

<a href="members/">Members</a>
<a href="loans/">Loans</a>
<a href="installments/">Installments</a>
<a href="savings/">Savings</a>
<a href="due_system/">Due System</a>

<a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<h3 class="mb-3">📊 Bank Analytics Dashboard</h3>

<?php if($overdue > 0): ?>
<div class="alert-strip">
⚠️ Overdue Loans: <b><?php echo $overdue; ?></b> require attention!
</div>
<?php endif; ?>

<!-- KPI ROW -->
<div class="row g-3">

<div class="col-md-3">
<div class="card-box">
<div class="title">Total Members</div>
<div class="value"><?php echo $total_members; ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Active Members</div>
<div class="value text-success"><?php echo $active_members; ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Total Loans</div>
<div class="value"><?php echo number_format($total_loans); ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Total Paid</div>
<div class="value text-success"><?php echo number_format($total_paid); ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Total Due</div>
<div class="value text-danger"><?php echo number_format($total_due); ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Savings</div>
<div class="value text-primary"><?php echo number_format($total_savings); ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Monthly Collection</div>
<div class="value text-success"><?php echo number_format($this_month_collection); ?></div>
</div>
</div>

<div class="col-md-3">
<div class="card-box">
<div class="title">Loan Health</div>
<div class="value"><?php echo round($health,2); ?>%</div>
</div>
</div>

</div>

<!-- TOP PANEL -->
<div class="row mt-4">

<div class="col-md-8">
<div class="card-box">
<h6>📈 Monthly Trend</h6>
<canvas id="trendChart" height="90"></canvas>
</div>
</div>

<div class="col-md-4">
<div class="panel">
<h6>🏆 Top Borrower</h6>
<br>
<h5><?php echo $top['full_name'] ?? 'N/A'; ?></h5>
</div>

<div class="panel mt-3">
<h6>💰 This Month Loans</h6>
<h4><?php echo number_format($this_month_loans); ?></h4>
</div>
</div>

</div>

</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [
            {
                label: 'Loans',
                data: <?php echo json_encode($loanData); ?>,
                borderColor: '#3b82f6',
                fill: false
            },
            {
                label: 'Collection',
                data: <?php echo json_encode($payData); ?>,
                borderColor: '#22c55e',
                fill: false
            }
        ]
    }
});
</script>

</body>
</html>