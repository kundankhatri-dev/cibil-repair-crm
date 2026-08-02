<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Sale API
// Endpoint: /api/get_sale.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

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

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'sales'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Sales table not found']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Sale ID is required']);
    exit;
}

// ============================================================
// GET SALE
// ============================================================

$sql = "SELECT * FROM sales WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sale = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$sale) {
    echo json_encode(['success' => false, 'error' => 'Sale not found']);
    exit;
}

// ============================================================
// GET LEAD DETAILS (if lead_id exists)
// ============================================================

$lead = null;
if (!empty($sale['lead_id'])) {
    $lSql = "SELECT id, name, phone, email, status, priority, source FROM leads WHERE id = ?";
    $lStmt = mysqli_prepare($conn, $lSql);
    mysqli_stmt_bind_param($lStmt, 'i', $sale['lead_id']);
    mysqli_stmt_execute($lStmt);
    $lResult = mysqli_stmt_get_result($lStmt);
    $lead = mysqli_fetch_assoc($lResult);
    mysqli_stmt_close($lStmt);
}

// ============================================================
// GET PARTNER DETAILS (if partner_id exists)
// ============================================================

$partner = null;
if (!empty($sale['partner_id'])) {
    $pSql = "SELECT id, name, email, phone, company_name, commission_rate FROM partners WHERE id = ?";
    $pStmt = mysqli_prepare($conn, $pSql);
    mysqli_stmt_bind_param($pStmt, 'i', $sale['partner_id']);
    mysqli_stmt_execute($pStmt);
    $pResult = mysqli_stmt_get_result($pStmt);
    $partner = mysqli_fetch_assoc($pResult);
    mysqli_stmt_close($pStmt);
}

// ============================================================
// GET CUSTOMER DETAILS
// ============================================================

$customer = null;
if (!empty($sale['customer_email'])) {
    $cSql = "SELECT id, name, email, phone, city, status FROM customers WHERE email = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 's', $sale['customer_email']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $customer = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
} elseif (!empty($sale['customer_phone'])) {
    $cSql = "SELECT id, name, email, phone, city, status FROM customers WHERE phone = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 's', $sale['customer_phone']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $customer = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
}

// ============================================================
// GET TRANSACTIONS
// ============================================================

$transactions = [];
if (!empty($sale['customer_name'])) {
    $tSql = "SELECT id, date, description, amount, type, method FROM transactions WHERE description LIKE ? ORDER BY date DESC LIMIT 10";
    $searchTerm = "%" . $sale['customer_name'] . "%";
    $tStmt = mysqli_prepare($conn, $tSql);
    mysqli_stmt_bind_param($tStmt, 's', $searchTerm);
    mysqli_stmt_execute($tStmt);
    $tResult = mysqli_stmt_get_result($tStmt);
    while ($row = mysqli_fetch_assoc($tResult)) {
        $transactions[] = $row;
    }
    mysqli_stmt_close($tStmt);
}

// ============================================================
// CALCULATE STATISTICS
// ============================================================

$customerSales = [];
if (!empty($sale['customer_name'])) {
    $csSql = "SELECT id, amount, status, sale_date FROM sales WHERE customer_name = ? ORDER BY sale_date DESC";
    $csStmt = mysqli_prepare($conn, $csSql);
    mysqli_stmt_bind_param($csStmt, 's', $sale['customer_name']);
    mysqli_stmt_execute($csStmt);
    $csResult = mysqli_stmt_get_result($csStmt);
    while ($row = mysqli_fetch_assoc($csResult)) {
        $customerSales[] = $row;
    }
    mysqli_stmt_close($csStmt);
}

$totalCustomerSales = 0;
$completedCustomerSales = 0;
foreach ($customerSales as $cs) {
    $totalCustomerSales += floatval($cs['amount']);
    if ($cs['status'] === 'Completed') {
        $completedCustomerSales += floatval($cs['amount']);
    }
}

$paymentStatus = 'Unpaid';
if ($sale['status'] === 'Completed') {
    $paymentStatus = 'Paid';
} elseif ($sale['status'] === 'Pending') {
    $paymentStatus = 'Pending';
}

$daysSinceSale = 0;
if (!empty($sale['sale_date'])) {
    $saleDate = new DateTime($sale['sale_date']);
    $now = new DateTime();
    $interval = $saleDate->diff($now);
    $daysSinceSale = $interval->days;
}

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Sale retrieved successfully',
    'data' => [
        'id' => intval($sale['id']),
        'lead_id' => isset($sale['lead_id']) ? intval($sale['lead_id']) : null,
        'lead' => $lead,
        'customer_name' => $sale['customer_name'],
        'customer_email' => $sale['customer_email'] ?? '',
        'customer_phone' => $sale['customer_phone'] ?? '',
        'customer' => $customer,
        'service' => $sale['service'] ?? 'Written Off',
        'amount' => floatval($sale['amount']),
        'commission_amount' => floatval($sale['commission_amount'] ?? 0),
        'partner_id' => isset($sale['partner_id']) ? intval($sale['partner_id']) : null,
        'partner' => $partner,
        'status' => $sale['status'] ?? 'Pending',
        'sale_date' => $sale['sale_date'] ?? null,
        'notes' => $sale['notes'] ?? '',
        'created_at' => $sale['created_at'] ?? null,
        'updated_at' => $sale['updated_at'] ?? null,
        'stats' => [
            'payment_status' => $paymentStatus,
            'days_since_sale' => $daysSinceSale,
            'total_customer_sales' => count($customerSales),
            'total_customer_revenue' => $totalCustomerSales,
            'completed_customer_revenue' => $completedCustomerSales,
            'commission_rate' => $partner ? floatval($partner['commission_rate']) : 0
        ],
        'transactions' => $transactions,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>