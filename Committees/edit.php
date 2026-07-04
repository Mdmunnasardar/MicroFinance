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
        header("Location: view.php?id=" . $committee_id . "&success=1");
        exit();
    } else {
        $error = "Error updating committee: " . $conn->error;
    }
}

// Get branches
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Get field officers
$officers = $conn->query("SELECT * FROM users WHERE role = 'field_officer' ORDER BY full_name");

$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Committee - MicroFinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-label {
            font-weight: 600;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3><i class="fas fa-edit text-primary me-2"></i>Edit Committee</h3>
            <p class="text-muted mb-0">Update committee information</p>
        </div>
        <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST">

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label required">Committee Name</label>
                        <input type="text" name="committee_name" class="form-control" 
                               value="<?php echo htmlspecialchars($committee['committee_name']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Branch</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            <?php while($b = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $b['branch_id']; ?>" 
                                <?php echo $committee['branch_id'] == $b['branch_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['branch_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Field Officer</label>
                        <select name="field_officer_id" class="form-select" required>
                            <option value="">Select Officer</option>
                            <?php while($o = $officers->fetch_assoc()): ?>
                            <option value="<?php echo $o['user_id']; ?>" 
                                <?php echo $committee['field_officer_id'] == $o['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($o['full_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Meeting Day</label>
                        <select name="meeting_day" class="form-select" required>
                            <?php foreach($days as $day): ?>
                            <option value="<?php echo $day; ?>" 
                                <?php echo $committee['meeting_day'] == $day ? 'selected' : ''; ?>>
                                <?php echo $day; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Meeting Time</label>
                        <input type="time" name="meeting_time" class="form-control" 
                               value="<?php echo $committee['meeting_time']; ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Formed Date</label>
                        <input type="date" name="formed_date" class="form-control" 
                               value="<?php echo $committee['formed_date']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" class="form-check-input" 
                                   id="is_active" <?php echo $committee['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-<?php echo $committee['is_active'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-1"></i>
                                Active Committee
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Committee
                    </button>
                    <a href="view.php?id=<?php echo $committee_id; ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('is_active').addEventListener('change', function() {
    const label = document.querySelector('label[for="is_active"]');
    if (this.checked) {
        label.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Active Committee';
    } else {
        label.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Inactive Committee';
    }
});
</script>

</body>
</html>