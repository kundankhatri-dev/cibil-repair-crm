<?php
// api/partner/test_customers.php

// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Test if PHP is working
if (!defined('TEST_RUN')) {
    define('TEST_RUN', true);
}

try {
    // Get partner_id
    $partner_id = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
    
    // Return test data if no database connection
    if (!file_exists('../../config/database.php')) {
        throw new Exception('Database config not found');
    }
    
    require_once '../../config/database.php';
    
    if (!function_exists('getDatabaseConnection')) {
        throw new Exception('getDatabaseConnection() function not found');
    }
    
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Search parameter
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchParam = "%{$search}%";
    
    // Build query
    $sql = "SELECT * FROM customers WHERE partner_id = ?";
    $params = [$partner_id];
    $types = "i";
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('SQL Prepare failed: ' . $conn->error);
    }
    
    // Dynamic binding
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('SQL Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('SQL Result failed: ' . $stmt->error);
    }
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'data' => $customers,
        'total' => count($customers),
        'debug' => [
            'partner_id' => $partner_id,
            'search' => $search,
            'count' => count($customers)
        ]
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

exit;
?>