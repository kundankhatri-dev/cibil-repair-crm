<?php
// ============================================================
// CIBIL REPAIR CRM - Customer API (Create/Update/Get/Delete)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================================
// GET CUSTOMERS
// ============================================================

if ($action === 'get') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($id > 0) {
        $result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
        if ($result && mysqli_num_rows($result) > 0) {
            $customer = mysqli_fetch_assoc($result);
            echo json_encode(['success' => true, 'data' => $customer]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
        }
        exit;
    }
    
    $sql = "SELECT * FROM customers";
    $conditions = [];
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $conditions[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' OR city LIKE '%$search%')";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY id DESC";
    
    $result = mysqli_query($conn, $sql);
    $customers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $customers, 'total' => count($customers)]);
    exit;
}

// ============================================================
// CREATE CUSTOMER
// ============================================================

if ($action === 'create') {
    if (!in_array($role, ['admin', 'super_admin', 'manager'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $city = isset($input['city']) ? trim($input['city']) : '';
    $service = isset($input['service']) ? trim($input['service']) : 'Written Off';
    $status = isset($input['status']) ? trim($input['status']) : 'active';
    $joined = isset($input['joined']) ? trim($input['joined']) : date('Y-m-d');
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $address = isset($input['address']) ? trim($input['address']) : '';
    $state = isset($input['state']) ? trim($input['state']) : '';
    $pincode = isset($input['pincode']) ? trim($input['pincode']) : '';
    $gst_number = isset($input['gst_number']) ? strtoupper(trim($input['gst_number'])) : '';
    $pan_number = isset($input['pan_number']) ? strtoupper(trim($input['pan_number'])) : '';
    $occupation = isset($input['occupation']) ? trim($input['occupation']) : '';
    $company = isset($input['company']) ? trim($input['company']) : '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        exit;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
        exit;
    }
    
    // Check duplicates
    $check = mysqli_query($conn, "SELECT id FROM customers WHERE email = '$email'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
    
    if (!empty($phone)) {
        $check = mysqli_query($conn, "SELECT id FROM customers WHERE phone = '$phone'");
        if ($check && mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'error' => 'Phone number already exists']);
            exit;
        }
    }
    
    // Build notes
    if (!empty($address)) $notes .= ($notes ? "\n" : "") . "Address: $address";
    if (!empty($state)) $notes .= ($notes ? "\n" : "") . "State: $state";
    if (!empty($pincode)) $notes .= ($notes ? "\n" : "") . "Pincode: $pincode";
    if (!empty($gst_number)) $notes .= ($notes ? "\n" : "") . "GST: $gst_number";
    if (!empty($pan_number)) $notes .= ($notes ? "\n" : "") . "PAN: $pan_number";
    if (!empty($occupation)) $notes .= ($notes ? "\n" : "") . "Occupation: $occupation";
    if (!empty($company)) $notes .= ($notes ? "\n" : "") . "Company: $company";
    $notes = trim(mysqli_real_escape_string($conn, $notes));
    
    $sql = "INSERT INTO customers (name, email, phone, city, service, status, joined, notes) 
            VALUES (
                '" . mysqli_real_escape_string($conn, $name) . "',
                '" . mysqli_real_escape_string($conn, $email) . "',
                '" . mysqli_real_escape_string($conn, $phone) . "',
                '" . mysqli_real_escape_string($conn, $city) . "',
                '" . mysqli_real_escape_string($conn, $service) . "',
                '" . mysqli_real_escape_string($conn, $status) . "',
                '" . mysqli_real_escape_string($conn, $joined) . "',
                '" . $notes . "'
            )";
    
    if (mysqli_query($conn, $sql)) {
        $id = mysqli_insert_id($conn);
        $result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
        $customer = mysqli_fetch_assoc($result);
        
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $logDetails = "Created customer: $name ($email)";
        mysqli_query($conn, "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                             VALUES ($user_id, '$user_name', 'Customer created', '$logDetails', '$ip', NOW())");
        
        echo json_encode(['success' => true, 'message' => 'Customer created successfully', 'data' => $customer]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create customer: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// UPDATE CUSTOMER
// ============================================================

if ($action === 'update') {
    if (!in_array($role, ['admin', 'super_admin', 'manager'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $id = isset($input['id']) ? intval($input['id']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        exit;
    }
    
    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $city = isset($input['city']) ? trim($input['city']) : '';
    $service = isset($input['service']) ? trim($input['service']) : '';
    $status = isset($input['status']) ? trim($input['status']) : '';
    
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
        exit;
    }
    
    $sql = "UPDATE customers SET 
            name = '" . mysqli_real_escape_string($conn, $name) . "',
            email = '" . mysqli_real_escape_string($conn, $email) . "',
            phone = '" . mysqli_real_escape_string($conn, $phone) . "',
            city = '" . mysqli_real_escape_string($conn, $city) . "',
            service = '" . mysqli_real_escape_string($conn, $service) . "',
            status = '" . mysqli_real_escape_string($conn, $status) . "'
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        $result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
        $customer = mysqli_fetch_assoc($result);
        
        echo json_encode(['success' => true, 'message' => 'Customer updated successfully', 'data' => $customer]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update customer: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// DELETE CUSTOMER
// ============================================================

if ($action === 'delete') {
    if (!in_array($role, ['admin', 'super_admin'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $id = isset($input['id']) ? intval($input['id']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        exit;
    }
    
    $result = mysqli_query($conn, "SELECT name, email FROM customers WHERE id = $id");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
        exit;
    }
    $customer = mysqli_fetch_assoc($result);
    
    if (mysqli_query($conn, "DELETE FROM customers WHERE id = $id")) {
        echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete customer: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// DEFAULT RESPONSE
// ============================================================

echo json_encode([
    'success' => false,
    'error' => 'Invalid action',
    'available_actions' => ['get', 'create', 'update', 'delete']
]);

mysqli_close($conn);
exit;
?>