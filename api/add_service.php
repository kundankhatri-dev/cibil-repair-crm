<?php
header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$price = $data['price'] ?? 0;
$category = $data['category'] ?? 'other';
$duration = $data['duration'] ?? '30-45 days';
$icon = $data['icon'] ?? '⭐';
$status = $data['status'] ?? 'active';
$is_featured = $data['is_featured'] ?? 0;
$is_popular = $data['is_popular'] ?? 0;

if (!$name || !$description || !$price) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$conn = mysqli_connect('localhost', 'user', 'pass', 'database');

$query = "INSERT INTO services (name, description, price, category, duration, icon, status, is_featured, is_popular, created_at) 
          VALUES ('$name', '$description', $price, '$category', '$duration', '$icon', '$status', $is_featured, $is_popular, NOW())";

if (mysqli_query($conn, $query)) {
    $id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'data' => ['id' => $id]]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}