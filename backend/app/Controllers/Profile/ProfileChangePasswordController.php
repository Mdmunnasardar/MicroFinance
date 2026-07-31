<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if (isset($_POST['submit'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash
    $sql = "SELECT password_hash FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_hash, $user_id);
        if ($stmt->execute()) {
            header("Location: ../profile.php?success=password");
            exit();
        }
    }
}

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="profile-container">
        <div class="form-card">
            <div class="form-header warning">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h2>Change Password</h2>
                        <p>Update your password securely</p>
                    </div>
                </div>
            </div>
            <div class="form-body">
                <?php if (isset($error)): ?>
                <div class="bg-danger-bg border-l-4 border-danger text-danger-dark p-4 rounded-xl mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password <span class="required">*</span></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password <span class="required">*</span></label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <small class="text-gray-400">Minimum 6 characters</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password <span class="required">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                        <a href="../profile.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>