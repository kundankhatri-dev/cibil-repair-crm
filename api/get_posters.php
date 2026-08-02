<?php
// ============================================================
// CIBIL REPAIR CRM - Get Posters API (FIXED)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
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
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
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
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

// ============================================================
// CHECK IF POSTERS TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'posters'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    // Create posters table if not exists
    $createTable = "
        CREATE TABLE posters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_filename (filename),
            INDEX idx_deleted (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    mysqli_query($conn, $createTable);
    
    // Insert sample poster
    $sampleSql = "
        INSERT INTO posters (filename, original_name, file_path, file_size, created_at) VALUES
        ('sample_poster.jpg', 'Sample Poster', '/uploads/posters/sample_poster.jpg', 102400, NOW())
    ";
    mysqli_query($conn, $sampleSql);
}

// ============================================================
// GET POSTERS
// ============================================================

$query = "SELECT * FROM posters WHERE deleted_at IS NULL ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit();
}

$posters = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format file size
    $fileSize = intval($row['file_size'] ?? 0);
    if ($fileSize >= 1048576) {
        $sizeFormatted = number_format($fileSize / 1048576, 2) . ' MB';
    } elseif ($fileSize >= 1024) {
        $sizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
    } else {
        $sizeFormatted = $fileSize . ' bytes';
    }
    
    // Ensure correct file path for web display
    $filePath = $row['file_path'] ?? '';
    
    // Fix path (compatible with PHP 7.x)
    if (empty($filePath)) {
        $filePath = '/uploads/posters/' . $row['filename'];
    } elseif (substr($filePath, 0, 1) !== '/') {
        // Check if it's a relative path like 'uploads/posters/file.jpg'
        if (substr($filePath, 0, 8) === 'uploads/') {
            $filePath = '/' . $filePath;
        } else {
            $filePath = '/uploads/posters/' . $row['filename'];
        }
    }
    
    $posters[] = [
        'id' => intval($row['id']),
        'filename' => $row['filename'],
        'original_name' => $row['original_name'],
        'file_path' => $filePath,
        'file_size' => $fileSize,
        'file_size_formatted' => $sizeFormatted,
        'created_at' => $row['created_at'],
        'formatted_date' => date('d M Y, h:i A', strtotime($row['created_at']))
    ];
}

mysqli_close($conn);

// ============================================================
// RETURN SUCCESS RESPONSE
// ============================================================

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Posters retrieved successfully',
    'total' => count($posters),
    'data' => $posters
]);
exit();
?>