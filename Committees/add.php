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

<div class="container mx-auto px-4 py-8 max-w-4xl">
    
    <div class="form-container animate-slide-up">
        
        <div class="form-card">
            
            <div class="form-header primary">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <h2>Create New Committee</h2>
                        <p>Fill in the details to add a new committee</p>
                    </div>
                </div>
            </div>

            <div class="form-body">
                <form method="POST" id="committeeForm" novalidate>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag label-icon primary-icon"></i>
                            Committee Name <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" name="committee_name" class="form-control" 
                                   placeholder="e.g., Village Development Committee" 
                                   required autofocus>
                        </div>
                        <div class="error-message">Please enter a committee name</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-store label-icon primary-icon"></i>
                                Branch <span class="required">*</span>
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

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie label-icon primary-icon"></i>
                                Field Officer <span class="required">*</span>
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

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-day label-icon primary-icon"></i>
                                Meeting Day <span class="required">*</span>
                            </label>
                            <select name="meeting_day" class="form-control" required>
                                <option value="">Select Day</option>
                                <?php foreach($days as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message">Please select a meeting day</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock label-icon primary-icon"></i>
                                Meeting Time <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-clock input-icon"></i>
                                <input type="time" name="meeting_time" class="form-control" required>
                            </div>
                            <div class="error-message">Please select a meeting time</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt label-icon primary-icon"></i>
                            Formation Date <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="formed_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="error-message">Please select a formation date</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Committee
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </form>
            </div>

        </div>

        <div class="mt-6 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
            <i class="fas fa-info-circle text-primary"></i>
            <span>All fields marked with <span class="text-danger">*</span> are required</span>
        </div>

    </div>

</div>

<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>