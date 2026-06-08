<?php
session_start();
include "config/db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>MicroFinance Login</title>
</head>
<body>

<h2>Login System</h2>

<form method="POST" action="login.php">
    <input type="text" name="username" placeholder="Username" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit">Login</button>
</form>

</body>
</html>