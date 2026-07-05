<?php
// includes/topbar.php - Top Navigation Bar with Profile
?>

<!-- Top Navigation Bar -->
<nav class="topbar">
    <div class="topbar-left">
        <!-- Mobile Toggle Button -->
        <button class="topbar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title -->
        <div class="topbar-title">
            <span id="pageTitle">Dashboard</span>
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Search Box -->
        <div class="topbar-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search..." id="globalSearch">
        </div>
        
        <!-- Notifications -->
        <div class="topbar-notifications">
            <button class="notification-btn" id="notificationToggle">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="dropdown-header">
                    <span>Notifications</span>
                    <a href="#">Mark all read</a>
                </div>
                <div class="dropdown-body">
                    <div class="notification-item">
                        <div class="notification-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <p class="notification-text">Loan payment due for John Doe</p>
                            <span class="notification-time">2 hours ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <p class="notification-text">Member Sarah Johnson joined</p>
                            <span class="notification-time">5 hours ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <p class="notification-text">New savings deposit received</p>
                            <span class="notification-time">1 day ago</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="topbar-user">
            <button class="user-btn" id="userMenuToggle">
                <div class="user-avatar">
                    <?php 
                    // Get user details from session
                    $user_name = $_SESSION['name'] ?? 'User';
                    $user_role = $_SESSION['role'] ?? 'user';
                    $user_id = $_SESSION['user_id'] ?? 0;
                    
                    // Get avatar from database
                    $avatar = null;
                    if ($user_id > 0) {
                        $avatar_sql = "SELECT avatar FROM users WHERE user_id = ?";
                        $stmt = $conn->prepare($avatar_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $avatar_row = $result->fetch_assoc();
                            $avatar = $avatar_row['avatar'];
                        }
                    }
                    
                    // Get initials from name
                    $initials = strtoupper(substr($user_name, 0, 2));
                    if (strpos($user_name, ' ') !== false) {
                        $names = explode(' ', $user_name);
                        $initials = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                    }
                    
                    // Check if avatar exists and file exists
                    if ($avatar && file_exists("uploads/avatars/" . $avatar)) {
                        echo '<img src="uploads/avatars/' . $avatar . '" alt="Avatar" class="avatar-img">';
                    } else {
                        // Show initials with gradient background
                        echo '<span class="avatar-text" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-weight: 600; font-size: 16px; border-radius: 50%;">' . $initials . '</span>';
                    }
                    ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role">
                        <?php 
                        $role = $_SESSION['role'] ?? 'user';
                        echo ucfirst(str_replace('_', ' ', $role)); 
                        ?>
                    </span>
                </div>
                <i class="fas fa-chevron-down user-arrow"></i>
            </button>
            
            <!-- User Dropdown Menu -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="user-avatar-large">
                        <?php 
                        if ($avatar && file_exists("uploads/avatars/" . $avatar)) {
                            echo '<img src="uploads/avatars/' . $avatar . '" alt="Avatar" class="avatar-img">';
                        } else {
                            echo '<span class="avatar-text" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-weight: 700; font-size: 24px; border-radius: 50%;">' . $initials . '</span>';
                        }
                        ?>
                    </div>
                    <div>
                        <p class="dropdown-user-name"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="dropdown-user-role"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-body">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="profile/edit.php" class="dropdown-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Edit Profile</span>
                    </a>
                    <a href="profile/change-password.php" class="dropdown-item">
                        <i class="fas fa-key"></i>
                        <span>Change Password</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    
                    <?php if ($role == 'field_officer'): ?>
                    <a href="field-officer/dashboard.php" class="dropdown-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>My Dashboard</span>
                    </a>
                    <a href="field-officer/members.php" class="dropdown-item">
                        <i class="fas fa-users"></i>
                        <span>My Members</span>
                    </a>
                    <a href="field-officer/committees.php" class="dropdown-item">
                        <i class="fas fa-users-cog"></i>
                        <span>My Committees</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($role == 'admin' || $role == 'branch_manager'): ?>
                    <a href="Committees/officers/index.php" class="dropdown-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Field Officers</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* ==========================================
   TOPBAR STYLES
   ========================================== */
.topbar {
    background: white;
    padding: 12px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.topbar-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: 0.3s ease;
}

.topbar-toggle:hover {
    background: #f1f5f9;
}

.topbar-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Search */
.topbar-search {
    position: relative;
    display: flex;
    align-items: center;
}

.topbar-search i {
    position: absolute;
    left: 14px;
    color: #94a3b8;
    font-size: 14px;
}

.topbar-search input {
    padding: 8px 14px 8px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    width: 220px;
    transition: 0.3s ease;
    background: #f8fafc;
}

.topbar-search input:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
    width: 280px;
}

/* Notifications */
.topbar-notifications {
    position: relative;
}

.notification-btn {
    position: relative;
    background: none;
    border: none;
    font-size: 20px;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: 0.3s ease;
}

.notification-btn:hover {
    background: #f1f5f9;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* User Profile */
.topbar-user {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 12px 4px 4px;
    border-radius: 8px;
    transition: 0.3s ease;
}

.user-btn:hover {
    background: #f1f5f9;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: #eef2ff;
}

.user-avatar .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar .avatar-text {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    border-radius: 50%;
}

.user-info {
    text-align: left;
    line-height: 1.3;
}

.user-name {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.user-role {
    display: block;
    font-size: 11px;
    color: #94a3b8;
    text-transform: capitalize;
}

.user-arrow {
    color: #94a3b8;
    font-size: 12px;
    transition: 0.3s ease;
}

.user-btn.active .user-arrow {
    transform: rotate(180deg);
}

/* User Dropdown */
.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12);
    border: 1px solid #e2e8f0;
    display: none;
    overflow: hidden;
    z-index: 1000;
}

.user-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

.user-dropdown .dropdown-header {
    padding: 20px 20px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.user-dropdown .user-avatar-large {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: #eef2ff;
}

.user-dropdown .user-avatar-large .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-dropdown .user-avatar-large .avatar-text {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    border-radius: 50%;
}

.user-dropdown .dropdown-user-name {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.user-dropdown .dropdown-user-role {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
    text-transform: capitalize;
}

.user-dropdown .dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0 16px;
}

.user-dropdown .dropdown-body {
    padding: 8px 0;
}

.user-dropdown .dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: #475569;
    text-decoration: none;
    transition: 0.3s ease;
    font-size: 14px;
}

.user-dropdown .dropdown-item:hover {
    background: #f8fafc;
    color: #4f46e5;
}

.user-dropdown .dropdown-item i {
    width: 20px;
    text-align: center;
    color: #94a3b8;
}

.user-dropdown .dropdown-item:hover i {
    color: #4f46e5;
}

.user-dropdown .dropdown-item.logout {
    color: #ef4444;
}

.user-dropdown .dropdown-item.logout:hover {
    background: #fee2e2;
    color: #dc2626;
}

.user-dropdown .dropdown-item.logout i {
    color: #ef4444;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 360px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12);
    border: 1px solid #e2e8f0;
    display: none;
    overflow: hidden;
    z-index: 1000;
}

.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

.notification-dropdown .dropdown-header {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}

.notification-dropdown .dropdown-header span {
    font-weight: 600;
    color: #1e293b;
}

.notification-dropdown .dropdown-header a {
    font-size: 12px;
    color: #4f46e5;
    text-decoration: none;
}

.notification-dropdown .dropdown-body {
    max-height: 300px;
    overflow-y: auto;
    padding: 8px 0;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 12px 20px;
    transition: 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: #f8fafc;
}

.notification-item .notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

.notification-item .notification-icon.warning {
    background: #fef3c7;
    color: #f59e0b;
}

.notification-item .notification-icon.success {
    background: #d1fae5;
    color: #10b981;
}

.notification-item .notification-icon.info {
    background: #eef2ff;
    color: #4f46e5;
}

.notification-item .notification-text {
    font-size: 13px;
    color: #475569;
    margin: 0;
}

.notification-item .notification-time {
    font-size: 11px;
    color: #94a3b8;
}

.notification-dropdown .dropdown-footer {
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.notification-dropdown .dropdown-footer a {
    font-size: 13px;
    color: #4f46e5;
    text-decoration: none;
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .topbar {
        padding: 10px 16px;
    }
    
    .topbar-toggle {
        display: block;
    }
    
    .topbar-title {
        font-size: 16px;
    }
    
    .topbar-search input {
        width: 150px;
    }
    
    .topbar-search input:focus {
        width: 180px;
    }
    
    .user-info {
        display: none;
    }
    
    .user-arrow {
        display: none;
    }
    
    .user-dropdown {
        width: 280px;
        right: -60px;
    }
    
    .notification-dropdown {
        width: 300px;
        right: -40px;
    }
}

@media (max-width: 480px) {
    .topbar-search {
        display: none;
    }
    
    .user-dropdown {
        width: 260px;
        right: -80px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Dropdown Toggle
    const userBtn = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            this.classList.toggle('active');
            
            const notifDropdown = document.getElementById('notificationDropdown');
            if (notifDropdown) notifDropdown.classList.remove('show');
        });
    }
    
    // Notification Dropdown Toggle
    const notifBtn = document.getElementById('notificationToggle');
    const notifDropdown = document.getElementById('notificationDropdown');
    
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
            
            if (userDropdown) userDropdown.classList.remove('show');
            if (userBtn) userBtn.classList.remove('active');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (userDropdown && !userDropdown.contains(e.target) && !userBtn?.contains(e.target)) {
            userDropdown.classList.remove('show');
            if (userBtn) userBtn.classList.remove('active');
        }
        
        if (notifDropdown && !notifDropdown.contains(e.target) && !notifBtn?.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });
    
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    console.log('✅ Topbar loaded successfully');
});
</script>