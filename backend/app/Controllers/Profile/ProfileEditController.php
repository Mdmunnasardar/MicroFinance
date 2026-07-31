<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'] ?? '';

// Check permissions
if ($user_id != $current_user_id) {
    if (!in_array($current_user_role, ['admin', 'branch_manager'])) {
        header("Location: ../profile.php");
        exit();
    }
}

// Get user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../profile.php");
    exit();
}

// Get branches
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Handle form submission
if (isset($_POST['submit'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'] ?? '';
    $branch_id = $_POST['branch_id'] ?? null;
    
    $update_sql = "UPDATE users SET full_name = ?, phone = ?, branch_id = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssii", $full_name, $phone, $branch_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ../profile.php?id=" . $user_id . "&success=1");
        exit();
    }
}

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="profile-container">
        <div class="form-card">
            <div class="form-header primary">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h2>Edit Profile</h2>
                        <p>Update your personal information</p>
                    </div>
                </div>
            </div>
            <div class="form-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-gray-400">Username cannot be changed</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">Select Branch</option>
                                <?php while($b = $branches->fetch_assoc()): ?>
                                <option value="<?php echo $b['branch_id']; ?>" 
                                    <?php echo $user['branch_id'] == $b['branch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="../profile.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>