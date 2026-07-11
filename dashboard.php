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

// Include ALL components
include "includes/components/stat-card.php";
include "includes/components/overdue-alert.php";
include "includes/components/chart-card.php";
include "includes/components/health-card.php";
include "includes/components/top-borrower.php";

// Prepare chart data for JavaScript
$chartLabels = array_map(function($m) {
    return date('M y', strtotime($m . '-01'));
}, $months);
$chartLoanData = json_encode($loanData);
$chartPayData = json_encode($payData);
?>

<!-- Include Sidebar -->
<?php include "includes/sidebar.php"; ?>

<!-- Include Topbar -->
<?php include "includes/topbar.php"; ?>

<!-- ========================================
   MAIN CONTENT
======================================== -->
<div class="main-content">
    
    <!-- Dashboard Top -->
    <div class="dashboard-top">
        <div class="welcome-section">
            <h1>Welcome back, <span class="highlight"><?php echo $_SESSION['name']; ?></span>!</h1>
            <p>Here's what's happening with your microfinance today.</p>
        </div>
        <div class="quick-actions">
            <a href="#" class="btn-quick btn-quick-primary">
                <i class="fa-solid fa-user-plus"></i> Add Member
            </a>
            <a href="#" class="btn-quick btn-quick-success">
                <i class="fa-solid fa-hand-holding-dollar"></i> Add Loan
            </a>
            <a href="#" class="btn-quick btn-quick-info">
                <i class="fa-solid fa-piggy-bank"></i> Add Savings
            </a>
        </div>
    </div>

    <!-- Overdue Alert -->
    <?php renderOverdueAlert($overdue); ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <?php
        renderStatCard('fa-users', 'blue', number_format($total_members), 'Total Members', 'fa-arrow-up', '12%', 'up', '+18 this month');
        renderStatCard('fa-user-check', 'green', number_format($active_members), 'Active Members', 'fa-arrow-up', '8%', 'up', ($total_members > 0 ? round(($active_members/$total_members)*100) : 0) . '% of total');
        renderStatCard('fa-money-bill-wave', 'purple', '$' . number_format($total_loans), 'Total Loans', 'fa-arrow-up', '12.5%', 'up', '+12.5% this month');
        renderStatCard('fa-circle-dollar', 'teal', '$' . number_format($total_collection), 'Total Collection', 'fa-arrow-up', '15.3%', 'up', '+15.3% this month');
        renderStatCard('fa-check-circle', 'green', '$' . number_format($total_paid), 'Total Paid', 'fa-arrow-up', '8.2%', 'up', ($total_loans > 0 ? round(($total_paid/$total_loans)*100) : 0) . '% of total loans');
        renderStatCard('fa-clock', 'red', '$' . number_format($total_due), 'Total Due', 'fa-arrow-down', '3.2%', 'down', ($total_loans > 0 ? round(($total_due/$total_loans)*100) : 0) . '% remaining');
        renderStatCard('fa-piggy-bank', 'gold', '$' . number_format($total_savings), 'Total Savings', 'fa-arrow-up', '8.2%', 'up', '+8.2% this month');
        renderStatCard('fa-triangle-exclamation', 'red', $overdue, 'Overdue Loans', 'fa-circle', 'Alert', 'danger', 'Requires attention');
        ?>
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
            <?php renderHealthCard($health); ?>
            <?php renderTopBorrower($top); ?>
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
   CHART SCRIPT - DIRECTLY IN THE PAGE
======================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from PHP
    const labels = <?php echo json_encode($chartLabels); ?>;
    const loanData = <?php echo $chartLoanData; ?>;
    const payData = <?php echo $chartPayData; ?>;
    
    const ctx = document.getElementById('trendChart');
    
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Loans Disbursed',
                        data: loanData,
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
                        data: payData,
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
    } else {
        console.log('Chart.js not loaded or canvas not found');
    }
});
</script>



<?php include "includes/footer.php"; ?>