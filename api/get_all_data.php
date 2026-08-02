<?php
// ============================================================
// CIBIL REPAIR CRM - Unified API Endpoint (FIXED)
// ============================================================

// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET TABLE COLUMNS (Helper function)
// ============================================================

function getTableColumns($conn, $tableName) {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $tableName");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        mysqli_free_result($result);
    }
    return $columns;
}

// ============================================================
// GET DATA
// ============================================================

try {
    $response = [];
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // ============================================================
    // 1. BANKS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, contact, email, phone, status, created_at FROM banks ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['banks'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['banks'][] = $row;
    }

    // ============================================================
    // 2. CUSTOMERS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, phone, city, service, status, DATE(created_at) as joined_date FROM customers ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['customers'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['customers'][] = $row;
    }

    // ============================================================
    // 3. PARTNERS (FIXED - Added user_id)
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, phone, company_name, location, owner, commission_rate, total_leads, total_converted, tier_level, kyc_status, status, user_id, created_at FROM partners ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['partners'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['partners'][] = $row;
    }

    // ============================================================
    // 4. LEADS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, partner_id, name, phone, email, service, status, priority, source, amount, message, created_at FROM leads ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['leads'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['leads'][] = $row;
    }

    // ============================================================
    // 5. SALES
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, lead_id, customer_name, customer_email, customer_phone, service, amount, commission_amount, status, sale_date, created_at FROM sales ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['sales'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['sales'][] = $row;
    }

    // ============================================================
    // 6. QUOTATIONS - FIXED (Check columns first)
    // ============================================================
    $quotationCols = getTableColumns($conn, 'quotations');
    $quotationSelect = "id, quote_no, customer_name, customer_email, customer_phone, service, amount, status, valid_until, notes, created_at";
    if (in_array('gst_amount', $quotationCols)) {
        $quotationSelect .= ", gst_amount";
    }
    if (in_array('total_amount', $quotationCols)) {
        $quotationSelect .= ", total_amount";
    }
    $result = mysqli_query($conn, "SELECT $quotationSelect FROM quotations ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['quotations'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['quotations'][] = $row;
    }

    // ============================================================
    // 7. EXPENSES
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, category, description, amount, date, created_at FROM expenses ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['expenses'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['expenses'][] = $row;
    }

    // ============================================================
    // 8. TRANSACTIONS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, date, description, amount, type, method, balance_after, created_at FROM transactions ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['transactions'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['transactions'][] = $row;
    }

    // ============================================================
    // 9. CUSTOMER REQUESTS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, phone, service, date, status, created_at FROM customer_requests ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['customer_requests'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['customer_requests'][] = $row;
    }

    // ============================================================
    // 10. REGISTRATION CODES
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, code, role, assigned_to_email, is_used, expires_at, created_at FROM registration_codes ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['registration_codes'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['registration_codes'][] = $row;
    }

    // ============================================================
    // 11. ACTIVITY LOGS (Recent 20)
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, user_name, action, details, ip_address, created_at FROM activity_logs ORDER BY id DESC LIMIT 20");
    $response['activity_logs'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['activity_logs'][] = $row;
    }

    // ============================================================
    // 12. SETTINGS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, setting_key, setting_value, updated_at FROM settings");
    $response['settings'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['settings'][] = $row;
    }

    // ============================================================
    // 13. USERS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, phone, role, status, created_at FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['users'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['users'][] = $row;
    }

    // ============================================================
    // 14. POSTERS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, filename, original_name, file_path, file_size, created_at FROM posters WHERE deleted_at IS NULL ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['posters'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['posters'][] = $row;
    }

    // ============================================================
    // 15. SERVICES
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, description, price, category, duration, icon, status, is_featured, is_popular, created_at FROM services ORDER BY id DESC");
    $response['services'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['services'][] = $row;
    }

    // ============================================================
    // 16. REVIEWS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, rating, review_text, status, created_at FROM reviews ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $response['reviews'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['reviews'][] = $row;
    }

    // ============================================================
    // 17. PARTNER APPLICATIONS
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, name, email, phone, partner_type, status, notes, created_at FROM partner_applications ORDER BY CASE WHEN status = 'pending' THEN 0 ELSE 1 END, id DESC LIMIT $limit OFFSET $offset");
    $response['partner_applications'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $response['partner_applications'][] = $row;
    }

    // ============================================================
    // 18. WALLET
    // ============================================================
    $result = mysqli_query($conn, "SELECT id, user_id, balance, updated_at FROM wallet WHERE user_id = $userId");
    $wallet = mysqli_fetch_assoc($result);
    if (!$wallet) {
        mysqli_query($conn, "INSERT INTO wallet (user_id, balance) VALUES ($userId, 0)");
        $wallet = ['id' => mysqli_insert_id($conn), 'user_id' => $userId, 'balance' => 0, 'updated_at' => date('Y-m-d H:i:s')];
    }
    $response['wallet'] = $wallet;

    // ============================================================
    // CALCULATE STATISTICS
    // ============================================================

    $sales = $response['sales'] ?? [];
    $expenses = $response['expenses'] ?? [];
    $posters = $response['posters'] ?? [];
    $leads = $response['leads'] ?? [];
    $customers = $response['customers'] ?? [];
    $partners = $response['partners'] ?? [];
    $banks = $response['banks'] ?? [];

    $totalRev = array_sum(array_column($sales, 'amount'));
    $totalExp = array_sum(array_column($expenses, 'amount'));

    $response['stats'] = [
        'total_banks' => count($banks),
        'total_customers' => count($customers),
        'total_partners' => count($partners),
        'total_leads' => count($leads),
        'total_posters' => count($posters),
        'total_revenue' => $totalRev,
        'total_expenses' => $totalExp,
        'net_revenue' => $totalRev - $totalExp,
        'wallet_balance' => (float)$wallet['balance']
    ];

    // ============================================================
    // RESPONSE
    // ============================================================

    echo json_encode([
        'success' => true,
        'message' => 'Data retrieved successfully',
        'data' => $response,
        'user' => [
            'id' => $userId,
            'role' => $userRole
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error in get_all_data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching data: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>