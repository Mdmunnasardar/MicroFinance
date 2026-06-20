<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include "config/db.php";

// 👤 Total Members
$result = $conn->query("SELECT COUNT(*) AS total FROM members");
$data = $result->fetch_assoc();
$total_members = $data['total'] ?? 0;

// 💰 Total Loans
$result = $conn->query("SELECT SUM(principal_amount) AS total FROM loans");
$data = $result->fetch_assoc();
$total_loans = $data['total'] ?? 0;

// 💵 Total Savings
$result = $conn->query("SELECT SUM(balance) AS total FROM savings");
$data = $result->fetch_assoc();
$total_savings = $data['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

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
    font-size: 18px;
    color: gray;
}

.card-value {
    font-size: 28px;
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
<div class="sidebar">
    <h2>MicroFinance</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="committees/">Committees</a>
    <a href="members/">Members</a>
    <a href="loans/">Loans</a>
    <a href="installments/">Installments</a>
    <a href="savings/">Savings</a>

    <a href="logout.php">Logout</a>
</div>

<!-- Main -->
<div class="main">

    <div class="topbar">
        <h4>Welcome, <?php echo $_SESSION['name']; ?></h4>
        <small>Role: <?php echo $_SESSION['role']; ?></small>
    </div>

    <!-- Cards -->
    <div class="row g-3">

        <!-- Members -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Members</div>
                <div class="card-value"><?php echo $total_members; ?></div>
            </div>
        </div>

        <!-- Loans -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Loans</div>
                <div class="card-value">৳ <?php echo number_format($total_loans); ?></div>
            </div>
        </div>

        <!-- Savings -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Savings</div>
                <div class="card-value">৳ <?php echo number_format($total_savings); ?></div>
            </div>
        </div>

    </div>

</div>

</body>
</html>