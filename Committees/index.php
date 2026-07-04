<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$branch_filter = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$day_filter = isset($_GET['meeting_day']) ? $_GET['meeting_day'] : '';

// Build WHERE clause
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

// Main query with member count
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

// Get statistics
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

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <div class="header-icon primary">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h1 class="header-title">Committees</h1>
                <p class="header-subtitle">Manage all committees and their members</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Add Committee
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stat-grid">
        <div class="stat-card primary animate-slide-up" style="animation-delay: 0.05s">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Committees</p>
                    <p class="stat-number"><?php echo $stats['total'] ?? 0; ?></p>
                </div>
                <div class="stat-icon primary-icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
        <div class="stat-card success animate-slide-up" style="animation-delay: 0.1s">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Active</p>
                    <p class="stat-number success-text"><?php echo $stats['active'] ?? 0; ?></p>
                </div>
                <div class="stat-icon success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="stat-card danger animate-slide-up" style="animation-delay: 0.15s">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Inactive</p>
                    <p class="stat-number danger-text"><?php echo $stats['inactive'] ?? 0; ?></p>
                </div>
                <div class="stat-icon danger-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
        <div class="stat-card purple animate-slide-up" style="animation-delay: 0.2s">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Members</p>
                    <p class="stat-number purple-text"><?php echo $total_members['total'] ?? 0; ?></p>
                </div>
                <div class="stat-icon purple-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section animate-slide-up" style="animation-delay: 0.25s">
        <form method="GET" class="filter-grid">
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-search"></i> Search</label>
                <div class="filter-input-icon">
                    <i class="fas fa-search icon"></i>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Committee name..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-store"></i> Branch</label>
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
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-circle"></i> Status</label>
                <select name="status" class="filter-select">
                    <option value="">All</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label"><i class="fas fa-calendar-day"></i> Meeting Day</label>
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
            <div class="filter-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Committee Cards -->
    <?php if ($committees->num_rows > 0): ?>
    <div class="committee-grid animate-slide-up" style="animation-delay: 0.3s">
        <?php while($row = $committees->fetch_assoc()): ?>
        <div class="committee-card">
            <div class="card-top">
                <div>
                    <h3 class="committee-name"><?php echo htmlspecialchars($row['committee_name']); ?></h3>
                    <span class="committee-code">#<?php echo $row['committee_id']; ?></span>
                </div>
                <span class="badge <?php echo $row['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label"><i class="fas fa-building"></i> Branch</span>
                        <span class="value"><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-user-tie"></i> Officer</span>
                        <span class="value"><?php echo htmlspecialchars($row['officer_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-calendar-alt"></i> Formed</span>
                        <span class="value"><?php echo date('d M Y', strtotime($row['formed_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-users"></i> Members</span>
                        <span class="value"><span class="badge badge-purple badge-sm"><?php echo $row['member_count'] ?? 0; ?></span></span>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--gray-100);">
                    <span class="badge badge-info badge-sm">
                        <i class="fas fa-calendar-day"></i> <?php echo $row['meeting_day']; ?>
                    </span>
                    <span class="badge badge-gray badge-sm">
                        <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($row['meeting_time'])); ?>
                    </span>
                </div>
            </div>
            <div class="card-footer">
                <div class="action-group">
                    <a href="view.php?id=<?php echo $row['committee_id']; ?>" class="action-btn primary" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?php echo $row['committee_id']; ?>" class="action-btn primary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="assign-member.php?committee_id=<?php echo $row['committee_id']; ?>" class="action-btn success" title="Manage Members">
                        <i class="fas fa-users"></i>
                    </a>
                </div>
                <div class="action-group">
                    <button onclick="toggleStatus(<?php echo $row['committee_id']; ?>, <?php echo $row['is_active']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="action-btn <?php echo $row['is_active'] ? 'warning' : 'success'; ?>" 
                            title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                        <i class="fas fa-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                    </button>
                    <button onclick="deleteCommittee(<?php echo $row['committee_id']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="action-btn danger" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state animate-slide-up" style="animation-delay: 0.3s">
        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
        <h3 class="empty-title">No Committees Found</h3>
        <p class="empty-description">Get started by creating your first committee.</p>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Committee
        </a>
    </div>
    <?php endif; ?>

</div>

<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>