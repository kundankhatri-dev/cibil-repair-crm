<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'API is working correctly',
    'api_version' => '2.0.0',
    'server_time' => date('Y-m-d H:i:s'),
    'available_endpoints' => [
        'login' => '/api/partner/login.php',
        'register' => '/api/partner/register.php'
    ]
]);
?>