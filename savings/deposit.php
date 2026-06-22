<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['submit'])){

    $saving_id = $_POST['saving_id'];
    $amount = $_POST['amount'];
    $notes = $_POST['notes'];

    $saving = $conn->query("
        SELECT * FROM savings
        WHERE saving_id='$saving_id'
    ");

    $s = $saving->fetch_assoc();

    $new_balance = $s['balance'] + $amount;

    // Update savings balance
    $conn->query("
        UPDATE savings
        SET
        balance='$new_balance',
        last_transaction_date=CURDATE()
        WHERE saving_id='$saving_id'
    ");

    // Insert transaction
    $conn->query("
        INSERT INTO savings_transactions(
            saving_id,
            type,
            amount,
            balance_after,
            processed_by,
            notes
        )
        VALUES(
            '$saving_id',
            'deposit',
            '$amount',
            '$new_balance',
            '{$_SESSION['user_id']}',
            '$notes'
        )
    ");

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Deposit Savings</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>Deposit Savings</h3>

<form method="POST">

<select name="saving_id" class="form-control mb-3" required>

<option value="">Select Account</option>

<?php
$result = $conn->query("
SELECT s.saving_id,m.full_name
FROM savings s
LEFT JOIN members m
ON s.member_id=m.member_id
");

while($row=$result->fetch_assoc()){
?>

<option value="<?php echo $row['saving_id']; ?>">
    <?php echo $row['full_name']; ?>
</option>

<?php } ?>

</select>

<input
type="number"
step="0.01"
name="amount"
class="form-control mb-3"
placeholder="Deposit Amount"
required>

<textarea
name="notes"
class="form-control mb-3"
placeholder="Notes"></textarea>

<button
type="submit"
name="submit"
class="btn btn-success">
Deposit
</button>

</form>

</div>

</body>
</html>