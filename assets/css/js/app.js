/**
 * MICROFINANCE DASHBOARD
 * All JavaScript functionality
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
    // 2. CHARTS - Initialize Chart.js
    // ========================================
    const chartCanvas = document.getElementById('trendChart');
    
    if (chartCanvas) {
        // Get data from PHP (passed via JSON in the page)
        const chartData = window.chartData || {};
        
        if (chartData.labels && chartData.loanData && chartData.payData) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Loans Disbursed',
                            data: chartData.loanData,
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
                            data: chartData.payData,
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
        }
    }
    
    // ========================================
    // 3. SEARCH FUNCTIONALITY (Optional)
    // ========================================
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query.length > 0) {
                    // You can add search logic here
                    console.log('Searching for:', query);
                    // window.location.href = '/search.php?q=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // ========================================
    // 4. NOTIFICATION BELL (Optional)
    // ========================================
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // Add notification panel logic here
            alert('You have 5 notifications');
        });
    }
    
    // ========================================
    // 5. PROFILE DROPDOWN (Optional)
    // ========================================
    const profile = document.querySelector('.profile');
    if (profile) {
        profile.addEventListener('click', function() {
            // Add profile dropdown logic here
            console.log('Profile clicked');
        });
    }
    
    // ========================================
    // 6. AUTO-DISMISS ALERTS
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
        }, 8000); // Auto-dismiss after 8 seconds
    }
});