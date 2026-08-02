<?php
// api/employee/update_profile.php - Update employee profile information
session_start();
header('Content-Type: application/json');

// Allow only employees
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;
$user_id = $_SESSION['user_id'];
$first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
$last_name = isset($input['last_name']) ? trim($input['last_name']) : '';
$personal_email = isset($input['personal_email']) ? trim($input['personal_email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$state = isset($input['state']) ? trim($input['state']) : '';
$pincode = isset($input['pincode']) ? trim($input['pincode']) : '';
$emergency_contact_name = isset($input['emergency_contact_name']) ? trim($input['emergency_contact_name']) : '';
$emergency_contact_phone = isset($input['emergency_contact_phone']) ? trim($input['emergency_contact_phone']) : '';
$emergency_contact_relation = isset($input['emergency_contact_relation']) ? trim($input['emergency_contact_relation']) : '';

// Validation
$errors = [];

if ($employee_id <= 0) {
    $errors[] = "Invalid employee ID";
}

if (empty($first_name)) {
    $errors[] = "First name is required";
} elseif (strlen($first_name) < 2) {
    $errors[] = "First name must be at least 2 characters";
}

if (!empty($personal_email) && !filter_var($personal_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (!empty($phone)) {
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }
}

if (!empty($pincode) && !preg_match('/^[0-9]{6}$/', $pincode)) {
    $errors[] = "Pincode must be 6 digits";
}

if (!empty($emergency_contact_phone) && !preg_match('/^[0-9]{10}$/', $emergency_contact_phone)) {
    $errors[] = "Emergency contact number must be 10 digits";
}

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id, work_email FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$employee = mysqli_fetch_assoc($verify_result);

if (!$employee) {
    $errors[] = "Access denied";
}
mysqli_stmt_close($verify);

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Update employee profile
$update_query = "UPDATE employees SET 
                    first_name = ?,
                    last_name = ?,
                    personal_email = ?,
                    personal_phone = ?,
                    current_address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?,
                    emergency_contact_name = ?,
                    emergency_contact_phone = ?,
                    emergency_contact_relation = ?,
                    updated_at = NOW()
                WHERE id = ?";

$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "sssssssssssi", 
    $first_name, $last_name, $personal_email, $phone,
    $address, $city, $state, $pincode,
    $emergency_contact_name, $emergency_contact_phone, $emergency_contact_relation,
    $employee_id
);

$updated = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if ($updated) {
    // Also update users table name if changed
    $full_name = $first_name . ' ' . $last_name;
    $update_user = mysqli_prepare($conn, "UPDATE users SET name = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_user, "si", $full_name, $user_id);
    mysqli_stmt_execute($update_user);
    mysqli_stmt_close($update_user);
    
    // Update session name
    $_SESSION['user_name'] = $full_name;
    
    // Log activity
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'profile_updated', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = "Updated profile information";
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    mysqli_stmt_bind_param($log_stmt, "iss", $user_id, $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'profile' => [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'full_name' => $full_name,
            'personal_email' => $personal_email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
}

mysqli_close($conn);
?>