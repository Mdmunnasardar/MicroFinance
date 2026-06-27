<?php
function renderRecentMembers($members) {
?>
<div class="recent-members">
    <div class="table-header">
        <h3><i class="fa-solid fa-user-plus"></i> Recent Members</h3>
        <a href="#" class="view-all">View All →</a>
    </div>
    <div class="members-list">
        <?php if($members && $members->num_rows > 0): ?>
            <?php while($row = $members->fetch_assoc()): ?>
            <div class="member-item">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=random&color=fff&size=36" alt="Avatar">
                <div>
                    <div class="member-name"><?php echo $row['full_name']; ?></div>
                    <div class="member-code"><?php echo $row['member_code'] ?? 'N/A'; ?></div>
                </div>
                <div class="member-join">
                    <small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No members found</p>
        <?php endif; ?>
    </div>
</div>
<?php
}
?>