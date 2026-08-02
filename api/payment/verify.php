<?php
require_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_receipt = $input['order_id'] ?? '';
$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';

if (empty($order_receipt) || empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
    echo json_encode(['success' => false, 'error' => 'Missing payment details']);
    exit;
}

// Razorpay API credentials
$key_secret = 'YOUR_KEY_SECRET'; // Replace with your Key Secret

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);

if ($generated_signature === $razorpay_signature) {
    // Get payment order
    $order_query = mysqli_prepare($conn, "SELECT id, amount FROM payment_orders WHERE order_receipt = ? AND user_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($order_query, "si", $order_receipt, $user_id);
    mysqli_stmt_execute($order_query);
    $order_result = mysqli_stmt_get_result($order_query);
    $order = mysqli_fetch_assoc($order_result);
    mysqli_stmt_close($order_query);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Update payment order status
    $update = mysqli_prepare($conn, "UPDATE payment_orders SET 
        razorpay_payment_id = ?, 
        razorpay_signature = ?, 
        status = 'paid', 
        paid_at = NOW() 
        WHERE order_receipt = ?");
    mysqli_stmt_bind_param($update, "sss", $razorpay_payment_id, $razorpay_signature, $order_receipt);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    
    // Log successful transaction
    $log = mysqli_prepare($conn, "INSERT INTO payment_transactions (payment_order_id, transaction_type, amount, status, response_data, created_at) 
        VALUES (?, 'payment', ?, 'success', ?, NOW())");
    $response_data = json_encode($input);
    mysqli_stmt_bind_param($log, "ids", $order['id'], $order['amount'], $response_data);
    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);
    
    // Send email notification
    $user_query = mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($user_query);
    
    if ($user) {
        $subject = "Payment Confirmation - CIBIL Repair";
        $message = "<h3>Dear {$user['name']},</h3>
                    <p>Your payment of ₹{$order['amount']} has been received successfully.</p>
                    <p>Transaction ID: $razorpay_payment_id</p>
                    <p>Order ID: $order_receipt</p>
                    <p>Thank you for choosing CIBIL Repair!</p>";
        
        // Uncomment to send email
        // mail($user['email'], $subject, $message, "Content-Type: text/html; charset=UTF-8");
    }
    
    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
} else {
    // Log failed verification
    $order_query = mysqli_prepare($conn, "SELECT id FROM payment_orders WHERE order_receipt = ?");
    mysqli_stmt_bind_param($order_query, "s", $order_receipt);
    mysqli_stmt_execute($order_query);
    $order_result = mysqli_stmt_get_result($order_query);
    $order = mysqli_fetch_assoc($order_result);
    mysqli_stmt_close($order_query);
    
    if ($order) {
        $log = mysqli_prepare($conn, "INSERT INTO payment_transactions (payment_order_id, transaction_type, status, response_data, created_at) 
            VALUES (?, 'payment_verification', 'failed', ?, NOW())");
        $response_data = json_encode(['error' => 'Invalid signature', 'input' => $input]);
        mysqli_stmt_bind_param($log, "is", $order['id'], $response_data);
        mysqli_stmt_execute($log);
        mysqli_stmt_close($log);
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid payment signature']);
}

mysqli_close($conn);
?>