<?php
function renderChartCard($title, $badge, $chartId, $height = '280px') {
?>
<div class="chart-container">
    <div class="card-header-section">
        <h3><i class="fa-solid fa-chart-line"></i> <?php echo $title; ?></h3>
        <span class="badge-bg"><?php echo $badge; ?></span>
    </div>
    <div class="chart-wrapper" style="height: <?php echo $height; ?>;">
        <canvas id="<?php echo $chartId; ?>"></canvas>
    </div>
</div>
<?php
}
?>