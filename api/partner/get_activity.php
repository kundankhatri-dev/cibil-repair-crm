<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : $_SESSION['user_id'];

// Return sample activity data
$activities = [
    [
        'activity' => 'Welcome to Partner Dashboard',
        'customer' => 'System',
        'date' => date('Y-m-d H:i:s'),
        'status' => 'success',
        'amount' => null
    ],
    [
        'activity' => 'Start adding leads to earn commission',
        'customer' => 'System',
        'date' => date('Y-m-d H:i:s'),
        'status' => 'info',
        'amount' => null
    ]
];

echo json_encode(['success' => true, 'activities' => $activities]);
?>