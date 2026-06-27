<?php
/**
 * Health Card Component
 * Usage: renderHealthCard($health)
 */
function renderHealthCard($health) {
?>
<div class="health-card">
    <div class="health-header">
        <i class="fa-solid fa-heart-pulse"></i>
        <span>Loan Health</span>
    </div>
    <div class="health-value"><?php echo round($health, 1); ?>%</div>
    <div class="health-bar">
        <div class="health-bar-fill" style="width: <?php echo min($health, 100); ?>%;"></div>
    </div>
    <div class="health-status">
        <?php if($health >= 70): ?>
            <span class="badge-success">✅ Your loan portfolio is healthy and performing well.</span>
        <?php elseif($health >= 50): ?>
            <span class="badge-warning">⚠️ Moderate health. Some attention needed.</span>
        <?php else: ?>
            <span class="badge-danger">🔴 Critical health. Immediate action required.</span>
        <?php endif; ?>
        <a href="#" class="health-link">View Details →</a>
    </div>
</div>
<?php
}
?>