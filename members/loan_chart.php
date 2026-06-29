<?php
session_start();
include "../config/db.php";

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ============================================
// BUSINESS LOGIC
// ============================================

// Get filters
$search = $_GET['search'] ?? "";
$branch = $_GET['branch'] ?? "";
$status = $_GET['status'] ?? "";

// Get statistics
$total_members = $conn->query("SELECT COUNT(*) as t FROM members")->fetch_assoc()['t'] ?? 0;
$active_members = $conn->query("SELECT COUNT(*) as t FROM members WHERE is_active=1")->fetch_assoc()['t'] ?? 0;
$inactive_members = $conn->query("SELECT COUNT(*) as t FROM members WHERE is_active=0")->fetch_assoc()['t'] ?? 0;
$total_loans = $conn->query("SELECT COALESCE(SUM(principal_amount),0) as t FROM loans")->fetch_assoc()['t'] ?? 0;

// Build members query
$sql = "
SELECT m.*, c.committee_name, b.branch_name
FROM members m
LEFT JOIN committees c ON m.committee_id = c.committee_id
LEFT JOIN branches b ON m.branch_id = b.branch_id
WHERE 1
";

if($search != ""){
    $sql .= " AND (
        m.full_name LIKE '%$search%'
        OR m.member_code LIKE '%$search%'
        OR m.phone LIKE '%$search%'
        OR m.email LIKE '%$search%'
    )";
}

if($branch != ""){
    $sql .= " AND m.branch_id='$branch'";
}

if($status != ""){
    $sql .= " AND m.is_active='$status'";
}

$sql .= " ORDER BY m.member_id DESC";

$members = $conn->query($sql);

// Get branches for filter dropdown
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Prepare data for components
$stats_data = [
    'total' => $total_members,
    'active' => $active_members,
    'inactive' => $inactive_members,
    'loans' => $total_loans,
    'active_percentage' => $total_members > 0 ? round(($active_members/$total_members)*100, 1) : 0
];

$filter_data = [
    'search' => $search,
    'branch' => $branch,
    'status' => $status,
    'branches' => $branches
];

$table_data = [
    'members' => $members,
    'total' => $total_members
];

// ============================================
// RENDER VIEW
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - MicroFinance</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<!-- Include Layout Components -->
<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>
<?php include "../includes/topbar.php"; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container mx-auto px-4 py-8">
        
        <!-- Page Header -->
        <?php include "../includes/components/member/page-header.php"; ?>
        
        <!-- Stats Cards -->
        <?php include "../includes/components/member/stats.php"; ?>
        
        <!-- Filters -->
        <?php include "../includes/components/member/filters.php"; ?>
        
        <!-- Members Table -->
        <?php include "../includes/components/member/table.php"; ?>
        
    </div>
</div>

<!-- Quick View Modal -->
<?php include "../includes/components/member/modal.php"; ?>

<!-- Footer -->
<?php include "../includes/footer.php"; ?>

<!-- JavaScript -->
<script src="../assets/js/members.js"></script>

</body>
</html>