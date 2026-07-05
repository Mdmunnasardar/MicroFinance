<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'field_officer') {
    header("Location: ../login.php");
    exit();
}

$officer_id = $_SESSION['user_id'];

// Get officer name
$sql = "SELECT full_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$officer = $stmt->get_result()->fetch_assoc();

// Get members under this officer
$members_sql = "
SELECT m.*, c.committee_name
FROM members m
JOIN committees c ON m.committee_id = c.committee_id
WHERE c.field_officer_id = ? AND m.is_active = 1
ORDER BY m.full_name
";
$stmt = $conn->prepare($members_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$members = $stmt->get_result();

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <div class="page-header">
        <div class="header-left">
            <div class="header-icon success">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h1 class="header-title">My Members</h1>
                <p class="header-subtitle">
                    <?php echo htmlspecialchars($officer['full_name']); ?> 
                    <span class="text-gray-400">|</span> 
                    <span class="text-primary"><?php echo $members->num_rows; ?> members</span>
                </p>
            </div>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($members->num_rows > 0): ?>
    <div class="detail-section">
        <div class="table-wrapper">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Code</th>
                        <th>Committee</th>
                        <th>Phone</th>
                        <th>Joined</th>
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
                                    <p class="text-xs text-gray-500"><?php echo $member['member_code']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $member['member_code']; ?></td>
                        <td>
                            <span class="badge badge-info badge-sm"><?php echo htmlspecialchars($member['committee_name']); ?></span>
                        </td>
                        <td><?php echo $member['phone'] ?? 'N/A'; ?></td>
                        <td><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-users"></i></div>
        <h3 class="empty-title">No Members Assigned</h3>
        <p class="empty-description">You don't have any members assigned yet.</p>
    </div>
    <?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>