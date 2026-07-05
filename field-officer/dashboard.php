<?php
session_start();
include "../config/db.php";

// Check if logged in and is field officer
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'field_officer') {
    header("Location: ../dashboard.php");
    exit();
}

$officer_id = $_SESSION['user_id'];
$officer_name = $_SESSION['name'] ?? 'Officer';

// ============================================
// GET OFFICER DETAILS
// ============================================
$sql = "
SELECT u.*, b.branch_name,
       COUNT(DISTINCT c.committee_id) as total_committees,
       COUNT(DISTINCT m.member_id) as total_members
FROM users u
LEFT JOIN branches b ON u.branch_id = b.branch_id
LEFT JOIN committees c ON u.user_id = c.field_officer_id
LEFT JOIN members m ON c.committee_id = m.committee_id AND m.is_active = 1
WHERE u.user_id = ? AND u.role = 'field_officer'
GROUP BY u.user_id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$officer = $stmt->get_result()->fetch_assoc();

if (!$officer) {
    // If no data found, still show dashboard with basic info
    $officer = [
        'full_name' => $officer_name,
        'branch_name' => 'N/A',
        'phone' => 'N/A',
        'total_committees' => 0,
        'total_members' => 0,
        'is_active' => 1,
        'role' => 'field_officer'
    ];
}

// ============================================
// GET TODAY'S COLLECTIONS
// ============================================
$today_sql = "
SELECT COALESCE(SUM(amount), 0) as total_collected
FROM loan_payments
WHERE DATE(payment_date) = CURDATE() AND collected_by = ?
";
$stmt = $conn->prepare($today_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$today_total = $stmt->get_result()->fetch_assoc();

// ============================================
// GET PENDING COLLECTIONS
// ============================================
$pending_sql = "
SELECT COUNT(DISTINCT m.member_id) as pending_count
FROM loans l
JOIN members m ON l.member_id = m.member_id
JOIN committees c ON m.committee_id = c.committee_id
WHERE c.field_officer_id = ? 
  AND l.status = 'active' 
  AND l.next_due_date <= CURDATE()
";
$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc();

// ============================================
// GET TOTAL COLLECTIONS
// ============================================
$total_collection_sql = "
SELECT COALESCE(SUM(amount), 0) as total_collected
FROM loan_payments
WHERE collected_by = ?
";
$stmt = $conn->prepare($total_collection_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$total_collection = $stmt->get_result()->fetch_assoc();

// ============================================
// GET RECENT MEMBERS
// ============================================
$recent_members_sql = "
SELECT m.*, c.committee_name
FROM members m
JOIN committees c ON m.committee_id = c.committee_id
WHERE c.field_officer_id = ? AND m.is_active = 1
ORDER BY m.created_at DESC
LIMIT 5
";
$stmt = $conn->prepare($recent_members_sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$recent_members = $stmt->get_result();

include "../includes/header.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Field Officer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/committees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .welcome-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 16px;
            padding: 30px 32px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .welcome-section .welcome-text {
            position: relative;
            z-index: 1;
        }
        .welcome-section .welcome-text h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        .welcome-section .welcome-text h1 span {
            color: #fcd34d;
        }
        .welcome-section .welcome-text p {
            font-size: 15px;
            opacity: 0.9;
            margin: 6px 0 0;
        }
        .welcome-section .welcome-badge {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .officer-info-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(226, 232, 240, 0.4);
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }
        .officer-info-card .officer-avatar-large {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eef2ff, #dbeafe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            color: #4f46e5;
            flex-shrink: 0;
        }
        .officer-info-card .officer-details h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .officer-info-card .officer-details p {
            font-size: 14px;
            color: #64748b;
            margin: 2px 0 0;
        }
        .officer-info-card .officer-details .badge-role {
            display: inline-block;
            background: #dbeafe;
            color: #2563eb;
            padding: 2px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(226, 232, 240, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .stat-card.primary::before { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
        .stat-card.success::before { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card.purple::before { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stat-card.warning::before { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        .stat-card .stat-number.primary-text { color: #4f46e5; }
        .stat-card .stat-number.success-text { color: #10b981; }
        .stat-card .stat-number.purple-text { color: #8b5cf6; }
        .stat-card .stat-number.warning-text { color: #f59e0b; }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .stat-card .stat-icon.primary-icon {
            background: #eef2ff;
            color: #4f46e5;
        }
        .stat-card .stat-icon.success-icon {
            background: #d1fae5;
            color: #10b981;
        }
        .stat-card .stat-icon.purple-icon {
            background: #ede9fe;
            color: #8b5cf6;
        }
        .stat-card .stat-icon.warning-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .quick-action-btn {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.4);
            border-radius: 14px;
            padding: 18px 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #1e293b;
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #4f46e5;
        }
        .quick-action-btn .qa-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .quick-action-btn .qa-icon.primary { background: #eef2ff; color: #4f46e5; }
        .quick-action-btn .qa-icon.success { background: #d1fae5; color: #10b981; }
        .quick-action-btn .qa-icon.warning { background: #fef3c7; color: #f59e0b; }
        .quick-action-btn .qa-text h4 {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }
        .quick-action-btn .qa-text p {
            font-size: 12px;
            color: #94a3b8;
            margin: 2px 0 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: #4f46e5;
        }

        .member-item-compact {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .member-item-compact:hover {
            background: #f1f5f9;
        }
        .member-item-compact .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .member-item-compact .member-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eef2ff, #dbeafe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #4f46e5;
            flex-shrink: 0;
        }
        .member-item-compact .member-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }
        .member-item-compact .member-code {
            font-size: 12px;
            color: #94a3b8;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .panel {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(226, 232, 240, 0.4);
        }
        .panel .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .panel .panel-header h4 {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        .panel .panel-header a {
            font-size: 13px;
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .collection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .collection-item:last-child {
            border-bottom: none;
        }
        .collection-item .collection-info .collection-member {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }
        .collection-item .collection-info .collection-date {
            font-size: 12px;
            color: #94a3b8;
        }
        .collection-item .collection-amount {
            font-weight: 700;
            color: #10b981;
            font-size: 16px;
        }

        @media (max-width: 992px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
            .welcome-section .welcome-text h1 {
                font-size: 20px;
            }
            .officer-info-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- WELCOME SECTION -->
    <div class="welcome-section animate-slide-up">
        <div class="flex items-center justify-between flex-wrap">
            <div class="welcome-text">
                <h1>👋 Welcome back, <span><?php echo htmlspecialchars($officer['full_name']); ?></span>!</h1>
                <p>Here's your field officer dashboard overview for today.</p>
            </div>
            <div class="welcome-badge">
                <i class="fas fa-calendar-day mr-2"></i>
                <?php echo date('l, d F Y'); ?>
            </div>
        </div>
    </div>

    <!-- OFFICER INFO CARD -->
    <div class="officer-info-card animate-slide-up" style="animation-delay: 0.05s">
        <div class="officer-avatar-large">
            <?php 
            $initial = strtoupper(substr($officer['full_name'], 0, 2));
            if (strpos($officer['full_name'], ' ') !== false) {
                $names = explode(' ', $officer['full_name']);
                $initial = strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
            }
            echo $initial;
            ?>
        </div>
        <div class="officer-details">
            <h3><?php echo htmlspecialchars($officer['full_name']); ?></h3>
            <p>
                <span class="badge-role"><?php echo ucfirst(str_replace('_', ' ', $officer['role'])); ?></span>
                <?php if ($officer['branch_name'] && $officer['branch_name'] != 'N/A'): ?>
                • <i class="fas fa-building"></i> <?php echo htmlspecialchars($officer['branch_name']); ?>
                <?php endif; ?>
                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($officer['phone'] ?? 'No phone'); ?>
            </p>
        </div>
        <div style="margin-left: auto;" class="flex gap-2">
            <a href="../profile.php" class="btn btn-primary btn-sm">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="../logout.php" class="btn btn-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="stat-grid animate-slide-up" style="animation-delay: 0.1s">
        <div class="stat-card primary">
            <div class="stat-top">
                <div>
                    <p class="stat-label">My Committees</p>
                    <p class="stat-number primary-text"><?php echo $officer['total_committees']; ?></p>
                </div>
                <div class="stat-icon primary-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-top">
                <div>
                    <p class="stat-label">My Members</p>
                    <p class="stat-number success-text"><?php echo $officer['total_members']; ?></p>
                </div>
                <div class="stat-icon success-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total Collection</p>
                    <p class="stat-number purple-text">$<?php echo number_format($total_collection['total_collected'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-icon purple-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Today's Collection</p>
                    <p class="stat-number warning-text">$<?php echo number_format($today_total['total_collected'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-icon warning-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions-grid animate-slide-up" style="animation-delay: 0.15s">
        <a href="members.php?officer_id=<?php echo $officer_id; ?>" class="quick-action-btn">
            <div class="qa-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="qa-text">
                <h4>My Members</h4>
                <p><?php echo $officer['total_members']; ?> members assigned to you</p>
            </div>
            <i class="fas fa-chevron-right" style="margin-left: auto; color: #94a3b8;"></i>
        </a>
        <a href="committees.php?officer_id=<?php echo $officer_id; ?>" class="quick-action-btn">
            <div class="qa-icon success">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="qa-text">
                <h4>My Committees</h4>
                <p><?php echo $officer['total_committees']; ?> committees under you</p>
            </div>
            <i class="fas fa-chevron-right" style="margin-left: auto; color: #94a3b8;"></i>
        </a>
        <a href="collections.php?officer_id=<?php echo $officer_id; ?>" class="quick-action-btn">
            <div class="qa-icon warning">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="qa-text">
                <h4>Collect Payments</h4>
                <p><?php echo $pending['pending_count'] ?? 0; ?> pending collections</p>
            </div>
            <i class="fas fa-chevron-right" style="margin-left: auto; color: #94a3b8;"></i>
        </a>
    </div>

    <!-- BOTTOM GRID -->
    <div class="bottom-grid animate-slide-up" style="animation-delay: 0.2s">

        <!-- Recent Members -->
        <div class="panel">
            <div class="panel-header">
                <h4><i class="fas fa-user-plus text-primary"></i> Recent Members</h4>
                <a href="members.php?officer_id=<?php echo $officer_id; ?>">View All →</a>
            </div>
            <div class="space-y-2">
                <?php if ($recent_members->num_rows > 0): ?>
                    <?php while($member = $recent_members->fetch_assoc()): ?>
                    <div class="member-item-compact">
                        <div class="member-info">
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
                                <div class="member-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                <div class="member-code"><?php echo $member['member_code']; ?> • <?php echo htmlspecialchars($member['committee_name']); ?></div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo date('d M Y', strtotime($member['created_at'])); ?></span>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 py-4">No members yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="panel">
            <div class="panel-header">
                <h4><i class="fas fa-clock-rotate-left text-primary"></i> Recent Collections</h4>
                <a href="collections.php?officer_id=<?php echo $officer_id; ?>">View All →</a>
            </div>
            <div>
                <?php if ($recent_collections->num_rows > 0): ?>
                    <?php while($collection = $recent_collections->fetch_assoc()): ?>
                    <div class="collection-item">
                        <div class="collection-info">
                            <div class="collection-member"><?php echo htmlspecialchars($collection['member_name']); ?></div>
                            <div class="collection-date">
                                <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($collection['payment_date'])); ?>
                                • <?php echo $collection['member_code']; ?>
                            </div>
                        </div>
                        <div class="collection-amount">$<?php echo number_format($collection['amount'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 py-4">No collections yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
console.log('👤 Field Officer Dashboard loaded for: <?php echo htmlspecialchars($officer['full_name']); ?>');
</script>

<?php include "../includes/footer.php"; ?>