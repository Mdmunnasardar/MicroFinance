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

// ============================================
// GET COMMITTEE DETAILS
// ============================================
$sql = "
SELECT c.*, 
       b.branch_name, 
       u.full_name AS officer_name,
       u.email AS officer_email,
       u.phone AS officer_phone,
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

// ============================================
// GET MEMBERS
// ============================================
$members_sql = "SELECT * FROM members WHERE committee_id = ? AND is_active = 1 ORDER BY full_name";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$members = $stmt->get_result();

// ============================================
// GET STATS
// ============================================
$stats_sql = "
SELECT 
    (SELECT COALESCE(SUM(balance), 0) FROM savings s 
     JOIN members m ON s.member_id = m.member_id 
     WHERE m.committee_id = ?) as total_savings,
    (SELECT COUNT(*) FROM loans l 
     JOIN members m ON l.member_id = m.member_id 
     WHERE m.committee_id = ?) as total_loans
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("ii", $committee_id, $committee_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

include "../includes/header.php";
?>

<!-- ============================================
     PAGE HEADER
     ============================================ -->
<div class="page-header flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-users-cog text-indigo-500 mr-3"></i>Committee Details
        </h1>
        <p class="page-subtitle">Complete committee information</p>
    </div>
    <div class="flex gap-2">
        <a href="edit.php?id=<?php echo $committee_id; ?>" class="btn-primary gap-2">
            <i class="fas fa-edit"></i>
            <span>Edit</span>
        </a>
        <a href="index.php" class="btn-secondary gap-2">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
     ============================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    
    <!-- ==========================================
         COMMITTEE INFO (Left - 2 columns)
         ========================================== -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($committee['committee_name']); ?>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-tag mr-1"></i>
                        #<?php echo $committee['committee_id']; ?>
                    </p>
                </div>
                <span class="badge-status <?php echo $committee['is_active'] ? 'active' : 'inactive'; ?> text-sm px-4 py-1.5">
                    <?php echo $committee['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <p class="detail-label">Branch</p>
                    <p class="detail-value">
                        <i class="fas fa-building text-indigo-500 mr-2"></i>
                        <?php echo htmlspecialchars($committee['branch_name'] ?? 'N/A'); ?>
                    </p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Formed Date</p>
                    <p class="detail-value">
                        <i class="fas fa-calendar-alt text-amber-500 mr-2"></i>
                        <?php echo date('d F Y', strtotime($committee['formed_date'])); ?>
                    </p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Meeting Schedule</p>
                    <p class="detail-value">
                        <span class="badge-day">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <?php echo $committee['meeting_day']; ?>
                        </span>
                        <span class="badge-time ml-2">
                            <i class="fas fa-clock mr-1"></i>
                            <?php echo date('h:i A', strtotime($committee['meeting_time'])); ?>
                        </span>
                    </p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Total Members</p>
                    <p class="detail-value">
                        <i class="fas fa-users text-violet-500 mr-2"></i>
                        <span class="font-bold text-lg"><?php echo $committee['member_count']; ?></span>
                    </p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Total Savings</p>
                    <p class="detail-value">
                        <i class="fas fa-piggy-bank text-emerald-500 mr-2"></i>
                        <span class="font-bold text-lg">$<?php echo number_format($stats['total_savings'] ?? 0, 2); ?></span>
                    </p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Total Loans</p>
                    <p class="detail-value">
                        <i class="fas fa-hand-holding-usd text-amber-500 mr-2"></i>
                        <span class="font-bold text-lg"><?php echo $stats['total_loans'] ?? 0; ?></span>
                    </p>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                <i class="fas fa-clock mr-1"></i>
                Created: <?php echo date('d F Y h:i A', strtotime($committee['created_at'])); ?>
            </p>
        </div>
    </div>

    <!-- ==========================================
         SIDEBAR (Right - 1 column)
         ========================================== -->
    <div class="lg:col-span-1">
        <!-- Field Officer Card -->
        <div class="officer-card mb-4">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-user-tie text-indigo-500 mr-2"></i>
                Field Officer
            </h4>
            <div class="flex items-center gap-3">
                <div class="officer-avatar">
                    <?php 
                    $officer_name = $committee['officer_name'] ?? '?';
                    $initial = strtoupper(substr($officer_name, 0, 2));
                    if (strpos($officer_name, ' ') !== false) {
                        $names = explode(' ', $officer_name);
                        $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
                    }
                    echo $initial;
                    ?>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">
                        <?php echo htmlspecialchars($committee['officer_name'] ?? 'Not Assigned'); ?>
                    </p>
                    <?php if ($committee['officer_email']): ?>
                    <p class="text-xs text-gray-600">
                        <i class="fas fa-envelope mr-1"></i>
                        <?php echo $committee['officer_email']; ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($committee['officer_phone']): ?>
                    <p class="text-xs text-gray-600">
                        <i class="fas fa-phone mr-1"></i>
                        <?php echo $committee['officer_phone']; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm p-4">
            <h4 class="font-semibold text-gray-800 text-sm mb-3">Quick Actions</h4>
            <div class="space-y-2">
                <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" 
                   class="btn-outline-primary w-full gap-2 text-sm">
                    <i class="fas fa-user-plus"></i>
                    <span>Manage Members</span>
                </a>
                <button onclick="toggleStatus(<?php echo $committee['committee_id']; ?>, <?php echo $committee['is_active']; ?>, '<?php echo addslashes($committee['committee_name']); ?>')"
                        class="btn-outline-<?php echo $committee['is_active'] ? 'amber' : 'emerald'; ?> w-full gap-2 text-sm">
                    <i class="fas fa-<?php echo $committee['is_active'] ? 'pause' : 'play'; ?>"></i>
                    <span><?php echo $committee['is_active'] ? 'Deactivate' : 'Activate'; ?></span>
                </button>
                <button onclick="deleteCommittee(<?php echo $committee_id; ?>, '<?php echo addslashes($committee['committee_name']); ?>')"
                        class="btn-outline-danger w-full gap-2 text-sm">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     MEMBERS LIST
     ============================================ -->
<div class="bg-white rounded-2xl shadow-sm mt-6 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">
            <i class="fas fa-users text-indigo-500 mr-2"></i>
            Committee Members
            <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                <?php echo $members->num_rows; ?>
            </span>
        </h3>
        <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" 
           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            <i class="fas fa-plus mr-1"></i> Add Member
        </a>
    </div>
    <div class="p-4">
        <?php if ($members->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Gender</th>
                        <th class="px-4 py-3">Join Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <tr class="border-t border-gray-100 hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="member-avatar">
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
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($member['full_name']); ?></p>
                                    <?php if ($member['guarantor_name']): ?>
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-user-check mr-1"></i>
                                        Guarantor: <?php echo htmlspecialchars($member['guarantor_name']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                                <?php echo $member['member_code']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php if ($member['phone']): ?>
                            <p><i class="fas fa-phone text-emerald-500 mr-1"></i> <?php echo $member['phone']; ?></p>
                            <?php endif; ?>
                            <?php if ($member['national_id']): ?>
                            <p class="text-xs text-gray-400">
                                <i class="fas fa-id-card mr-1"></i> <?php echo $member['national_id']; ?>
                            </p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($member['gender'] == 'M'): ?>
                            <span class="bg-blue-100 text-blue-700 text-xs px-2.5 py-1 rounded-full">Male</span>
                            <?php elseif ($member['gender'] == 'F'): ?>
                            <span class="bg-pink-100 text-pink-700 text-xs px-2.5 py-1 rounded-full">Female</span>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-full">Other</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php echo date('d M Y', strtotime($member['join_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="../members/view.php?id=<?php echo $member['member_id']; ?>" 
                                   class="text-indigo-500 hover:text-indigo-700 p-1.5 rounded-lg hover:bg-indigo-50 transition-colors"
                                   title="View Member">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                                   class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 transition-colors"
                                   onclick="return confirm('Remove <?php echo htmlspecialchars($member['full_name']); ?>?')"
                                   title="Remove">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No members assigned to this committee</p>
            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" 
               class="text-indigo-600 hover:text-indigo-800 text-sm mt-2 inline-block">
                <i class="fas fa-plus mr-1"></i> Assign First Member
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>