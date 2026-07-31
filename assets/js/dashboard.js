/**
 * MICROFINANCE DASHBOARD
 * Additional JavaScript functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // 1. MOBILE MENU TOGGLE
    // ========================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }
    
    // ========================================
    // 2. SEARCH FUNCTIONALITY
    // ========================================
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query.length > 0) {
                    console.log('Searching for:', query);
                    // window.location.href = '/search.php?q=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // ========================================
    // 3. NOTIFICATION BELL
    // ========================================
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            alert('You have notifications!');
        });
    }
    
    // ========================================
    // 4. AUTO-DISMISS ALERTS
    // ========================================
    const alerts = document.querySelectorAll('.overdue-alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 8000);
    }
});