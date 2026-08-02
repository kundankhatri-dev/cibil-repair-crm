<?php
require_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Initialize loyalty points for user
$init = mysqli_prepare($conn, "INSERT INTO loyalty_points (user_id, points, total_earned, total_redeemed) VALUES (?, 0, 0, 0) ON DUPLICATE KEY UPDATE points = points");
mysqli_stmt_bind_param($init, "i", $user_id);
mysqli_stmt_execute($init);
mysqli_stmt_close($init);

// Get points balance
$points_query = mysqli_prepare($conn, "SELECT points, total_earned, total_redeemed FROM loyalty_points WHERE user_id = ?");
mysqli_stmt_bind_param($points_query, "i", $user_id);
mysqli_stmt_execute($points_query);
$points_result = mysqli_stmt_get_result($points_query);
$points = mysqli_fetch_assoc($points_result);
mysqli_stmt_close($points_query);

// Get points history
$history_query = mysqli_prepare($conn, "SELECT points, type, description, created_at FROM loyalty_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
mysqli_stmt_bind_param($history_query, "i", $user_id);
mysqli_stmt_execute($history_query);
$history_result = mysqli_stmt_get_result($history_query);
$history = mysqli_fetch_all($history_result, MYSQLI_ASSOC);
mysqli_stmt_close($history_query);

echo json_encode([
    'success' => true,
    'balance' => (int)$points['points'],
    'total_earned' => (int)$points['total_earned'],
    'total_redeemed' => (int)$points['total_redeemed'],
    'history' => $history
]);

mysqli_close($conn);
?>