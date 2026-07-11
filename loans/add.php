<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Add New Loan';

// Get members
$members = $conn->query("SELECT member_id, full_name, member_code FROM members ORDER BY full_name");

if (isset($_POST['submit'])) {
    $loan_code = sanitize($_POST['loan_code']);
    $member_id = (int)$_POST['member_id'];
    $principal = (float)$_POST['principal_amount'];
    $rate = (float)$_POST['interest_rate'];
    $interest_type = sanitize($_POST['interest_type']);
    $term = (int)$_POST['loan_term_months'];
    $installment_type = sanitize($_POST['installment_type']);
    $disbursement_date = sanitize($_POST['disbursement_date']);
    $first_installment_date = sanitize($_POST['first_installment_date']);
    $purpose = sanitize($_POST['purpose']);
    
    // Calculate
    if ($interest_type == 'flat') {
        $interest = $principal * ($rate / 100);
        $total_payable = $principal + $interest;
    } else {
        $interest = $principal * ($rate / 100);
        $total_payable = $principal + $interest;
    }
    
    $installment_amount = $term > 0 ? $total_payable / $term : 0;
    $maturity_date = date('Y-m-d', strtotime($disbursement_date . " + $term months"));
    
    // Get branch
    $member = $conn->query("SELECT branch_id FROM members WHERE member_id = $member_id");
    $m = $member->fetch_assoc();
    $branch_id = $m['branch_id'] ?? 1;
    
    $sql = "INSERT INTO loans (
        loan_code, member_id, branch_id, principal_amount, interest_rate,
        interest_type, loan_term_months, installment_type, installment_amount,
        total_payable, total_paid, disbursement_date, first_installment_date,
        maturity_date, status, purpose
    ) VALUES (
        '$loan_code', $member_id, $branch_id, $principal, $rate,
        '$interest_type', $term, '$installment_type', $installment_amount,
        $total_payable, 0, '$disbursement_date', '$first_installment_date',
        '$maturity_date', 'active', '$purpose'
    )";
    
    if ($conn->query($sql)) {
        header("Location: index.php?success=Loan created successfully");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">

<div class="main-content">
    <div class="loan-form-container" style="max-width: 800px; margin: 0 auto;">
        <div class="loan-form-card" style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.04); overflow: hidden;">
            <div class="form-header" style="padding: 24px 32px; background: linear-gradient(135deg, #f8fafc, #eef2ff); border-bottom: 1px solid #e2e8f0;">
                <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">
                    <i class="fa-solid fa-plus-circle" style="color: #4f46e5;"></i> Create New Loan
                </h3>
                <p style="font-size: 14px; color: #64748b; margin: 4px 0 0;">Fill in the loan details below</p>
            </div>
            
            <div class="form-body" style="padding: 32px;">
                <?php if (isset($error)): ?>
                    <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 20px;">