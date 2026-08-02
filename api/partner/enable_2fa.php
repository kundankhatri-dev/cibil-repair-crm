<?php
// api/partner/enable_2fa.php
// Enable Two-Factor Authentication

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'setup';

// Generate secret for 2FA
function generate2FASecret() {
    return bin2hex(random_bytes(20));
}

// Create 2FA table if not exists
$twofaTable = 'partner_2fa';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$twofaTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $twofaTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL UNIQUE,
        secret_key VARCHAR(255),
        is_enabled TINYINT(1) DEFAULT 0,
        backup_codes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

if ($action === 'setup') {
    // Generate new secret
    $secret = generate2FASecret();
    $backup_codes = [];
    for ($i = 0; $i < 5; $i++) {
        $backup_codes[] = bin2hex(random_bytes(4));
    }
    
    // Check if already exists
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM $twofaTable WHERE partner_id = ?");
    mysqli_stmt_bind_param($check_stmt, "i", $partner_id);
    mysqli_stmt_execute($check_stmt);
    $exists = mysqli_stmt_num_rows($check_stmt) > 0;
    
    if ($exists) {
        $update_stmt = mysqli_prepare($conn, "UPDATE $twofaTable SET secret_key = ?, backup_codes = ? WHERE partner_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ssi", $secret, json_encode($backup_codes), $partner_id);
        mysqli_stmt_execute($update_stmt);
    } else {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO $twofaTable (partner_id, secret_key, backup_codes) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "iss", $partner_id, $secret, json_encode($backup_codes));
        mysqli_stmt_execute($insert_stmt);
    }
    
    // Generate QR code URL (using Google Charts API)
    $appName = "CIBIL Repair";
    $email = $_SESSION['user_email'] ?? '';
    $qrUrl = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=otpauth://totp/$appName:$email?secret=$secret&issuer=$appName";
    
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code_url' => $qrUrl,
        'backup_codes' => $backup_codes,
        'message' => 'Scan QR code with Google Authenticator app'
    ]);
    
} elseif ($action === 'enable') {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = $data['code'] ?? '';
    
    // Verify code (simplified - use proper TOTP verification in production)
    if (strlen($code) == 6 && is_numeric($code)) {
        $update_stmt = mysqli_prepare($conn, "UPDATE $twofaTable SET is_enabled = 1 WHERE partner_id = ?");
        mysqli_stmt_bind_param($update_stmt, "i", $partner_id);
        mysqli_stmt_execute($update_stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid verification code'
        ]);
    }
    
} elseif ($action === 'disable') {
    $update_stmt = mysqli_prepare($conn, "UPDATE $twofaTable SET is_enabled = 0 WHERE partner_id = ?");
    mysqli_stmt_bind_param($update_stmt, "i", $partner_id);
    mysqli_stmt_execute($update_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Two-factor authentication disabled'
    ]);
}

mysqli_close($conn);
?>