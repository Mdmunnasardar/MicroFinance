<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <!-- Logo -->
    <div class="logo">

        <div class="logo-icon">
            <i class="fa-solid fa-building-columns"></i>
        </div>

        <div>
            <h4>MicroFinance</h4>
            <small>Management System</small>
        </div>

    </div>

    <!-- Menu -->
    <div class="menu">

        <a class="<?= ($current=="dashboard.php")?'active':''; ?>" href="/dashboard.php">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <a href="/members/" class="<?= strpos($_SERVER['REQUEST_URI'],'members')!==false?'active':''; ?>">
            <i class="fa-solid fa-users"></i>
            <span>Members</span>
        </a>

        <a href="/committees/" class="<?= strpos($_SERVER['REQUEST_URI'],'committees')!==false?'active':''; ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Committees</span>
        </a>

        <a href="/loans/" class="<?= strpos($_SERVER['REQUEST_URI'],'loans')!==false?'active':''; ?>">
            <i class="fa-solid fa-money-bill-wave"></i>
            <span>Loans</span>
        </a>

        <a href="/installments/" class="<?= strpos($_SERVER['REQUEST_URI'],'installments')!==false?'active':''; ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Installments</span>
        </a>

        <a href="/savings/" class="<?= strpos($_SERVER['REQUEST_URI'],'savings')!==false?'active':''; ?>">
            <i class="fa-solid fa-piggy-bank"></i>
            <span>Savings</span>
        </a>

        <a href="/due_system/" class="<?= strpos($_SERVER['REQUEST_URI'],'due_system')!==false?'active':''; ?>">
            <i class="fa-solid fa-clock"></i>
            <span>Due System</span>
        </a>

    </div>

    <!-- Bottom -->
    <div class="sidebar-bottom">

        <a href="/logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Logout</span>

        </a>

    </div>

</div>