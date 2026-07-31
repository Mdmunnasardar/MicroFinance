<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

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
        OR m.national_id LIKE '%$search%'
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
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Members CSS -->
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<div class="container">

    <!-- Back to Dashboard -->
    <a href="../dashboard.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>

    <h1 class="page-title">
        <i class="fa-solid fa-users"></i> Members Management
    </h1>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Members</div>
                    <div class="stat-value"><?php echo number_format($total_members); ?></div>
                </div>
                <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12%</div>
            <div class="stat-sub">+18 this month</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Active Members</div>
                    <div class="stat-value"><?php echo number_format($active_members); ?></div>
                </div>
                <div class="stat-icon green"><i class="fa-solid fa-user-check"></i></div>
            </div>
            <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8%</div>
            <div class="stat-sub"><?php echo $total_members > 0 ? round(($active_members / $total_members) * 100) : 0; ?>% of total</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Total Loans</div>
                    <div class="stat-value">$<?php echo number_format($total_loans, 0); ?></div>
                </div>
                <div class="stat-icon purple"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
            <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12.5%</div>
            <div class="stat-sub">+12.5% this month</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-label">Inactive Members</div>
                    <div class="stat-value"><?php echo number_format($inactive_members); ?></div>
                </div>
                <div class="stat-icon red"><i class="fa-solid fa-user-slash"></i></div>
            </div>
            <div class="stat-trend down"><i class="fa-solid fa-arrow-down"></i> Needs attention</div>
            <div class="stat-sub">Requires review</div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by name, code, phone or NID..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="branch" class="form-select">
                    <option value="">All Branches</option>
                    <?php while ($b = $branches->fetch_assoc()): ?>
                        <option value="<?php echo $b['branch_id']; ?>"
                            <?php if ($branch == $b['branch_id']) echo "selected"; ?>>
                            <?php echo htmlspecialchars($b['branch_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" <?php if ($status == "1") echo "selected"; ?>>Active</option>
                    <option value="0" <?php if ($status == "0") echo "selected"; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted">Total: <?php echo $members->num_rows; ?> members</span>
        <div>
            <a href="export_csv.php" class="btn btn-success me-2">
                <i class="fa-solid fa-file-export"></i> Export CSV
            </a>
            <a href="add.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Member
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Phone</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members->num_rows > 0): ?>
                    <?php while ($row = $members->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['member_code']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="fa-solid fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <i class="fa-solid <?php echo $row['is_active'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="view.php?id=<?php echo $row['member_id']; ?>"
                                       class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['member_id']; ?>"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['member_id']; ?>"
                                       class="btn btn-sm btn-outline-danger" title="Delete"
                                       onclick="return confirm('Delete this member?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-users" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                            No members found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>