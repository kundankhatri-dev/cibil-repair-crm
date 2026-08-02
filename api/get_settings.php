<?php
// ============================================================
// CIBIL REPAIR CRM - Get Settings API
// Endpoint: /api/get_settings.php
// Method: GET, POST
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// HANDLE GET REQUEST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';

    // Create settings table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            category VARCHAR(50) NOT NULL DEFAULT 'general',
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(20) DEFAULT 'string',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_category_key (category, setting_key),
            INDEX idx_category (category),
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    mysqli_query($conn, $createTableSql);

    // Insert default settings if empty
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM settings");
    $countRow = mysqli_fetch_assoc($countResult);
    if ($countRow && $countRow['count'] == 0) {
        $defaults = [
            ['general', 'company_name', 'CIBIL Repair', 'string', 'Company name'],
            ['general', 'company_email', 'contact@cibilrepair.in', 'email', 'Company email'],
            ['general', 'company_phone', '+91 87094 55441', 'phone', 'Company phone'],
            ['general', 'company_website', 'https://cibilrepair.in', 'url', 'Company website'],
            ['general', 'company_address', 'Mumbai, India', 'string', 'Company address'],
            ['general', 'gst_rate', '18', 'string', 'GST rate percentage'],
            ['general', 'currency', 'INR', 'string', 'Currency code'],
            ['general', 'timezone', 'Asia/Kolkata', 'string', 'Timezone'],
            ['general', 'items_per_page', '20', 'integer', 'Items per page'],
            ['finance', 'default_commission_rate', '10', 'integer', 'Default commission rate'],
            ['notification', 'email_notifications', 'true', 'boolean', 'Enable email notifications']
        ];
        
        foreach ($defaults as $d) {
            $sql = "INSERT INTO settings (category, setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sssss', $d[0], $d[1], $d[2], $d[3], $d[4]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Build query
    $where = [];
    $params = [];
    $types = '';

    if (!empty($category)) {
        $where[] = "category = ?";
        $params[] = $category;
        $types .= 's';
    }

    if (!empty($key)) {
        $where[] = "setting_key = ?";
        $params[] = $key;
        $types .= 's';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT * FROM settings $whereClause ORDER BY category, setting_key";
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Format response
    $flatSettings = [];
    $groupedSettings = [];

    foreach ($settings as $s) {
        $value = $s['setting_value'];
        $type = $s['setting_type'];
        
        if ($type === 'integer') $value = intval($value);
        elseif ($type === 'boolean') $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        elseif ($type === 'json') $value = json_decode($value, true);
        elseif ($type === 'array') $value = !empty($value) ? explode(',', $value) : [];
        
        $settingData = [
            'id' => intval($s['id']),
            'category' => $s['category'],
            'key' => $s['setting_key'],
            'value' => $value,
            'type' => $type,
            'description' => $s['description'],
            'updated_at' => $s['updated_at']
        ];
        
        $flatSettings[$s['setting_key']] = $settingData;
        $groupedSettings[$s['category']][] = $settingData;
    }

    // Get categories
    $catResult = mysqli_query($conn, "SELECT DISTINCT category FROM settings ORDER BY category");
    $categories = [];
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row['category'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'categories' => $categories,
            'settings' => $flatSettings,
            'settings_grouped' => $groupedSettings,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// HANDLE POST REQUEST (Save Settings)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $key = isset($input['key']) ? trim($input['key']) : '';
    $value = isset($input['value']) ? $input['value'] : '';
    $category = isset($input['category']) ? trim($input['category']) : 'general';
    $type = isset($input['type']) ? trim($input['type']) : 'string';
    $description = isset($input['description']) ? trim($input['description']) : '';

    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Setting key is required']);
        exit;
    }

    // Check if setting exists
    $checkSql = "SELECT id FROM settings WHERE setting_key = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, 's', $key);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);

    if ($existing) {
        // Update
        $updateSql = "UPDATE settings SET setting_value = ?, setting_type = ?, description = ?, updated_at = NOW() WHERE setting_key = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, 'ssss', $value, $type, $description, $key);
        mysqli_stmt_execute($updateStmt);
        $affected = mysqli_stmt_affected_rows($updateStmt);
        mysqli_stmt_close($updateStmt);

        echo json_encode([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => ['key' => $key, 'value' => $value]
        ]);
    } else {
        // Insert
        $insertSql = "INSERT INTO settings (category, setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);
        mysqli_stmt_bind_param($insertStmt, 'sssss', $category, $key, $value, $type, $description);
        mysqli_stmt_execute($insertStmt);
        $affected = mysqli_stmt_affected_rows($insertStmt);
        mysqli_stmt_close($insertStmt);

        echo json_encode([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => ['key' => $key, 'value' => $value]
        ]);
    }
    mysqli_close($conn);
    exit;
}

// ============================================================
# INVALID METHOD
// ============================================================

echo json_encode(['success' => false, 'error' => 'Invalid request method. Use GET or POST.']);
mysqli_close($conn);
?>