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

<div class="container mx-auto px-4 py-8 max-w-4xl">
    
    <div class="form-container animate-slide-up">
        
        <div class="form-card">
            
            <div class="form-header warning">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h2>Edit Committee</h2>
                        <p>Update committee information</p>
                    </div>
                </div>
            </div>

            <div class="form-body">
                <form method="POST" id="committeeForm" novalidate>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag label-icon warning-icon"></i>
                            Committee Name <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" name="committee_name" class="form-control" 
                                   placeholder="e.g., Village Development Committee" 
                                   value="<?php echo htmlspecialchars($committee['committee_name']); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-store label-icon warning-icon"></i>
                                Branch <span class="required">*</span>
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
                                <i class="fas fa-user-tie label-icon warning-icon"></i>
                                Field Officer <span class="required">*</span>
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

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-day label-icon warning-icon"></i>
                                Meeting Day <span class="required">*</span>
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
                                <i class="fas fa-clock label-icon warning-icon"></i>
                                Meeting Time <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-clock input-icon"></i>
                                <input type="time" name="meeting_time" class="form-control" 
                                       value="<?php echo $committee['meeting_time']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt label-icon warning-icon"></i>
                            Formation Date <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="formed_date" class="form-control" 
                                   value="<?php echo $committee['formed_date']; ?>" required>
                        </div>
                    </div>

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

                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Committee
                        </button>
                        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <a href="delete.php?id=<?php echo $committee_id; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Delete this committee?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>

                </form>
            </div>

        </div>

    </div>

</div>

<script src="../assets/js/committees.js"></script>

<?php include "../includes/footer.php"; ?>