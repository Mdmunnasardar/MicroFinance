// ============================================
// COMMITTEES MODULE - JavaScript
// ============================================

(function() {
    'use strict';

    // ============================================
    // 1. DOM READY
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initAlerts();
        initSearch();
        initSelectAll();
        initMemberItems();
        initStatusToggles();
        initDeleteButtons();
        initExportButtons();
        updateSelectedCount();
    });

    // ============================================
    // 2. INIT ALERTS (Auto-dismiss)
    // ============================================
    function initAlerts() {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            setTimeout(() => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) closeBtn.click();
            }, 5000);
        });
    }

    // ============================================
    // 3. INIT SEARCH (Debounce)
    // ============================================
    function initSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const form = this.closest('form');
                if (form) form.submit();
            }, 500);
        });
    }

    // ============================================
    // 4. INIT SELECT ALL
    // ============================================
    function initSelectAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;

        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach((cb, index) => {
                cb.checked = this.checked;
                const parent = cb.closest('.member-item');
                if (parent) {
                    parent.classList.toggle('selected', this.checked);
                }
            });
            updateSelectedCount();
        });
    }

    // ============================================
    // 5. INIT MEMBER ITEMS
    // ============================================
    function initMemberItems() {
        document.querySelectorAll('.member-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Ignore if clicking checkbox directly
                if (e.target.type === 'checkbox') return;

                const checkbox = this.querySelector('.member-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected');
                    updateSelectedCount();
                }
            });
        });

        // Individual checkbox change
        document.querySelectorAll('.member-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('.member-item');
                if (parent) {
                    parent.classList.toggle('selected', this.checked);
                }
                updateSelectedCount();
            });
        });
    }

    // ============================================
    // 6. UPDATE SELECTED COUNT
    // ============================================
    window.updateSelectedCount = function() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        const count = checked.length;
        const countEl = document.getElementById('selectedCount');
        const assignBtn = document.getElementById('assignBtn');

        if (countEl) {
            countEl.textContent = count + ' selected';
            countEl.style.display = count > 0 ? 'inline-block' : 'inline-block';
        }
        if (assignBtn) {
            assignBtn.disabled = count === 0;
            assignBtn.classList.toggle('opacity-50', count === 0);
            assignBtn.classList.toggle('cursor-not-allowed', count === 0);
        }
    };

    // ============================================
    // 7. INIT STATUS TOGGLES
    // ============================================
    function initStatusToggles() {
        document.querySelectorAll('[data-toggle-status]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const currentStatus = parseInt(this.dataset.status);
                const action = currentStatus ? 'deactivate' : 'activate';
                const committeeName = this.dataset.name || 'this committee';

                if (confirm(`Are you sure you want to ${action} "${committeeName}"?`)) {
                    window.location.href = `toggle-status.php?id=${id}&status=${currentStatus ? 0 : 1}`;
                }
            });
        });
    }

    // ============================================
    // 8. INIT DELETE BUTTONS
    // ============================================
    function initDeleteButtons() {
        document.querySelectorAll('[data-delete-committee]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const name = this.dataset.name || 'this committee';

                if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                    window.location.href = `delete.php?id=${id}`;
                }
            });
        });
    }

    // ============================================
    // 9. INIT EXPORT BUTTONS
    // ============================================
    function initExportButtons() {
        document.querySelectorAll('[data-export]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const format = this.dataset.format || 'csv';
                alert(`Export feature coming soon! (${format})`);
                // window.location.href = `export.php?id=${id}&format=${format}`;
            });
        });
    }

    // ============================================
    // 10. GLOBAL HELPERS
    // ============================================
    window.toggleStatus = function(id, currentStatus, name) {
        const action = currentStatus ? 'deactivate' : 'activate';
        if (confirm(`Are you sure you want to ${action} "${name}"?`)) {
            window.location.href = `toggle-status.php?id=${id}&status=${currentStatus ? 0 : 1}`;
        }
    };

    window.deleteCommittee = function(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
            window.location.href = `delete.php?id=${id}`;
        }
    };

    window.formatCurrency = function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        }).format(amount);
    };

    window.formatDate = function(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    window.getStatusBadge = function(status) {
        const map = {
            'Active': 'badge-status active',
            'Inactive': 'badge-status inactive',
            'Dissolved': 'badge-status danger'
        };
        return map[status] || 'badge-status secondary';
    };

    console.log('🏦 Committees Module loaded successfully');
})();