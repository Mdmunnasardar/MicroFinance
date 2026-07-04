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
    $field_officer_id = $_POST['field_officer_id'] ?? null;
    
    $update_sql = "UPDATE members SET committee_id = ?, field_officer_id = ? WHERE member_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("iii", $committee_id, $field_officer_id, $member_id);
    $stmt->execute();
    
    header("Location: view.php?id=$committee_id&success=assigned");
    exit();
}

// Handle bulk assign
if (isset($_POST['assign_multiple'])) {
    $member_ids = $_POST['member_ids'] ?? [];
    $field_officer_id = $_POST['field_officer_id'] ?? null;
    
    if (!empty($member_ids)) {
        foreach ($member_ids as $member_id) {
            $update_sql = "UPDATE members SET committee_id = ?, field_officer_id = ? WHERE member_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("iii", $committee_id, $field_officer_id, $member_id);
            $stmt->execute();
        }
        header("Location: view.php?id=$committee_id&success=assigned");
        exit();
    }
}

// Get field officers
$officers = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'field_officer' ORDER BY full_name");

// Get available members (not assigned to any committee)
$available_sql = "
SELECT m.* FROM members m
WHERE m.is_active = 1 
AND (m.committee_id IS NULL OR m.committee_id = 0 OR m.committee_id != ?)
ORDER BY m.full_name
";
$stmt = $conn->prepare($available_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$available_members = $stmt->get_result();

// Get current members
$current_sql = "
SELECT m.*, u.full_name as officer_name
FROM members m
LEFT JOIN users u ON m.field_officer_id = u.user_id
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
        .member-item {
            background: white;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
            cursor: pointer;
        }
        .member-item:hover {
            background: #f8fafc;
            border-color: #4f46e5;
        }
        .member-item.selected {
            background: #eef2ff;
            border-color: #4f46e5;
            border-left: 4px solid #4f46e5;
        }
        .member-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-weight: 600;
            font-size: 14px;
        }
        .current-member {
            background: white;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 8px;
            border: 1px solid #e5e7eb;
        }
        .current-member:hover {
            background: #f8fafc;
        }
        .badge-officer {
            background: #dbeafe;
            color: #1e40af;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
    </style>
</head>

<body>

<div class="container mt-4">

    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-plus text-primary me-2"></i>Manage Committee Members
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
        <i class="fas fa-check-circle me-2"></i>Member(s) assigned successfully!
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
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if ($current_members->num_rows > 0): ?>
                        <?php while($member = $current_members->fetch_assoc()): ?>
                        <div class="current-member">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="member-avatar me-3">
                                        <?php 
                                        $initial = strtoupper(substr($member['full_name'], 0, 2));
                                        if (strpos($member['full_name'], ' ') !== false) {
                                            $names = explode(' ', $member['full_name']);
                                            $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                        }
                                        echo $initial;
                                        ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $member['member_code']; ?>
                                            <?php if ($member['officer_name']): ?>
                                            <span class="badge-officer ms-2">
                                                <i class="fas fa-user-tie me-1"></i>
                                                <?php echo $member['officer_name']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <a href="remove-member.php?committee_id=<?php echo $committee_id; ?>&member_id=<?php echo $member['member_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Remove this member from the committee?')">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No members assigned</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assign Members -->
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
                        <form method="POST" id="assignForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Select Field Officer (Optional)</label>
                                <select name="field_officer_id" class="form-select">
                                    <option value="">Assign to Field Officer</option>
                                    <?php while($officer = $officers->fetch_assoc()): ?>
                                    <option value="<?php echo $officer['user_id']; ?>">
                                        <?php echo htmlspecialchars($officer['full_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div style="max-height: 350px; overflow-y: auto;">
                                <?php while($member = $available_members->fetch_assoc()): ?>
                                <div class="member-item" onclick="toggleMember(this)">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" name="member_ids[]" value="<?php echo $member['member_id']; ?>" 
                                               class="form-check-input me-2" id="member_<?php echo $member['member_id']; ?>">
                                        <label class="d-flex align-items-center w-100 cursor-pointer" for="member_<?php echo $member['member_id']; ?>">
                                            <div class="member-avatar me-3">
                                                <?php 
                                                $initial = strtoupper(substr($member['full_name'], 0, 2));
                                                if (strpos($member['full_name'], ' ') !== false) {
                                                    $names = explode(' ', $member['full_name']);
                                                    $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                                }
                                                echo $initial;
                                                ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $member['member_code']; ?>
                                                    <?php if ($member['phone']): ?>
                                                    • <?php echo $member['phone']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-3">
                                <button type="submit" name="assign_multiple" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>Assign Selected Members
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                            <p class="text-muted">All members are already assigned to committees</p>
                            <a href="../members/index.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-users me-2"></i>View All Members
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMember(element) {
    const checkbox = element.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    element.classList.toggle('selected');
}

// Auto-select all when clicking on member item
document.querySelectorAll('.member-item').forEach(item => {
    item.addEventListener('click', function(e) {
        // Don't toggle if clicking directly on checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected');
        }
    });
});
</script>

</body>
</html>