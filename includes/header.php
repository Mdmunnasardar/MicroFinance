<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MicroFinance Management System</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">

<!-- Tailwind Output -->
<link rel="stylesheet" href="/assets/css/output.css">

<!-- Global CSS -->
<link rel="stylesheet" href="/assets/css/app.css">

<!-- Components -->
<link rel="stylesheet" href="/assets/css/sidebar.css">
<link rel="stylesheet" href="/assets/css/topbar.css">
<link rel="stylesheet" href="/assets/css/cards.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<link rel="stylesheet" href="/assets/css/table.css">
<link rel="stylesheet" href="/assets/css/responsive.css">

</head>

<body>