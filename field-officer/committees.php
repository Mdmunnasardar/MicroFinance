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

// Get committees under this officer
$committees_sql = "
SELECT c.*, COUNT(m.member_id) as member_count
FROM committees c
LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
WHERE c.field_officer_id = ? AND c.is_active = 1
GROUP BY c.committee_id
";
$stmt = $conn->prepare($committees_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$committees = $stmt->get_result();

include "../includes/header.php";
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <div class="page-header">
        <div class="header-left">
            <div class="header-icon primary">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h1 class="header-title">My Committees</h1>
                <p class="header-subtitle">
                    <?php echo htmlspecialchars($officer['full_name']); ?> 
                    <span class="text-gray-400">|</span> 
                    <span class="text-primary"><?php echo $committees->num_rows; ?> committees</span>
                </p>
            </div>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($committees->num_rows > 0): ?>
    <div class="committee-grid">
        <?php while($committee = $committees->fetch_assoc()): ?>
        <div class="committee-card">
            <div class="card-top">
                <div>
                    <h3 class="committee-name"><?php echo htmlspecialchars($committee['committee_name']); ?></h3>
                    <span class="committee-code">#<?php echo $committee['committee_id']; ?></span>
                </div>
                <span class="badge badge-success">Active</span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label"><i class="fas fa-calendar-day"></i> Meeting</span>
                        <span class="value"><?php echo $committee['meeting_day']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-clock"></i> Time</span>
                        <span class="value"><?php echo date('h:i A', strtotime($committee['meeting_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-users"></i> Members</span>
                        <span class="value"><span class="badge badge-purple badge-sm"><?php echo $committee['member_count']; ?></span></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="../Committees/view.php?id=<?php echo $committee['committee_id']; ?>" 
                   class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-eye"></i> View Details
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-users-cog"></i></div>
        <h3 class="empty-title">No Committees Assigned</h3>
        <p class="empty-description">You don't have any committees assigned yet.</p>
    </div>
    <?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>