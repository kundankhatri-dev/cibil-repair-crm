<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

$response = [
    'success' => true,
    'session' => [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'is_logged_in' => isset($_SESSION['user_id'])
    ]
];

echo json_encode($response);
exit;
?>