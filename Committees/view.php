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

// Fetch committee details
$sql = "
SELECT c.*, b.branch_name, u.full_name AS officer_name,
       (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.is_active = 1) as member_count
FROM committees c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN users u ON c.field_officer_id = u.user_id
WHERE c.committee_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$committee = $stmt->get_result()->fetch_assoc();

if (!$committee) {
    header("Location: index.php");
    exit();
}

// Fetch committee members
$members_sql = "
SELECT cm.*, m.full_name, m.member_code, m.phone, m.email
FROM committee_members cm
JOIN members m ON cm.member_id = m.member_id
WHERE cm.committee_id = ? AND cm.is_active = 1
ORDER BY cm.joined_date DESC
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
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .detail-value {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 1rem;
            padding: 8px 16px;
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-users-cog text-primary me-2"></i>Committee Details</h3>
            <p class="text-muted mb-0">View complete committee information</p>
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
    <div class="info-card">
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-3"><?php echo htmlspecialchars($committee['committee_name']); ?></h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Committee ID</div>
                        <div class="detail-value">#<?php echo $committee['committee_id']; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <?php if ($committee['is_active']): ?>
                            <span class="badge bg-success status-badge">Active</span>
                            <?php else: ?>
                            <span class="badge bg-danger status-badge">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="detail-label">Branch</div>
                        <div class="detail-value">
                            <i class="fas fa-building text-primary me-2"></i>
                            <?php echo htmlspecialchars($committee['branch_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Field Officer</div>
                        <div class="detail-value">
                            <i class="fas fa-user-tie text-success me-2"></i>
                            <?php echo htmlspecialchars($committee['officer_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="detail-label">Meeting Schedule</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-day text-info me-2"></i>
                            <?php echo $committee['meeting_day']; ?> 
                            <i class="fas fa-clock text-info ms-2 me-2"></i>
                            <?php echo date('h:i A', strtotime($committee['meeting_time'])); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Formed Date</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-alt text-warning me-2"></i>
                            <?php echo date('d F Y', strtotime($committee['formed_date'])); ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="detail-label">Total Members</div>
                        <div class="detail-value">
                            <i class="fas fa-users text-primary me-2"></i>
                            <?php echo $committee['member_count']; ?> members
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Created</div>
                        <div class="detail-value">
                            <i class="fas fa-clock text-muted me-2"></i>
                            <?php echo date('d F Y h:i A', strtotime($committee['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="members.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Manage Members
                            </a>
                            <button class="btn btn-outline-success" onclick="generateReport(<?php echo $committee_id; ?>)">
                                <i class="fas fa-file-pdf me-2"></i>Generate Report
                            </button>
                            <button class="btn btn-outline-info" onclick="exportCommittee(<?php echo $committee_id; ?>)">
                                <i class="fas fa-file-export me-2"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Committee Members -->
    <div class="info-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Committee Members</h5>
            <a href="members.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-2"></i>Add Member
            </a>
        </div>

        <?php if ($members->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Code</th>
                        <th>Joined Date</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                        </td>
                        <td><?php echo $member['member_code']; ?></td>
                        <td><?php echo date('d M Y', strtotime($member['joined_date'])); ?></td>
                        <td>
                            <?php if ($member['email']): ?>
                            <a href="mailto:<?php echo $member['email']; ?>" class="text-decoration-none me-2">
                                <i class="fas fa-envelope text-primary"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($member['phone']): ?>
                            <a href="tel:<?php echo $member['phone']; ?>" class="text-decoration-none">
                                <i class="fas fa-phone text-success"></i>
                            </a>
                            <?php endif; ?>
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
            <a href="members.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus me-2"></i>Add First Member
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function generateReport(id) {
    alert('Report generation feature coming soon!');
}

function exportCommittee(id) {
    alert('Export feature coming soon!');
}
</script>

</body>
</html>