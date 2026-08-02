<?php
// ============================================================
// CIBIL REPAIR CRM - Get System Settings API
// Endpoint: /api/get_system_settings.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

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

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ============================================================
// CHECK/CREATE TABLE
// ============================================================

// Drop existing table to recreate with correct columns
mysqli_query($conn, "DROP TABLE IF EXISTS system_settings");

// Create table with correct columns
$createTable = "
    CREATE TABLE system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type VARCHAR(20) DEFAULT 'string',
        category VARCHAR(50) DEFAULT 'general',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $createTable);

// Insert defaults
$defaults = [
    ['site_name', 'CIBIL Repair', 'string', 'general', 'Website name'],
    ['site_tagline', 'Better Credit. Better Future.', 'string', 'general', 'Website tagline'],
    ['site_email', 'contact@cibilrepair.in', 'string', 'general', 'Contact email'],
    ['site_phone', '+91 87094 55441', 'string', 'general', 'Contact phone'],
    ['site_address', 'Delhi NCR, India', 'string', 'general', 'Office address'],
    ['maintenance_mode', '0', 'boolean', 'general', 'Maintenance mode'],
    ['gst_rate', '18', 'integer', 'general', 'GST rate'],
    ['commission_rate', '10', 'integer', 'general', 'Commission rate'],
    ['items_per_page', '20', 'integer', 'general', 'Items per page']
];

foreach ($defaults as $d) {
    $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $d[0], $d[1], $d[2], $d[3], $d[4]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ============================================================
// GET PARAMETERS
// ============================================================

$key = isset($_GET['key']) ? trim($_GET['key']) : '';

// ============================================================
# GET SETTINGS
// ============================================================

$sql = "SELECT setting_key, setting_value, setting_type FROM system_settings";
if (!empty($key)) {
    $sql .= " WHERE setting_key = '$key'";
}
$result = mysqli_query($conn, $sql);

$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $value = $row['setting_value'];
    $type = $row['setting_type'];
    if ($type === 'integer') {
        $value = intval($value);
    } elseif ($type === 'boolean') {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    $settings[$row['setting_key']] = $value;
}

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'System settings retrieved successfully',
    'data' => [
        'settings' => $settings,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>