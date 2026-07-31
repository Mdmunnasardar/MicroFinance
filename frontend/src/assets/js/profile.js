// ============================================
// PROFILE MODULE - JAVASCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    // Avatar upload preview
    const avatarInput = document.getElementById('avatarUpload');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.avatar-img').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    console.log('👤 Profile module loaded successfully');
});