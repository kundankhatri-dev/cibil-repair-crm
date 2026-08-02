<?php
// api/reports/get_templates.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

$query = "SELECT * FROM report_templates WHERE partner_id = ? OR partner_id = 0 ORDER BY is_favorite DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$templates = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'templates' => $templates,
    'total' => count($templates)
]);

mysqli_close($conn);
?>