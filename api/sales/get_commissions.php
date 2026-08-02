<?php
// api/sales/get_commissions.php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

// Check authentication
$allowed_roles = ['sales_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// If admin/manager, can view all; otherwise only own
if ($user_role == 'admin' || $user_role == 'manager') {
    $emp_condition = $employee_id ? "AND sc.sales_person_id = $employee_id" : "";
} else {
    $emp_condition = "AND sc.sales_person_id = (SELECT id FROM employees WHERE user_id = $user_id)";
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get commission summary
$stmt = $conn->prepare("SELECT 
    COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as paid,
    COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending,
    COALESCE(SUM(commission_amount), 0) as total
    FROM sales_commissions sc
    WHERE 1=1 $emp_condition");
$stmt->execute();
$summary = $stmt->fetch();

// Get commission details with lead/client info
$sql = "SELECT sc.*, sl.client_name, sl.service_interest 
        FROM sales_commissions sc
        LEFT JOIN sales_leads sl ON sc.lead_id = sl.id
        WHERE 1=1 $emp_condition
        ORDER BY sc.sale_date DESC, sc.created_at DESC";

$commissions = [];
$stmt = $conn->query($sql);
while ($row = $stmt->fetch()) {
    $commissions[] = $row;
}

echo json_encode([
    'success' => true,
    'total' => $summary['total'] ?? 0,
    'paid' => $summary['paid'] ?? 0,
    'pending' => $summary['pending'] ?? 0,
    'commissions' => $commissions
]);
?>