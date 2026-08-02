<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ============================================================
// SESSION CHECK
// ============================================================
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$partner_id = isset($input['partner_id']) ? (int)$input['partner_id'] : 0;
$email = isset($input['email']) ? trim($input['email']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

// Validate
if (!$partner_id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID is required']);
    exit;
}

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (!$name) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

try {
    // Check if partner exists
    $checkPartner = $pdo->prepare("SELECT id, name, user_id FROM partners WHERE id = ?");
    $checkPartner->execute([$partner_id]);
    $partner = $checkPartner->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        echo json_encode(['success' => false, 'error' => 'Partner not found']);
        exit;
    }
    
    if (!empty($partner['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Partner already linked to user ID: ' . $partner['user_id']]);
        exit;
    }
    
    // Check if user exists
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkUser->execute([$email]);
    $existingUser = $checkUser->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // Link to existing user
        $update = $pdo->prepare("UPDATE partners SET user_id = ? WHERE id = ?");
        $update->execute([$existingUser['id'], $partner_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Partner linked to existing user'
        ]);
    } else {
        // Create new user
        $password = 'password123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at) 
                                 VALUES (?, ?, ?, ?, 'partner', 'active', NOW())");
        $insert->execute([$name, $email, $phone, $hashed_password]);
        $user_id = $pdo->lastInsertId();
        
        // Link partner to new user
        $update = $pdo->prepare("UPDATE partners SET user_id = ? WHERE id = ?");
        $update->execute([$user_id, $partner_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'New user created! Email: ' . $email . ', Password: ' . $password
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>