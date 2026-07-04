<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($committee_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch committee details with member count
$sql = "
SELECT c.*, 
       b.branch_name, 
       u.full_name AS officer_name,
       COUNT(m.member_id) as member_count
FROM committees c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN users u ON c.field_officer_id = u.user_id
LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
WHERE c.committee_id = ?
GROUP BY c.committee_id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$committee = $stmt->get_result()->fetch_assoc();

if (!$committee) {
    header("Location: index.php");
    exit();
}

// Fetch committee members (from members table directly)
$members_sql = "
SELECT m.*
FROM members m
WHERE m.committee_id = ? AND m.is_active = 1
ORDER BY m.full_name
";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$members = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Committee Details - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 1.05rem;
            margin-bottom: 12px;
            padding: 5px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 30px;
        }
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h4 class="fw-bold">
                <i class="fas fa-users-cog text-primary me-2"></i>Committee Details
            </h4>
            <p class="text-muted mb-0 small">Complete committee information</p>
        </div>
        <div>
            <a href="edit.php?id=<?php echo $committee_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Committee Info -->
    <div class="detail-card">
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-3">
                    <?php echo htmlspecialchars($committee['committee_name']); ?>
                    <?php if ($committee['is_active']): ?>
                    <span class="badge bg-success ms-2">Active</span>
                    <?php else: ?>
                    <span class="badge bg-danger ms-2">Inactive</span>
                    <?php endif; ?>
                </h4>
                
                <div class="info-grid">
                    <div>
                        <div class="detail-label">Committee ID</div>
                        <div class="detail-value">#<?php echo $committee['committee_id']; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Formed Date</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-alt text-warning me-2"></i>
                            <?php echo date('d F Y', strtotime($committee['formed_date'])); ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Branch</div>
                        <div class="detail-value">
                            <i class="fas fa-building text-primary me-2"></i>
                            <?php echo htmlspecialchars($committee['branch_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Field Officer</div>
                        <div class="detail-value">
                            <i class="fas fa-user-tie text-success me-2"></i>
                            <?php echo htmlspecialchars($committee['officer_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Meeting Schedule</div>
                        <div class="detail-value">
                            <span class="badge bg-info"><?php echo $committee['meeting_day']; ?></span>
                            <span class="badge bg-secondary ms-1"><?php echo date('h:i A', strtotime($committee['meeting_time'])); ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Total Members</div>
                        <div class="detail-value">
                            <i class="fas fa-users text-primary me-2"></i>
                            <span class="badge bg-primary rounded-pill"><?php echo $committee['member_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Assign Members
                            </a>
                            <button onclick="toggleStatus(<?php echo $committee['committee_id']; ?>, <?php echo $committee['is_active']; ?>)" 
                                    class="btn btn-outline-<?php echo $committee['is_active'] ? 'warning' : 'success'; ?>">
                                <i class="fas fa-<?php echo $committee['is_active'] ? 'pause' : 'play'; ?> me-2"></i>
                                <?php echo $committee['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <button onclick="deleteCommittee(<?php echo $committee_id; ?>)" 
                                    class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Delete Committee
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Committee Members -->
    <div class="detail-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-users text-primary me-2"></i>Committee Members
                <span class="badge bg-secondary ms-2"><?php echo $members->num_rows; ?></span>
            </h5>
            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-2"></i>Assign Member
            </a>
        </div>

        <?php if ($members->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Code</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="member-avatar me-2">
                                    <?php echo strtoupper(substr($member['full_name'], 0, 2)); ?>
                                </div>
                                <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo $member['member_code']; ?></td>
                        <td><?php echo $member['phone']; ?></td>
                        <td><?php echo $member['gender']; ?></td>
                        <td>
                            <a href="remove-member.php?committee_id=<?php echo $committee_id; ?>&member_id=<?php echo $member['member_id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Remove this member from the committee?')">
                                <i class="fas fa-user-minus"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">No members assigned to this committee</p>
            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus me-2"></i>Assign First Member
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this committee?`)) {
        window.location.href = `toggle-status.php?id=${id}&status=${currentStatus ? 0 : 1}`;
    }
}

function deleteCommittee(id) {
    if (confirm('Are you sure you want to delete this committee?')) {
        window.location.href = `delete.php?id=${id}`;
    }
}
</script>

</body>
</html>