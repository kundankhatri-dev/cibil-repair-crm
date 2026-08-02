<?php
// get_analyses.php - Retrieve saved analyses

require_once 'config.php';

header('Content-Type: application/json');

$dbFile = DB_FILE;
$analyses = [];

if (file_exists($dbFile)) {
    $analyses = json_decode(file_get_contents($dbFile), true) ?? [];
}

// Filter by customer if requested
$customer = $_GET['customer'] ?? '';
if ($customer) {
    $analyses = array_filter($analyses, function($a) use ($customer) {
        return stripos($a['customer_name'], $customer) !== false;
    });
}

// Limit results
$limit = min($_GET['limit'] ?? 50, 100);
$analyses = array_slice($analyses, 0, $limit);

echo json_encode(['success' => true, 'analyses' => $analyses]);
?>