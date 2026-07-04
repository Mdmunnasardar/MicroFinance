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

// Handle adding member
if (isset($_POST['add_member'])) {
    $member_id = $_POST['member_id'];
    $joined_date = date('Y-m-d');
    
    $insert_sql = "INSERT INTO committee_members (committee_id, member_id, joined_date, is_active) 
                   VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iis", $committee_id, $member_id, $joined_date);
    $stmt->execute();
    header("Location: members.php?committee_id=$committee_id&success=added");
    exit();
}

// Handle removing member
if (isset($_GET['remove'])) {
    $member_id = (int)$_GET['remove'];
    $delete_sql = "UPDATE committee_members SET is_active = 0, left_date = CURDATE() 
                   WHERE committee_id = ? AND member_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $committee_id, $member_id);
    $stmt->execute();
    header("Location: members.php?committee_id=$committee_id&success=removed");
    exit();
}

// Get current members
$members_sql = "
SELECT cm.*, m.full_name, m.member_code, m.phone, m.email, m.address
FROM committee_members cm
JOIN members m ON cm.member_id = m.member_id
WHERE cm.committee_id = ? AND cm.is_active = 1
ORDER BY cm.joined_date DESC
";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$current_members = $stmt->get_result();

// Get available members (not in committee)
$available_sql = "
SELECT m.* FROM members m
WHERE m.is_active = 1 
AND m.member_id NOT IN (
    SELECT member_id FROM committee_members 
    WHERE committee_id = ? AND is_active = 1
)
ORDER BY m.full_name
";
$stmt = $conn->prepare($available_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$available_members = $stmt->get_result();

$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Committee Members - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .member-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-users text-primary me-2"></i>Committee Members</h3>
            <p class="text-muted mb-0">
                <a href="view.php?id=<?php echo $committee_id; ?>" class="text-decoration-none">
                    <?php echo htmlspecialchars($committee['committee_name']); ?>
                </a>
            </p>
        </div>
        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <?php if ($success == 'added'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>Member added successfully!
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
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-users me-2"></i>
                        Current Members (<?php echo $current_members->num_rows; ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($current_members->num_rows > 0): ?>
                    <div class="row">
                        <?php while($member = $current_members->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="member-card">
                                <div class="d-flex align-items-center">
                                    <div class="member-avatar me-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo $member['member_code']; ?></small>
                                        <br>
                                        <small>
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Joined: <?php echo date('d M Y', strtotime($member['joined_date'])); ?>
                                        </small>
                                    </div>
                                    <a href="?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Remove this member from committee?')">
                                        <i class="fas fa-user-minus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No members in this committee</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Members -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-user-plus me-2"></i>
                        Add Members
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($available_members->num_rows > 0): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Member</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">Choose a member...</option>
                                <?php while($m = $available_members->fetch_assoc()): ?>
                                <option value="<?php echo $m['member_id']; ?>">
                                    <?php echo htmlspecialchars($m['full_name']); ?> 
                                    (<?php echo $m['member_code']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_member" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add to Committee
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                        <p class="text-muted">All members are already in this committee</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>