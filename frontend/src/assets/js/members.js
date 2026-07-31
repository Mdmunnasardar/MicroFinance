/**
 * MEMBERS MODULE - JavaScript
 * All member-related functionality
 */

// ============================================
// QUICK VIEW MODAL
// ============================================

function openQuickView(memberId) {
    const modal = document.getElementById('quickViewModal');
    const content = document.getElementById('quickViewContent');
    
    if (!modal || !content) return;
    
    // Show loading
    content.innerHTML = `
        <div class="p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
            <p class="mt-4 text-gray-500">Loading member details...</p>
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        content.classList.add('active');
    }, 10);
    
    // Fetch data
    fetch(`quick_view.php?id=${memberId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = `
                <div class="p-8 text-center">
                    <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                    <p class="text-gray-600 font-medium">Failed to load member data</p>
                    <button onclick="closeQuickView()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Close
                    </button>
                </div>
            `;
        });
}

function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    const content = document.getElementById('quickViewContent');
    
    if (!modal || !content) return;
    
    content.classList.remove('active');
    
    setTimeout(() => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }, 300);
}

// ============================================
// DELETE MEMBER
// ============================================

function deleteMember(memberId, memberName) {
    if (confirm(`⚠️ Are you sure you want to delete "${memberName}"?\n\nThis action cannot be undone!`)) {
        window.location.href = `delete.php?id=${memberId}`;
    }
}

// ============================================
// SEARCH AUTO-FOCUS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.filter-search');
    if (searchInput && !searchInput.value && window.innerWidth > 768) {
        setTimeout(() => searchInput.focus(), 300);
    }
});

// ============================================
// CLOSE MODAL ON ESCAPE
// ============================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuickView();
    }
});

// ============================================
// CLOSE MODAL ON BACKDROP CLICK
// ============================================

document.addEventListener('click', function(e) {
    const modal = document.getElementById('quickViewModal');
    if (e.target === modal) {
        closeQuickView();
    }
});

// ============================================
// EXPORT FUNCTIONALITY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.querySelector('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            // Show loading state on button
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            this.disabled = true;
            
            // Reset after export (actual export handled by PHP)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 2000);
        });
    }
});