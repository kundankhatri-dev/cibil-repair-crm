<?php
// api/partner/test_simple.php

// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json');

// Simple test
$response = [
    'success' => true,
    'message' => 'PHP is working!',
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);
exit;
?>