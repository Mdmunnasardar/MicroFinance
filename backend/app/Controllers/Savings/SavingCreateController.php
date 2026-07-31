<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['submit'])){

    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $note = $_POST['note'];

    // insert savings
    $conn->query("
        INSERT INTO savings (member_id, balance, date, note)
        VALUES ('$member_id','$amount','$date','$note')
    ");

    header("Location: index.php");
    exit();
}
?>

<h3>Add Savings</h3>

<form method="POST">

<select name="member_id" class="form-control mb-2" required>
<option value="">Select Member</option>

<?php
$m = $conn->query("SELECT member_id, full_name FROM members");
while($row=$m->fetch_assoc()){
?>
<option value="<?php echo $row['member_id']; ?>">
<?php echo $row['full_name']; ?>
</option>
<?php } ?>

</select>

<input type="number" name="amount" class="form-control mb-2" placeholder="Amount" required>

<input type="date" name="date" class="form-control mb-2" required>

<input type="text" name="note" class="form-control mb-2" placeholder="Note">

<button class="btn btn-success" name="submit">Add Deposit</button>

</form>