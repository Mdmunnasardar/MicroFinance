<?php
session_start();
include "config/db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MicroFinance System</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Animated background */
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(-45deg, #0f2027, #203a43, #2c5364, #1c92d2);
    background-size: 400% 400%;
    animation: gradientBG 10s ease infinite;
    font-family: 'Segoe UI';
}

@keyframes gradientBG {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

/* Glass card */
.glass {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 40px;
    width: 360px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    color: white;
    text-align: center;
}

/* Logo */
.logo {
    width: 70px;
    margin-bottom: 15px;
}

/* Input */
.form-control {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
}

.form-control::placeholder {
    color: #ddd;
}

/* Button */
.btn-custom {
    background: #00c6ff;
    color: white;
    border: none;
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    transition: 0.3s;
}

.btn-custom:hover {
    background: #0072ff;
}

/* text */
small {
    color: #ccc;
}
</style>

</head>

<body>

<div class="glass">

    <!-- Logo -->
    <img src="https://cdn-icons-png.flaticon.com/512/2830/2830284.png" class="logo">

    <h3>MicroFinance System</h3>
    <small>Secure NGO Management Platform</small>

    <form method="POST" action="login.php" class="mt-4">

        <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>

        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

        <button class="btn btn-custom">Login</button>

    </form>

    <small>Powered by Smart Finance System</small>

</div>

</body>
</html>