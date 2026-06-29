<!-- Search & Filter -->
<div class="filter-section">
    <form method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" 
                   name="search" 
                   class="filter-input filter-search pl-10" 
                   placeholder="Search by name, code, phone or email..." 
                   value="<?php echo htmlspecialchars($filter_data['search']); ?>">
        </div>
        
        <div class="md:w-48">
            <select name="branch" class="filter-input">
                <option value="">All Branches</option>
                <?php while($b = $filter_data['branches']->fetch_assoc()): ?>
                <option value="<?php echo $b['branch_id']; ?>" 
                        <?php if($filter_data['branch'] == $b['branch_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($b['branch_name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="md:w-40">
            <select name="status" class="filter-input">
                <option value="">All Status</option>
                <option value="1" <?php if($filter_data['status'] == "1") echo "selected"; ?>>Active</option>
                <option value="0" <?php if($filter_data['status'] == "0") echo "selected"; ?>>Inactive</option>
            </select>
        </div>
        
        <button type="submit" class="filter-btn">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if($filter_data['search'] || $filter_data['branch'] || $filter_data['status']): ?>
        <a href="index.php" class="filter-clear-btn">
            <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
    </form>
</div>

<style>
.filter-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}
.filter-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    outline: none;
    transition: all 0.2s ease;
}
.filter-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.filter-btn {
    padding: 0.625rem 1.5rem;
    background: #4f46e5;
    color: white;
    border-radius: 8px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    border: none;
    cursor: pointer;
}
.filter-btn:hover {
    background: #4338ca;
}
.filter-clear-btn {
    padding: 0.625rem 1.5rem;
    background: #f3f4f6;
    color: #374151;
    border-radius: 8px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    text-decoration: none;
}
.filter-clear-btn:hover {
    background: #e5e7eb;
}
@media (max-width: 768px) {
    .filter-section {
        padding: 1rem;
    }
}
</style>