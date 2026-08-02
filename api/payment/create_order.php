<?php
require_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = isset($input['amount']) ? (float)$input['amount'] : 0;
$description = $input['description'] ?? 'Payment for services';
$service_type = $input['service_type'] ?? '';

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    exit;
}

// Store order in database first
$order_receipt = 'ORD_' . time() . '_' . rand(1000, 9999);

$stmt = mysqli_prepare($conn, "INSERT INTO payment_orders (order_receipt, user_id, amount, description, service_type, status, created_at) VALUES (?, ?, ?, ?, ?, 'created', NOW())");
mysqli_stmt_bind_param($stmt, "sidss", $order_receipt, $user_id, $amount, $description, $service_type);
mysqli_stmt_execute($stmt);
$local_order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Razorpay API credentials (Replace with your actual keys from razorpay.com)
$key_id = 'rzp_test_YOUR_KEY_ID';      // Replace with your Key ID
$key_secret = 'YOUR_KEY_SECRET';        // Replace with your Key Secret

// Create Razorpay order
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => $amount * 100, // Amount in paise
    'currency' => 'INR',
    'receipt' => $order_receipt,
    'payment_capture' => 1,
    'notes' => [
        'user_id' => $user_id,
        'description' => $description,
        'service_type' => $service_type
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $order = json_decode($response, true);
    
    // Update order with Razorpay order ID
    $update = mysqli_prepare($conn, "UPDATE payment_orders SET razorpay_order_id = ?, status = 'pending' WHERE id = ?");
    mysqli_stmt_bind_param($update, "si", $order['id'], $local_order_id);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    
    // Log transaction
    $log = mysqli_prepare($conn, "INSERT INTO payment_transactions (payment_order_id, transaction_type, amount, status, response_data, created_at) VALUES (?, 'order_created', ?, 'success', ?, NOW())");
    $response_data = json_encode($order);
    mysqli_stmt_bind_param($log, "ids", $local_order_id, $amount, $response_data);
    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);
    
    // Get user details for prefill
    $user_query = mysqli_query($conn, "SELECT name, email, phone FROM users WHERE id = $user_id");
    $user_data = mysqli_fetch_assoc($user_query);
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_receipt,
        'razorpay_order_id' => $order['id'],
        'amount' => $amount,
        'key_id' => $key_id,
        'user' => [
            'name' => $user_data['name'] ?? '',
            'email' => $user_data['email'] ?? '',
            'phone' => $user_data['phone'] ?? ''
        ]
    ]);
} else {
    // Update order status to failed
    $update = mysqli_prepare($conn, "UPDATE payment_orders SET status = 'failed' WHERE id = ?");
    mysqli_stmt_bind_param($update, "i", $local_order_id);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    
    // Log error
    $log = mysqli_prepare($conn, "INSERT INTO payment_transactions (payment_order_id, transaction_type, amount, status, response_data, created_at) VALUES (?, 'order_created', ?, 'failed', ?, NOW())");
    $error_data = json_encode(['error' => 'Failed to create Razorpay order', 'response' => $response]);
    mysqli_stmt_bind_param($log, "ids", $local_order_id, $amount, $error_data);
    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);
    
    echo json_encode(['success' => false, 'error' => 'Failed to create payment order. Please try again.']);
}

mysqli_close($conn);
?>