<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized access');
}

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'City', 'Service', 'Status', 'Joined Date']);

$result = $conn->query("SELECT id, name, email, phone, city, service, status, DATE(created_at) as joined FROM customers ORDER BY id DESC");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'], $row['name'], $row['email'], $row['phone'],
        $row['city'], $row['service'], $row['status'], $row['joined']
    ]);
}

fclose($output);
exit;
?>