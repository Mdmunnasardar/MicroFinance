<?php
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit();
}

// Otherwise redirect to login
header("Location: login.php");
exit();
?>