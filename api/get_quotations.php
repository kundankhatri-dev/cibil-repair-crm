<?php
// ============================================================
// CIBIL REPAIR CRM - Quotations API
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'quotations'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Quotations table not found']);
    exit;
}

// ============================================================
// GET SINGLE QUOTATION
// ============================================================

if ($id > 0) {
    $sql = "SELECT * FROM quotations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $quotation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$quotation) {
        echo json_encode(['success' => false, 'error' => 'Quotation not found']);
        mysqli_close($conn);
        exit;
    }

    $amount = (float)($quotation['amount'] ?? 0);
    $gstAmount = $amount * GST_RATE / 100;
    $cgstAmount = $amount * GST_CGST / 100;
    $sgstAmount = $amount * GST_SGST / 100;

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => intval($quotation['id']),
            'quote_no' => $quotation['quote_no'] ?? '',
            'customer' => $quotation['customer'] ?? '',
            'customer_email' => $quotation['customer_email'] ?? '',
            'customer_phone' => $quotation['customer_phone'] ?? '',
            'service' => $quotation['service'] ?? '',
            'amount' => round($amount, 2),
            'gst' => [
                'gst_rate' => GST_RATE,
                'cgst_rate' => GST_CGST,
                'sgst_rate' => GST_SGST,
                'cgst_amount' => round($cgstAmount, 2),
                'sgst_amount' => round($sgstAmount, 2),
                'total_gst' => round($gstAmount, 2),
                'total_with_gst' => round($amount + $gstAmount, 2)
            ],
            'total_with_gst' => round($amount + $gstAmount, 2),
            'status' => $quotation['status'] ?? 'Draft',
            'date' => $quotation['date'] ?? null,
            'validity' => $quotation['validity'] ?? null,
            'notes' => $quotation['notes'] ?? '',
            'created_at' => $quotation['created_at'] ?? null
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET ALL QUOTATIONS
// ============================================================

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(quote_no LIKE ? OR customer LIKE ? OR customer_email LIKE ? OR service LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countQuery = "SELECT COUNT(*) as total FROM quotations $whereClause";
$stmt = mysqli_prepare($conn, $countQuery);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? intval($totalRow['total']) : 0;
mysqli_stmt_close($stmt);

$query = "SELECT * FROM quotations $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$quotations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $amount = (float)($row['amount'] ?? 0);
    $gstAmount = $amount * GST_RATE / 100;

    $quotations[] = [
        'id' => intval($row['id']),
        'quote_no' => $row['quote_no'] ?? '',
        'customer' => $row['customer'] ?? '',
        'customer_email' => $row['customer_email'] ?? '',
        'customer_phone' => $row['customer_phone'] ?? '',
        'service' => $row['service'] ?? '',
        'amount' => round($amount, 2),
        'gst_amount' => round($gstAmount, 2),
        'total_with_gst' => round($amount + $gstAmount, 2),
        'status' => $row['status'] ?? 'Draft',
        'date' => $row['date'] ?? null,
        'validity' => $row['validity'] ?? null,
        'created_at' => $row['created_at'] ?? null
    ];
}
mysqli_stmt_close($stmt);

$statusCounts = ['Draft' => 0, 'Sent' => 0, 'Approved' => 0, 'Rejected' => 0, 'Converted' => 0];
$statuses = ['Draft', 'Sent', 'Approved', 'Rejected', 'Converted'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations WHERE status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}
$statusCounts['total'] = $total;

$amountResult = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM quotations");
$amountRow = mysqli_fetch_assoc($amountResult);
$totalAmount = floatval($amountRow['total'] ?? 0);
$totalGst = $totalAmount * GST_RATE / 100;

echo json_encode([
    'success' => true,
    'data' => [
        'quotations' => $quotations,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'search' => $search,
        'status' => $status,
        'status_counts' => $statusCounts,
        'summary' => [
            'total_amount' => round($totalAmount, 2),
            'total_gst' => round($totalGst, 2),
            'total_with_gst' => round($totalAmount + $totalGst, 2)
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>