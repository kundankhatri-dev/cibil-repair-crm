<?php
// ============================================================
// CIBIL REPAIR CRM - Get Dashboard Stats API (SIMPLIFIED)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET STATS - SIMPLE QUERIES
// ============================================================

try {
    $stats = [];

    // 1. Total Customers
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers");
    $stats['total_customers'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 2. Total Partners
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM partners");
    $stats['total_partners'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 3. Total Banks
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM banks");
    $stats['total_banks'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 4. Total Revenue
    $r = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM sales");
    $stats['total_revenue'] = $r ? (float)mysqli_fetch_assoc($r)['total'] : 0;

    // 5. Total Sales
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales");
    $stats['total_sales'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 6. Total Leads
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads");
    $stats['total_leads'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 7. Total Quotations
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM quotations");
    $stats['total_quotations'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 8. Total Expenses
    $r = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM expenses");
    $stats['total_expenses'] = $r ? (float)mysqli_fetch_assoc($r)['total'] : 0;

    // 9. Wallet Balance
    $r = mysqli_query($conn, "SELECT balance FROM wallet WHERE user_id = " . (int)$_SESSION['user_id']);
    $wallet = mysqli_fetch_assoc($r);
    $stats['wallet_balance'] = $wallet ? (float)$wallet['balance'] : 0;

    // 10. Total Services
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
    $stats['total_services'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 11. Total Reviews
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM reviews");
    $stats['total_reviews'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 12. Average Rating
    $r = mysqli_query($conn, "SELECT IFNULL(AVG(rating), 0) as avg FROM reviews");
    $stats['avg_rating'] = $r ? round((float)mysqli_fetch_assoc($r)['avg'], 1) : 0;

    // 13. Total Posters
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM posters");
    $stats['total_posters'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 14. Total Users
    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $r ? (int)mysqli_fetch_assoc($r)['total'] : 0;

    // 15. Monthly Revenue Chart (Last 6 Months)
    $chartData = [];
    $chartResult = mysqli_query($conn, "SELECT DATE_FORMAT(sale_date, '%b') as month, DATE_FORMAT(sale_date, '%Y-%m') as month_key, IFNULL(SUM(amount), 0) as total FROM sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_key ORDER BY sale_date ASC");
    if ($chartResult) {
        while ($row = mysqli_fetch_assoc($chartResult)) {
            $chartData[] = $row;
        }
    }

    $stats['revenue_chart'] = ['labels' => [], 'values' => []];
    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        $found = false;
        foreach ($chartData as $row) {
            if ($row['month_key'] === $monthKey) {
                $stats['revenue_chart']['labels'][] = $row['month'];
                $stats['revenue_chart']['values'][] = (float)$row['total'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $stats['revenue_chart']['labels'][] = $monthLabel;
            $stats['revenue_chart']['values'][] = 0;
        }
    }

    // 16. Revenue by Service
    $serviceData = [];
    $serviceResult = mysqli_query($conn, "SELECT service, IFNULL(SUM(amount), 0) as total FROM sales WHERE service IS NOT NULL AND service != '' GROUP BY service ORDER BY total DESC");
    if ($serviceResult) {
        while ($row = mysqli_fetch_assoc($serviceResult)) {
            $serviceData[] = $row;
        }
    }

    $stats['revenue_by_service'] = ['labels' => [], 'values' => []];
    foreach ($serviceData as $row) {
        $stats['revenue_by_service']['labels'][] = $row['service'];
        $stats['revenue_by_service']['values'][] = (float)$row['total'];
    }

    // 17. Recent Activity
    $recentActivity = [];
    $activityResult = mysqli_query($conn, "SELECT user_name, action, details, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 5");
    if ($activityResult) {
        while ($row = mysqli_fetch_assoc($activityResult)) {
            $recentActivity[] = $row;
        }
    }
    $stats['recent_activity'] = $recentActivity;

    // ============================================================
    // RESPONSE
    // ============================================================

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching stats: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>