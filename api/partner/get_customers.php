<?php
// api/partner/get_customers.php

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
    
    // Check if customers table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($table_check->num_rows === 0) {
        // Return sample data
        echo json_encode([
            'success' => true,
            'customers' => [
                ['id' => 1, 'name' => 'Sample Customer 1', 'phone' => '9876543210', 'email' => 'sample1@example.com'],
                ['id' => 2, 'name' => 'Sample Customer 2', 'phone' => '9876543220', 'email' => 'sample2@example.com']
            ],
            'total' => 2,
            'message' => 'Sample data (customers table not found)'
        ]);
        exit;
    }
    
    // Get column names from customers table
    $columns_result = $conn->query("DESCRIBE customers");
    $columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Find the correct column names
    $id_col = in_array('id', $columns) ? 'id' : (in_array('customer_id', $columns) ? 'customer_id' : 'id');
    $name_col = in_array('name', $columns) ? 'name' : (in_array('full_name', $columns) ? 'full_name' : 'name');
    $phone_col = in_array('phone', $columns) ? 'phone' : (in_array('mobile', $columns) ? 'mobile' : 'phone');
    $email_col = in_array('email', $columns) ? 'email' : (in_array('email_address', $columns) ? 'email_address' : 'email');
    $status_col = in_array('status', $columns) ? 'status' : 'status';
    $created_col = in_array('created_at', $columns) ? 'created_at' : (in_array('created', $columns) ? 'created' : 'created_at');
    
    // Find the partner column
    $partner_col = null;
    foreach ($columns as $col) {
        if (strpos($col, 'partner') !== false || strpos($col, 'user') !== false) {
            $partner_col = $col;
            break;
        }
    }
    
    $partner_id = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build query - if no partner column exists, return all customers
    $sql = "SELECT * FROM customers";
    $params = [];
    $types = "";
    $where = [];
    
    // Add partner condition if column exists
    if ($partner_col && $partner_id > 0) {
        $where[] = "$partner_col = ?";
        $params[] = $partner_id;
        $types .= "i";
    }
    
    // Add search condition
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $where[] = "($name_col LIKE ? OR $phone_col LIKE ? OR $email_col LIKE ?)";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY $created_col DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('SQL Error: ' . $conn->error);
    }
    
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        // Map to standard format
        $customers[] = [
            'id' => $row[$id_col],
            'name' => $row[$name_col],
            'phone' => $row[$phone_col],
            'email' => $row[$email_col],
            'status' => $row[$status_col] ?? 'active',
            'created_at' => $row[$created_col] ?? date('Y-m-d H:i:s')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'data' => $customers,
        'total' => count($customers),
        'debug' => [
            'partner_col' => $partner_col,
            'columns_found' => $columns
        ]
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
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