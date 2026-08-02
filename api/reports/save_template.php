<?php
// api/reports/save_template.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$report_type = $data['report_type'] ?? 'leads';
$columns = json_encode($data['columns'] ?? []);
$filters = json_encode($data['filters'] ?? []);
$date_range = $data['date_range'] ?? 'month';
$is_favorite = isset($data['is_favorite']) ? (int)$data['is_favorite'] : 0;

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Template name is required']);
    exit;
}

$query = "INSERT INTO report_templates (partner_id, name, report_type, columns, filters, date_range, is_favorite) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "isssssi", $partner_id, $name, $report_type, $columns, $filters, $date_range, $is_favorite);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Template saved successfully',
        'template_id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>