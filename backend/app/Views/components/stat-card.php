<?php
/**
 * Stat Card Component
 * Usage: renderStatCard($icon, $iconClass, $value, $label, $trendIcon, $trendValue, $trendClass, $subText)
 */
function renderStatCard($icon, $iconClass, $value, $label, $trendIcon, $trendValue, $trendClass, $subText) {
?>
<div class="stat-card">
    <div class="stat-header">
        <div class="stat-icon <?php echo $iconClass; ?>">
            <i class="fa-solid <?php echo $icon; ?>"></i>
        </div>
        <div class="stat-trend <?php echo $trendClass; ?>">
            <i class="fa-solid <?php echo $trendIcon; ?>"></i>
            <?php echo $trendValue; ?>
        </div>
    </div>
    <div class="stat-value"><?php echo $value; ?></div>
    <div class="stat-label"><?php echo $label; ?></div>
    <div class="stat-sub"><?php echo $subText; ?></div>
</div>
<?php
}
?>