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

// Main query with member count (using members table directly)
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
$result = $stmt->get_result();

// Get statistics
$stats_sql = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
FROM committees
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get branches for filter
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Committees - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #4f46e5;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .stat-card.green { border-left-color: #22c55e; }
        .stat-card.red { border-left-color: #ef4444; }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
        }
        .table td { vertical-align: middle; }
        .btn-action {
            padding: 5px 10px;
            margin: 0 3px;
            border-radius: 6px;
        }
        .badge-day {
            padding: 6px 14px;
            font-weight: 500;
        }
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .member-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="container-fluid px-4 py-4">

    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-users-cog text-primary me-2"></i>Committees
            </h4>
            <p class="text-muted mb-0 small">Manage all committees and their members</p>
        </div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add Committee
        </a>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small fw-bold">Total Committees</p>
                        <h3 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small fw-bold">Active</p>
                        <h3 class="mb-0 text-success"><?php echo $stats['active'] ?? 0; ?></h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card red">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small fw-bold">Inactive</p>
                        <h3 class="mb-0 text-danger"><?php echo $stats['inactive'] ?? 0; ?></h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small fw-bold">Total Members</p>
                        <h3 class="mb-0 text-purple">
                            <?php
                            $total_members = $conn->query("SELECT COUNT(*) as total FROM members WHERE is_active = 1")->fetch_assoc();
                            echo $total_members['total'] ?? 0;
                            ?>
                        </h3>
                    </div>
                    <div class="stat-icon bg-purple bg-opacity-10 text-purple">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-secondary">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" 
                           placeholder="Committee name..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small text-secondary">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    <?php while($b = $branches->fetch_assoc()): ?>
                    <option value="<?php echo $b['branch_id']; ?>" 
                        <?php echo $branch_filter == $b['branch_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($b['branch_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-secondary">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-secondary">Meeting Day</label>
                <select name="meeting_day" class="form-select">
                    <option value="">All Days</option>
                    <?php 
                    $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    foreach($days as $day): 
                    ?>
                    <option value="<?php echo $day; ?>" 
                        <?php echo $day_filter == $day ? 'selected' : ''; ?>>
                        <?php echo $day; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Committee Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Committee Name</th>
                            <th>Branch</th>
                            <th>Field Officer</th>
                            <th>Meeting</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark"><?php echo $row['committee_id']; ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['committee_name']); ?></strong>
                                <br>
                                <small class="text-muted">Formed: <?php echo date('d M Y', strtotime($row['formed_date'])); ?></small>
                            </td>
                            <td>
                                <i class="fas fa-building text-primary me-1"></i>
                                <?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <i class="fas fa-user-tie text-success me-1"></i>
                                <?php echo htmlspecialchars($row['officer_name'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <span class="badge bg-info badge-day"><?php echo $row['meeting_day']; ?></span>
                                <span class="badge bg-secondary"><?php echo date('h:i A', strtotime($row['meeting_time'])); ?></span>
                            </td>
                            <td>
                                <span class="member-badge">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $row['member_count'] ?? 0; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $row['committee_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary btn-action" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $row['committee_id']; ?>" 
                                   class="btn btn-sm btn-outline-success btn-action" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="toggleStatus(<?php echo $row['committee_id']; ?>, <?php echo $row['is_active']; ?>)" 
                                        class="btn btn-sm btn-outline-<?php echo $row['is_active'] ? 'warning' : 'success'; ?> btn-action" 
                                        title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                                </button>
                                <button onclick="deleteCommittee(<?php echo $row['committee_id']; ?>)" 
                                        class="btn btn-sm btn-outline-danger btn-action" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">No committees found</p>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus me-2"></i>Add Your First Committee
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this committee?`)) {
        window.location.href = `toggle-status.php?id=${id}&status=${currentStatus ? 0 : 1}`;
    }
}

function deleteCommittee(id) {
    if (confirm('Are you sure you want to delete this committee? This action cannot be undone.')) {
        window.location.href = `delete.php?id=${id}`;
    }
}
</script>

</body>
</html>