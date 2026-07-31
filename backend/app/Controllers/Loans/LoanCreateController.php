<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

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

    if ($interest_type == 'flat') {
        $interest = ($principal * $rate / 100);
        $total_payable = $principal + $interest;
    } else {
        $total_payable = $principal + ($principal * $rate / 100);
    }

    $installment_amount = $total_payable / $term;
    $maturity_date = date('Y-m-d', strtotime($disbursement_date . " + $term months"));

    $member = $conn->query("SELECT branch_id FROM members WHERE member_id=$member_id");
    $m = $member->fetch_assoc();
    $branch_id = $m['branch_id'];

    $sql = "INSERT INTO loans (
        loan_code, member_id, branch_id, principal_amount, interest_rate,
        interest_type, loan_term_months, installment_type, installment_amount,
        total_payable, total_paid, disbursement_date, first_installment_date,
        maturity_date, status, purpose
    ) VALUES (
        '$loan_code', '$member_id', '$branch_id', '$principal', '$rate',
        '$interest_type', '$term', '$installment_type', '$installment_amount',
        '$total_payable', 0, '$disbursement_date', '$first_installment_date',
        '$maturity_date', 'active', '$purpose'
    )";

    $conn->query($sql);
    header("Location: index.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Loan</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0B1120 0%, #111B30 40%, #162040 70%, #111B30 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            color: #FFFFFF;
        }
        .container { max-width: 800px; margin: 0 auto; }
        
        .form-wrapper {
            background: rgba(20, 35, 60, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        .form-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4F8CFF, #00D4AA, #4F8CFF);
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .form-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .form-title .icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #4F8CFF, #2D6CD4);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #FFFFFF;
            box-shadow: 0 0 40px rgba(79, 140, 255, 0.3);
        }
        .form-title span {
            background: linear-gradient(135deg, #4F8CFF, #7DB0FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-control {
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 2px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 100%;
            color: #FFFFFF;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus {
            outline: none;
            border-color: #4F8CFF;
            box-shadow: 0 0 0 4px rgba(79, 140, 255, 0.1);
            background: rgba(255, 255, 255, 0.06);
        }
        .form-control::placeholder { color: #8AA0C8; }
        .form-control option { background: #111B30; color: #FFFFFF; }
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238AA0C8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
            cursor: pointer;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #C8D6E8;
            margin-bottom: 8px;
            display: block;
        }
        .form-label i { margin-right: 6px; color: #7DB0FF; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }
        .btn-success {
            background: linear-gradient(135deg, #00E676, #00C853);
            color: #FFFFFF;
            box-shadow: 0 4px 20px rgba(0, 230, 118, 0.3);
            margin-top: 8px;
            padding: 14px;
            font-size: 16px;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0, 230, 118, 0.4);
            color: #FFFFFF;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #C8D6E8;
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-top: 12px;
            padding: 12px;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
        }
        
        @media (max-width: 768px) {
            .form-wrapper { padding: 24px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .form-title { font-size: 24px; }
        }
        
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
            animation: float 8s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .orb-1 { width: 400px; height: 400px; background: rgba(79, 140, 255, 0.15); top: -150px; right: -150px; }
        .orb-2 { width: 300px; height: 300px; background: rgba(0, 212, 170, 0.12); bottom: -50px; left: -50px; animation-delay: -3s; }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="container">
    <div class="form-wrapper">
        <div class="form-title">
            <div class="icon"><i class="fas fa-plus-circle"></i></div>
            <span>Create New Loan</span>
        </div>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-barcode"></i> Loan Code</label>
                    <input type="text" name="loan_code" class="form-control" placeholder="e.g., L001" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Select Member</label>
                    <select name="member_id" class="form-control" required>
                        <option value="">Select Member</option>
                        <?php
                        $members = $conn->query("SELECT * FROM members ORDER BY full_name");
                        while($m = $members->fetch_assoc()) {
                            echo '<option value="'.$m['member_id'].'">'.$m['full_name'].' ('.$m['member_code'].')</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-money-bill-wave"></i> Principal Amount</label>
                    <input type="number" name="principal_amount" class="form-control" placeholder="Enter principal amount" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-percent"></i> Interest Rate</label>
                    <input type="number" step="0.01" name="interest_rate" class="form-control" placeholder="Enter interest rate" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calculator"></i> Interest Type</label>
                    <select name="interest_type" class="form-control">
                        <option value="flat">Flat Rate</option>
                        <option value="reducing_balance">Reducing Balance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-clock"></i> Loan Term (Months)</label>
                    <input type="number" name="loan_term_months" class="form-control" placeholder="e.g., 12" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-alt"></i> Installment Type</label>
                    <select name="installment_type" class="form-control">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-day"></i> Disbursement Date</label>
                    <input type="date" name="disbursement_date" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-check"></i> First Installment Date</label>
                    <input type="date" name="first_installment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-info-circle"></i> Loan Purpose</label>
                    <input type="text" name="purpose" class="form-control" placeholder="Enter loan purpose">
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Create Loan
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Loan List
            </a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>