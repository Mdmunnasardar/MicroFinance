<?php
session_start();
include "../config/db.php";

$id = $_GET['id'];

$data = $conn->query("SELECT * FROM loan_payments WHERE payment_id=$id")->fetch_assoc();

if(isset($_POST['update'])){

    $amount = $_POST['amount'];
    $note = $_POST['note'];

    $conn->query("
        UPDATE loan_payments 
        SET amount='$amount', note='$note'
        WHERE payment_id=$id
    ");

    header("Location: payment_list.php");
}
?>

<form method="POST" class="container mt-4">

<h3>Edit Payment</h3>

<input type="number" name="amount" value="<?php echo $data['amount']; ?>" class="form-control mb-2">

<input type="text" name="note" value="<?php echo $data['note']; ?>" class="form-control mb-2">

<button class="btn btn-success" name="update">Update</button>

</form>