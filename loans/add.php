<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ======================
// INSERT LOAN
// ======================
if (isset($_POST['submit'])) {

    $loan_code = $_POST['loan_code'];
    $member_id = $_POST['member_id'];

    $principal = $_POST['principal_amount'];
    $rate = $_POST['interest_rate'];
    $interest_type = $_POST['interest_type'];

    $term = $_POST['loan_term_months'];
    $installment_type = $_POST['installment_type'];

    $disbursement_date = $_POST['disbursement_date'];
    $first_installment_date = $_POST['first_installment_date'];

    $purpose = $_POST['purpose'];

    // ======================
    // AUTO CALCULATION
    // ======================

    // Flat interest
    if ($interest_type == 'flat') {
        $interest = ($principal * $rate / 100);
        $total_payable = $principal + $interest;
    } else {
        // reducing balance (simple version)
        $total_payable = $principal + ($principal * $rate / 100);
    }

    $installment_amount = $total_payable / $term;

    // Maturity Date
    $maturity_date = date('Y-m-d', strtotime($disbursement_date . " + $term months"));

    // Get branch from member
    $member = $conn->query("SELECT branch_id FROM members WHERE member_id=$member_id");
    $m = $member->fetch_assoc();
    $branch_id = $m['branch_id'];

    // Insert
    $sql = "INSERT INTO loans (
        loan_code,
        member_id,
        branch_id,
        principal_amount,
        interest_rate,
        interest_type,
        loan_term_months,
        installment_type,
        installment_amount,
        total_payable,
        total_paid,
        disbursement_date,
        first_installment_date,
        maturity_date,
        status,
        purpose
    ) VALUES (
        '$loan_code',
        '$member_id',
        '$branch_id',
        '$principal',
        '$rate',
        '$interest_type',
        '$term',
        '$installment_type',
        '$installment_amount',
        '$total_payable',
        0,
        '$disbursement_date',
        '$first_installment_date',
        '$maturity_date',
        'active',
        '$purpose'
    )";

    $conn->query($sql);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Loan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Add Loan</h3>

<form method="POST">

<!-- Loan Code -->
<input type="text" name="loan_code" class="form-control mb-2" placeholder="Loan Code (L001)" required>

<!-- Member -->
<select name="member_id" class="form-control mb-2" required>
<option value="">Select Member</option>

<?php
$members = $conn->query("SELECT * FROM members");
while($m = $members->fetch_assoc()) {
?>
<option value="<?php echo $m['member_id']; ?>">
    <?php echo $m['full_name']; ?> (<?php echo $m['member_code']; ?>)
</option>
<?php } ?>

</select>

<!-- Principal -->
<input type="number" name="principal_amount" class="form-control mb-2" placeholder="Principal Amount" required>

<!-- Interest -->
<input type="number" step="0.01" name="interest_rate" class="form-control mb-2" placeholder="Interest Rate (%)" required>

<!-- Interest Type -->
<select name="interest_type" class="form-control mb-2">
    <option value="flat">Flat</option>
    <option value="reducing_balance">Reducing Balance</option>
</select>

<!-- Term -->
<input type="number" name="loan_term_months" class="form-control mb-2" placeholder="Loan Term (months)" required>

<!-- Installment Type -->
<select name="installment_type" class="form-control mb-2">
    <option value="monthly">Monthly</option>
    <option value="weekly">Weekly</option>
</select>

<!-- Dates -->
<input type="date" name="disbursement_date" class="form-control mb-2" required>

<input type="date" name="first_installment_date" class="form-control mb-2" required>

<!-- Purpose -->
<input type="text" name="purpose" class="form-control mb-2" placeholder="Loan Purpose">

<!-- Submit -->
<button type="submit" name="submit" class="btn btn-success w-100">
Create Loan
</button>

</form>

</div>

</body>
</html>