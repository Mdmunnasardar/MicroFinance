// ============================================
// COMMITTEES MODULE - JAVASCRIPT
// ============================================

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initAlerts();
        initSearch();
        initSelectAll();
        initMemberItems();
        initFormValidation();
        initToggleStatusLabel();
        updateSelectedCount();
    });

    // ============================================
    // ALERTS
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
    // SEARCH
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
    // SELECT ALL
    // ============================================
    function initSelectAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;

        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.member-checkbox').forEach((cb, index) => {
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
    // MEMBER ITEMS
    // ============================================
    function initMemberItems() {
        document.querySelectorAll('.member-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.type === 'checkbox') return;
                const checkbox = this.querySelector('.member-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected');
                    updateSelectedCount();
                }
            });
        });

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
    // UPDATE SELECTED COUNT
    // ============================================
    window.updateSelectedCount = function() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        const count = checked.length;
        const countEl = document.getElementById('selectedCount');
        const assignBtn = document.getElementById('assignBtn');

        if (countEl) {
            countEl.textContent = count + ' selected';
        }
        if (assignBtn) {
            assignBtn.disabled = count === 0;
        }
    };

    // ============================================
    // FORM VALIDATION
    // ============================================
    function initFormValidation() {
        const form = document.getElementById('committeeForm');
        if (!form) return;

        form.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });

        form.addEventListener('submit', function(e) {
            let isValid = true;
            this.querySelectorAll('[required]').forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                const firstError = this.querySelector('.form-control.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        function validateField(field) {
            const errorMsg = field.closest('.form-group').querySelector('.error-message');
            const isRequired = field.hasAttribute('required');
            
            if (isRequired) {
                if (field.value.trim() === '' || field.value === '') {
                    field.classList.add('error');
                    field.classList.remove('success');
                    if (errorMsg) errorMsg.classList.add('show');
                    return false;
                } else {
                    field.classList.remove('error');
                    field.classList.add('success');
                    if (errorMsg) errorMsg.classList.remove('show');
                    return true;
                }
            }
            return true;
        }
    }

    // ============================================
    // TOGGLE STATUS LABEL
    // ============================================
    function initToggleStatusLabel() {
        const toggle = document.getElementById('isActive');
        const statusLabel = document.getElementById('statusLabel');
        
        if (toggle && statusLabel) {
            toggle.addEventListener('change', function() {
                if (this.checked) {
                    statusLabel.textContent = 'Active';
                    statusLabel.className = 'toggle-status active';
                } else {
                    statusLabel.textContent = 'Inactive';
                    statusLabel.className = 'toggle-status inactive';
                }
            });
        }
    }

    // ============================================
    // GLOBAL FUNCTIONS
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

    console.log('🏦 Committees Module loaded successfully');
})();