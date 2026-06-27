<?php
function renderRecentTransactions($transactions) {
?>
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
                <?php if($transactions && $transactions->num_rows > 0): ?>
                    <?php while($row = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge-type <?php echo strtolower($row['type']); ?>"><?php echo $row['type']; ?></span></td>
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
<?php
}
?>