<?php
// api/hr/get_holidays.php - Get all holidays
session_start();
header('Content-Type: application/json');

// Allow only HR or Admin
$allowed_roles = ['hr', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get filter parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$holiday_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Build query
$query = "SELECT 
            id,
            holiday_date,
            DATE_FORMAT(holiday_date, '%d %b %Y') as date_formatted,
            DAYNAME(holiday_date) as day_name,
            holiday_name,
            holiday_type,
            description,
            year,
            created_at
          FROM holidays
          WHERE year = ?";

$params = [$year];
$types = "i";

if ($month > 0 && $month >= 1 && $month <= 12) {
    $query .= " AND MONTH(holiday_date) = ?";
    $params[] = $month;
    $types .= "i";
}

if (!empty($holiday_type)) {
    $query .= " AND holiday_type = ?";
    $params[] = $holiday_type;
    $types .= "s";
}

$query .= " ORDER BY holiday_date ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$holidays = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get holiday types for filter
$type_query = "SELECT DISTINCT holiday_type FROM holidays";
$type_result = mysqli_query($conn, $type_query);
$holiday_types = mysqli_fetch_all($type_result, MYSQLI_ASSOC);

// Get count by type for current year
$count_by_type_query = "SELECT 
                            holiday_type,
                            COUNT(*) as count
                        FROM holidays
                        WHERE year = ?
                        GROUP BY holiday_type";
$count_stmt = mysqli_prepare($conn, $count_by_type_query);
mysqli_stmt_bind_param($count_stmt, "i", $year);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts_by_type = mysqli_fetch_all($count_result, MYSQLI_ASSOC);
mysqli_stmt_close($count_stmt);

// Get upcoming holidays (next 30 days)
$upcoming_query = "SELECT 
                    id,
                    holiday_date,
                    DATE_FORMAT(holiday_date, '%d %b %Y') as date_formatted,
                    DAYNAME(holiday_date) as day_name,
                    holiday_name,
                    holiday_type,
                    DATEDIFF(holiday_date, CURDATE()) as days_until
                  FROM holidays
                  WHERE holiday_date >= CURDATE()
                  AND holiday_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  ORDER BY holiday_date ASC";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming_holidays = mysqli_fetch_all($upcoming_result, MYSQLI_ASSOC);

// Get stats
$stats_query = "SELECT 
                    COUNT(*) as total_holidays,
                    COUNT(CASE WHEN holiday_type = 'public' THEN 1 END) as public_holidays,
                    COUNT(CASE WHEN holiday_type = 'company' THEN 1 END) as company_holidays,
                    COUNT(CASE WHEN holiday_type = 'festival' THEN 1 END) as festival_holidays,
                    COUNT(CASE WHEN MONTH(holiday_date) = MONTH(CURDATE()) THEN 1 END) as this_month
                FROM holidays
                WHERE year = ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $year);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
mysqli_stmt_close($stats_stmt);

// Format holiday types
$type_labels = [
    'public' => 'Public Holiday',
    'company' => 'Company Holiday',
    'festival' => 'Festival',
    'national' => 'National Holiday'
];

foreach ($holidays as &$h) {
    $h['type_label'] = $type_labels[$h['holiday_type']] ?? ucfirst($h['holiday_type']);
    $h['type_badge'] = $h['holiday_type'] == 'public' ? 'danger' : ($h['holiday_type'] == 'company' ? 'info' : 'warning');
}

foreach ($upcoming_holidays as &$uh) {
    $uh['type_label'] = $type_labels[$uh['holiday_type']] ?? ucfirst($uh['holiday_type']);
    $uh['type_badge'] = $uh['holiday_type'] == 'public' ? 'danger' : ($uh['holiday_type'] == 'company' ? 'info' : 'warning');
}

// Get available years (for dropdown)
$years_query = "SELECT DISTINCT YEAR(holiday_date) as year FROM holidays ORDER BY year DESC";
$years_result = mysqli_query($conn, $years_query);
$available_years = mysqli_fetch_all($years_result, MYSQLI_ASSOC);

// If no years found, add current and next year
if (empty($available_years)) {
    $available_years = [
        ['year' => date('Y')],
        ['year' => date('Y') + 1]
    ];
}

echo json_encode([
    'success' => true,
    'holidays' => $holidays,
    'upcoming_holidays' => $upcoming_holidays,
    'holiday_types' => $holiday_types,
    'counts_by_type' => $counts_by_type,
    'stats' => [
        'total_holidays' => (int)($stats['total_holidays'] ?? 0),
        'public_holidays' => (int)($stats['public_holidays'] ?? 0),
        'company_holidays' => (int)($stats['company_holidays'] ?? 0),
        'festival_holidays' => (int)($stats['festival_holidays'] ?? 0),
        'this_month' => (int)($stats['this_month'] ?? 0)
    ],
    'available_years' => $available_years,
    'current_year' => $year,
    'filters' => [
        'year' => $year,
        'month' => $month,
        'type' => $holiday_type
    ],
    'month_names' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
]);

mysqli_close($conn);
?>