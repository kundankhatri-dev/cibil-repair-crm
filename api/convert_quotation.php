<?php
// ============================================================
// CIBIL REPAIR CRM - Convert Quotation to Sale API (FIXED)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'convert_quotation.php') {
//     http_response_code(403);
//     exit('Direct access forbidden.');
// }

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if (!in_array($role, ['admin', 'super_admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$quotation_id = isset($input['quotation_id']) ? intval($input['quotation_id']) : 0;
$sale_amount = isset($input['sale_amount']) ? floatval($input['sale_amount']) : 0;
$sale_service = isset($input['sale_service']) ? trim($input['sale_service']) : '';
$sale_date = isset($input['sale_date']) ? trim($input['sale_date']) : date('Y-m-d');
$sale_status = isset($input['sale_status']) ? trim($input['sale_status']) : 'Completed';
$commission_amount = isset($input['commission_amount']) ? floatval($input['commission_amount']) : 0;
$partner_id = isset($input['partner_id']) ? intval($input['partner_id']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$customer_status = isset($input['customer_status']) ? trim($input['customer_status']) : 'active';
$customer_city = isset($input['customer_city']) ? trim($input['customer_city']) : '';

// ============================================================
// VALIDATION
// ============================================================

if (!$quotation_id) {
    echo json_encode(['success' => false, 'error' => 'Quotation ID is required']);
    exit;
}

// ============================================================
// CHECK IF QUOTATION EXISTS
// ============================================================

$quotationResult = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $quotation_id");
if (!$quotationResult || mysqli_num_rows($quotationResult) == 0) {
    echo json_encode(['success' => false, 'error' => 'Quotation not found']);
    exit;
}
$quotation = mysqli_fetch_assoc($quotationResult);

if ($quotation['status'] === 'Converted') {
    echo json_encode(['success' => false, 'error' => 'Quotation is already converted']);
    exit;
}

// ============================================================
// GET SALES TABLE COLUMNS
// ============================================================

$salesColumns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM sales");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $salesColumns[] = $col['Field'];
    }
}

// ============================================================
// START TRANSACTION
// ============================================================

mysqli_begin_transaction($conn);

try {
    // ============================================================
    // STEP 1: CREATE/UPDATE CUSTOMER
    // ============================================================

    $customer_name = $quotation['customer'];
    $customer_email = $quotation['customer_email'] ?? '';
    $customer_phone = $quotation['customer_phone'] ?? '';
    $service = $sale_service ?: $quotation['service'];
    $amount = $sale_amount > 0 ? $sale_amount : (float)$quotation['amount'];

    // Check if customer exists
    $existingCustomer = null;
    if (!empty($customer_email)) {
        $checkResult = mysqli_query($conn, "SELECT id FROM customers WHERE email = '$customer_email'");
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $existingCustomer = mysqli_fetch_assoc($checkResult);
        }
    }

    if ($existingCustomer) {
        $customer_id = $existingCustomer['id'];
        $customerAction = 'updated';
        
        mysqli_query($conn, "UPDATE customers SET 
                        name = '$customer_name',
                        phone = '$customer_phone',
                        city = '$customer_city',
                        status = '$customer_status',
                        service = '$service'
                      WHERE id = $customer_id");
    } else {
        $customerAction = 'created';
        
        mysqli_query($conn, "INSERT INTO customers (name, email, phone, city, service, status, joined) 
                      VALUES ('$customer_name', '$customer_email', '$customer_phone', '$customer_city', '$service', '$customer_status', NOW())");
        $customer_id = mysqli_insert_id($conn);
    }

    // ============================================================
    // STEP 2: CREATE SALE - Only use columns that exist
    // ============================================================

    $saleFields = ['customer_name', 'customer_email', 'customer_phone', 'service', 'amount', 'status', 'sale_date'];
    $saleValues = [
        "'$customer_name'", 
        "'$customer_email'", 
        "'$customer_phone'", 
        "'$service'", 
        $amount, 
        "'$sale_status'", 
        "'$sale_date'"
    ];

    // Add optional fields if columns exist
    if (in_array('commission_amount', $salesColumns)) {
        $saleFields[] = 'commission_amount';
        $saleValues[] = $commission_amount;
    }
    
    if (in_array('partner_id', $salesColumns)) {
        $saleFields[] = 'partner_id';
        $saleValues[] = $partner_id;
    }
    
    if (in_array('notes', $salesColumns)) {
        $saleFields[] = 'notes';
        $saleValues[] = "'" . mysqli_real_escape_string($conn, $notes . " (Converted from quotation: {$quotation['quote_no']})") . "'";
    }

    $insertSale = "INSERT INTO sales (" . implode(', ', $saleFields) . ") 
                   VALUES (" . implode(', ', $saleValues) . ")";
    mysqli_query($conn, $insertSale);
    $sale_id = mysqli_insert_id($conn);

    // ============================================================
    // STEP 3: UPDATE QUOTATION STATUS
    // ============================================================

    mysqli_query($conn, "UPDATE quotations SET status = 'Converted' WHERE id = $quotation_id");

    // ============================================================
    // STEP 4: ADD TRANSACTION (if completed)
    // ============================================================

    if ($sale_status === 'Completed' || $sale_status === 'completed') {
        $tx_description = "Payment from $customer_name for $service (Quote: {$quotation['quote_no']})";
        mysqli_query($conn, "INSERT INTO transactions (date, description, amount, type, method) 
                             VALUES ('$sale_date', '$tx_description', $amount, 'credit', 'Cash')");
        
        // Update wallet
        $walletCheck = mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1");
        if ($walletCheck && mysqli_num_rows($walletCheck) > 0) {
            $wallet = mysqli_fetch_assoc($walletCheck);
            $newBalance = ($wallet['balance'] ?? 0) + $amount;
            mysqli_query($conn, "UPDATE wallet SET balance = $newBalance WHERE id = 1");
        } else {
            mysqli_query($conn, "INSERT INTO wallet (id, balance) VALUES (1, $amount)");
        }
    }

    // ============================================================
    // STEP 5: LOG ACTIVITY
    // ============================================================

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Quote: {$quotation['quote_no']}, Customer: $customer_name, Sale ID: $sale_id, Amount: ₹$amount";
    
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                         VALUES ($user_id, '$user_name', 'Converted quotation to sale', '$logDetails', '$ip', NOW())");

    // ============================================================
    // COMMIT TRANSACTION
    // ============================================================

    mysqli_commit($conn);

    // ============================================================
    // GET CONVERTED DATA
    // ============================================================

    $customerResult = mysqli_query($conn, "SELECT * FROM customers WHERE id = $customer_id");
    $customer = mysqli_fetch_assoc($customerResult);
    
    $saleResult = mysqli_query($conn, "SELECT * FROM sales WHERE id = $sale_id");
    $sale = mysqli_fetch_assoc($saleResult);
    
    $quotationResult2 = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $quotation_id");
    $updatedQuotation = mysqli_fetch_assoc($quotationResult2);

    // GST Calculation
    $gst_rate = 18;
    $gst_amount = $amount * $gst_rate / 100;
    $cgst_amount = $amount * ($gst_rate / 2) / 100;
    $sgst_amount = $amount * ($gst_rate / 2) / 100;
    $total_with_gst = $amount + $gst_amount;

    echo json_encode([
        'success' => true,
        'message' => 'Quotation converted to sale successfully',
        'data' => [
            'quotation' => [
                'id' => $quotation_id,
                'quote_no' => $quotation['quote_no'],
                'status' => 'Converted',
                'converted_at' => date('Y-m-d H:i:s')
            ],
            'customer' => $customer,
            'sale' => $sale,
            'customer_action' => $customerAction,
            'sale_amount' => $amount,
            'gst_details' => [
                'base_amount' => $amount,
                'gst_rate' => $gst_rate,
                'cgst_rate' => $gst_rate / 2,
                'sgst_rate' => $gst_rate / 2,
                'gst_amount' => $gst_amount,
                'cgst_amount' => $cgst_amount,
                'sgst_amount' => $sgst_amount,
                'total_with_gst' => $total_with_gst
            ],
            'commission_amount' => $commission_amount,
            'conversion_date' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'error' => 'Conversion failed: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>