<?php
// api/partner/bulk_import.php
// Partner Bulk Import API - Bulk import leads from CSV/Excel

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// ========== VALIDATE INPUT ==========
$data = json_decode(file_get_contents('php://input'), true);
$leads = $data['leads'] ?? [];

if (empty($leads) || !is_array($leads)) {
    echo json_encode(['success' => false, 'error' => 'No leads data provided']);
    exit;
}

// Limit bulk import size
$max_bulk_size = 500;
if (count($leads) > $max_bulk_size) {
    echo json_encode([
        'success' => false, 
        'error' => "Maximum $max_bulk_size leads can be imported at once. You have " . count($leads)
    ]);
    exit;
}

// Valid services list
$valid_services = [
    'Written Off Clearance',
    'Settled Clearance',
    'Suit Filed Clearance',
    'Credit Report Analysis',
    'Profile Correction',
    'Wrong Entry Clearance'
];

// ========== START TRANSACTION ==========
mysqli_begin_transaction($conn);

$inserted = 0;
$failed = 0;
$duplicate = 0;
$errors = [];
$successful_imports = [];

foreach ($leads as $index => $lead) {
    $name = trim($lead['name'] ?? $lead['customer_name'] ?? '');
    $phone = trim($lead['phone'] ?? $lead['customer_phone'] ?? '');
    $email = trim($lead['email'] ?? $lead['customer_email'] ?? '');
    $service = trim($lead['service'] ?? $lead['service_type'] ?? 'Written Off Clearance');
    $source = trim($lead['source'] ?? 'Bulk Import');
    $notes = trim($lead['notes'] ?? '');
    
    $row_num = $index + 2; // For error reporting (assuming row 1 is headers)
    
    // ========== VALIDATE REQUIRED FIELDS ==========
    if (empty($name)) {
        $failed++;
        $errors[] = "Row $row_num: Name is required";
        continue;
    }
    
    if (empty($phone)) {
        $failed++;
        $errors[] = "Row $row_num: Phone number is required";
        continue;
    }
    
    // Validate phone format (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $failed++;
        $errors[] = "Row $row_num: Invalid phone number '$phone' (must be 10 digits)";
        continue;
    }
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $failed++;
        $errors[] = "Row $row_num: Invalid email format '$email'";
        continue;
    }
    
    // Validate service
    if (!in_array($service, $valid_services)) {
        $failed++;
        $errors[] = "Row $row_num: Invalid service '$service'";
        continue;
    }
    
    // ========== CHECK FOR DUPLICATE PHONE ==========
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM $leadsTable WHERE partner_id = ? AND customer_phone = ? AND status != 'lost'");
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "is", $partner_id, $phone);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $duplicate++;
            $errors[] = "Row $row_num: Lead with phone $phone already exists";
            mysqli_stmt_close($check_stmt);
            continue;
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // ========== INSERT LEAD ==========
    $stmt = mysqli_prepare($conn, "INSERT INTO $leadsTable (partner_id, customer_name, customer_phone, customer_email, service_type, source, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())");
    
    if (!$stmt) {
        $failed++;
        $errors[] = "Row $row_num: Database prepare failed";
        continue;
    }
    
    mysqli_stmt_bind_param($stmt, "issssss", $partner_id, $name, $phone, $email, $service, $source, $notes);
    
    if (mysqli_stmt_execute($stmt)) {
        $inserted++;
        $successful_imports[] = [
            'row' => $row_num,
            'name' => $name,
            'phone' => $phone,
            'lead_id' => mysqli_insert_id($conn)
        ];
    } else {
        $failed++;
        $errors[] = "Row $row_num: Database error - " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// ========== COMMIT OR ROLLBACK ==========
if ($failed == 0 && $duplicate == 0) {
    mysqli_commit($conn);
    $status = 'success';
    $message = "Successfully imported all $inserted leads";
} elseif ($inserted > 0) {
    mysqli_commit($conn);
    $status = 'partial';
    $message = "Imported $inserted leads. $failed failed, $duplicate duplicates skipped.";
} else {
    mysqli_rollback($conn);
    $status = 'failed';
    $message = "Import failed. No leads were imported.";
}

// ========== LOG ACTIVITY ==========
if ($inserted > 0) {
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'bulk_import', ?, NOW())");
        if ($log_stmt) {
            $description = "Bulk imported $inserted leads";
            if ($failed > 0) {
                $description .= " ($failed failed, $duplicate duplicates)";
            }
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
}

// ========== UPDATE PARTNER STATS ==========
if ($inserted > 0) {
    $update_partner = mysqli_prepare($conn, "UPDATE partners SET total_leads = total_leads + ? WHERE user_id = ?");
    if ($update_partner) {
        mysqli_stmt_bind_param($update_partner, "ii", $inserted, $partner_id);
        mysqli_stmt_execute($update_partner);
        mysqli_stmt_close($update_partner);
    }
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => ($inserted > 0),
    'status' => $status,
    'message' => $message,
    'inserted' => $inserted,
    'failed' => $failed,
    'duplicate' => $duplicate,
    'total' => count($leads),
    'successful_imports' => $successful_imports,
    'errors' => $errors
]);

// ========== CLEAN UP ==========
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>