<?php
// Legacy sidebar — kept only as a compatibility shim for the Dashboard
// controller, which still executes directly when accessed from internal
// legacy code paths. After Phase 7C, the SPA handles all navigation.
$current = basename($_SERVER['PHP_SELF']);
$spa_base = '/MicroFinance/';
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
        <a href="<?php echo $spa_base; ?>" class="<?= ($current=="dashboard.php")?'active':''; ?>">
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>
        <a href="<?php echo $spa_base; ?>members">
            <i class="fa-solid fa-users"></i>
            Members
        </a>
        <a href="<?php echo $spa_base; ?>committees">
            <i class="fa-solid fa-layer-group"></i>
            Committees
        </a>
        <a href="<?php echo $spa_base; ?>installments">
            <i class="fa-solid fa-credit-card"></i>
            Installments
        </a>
        <a href="<?php echo $spa_base; ?>due-system">
            <i class="fa-solid fa-clock"></i>
            Due System
        </a>
    </div>

    <div class="sidebar-bottom">
        <a href="<?php echo $spa_base; ?>login" onclick="return true;">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</div>