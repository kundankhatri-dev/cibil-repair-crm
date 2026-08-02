<?php
// ============================================================
// CIBIL REPAIR CRM - Convert Lead to Customer/Sale API (FIXED)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json');

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

$lead_id = isset($input['lead_id']) ? intval($input['lead_id']) : 0;
$sale_amount = isset($input['sale_amount']) ? floatval($input['sale_amount']) : 0;
$sale_service = isset($input['sale_service']) ? trim($input['sale_service']) : '';
$sale_date = isset($input['sale_date']) ? trim($input['sale_date']) : date('Y-m-d');
$sale_status = isset($input['sale_status']) ? trim($input['sale_status']) : 'Completed';
$commission_amount = isset($input['commission_amount']) ? floatval($input['commission_amount']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$customer_status = isset($input['customer_status']) ? trim($input['customer_status']) : 'active';
$customer_city = isset($input['customer_city']) ? trim($input['customer_city']) : '';
$customer_phone = isset($input['customer_phone']) ? trim($input['customer_phone']) : '';
$partner_id = isset($input['partner_id']) ? intval($input['partner_id']) : 0;

// ============================================================
// VALIDATION
// ============================================================

if (!$lead_id) {
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    exit;
}

if ($sale_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid sale amount is required']);
    exit;
}

// ============================================================
// CHECK IF LEAD EXISTS
// ============================================================

$leadResult = mysqli_query($conn, "SELECT * FROM leads WHERE id = $lead_id");
if (!$leadResult || mysqli_num_rows($leadResult) == 0) {
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    exit;
}
$lead = mysqli_fetch_assoc($leadResult);

if ($lead['status'] === 'converted') {
    echo json_encode(['success' => false, 'error' => 'Lead is already converted']);
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
    // STEP 1: CREATE CUSTOMER
    // ============================================================

    $customer_name = $lead['name'];
    $customer_email = $lead['email'] ?? '';
    $customer_phone = $customer_phone ?: $lead['phone'] ?? '';
    $sale_service = $sale_service ?: $lead['service'] ?? 'Written Off';

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
                        service = '$sale_service'
                      WHERE id = $customer_id");
    } else {
        $customerAction = 'created';
        
        mysqli_query($conn, "INSERT INTO customers (name, email, phone, city, service, status, joined) 
                      VALUES ('$customer_name', '$customer_email', '$customer_phone', '$customer_city', '$sale_service', '$customer_status', NOW())");
        $customer_id = mysqli_insert_id($conn);
    }

    // ============================================================
    // STEP 2: CREATE SALE - Only use columns that exist
    // ============================================================

    $saleFields = [];
    $saleValues = [];

    // Required fields
    $saleFields[] = 'lead_id';
    $saleValues[] = $lead_id;
    
    $saleFields[] = 'customer_name';
    $saleValues[] = "'$customer_name'";
    
    $saleFields[] = 'customer_email';
    $saleValues[] = "'$customer_email'";
    
    $saleFields[] = 'customer_phone';
    $saleValues[] = "'$customer_phone'";
    
    $saleFields[] = 'service';
    $saleValues[] = "'$sale_service'";
    
    $saleFields[] = 'amount';
    $saleValues[] = $sale_amount;
    
    $saleFields[] = 'status';
    $saleValues[] = "'$sale_status'";
    
    $saleFields[] = 'sale_date';
    $saleValues[] = "'$sale_date'";

    // Optional fields (only if column exists)
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
        $saleValues[] = "'$notes'";
    }

    $insertSale = "INSERT INTO sales (" . implode(', ', $saleFields) . ") 
                   VALUES (" . implode(', ', $saleValues) . ")";
    mysqli_query($conn, $insertSale);
    $sale_id = mysqli_insert_id($conn);

    // ============================================================
    // STEP 3: UPDATE LEAD STATUS
    // ============================================================

    mysqli_query($conn, "UPDATE leads SET status = 'converted' WHERE id = $lead_id");

    // ============================================================
    // STEP 4: ADD TRANSACTION (if completed)
    // ============================================================

    if ($sale_status === 'Completed' || $sale_status === 'completed') {
        $tx_description = "Payment from $customer_name for $sale_service";
        mysqli_query($conn, "INSERT INTO transactions (date, description, amount, type, method) 
                             VALUES ('$sale_date', '$tx_description', $sale_amount, 'credit', 'Cash')");
        
        // Update wallet
        $walletCheck = mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1");
        if ($walletCheck && mysqli_num_rows($walletCheck) > 0) {
            $wallet = mysqli_fetch_assoc($walletCheck);
            $newBalance = ($wallet['balance'] ?? 0) + $sale_amount;
            mysqli_query($conn, "UPDATE wallet SET balance = $newBalance WHERE id = 1");
        } else {
            mysqli_query($conn, "INSERT INTO wallet (id, balance) VALUES (1, $sale_amount)");
        }
    }

    // ============================================================
    // STEP 5: LOG ACTIVITY
    // ============================================================

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Lead ID: $lead_id, Customer: $customer_name, Sale Amount: ₹$sale_amount, Sale ID: $sale_id";
    
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
                         VALUES ($user_id, '$user_name', 'Converted lead to sale', '$logDetails', '$ip', NOW())");

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
    
    $leadResult2 = mysqli_query($conn, "SELECT * FROM leads WHERE id = $lead_id");
    $updatedLead = mysqli_fetch_assoc($leadResult2);

    echo json_encode([
        'success' => true,
        'message' => 'Lead converted successfully',
        'data' => [
            'lead' => [
                'id' => $lead_id,
                'name' => $lead['name'],
                'status' => 'converted',
                'converted_at' => date('Y-m-d H:i:s')
            ],
            'customer' => $customer,
            'sale' => $sale,
            'customer_action' => $customerAction,
            'sale_amount' => $sale_amount,
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