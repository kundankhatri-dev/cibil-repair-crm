<?php
// ============================================================
// CIBIL REPAIR CRM - Download Poster API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET HEADERS =====
header('Content-Type: application/octet-stream');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    ob_clean();
    die('Authentication required');
}

// ============================================================
// GET POSTER ID
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    ob_clean();
    die('Invalid poster ID');
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
    die('Database connection failed');
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
# GET POSTER DETAILS
// ============================================================

$query = "SELECT filename, original_name, file_path FROM posters WHERE id = ? AND deleted_at IS NULL";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$poster = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$poster) {
    ob_clean();
    die('Poster not found');
}

// ============================================================
# FIND THE FILE
// ============================================================

$found_path = null;

// Try different possible paths
$paths_to_try = [
    // 1. Stored path with public_html prefix
    __DIR__ . '/../' . $poster['file_path'],
    // 2. Remove leading slash if present
    __DIR__ . '/..' . $poster['file_path'],
    // 3. Uploads folder with filename
    __DIR__ . '/../uploads/posters/' . $poster['filename'],
    // 4. Direct uploads path (if API is in a subfolder)
    __DIR__ . '/uploads/posters/' . $poster['filename'],
    // 5. Try with public_html full path
    '/home/u929623538/domains/cibilrepair.in/public_html/' . ltrim($poster['file_path'], '/'),
    // 6. Try with document root
    $_SERVER['DOCUMENT_ROOT'] . $poster['file_path'],
    // 7. Try with document root without leading slash
    $_SERVER['DOCUMENT_ROOT'] . '/' . $poster['file_path'],
];

foreach ($paths_to_try as $path) {
    if (!empty($path) && file_exists($path)) {
        $found_path = $path;
        break;
    }
}

if (!$found_path) {
    ob_clean();
    die('File not found on server. Paths tried: ' . implode(', ', $paths_to_try));
}

// ============================================================
# GET FILE MIME TYPE
// ============================================================

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $found_path);
finfo_close($finfo);

if (!$mime_type) {
    // Fallback based on extension
    $extension = strtolower(pathinfo($poster['filename'], PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
    ];
    $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
}

// ============================================================
# FORCE DOWNLOAD
// ============================================================

// Clear any output buffers
ob_clean();

// Set headers for download
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $poster['original_name'] . '"');
header('Content-Length: ' . filesize($found_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Output the file
readfile($found_path);
exit();
?>