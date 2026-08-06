<?php
// Legacy logout endpoint — kept as a thin compatibility shim for any
// stale bookmark/URL that still hits this controller after Phase 7D.
//
// After Phase 7C's project-root .htaccess intercepts /MicroFinance/logout.php
// and serves the SPA shell instead, this script will normally never run.
// If it does execute (e.g. a direct internal include from a leftover legacy
// page), we still terminate the PHP session and bounce the browser to the
// React SPA login screen at /MicroFinance/login. The JSON API at
// /MicroFinance/backend/public/api/auth/logout is the source of truth.
session_start();
session_destroy();
header("Location: /MicroFinance/login");
exit();
?>