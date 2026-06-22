<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'] ?? 0;

// 1. GET PAYMENT INFO BEFORE DELETE
$payment = $conn->query("
SELECT loan_id 
FROM loan_payments 
WHERE payment_id=$id
")->fetch_assoc();

if(!$payment){
    header("Location: index.php");
    exit();
}

$loan_id = $payment['loan_id'];

// 2. DELETE PAYMENT
$conn->query("
DELETE FROM loan_payments 
WHERE payment_id=$id
");

// 3. RE-CALCULATE LOAN TOTAL PAID (IMPORTANT FIX)
$total = $conn->query("
SELECT IFNULL(SUM(amount),0) as total
FROM loan_payments
WHERE loan_id=$loan_id
")->fetch_assoc()['total'];

$conn->query("
UPDATE loans 
SET total_paid='$total'
WHERE loan_id=$loan_id
");

// 4. (OPTIONAL) RESET INSTALLMENT STATUS SAFE WAY
$conn->query("
UPDATE loan_installments
SET status='pending', paid_date=NULL
WHERE loan_id=$loan_id
");

// 5. REDIRECT
header("Location: index.php");
exit();
?>