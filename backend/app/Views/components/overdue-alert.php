<?php
/**
 * Overdue Alert Component
 * Usage: renderOverdueAlert($count)
 */
function renderOverdueAlert($count) {
    if ($count > 0): ?>
    <div class="overdue-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><strong><?php echo $count; ?></strong> overdue loans require immediate attention!</span>
        <a href="due_system/" class="alert-link">View Details →</a>
    </div>
    <?php endif;
}
?>