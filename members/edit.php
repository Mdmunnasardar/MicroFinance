<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM members WHERE member_id=$id");
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: index.php?error=Member not found");
    exit();
}

$committees = $conn->query("SELECT * FROM committees ORDER BY committee_name");
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

if (isset($_POST['update'])) {
    $member_code = $_POST['member_code'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $national_id = $_POST['national_id'];
    $guarantor_name = $_POST['guarantor_name'];
    $guarantor_phone = $_POST['guarantor_phone'];
    $committee_id = $_POST['committee_id'] ?: 'NULL';
    $branch_id = $_POST['branch_id'] ?: 'NULL';
    $join_date = $_POST['join_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE members SET 
        member_code='$member_code',
        full_name='$full_name',
        phone='$phone',
        dob='$dob',
        address='$address',
        national_id='$national_id',
        guarantor_name='$guarantor_name',
        guarantor_phone='$guarantor_phone',
        committee_id=$committee_id,
        branch_id=$branch_id,
        join_date='$join_date',
        is_active=$is_active
        WHERE member_id=$id
    ";

    if ($conn->query($sql)) {
        header("Location: index.php?success=Member updated successfully");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Members CSS -->
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<div class="container">

    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Members
    </a>

    <h1 class="page-title">
        <i class="fa-solid fa-user-edit"></i> Edit Member
    </h1>

    <!-- Form -->
    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <h5 class="mb-3"><i class="fa-solid fa-user text-primary"></i> Personal Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" required class="form-control"
                               value="<?php echo htmlspecialchars($data['full_name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Member Code *</label>
                        <input type="text" name="member_code" required class="form-control"
                               value="<?php echo htmlspecialchars($data['member_code']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone *</label>
                        <input type="text" name="phone" required class="form-control"
                               value="<?php echo htmlspecialchars($data['phone']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control"
                               value="<?php echo $data['dob']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">National ID</label>
                        <input type="text" name="national_id" class="form-control"
                               value="<?php echo htmlspecialchars($data['national_id']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="2" class="form-control"><?php echo htmlspecialchars($data['address']); ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3"><i class="fa-solid fa-handshake text-success"></i> Guarantor Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Guarantor Name</label>
                        <input type="text" name="guarantor_name" class="form-control"
                               value="<?php echo htmlspecialchars($data['guarantor_name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Guarantor Phone</label>
                        <input type="text" name="guarantor_phone" class="form-control"
                               value="<?php echo htmlspecialchars($data['guarantor_phone']); ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3"><i class="fa-solid fa-building text-info"></i> Organization Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Committee</label>
                        <select name="committee_id" class="form-select">
                            <option value="">Select Committee</option>
                            <?php while ($c = $committees->fetch_assoc()): ?>
                                <option value="<?php echo $c['committee_id']; ?>"
                                    <?php if ($data['committee_id'] == $c['committee_id']) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($c['committee_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Select Branch</option>
                            <?php while ($b = $branches->fetch_assoc()): ?>
                                <option value="<?php echo $b['branch_id']; ?>"
                                    <?php if ($data['branch_id'] == $b['branch_id']) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Join Date *</label>
                        <input type="date" name="join_date" required class="form-control"
                               value="<?php echo $data['join_date']; ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" <?php if ($data['is_active']) echo "checked"; ?>
                                   class="form-check-input" id="isActive">
                            <label class="form-check-label" for="isActive">Active Member</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex gap-3">
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Member
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>