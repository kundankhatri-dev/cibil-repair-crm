<?php
// ============================================================
// CIBIL REPAIR CRM - Add Money API
// Endpoint: /api/add_money.php
// Method: POST
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

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

// Check if user has permission
$allowedRoles = ['admin', 'super_admin', 'manager', 'partner'];
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden. Insufficient permissions.']);
    exit;
}

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

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    mysqli_close($conn);
    exit;
}

$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$method = isset($input['method']) ? trim($input['method']) : 'Cash';
$description = isset($input['description']) ? trim($input['description']) : 'Money added';
$referenceId = isset($input['reference_id']) ? trim($input['reference_id']) : '';

// Validate
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    mysqli_close($conn);
    exit;
}

if ($amount > 100000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Maximum deposit amount is ₹100,000']);
    mysqli_close($conn);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Get wallet
    $walletQuery = "SELECT id, balance FROM wallet WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $walletQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $wallet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // If no wallet, create one
    if (!$wallet) {
        $insert = "INSERT INTO wallet (user_id, balance) VALUES (?, 0)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $walletId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        $currentBalance = 0;
    } else {
        $walletId = (int)$wallet['id'];
        $currentBalance = (float)$wallet['balance'];
    }
    
    $newBalance = $currentBalance + $amount;
    
    // Update wallet
    $updateQuery = "UPDATE wallet SET balance = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "di", $newBalance, $walletId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Calculate GST (18%)
    $gstAmount = $amount * 0.18;
    $feeAmount = 0;
    $totalAmount = $amount + $gstAmount + $feeAmount;
    
    // Insert transaction
    $txQuery = "INSERT INTO transactions (
        wallet_id, date, description, amount, type, method, 
        fee_amount, gst_amount, total_amount, balance_after, 
        reference_id, status, created_at
    ) VALUES (?, CURDATE(), ?, ?, 'credit', ?, ?, ?, ?, ?, ?, 'completed', NOW())";
    
    $stmt = mysqli_prepare($conn, $txQuery);
    mysqli_stmt_bind_param($stmt, "isdiddsss", 
        $walletId, 
        $description, 
        $amount, 
        $method, 
        $feeAmount, 
        $gstAmount, 
        $totalAmount, 
        $newBalance, 
        $referenceId
    );
    mysqli_stmt_execute($stmt);
    $txId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log activity
    $logQuery = "INSERT INTO activities (user_id, activity_type, description, created_at) 
                 VALUES (?, 'wallet_add', ?, NOW())";
    $stmt = mysqli_prepare($conn, $logQuery);
    $logDesc = "Added ₹" . number_format($amount, 2) . " to wallet. Method: {$method}";
    mysqli_stmt_bind_param($stmt, "is", $userId, $logDesc);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Response
    echo json_encode([
        'success' => true,
        'data' => [
            'transaction_id' => $txId,
            'amount' => $amount,
            'method' => $method,
            'description' => $description,
            'gst_amount' => $gstAmount,
            'fee_amount' => $feeAmount,
            'total_amount' => $totalAmount,
            'new_balance' => $newBalance,
            'formatted_balance' => '₹' . number_format($newBalance, 2)
        ],
        'message' => 'Money added successfully!'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Transaction failed: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>