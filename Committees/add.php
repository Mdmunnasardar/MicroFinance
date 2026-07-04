<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Insert data
if (isset($_POST['submit'])) {
    $name = $_POST['committee_name'];
    $branch_id = $_POST['branch_id'];
    $officer_id = $_POST['field_officer_id'];
    $day = $_POST['meeting_day'];
    $time = $_POST['meeting_time'];
    $date = $_POST['formed_date'];

    $sql = "INSERT INTO committees (
        committee_name,
        branch_id,
        field_officer_id,
        meeting_day,
        meeting_time,
        formed_date,
        is_active
    ) VALUES (
        '$name',
        '$branch_id',
        '$officer_id',
        '$day',
        '$time',
        '$date',
        1
    )";

    $conn->query($sql);
    header("Location: index.php");
    exit();
}

// Get branches
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Get field officers
$officers = $conn->query("SELECT * FROM users WHERE role = 'field_officer' ORDER BY full_name");

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Committee - MicroFinance</title>
    <link rel="stylesheet" href="../assets/css/committees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           ADD COMMITTEE PAGE - PREMIUM STYLES
           ============================================ */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .form-card .form-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
        }

        .form-card .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .form-card .form-header::after {
            content: '';
            position: absolute;
            bottom: -60%;
            left: -10%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .form-card .form-header .header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-card .form-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .form-card .form-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin: 4px 0 0 0;
        }

        .form-card .form-body {
            padding: 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .form-group .form-label .required {
            color: #ef4444;
            margin-left: 2px;
        }

        .form-group .form-label .label-icon {
            margin-right: 6px;
            color: #4f46e5;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s ease;
            background: white;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control.error {
            border-color: #ef4444;
        }

        .form-control.success {
            border-color: #22c55e;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
            cursor: pointer;
        }

        select.form-control option {
            padding: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            padding-top: 24px;
            border-top: 2px solid #f1f5f9;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:active {
            transform: scale(0.97);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .btn .btn-icon {
            font-size: 16px;
        }

        /* Field with icon */
        .input-with-icon {
            position: relative;
        }

        .input-with-icon .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }

        .input-with-icon .form-control {
            padding-left: 44px;
        }

        /* Error message */
        .error-message {
            color: #ef4444;
            font-size: 12px;
            font-weight: 500;
            margin-top: 4px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-card .form-body {
                padding: 20px;
            }

            .form-card .form-header {
                padding: 20px 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    
    <!-- Form Container -->
    <div class="form-container animate-slide-up">
        
        <!-- Form Card -->
        <div class="form-card">
            
            <!-- Header -->
            <div class="form-header flex items-center gap-4">
                <div class="header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <h2>Create New Committee</h2>
                    <p>Fill in the details to add a new committee</p>
                </div>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <form method="POST" id="committeeForm" novalidate>
                    
                    <!-- Committee Name -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag label-icon"></i>
                            Committee Name
                            <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" name="committee_name" class="form-control" 
                                   placeholder="e.g., Village Development Committee" 
                                   required autofocus>
                        </div>
                        <div class="error-message">Please enter a committee name</div>
                    </div>

                    <!-- Branch & Officer Row -->
                    <div class="form-row">
                        <!-- Branch -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-store label-icon"></i>
                                Branch
                                <span class="required">*</span>
                            </label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Select Branch</option>
                                <?php while($b = $branches->fetch_assoc()): ?>
                                <option value="<?php echo $b['branch_id']; ?>">
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="error-message">Please select a branch</div>
                        </div>

                        <!-- Field Officer -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie label-icon"></i>
                                Field Officer
                                <span class="required">*</span>
                            </label>
                            <select name="field_officer_id" class="form-control" required>
                                <option value="">Select Field Officer</option>
                                <?php while($o = $officers->fetch_assoc()): ?>
                                <option value="<?php echo $o['user_id']; ?>">
                                    <?php echo htmlspecialchars($o['full_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="error-message">Please select a field officer</div>
                        </div>
                    </div>

                    <!-- Meeting Day & Time Row -->
                    <div class="form-row">
                        <!-- Meeting Day -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-day label-icon"></i>
                                Meeting Day
                                <span class="required">*</span>
                            </label>
                            <select name="meeting_day" class="form-control" required>
                                <option value="">Select Day</option>
                                <?php foreach($days as $day): ?>
                                <option value="<?php echo $day; ?>">
                                    <?php echo $day; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message">Please select a meeting day</div>
                        </div>

                        <!-- Meeting Time -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock label-icon"></i>
                                Meeting Time
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-clock input-icon"></i>
                                <input type="time" name="meeting_time" class="form-control" required>
                            </div>
                            <div class="error-message">Please select a meeting time</div>
                        </div>
                    </div>

                    <!-- Formed Date -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt label-icon"></i>
                            Formation Date
                            <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="formed_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="error-message">Please select a formation date</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save btn-icon"></i>
                            Create Committee
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times btn-icon"></i>
                            Cancel
                        </a>
                    </div>

                </form>
            </div>

        </div>

        <!-- Info Note -->
        <div class="mt-6 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
            <i class="fas fa-info-circle text-indigo-400"></i>
            <span>All fields marked with <span class="text-red-500">*</span> are required</span>
        </div>

    </div>

</div>

<!-- ==========================================
     SCRIPTS
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('committeeForm');

    // Real-time validation on input
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

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = this.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = this.querySelector('.form-control.error');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Auto-dismiss alerts if any
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    console.log('🏦 Add Committee page loaded');
});
</script>

</body>
</html>

<?php include "../includes/footer.php"; ?>