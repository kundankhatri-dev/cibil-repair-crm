<?php
// ============================================================
// GET USER ROLE
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'user_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
        'is_admin' => in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])
    ]
]);
exit;
?>