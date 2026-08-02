<?php
// api/partner/test_customers.php

// Suppress warnings from the session
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json');

try {
    // Load database config
    require_once '../../config/database.php';
    
    // Use global connection
    global $conn;
    
    // Create connection if needed
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
    
    $partner_id = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
    
    if ($partner_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Missing partner_id']);
        exit;
    }
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchParam = "%{$search}%";
    
    // Query customers
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
        throw new Exception('SQL Error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    // Clear any output buffers
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'data' => $customers,
        'total' => count($customers)
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?>