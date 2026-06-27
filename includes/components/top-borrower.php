<?php
function renderTopBorrower($top) {
?>
<div class="top-borrower">
    <div class="top-header">
        <i class="fa-solid fa-trophy"></i>
        <span>Top Borrower</span>
    </div>
    <?php if($top): ?>
    <div class="top-member">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top['full_name']); ?>&background=4f46e5&color=fff&size=52" alt="Avatar">
        <div>
            <h4><?php echo $top['full_name']; ?></h4>
            <span class="member-id">ID: <?php echo $top['member_id'] ?? 'N/A'; ?></span>
            <div class="top-amount">
                <span class="label">Total Borrowed</span>
                <span class="value">$<?php echo number_format($top['total'] ?? 0); ?></span>
            </div>
        </div>
    </div>
    <?php else: ?>
    <p class="text-muted">No borrowers found</p>
    <?php endif; ?>
</div>
<?php
}
?>