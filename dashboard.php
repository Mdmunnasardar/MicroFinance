<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

/* =========================
   CORE STATS
========================= */

$total_members = $conn->query("SELECT COUNT(*) AS t FROM members")->fetch_assoc()['t'] ?? 0;
$active_members = $conn->query("SELECT COUNT(*) AS t FROM members WHERE is_active=1")->fetch_assoc()['t'] ?? 0;
$total_loans = $conn->query("SELECT SUM(principal_amount) AS t FROM loans")->fetch_assoc()['t'] ?? 0;
$total_paid = $conn->query("SELECT SUM(total_paid) AS t FROM loans")->fetch_assoc()['t'] ?? 0;
$total_due = $total_loans - $total_paid;
$total_savings = $conn->query("SELECT SUM(balance) AS t FROM savings")->fetch_assoc()['t'] ?? 0;
$total_collection = $conn->query("SELECT SUM(amount) AS t FROM loan_payments")->fetch_assoc()['t'] ?? 0;

/* =========================
   ADVANCED ANALYTICS
========================= */

$overdue = $conn->query("
SELECT COUNT(*) AS t
FROM loans
WHERE status='active'
AND maturity_date < CURDATE()
")->fetch_assoc()['t'] ?? 0;

$top = $conn->query("
SELECT m.full_name, m.member_id, SUM(l.principal_amount) AS total
FROM loans l
LEFT JOIN members m ON l.member_id=m.member_id
GROUP BY l.member_id
ORDER BY total DESC
LIMIT 1
")->fetch_assoc();

/* =========================
   LOAN HEALTH
========================= */

$health = ($total_loans > 0) ? ($total_paid / $total_loans) * 100 : 0;

/* =========================
   MONTHLY GRAPH DATA
========================= */

$months = [];
$loanData = [];
$payData = [];

for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i month"));
    $months[] = $m;
    
    $loan = $conn->query("
        SELECT SUM(principal_amount) AS t
        FROM loans
        WHERE DATE_FORMAT(created_at,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;
    
    $pay = $conn->query("
        SELECT SUM(amount) AS t
        FROM loan_payments
        WHERE DATE_FORMAT(payment_date,'%Y-%m')='$m'
    ")->fetch_assoc()['t'] ?? 0;
    
    $loanData[] = $loan;
    $payData[] = $pay;
}

/* =========================
   RECENT TRANSACTIONS
========================= */

$recent_transactions = $conn->query("
SELECT 
    'Payment' as type,
    'Loan Payment' as description,
    m.full_name as member,
    lp.amount,
    lp.payment_date as date,
    'Completed' as status
FROM loan_payments lp
LEFT JOIN loans l ON lp.loan_id = l.loan_id
LEFT JOIN members m ON l.member_id = m.member_id
ORDER BY lp.payment_date DESC
LIMIT 5
");

/* =========================
   RECENT MEMBERS
========================= */

$recent_members = $conn->query("
SELECT member_id, full_name, member_code, created_at
FROM members
ORDER BY created_at DESC
LIMIT 5
");

// Include header
include "includes/header.php";
?>

<!-- Include Sidebar -->
<?php include "includes/sidebar.php"; ?>

<!-- Include Topbar -->
<?php include "includes/topbar.php"; ?>

<!-- ========================================
   MAIN CONTENT
======================================== -->
<div class="main-content">
    
    <!-- Top Section -->
    <div class="dashboard-top">
        <div class="welcome-section">
            <h1>Welcome back, <span class="highlight"><?php echo $_SESSION['name'] ?? 'Admin'; ?></span>!</h1>
            <p>Here's what's happening with your microfinance today.</p>
        </div>
        <div class="quick-actions">
            <a href="#" class="btn-quick btn-quick-primary">
                <i class="fa-solid fa-user-plus"></i>
                Add Member
            </a>
            <a href="#" class="btn-quick btn-quick-success">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                Add Loan
            </a>
            <a href="#" class="btn-quick btn-quick-info">
                <i class="fa-solid fa-piggy-bank"></i>
                Add Savings
            </a>
        </div>
    </div>

    <!-- Overdue Alert -->
    <?php if($overdue > 0): ?>
    <div class="overdue-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><strong><?php echo $overdue; ?></strong> overdue loans require immediate attention!</span>
        <a href="due_system/" class="alert-link">View Details →</a>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12%</div>
            </div>
            <div class="stat-value"><?php echo number_format($total_members); ?></div>
            <div class="stat-label">Total Members</div>
            <div class="stat-sub">+18 this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon green"><i class="fa-solid fa-user-check"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8%</div>
            </div>
            <div class="stat-value"><?php echo number_format($active_members); ?></div>
            <div class="stat-label">Active Members</div>
            <div class="stat-sub"><?php echo $total_members > 0 ? round(($active_members/$total_members)*100) : 0; ?>% of total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12.5%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_loans); ?></div>
            <div class="stat-label">Total Loans</div>
            <div class="stat-sub">+12.5% this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon teal"><i class="fa-solid fa-circle-dollar"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 15.3%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_collection); ?></div>
            <div class="stat-label">Total Collection</div>
            <div class="stat-sub">+15.3% this month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_paid); ?></div>
            <div class="stat-label">Total Paid</div>
            <div class="stat-sub"><?php echo $total_loans > 0 ? round(($total_paid/$total_loans)*100) : 0; ?>% of total loans</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-trend down"><i class="fa-solid fa-arrow-down"></i> 3.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_due); ?></div>
            <div class="stat-label">Total Due</div>
            <div class="stat-sub"><?php echo $total_loans > 0 ? round(($total_due/$total_loans)*100) : 0; ?>% remaining</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon gold"><i class="fa-solid fa-piggy-bank"></i></div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8.2%</div>
            </div>
            <div class="stat-value">$<?php echo number_format($total_savings); ?></div>
            <div class="stat-label">Total Savings</div>
            <div class="stat-sub">+8.2% this month</div>
        </div>
        
        <div class="stat-card overdue-card">
            <div class="stat-header">
                <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-trend danger"><i class="fa-solid fa-circle"></i> Alert</div>
            </div>
            <div class="stat-value"><?php echo $overdue; ?></div>
            <div class="stat-label">Overdue Loans</div>
            <div class="stat-sub">Requires attention</div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Chart -->
        <div class="chart-container">
            <div class="card-header-section">
                <h3><i class="fa-solid fa-chart-line"></i> Loans vs Collection</h3>
                <span class="badge-bg">Last 6 Months</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        
        <!-- Right Panel -->
        <div class="right-panel">
            <!-- Loan Health -->
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
            
            <!-- Top Borrower -->
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
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">
        <!-- Recent Transactions -->
        <div class="transaction-table">
            <div class="table-header">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Transactions</h3>
                <a href="#" class="view-all">View All →</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_transactions && $recent_transactions->num_rows > 0): ?>
                            <?php while($row = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge-type payment"><?php echo $row['type']; ?></span></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><strong><?php echo $row['member']; ?></strong></td>
                                <td>$<?php echo number_format($row['amount']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td><span class="badge-status completed"><i class="fa-solid fa-check-circle"></i> <?php echo $row['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No transactions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Members -->
        <div class="recent-members">
            <div class="table-header">
                <h3><i class="fa-solid fa-user-plus"></i> Recent Members</h3>
                <a href="#" class="view-all">View All →</a>
            </div>
            <div class="members-list">
                <?php if($recent_members && $recent_members->num_rows > 0): ?>
                    <?php while($row = $recent_members->fetch_assoc()): ?>
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
    </div>

</div>

<!-- ========================================
   SCRIPTS
======================================== -->
<script>
    // Mobile Menu Toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
    });

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });

    // Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($m) {
                    return date('M y', strtotime($m . '-01'));
                }, $months)); ?>,
                datasets: [
                    {
                        label: 'Loans Disbursed',
                        data: <?php echo json_encode($loanData); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5',
                        fill: true,
                    },
                    {
                        label: 'Collection',
                        data: <?php echo json_encode($payData); ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#22c55e',
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: {
                                family: 'Inter',
                                size: 12,
                                weight: '500'
                            }
                        },
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (context.parsed.y !== null) {
                                    label += ': $' + context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            },
                            font: {
                                family: 'Inter',
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    });
</script>

<?php include "includes/footer.php"; ?>