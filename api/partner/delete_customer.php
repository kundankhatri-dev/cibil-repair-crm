<?php
// api/partner/delete_customer.php

header('Content-Type: application/json');

try {
    // Get the request data
    $input = json_decode(file_get_contents('php://input'), true);
    $customer_id = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
    
    if ($customer_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Missing customer_id']);
        exit;
    }
    
    // Load database
    require_once '../../config/database.php';
    global $conn;
    
    if (!isset($conn) || !$conn instanceof mysqli) {
        if (defined('DB_HOST')) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
        } else {
            throw new Exception('Database connection not available');
        }
    }
    
    // Delete the customer
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Delete failed: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?>