<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$page_title = 'Add New Loan';

// Get members for dropdown
$members = $conn->query("SELECT member_id, full_name, member_code FROM members ORDER BY full_name");

// ======================
// PROCESS FORM
// ======================
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
        // Reducing balance - simple calculation
        $interest = $principal * ($rate / 100);
        $total_payable = $principal + $interest;
    }
    
    $installment_amount = $term > 0 ? $total_payable / $term : 0;
    $maturity_date = date('Y-m-d', strtotime($disbursement_date . " + $term months"));
    
    // Get branch from member
    $member = $conn->query("SELECT branch_id FROM members WHERE member_id = $member_id");
    $m = $member->fetch_assoc();
    $branch_id = $m['branch_id'] ?? 1;
    
    // Insert
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
        $loan_id = $conn->insert_id;
        
        // Generate installments
        generateInstallments($loan_id, $installment_amount, $first_installment_date, $term, $installment_type);
        
        header("Location: index.php?success=Loan created successfully");
        exit();
    } else {
        $error = "Error creating loan: " . $conn->error;
    }
}

function generateInstallments($loan_id, $amount, $start_date, $total_installments, $type) {
    global $conn;
    
    $interval = ($type == 'monthly') ? '+1 month' : '+1 week';
    $date = $start_date;
    
    for ($i = 1; $i <= $total_installments; $i++) {
        $sql = "INSERT INTO loan_installments (loan_id, installment_number, due_date, amount, status)
                VALUES ($loan_id, $i, '$date', $amount, 'pending')";
        $conn->query($sql);
        $date = date('Y-m-d', strtotime($date . " $interval"));
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<link rel="stylesheet" href="../assets/css/loans.css">

<div class="main-content">
    <div class="loan-form-container">
        <div class="loan-form-card">
            <div class="form-header">
                <h3><i class="fa-solid fa-plus-circle" style="color: #4f46e5;"></i> Create New Loan</h3>
                <p>Fill in the loan details below. All fields marked with <span style="color: #ef4444;">*</span> are required.</p>
            </div>
            
            <div class="form-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loanForm" onsubmit="return validateForm()">
                    <div class="form-grid">
                        <!-- Loan Code -->
                        <div class="form-group">
                            <label>Loan Code <span class="required">*</span></label>
                            <input type="text" name="loan_code" id="loan_code" class="form-control-custom" 
                                   placeholder="e.g., L-2026-001" required>
                            <span class="form-hint">Unique identifier for this loan</span>
                        </div>
                        
                        <!-- Member -->
                        <div class="form-group">
                            <label>Member <span class="required">*</span></label>
                            <select name="member_id" id="member_id" class="form-control-custom" required>
                                <option value="">Select Member</option>
                                <?php while($m = $members->fetch_assoc()): ?>
                                    <option value="<?php echo $m['member_id']; ?>">
                                        <?php echo htmlspecialchars($m['full_name']); ?> (<?php echo $m['member_code']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Principal Amount -->
                        <div class="form-group">
                            <label>Principal Amount <span class="required">*</span></label>
                            <input type="number" name="principal_amount" id="principal" class="form-control-custom" 
                                   placeholder="0.00" step="0.01" required oninput="calculateSummary()">
                        </div>
                        
                        <!-- Interest Rate -->
                        <div class="form-group">
                            <label>Interest Rate (%) <span class="required">*</span></label>
                            <input type="number" name="interest_rate" id="interest_rate" class="form-control-custom" 
                                   placeholder="12.5" step="0.01" required oninput="calculateSummary()">
                        </div>
                        
                        <!-- Interest Type -->
                        <div class="form-group">
                            <label>Interest Type <span class="required">*</span></label>
                            <select name="interest_type" id="interest_type" class="form-control-custom" onchange="calculateSummary()">
                                <option value="flat">Flat Rate</option>
                                <option value="reducing_balance">Reducing Balance</option>
                            </select>
                        </div>
                        
                        <!-- Loan Term -->
                        <div class="form-group">
                            <label>Loan Term (Months) <span class="required">*</span></label>
                            <input type="number" name="loan_term_months" id="loan_term" class="form-control-custom" 
                                   placeholder="12" min="1" required oninput="calculateSummary()">
                        </div>
                        
                        <!-- Installment Type -->
                        <div class="form-group">
                            <label>Installment Type <span class="required">*</span></label>
                            <select name="installment_type" id="installment_type" class="form-control-custom">
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                        
                        <!-- Disbursement Date -->
                        <div class="form-group">
                            <label>Disbursement Date <span class="required">*</span></label>
                            <input type="date" name="disbursement_date" id="disbursement_date" 
                                   class="form-control-custom" required>
                        </div>
                        
                        <!-- First Installment Date -->
                        <div class="form-group">
                            <label>First Installment Date <span class="required">*</span></label>
                            <input type="date" name="first_installment_date" id="first_installment_date" 
                                   class="form-control-custom" required>
                        </div>
                        
                        <!-- Purpose -->
                        <div class="form-group full-width">
                            <label>Loan Purpose</label>
                            <input type="text" name="purpose" class="form-control-custom" 
                                   placeholder="e.g., Business expansion, Education fees, Emergency medical">
                        </div>
                    </div>
                    
                    <!-- Summary Box -->
                    <div class="loan-summary-box" id="summaryBox">
                        <div class="summary-item">
                            <div class="label">Total Payable</div>
                            <div class="value primary" id="totalPayable">$0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Installment Amount</div>
                            <div class="value success" id="installmentAmount">$0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Interest Amount</div>
                            <div class="value" id="interestAmount">$0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Maturity Date</div>
                            <div class="value" id="maturityDate">-</div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn-cancel">
                            <i class="fa-solid fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="submit" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Create Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calculateSummary() {
    const principal = parseFloat(document.getElementById('principal').value) || 0;
    const rate = parseFloat(document.getElementById('interest_rate').value) || 0;
    const term = parseInt(document.getElementById('loan_term').value) || 0;
    const interestType = document.getElementById('interest_type').value;
    const disbursement = document.getElementById('disbursement_date').value;
    
    if (principal > 0 && term > 0) {
        let interest, totalPayable;
        
        if (interestType === 'flat') {
            interest = principal * (rate / 100);
        } else {
            interest = principal * (rate / 100);
        }
        
        totalPayable = principal + interest;
        const installmentAmount = totalPayable / term;
        
        document.getElementById('totalPayable').textContent = '$' + totalPayable.toFixed(2);
        document.getElementById('installmentAmount').textContent = '$' + installmentAmount.toFixed(2);
        document.getElementById('interestAmount').textContent = '$' + interest.toFixed(2);
        
        if (disbursement) {
            const date = new Date(disbursement);
            date.setMonth(date.getMonth() + term);
            document.getElementById('maturityDate').textContent = date.toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
        }
    } else {
        document.getElementById('totalPayable').textContent = '$0.00';
        document.getElementById('installmentAmount').textContent = '$0.00';
        document.getElementById('interestAmount').textContent = '$0.00';
    }
}

function validateForm() {
    const member = document.getElementById('member_id').value;
    if (!member) {
        alert('Please select a member');
        return false;
    }
    return true;
}

// Set default dates
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('disbursement_date').value = today;
    
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('first_installment_date').value = nextMonth.toISOString().split('T')[0];
});
</script>

<?php include "../includes/footer.php"; ?>