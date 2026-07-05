// After successful login - REPLACE THIS SECTION
if (password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['full_name'];
    
    // ==========================================
    // ROLE-BASED REDIRECT
    // ==========================================
    switch($user['role']) {
        case 'admin':
            header("Location: dashboard.php");
            break;
        case 'branch_manager':
            header("Location: dashboard.php");
            break;
        case 'field_officer':
            header("Location: field-officer/dashboard.php");
            break;
        case 'member':
            header("Location: member-dashboard.php");
            break;
        default:
            header("Location: dashboard.php");
    }
    exit();
}