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

// Get committee details - REMOVED email and phone from users table
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

// Get members
$members_sql = "SELECT * FROM members WHERE committee_id = ? AND is_active = 1 ORDER BY full_name";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$members = $stmt->get_result();

// Get stats
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

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <div class="header-icon primary">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h1 class="header-title">Committee Details</h1>
                <p class="header-subtitle">Complete committee information</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="edit.php?id=<?php echo $committee_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-3 gap-6">
        
        <!-- Committee Info -->
        <div class="grid-cols-2" style="grid-column: span 2;">
            <div class="detail-section">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($committee['committee_name']); ?></h2>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-tag mr-1"></i> #<?php echo $committee['committee_id']; ?>
                        </p>
                    </div>
                    <span class="badge <?php echo $committee['is_active'] ? 'badge-success' : 'badge-danger'; ?> text-sm px-4 py-2">
                        <?php echo $committee['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div>
                        <p class="detail-label"><i class="fas fa-building text-primary mr-1"></i> Branch</p>
                        <p class="detail-value"><?php echo htmlspecialchars($committee['branch_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="detail-label"><i class="fas fa-calendar-alt text-warning mr-1"></i> Formed Date</p>
                        <p class="detail-value"><?php echo date('d F Y', strtotime($committee['formed_date'])); ?></p>
                    </div>
                    <div>
                        <p class="detail-label"><i class="fas fa-calendar-day text-info mr-1"></i> Meeting Schedule</p>
                        <p class="detail-value">
                            <span class="badge badge-info"><?php echo $committee['meeting_day']; ?></span>
                            <span class="badge badge-gray ml-2"><?php echo date('h:i A', strtotime($committee['meeting_time'])); ?></span>
                        </p>
                    </div>
                    <div>
                        <p class="detail-label"><i class="fas fa-users text-purple mr-1"></i> Total Members</p>
                        <p class="detail-value text-2xl font-bold text-purple"><?php echo $committee['member_count']; ?></p>
                    </div>
                    <div>
                        <p class="detail-label"><i class="fas fa-piggy-bank text-success mr-1"></i> Total Savings</p>
                        <p class="detail-value text-xl font-bold text-success">
                            $<?php echo number_format($stats['total_savings'] ?? 0, 2); ?>
                        </p>
                    </div>
                    <div>
                        <p class="detail-label"><i class="fas fa-hand-holding-usd text-warning mr-1"></i> Total Loans</p>
                        <p class="detail-value text-xl font-bold text-warning"><?php echo $stats['total_loans'] ?? 0; ?></p>
                    </div>
                </div>

                <p class="text-xs text-gray-400 mt-6 pt-4 border-t border-gray-100">
                    <i class="fas fa-clock mr-1"></i>
                    Created: <?php echo date('d F Y h:i A', strtotime($committee['created_at'])); ?>
                </p>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="grid-column: span 1;">
            <!-- Field Officer -->
            <div class="officer-card mb-4">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-user-tie text-primary mr-2"></i> Field Officer
                </h4>
                <div class="flex items-center gap-4">
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
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($committee['officer_name'] ?? 'Not Assigned'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="detail-section">
                <h4 class="font-semibold text-gray-800 text-sm mb-3">Quick Actions</h4>
                <div class="space-y-2">
                    <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-user-plus"></i> Manage Members
                    </a>
                    <button onclick="toggleStatus(<?php echo $committee['committee_id']; ?>, <?php echo $committee['is_active']; ?>, '<?php echo addslashes($committee['committee_name']); ?>')"
                            class="btn <?php echo $committee['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-block">
                        <i class="fas fa-<?php echo $committee['is_active'] ? 'pause' : 'play'; ?>"></i>
                        <?php echo $committee['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                    <button onclick="deleteCommittee(<?php echo $committee_id; ?>, '<?php echo addslashes($committee['committee_name']); ?>')"
                            class="btn btn-danger btn-block">
                        <i class="fas fa-trash"></i> Delete Committee
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Members List -->
    <div class="detail-section mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-users text-primary mr-2"></i>
                Committee Members
                <span class="ml-2 bg-gray-200 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                    <?php echo $members->num_rows; ?>
                </span>
            </h3>
            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Member
            </a>
        </div>

        <?php if ($members->num_rows > 0): ?>
        <div class="table-wrapper">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Code</th>
                        <th>Contact</th>
                        <th>Gender</th>
                        <th>Join Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
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
                        <td><span class="badge badge-gray badge-sm"><?php echo $member['member_code']; ?></span></td>
                        <td>
                            <?php if ($member['phone']): ?>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-phone text-success mr-1"></i> <?php echo $member['phone']; ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($member['national_id']): ?>
                            <p class="text-xs text-gray-400">
                                <i class="fas fa-id-card mr-1"></i> <?php echo $member['national_id']; ?>
                            </p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($member['gender'] == 'M'): ?>
                            <span class="badge badge-info badge-sm">Male</span>
                            <?php elseif ($member['gender'] == 'F'): ?>
                            <span class="badge badge-purple badge-sm">Female</span>
                            <?php else: ?>
                            <span class="badge badge-gray badge-sm">Other</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-600"><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                        <td style="text-align: right;">
                            <div class="flex items-center justify-end gap-1">
                                <a href="../members/view.php?id=<?php echo $member['member_id']; ?>" class="action-btn primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>&remove=<?php echo $member['member_id']; ?>" 
                                   class="action-btn danger"
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
        <div class="empty-state" style="padding: 40px 20px;">
            <div class="empty-icon" style="font-size: 48px;"><i class="fas fa-users"></i></div>
            <h3 class="empty-title" style="font-size: 18px;">No Members Assigned</h3>
            <p class="empty-description" style="font-size: 13px;">This committee doesn't have any members yet.</p>
            <a href="assign-member.php?committee_id=<?php echo $committee_id; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Assign First Member
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>