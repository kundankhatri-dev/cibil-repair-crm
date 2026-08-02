<?php
// api/client/add_dispute.php - File a new dispute
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (only client can file dispute)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can file dispute (admins/partners cannot file on behalf)
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can file disputes']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// ========== GET INPUT ==========
$bank_name = trim($input['bank_name'] ?? '');
$issue_type = trim($input['issue_type'] ?? '');
$account_number = trim($input['account_number'] ?? '');
$amount = isset($input['amount']) ? (float)$input['amount'] : null;
$description = trim($input['description'] ?? '');
$case_id = isset($input['case_id']) ? (int)$input['case_id'] : null;

// ========== VALIDATION ==========
$errors = [];

if (empty($bank_name)) {
    $errors[] = "Bank/Lender name is required";
} elseif (strlen($bank_name) < 2) {
    $errors[] = "Bank name must be at least 2 characters";
} elseif (strlen($bank_name) > 100) {
    $errors[] = "Bank name must be less than 100 characters";
}

if (empty($issue_type)) {
    $errors[] = "Issue type is required";
}

$valid_issue_types = [
    'Written Off Entry',
    'Settled Entry',
    'Wrong Late Payment',
    'Duplicate Account',
    'Incorrect Personal Info',
    'Fraudulent Loan',
    'Other Error'
];

if (!empty($issue_type) && !in_array($issue_type, $valid_issue_types)) {
    $errors[] = "Invalid issue type selected";
}

if (empty($description)) {
    $errors[] = "Description is required";
} elseif (strlen($description) < 20) {
    $errors[] = "Please provide more details (minimum 20 characters)";
} elseif (strlen($description) > 2000) {
    $errors[] = "Description must be less than 2000 characters";
}

if ($amount !== null && $amount < 0) {
    $errors[] = "Amount cannot be negative";
}

if ($case_id !== null && $case_id <= 0) {
    $errors[] = "Invalid case ID";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ========== CREATE DISPUTES TABLE IF NOT EXISTS ==========
$create_table = "CREATE TABLE IF NOT EXISTS disputes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    dispute_id VARCHAR(50) UNIQUE,
    bank_name VARCHAR(100) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    account_number VARCHAR(50),
    amount DECIMAL(12,2),
    description TEXT,
    status ENUM('pending', 'in_progress', 'resolved', 'rejected', 'closed') DEFAULT 'pending',
    filed_date DATE,
    expected_resolution DATE,
    resolution_notes TEXT,
    resolved_date DATE,
    case_id INT,
    assigned_to INT,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_filed_date (filed_date),
    INDEX idx_dispute_id (dispute_id)
)";

mysqli_query($conn, $create_table);

// ========== CREATE DISPUTE DOCUMENTS TABLE ==========
$create_docs = "CREATE TABLE IF NOT EXISTS dispute_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispute_id INT NOT NULL,
    document_name VARCHAR(200),
    file_path VARCHAR(500),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dispute (dispute_id)
)";

mysqli_query($conn, $create_docs);

// ========== CREATE DISPUTE TIMELINE TABLE ==========
$create_timeline = "CREATE TABLE IF NOT EXISTS dispute_timeline (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispute_id INT NOT NULL,
    status VARCHAR(50),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dispute (dispute_id)
)";

mysqli_query($conn, $create_timeline);

// ========== GENERATE DISPUTE ID ==========
function generateDisputeId($conn, $client_id) {
    $prefix = 'DSP';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT dispute_id FROM disputes WHERE dispute_id LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['dispute_id'], -4);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return $prefix . $year . $month . str_pad($new_num, 4, '0', STR_PAD_LEFT);
}

$dispute_id = generateDisputeId($conn, $client_id);
$filed_date = date('Y-m-d');
$expected_resolution = date('Y-m-d', strtotime('+45 days')); // Default 45 days for resolution

// ========== INSERT DISPUTE ==========
$insert_query = "INSERT INTO disputes (
                    client_id, dispute_id, bank_name, issue_type, 
                    account_number, amount, description, status, 
                    filed_date, expected_resolution, case_id, priority
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, 'high')";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 
    "issssdsdsi", 
    $client_id, $dispute_id, $bank_name, $issue_type,
    $account_number, $amount, $description,
    $filed_date, $expected_resolution, $case_id
);

$inserted = mysqli_stmt_execute($insert_stmt);
$dispute_db_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if (!$inserted) {
    echo json_encode(['success' => false, 'error' => 'Failed to file dispute. Please try again.']);
    exit;
}

// ========== ADD TO TIMELINE ==========
$timeline_note = "Dispute filed against $bank_name for $issue_type";
$add_timeline = mysqli_prepare($conn, "INSERT INTO dispute_timeline (dispute_id, status, notes, created_by) VALUES (?, 'filed', ?, ?)");
mysqli_stmt_bind_param($add_timeline, "isi", $dispute_db_id, $timeline_note, $client_id);
mysqli_stmt_execute($add_timeline);
mysqli_stmt_close($add_timeline);

// ========== CREATE NOTIFICATION ==========
$notification_title = "Dispute Filed Successfully";
$notification_message = "Your dispute against $bank_name for '$issue_type' has been filed. Dispute ID: $dispute_id. Expected resolution by " . date('d M Y', strtotime($expected_resolution));

$add_notification = mysqli_prepare($conn, "INSERT INTO client_notifications (client_id, notification_type, title, message, link, priority) VALUES (?, 'dispute', ?, ?, ?, 'high')");
$link = "client-dashboard.php?section=disputes";
mysqli_stmt_bind_param($add_notification, "issss", $client_id, $notification_title, $notification_message, $link);
mysqli_stmt_execute($add_notification);
mysqli_stmt_close($add_notification);

// ========== LOG ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'dispute_filed', ?, ?, ?)");
$desc = "Filed dispute #$dispute_id against $bank_name for $issue_type";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Dispute filed successfully',
    'dispute' => [
        'id' => $dispute_db_id,
        'dispute_id' => $dispute_id,
        'bank_name' => $bank_name,
        'issue_type' => $issue_type,
        'filed_date' => $filed_date,
        'expected_resolution' => $expected_resolution,
        'expected_resolution_formatted' => date('d M Y', strtotime($expected_resolution)),
        'status' => 'pending',
        'status_label' => 'Pending Review'
    ],
    'next_steps' => [
        'Our team will review your dispute within 3-5 business days',
        'You may be contacted for additional documentation',
        'Expected resolution timeline: 45 days from filing',
        'Track dispute status in the Disputes section'
    ]
]);

mysqli_close($conn);
?>