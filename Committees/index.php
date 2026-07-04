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

// Main query
$sql = "
SELECT c.*, b.branch_name, u.full_name AS officer_name,
       (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.is_active = 1) as member_count
FROM committees c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN users u ON c.field_officer_id = u.user_id
$where_clause
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
    <title>Committee List - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .btn-action {
            padding: 4px 8px;
            margin: 0 2px;
        }
        .search-box {
            max-width: 300px;
        }
        @media (max-width: 768px) {
            .search-box {
                max-width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body class="bg-light">

<div class="container-fluid px-4 mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3><i class="fas fa-users-cog text-primary me-2"></i>Committee Management</h3>
            <p class="text-muted mb-0">Manage all committees, branches, and field officers</p>
        </div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add Committee
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Committees</p>
                        <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Active Committees</p>
                        <h4 class="mb-0 text-success"><?php echo $stats['active']; ?></h4>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Inactive Committees</p>
                        <h4 class="mb-0 text-danger"><?php echo $stats['inactive']; ?></h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Committee name..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Branch</label>
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
                    <label class="form-label fw-bold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Meeting Day</label>
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
    </div>

    <!-- Committee Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Committee Name</th>
                            <th>Branch</th>
                            <th>Field Officer</th>
                            <th>Meeting</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['committee_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['committee_name']); ?></strong>
                                <br>
                                <small class="text-muted">Formed: <?php echo date('d M Y', strtotime($row['formed_date'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['officer_name'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $row['meeting_day']; ?></span>
                                <span class="badge bg-secondary"><?php echo date('h:i A', strtotime($row['meeting_time'])); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill">
                                    <i class="fas fa-users me-1"></i><?php echo $row['member_count'] ?? 0; ?>
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
                                <a href="members.php?committee_id=<?php echo $row['committee_id']; ?>" 
                                   class="btn btn-sm btn-outline-info btn-action" title="Manage Members">
                                    <i class="fas fa-users"></i>
                                </a>
                                <button onclick="toggleStatus(<?php echo $row['committee_id']; ?>, <?php echo $row['is_active']; ?>)" 
                                        class="btn btn-sm btn-outline-<?php echo $row['is_active'] ? 'warning' : 'success'; ?> btn-action" 
                                        title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
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
</script>

</body>
</html>