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

// ============================================
// GET COMMITTEE DETAILS
// ============================================
$committee_sql = "SELECT * FROM committees WHERE committee_id = ?";
$stmt = $conn->prepare($committee_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$committee = $stmt->get_result()->fetch_assoc();

if (!$committee) {
    header("Location: index.php");
    exit();
}

// ============================================
// HANDLE REMOVE MEMBER
// ============================================
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

// ============================================
// HANDLE SINGLE ASSIGN
// ============================================
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

// ============================================
// HANDLE BULK ASSIGN
// ============================================
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

// ============================================
// GET AVAILABLE MEMBERS
// ============================================
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

// ============================================
// GET CURRENT MEMBERS
// ============================================
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - MicroFinance</title>
    <link rel="stylesheet" href="../assets/css/committees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- ==========================================
         PAGE HEADER
         ========================================== -->
    <div class="page-header">
        <div class="flex items-center gap-4">
            <div class="page-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <h1 class="page-title">Manage Members</h1>
                <p class="page-subtitle">
                    <a href="view.php?id=<?php echo $committee_id; ?>" class="text-indigo-600 hover:text-indigo-800">
                        <?php echo htmlspecialchars($committee['committee_name']); ?>
                    </a>
                </p>
            </div>
        </div>
        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <!-- ==========================================
         SUCCESS MESSAGES
         ========================================== -->
    <?php if ($success == 'assigned'): ?>
    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl mb-6 flex items-center justify-between animate-slide-up">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
            <span>Member(s) assigned successfully!</span>
        </div>
        <button type="button" class="text-emerald-700 hover:text-emerald-900" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php elseif ($success == 'removed'): ?>
    <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-700 p-4 rounded-xl mb-6 flex items-center justify-between animate-slide-up">
        <div class="flex items-center gap-3">
            <i class="fas fa-user-minus text-amber-500 text-xl"></i>
            <span>Member removed successfully!</span>
        </div>
        <button type="button" class="text-amber-700 hover:text-amber-900" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- ==========================================
         TWO COLUMN LAYOUT
         ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- ==========================================
             CURRENT MEMBERS
             ========================================== -->
        <div class="detail-section">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-users text-indigo-500 mr-2"></i>
                    Current Members
                    <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                        <?php echo $current_members->num_rows; ?>
                    </span>
                </h3>
            </div>

            <div class="max-h-[500px] overflow-y-auto custom-scroll space-y-2">
                <?php if ($current_members->num_rows > 0): ?>
                    <?php while($member = $current_members->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-indigo-300 transition-all">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="avatar" style="width: 40px; height: 40px; font-size: 14px; background: var(--primary-bg); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                <?php 
                                $initial = strtoupper(substr($member['full_name'], 0, 2));
                                if (strpos($member['full_name'], ' ') !== false) {
                                    $names = explode(' ', $member['full_name']);
                                    $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                }
                                echo $initial;
                                ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate">
                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-id-card mr-1"></i>
                                    <?php echo $member['member_code']; ?>
                                    <?php if ($member['phone']): ?>
                                    • <i class="fas fa-phone mr-1"></i><?php echo $member['phone']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                           class="text-rose-500 hover:text-rose-700 p-2 rounded-lg hover:bg-rose-50 transition-colors"
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

        <!-- ==========================================
             AVAILABLE MEMBERS
             ========================================== -->
        <div class="detail-section">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-user-plus text-emerald-500 mr-2"></i>
                    Available Members
                    <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                        <?php echo $available_members->num_rows; ?>
                    </span>
                </h3>
            </div>

            <?php if ($available_members->num_rows > 0): ?>
                <form method="POST">
                    <!-- Select All -->
                    <div class="select-all-wrapper">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Select All</span>
                        </label>
                        <span id="selectedCount" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">0 selected</span>
                    </div>

                    <!-- Member List -->
                    <div class="max-h-[350px] overflow-y-auto custom-scroll space-y-2">
                        <?php while($member = $available_members->fetch_assoc()): ?>
                        <div class="member-item">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="member_ids[]" value="<?php echo $member['member_id']; ?>" 
                                       class="member-checkbox w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label class="flex items-center gap-3 flex-1 cursor-pointer min-w-0">
                                    <div class="avatar" style="width: 40px; height: 40px; font-size: 14px; background: var(--primary-bg); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                        <?php 
                                        $initial = strtoupper(substr($member['full_name'], 0, 2));
                                        if (strpos($member['full_name'], ' ') !== false) {
                                            $names = explode(' ', $member['full_name']);
                                            $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                                        }
                                        echo $initial;
                                        ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate">
                                            <?php echo htmlspecialchars($member['full_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-id-card mr-1"></i>
                                            <?php echo $member['member_code']; ?>
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

                    <!-- Assign Button -->
                    <div class="mt-4">
                        <button type="submit" name="assign_multiple" id="assignBtn"
                                class="btn btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-user-plus"></i>
                            Assign Selected Members
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-check-circle text-4xl text-emerald-300 mb-3"></i>
                    <p class="text-gray-500">All members are already assigned</p>
                    <a href="../members/index.php" class="text-indigo-600 hover:text-indigo-800 text-sm mt-2 inline-block">
                        <i class="fas fa-arrow-right mr-1"></i> View All Members
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==========================================
     SCRIPTS
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle member selection
    document.querySelectorAll('.member-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox') return;
            const checkbox = this.querySelector('.member-checkbox');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected');
            updateSelectedCount();
        });
    });

    // Individual checkbox change
    document.querySelectorAll('.member-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const parent = this.closest('.member-item');
            if (parent) {
                parent.classList.toggle('selected', this.checked);
            }
            updateSelectedCount();
        });
    });

    // Select All
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.member-checkbox').forEach((cb, index) => {
            cb.checked = this.checked;
            const parent = cb.closest('.member-item');
            if (parent) {
                parent.classList.toggle('selected', this.checked);
            }
        });
        updateSelectedCount();
    });

    // Update selected count
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        const count = checked.length;
        const countEl = document.getElementById('selectedCount');
        const assignBtn = document.getElementById('assignBtn');
        
        if (countEl) countEl.textContent = count + ' selected';
        if (assignBtn) assignBtn.disabled = count === 0;
    }

    updateSelectedCount();
});
</script>

</body>
</html>

<?php include "../includes/footer.php"; ?>