<?php

$host = "127.0.0.1";
$port = 3306;   // Workbench port (confirm করলে change হবে)
$user = "root";
$pass = "";     // empty password
$db   = "MicroFinance";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

echo "DB Connected Successfully";
?>