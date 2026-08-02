<?php
// api/lead/get_performance.php - Get performance metrics
session_start();
header('Content-Type: application/json');

$allowed_roles = ['sales', 'bd', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Total leads
$total_query = "SELECT COUNT(*) as total FROM leads";
$total_result = mysqli_query($conn, $total_query);
$total = mysqli_fetch_assoc($total_result)['total'] ?? 1;

// Converted leads
$converted_query = "SELECT COUNT(*) as converted FROM leads WHERE stage = 'converted'";
$converted_result = mysqli_query($conn, $converted_query);
$converted = mysqli_fetch_assoc($converted_result)['converted'] ?? 0;

// Lost leads
$lost_query = "SELECT COUNT(*) as lost FROM leads WHERE stage = 'lost'";
$lost_result = mysqli_query($conn, $lost_query);
$lost = mysqli_fetch_assoc($lost_result)['lost'] ?? 0;

$lead_to_client = $total > 0 ? round(($converted / $total) * 100, 1) . '%' : '0%';
$win_rate = ($converted + $lost) > 0 ? round(($converted / ($converted + $lost)) * 100, 1) . '%' : '0%';
$leak_rate = $total > 0 ? round(($lost / $total) * 100, 1) . '%' : '0%';

// Average conversion days
$avg_days_query = "SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
                   FROM leads WHERE stage = 'converted' AND updated_at IS NOT NULL";
$avg_result = mysqli_query($conn, $avg_days_query);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_conversion_days = round($avg_data['avg_days'] ?? 0) . ' days';

// Funnel data
$funnel_query = "SELECT stage, COUNT(*) as count FROM leads GROUP BY stage";
$funnel_result = mysqli_query($conn, $funnel_query);
$funnel_labels = [];
$funnel_values = [];
$stage_order = ['new', 'contacted', 'analysis', 'proposal', 'converted'];
$stage_names = ['New Leads', 'Contacted', 'Credit Analysis', 'Proposal Sent', 'Converted'];
$stage_counts = [];

while ($row = mysqli_fetch_assoc($funnel_result)) {
    $stage_counts[$row['stage']] = (int)$row['count'];
}

foreach ($stage_order as $i => $stage) {
    $funnel_labels[] = $stage_names[$i];
    $funnel_values[] = $stage_counts[$stage] ?? 0;
}

echo json_encode([
    'success' => true,
    'lead_to_client' => $lead_to_client,
    'avg_conversion_days' => $avg_conversion_days,
    'win_rate' => $win_rate,
    'leak_rate' => $leak_rate,
    'funnel_data' => [
        'labels' => $funnel_labels,
        'values' => $funnel_values
    ]
]);

mysqli_close($conn);
?>