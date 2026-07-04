<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['committee_id']) ? (int)$_GET['committee_id'] : 0;
if ($committee_id <= 0) {
    header("Location: index.php");
    exit();
}

// Get committee details
$committee_sql = "SELECT * FROM committees WHERE committee_id = ?";
$stmt = $conn->prepare($committee_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$committee = $stmt->get_result()->fetch_assoc();

if (!$committee) {
    header("Location: index.php");
    exit();
}

// Handle assigning member
if (isset($_POST['assign_member'])) {
    $member_id = $_POST['member_id'];
    
    $update_sql = "UPDATE members SET committee_id = ? WHERE member_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $committee_id, $member_id);
    $stmt->execute();
    
    header("Location: view.php?id=$committee_id&success=assigned");
    exit();
}

// Get available members (not assigned to any committee)
$available_sql = "
SELECT m.* FROM members m
WHERE m.is_active = 1 
AND (m.committee_id IS NULL OR m.committee_id = 0)
ORDER BY m.full_name
";
$available_members = $conn->query($available_sql);

// Get current members
$current_sql = "
SELECT m.* FROM members m
WHERE m.committee_id = ? AND m.is_active = 1
ORDER BY m.full_name
";
$stmt = $conn->prepare($current_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$current_members = $stmt->get_result();

$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Members - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .member-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
        }
        .member-card:hover {
            background: #f8fafc;
        }
    </style>
</head>

<body>

<div class="container mt-4">

    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-plus text-primary me-2"></i>Assign Members
            </h4>
            <p class="text-muted mb-0 small">
                <a href="view.php?id=<?php echo $committee_id; ?>" class="text-decoration-none">
                    <?php echo htmlspecialchars($committee['committee_name']); ?>
                </a>
            </p>
        </div>
        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <?php if ($success == 'assigned'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>Member assigned successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Current Members -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-users me-2"></i>
                        Current Members (<?php echo $current_members->num_rows; ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($current_members->num_rows > 0): ?>
                        <?php while($member = $current_members->fetch_assoc()): ?>
                        <div class="member-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $member['member_code']; ?></small>
                                </div>
                                <a href="remove-member.php?committee_id=<?php echo $committee_id; ?>&member_id=<?php echo $member['member_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Remove this member from the committee?')">
                                    <i class="fas fa-user-minus"></i> Remove
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No members assigned</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Members -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-user-plus me-2"></i>
                        Available Members (<?php echo $available_members->num_rows; ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($available_members->num_rows > 0): ?>
                        <?php while($member = $available_members->fetch_assoc()): ?>
                        <div class="member-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $member['member_code']; ?></small>
                                </div>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
                                    <button type="submit" name="assign_member" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Assign
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                        <p class="text-muted">All members are assigned to committees</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>