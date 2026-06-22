<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

# =========================
# 📊 BASIC STATS
# =========================

// Members
$total_members = $conn->query("SELECT COUNT(*) AS total FROM members")
->fetch_assoc()['total'] ?? 0;

// Active Members
$active_members = $conn->query("SELECT COUNT(*) AS total FROM members WHERE is_active=1")
->fetch_assoc()['total'] ?? 0;

// Loans
$total_loans = $conn->query("SELECT COUNT(*) AS total FROM loans")
->fetch_assoc()['total'] ?? 0;

// Active Loans
$active_loans = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status='active'")
->fetch_assoc()['total'] ?? 0;

// Total Loan Amount
$total_loan_amount = $conn->query("SELECT SUM(principal_amount) AS total FROM loans")
->fetch_assoc()['total'] ?? 0;

// Total Paid
$total_paid = $conn->query("SELECT SUM(total_paid) AS total FROM loans")
->fetch_assoc()['total'] ?? 0;

// Due Amount
$total_due = $total_loan_amount - $total_paid;

// Savings
$total_savings = $conn->query("SELECT SUM(balance) AS total FROM savings")
->fetch_assoc()['total'] ?? 0;

// Total Collected (Installments)
$total_collected = $conn->query("SELECT SUM(amount) AS total FROM loan_payments")
->fetch_assoc()['total'] ?? 0;

// Overdue Loans (simple logic)
$overdue_loans = $conn->query("
SELECT COUNT(*) AS total
FROM loans
WHERE status='active'
AND maturity_date < CURDATE()
")->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MicroFinance Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI';
    background: #f4f6f9;
}

/* Sidebar */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: #1f2937;
    color: white;
    padding-top: 20px;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    transition: 0.3s;
}

.sidebar a:hover {
    background: #374151;
}

/* Main */
.main {
    margin-left: 250px;
    padding: 20px;
}

/* Cards */
.card-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.card-title {
    font-size: 15px;
    color: gray;
}

.card-value {
    font-size: 26px;
    font-weight: bold;
}

/* Topbar */
.topbar {
    background: white;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>MicroFinance</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="members/">Members</a>
    <a href="committees/">Committees</a>
    <a href="loans/">Loans</a>
    <a href="installments/">Installments</a>
    <a href="savings/">Savings</a>
    <a href="due_system/index.php">Due System</a>
    <a href="due_system/overdue.php">Overdue</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <h4>Welcome, <?php echo $_SESSION['name']; ?></h4>
        <small>Role: <?php echo $_SESSION['role']; ?></small>
    </div>

    <!-- CARDS -->
    <div class="row g-3">

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Total Members</div>
                <div class="card-value"><?php echo $total_members; ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Active Members</div>
                <div class="card-value"><?php echo $active_members; ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Total Loans</div>
                <div class="card-value"><?php echo $total_loans; ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Active Loans</div>
                <div class="card-value"><?php echo $active_loans; ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Total Loan Amount</div>
                <div class="card-value">৳ <?php echo number_format($total_loan_amount); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Total Paid</div>
                <div class="card-value">৳ <?php echo number_format($total_paid); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Total Due</div>
                <div class="card-value text-danger">৳ <?php echo number_format($total_due); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Savings</div>
                <div class="card-value text-success">৳ <?php echo number_format($total_savings); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Collected</div>
                <div class="card-value">৳ <?php echo number_format($total_collected); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-box">
                <div class="card-title">Overdue Loans</div>
                <div class="card-value text-danger"><?php echo $overdue_loans; ?></div>
            </div>
        </div>

    </div>

</div>

</body>
</html>