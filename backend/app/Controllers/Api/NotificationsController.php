<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

$notifications = [];

// ============================================
// 1. OVERDUE LOANS (For Admin & Branch Manager)
// ============================================
if ($user_role == 'admin' || $user_role == 'branch_manager') {
    try {
        $sql = "SELECT COUNT(*) as count FROM loans WHERE status = 'active' AND next_due_date < CURDATE()";
        $result = $conn->query($sql);
        $overdue = $result->fetch_assoc()['count'] ?? 0;
        
        if ($overdue > 0) {
            $notifications[] = [
                'icon' => 'warning',
                'text' => "$overdue loan(s) are overdue. Please take action.",
                'time' => 'Now',
                'unread' => true,
                'link' => 'loans/index.php?status=overdue'
            ];
        }
    } catch (Exception $e) {}
}

// ============================================
// 2. PENDING LOAN APPROVALS
// ============================================
if ($user_role == 'admin' || $user_role == 'branch_manager') {
    try {
        $sql = "SELECT COUNT(*) as count FROM loans WHERE status = 'pending'";
        $result = $conn->query($sql);
        $pending = $result->fetch_assoc()['count'] ?? 0;
        
        if ($pending > 0) {
            $notifications[] = [
                'icon' => 'info',
                'text' => "$pending loan(s) waiting for approval.",
                'time' => 'Now',
                'unread' => true,
                'link' => 'loans/index.php?status=pending'
            ];
        }
    } catch (Exception $e) {}
}

// ============================================
// 3. TODAY'S COLLECTIONS (For Field Officer)
// ============================================
if ($user_role == 'field_officer') {
    try {
        $sql = "SELECT COUNT(*) as count FROM loan_payments 
                WHERE collected_by = ? AND DATE(payment_date) = CURDATE()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $today_collections = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        
        if ($today_collections > 0) {
            $notifications[] = [
                'icon' => 'success',
                'text' => "You collected $today_collections payment(s) today. Great job!",
                'time' => 'Today',
                'unread' => false,
                'link' => 'field-officer/collections.php'
            ];
        }
    } catch (Exception $e) {}
}

// ============================================
// 4. PENDING COLLECTIONS (For Field Officer)
// ============================================
if ($user_role == 'field_officer') {
    try {
        $sql = "SELECT COUNT(DISTINCT m.member_id) as count 
                FROM loans l
                JOIN members m ON l.member_id = m.member_id
                JOIN committees c ON m.committee_id = c.committee_id
                WHERE c.field_officer_id = ? 
                AND l.status = 'active' 
                AND l.next_due_date <= CURDATE()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $pending_collections = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        
        if ($pending_collections > 0) {
            $notifications[] = [
                'icon' => 'warning',
                'text' => "$pending_collections member(s) have pending collections today.",
                'time' => 'Today',
                'unread' => true,
                'link' => 'field-officer/collections.php'
            ];
        }
    } catch (Exception $e) {}
}

// ============================================
// 5. NEW MEMBERS (Last 7 days)
// ============================================
try {
    $sql = "SELECT COUNT(*) as count FROM members 
            WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = $conn->query($sql);
    $new_members = $result->fetch_assoc()['count'] ?? 0;
    
    if ($new_members > 0) {
        $notifications[] = [
            'icon' => 'primary',
            'text' => "$new_members new member(s) joined in the last 7 days.",
            'time' => 'This week',
            'unread' => false,
            'link' => 'members/index.php'
        ];
    }
} catch (Exception $e) {}

// ============================================
// 6. LOW SAVINGS ALERT (If savings table exists)
// ============================================
try {
    $sql = "SELECT COUNT(*) as count FROM savings WHERE balance < 100";
    $result = $conn->query($sql);
    $low_savings = $result->fetch_assoc()['count'] ?? 0;
    
    if ($low_savings > 0) {
        $notifications[] = [
            'icon' => 'info',
            'text' => "$low_savings member(s) have low savings balance (below $100).",
            'time' => 'Now',
            'unread' => true,
            'link' => 'savings/index.php'
        ];
    }
} catch (Exception $e) {}

// ============================================
// 7. DEFAULT NOTIFICATION (If no notifications)
// ============================================
if (empty($notifications)) {
    $notifications[] = [
        'icon' => 'info',
        'text' => 'All systems are running smoothly. No pending actions.',
        'time' => 'Now',
        'unread' => false,
        'link' => '#'
    ];
}

// Limit to 10 notifications
$notifications = array_slice($notifications, 0, 10);

header('Content-Type: application/json');
echo json_encode([
    'notifications' => $notifications,
    'unread_count' => count(array_filter($notifications, function($n) {
        return $n['unread'] ?? false;
    }))
]);
?>