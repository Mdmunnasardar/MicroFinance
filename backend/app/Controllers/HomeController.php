<?php
// Legacy home controller — kept as a thin compatibility shim.
// Phase 7C's project-root .htaccess intercepts /MicroFinance/index.php
// before this script ever runs in normal traffic. If something DOES reach
// this controller (e.g. an internal legacy include), bounce the browser
// to the React SPA entry points instead of the retired .php shims.
session_start();

// If already logged in, go to the React dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /MicroFinance/");
    exit();
}

// Otherwise redirect to the React login screen
header("Location: /MicroFinance/login");
exit();
?>