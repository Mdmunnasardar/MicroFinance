<!-- Members Table -->
<div class="member-table-container">
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Contact</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($table_data['members']->num_rows > 0): ?>
                    <?php while($row = $table_data['members']->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="member-avatar">
                                    <?php echo strtoupper(substr($row['full_name'], 0, 2)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['member_code']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-600">
                                <p><i class="fas fa-phone text-gray-400 text-xs w-4"></i> <?php echo htmlspecialchars($row['phone']); ?></p>
                                <p><i class="fas fa-envelope text-gray-400 text-xs w-4"></i> <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></p>
                            </div>
                        </td>
                        <td>
                            <span class="branch-badge">
                                <?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                <i class="fas <?php echo $row['is_active'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="openQuickView(<?php echo $row['member_id']; ?>)" 
                                        class="action-btn view" title="Quick View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="view.php?id=<?php echo $row['member_id']; ?>" 
                                   class="action-btn profile" title="Full Profile">
                                    <i class="fas fa-user-circle"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $row['member_id']; ?>" 
                                   class="action-btn edit" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button onclick="deleteMember(<?php echo $row['member_id']; ?>, '<?php echo addslashes($row['full_name']); ?>')" 
                                        class="action-btn delete" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-users text-4xl text-gray-300"></i>
                                <p class="text-gray-500 font-medium">No members found</p>
                                <a href="add.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                    Add your first member
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Table Footer -->
    <div class="table-footer">
        <p class="text-sm text-gray-500">
            Showing <span class="font-medium"><?php echo $table_data['members']->num_rows; ?></span> members
        </p>
        <p class="text-sm text-gray-400">
            Total: <span class="font-medium text-gray-600"><?php echo number_format($table_data['total']); ?></span>
        </p>
    </div>
</div>

<style>
.member-table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.member-table-container table {
    width: 100%;
}
.member-table-container thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.member-table-container thead th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
}
.member-table-container tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.15s ease;
}
.member-table-container tbody tr:hover {
    background-color: #f9fafb;
}
.member-table-container tbody td {
    padding: 1rem 1.5rem;
}
.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}
.branch-badge {
    padding: 0.25rem 0.75rem;
    background: #eff6ff;
    color: #2563eb;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.status-badge.active {
    background: #ecfdf5;
    color: #065f46;
}
.status-badge.inactive {
    background: #fef2f2;
    color: #991b1b;
}
.action-btn {
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.action-btn.view { color: #2563eb; }
.action-btn.view:hover { background: #eff6ff; }
.action-btn.profile { color: #4f46e5; }
.action-btn.profile:hover { background: #eef2ff; }
.action-btn.edit { color: #d97706; }
.action-btn.edit:hover { background: #fffbeb; }
.action-btn.delete { color: #dc2626; }
.action-btn.delete:hover { background: #fef2f2; }
.table-footer {
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
@media (max-width: 768px) {
    .member-table-container {
        overflow-x: auto;
    }
    .member-table-container thead th,
    .member-table-container tbody td {
        padding: 0.75rem 1rem;
    }
}
</style>