<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Members -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Members</p>
                <h3 class="stat-number"><?php echo number_format($stats_data['total']); ?></h3>
            </div>
            <div class="stat-icon bg-indigo-100">
                <i class="fas fa-users text-indigo-600 text-xl"></i>
            </div>
        </div>
        <div class="stat-trend">
            <i class="fas fa-arrow-up text-green-500"></i> 12% from last month
        </div>
    </div>

    <!-- Active Members -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Active Members</p>
                <h3 class="stat-number text-green-600"><?php echo number_format($stats_data['active']); ?></h3>
            </div>
            <div class="stat-icon bg-green-100">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="stat-trend">
            <i class="fas fa-check-circle text-green-500"></i> <?php echo $stats_data['active_percentage']; ?>% active
        </div>
    </div>

    <!-- Inactive Members -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Inactive Members</p>
                <h3 class="stat-number text-red-600"><?php echo number_format($stats_data['inactive']); ?></h3>
            </div>
            <div class="stat-icon bg-red-100">
                <i class="fas fa-user-slash text-red-600 text-xl"></i>
            </div>
        </div>
        <div class="stat-trend">
            <i class="fas fa-exclamation-circle text-yellow-500"></i> Needs attention
        </div>
    </div>

    <!-- Total Loans -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Loans</p>
                <h3 class="stat-number text-blue-600">$<?php echo number_format($stats_data['loans'], 0); ?></h3>
            </div>
            <div class="stat-icon bg-blue-100">
                <i class="fas fa-hand-holding-usd text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="stat-trend">
            <i class="fas fa-arrow-up text-green-500"></i> 8% from last month
        </div>
    </div>
</div>