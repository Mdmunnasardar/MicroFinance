<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = "../uploads/avatars/";

// Create directory if not exists
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['avatar']['type'];
    $file_size = $_FILES['avatar']['size'];
    $file_name = time() . '_' . $_FILES['avatar']['name'];
    $file_path = $upload_dir . $file_name;
    
    if (!in_array($file_type, $allowed)) {
        header("Location: ../profile.php?error=invalid_type");
        exit();
    }
    
    if ($file_size > 5 * 1024 * 1024) {
        header("Location: ../profile.php?error=file_too_large");
        exit();
    }
    
    // Delete old avatar
    $sql = "SELECT avatar FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $old_avatar = $stmt->get_result()->fetch_assoc()['avatar'];
    if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
        unlink($upload_dir . $old_avatar);
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $file_path)) {
        $update_sql = "UPDATE users SET avatar = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $file_name, $user_id);
        $stmt->execute();
        header("Location: ../profile.php?success=avatar");
        exit();
    }
}

header("Location: ../profile.php?error=upload_failed");
exit();
?>