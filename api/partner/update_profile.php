<?php
// api/partner/update_profile.php
// Partner Update Profile API - Update partner profile information

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

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

// Verify user is actually a partner and get current data
$role_check = mysqli_prepare($conn, "SELECT id, role, name, email, phone FROM users WHERE id = ?");
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

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$company_name = trim($data['company_name'] ?? '');
$email = trim($data['email'] ?? '');

$updates_made = false;
$updated_fields = [];

// ========== VALIDATE INPUTS ==========
// Validate name
if (!empty($name)) {
    if (strlen($name) < 2) {
        echo json_encode(['success' => false, 'error' => 'Name must be at least 2 characters']);
        exit;
    }
    if (strlen($name) > 100) {
        echo json_encode(['success' => false, 'error' => 'Name is too long (maximum 100 characters)']);
        exit;
    }
}

// Validate phone (optional but format check)
if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Phone number must be 10 digits']);
    exit;
}

// Validate email if provided
if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check if email is already used by another user
    $email_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    if ($email_check) {
        mysqli_stmt_bind_param($email_check, "si", $email, $partner_id);
        mysqli_stmt_execute($email_check);
        mysqli_stmt_store_result($email_check);
        if (mysqli_stmt_num_rows($email_check) > 0) {
            echo json_encode(['success' => false, 'error' => 'Email already used by another account']);
            exit;
        }
        mysqli_stmt_close($email_check);
    }
}

// ========== CHECK IF PARTNER EXISTS IN PARTNERS TABLE ==========
$partner_exists = false;
$check_partner = mysqli_prepare($conn, "SELECT id, company_name FROM partners WHERE user_id = ?");
if ($check_partner) {
    mysqli_stmt_bind_param($check_partner, "i", $partner_id);
    mysqli_stmt_execute($check_partner);
    $partner_result = mysqli_stmt_get_result($check_partner);
    $partner_data = mysqli_fetch_assoc($partner_result);
    $partner_exists = ($partner_data !== null);
    $current_company = $partner_data['company_name'] ?? '';
    mysqli_stmt_close($check_partner);
}

// ========== UPDATE USERS TABLE (Main Profile) ==========
$updates = [];
$params = [];
$types = "";

if (!empty($name) && $name !== $role_data['name']) {
    $updates[] = "name = ?";
    $params[] = $name;
    $types .= "s";
    $updated_fields[] = 'name';
}

if (!empty($phone) && $phone !== ($role_data['phone'] ?? '')) {
    $updates[] = "phone = ?";
    $params[] = $phone;
    $types .= "s";
    $updated_fields[] = 'phone';
}

if (!empty($email) && $email !== $role_data['email']) {
    $updates[] = "email = ?";
    $params[] = $email;
    $types .= "s";
    $updated_fields[] = 'email';
}

if (!empty($updates)) {
    $params[] = $partner_id;
    $types .= "i";
    
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        $updates_made = true;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_close($stmt);
}

// ========== UPDATE PARTNERS TABLE (Partner-specific info) ==========
if (!empty($company_name) && $company_name !== $current_company) {
    // Check if company_name column exists in partners table
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'company_name'");
    $hasCompanyColumn = mysqli_num_rows($checkColumn) > 0;
    
    if ($hasCompanyColumn) {
        if ($partner_exists) {
            // Update existing partner record
            $partner_stmt = mysqli_prepare($conn, "UPDATE partners SET company_name = ? WHERE user_id = ?");
            if ($partner_stmt) {
                mysqli_stmt_bind_param($partner_stmt, "si", $company_name, $partner_id);
                if (mysqli_stmt_execute($partner_stmt)) {
                    $updates_made = true;
                    $updated_fields[] = 'company_name';
                }
                mysqli_stmt_close($partner_stmt);
            }
        } else {
            // Insert new partner record with company_name
            $partner_stmt = mysqli_prepare($conn, "INSERT INTO partners (user_id, company_name, commission_rate) VALUES (?, ?, 10.00)");
            if ($partner_stmt) {
                mysqli_stmt_bind_param($partner_stmt, "is", $partner_id, $company_name);
                if (mysqli_stmt_execute($partner_stmt)) {
                    $updates_made = true;
                    $updated_fields[] = 'company_name';
                }
                mysqli_stmt_close($partner_stmt);
            }
        }
    }
}

// ========== UPDATE SESSION DATA ==========
// Get updated user data from database
$final_name = !empty($name) ? $name : $role_data['name'];
$final_email = !empty($email) ? $email : $role_data['email'];
$final_phone = !empty($phone) ? $phone : ($role_data['phone'] ?? '');

// Update session
$_SESSION['user_name'] = $final_name;
$_SESSION['user_email'] = $final_email;

// Update stored user in localStorage (via response header or will be handled by frontend)
$updated_user = [
    'id' => $partner_id,
    'name' => $final_name,
    'email' => $final_email,
    'phone' => $final_phone,
    'role' => 'partner',
    'company_name' => $company_name
];

// ========== LOG ACTIVITY ==========
if ($updates_made) {
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'update_profile', ?, NOW())");
        if ($log_stmt) {
            $description = "Updated profile: " . implode(', ', $updated_fields);
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
}

// ========== RETURN RESPONSE ==========
if ($updates_made) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'updated_fields' => $updated_fields,
        'user' => $updated_user
    ]);
} else if (empty($name) && empty($phone) && empty($email) && empty($company_name)) {
    echo json_encode([
        'success' => true,
        'message' => 'No changes were made',
        'user' => $updated_user
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Profile up to date',
        'user' => $updated_user
    ]);
}

// ========== CLEAN UP ==========
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>