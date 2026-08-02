<?php
// ============================================================
// CIBIL REPAIR CRM - Direct API Access (COMPLETE)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// CREATE MISSING TABLES
// ============================================================

// Cases table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caseNo VARCHAR(50) NOT NULL,
    clientId INT NOT NULL,
    clientName VARCHAR(255),
    service VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Payments table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clientName VARCHAR(255),
    amount DECIMAL(10,2),
    service VARCHAR(255),
    status VARCHAR(50) DEFAULT 'completed',
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    payment_mode VARCHAR(50),
    package VARCHAR(100)
)");

// Activity logs table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(100),
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Posters table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS posters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
)");

// Quotations table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_no VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    customer_gst VARCHAR(15),
    customer_pan VARCHAR(10),
    customer_address TEXT,
    customer_city VARCHAR(60),
    customer_state VARCHAR(50),
    customer_pincode VARCHAR(10),
    service VARCHAR(100),
    amount DECIMAL(12,2) NOT NULL,
    gst_amount DECIMAL(12,2) DEFAULT 0,
    cgst_amount DECIMAL(12,2) DEFAULT 0,
    sgst_amount DECIMAL(12,2) DEFAULT 0,
    total_with_gst DECIMAL(12,2) DEFAULT 0,
    status ENUM('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
    valid_until DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================================
// CLIENTS ACTIONS
// ============================================================

if ($action === 'getClients') {
    $query = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE role = 'client' ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $clients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $clients[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $clients]);
    exit;
}

elseif ($action === 'addClient') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $name = mysqli_real_escape_string($conn, $data['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
        exit;
    }
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
    
    $query = "INSERT INTO users (name, email, phone, role, status, created_at) 
              VALUES ('$name', '$email', '$phone', 'client', 'approved', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Client added successfully', 'data' => ['id' => $id]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

elseif ($action === 'deleteClient') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)$data['id'];
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Client ID is required']);
        exit;
    }
    
    $query = "DELETE FROM users WHERE id = $id AND role = 'client'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// LEADS ACTIONS
// ============================================================

elseif ($action === 'getLeads') {
    $query = "SELECT * FROM leads ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $leads = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $leads[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $leads]);
    exit;
}

elseif ($action === 'addLead') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $name = mysqli_real_escape_string($conn, $data['name'] ?? '');
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
    $service = mysqli_real_escape_string($conn, $data['service'] ?? '');
    
    if (empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'Name and phone are required']);
        exit;
    }
    
    $query = "INSERT INTO leads (name, phone, email, service, status, created_at) 
              VALUES ('$name', '$phone', '$email', '$service', 'new', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Lead added successfully', 'data' => ['id' => $id]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// CASES ACTIONS
// ============================================================

elseif ($action === 'getCases') {
    $query = "SELECT * FROM cases ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $cases = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $cases]);
    exit;
}

elseif ($action === 'addCase') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $clientId = (int)$data['client_id'];
    $clientName = mysqli_real_escape_string($conn, $data['client_name'] ?? '');
    $service = mysqli_real_escape_string($conn, $data['service'] ?? '');
    $amount = (float)$data['amount'];
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'pending');
    $caseNo = 'CR' . date('Y') . str_pad(rand(1, 9999), 5, '0', STR_PAD_LEFT);
    
    if (!$clientId || empty($service) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Client ID, service, and amount are required']);
        exit;
    }
    
    if (empty($clientName)) {
        $clientResult = mysqli_query($conn, "SELECT name FROM users WHERE id = $clientId");
        if ($clientResult && mysqli_num_rows($clientResult) > 0) {
            $clientRow = mysqli_fetch_assoc($clientResult);
            $clientName = mysqli_real_escape_string($conn, $clientRow['name']);
        } else {
            $clientName = 'Unknown Client';
        }
    }
    
    $query = "INSERT INTO cases (caseNo, clientId, clientName, service, status, amount, created_at) 
              VALUES ('$caseNo', $clientId, '$clientName', '$service', '$status', $amount, NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Case added successfully', 
            'data' => [
                'id' => $id, 
                'caseNo' => $caseNo,
                'clientId' => $clientId,
                'clientName' => $clientName,
                'service' => $service,
                'status' => $status,
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// PAYMENTS ACTIONS
// ============================================================

elseif ($action === 'getPayments') {
    $query = "SELECT * FROM payments ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $payments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $payments]);
    exit;
}

elseif ($action === 'addPayment') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $clientName = mysqli_real_escape_string($conn, $data['client_name'] ?? '');
    $amount = (float)$data['amount'];
    $service = mysqli_real_escape_string($conn, $data['service'] ?? '');
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'completed');
    $payment_mode = mysqli_real_escape_string($conn, $data['payment_method'] ?? 'cash');
    $package = mysqli_real_escape_string($conn, $data['package'] ?? '');
    $clientId = (int)$data['client_id'];
    
    if (!$clientId || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Client ID and valid amount are required']);
        exit;
    }
    
    if (empty($clientName)) {
        $clientResult = mysqli_query($conn, "SELECT name FROM users WHERE id = $clientId");
        if ($clientResult && mysqli_num_rows($clientResult) > 0) {
            $clientRow = mysqli_fetch_assoc($clientResult);
            $clientName = mysqli_real_escape_string($conn, $clientRow['name']);
        } else {
            $clientName = 'Unknown Client';
        }
    }
    
    $query = "INSERT INTO payments (clientName, amount, service, status, date, payment_mode, package) 
              VALUES ('$clientName', $amount, '$service', '$status', NOW(), '$payment_mode', '$package')";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Payment added successfully', 
            'data' => [
                'id' => $id,
                'clientName' => $clientName,
                'amount' => $amount,
                'service' => $service,
                'status' => $status,
                'payment_mode' => $payment_mode,
                'package' => $package,
                'date' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// POSTER ACTIONS
// ============================================================

elseif ($action === 'addPoster') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $filename = isset($data['filename']) ? trim($data['filename']) : '';
    $original_name = isset($data['original_name']) ? trim($data['original_name']) : '';
    $file_path = isset($data['file_path']) ? trim($data['file_path']) : '/uploads/posters/' . $filename;
    $file_size = isset($data['file_size']) ? intval($data['file_size']) : 0;
    
    if (empty($filename)) {
        echo json_encode(['success' => false, 'error' => 'Filename is required']);
        exit;
    }
    
    if (empty($original_name)) {
        $original_name = $filename;
    }
    
    $sql = "INSERT INTO posters (filename, original_name, file_path, file_size, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssi', $filename, $original_name, $file_path, $file_size);
    
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Poster created successfully',
            'data' => [
                'id' => $id,
                'filename' => $filename,
                'original_name' => $original_name,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create poster: ' . mysqli_error($conn)
        ]);
    }
    exit;
}

elseif ($action === 'getPosters') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($id > 0) {
        $result = mysqli_query($conn, "SELECT * FROM posters WHERE id = $id AND deleted_at IS NULL");
        if ($result && mysqli_num_rows($result) > 0) {
            $poster = mysqli_fetch_assoc($result);
            echo json_encode(['success' => true, 'data' => $poster]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Poster not found']);
        }
        exit;
    }
    
    $sql = "SELECT * FROM posters WHERE deleted_at IS NULL";
    $conditions = [];
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $conditions[] = "(filename LIKE '%$search%' OR original_name LIKE '%$search%')";
    }
    
    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY id DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $posters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posters[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $posters,
        'total' => count($posters)
    ]);
    exit;
}

elseif ($action === 'deletePoster') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Poster ID is required']);
        exit;
    }
    
    $result = mysqli_query($conn, "SELECT filename, original_name FROM posters WHERE id = $id AND deleted_at IS NULL");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'error' => 'Poster not found']);
        exit;
    }
    $poster = mysqli_fetch_assoc($result);
    
    $sql = "UPDATE posters SET deleted_at = NOW() WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Poster deleted successfully',
            'data' => [
                'id' => $id,
                'filename' => $poster['filename'],
                'original_name' => $poster['original_name']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete poster: ' . mysqli_error($conn)
        ]);
    }
    exit;
}

elseif ($action === 'describePosters') {
    $result = mysqli_query($conn, "DESCRIBE posters");
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    echo json_encode(['success' => true, 'columns' => $columns]);
    exit;
}

// ============================================================
// QUOTATIONS ACTIONS
// ============================================================

elseif ($action === 'addQuotation') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $customer_name = isset($data['customer']) ? trim($data['customer']) : '';
    $customer_email = isset($data['customer_email']) ? trim($data['customer_email']) : '';
    $customer_phone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';
    $service = isset($data['service']) ? trim($data['service']) : 'Written Off';
    $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
    $status = isset($data['status']) ? trim($data['status']) : 'draft';
    $valid_until = isset($data['valid_until']) ? trim($data['valid_until']) : date('Y-m-d', strtotime('+30 days'));
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    $gst_rate = isset($data['gst_rate']) ? floatval($data['gst_rate']) : 18;
    $customer_gst = isset($data['customer_gst']) ? trim($data['customer_gst']) : '';
    $customer_pan = isset($data['customer_pan']) ? trim($data['customer_pan']) : '';
    $customer_address = isset($data['customer_address']) ? trim($data['customer_address']) : '';
    $customer_city = isset($data['customer_city']) ? trim($data['customer_city']) : '';
    $customer_state = isset($data['customer_state']) ? trim($data['customer_state']) : '';
    $customer_pincode = isset($data['customer_pincode']) ? trim($data['customer_pincode']) : '';
    $created_by = isset($data['created_by']) ? intval($data['created_by']) : 0;
    
    if (empty($customer_name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid amount is required']);
        exit;
    }
    
    // Generate quote number
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations");
    $row = mysqli_fetch_assoc($countResult);
    $count = $row ? $row['count'] + 1 : 1;
    $quote_no = 'QUO' . date('Y') . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // GST Calculations
    $gst_amount = $amount * ($gst_rate / 100);
    $cgst_amount = $gst_amount / 2;
    $sgst_amount = $gst_amount / 2;
    $total_with_gst = $amount + $gst_amount;
    
    // Escape values
    $customer_name_escaped = mysqli_real_escape_string($conn, $customer_name);
    $customer_email_escaped = mysqli_real_escape_string($conn, $customer_email);
    $customer_phone_escaped = mysqli_real_escape_string($conn, $customer_phone);
    $service_escaped = mysqli_real_escape_string($conn, $service);
    $status_escaped = mysqli_real_escape_string($conn, $status);
    $valid_until_escaped = mysqli_real_escape_string($conn, $valid_until);
    $notes_escaped = mysqli_real_escape_string($conn, $notes);
    $customer_gst_escaped = mysqli_real_escape_string($conn, $customer_gst);
    $customer_pan_escaped = mysqli_real_escape_string($conn, $customer_pan);
    $customer_address_escaped = mysqli_real_escape_string($conn, $customer_address);
    $customer_city_escaped = mysqli_real_escape_string($conn, $customer_city);
    $customer_state_escaped = mysqli_real_escape_string($conn, $customer_state);
    $customer_pincode_escaped = mysqli_real_escape_string($conn, $customer_pincode);
    
    $sql = "INSERT INTO quotations (
        quote_no, customer_name, customer_email, customer_phone, 
        customer_gst, customer_pan, customer_address, customer_city, 
        customer_state, customer_pincode, service, amount, 
        gst_amount, cgst_amount, sgst_amount, total_with_gst, 
        status, valid_until, notes, created_by
    ) VALUES (
        '$quote_no', '$customer_name_escaped', '$customer_email_escaped', 
        '$customer_phone_escaped', '$customer_gst_escaped', 
        '$customer_pan_escaped', '$customer_address_escaped', 
        '$customer_city_escaped', '$customer_state_escaped', 
        '$customer_pincode_escaped', '$service_escaped', 
        $amount, $gst_amount, $cgst_amount, $sgst_amount, 
        $total_with_gst, '$status_escaped', '$valid_until_escaped', 
        '$notes_escaped', $created_by
    )";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $id = mysqli_insert_id($conn);
        $result2 = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $id");
        $quotation = mysqli_fetch_assoc($result2);
        
        echo json_encode([
            'success' => true,
            'message' => 'Quotation added successfully',
            'data' => [
                'quotation' => $quotation,
                'gst_details' => [
                    'base_amount' => $amount,
                    'gst_rate' => $gst_rate,
                    'gst_amount' => $gst_amount,
                    'cgst_amount' => $cgst_amount,
                    'sgst_amount' => $sgst_amount,
                    'total_with_gst' => $total_with_gst
                ]
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . mysqli_error($conn)
        ]);
    }
    exit;
}

elseif ($action === 'getQuotations') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id > 0) {
        $result = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $id");
        if ($result && mysqli_num_rows($result) > 0) {
            $quotation = mysqli_fetch_assoc($result);
            echo json_encode(['success' => true, 'data' => $quotation]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Quotation not found']);
        }
        exit;
    }
    
    $sql = "SELECT * FROM quotations ORDER BY id DESC LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    $quotations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $quotations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $quotations,
        'total' => count($quotations)
    ]);
    exit;
}

elseif ($action === 'deleteQuotation') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Quotation ID is required']);
        exit;
    }
    
    $result = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $id");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'error' => 'Quotation not found']);
        exit;
    }
    $quotation = mysqli_fetch_assoc($result);
    
    $sql = "DELETE FROM quotations WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Quotation deleted successfully',
            'data' => [
                'id' => $id,
                'quote_no' => $quotation['quote_no'],
                'customer_name' => $quotation['customer_name']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete quotation: ' . mysqli_error($conn)
        ]);
    }
    exit;
}

elseif ($action === 'describeQuotations') {
    $result = mysqli_query($conn, "DESCRIBE quotations");
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Table not found']);
        exit;
    }
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    echo json_encode(['success' => true, 'columns' => $columns]);
    exit;
}

// ============================================================
// TABLE DESCRIPTION ACTIONS
// ============================================================

elseif ($action === 'describeCases') {
    $result = mysqli_query($conn, "DESCRIBE cases");
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    echo json_encode(['success' => true, 'columns' => $columns]);
    exit;
}

elseif ($action === 'describePayments') {
    $result = mysqli_query($conn, "DESCRIBE payments");
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    echo json_encode(['success' => true, 'columns' => $columns]);
    exit;
}

elseif ($action === 'describeUsers') {
    $result = mysqli_query($conn, "DESCRIBE users");
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }
    echo json_encode(['success' => true, 'columns' => $columns]);
    exit;
}

// ============================================================
// CHECK TABLES
// ============================================================

elseif ($action === 'checkTables') {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    echo json_encode(['success' => true, 'tables' => $tables, 'total' => count($tables)]);
    exit;
}

// ============================================================
// DEFAULT RESPONSE
// ============================================================

else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action',
        'available_actions' => [
            'getClients', 'addClient', 'deleteClient',
            'getLeads', 'addLead',
            'getCases', 'addCase',
            'getPayments', 'addPayment',
            'addPoster', 'getPosters', 'deletePoster', 'describePosters',
            'addQuotation', 'getQuotations', 'deleteQuotation', 'describeQuotations',
            'describeCases', 'describePayments', 'describeUsers',
            'checkTables'
        ]
    ]);
    exit;
}

mysqli_close($conn);
?>