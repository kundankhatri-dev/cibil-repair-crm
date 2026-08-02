<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$report_type = $_GET['type'] ?? 'campaigns';
$format = $_GET['format'] ?? 'json';

$data = [];

if ($report_type == 'campaigns') {
    $query = "SELECT * FROM marketing_campaigns ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} elseif ($report_type == 'leads') {
    $query = "SELECT * FROM lead_sources";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} elseif ($report_type == 'social') {
    $query = "SELECT * FROM social_media_posts ORDER BY post_date DESC LIMIT 100";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} elseif ($report_type == 'email') {
    $query = "SELECT * FROM email_campaigns ORDER BY sent_date DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="marketing_report.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
} else {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
        'export_date' => date('Y-m-d H:i:s')
    ]);
}
?>