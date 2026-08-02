<?php
// ============================================================
// CIBIL REPAIR CRM - Download Backup API
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    die('Unauthorized');
}

$file = isset($_GET['file']) ? trim($_GET['file']) : '';

if (empty($file)) {
    http_response_code(400);
    die('File name required');
}

$backupDir = __DIR__ . '/../backups/';
$filepath = $backupDir . basename($file);

if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Security: Only allow specific extensions
$allowedExtensions = ['sql', 'gz', 'zip', 'json', 'csv'];
$extension = pathinfo($filepath, PATHINFO_EXTENSION);
if (!in_array($extension, $allowedExtensions)) {
    http_response_code(403);
    die('File type not allowed');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filepath);
exit;
?>