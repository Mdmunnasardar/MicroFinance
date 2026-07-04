<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ============================================
// GET FILTER PARAMETERS
// ============================================
$search = isset($_GET['search']) ? $_GET['search'] : '';
$branch_filter = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$day_filter = isset($_GET['meeting_day']) ? $_GET['meeting_day'] : '';

// ============================================
// BUILD WHERE CLAUSE
// ============================================
$where = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "c.committee_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($branch_filter)) {
    $where[] = "c.branch_id = ?";
    $params[] = $branch_filter;
    $types .= "i";
}

if ($status_filter !== '') {
    $where[] = "c.is_active = ?";
    $params[] = $status_filter;
    $types .= "i";
}

if (!empty($day_filter)) {
    $where[] = "c.meeting_day = ?";
    $params[] = $day_filter;
    $types .= "s";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// ============================================
// MAIN QUERY
// ============================================
$sql = "
SELECT c.*, 
       b.branch_name, 
       u.full_name AS officer_name,
       COUNT(m.member_id) as member_count
FROM committees c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN users u ON c.field_officer_id = u.user_id
LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
$where_clause
GROUP BY c.committee_id
ORDER BY c.committee_id DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$committees = $stmt->get_result();

// ============================================
// GET STATISTICS
// ============================================
$stats_sql = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
FROM committees
";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Get branches for filter
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Get total members
$total_members = $conn->query("SELECT COUNT(*) as total FROM members WHERE is_active = 1")->fetch_assoc();

// Get days for filter
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committees - MicroFinance</title>
    <link rel="stylesheet" href="../assets/css/committees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- ==========================================
         PAGE HEADER
         ========================================== -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <div class="page-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h1 class="page-title">Committees</h1>
                <p class="page-subtitle">Manage all committees and their members</p>
            </div>
        </div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i>
            Add Committee
        </a>
    </div>

    <!-- ==========================================
         STATISTICS CARDS
         ========================================== -->
    <div class="stat-grid">
        <div class="stat-card stat-card-primary animate-slide-up" style="animation-delay: 0.05s">
            <div class="flex justify-between items-start">
                <div>
                    <p class="stat-label">Total Committees</p>
                    <p class="stat-number"><?php echo $stats['total'] ?? 0; ?></p>
                </div>
                <div class="stat-icon stat-icon-primary">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-success animate-slide-up" style="animation-delay: 0.1s">
            <div class="flex justify-between items-start">
                <div>
                    <p class="stat-label">Active</p>
                    <p class="stat-number" style="color: var(--success);"><?php echo $stats['active'] ?? 0; ?></p>
                </div>
                <div class="stat-icon stat-icon-success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-danger animate-slide-up" style="animation-delay: 0.15s">
            <div class="flex justify-between items-start">
                <div>
                    <p class="stat-label">Inactive</p>
                    <p class="stat-number" style="color: var(--danger);"><?php echo $stats['inactive'] ?? 0; ?></p>
                </div>
                <div class="stat-icon stat-icon-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-purple animate-slide-up" style="animation-delay: 0.2s">
            <div class="flex justify-between items-start">
                <div>
                    <p class="stat-label">Total Members</p>
                    <p class="stat-number" style="color: var(--purple);"><?php echo $total_members['total'] ?? 0; ?></p>
                </div>
                <div class="stat-icon stat-icon-purple">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         FILTER SECTION
         ========================================== -->
    <div class="filter-section animate-slide-up" style="animation-delay: 0.25s">
        <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
            <div>
                <label class="filter-label">Search</label>
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Committee name..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding-left: 40px;">
                </div>
            </div>
            <div>
                <label class="filter-label">Branch</label>
                <select name="branch_id" class="filter-select">
                    <option value="">All Branches</option>
                    <?php while($b = $branches->fetch_assoc()): ?>
                    <option value="<?php echo $b['branch_id']; ?>" 
                        <?php echo $branch_filter == $b['branch_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($b['branch_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label class="filter-label">Meeting Day</label>
                <select name="meeting_day" class="filter-select">
                    <option value="">All Days</option>
                    <?php foreach($days as $day): ?>
                    <option value="<?php echo $day; ?>" 
                        <?php echo $day_filter == $day ? 'selected' : ''; ?>>
                        <?php echo $day; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-filter"></i>
                    Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- ==========================================
         COMMITTEE LIST
         ========================================== -->
    <?php if ($committees->num_rows > 0): ?>
    <div class="committee-grid animate-slide-up" style="animation-delay: 0.3s">
        <?php while($row = $committees->fetch_assoc()): ?>
        <div class="committee-card">
            <!-- Card Header -->
            <div class="card-header">
                <div>
                    <h3 class="committee-name"><?php echo htmlspecialchars($row['committee_name']); ?></h3>
                    <span class="committee-code">#<?php echo $row['committee_id']; ?></span>
                </div>
                <span class="badge <?php echo $row['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-building"></i> Branch</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user-tie"></i> Officer</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['officer_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-alt"></i> Formed</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($row['formed_date'])); ?></span>
                </div>

                <div class="badge-group">
                    <span class="badge badge-info badge-sm">
                        <i class="fas fa-calendar-day"></i> <?php echo $row['meeting_day']; ?>
                    </span>
                    <span class="badge badge-gray badge-sm">
                        <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['meeting_time'])); ?>
                    </span>
                    <span class="badge badge-purple badge-sm">
                        <i class="fas fa-users"></i> <?php echo $row['member_count'] ?? 0; ?> members
                    </span>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="card-footer">
                <div class="flex items-center gap-1">
                    <a href="view.php?id=<?php echo $row['committee_id']; ?>" 
                       class="btn btn-secondary btn-sm btn-icon" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?php echo $row['committee_id']; ?>" 
                       class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="assign-member.php?committee_id=<?php echo $row['committee_id']; ?>" 
                       class="btn btn-secondary btn-sm btn-icon" title="Manage Members">
                        <i class="fas fa-users"></i>
                    </a>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="toggleStatus(<?php echo $row['committee_id']; ?>, <?php echo $row['is_active']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="btn <?php echo $row['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm btn-icon"
                            title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                        <i class="fas fa-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                    </button>
                    <button onclick="deleteCommittee(<?php echo $row['committee_id']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="btn btn-danger btn-sm btn-icon"
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state animate-slide-up" style="animation-delay: 0.3s">
        <div class="empty-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3 class="empty-title">No Committees Found</h3>
        <p class="empty-description">Get started by creating your first committee.</p>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i>
            Add Committee
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- ==========================================
     SCRIPTS
     ========================================== -->
<script>
// Toggle Status
function toggleStatus(id, currentStatus, name) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} "${name}"?`)) {
        window.location.href = `toggle-status.php?id=${id}&status=${currentStatus ? 0 : 1}`;
    }
}

// Delete Committee
function deleteCommittee(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        window.location.href = `delete.php?id=${id}`;
    }
}
</script>

</body>
</html>

<?php include "../includes/footer.php"; ?>