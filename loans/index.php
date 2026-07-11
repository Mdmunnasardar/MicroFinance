<?php
session_start();
include "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Loan Management';

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
if ($status_filter) {
    $where[] = "l.status = '$status_filter'";
}
if ($search) {
    $where[] = "(l.loan_code LIKE '%$search%' OR m.full_name LIKE '%$search%' OR m.member_code LIKE '%$search%')";
}
$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_sql = "
SELECT COUNT(*) as total 
FROM loans l 
LEFT JOIN members m ON l.member_id = m.member_id 
$where_clause
";
$total_result = $conn->query($count_sql);
$total_loans = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = $total_loans > 0 ? ceil($total_loans / $per_page) : 1;

// Get loans
$sql = "
SELECT 
l.*,
m.full_name,
m.member_code,
m.member_id
FROM loans l
LEFT JOIN members m ON l.member_id = m.member_id
$where_clause
ORDER BY l.loan_id DESC
LIMIT $offset, $per_page
";

$result = $conn->query($sql);

// Get statistics
$stats_sql = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
    SUM(principal_amount) as total_amount,
    SUM(total_paid) as total_paid
FROM loans
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

$total_amount = $stats['total_amount'] ?? 0;
$total_paid = $stats['total_paid'] ?? 0;
$total_due = $total_amount - $total_paid;
$overdue_count = $stats['overdue'] ?? 0;

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">

<div class="main-content">
    <div class="loan-dashboard">
        
        <!-- Page Header -->
        <div class="dashboard-top">
            <div class="welcome-section">
                <h1>Loan Management</h1>
                <p>Manage all loans, track payments, and monitor portfolio health</p>
            </div>
            <div class="quick-actions">
                <a href="add.php" class="btn-quick btn-quick-primary">
                    <i class="fa-solid fa-plus"></i> New Loan
                </a>
                <a href="#" class="btn-quick btn-quick-info" onclick="alert('Export functionality coming soon!')">
                    <i class="fa-solid fa-download"></i> Export
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="loan-stats-grid">
            <div class="loan-stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="stat-value"><?php echo formatCurrency($total_amount); ?></div>
                <div class="stat-label">Total Portfolio</div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 12.5%</div>
            </div>
            
            <div class="loan-stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-value"><?php echo formatCurrency($total_paid); ?></div>
                <div class="stat-label">Total Collected</div>
                <div class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> 8.2%</div>
            </div>
            
            <div class="loan-stat-card">
                <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-value"><?php echo formatCurrency($total_due); ?></div>
                <div class="stat-label">Total Outstanding</div>
                <div class="stat-trend down"><i class="fa-solid fa-arrow-down"></i> 3.1%</div>
            </div>
            
            <div class="loan-stat-card">
                <div class="stat-icon purple"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-value"><?php echo $overdue_count; ?></div>
                <div class="stat-label">Overdue Loans</div>
                <div class="stat-trend down"><i class="fa-solid fa-arrow-down"></i> Needs Attention</div>
            </div>
        </div>

        <!-- Loan List -->
        <div class="loan-list-container">
            <!-- Header -->
            <div class="loan-list-header">
                <div class="header-left">
                    <h4><i class="fa-solid fa-list"></i> All Loans</h4>
                    <span class="total-count"><?php echo $total_loans; ?> loans</span>
                </div>
                <div class="header-right">
                    <a href="add.php" class="btn-quick btn-quick-primary" style="padding: 8px 18px; font-size: 13px;">
                        <i class="fa-solid fa-plus"></i> Add Loan
                    </a>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="loan-toolbar">
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by loan code, member name..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <button class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>" data-filter="all">All</button>
                    <button class="filter-btn <?php echo $status_filter == 'active' ? 'active' : ''; ?>" data-filter="active">Active</button>
                    <button class="filter-btn <?php echo $status_filter == 'overdue' ? 'active' : ''; ?>" data-filter="overdue">Overdue</button>
                    <button class="filter-btn <?php echo $status_filter == 'closed' ? 'active' : ''; ?>" data-filter="closed">Closed</button>
                    <button class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" data-filter="pending">Pending</button>
                </div>
            </div>

            <!-- Table -->
            <div class="loan-table-wrap">
                <table class="loan-table">
                    <thead>
                        <tr>
                            <th>Loan Code</th>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Interest</th>
                            <th>Repayment</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $paid_percent = $row['total_payable'] > 0 ? ($row['total_paid'] / $row['total_payable']) * 100 : 0;
                                $progress_color = $paid_percent >= 70 ? '#22c55e' : ($paid_percent >= 40 ? '#f59e0b' : '#ef4444');
                                $initials = getInitials($row['full_name']);
                                $colors = ['#4f46e5', '#7c3aed', '#2563eb', '#0891b2', '#059669', '#d97706', '#dc2626'];
                                $color = $colors[($row['member_id'] ?? 1) % count($colors)];
                            ?>
                            <tr>
                                <td>
                                    <strong style="color: #4f46e5;"><?php echo htmlspecialchars($row['loan_code']); ?></strong>
                                </td>
                                <td>
                                    <div class="member-cell">
                                        <div class="member-avatar" style="background: <?php echo $color; ?>;">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div class="member-info">
                                            <span class="name"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                            <span class="code"><?php echo htmlspecialchars($row['member_code']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-cell">
                                        <span class="principal"><?php echo formatCurrency($row['principal_amount']); ?></span>
                                        <span class="interest">Payable: <?php echo formatCurrency($row['total_payable']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $row['interest_rate']; ?>%
                                    <br>
                                    <small style="color: #94a3b8; font-size: 11px;"><?php echo ucfirst(str_replace('_', ' ', $row['interest_type'])); ?></small>
                                </td>
                                <td>
                                    <?php echo formatCurrency($row['installment_amount']); ?>
                                    <br>
                                    <small style="color: #94a3b8; font-size: 11px;"><?php echo ucfirst($row['installment_type']); ?></small>
                                </td>
                                <td class="progress-cell">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?php echo min($paid_percent, 100); ?>%; background: <?php echo $progress_color; ?>;"></div>
                                    </div>
                                    <span class="progress-text"><?php echo round($paid_percent, 1); ?>% paid</span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['status']; ?>">
                                        <i class="fa-solid fa-circle"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="view.php?id=<?php echo $row['loan_id']; ?>" class="action-btn view" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $row['loan_id']; ?>" class="action-btn edit" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="payment.php?id=<?php echo $row['loan_id']; ?>" class="action-btn payment" title="Record Payment">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['loan_id']; ?>" 
                                           class="action-btn delete" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this loan? This action cannot be undone.');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                                    <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                    <p style="font-size: 16px;">No loans found</p>
                                    <p style="font-size: 13px;">Start by creating your first loan</p>
                                    <a href="add.php" class="btn-quick btn-quick-primary" style="margin-top: 12px; display: inline-flex;">
                                        <i class="fa-solid fa-plus"></i> Add Loan
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="loan-pagination">
                <div class="info">
                    Showing <?php echo ($offset + 1); ?> - <?php echo min($offset + $per_page, $total_loans); ?> of <?php echo $total_loans; ?> loans
                </div>
                <div class="pagination-btns">
                    <button onclick="goToPage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <button class="<?php echo $i == $page ? 'active' : ''; ?>" onclick="goToPage(<?php echo $i; ?>)">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    <button onclick="goToPage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = this.value.trim();
                const url = new URL(window.location);
                if (search) {
                    url.searchParams.set('search', search);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url;
            }, 500);
        });
    }
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            const url = new URL(window.location);
            if (filter !== 'all') {
                url.searchParams.set('status', filter);
            } else {
                url.searchParams.delete('status');
            }
            url.searchParams.delete('page');
            window.location.href = url;
        });
    });
});

function goToPage(page) {
    if (page < 1) return;
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url;
}
</script>

<?php include "../includes/footer.php"; ?>