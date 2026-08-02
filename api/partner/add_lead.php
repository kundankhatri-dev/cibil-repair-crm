<?php
// ============================================================
// API: Partner Add Lead - WITH SOURCE TRACKING
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DIRECT DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Verify partner role
$result = mysqli_query($conn, "SELECT role FROM users WHERE id = $partner_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    if (!$row || $row['role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        mysqli_close($conn);
        exit;
    }
}

// ========== GET INPUT DATA ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data)) {
    $data = $_POST;
}

// Log what we received for debugging
error_log('add_lead.php - Input: ' . print_r($data, true));

if (empty($data)) {
    echo json_encode(['success' => false, 'error' => 'No data received. Please send JSON or form data.']);
    mysqli_close($conn);
    exit;
}

// ========== EXTRACT DATA ==========
$name = trim($data['customer_name'] ?? $data['name'] ?? '');
$phone = trim($data['customer_phone'] ?? $data['phone'] ?? '');
$email = trim($data['customer_email'] ?? $data['email'] ?? '');
$service = trim($data['service_type'] ?? $data['service'] ?? '');
$source = trim($data['source'] ?? 'Website');
$city = trim($data['city'] ?? '');
$cibil_score = trim($data['cibil_score'] ?? '');
$loan_amount = trim($data['loan_amount'] ?? '');
$notes = trim($data['notes'] ?? '');

// ========== SOURCE TRACKING DATA ==========
$source_type = trim($data['source_type'] ?? 'direct');
$source_id = isset($data['source_id']) && $data['source_id'] ? (int)$data['source_id'] : null;
$source_name = trim($data['source_name'] ?? '');
$source_commission_rate = isset($data['source_commission_rate']) ? (float)$data['source_commission_rate'] : 0;

// Validate source type
if (!in_array($source_type, ['direct', 'referral', 'connector'])) {
    $source_type = 'direct';
}

// If source is referral or connector, source_id is required
if (($source_type === 'referral' || $source_type === 'connector') && empty($source_id)) {
    echo json_encode(['success' => false, 'error' => 'Please select a ' . $source_type]);
    mysqli_close($conn);
    exit;
}

// ========== VALIDATE ==========
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Customer name is required']);
    mysqli_close($conn);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    mysqli_close($conn);
    exit;
}

// Clean phone (remove non-digits)
$phone_clean = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phone_clean) != 10) {
    echo json_encode(['success' => false, 'error' => 'Phone number must be 10 digits']);
    mysqli_close($conn);
    exit;
}

// ========== DETERMINE TABLE ==========
$tableName = 'leads';
$check = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
if ($check && mysqli_num_rows($check) > 0) {
    $tableName = 'partner_leads';
}

// ========== CHECK AND ADD MISSING COLUMNS ==========
$required_columns = [
    'source_type' => "VARCHAR(50) DEFAULT 'direct'",
    'source_id' => 'INT NULL',
    'source_name' => "VARCHAR(255) DEFAULT NULL",
    'source_commission_rate' => 'DECIMAL(5,2) DEFAULT 0',
    'source_commission_amount' => 'DECIMAL(10,2) DEFAULT 0',
    'score' => 'INT DEFAULT 0',
    'priority' => "VARCHAR(20) DEFAULT 'low'"
];

foreach ($required_columns as $col => $col_type) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM $tableName LIKE '$col'");
    if (!$check_col || mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE $tableName ADD COLUMN $col $col_type");
    }
}

// ========== CHECK FOR DUPLICATE ==========
$check_dup = mysqli_query($conn, "SELECT id FROM $tableName WHERE partner_id = $partner_id AND phone = '$phone_clean' AND status NOT IN ('converted', 'lost')");
if ($check_dup && mysqli_num_rows($check_dup) > 0) {
    echo json_encode(['success' => false, 'error' => 'A lead with this phone number already exists']);
    mysqli_close($conn);
    exit;
}

// ========== INSERT LEAD ==========
$query = "INSERT INTO $tableName (
    partner_id, 
    name, 
    phone, 
    email, 
    service_type, 
    source, 
    city,
    cibil_score,
    loan_amount,
    notes, 
    status,
    source_type,
    source_id,
    source_name,
    source_commission_rate,
    source_commission_amount,
    score,
    priority,
    created_at
) VALUES (
    $partner_id, 
    '" . mysqli_real_escape_string($conn, $name) . "', 
    '" . mysqli_real_escape_string($conn, $phone_clean) . "', 
    '" . mysqli_real_escape_string($conn, $email) . "', 
    '" . mysqli_real_escape_string($conn, $service) . "', 
    '" . mysqli_real_escape_string($conn, $source) . "', 
    '" . mysqli_real_escape_string($conn, $city) . "', 
    '" . mysqli_real_escape_string($conn, $cibil_score) . "', 
    '" . mysqli_real_escape_string($conn, $loan_amount) . "', 
    '" . mysqli_real_escape_string($conn, $notes) . "', 
    'new',
    '" . mysqli_real_escape_string($conn, $source_type) . "',
    " . ($source_id ? $source_id : "NULL") . ",
    '" . mysqli_real_escape_string($conn, $source_name) . "',
    " . ($source_commission_rate ? $source_commission_rate : "0") . ",
    0,  -- source_commission_amount (initially 0)
    0,  -- score (initially 0)
    'low',  -- priority (initially low)
    NOW()
)";

if (mysqli_query($conn, $query)) {
    $lead_id = mysqli_insert_id($conn);
    
    // Update partner stats
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
    if ($check_table && mysqli_num_rows($check_table) > 0) {
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'total_leads'");
        if ($check_column && mysqli_num_rows($check_column) > 0) {
            mysqli_query($conn, "UPDATE partners SET total_leads = total_leads + 1 WHERE user_id = $partner_id");
        }
    }
    
    // If source is referral or connector, increment their lead count
    if ($source_type === 'referral' && $source_id) {
        $check_ref = mysqli_query($conn, "SHOW TABLES LIKE 'referrals'");
        if ($check_ref && mysqli_num_rows($check_ref) > 0) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM referrals LIKE 'leads_referred'");
            if ($check_col && mysqli_num_rows($check_col) > 0) {
                mysqli_query($conn, "UPDATE referrals SET leads_referred = leads_referred + 1 WHERE id = $source_id");
            }
        }
    }
    
    if ($source_type === 'connector' && $source_id) {
        $check_conn = mysqli_query($conn, "SHOW TABLES LIKE 'connectors'");
        if ($check_conn && mysqli_num_rows($check_conn) > 0) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM connectors LIKE 'leads_referred'");
            if ($check_col && mysqli_num_rows($check_col) > 0) {
                mysqli_query($conn, "UPDATE connectors SET leads_referred = leads_referred + 1 WHERE id = $source_id");
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'id' => $lead_id,
        'lead_id' => $lead_id,
        'message' => 'Lead added successfully',
        'source' => [
            'type' => $source_type,
            'name' => $source_name,
            'commission_rate' => $source_commission_rate
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>