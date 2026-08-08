<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Auth gate for legacy pages that still include this header layout.
    // After Phase 7C, any direct request for /MicroFinance/login.php etc.
    // is intercepted by the project-root .htaccess and never reaches PHP.
    // If a legacy page is loaded directly (e.g. via a stale internal
    // include), we still want to bounce the user to the React SPA login
    // screen rather than the retired /MicroFinance/index.php shim.
    header("Location: /MicroFinance/login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroFinance - Dashboard</title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Chart.js - MUST BE LOADED -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>