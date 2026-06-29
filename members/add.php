<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get committees and branches for dropdowns
$committees = $conn->query("SELECT * FROM committees ORDER BY committee_name");
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

if (isset($_POST['save'])) {
    $member_code = $_POST['member_code'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
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

    $sql = "INSERT INTO members (
        member_code, full_name, email, phone, dob, address, 
        national_id, guarantor_name, guarantor_phone, 
        committee_id, branch_id, join_date, is_active
    ) VALUES (
        '$member_code', '$full_name', '$email', '$phone', '$dob', '$address',
        '$national_id', '$guarantor_name', '$guarantor_phone',
        $committee_id, $branch_id, '$join_date', $is_active
    )";

    if ($conn->query($sql)) {
        header("Location: index.php?success=Member added successfully");
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
    <title>Add Member - MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/members.css">
</head>
<body>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>
<?php include "../includes/topbar.php"; ?>

<div class="main-content">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-user-plus text-indigo-600"></i>
                    Add New Member
                </h1>
                <p class="text-gray-500 mt-1">Register a new member in the system</p>
            </div>
            <a href="index.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <?php if(isset($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-indigo-600"></i>
                        Personal Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" required 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Member Code *</label>
                            <input type="text" name="member_code" required 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="e.g., MEM-2024-001">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter email address">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" name="phone" required 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter phone number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                            <input type="date" name="dob" 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">National ID</label>
                            <input type="text" name="national_id" 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter NID number">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="2" 
                                  class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                  placeholder="Enter full address"></textarea>
                    </div>
                </div>

                <!-- Guarantor Information -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-handshake text-indigo-600"></i>
                        Guarantor Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Guarantor Name</label>
                            <input type="text" name="guarantor_name" 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter guarantor name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Guarantor Phone</label>
                            <input type="text" name="guarantor_phone" 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200"
                                   placeholder="Enter guarantor phone">
                        </div>
                    </div>
                </div>

                <!-- Organization Information -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-building text-indigo-600"></i>
                        Organization Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Committee</label>
                            <select name="committee_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200">
                                <option value="">Select Committee</option>
                                <?php while($c = $committees->fetch_assoc()): ?>
                                <option value="<?php echo $c['committee_id']; ?>"><?php echo htmlspecialchars($c['committee_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                            <select name="branch_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200">
                                <option value="">Select Branch</option>
                                <?php while($b = $branches->fetch_assoc()): ?>
                                <option value="<?php echo $b['branch_id']; ?>"><?php echo htmlspecialchars($b['branch_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Join Date *</label>
                            <input type="date" name="join_date" required 
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all duration-200">
                        </div>
                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="is_active" checked 
                                   class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                            <label class="ml-3 text-sm font-medium text-gray-700">Active Member</label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="border-t border-gray-200 pt-6 flex flex-col sm:flex-row gap-4">
                    <button type="submit" name="save" 
                            class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all duration-200 flex items-center justify-center gap-2 font-medium shadow-lg shadow-indigo-600/20">
                        <i class="fas fa-save"></i> Save Member
                    </button>
                    <a href="index.php" 
                       class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 flex items-center justify-center gap-2 font-medium">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>

</body>
</html>