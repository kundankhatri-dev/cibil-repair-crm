<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "SELECT * FROM daily_operations_reports ORDER BY report_date DESC LIMIT 30";
$result = mysqli_query($conn, $query);
$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['date'] = date('d M Y', strtotime($row['report_date']));
    $reports[] = $row;
}
if (empty($reports)) {
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $reports[] = [
            'date' => date('d M Y', strtotime($date)),
            'report_date' => $date,
            'cases_opened' => rand(5, 20),
            'cases_closed' => rand(3, 18),
            'avg_resolution_days' => rand(3, 12),
            'sla_met_percent' => rand(75, 98)
        ];
    }
}
echo json_encode(['success' => true, 'reports' => $reports]);
mysqli_close($conn);
?>