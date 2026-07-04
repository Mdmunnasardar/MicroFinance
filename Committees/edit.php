<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$committee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($committee_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch committee details
$sql = "SELECT * FROM committees WHERE committee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $committee_id);
$stmt->execute();
$committee = $stmt->get_result()->fetch_assoc();

if (!$committee) {
    header("Location: index.php");
    exit();
}

// Update committee
if (isset($_POST['submit'])) {
    $name = $_POST['committee_name'];
    $branch_id = $_POST['branch_id'];
    $officer_id = $_POST['field_officer_id'];
    $day = $_POST['meeting_day'];
    $time = $_POST['meeting_time'];
    $date = $_POST['formed_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $update_sql = "
    UPDATE committees SET
        committee_name = ?,
        branch_id = ?,
        field_officer_id = ?,
        meeting_day = ?,
        meeting_time = ?,
        formed_date = ?,
        is_active = ?
    WHERE committee_id = ?
    ";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("siissiii", $name, $branch_id, $officer_id, $day, $time, $date, $is_active, $committee_id);
    
    if ($stmt->execute()) {
        header("Location: view.php?id=" . $committee_id . "&success=updated");
        exit();
    }
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
    <title>Edit Committee - MicroFinance</title>
    <link rel="stylesheet" href="../assets/css/committees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as add.php */
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
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
            color: #f59e0b;
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
            cursor: pointer;
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
            background: #f59e0b;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.35);
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

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.35);
        }

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

        /* Toggle Switch */
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }

        .toggle-switch input[type="checkbox"] {
            width: 44px;
            height: 24px;
            appearance: none;
            background: #cbd5e1;
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .toggle-switch input[type="checkbox"]:checked {
            background: #22c55e;
        }

        .toggle-switch input[type="checkbox"]::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 3px;
            left: 3px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .toggle-switch input[type="checkbox"]:checked::before {
            left: 23px;
        }

        .toggle-switch .toggle-label {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .toggle-switch .toggle-status {
            font-size: 12px;
            font-weight: 600;
            padding: 2px 12px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #475569;
        }

        .toggle-switch .toggle-status.active {
            background: #dcfce7;
            color: #16a34a;
        }

        .toggle-switch .toggle-status.inactive {
            background: #fee2e2;
            color: #dc2626;
        }

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
    
    <div class="form-container animate-slide-up">
        
        <div class="form-card">
            
            <!-- Header -->
            <div class="form-header flex items-center gap-4">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h2>Edit Committee</h2>
                    <p>Update committee information</p>
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
                                   value="<?php echo htmlspecialchars($committee['committee_name']); ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Branch & Officer Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-store label-icon"></i>
                                Branch
                                <span class="required">*</span>
                            </label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Select Branch</option>
                                <?php while($b = $branches->fetch_assoc()): ?>
                                <option value="<?php echo $b['branch_id']; ?>" 
                                    <?php echo $committee['branch_id'] == $b['branch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie label-icon"></i>
                                Field Officer
                                <span class="required">*</span>
                            </label>
                            <select name="field_officer_id" class="form-control" required>
                                <option value="">Select Field Officer</option>
                                <?php while($o = $officers->fetch_assoc()): ?>
                                <option value="<?php echo $o['user_id']; ?>" 
                                    <?php echo $committee['field_officer_id'] == $o['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($o['full_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Meeting Day & Time Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-day label-icon"></i>
                                Meeting Day
                                <span class="required">*</span>
                            </label>
                            <select name="meeting_day" class="form-control" required>
                                <?php foreach($days as $day): ?>
                                <option value="<?php echo $day; ?>" 
                                    <?php echo $committee['meeting_day'] == $day ? 'selected' : ''; ?>>
                                    <?php echo $day; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock label-icon"></i>
                                Meeting Time
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-clock input-icon"></i>
                                <input type="time" name="meeting_time" class="form-control" 
                                       value="<?php echo $committee['meeting_time']; ?>" required>
                            </div>
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
                                   value="<?php echo $committee['formed_date']; ?>" required>
                        </div>
                    </div>

                    <!-- Status Toggle -->
                    <div class="form-group">
                        <div class="toggle-switch">
                            <input type="checkbox" name="is_active" id="isActive" 
                                   <?php echo $committee['is_active'] ? 'checked' : ''; ?>>
                            <label class="toggle-label" for="isActive">Committee Status</label>
                            <span class="toggle-status <?php echo $committee['is_active'] ? 'active' : 'inactive'; ?>" id="statusLabel">
                                <?php echo $committee['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save btn-icon"></i>
                            Update Committee
                        </button>
                        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times btn-icon"></i>
                            Cancel
                        </a>
                        <a href="delete.php?id=<?php echo $committee_id; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Delete this committee?')">
                            <i class="fas fa-trash btn-icon"></i>
                            Delete
                        </a>
                    </div>

                </form>
            </div>

        </div>

    </div>

</div>

<!-- ==========================================
     SCRIPTS
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle status label
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

    console.log('✏️ Edit Committee page loaded');
});
</script>

</body>
</html>

<?php include "../includes/footer.php"; ?>