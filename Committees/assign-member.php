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

// Handle single member assignment
if (isset($_POST['assign_single'])) {
    $member_id = $_POST['member_id'];
    
    if ($member_id) {
        $update_sql = "UPDATE members SET committee_id = ? WHERE member_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $committee_id, $member_id);
        $stmt->execute();
        
        header("Location: assign-member.php?committee_id=$committee_id&success=assigned");
        exit();
    }
}

// Handle bulk assign
if (isset($_POST['assign_multiple']) && isset($_POST['member_ids'])) {
    $member_ids = $_POST['member_ids'];
    
    if (!empty($member_ids)) {
        foreach ($member_ids as $member_id) {
            $update_sql = "UPDATE members SET committee_id = ? WHERE member_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $committee_id, $member_id);
            $stmt->execute();
        }
        header("Location: assign-member.php?committee_id=$committee_id&success=assigned");
        exit();
    }
}

// Handle removing member
if (isset($_GET['remove'])) {
    $member_id = (int)$_GET['remove'];
    $update_sql = "UPDATE members SET committee_id = NULL WHERE member_id = ? AND committee_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $member_id, $committee_id);
    $stmt->execute();
    header("Location: assign-member.php?committee_id=$committee_id&success=removed");
    exit();
}

// Get available members (not assigned to any committee)
$available_sql = "
SELECT * FROM members m
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
SELECT * FROM members m
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
            transition: all 0.2s;
        }
        .current-member:hover {
            background: #f8fafc;
            border-color: #4f46e5;
        }
        .btn-remove {
            padding: 4px 10px;
        }
        .select-all-wrapper {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 10px;
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

    <!-- Success/Error Messages -->
    <?php if ($success == 'assigned'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>Member(s) assigned successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($success == 'removed'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-user-minus me-2"></i>Member removed successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Current Members -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
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
                                            <i class="fas fa-id-card me-1"></i>
                                            <?php echo $member['member_code']; ?>
                                            <?php if ($member['phone']): ?>
                                            • <i class="fas fa-phone me-1"></i><?php echo $member['phone']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <a href="?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger btn-remove"
                                   onclick="return confirm('Remove this member from the committee?')">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No members assigned to this committee</p>
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
                            <div class="select-all-wrapper">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                    <label class="form-check-label fw-bold" for="selectAll">
                                        Select All Members
                                    </label>
                                    <span class="badge bg-primary ms-2" id="selectedCount">0 selected</span>
                                </div>
                            </div>

                            <div style="max-height: 350px; overflow-y: auto;">
                                <?php while($member = $available_members->fetch_assoc()): ?>
                                <div class="member-item" data-member-id="<?php echo $member['member_id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" name="member_ids[]" value="<?php echo $member['member_id']; ?>" 
                                               class="form-check-input me-2 member-checkbox" 
                                               id="member_<?php echo $member['member_id']; ?>">
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
                                                    <i class="fas fa-id-card me-1"></i>
                                                    <?php echo $member['member_code']; ?>
                                                    <?php if ($member['phone']): ?>
                                                    • <i class="fas fa-phone me-1"></i><?php echo $member['phone']; ?>
                                                    <?php endif; ?>
                                                    <?php if ($member['national_id']): ?>
                                                    • <i class="fas fa-id-card me-1"></i><?php echo $member['national_id']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-3">
                                <button type="submit" name="assign_multiple" class="btn btn-primary w-100" id="assignBtn" disabled>
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
// Toggle member selection
document.querySelectorAll('.member-item').forEach(item => {
    item.addEventListener('click', function(e) {
        // Don't toggle if clicking on checkbox directly
        if (e.target.type === 'checkbox') return;
        
        const checkbox = this.querySelector('.member-checkbox');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected');
        updateSelectedCount();
    });
});

// Individual checkbox click
document.querySelectorAll('.member-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const parent = this.closest('.member-item');
        if (this.checked) {
            parent.classList.add('selected');
        } else {
            parent.classList.remove('selected');
        }
        updateSelectedCount();
    });
});

// Select All functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.member-checkbox');
    const memberItems = document.querySelectorAll('.member-item');
    
    checkboxes.forEach((checkbox, index) => {
        checkbox.checked = this.checked;
        if (this.checked) {
            memberItems[index].classList.add('selected');
        } else {
            memberItems[index].classList.remove('selected');
        }
    });
    updateSelectedCount();
});

// Update selected count and enable/disable assign button
function updateSelectedCount() {
    const checked = document.querySelectorAll('.member-checkbox:checked');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = count + ' selected';
    document.getElementById('assignBtn').disabled = count === 0;
}

// Initial update
updateSelectedCount();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>