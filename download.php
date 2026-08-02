<?php
// ============================================================
// public_html/download.php
// SECURE FILE DOWNLOAD
// ============================================================

// Get the file name from URL
$file = basename($_GET['file'] ?? '');
if (empty($file)) {
    die('No file specified');
}

// Security: Only allow safe characters
$file = preg_replace('/[^a-zA-Z0-9_.-]/', '', $file);

// Define the file path
$path = __DIR__ . '/uploads/partner_docs/' . $file;

// Check if file exists
if (!file_exists($path)) {
    die('File not found');
}

// Get file extension
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// Set proper MIME type
$mime_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];
$mime = $mime_types[$ext] ?? 'application/octet-stream';

// Set headers for download
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

// Output the file
readfile($path);
exit;
?>