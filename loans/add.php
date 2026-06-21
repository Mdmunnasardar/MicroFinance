<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['submit'])){

    $loan_code = $_POST['loan_code'];
    $member_id = $_POST['member_id'];
    $branch_id = $_POST['branch_id'];

    $principal = $_POST['principal_amount'];
    $rate = $_POST['interest_rate'];

    $term = $_POST['loan_term_months'];
    $installment_type = $_POST['installment_type'];

    $disbursement_date = $_POST['disbursement_date'];
    $first_installment_date = $_POST['first_installment_date'];

    $purpose = $_POST['purpose'];

    $total_payable =
        $principal +
        ($principal * $rate / 100);

    $installment_amount =
        $total_payable / $term;

    $sql = "INSERT INTO loans(
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
        status,
        purpose
    )
    VALUES(
        '$loan_code',
        '$member_id',
        '$branch_id',
        '$principal',
        '$rate',
        'flat',
        '$term',
        '$installment_type',
        '$installment_amount',
        '$total_payable',
        0,
        '$disbursement_date',
        '$first_installment_date',
        'active',
        '$purpose'
    )";

    $conn->query($sql);

    header("Location:index.php");
}
?>