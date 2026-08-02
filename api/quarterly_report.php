<?php
// ============================================================
// CIBIL REPAIR CRM - Quarterly Report API
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the current quarter
$currentYear = date('Y');
$currentQuarter = ceil(date('n') / 3);

// Calculate quarter start and end dates
$quarterStart = date('Y-m-d', mktime(0, 0, 0, ($currentQuarter - 1) * 3 + 1, 1, $currentYear));
$quarterEnd = date('Y-m-t', mktime(0, 0, 0, $currentQuarter * 3, 1, $currentYear));

// ============================================================
// QUARTERLY STATISTICS
// ============================================================

// 1. Total Payments (using 'date' column instead of 'payment_date')
$paymentsSql = "SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount
FROM payments 
WHERE date BETWEEN '$quarterStart' AND '$quarterEnd'";

$paymentsResult = mysqli_query($conn, $paymentsSql);
$paymentsData = mysqli_fetch_assoc($paymentsResult);

// 2. Payments by Status
$statusSql = "SELECT 
    status, 
    COUNT(*) as count,
    SUM(amount) as total
FROM payments 
WHERE date BETWEEN '$quarterStart' AND '$quarterEnd'
GROUP BY status";

$statusResult = mysqli_query($conn, $statusSql);
$statusData = [];
while ($row = mysqli_fetch_assoc($statusResult)) {
    $statusData[] = $row;
}

// 3. Top Services
$servicesSql = "SELECT 
    service,
    COUNT(*) as count,
    SUM(amount) as total
FROM payments 
WHERE date BETWEEN '$quarterStart' AND '$quarterEnd'
GROUP BY service
ORDER BY total DESC
LIMIT 5";

$servicesResult = mysqli_query($conn, $servicesSql);
$servicesData = [];
while ($row = mysqli_fetch_assoc($servicesResult)) {
    $servicesData[] = $row;
}

// 4. Monthly breakdown for the quarter
$monthlySql = "SELECT 
    DATE_FORMAT(date, '%Y-%m') as month,
    COUNT(*) as count,
    SUM(amount) as total
FROM payments 
WHERE date BETWEEN '$quarterStart' AND '$quarterEnd'
GROUP BY DATE_FORMAT(date, '%Y-%m')
ORDER BY month";

$monthlyResult = mysqli_query($conn, $monthlySql);
$monthlyData = [];
while ($row = mysqli_fetch_assoc($monthlyResult)) {
    $monthlyData[] = $row;
}

// 5. Cases created this quarter
$casesSql = "SELECT COUNT(*) as total_cases FROM cases WHERE created_at BETWEEN '$quarterStart' AND '$quarterEnd'";
$casesResult = mysqli_query($conn, $casesSql);
$casesData = mysqli_fetch_assoc($casesResult);

// 6. New customers this quarter
$customersSql = "SELECT COUNT(*) as new_customers FROM customers WHERE joined BETWEEN '$quarterStart' AND '$quarterEnd'";
$customersResult = mysqli_query($conn, $customersSql);
$customersData = mysqli_fetch_assoc($customersResult);

// ============================================================
// QUARTERLY GROWTH CALCULATIONS
// ============================================================

// Get previous quarter data for comparison
$prevQuarterStart = date('Y-m-d', strtotime('-3 months', strtotime($quarterStart)));
$prevQuarterEnd = date('Y-m-d', strtotime('-1 day', strtotime($quarterStart)));

$prevSql = "SELECT SUM(amount) as total FROM payments WHERE date BETWEEN '$prevQuarterStart' AND '$prevQuarterEnd'";
$prevResult = mysqli_query($conn, $prevSql);
$prevData = mysqli_fetch_assoc($prevResult);
$prevTotal = $prevData['total'] ?? 0;
$currentTotal = $paymentsData['total_amount'] ?? 0;

$growthPercentage = $prevTotal > 0 ? round((($currentTotal - $prevTotal) / $prevTotal) * 100, 2) : 0;

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Quarterly report generated successfully',
    'data' => [
        'quarter' => [
            'year' => $currentYear,
            'quarter' => $currentQuarter,
            'start_date' => $quarterStart,
            'end_date' => $quarterEnd
        ],
        'summary' => [
            'total_payments' => (int)($paymentsData['total_payments'] ?? 0),
            'total_amount' => (float)($paymentsData['total_amount'] ?? 0),
            'average_amount' => (float)($paymentsData['avg_amount'] ?? 0),
            'growth_percentage' => $growthPercentage,
            'total_cases' => (int)($casesData['total_cases'] ?? 0),
            'new_customers' => (int)($customersData['new_customers'] ?? 0)
        ],
        'by_status' => $statusData,
        'top_services' => $servicesData,
        'monthly_breakdown' => $monthlyData
    ]
]);

mysqli_close($conn);
exit;
?>