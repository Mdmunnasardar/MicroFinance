<?php
include "../config/db.php";

$id = $_POST['id'] ?? $_GET['id'] ?? 0;

$row = $conn->query("
    SELECT m.*, c.committee_name, b.branch_name 
    FROM members m
    LEFT JOIN committees c ON m.committee_id = c.committee_id
    LEFT JOIN branches b ON m.branch_id = b.branch_id
    WHERE m.member_id = $id
")->fetch_assoc();

if(!$row) {
    echo '<div class="p-8 text-center text-red-500">Member not found</div>';
    exit;
}

// Get stats
$loan_total = $conn->query("SELECT COALESCE(SUM(principal_amount),0) as t FROM loans WHERE member_id=$id")->fetch_assoc()['t'] ?? 0;
$saving_total = $conn->query("SELECT COALESCE(SUM(balance),0) as t FROM savings WHERE member_id=$id")->fetch_assoc()['t'] ?? 0;
?>
<div class="modal-body p-8">
    <div class="flex justify-between items-start mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                <span class="text-2xl font-bold text-indigo-600">
                    <?php echo strtoupper(substr($row['full_name'], 0, 2)); ?>
                </span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></h3>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($row['member_code']); ?></p>
            </div>
        </div>
        <button onclick="closeQuickView()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
            <i class="fas fa-times text-2xl"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">Total Loans</p>
            <p class="text-xl font-bold text-blue-600">$<?php echo number_format($loan_total, 2); ?></p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">Total Savings</p>
            <p class="text-xl font-bold text-emerald-600">$<?php echo number_format($saving_total, 2); ?></p>
        </div>
    </div>

    <div class="space-y-3 text-sm">
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Phone</span>
            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($row['phone']); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Email</span>
            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Branch</span>
            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b border-gray-100">
            <span class="text-gray-500">Status</span>
            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $row['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'; ?>">
                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
        </div>
        <div class="flex justify-between py-2">
            <span class="text-gray-500">Join Date</span>
            <span class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($row['join_date'] ?? $row['created_at'])); ?></span>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="view.php?id=<?php echo $row['member_id']; ?>" 
           class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all duration-200 text-center text-sm font-medium">
            <i class="fas fa-user-circle mr-2"></i> Full Profile
        </a>
        <a href="edit.php?id=<?php echo $row['member_id']; ?>" 
           class="flex-1 px-4 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 text-center text-sm font-medium">
            <i class="fas fa-pen mr-2"></i> Edit
        </a>
    </div>
</div>

<style>
.modal-body {
    animation: slideIn 0.3s ease-out;
}
@keyframes slideIn {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>