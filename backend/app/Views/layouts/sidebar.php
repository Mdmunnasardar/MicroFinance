<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <i class="fa-solid fa-building-columns"></i>
        </div>
        <div>
            <h4>MicroFinance</h4>
            <small>Management System</small>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?= ($current=="dashboard.php")?'active':''; ?>">
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>
        <a href="members/">
            <i class="fa-solid fa-users"></i>
            Members
        </a>
        <a href="committees/">
            <i class="fa-solid fa-layer-group"></i>
            Committees
        </a>
        <a href="loans/">
            <i class="fa-solid fa-money-bill-wave"></i>
            Loans
        </a>
        <a href="installments/">
            <i class="fa-solid fa-credit-card"></i>
            Installments
        </a>
        <a href="savings/">
            <i class="fa-solid fa-piggy-bank"></i>
            Savings
        </a>
        <a href="due_system/">
            <i class="fa-solid fa-clock"></i>
            Due System
        </a>
    </div>
    
    <div class="sidebar-bottom">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</div>