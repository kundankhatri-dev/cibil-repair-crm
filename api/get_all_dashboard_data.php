<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Single query to get all data
$data = [];

if ($role === 'admin') {
    // Admin stats
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'client'");
    $data['total_customers'] = mysqli_fetch_assoc($result)['count'];
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM banks");
    $data['total_banks'] = mysqli_fetch_assoc($result)['count'];
    
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM quotations");
    $data['total_sales'] = mysqli_fetch_assoc($result)['total'];
}

echo json_encode(['success' => true, 'data' => $data]);
?>