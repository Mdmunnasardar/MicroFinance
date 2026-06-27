<?php
session_start();
include "config/db.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: login.php?error=Please enter username and password");
    exit();
}

$sql = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: login.php?error=Invalid password");
        exit();
    }
} else {
    header("Location: login.php?error=User not found");
    exit();
}
?>