<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'] ?? '';

// Check permissions - only admins and branch managers can view others
if ($user_id != $current_user_id) {
    if (!in_array($current_user_role, ['admin', 'branch_manager'])) {
        header("Location: dashboard.php");
        exit();
    }
}

// Get user details
$sql = "
SELECT u.*, b.branch_name
FROM users u
LEFT JOIN branches b ON u.branch_id = b.branch_id
WHERE u.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php");
    exit();
}

// Get statistics based on user role
$stats = [];
if ($user['role'] == 'field_officer') {
    $stats_sql = "
    SELECT 
        COUNT(DISTINCT c.committee_id) as total_committees,
        COUNT(DISTINCT m.member_id) as total_members,
        (SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE collected_by = ? AND DATE(payment_date) = CURDATE()) as today_collection,
        (SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE collected_by = ?) as total_collection
    FROM users u
    LEFT JOIN committees c ON u.user_id = c.field_officer_id
    LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
    WHERE u.user_id = ?
    ";
    $stmt = $conn->prepare($stats_sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
}

include "includes/header.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="profile-container py-6">

    <!-- ==========================================
         PROFILE HEADER
         ========================================== -->
    <div class="profile-header animate-slide-up">
        <div class="profile-top">
            <!-- Avatar -->
            <div class="profile-avatar">
                <?php if (!empty($user['avatar']) && file_exists("uploads/avatars/" . $user['avatar'])): ?>
                <img src="uploads/avatars/<?php echo $user['avatar']; ?>" alt="Avatar" class="avatar-img">
                <?php else: ?>
                <div class="avatar-img" style="background: var(--primary-gradient);">
                    <?php 
                    $initial = strtoupper(substr($user['full_name'], 0, 2));
                    if (strpos($user['full_name'], ' ') !== false) {
                        $names = explode(' ', $user['full_name']);
                        $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                    }
                    echo $initial;
                    ?>
                </div>
                <?php endif; ?>
                <?php if ($user_id == $current_user_id): ?>
                <div class="avatar-badge" onclick="document.getElementById('avatarUpload').click()" title="Change Avatar">
                    <i class="fas fa-camera"></i>
                </div>
                <form id="avatarForm" method="POST" action="profile/upload-avatar.php" enctype="multipart/form-data" style="display:none;">
                    <input type="file" id="avatarUpload" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                </form>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p class="profile-username">
                    <i class="fas fa-at"></i> <?php echo htmlspecialchars($user['username']); ?>
                </p>
                <div class="profile-meta">
                    <span class="role-badge <?php echo $user['role']; ?>">
                        <i class="fas fa-user-tag"></i> <?php echo str_replace('_', ' ', $user['role']); ?>
                    </span>
                    <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle" style="font-size: 8px;"></i>
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                    <?php if ($user['branch_name']): ?>
                    <span class="meta-item">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($user['branch_name']); ?>
                    </span>
                    <?php endif; ?>
                    <span class="meta-item">
                        <i class="fas fa-calendar-alt"></i> Joined <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </span>
                    <?php if ($user_id != $current_user_id): ?>
                    <span class="meta-item">
                        <i class="fas fa-user-id"></i> User ID: #<?php echo $user['user_id']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions - ROLE BASED -->
            <div class="profile-actions">
                <?php if ($user_id == $current_user_id): ?>
                    <!-- OWN PROFILE - Show edit options -->
                    <a href="profile/edit.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="profile/change-password.php" class="btn btn-secondary">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                <?php endif; ?>
                
                <?php if ($current_user_role == 'admin' && $user_id != $current_user_id): ?>
                    <!-- ADMIN VIEWING OTHER USER - Full access -->
                    <a href="profile/edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                    <button onclick="deleteUser(<?php echo $user_id; ?>)" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                <?php endif; ?>
                
                <?php if ($current_user_role == 'branch_manager' && $user_id != $current_user_id && $user['role'] == 'field_officer'): ?>
                    <!-- BRANCH MANAGER VIEWING FIELD OFFICER -->
                    <a href="profile/edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Officer
                    </a>
                    <a href="Committees/officers/view.php?id=<?php echo $user_id; ?>" class="btn btn-info">
                        <i class="fas fa-users-cog"></i> View Committees
                    </a>
                <?php endif; ?>
                
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- ==========================================
         STATISTICS - Only for Field Officers
         ========================================== -->
    <?php if ($user['role'] == 'field_officer'): ?>
    <div class="stat-grid animate-slide-up" style="animation-delay: 0.1s">
        <div class="stat-card primary">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Committees</p>
                    <p class="stat-number primary-text"><?php echo $stats['total_committees'] ?? 0; ?></p>
                </div>
                <div class="stat-icon primary-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Members</p>
                    <p class="stat-number success-text"><?php echo $stats['total_members'] ?? 0; ?></p>
                </div>
                <div class="stat-icon success-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Today's Collection</p>
                    <p class="stat-number warning-text">$<?php echo number_format($stats['today_collection'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-icon warning-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Collection</p>
                    <p class="stat-number purple-text">$<?php echo number_format($stats['total_collection'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-icon purple-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ==========================================
         DETAILS & WORK SECTION
         ========================================== -->
    <div class="grid grid-cols-3 gap-6">
        
        <!-- Details (2 columns) -->
        <div class="grid-cols-2" style="grid-column: span 2;">
            <div class="detail-section animate-slide-up" style="animation-delay: 0.15s">
                <h4 class="section-title">
                    <i class="fas fa-info-circle"></i> Personal Information
                </h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <p class="detail-label">Full Name</p>
                        <p class="detail-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Username</p>
                        <p class="detail-value">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Phone</p>
                        <p class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Role</p>
                        <p class="detail-value">
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <?php echo str_replace('_', ' ', $user['role']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Branch</p>
                        <p class="detail-value"><?php echo htmlspecialchars($user['branch_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Status</p>
                        <p class="detail-value">
                            <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Joined Date</p>
                        <p class="detail-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <?php if ($user_id != $current_user_id): ?>
                    <div class="detail-item">
                        <p class="detail-label">User ID</p>
                        <p class="detail-value">#<?php echo $user['user_id']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar (1 column) - ROLE BASED -->
        <div style="grid-column: span 1;">
            
            <!-- FIELD OFFICER - Work Links -->
            <?php if ($user['role'] == 'field_officer'): ?>
            <div class="detail-section animate-slide-up" style="animation-delay: 0.2s">
                <h4 class="section-title">
                    <i class="fas fa-briefcase"></i> My Work
                </h4>
                <div class="work-grid">
                    <a href="field-officer/committees.php?officer_id=<?php echo $user_id; ?>" class="work-item">
                        <div class="work-icon primary">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="work-info">
                            <p class="work-title">Committees</p>
                            <p class="work-desc">View my committees</p>
                        </div>
                    </a>
                    <a href="field-officer/members.php?officer_id=<?php echo $user_id; ?>" class="work-item">
                        <div class="work-icon success">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="work-info">
                            <p class="work-title">Members</p>
                            <p class="work-desc">View my members</p>
                        </div>
                    </a>
                    <a href="field-officer/collections.php?officer_id=<?php echo $user_id; ?>" class="work-item">
                        <div class="work-icon warning">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="work-info">
                            <p class="work-title">Collections</p>
                            <p class="work-desc">Collect payments</p>
                        </div>
                    </a>
                    <a href="field-officer/dashboard.php?officer_id=<?php echo $user_id; ?>" class="work-item" style="grid-column: span 3;">
                        <div class="work-icon primary" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="work-info">
                            <p class="work-title">Full Dashboard</p>
                            <p class="work-desc">View complete performance dashboard</p>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ADMIN ACTIONS - Only for Admin viewing other users -->
            <?php if ($current_user_role == 'admin' && $user_id != $current_user_id): ?>
            <div class="detail-section animate-slide-up" style="animation-delay: 0.25s">
                <h4 class="section-title">
                    <i class="fas fa-shield-alt"></i> Admin Actions
                </h4>
                <div class="space-y-2">
                    <a href="profile/edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                    <button onclick="resetPassword(<?php echo $user_id; ?>)" class="btn btn-warning btn-block">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    <button onclick="forceLogout(<?php echo $user_id; ?>)" class="btn btn-danger btn-block">
                        <i class="fas fa-sign-out-alt"></i> Force Logout
                    </button>
                    <button onclick="changeRole(<?php echo $user_id; ?>)" class="btn btn-secondary btn-block">
                        <i class="fas fa-exchange-alt"></i> Change Role
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- BRANCH MANAGER ACTIONS - Viewing Field Officer -->
            <?php if ($current_user_role == 'branch_manager' && $user_id != $current_user_id && $user['role'] == 'field_officer'): ?>
            <div class="detail-section animate-slide-up" style="animation-delay: 0.25s">
                <h4 class="section-title">
                    <i class="fas fa-user-cog"></i> Management
                </h4>
                <div class="space-y-2">
                    <a href="profile/edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-edit"></i> Edit Officer
                    </a>
                    <a href="Committees/officers/view.php?id=<?php echo $user_id; ?>" class="btn btn-success btn-block">
                        <i class="fas fa-users-cog"></i> View Committees
                    </a>
                    <a href="field-officer/dashboard.php?officer_id=<?php echo $user_id; ?>" class="btn btn-info btn-block">
                        <i class="fas fa-chart-bar"></i> View Dashboard
                    </a>
                    <button onclick="toggleOfficerStatus(<?php echo $user_id; ?>, <?php echo $user['is_active']; ?>)" 
                            class="btn <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-block">
                        <i class="fas fa-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==========================================
     SCRIPTS
     ========================================== -->
<script>
function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = 'profile/delete.php?id=' + id;
    }
}

function resetPassword(id) {
    if (confirm('Reset password for this user? They will be notified.')) {
        window.location.href = 'profile/reset-password.php?id=' + id;
    }
}

function forceLogout(id) {
    if (confirm('Force logout this user?')) {
        window.location.href = 'profile/force-logout.php?id=' + id;
    }
}

function changeRole(id) {
    const roles = ['admin', 'branch_manager', 'field_officer', 'member'];
    const newRole = prompt('Enter new role (admin, branch_manager, field_officer, member):');
    if (newRole && roles.includes(newRole)) {
        window.location.href = 'profile/change-role.php?id=' + id + '&role=' + newRole;
    }
}

function toggleOfficerStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this officer?`)) {
        window.location.href = 'Committees/officers/toggle-status.php?id=' + id + '&status=' + (currentStatus ? 0 : 1);
    }
}
</script>

<script src="assets/js/profile.js"></script>

</body>
</html>

<?php include "includes/footer.php"; ?>