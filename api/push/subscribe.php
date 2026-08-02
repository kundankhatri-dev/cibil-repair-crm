<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$endpoint = $input['endpoint'] ?? '';
$keys = $input['keys'] ?? [];

if (empty($endpoint) || empty($keys)) {
    echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
    exit;
}

$p256dh = $keys['p256dh'] ?? '';
$auth = $keys['auth'] ?? '';

// Check if subscription exists
$check = mysqli_prepare($conn, "SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
mysqli_stmt_bind_param($check, "is", $user_id, $endpoint);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    // Update existing
    $stmt = mysqli_prepare($conn, "UPDATE push_subscriptions SET p256dh = ?, auth = ?, updated_at = NOW() WHERE user_id = ? AND endpoint = ?");
    mysqli_stmt_bind_param($stmt, "ssis", $p256dh, $auth, $user_id, $endpoint);
} else {
    // Insert new
    $stmt = mysqli_prepare($conn, "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $endpoint, $p256dh, $auth);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Subscribed successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>