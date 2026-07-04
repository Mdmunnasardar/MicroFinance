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

// Handle remove member
if (isset($_GET['remove'])) {
    $member_id = (int)$_GET['remove'];
    $unassigned_committee = 1;
    
    if ($member_id > 0) {
        $update_sql = "UPDATE members SET committee_id = ? WHERE member_id = ? AND committee_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $unassigned_committee, $member_id, $committee_id);
        $stmt->execute();
        
        header("Location: assign-member.php?committee_id=" . $committee_id . "&success=removed");
        exit();
    }
}

// Handle single assign
if (isset($_POST['assign_single'])) {
    $member_id = (int)$_POST['member_id'];
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

// Get available members
$unassigned_committee = 1;
$available_sql = "
SELECT * FROM members m
WHERE m.is_active = 1 
AND m.committee_id NOT IN (?, ?)
ORDER BY m.full_name
";
$stmt = $conn->prepare($available_sql);
$stmt->bind_param("ii", $unassigned_committee, $committee_id);
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

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <div class="header-icon primary">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <h1 class="header-title">Manage Members</h1>
                <p class="header-subtitle">
                    <a href="view.php?id=<?php echo $committee_id; ?>" class="text-primary hover:text-primary-dark">
                        <?php echo htmlspecialchars($committee['committee_name']); ?>
                    </a>
                </p>
            </div>
        </div>
        <div class="header-actions">
            <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if ($success == 'assigned'): ?>
    <div class="bg-success-bg border-l-4 border-success text-success-dark p-4 rounded-xl mb-6 flex items-center justify-between animate-slide-up">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-success text-xl"></i>
            <span>Member(s) assigned successfully!</span>
        </div>
        <button type="button" class="text-success-dark hover:text-success" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php elseif ($success == 'removed'): ?>
    <div class="bg-warning-bg border-l-4 border-warning text-warning-dark p-4 rounded-xl mb-6 flex items-center justify-between animate-slide-up">
        <div class="flex items-center gap-3">
            <i class="fas fa-user-minus text-warning text-xl"></i>
            <span>Member removed successfully!</span>
        </div>
        <button type="button" class="text-warning-dark hover:text-warning" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-2 gap-6">
        
        <!-- Current Members -->
        <div class="detail-section">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-users text-primary mr-2"></i>
                    Current Members
                    <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                        <?php echo $current_members->num_rows; ?>
                    </span>
                </h3>
            </div>

            <div class="custom-scroll" style="max-height: 500px; overflow-y: auto;">
                <?php if ($current_members->num_rows > 0): ?>
                    <?php while($member = $current_members->fetch_assoc()): ?>
                    <div class="current-member">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="avatar">
                                <?php 
                                $initial = strtoupper(substr($member['full_name'], 0, 2));
                                if (strpos($member['full_name'], ' ') !== false) {
                                    $names = explode(' ', $member['full_name']);
                                    $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                }
                                echo $initial;
                                ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($member['full_name']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-id-card mr-1"></i> <?php echo $member['member_code']; ?>
                                    <?php if ($member['phone']): ?>
                                    • <i class="fas fa-phone mr-1"></i><?php echo $member['phone']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                           class="action-btn danger"
                           onclick="return confirm('Remove <?php echo htmlspecialchars($member['full_name']); ?> from this committee?')"
                           title="Remove">
                            <i class="fas fa-user-minus"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No members assigned</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Members -->
        <div class="detail-section">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-user-plus text-success mr-2"></i>
                    Available Members
                    <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                        <?php echo $available_members->num_rows; ?>
                    </span>
                </h3>
            </div>

            <?php if ($available_members->num_rows > 0): ?>
                <form method="POST">
                    <div class="select-all-wrapper">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="text-sm font-medium text-gray-700">Select All</span>
                        </label>
                        <span id="selectedCount" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">0 selected</span>
                    </div>

                    <div class="custom-scroll" style="max-height: 350px; overflow-y: auto;">
                        <?php while($member = $available_members->fetch_assoc()): ?>
                        <div class="member-item">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="member_ids[]" value="<?php echo $member['member_id']; ?>" 
                                       class="member-checkbox w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary">
                                <label class="flex items-center gap-3 flex-1 cursor-pointer min-w-0">
                                    <div class="avatar">
                                        <?php 
                                        $initial = strtoupper(substr($member['full_name'], 0, 2));
                                        if (strpos($member['full_name'], ' ') !== false) {
                                            $names = explode(' ', $member['full_name']);
                                            $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                        }
                                        echo $initial;
                                        ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="name"><?php echo htmlspecialchars($member['full_name']); ?></p>
                                        <p class="details">
                                            <i class="fas fa-id-card mr-1"></i> <?php echo $member['member_code']; ?>
                                            <?php if ($member['phone']): ?>
                                            • <i class="fas fa-phone mr-1"></i><?php echo $member['phone']; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="assign_multiple" id="assignBtn"
                                class="btn btn-primary btn-block disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-user-plus"></i> Assign Selected Members
                        </button>
                    </div>
                </form>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-4xl text-success-light mb-3"></i>
                <p class="text-gray-500">All members are already assigned</p>
                <a href="../members/index.php" class="text-primary hover:text-primary-dark text-sm mt-2 inline-block">
                    <i class="fas fa-arrow-right mr-1"></i> View All Members
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>