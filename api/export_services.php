<?php
header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$format = $_GET['format'] ?? 'csv';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$conn = mysqli_connect('localhost', 'user', 'pass', 'database');

$query = "SELECT * FROM services WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category) {
    $query .= " AND category = '$category'";
}
if ($status) {
    $query .= " AND status = '$status'";
}
$query .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);

$categoryLabels = [
    'credit_repair' => 'Credit Repair',
    'dispute' => 'Dispute Resolution',
    'consulting' => 'Consulting',
    'legal' => 'Legal',
    'financial' => 'Financial',
    'other' => 'Other'
];

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'ID' => $row['id'],
        'Name' => $row['name'],
        'Description' => $row['description'],
        'Category' => $categoryLabels[$row['category']] ?? $row['category'],
        'Price' => $row['price'],
        'Duration' => $row['duration'],
        'Icon' => $row['icon'],
        'Status' => ucfirst($row['status']),
        'Featured' => $row['is_featured'] ? 'Yes' : 'No',
        'Popular' => $row['is_popular'] ? 'Yes' : 'No',
        'Created' => $row['created_at']
    ];
}

// Export based on format
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="services_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
} elseif ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="services_' . date('Y-m-d') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    // Default to CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="services_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
}