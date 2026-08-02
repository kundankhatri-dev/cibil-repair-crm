<?php
// ============================================================
// api/create_registration_code.php
// Generate secure registration codes for partners & clients
// ============================================================

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

session_start();

// ── Authentication ─────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Admin access required']);
    exit;
}

// ── CSRF Protection ────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$csrfToken = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
    exit;
}

// ── Input Validation ───────────────────────────────────────────
$role = isset($input['role']) ? trim($input['role']) : 'partner';
$email = isset($input['email']) ? trim($input['email']) : '';
$expiry_days = isset($input['expiry_days']) ? intval($input['expiry_days']) : 30;
$name = isset($input['name']) ? trim($input['name']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Validate role
$allowed_roles = ['partner', 'client', 'employee', 'admin'];
if (!in_array($role, $allowed_roles)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid role. Allowed: ' . implode(', ', $allowed_roles)]);
    exit;
}

// Validate expiry days
if ($expiry_days < 1 || $expiry_days > 365) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Expiry must be between 1 and 365 days']);
    exit;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ── Database Connection ────────────────────────────────────────
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

try {
    // ── Ensure Table Exists ────────────────────────────────────────
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'registration_codes'");
    
    if (mysqli_num_rows($tableCheck) == 0) {
        $createTable = "
        CREATE TABLE IF NOT EXISTS `registration_codes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `role` varchar(50) NOT NULL DEFAULT 'partner',
            `created_by` int(11) DEFAULT NULL,
            `assigned_to_email` varchar(255) DEFAULT NULL,
            `assigned_to_name` varchar(255) DEFAULT NULL,
            `status` enum('active','used','expired','revoked') DEFAULT 'active',
            `used_by_user_id` int(11) DEFAULT NULL,
            `used_at` datetime DEFAULT NULL,
            `notes` text,
            `expires_at` datetime NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            INDEX idx_status (status),
            INDEX idx_email (assigned_to_email),
            INDEX idx_role (role),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if (!mysqli_query($conn, $createTable)) {
            throw new Exception('Failed to create table: ' . mysqli_error($conn));
        }
    }
    
    // ── Generate Unique Code ──────────────────────────────────────
    $code = generateUniqueCode($conn);
    
    // ── Calculate Expiry ──────────────────────────────────────────
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
    
    // ── Insert Registration Code ──────────────────────────────────
    $stmt = mysqli_prepare($conn, "
        INSERT INTO registration_codes 
            (code, role, created_by, assigned_to_email, assigned_to_name, notes, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "ssissss", $code, $role, $_SESSION['user_id'], $email, $name, $notes, $expires_at);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to insert code: ' . mysqli_error($conn));
    }
    
    $codeId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // ── Log Activity ──────────────────────────────────────────────
    logActivity($conn, $_SESSION['user_id'], 'registration_code_created', 
        "Created {$role} registration code: {$code} for " . ($email ?: 'unassigned'));
    
    // ── Build Registration Link ───────────────────────────────────
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'cibilrepair.in';
    $registerLink = $protocol . $host . "/register.php?code=" . urlencode($code);
    
    if (!empty($email)) {
        $registerLink .= "&email=" . urlencode($email);
    }
    
    // ── Send Email if Assigned ────────────────────────────────────
    $emailSent = false;
    if (!empty($email)) {
        $emailSent = sendRegistrationEmail($email, $code, $role, $registerLink, $expires_at, $name);
    }
    
    // ── Response ──────────────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $codeId,
            'code' => $code,
            'role' => $role,
            'assigned_to_email' => $email,
            'assigned_to_name' => $name,
            'expires_at' => $expires_at,
            'expiry_days' => $expiry_days,
            'status' => 'active',
            'register_link' => $registerLink,
            'email_sent' => $emailSent
        ],
        'message' => empty($email) ? "Registration code generated: {$code}" : "Registration code sent to {$email}"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);


// ════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════════

/**
 * Generate a unique registration code
 */
function generateUniqueCode($conn, $length = 12) {
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Generate code with timestamp + random for uniqueness
        $timestamp = substr(time(), -4);
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $code = $random . $timestamp;
        
        // Ensure minimum length
        if (strlen($code) < $length) {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
        }
        
        // Check if code already exists
        $check = mysqli_query($conn, "SELECT id FROM registration_codes WHERE code = '" . mysqli_real_escape_string($conn, $code) . "'");
        if (mysqli_num_rows($check) == 0) {
            return $code;
        }
        $attempts++;
    }
    
    // Fallback: use timestamp + random
    return 'REG' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Send registration email
 */
function sendRegistrationEmail($email, $code, $role, $registerLink, $expires_at, $name) {
    try {
        $roleDisplay = ucfirst($role);
        $nameDisplay = !empty($name) ? htmlspecialchars($name) : 'User';
        $expiryDisplay = date('d M Y, h:i A', strtotime($expires_at));
        
        $subject = "Your {$roleDisplay} Registration Code - CIBIL Repair";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Registration Code</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
                .wrap { max-width: 550px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .hdr { background: linear-gradient(135deg, #0b2a23, #0d9e78); padding: 30px; color: #fff; text-align: center; }
                .hdr h1 { margin: 0; font-size: 24px; }
                .body { padding: 30px; }
                .code-box { background: #0b2a23; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                .code-box .code { font-family: monospace; font-size: 28px; font-weight: 700; color: #34d399; letter-spacing: 4px; }
                .btn { display: inline-block; background: #0d9e78; color: #fff; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: 600; }
                .info { font-size: 13px; color: #6b7280; margin: 10px 0; }
                .footer { padding: 20px; background: #f4f6f9; font-size: 12px; color: #9ca3af; text-align: center; }
            </style>
        </head>
        <body>
            <div class='wrap'>
                <div class='hdr'>
                    <h1>🏦 CIBIL Repair</h1>
                    <p>Your {$roleDisplay} Registration Code</p>
                </div>
                <div class='body'>
                    <p>Hello <strong>{$nameDisplay}</strong>,</p>
                    <p>You have been invited to join CIBIL Repair as a <strong>{$roleDisplay}</strong>.</p>
                    <div class='code-box'>
                        <div style='font-size:11px;opacity:0.6;text-transform:uppercase;letter-spacing:2px;color:#fff;'>Your Registration Code</div>
                        <div class='code'>{$code}</div>
                    </div>
                    <p style='text-align:center;'>
                        <a href='{$registerLink}' class='btn'>🔑 Register Now</a>
                    </p>
                    <p class='info'>⏳ This code expires on <strong>{$expiryDisplay}</strong></p>
                    <p class='info'>💡 If the button doesn't work, copy and paste this link:<br>
                    <a href='{$registerLink}' style='color:#0d9e78;word-break:break-all;'>{$registerLink}</a></p>
                </div>
                <div class='footer'>
                    © " . date('Y') . " CIBIL Repair · <a href='https://cibilrepair.in'>cibilrepair.in</a>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: CIBIL Repair <noreply@cibilrepair.in>\r\n";
        $headers .= "Reply-To: support@cibilrepair.in\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return @mail($email, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("Failed to send registration email: " . $e->getMessage());
        return false;
    }
}

/**
 * Log activity
 */
function logActivity($conn, $userId, $action, $description) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isss", $userId, $action, $description, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        // Silently fail
    }
}
?>