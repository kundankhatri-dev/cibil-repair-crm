<?php
// api/partner/describe_customers.php

header('Content-Type: application/json');

try {
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
    
    // Describe the customers table
    $result = $conn->query("DESCRIBE customers");
    
    if (!$result) {
        throw new Exception('Table "customers" does not exist');
    }
    
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'column_names' => array_column($columns, 'Field')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;
?>