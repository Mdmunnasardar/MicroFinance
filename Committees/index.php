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

<!-- ============================================
     PAGE HEADER
     ============================================ -->
<div class="page-header flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-users-cog text-indigo-500 mr-3"></i>Committees
        </h1>
        <p class="page-subtitle">Manage all committees and their members</p>
    </div>
    <a href="add.php" class="btn-primary gap-2">
        <i class="fas fa-plus-circle"></i>
        <span>Add Committee</span>
    </a>
</div>

<!-- ============================================
     STATISTICS CARDS
     ============================================ -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    <div class="stat-card stat-card-primary">
        <div class="flex justify-between items-center">
            <div>
                <p class="stat-label">Total Committees</p>
                <p class="stat-number"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
            <div class="stat-icon bg-indigo-50 text-indigo-600">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="flex justify-between items-center">
            <div>
                <p class="stat-label">Active</p>
                <p class="stat-number text-emerald-600"><?php echo $stats['active'] ?? 0; ?></p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-danger">
        <div class="flex justify-between items-center">
            <div>
                <p class="stat-label">Inactive</p>
                <p class="stat-number text-rose-600"><?php echo $stats['inactive'] ?? 0; ?></p>
            </div>
            <div class="stat-icon bg-rose-50 text-rose-600">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
    </div>
    <div class="stat-card stat-card-purple">
        <div class="flex justify-between items-center">
            <div>
                <p class="stat-label">Total Members</p>
                <p class="stat-number text-violet-600"><?php echo $total_members['total'] ?? 0; ?></p>
            </div>
            <div class="stat-icon bg-violet-50 text-violet-600">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     FILTER SECTION
     ============================================ -->
<div class="filter-section mt-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                <i class="fas fa-search mr-1"></i> Search
            </label>
            <input type="text" name="search" class="filter-input" 
                   placeholder="Committee name..." 
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                <i class="fas fa-building mr-1"></i> Branch
            </label>
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
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                <i class="fas fa-circle mr-1"></i> Status
            </label>
            <select name="status" class="filter-select">
                <option value="">All</option>
                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                <i class="fas fa-calendar-day mr-1"></i> Meeting Day
            </label>
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
        <div class="flex items-end">
            <button type="submit" class="btn-primary w-full gap-2">
                <i class="fas fa-filter"></i>
                <span>Apply Filter</span>
            </button>
        </div>
    </form>
</div>

<!-- ============================================
     COMMITTEE LIST
     ============================================ -->
<div class="mt-6">
    <?php if ($committees->num_rows > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php while($row = $committees->fetch_assoc()): ?>
        <div class="committee-card fade-in">
            <!-- Card Header -->
            <div class="card-header flex justify-between items-start">
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-800 truncate">
                        <?php echo htmlspecialchars($row['committee_name']); ?>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <i class="fas fa-tag mr-1"></i>
                        #<?php echo $row['committee_id']; ?>
                    </p>
                </div>
                <span class="badge-status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?> flex-shrink-0 ml-2">
                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Branch</p>
                        <p class="text-sm text-gray-800 truncate">
                            <?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Officer</p>
                        <p class="text-sm text-gray-800 truncate">
                            <?php echo htmlspecialchars($row['officer_name'] ?? 'N/A'); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3">
                    <span class="badge-day">
                        <i class="fas fa-calendar-day mr-1"></i>
                        <?php echo $row['meeting_day']; ?>
                    </span>
                    <span class="badge-time">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo date('h:i A', strtotime($row['meeting_time'])); ?>
                    </span>
                    <span class="badge-member">
                        <i class="fas fa-users mr-1"></i>
                        <?php echo $row['member_count'] ?? 0; ?>
                    </span>
                </div>

                <p class="text-xs text-gray-400 mt-2">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Formed: <?php echo date('d M Y', strtotime($row['formed_date'])); ?>
                </p>
            </div>

            <!-- Card Footer -->
            <div class="card-footer flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-1">
                    <a href="view.php?id=<?php echo $row['committee_id']; ?>" 
                       class="text-indigo-600 hover:text-indigo-800 p-1.5 rounded-lg hover:bg-indigo-50 transition-colors"
                       title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?php echo $row['committee_id']; ?>" 
                       class="text-emerald-600 hover:text-emerald-800 p-1.5 rounded-lg hover:bg-emerald-50 transition-colors"
                       title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="assign-member.php?committee_id=<?php echo $row['committee_id']; ?>" 
                       class="text-violet-600 hover:text-violet-800 p-1.5 rounded-lg hover:bg-violet-50 transition-colors"
                       title="Manage Members">
                        <i class="fas fa-users"></i>
                    </a>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="toggleStatus(<?php echo $row['committee_id']; ?>, <?php echo $row['is_active']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="text-<?php echo $row['is_active'] ? 'amber' : 'emerald'; ?>-600 hover:text-<?php echo $row['is_active'] ? 'amber' : 'emerald'; ?>-800 p-1.5 rounded-lg hover:bg-<?php echo $row['is_active'] ? 'amber' : 'emerald'; ?>-50 transition-colors"
                            title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                        <i class="fas fa-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                    </button>
                    <button onclick="deleteCommittee(<?php echo $row['committee_id']; ?>, '<?php echo addslashes($row['committee_name']); ?>')"
                            class="text-rose-600 hover:text-rose-800 p-1.5 rounded-lg hover:bg-rose-50 transition-colors"
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
    <div class="empty-state bg-white rounded-2xl shadow-sm">
        <div class="empty-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3 class="empty-title">No Committees Found</h3>
        <p class="empty-description">Get started by creating your first committee</p>
        <a href="add.php" class="btn-primary inline-flex gap-2">
            <i class="fas fa-plus-circle"></i>
            <span>Add Committee</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>