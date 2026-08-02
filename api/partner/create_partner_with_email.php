<?php
// ============================================================
// CREATE PARTNER WITH EMAIL NOTIFICATION
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include the email function
require_once 'send_welcome_email.php';

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$location = trim($data['location'] ?? '');
$owner = trim($data['owner'] ?? '');
$commission_rate = floatval($data['commission_rate'] ?? 25);
$tier = intval($data['tier'] ?? 2);
$status = $data['status'] ?? 'active';

// Validate
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Business name is required']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Generate password
    function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    $password = generateRandomPassword(12);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert into users
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, 
            email, 
            phone, 
            password, 
            role, 
            status, 
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, 'partner', ?, NOW(), NOW())
    ");
    $stmt->execute([$name, $email, $phone, $hashed_password, $status]);
    $user_id = $pdo->lastInsertId();
    
    // Tier level mapping
    $tier_levels = ['bronze', 'silver', 'gold', 'platinum', 'diamond'];
    $tier_level = $tier_levels[$tier - 1] ?? 'bronze';
    
    // Insert into partners
    $stmt = $pdo->prepare("
        INSERT INTO partners (
            user_id,
            name,
            email,
            phone,
            status,
            commission_rate,
            tier,
            tier_level,
            base_commission_rate,
            current_commission_rate,
            allow_payouts,
            allow_referrals,
            location,
            owner,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $name,
        $email,
        $phone,
        $status,
        $commission_rate,
        $tier,
        $tier_level,
        $commission_rate,
        $commission_rate,
        $location,
        $owner
    ]);
    
    $partner_id = $pdo->lastInsertId();
    
    // Commit transaction
    $pdo->commit();
    
    // Send welcome email
    $email_sent = sendPartnerWelcomeEmail($email, $name, $password, $partner_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Partner created successfully! Welcome email sent.',
        'data' => [
            'user_id' => $user_id,
            'partner_id' => $partner_id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_sent' => $email_sent,
            'commission_rate' => $commission_rate,
            'tier' => $tier,
            'tier_level' => $tier_level
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>