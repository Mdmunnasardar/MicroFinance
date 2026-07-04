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
SELECT c.*, b.branch_name, u.full_name AS officer_name
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Committee Details - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .detail-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding: 8px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3><i class="fas fa-users-cog text-primary me-2"></i>Committee Details</h3>
            <p class="text-muted mb-0">Complete committee information</p>
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
                        <div class="detail-label">Meeting Day</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-day text-info me-2"></i>
                            <span class="badge bg-info"><?php echo $committee['meeting_day']; ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Meeting Time</div>
                        <div class="detail-value">
                            <i class="fas fa-clock text-info me-2"></i>
                            <span class="badge bg-secondary"><?php echo date('h:i A', strtotime($committee['meeting_time'])); ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Created At</div>
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
                            <a href="edit.php?id=<?php echo $committee_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Committee
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
</div>

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