<?php
// api/partner/request_payout.php
// Partner Request Payout API - Submit a new payout request

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== ENSURE PAYOUTS TABLE EXISTS ==========
$payoutTable = 'partner_payouts';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$payoutTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $payoutTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        request_date DATETIME NOT NULL,
        status ENUM('pending', 'approved', 'paid', 'rejected') DEFAULT 'pending',
        paid_date DATETIME DEFAULT NULL,
        transaction_id VARCHAR(100) DEFAULT NULL,
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status)
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;

// ========== VALIDATE AMOUNT ==========
if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid amount. Amount must be greater than 0']);
    exit;
}

// Limit to 2 decimal places
$amount = round($amount, 2);

// Minimum payout amount
$min_payout = 500;
if ($amount < $min_payout) {
    echo json_encode(['success' => false, 'error' => "Minimum payout amount is ₹" . number_format($min_payout, 2)]);
    exit;
}

// Maximum payout amount (safety limit)
$max_payout = 100000;
if ($amount > $max_payout) {
    echo json_encode(['success' => false, 'error' => "Maximum payout amount per request is ₹" . number_format($max_payout, 2)]);
    exit;
}

// ========== CHECK AVAILABLE BALANCE ==========
$available_balance = 0;
$balance_data = null;

// Try partners table first
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (mysqli_num_rows($checkPartnersTable) > 0) {
    // Try with user_id column
    $balance_stmt = mysqli_prepare($conn, "SELECT pending_payout, total_commission FROM partners WHERE user_id = ?");
    if (!$balance_stmt) {
        // Try with id column
        $balance_stmt = mysqli_prepare($conn, "SELECT pending_payout, total_commission FROM partners WHERE id = ?");
    }
    
    if ($balance_stmt) {
        mysqli_stmt_bind_param($balance_stmt, "i", $partner_id);
        mysqli_stmt_execute($balance_stmt);
        $balance_result = mysqli_stmt_get_result($balance_stmt);
        $balance_data = mysqli_fetch_assoc($balance_result);
        $available_balance = $balance_data ? (float)($balance_data['pending_payout'] ?? 0) : 0;
        mysqli_stmt_close($balance_stmt);
    }
}

// If no balance found, calculate from leads
if ($available_balance == 0) {
    // Determine leads table
    $leadsTable = 'leads';
    $checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
    if (mysqli_num_rows($checkLeadsTable) > 0) {
        $leadsTable = 'partner_leads';
    }
    
    $calc_stmt = mysqli_prepare($conn, "SELECT SUM(commission_amount) as total FROM $leadsTable WHERE partner_id = ? AND status = 'converted' AND paid_status != 'paid'");
    if ($calc_stmt) {
        mysqli_stmt_bind_param($calc_stmt, "i", $partner_id);
        mysqli_stmt_execute($calc_stmt);
        $calc_result = mysqli_stmt_get_result($calc_stmt);
        $calc_data = mysqli_fetch_assoc($calc_result);
        $available_balance = (float)($calc_data['total'] ?? 0);
        mysqli_stmt_close($calc_stmt);
    }
}

// Check if amount exceeds available balance
if ($amount > $available_balance) {
    echo json_encode([
        'success' => false, 
        'error' => "Insufficient balance. Available: ₹" . number_format($available_balance, 2),
        'available_balance' => $available_balance,
        'requested_amount' => $amount
    ]);
    exit;
}

// ========== CHECK IF BANK DETAILS EXIST ==========
$has_bank_details = false;
$checkPartnersTable2 = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (mysqli_num_rows($checkPartnersTable2) > 0) {
    $bank_stmt = mysqli_prepare($conn, "SELECT bank_name, account_number, ifsc_code FROM partners WHERE user_id = ? AND bank_name IS NOT NULL AND bank_name != '' AND account_number IS NOT NULL AND account_number != ''");
    if (!$bank_stmt) {
        $bank_stmt = mysqli_prepare($conn, "SELECT bank_name, account_number, ifsc_code FROM partners WHERE id = ? AND bank_name IS NOT NULL AND bank_name != '' AND account_number IS NOT NULL AND account_number != ''");
    }
    
    if ($bank_stmt) {
        mysqli_stmt_bind_param($bank_stmt, "i", $partner_id);
        mysqli_stmt_execute($bank_stmt);
        $bank_result = mysqli_stmt_get_result($bank_stmt);
        $has_bank_details = mysqli_num_rows($bank_result) > 0;
        mysqli_stmt_close($bank_stmt);
    }
}

if (!$has_bank_details) {
    echo json_encode([
        'success' => false, 
        'error' => 'Please add your bank details in Profile > Bank Details tab before requesting payout'
    ]);
    exit;
}

// ========== CHECK FOR EXISTING PENDING REQUEST ==========
$check_stmt = mysqli_prepare($conn, "SELECT id, amount, request_date FROM $payoutTable WHERE partner_id = ? AND status = 'pending'");
if ($check_stmt) {
    mysqli_stmt_bind_param($check_stmt, "i", $partner_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $pending_count = mysqli_num_rows($check_result);
    
    if ($pending_count > 0) {
        $pending_data = mysqli_fetch_assoc($check_result);
        echo json_encode([
            'success' => false, 
            'error' => 'You already have a pending payout request. Please wait for it to be processed.',
            'pending_id' => $pending_data['id'],
            'pending_amount' => $pending_data['amount']
        ]);
        exit;
    }
    mysqli_stmt_close($check_stmt);
}

// ========== INSERT PAYOUT REQUEST ==========
$insert_stmt = mysqli_prepare($conn, "INSERT INTO $payoutTable (partner_id, amount, request_date, status) VALUES (?, ?, NOW(), 'pending')");
if (!$insert_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($insert_stmt, "id", $partner_id, $amount);

if (mysqli_stmt_execute($insert_stmt)) {
    $payout_id = mysqli_insert_id($conn);
    
    // Update pending_payout in partners table
    $checkPartnersTable3 = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
    if (mysqli_num_rows($checkPartnersTable3) > 0) {
        // Try with user_id
        $update_stmt = mysqli_prepare($conn, "UPDATE partners SET pending_payout = pending_payout - ? WHERE user_id = ?");
        if (!$update_stmt) {
            // Try with id
            $update_stmt = mysqli_prepare($conn, "UPDATE partners SET pending_payout = pending_payout - ? WHERE id = ?");
        }
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "di", $amount, $partner_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
    }
    
    // Update leads table to mark commission as requested
    $leadsTable = 'leads';
    $checkLeadsTable2 = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
    if (mysqli_num_rows($checkLeadsTable2) > 0) {
        $leadsTable = 'partner_leads';
    }
    
    // Check if paid_status column exists
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable LIKE 'paid_status'");
    if (mysqli_num_rows($checkColumn) > 0) {
        $update_leads = mysqli_prepare($conn, "UPDATE $leadsTable SET paid_status = 'requested' WHERE partner_id = ? AND status = 'converted' AND paid_status IS NULL");
        if ($update_leads) {
            mysqli_stmt_bind_param($update_leads, "i", $partner_id);
            mysqli_stmt_execute($update_leads);
            mysqli_stmt_close($update_leads);
        }
    }
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'payout_request', ?, NOW())");
        if ($log_stmt) {
            $description = "Requested payout of ₹" . number_format($amount, 2);
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payout request submitted successfully',
        'payout_id' => $payout_id,
        'payout_id_formatted' => 'PAY' . str_pad($payout_id, 6, '0', STR_PAD_LEFT),
        'amount' => $amount,
        'amount_formatted' => '₹' . number_format($amount, 2),
        'available_balance_after' => $available_balance - $amount
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

// ========== CLEAN UP ==========
if (isset($insert_stmt)) mysqli_stmt_close($insert_stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>