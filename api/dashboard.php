<?php
// ============================================================
// CIBIL REPAIR CRM - Dashboard API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// GET DASHBOARD DATA
// ============================================================

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'getStats') {
    // ============================================================
    // STATISTICS
    // ============================================================
    
    // Total Customers
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers");
    $totalCustomers = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Active Customers
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $activeCustomers = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Partners
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM partners");
    $totalPartners = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Active Partners
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM partners WHERE status = 'active'");
    $activePartners = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Banks
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM banks");
    $totalBanks = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Leads
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads");
    $totalLeads = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // New Leads (last 7 days)
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $newLeads = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Converted Leads
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads WHERE status = 'converted'");
    $convertedLeads = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Sales
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales");
    $totalSales = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Revenue
    $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM sales WHERE status = 'Completed' OR status = 'completed'");
    $totalRevenue = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Expenses
    $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses");
    $totalExpenses = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Net Profit
    $netProfit = $totalRevenue - $totalExpenses;
    
    // Total Transactions
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions");
    $totalTransactions = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Wallet Balance
    $result = mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1");
    $walletBalance = mysqli_fetch_assoc($result)['balance'] ?? 0;
    
    // Total Quotations
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM quotations");
    $totalQuotations = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Pending Quotations
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM quotations WHERE status = 'Draft' OR status = 'Sent'");
    $pendingQuotations = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Customer Requests
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_requests");
    $totalRequests = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Pending Requests
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_requests WHERE status = 'pending'");
    $pendingRequests = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Cases
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM cases");
    $totalCases = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Active Cases
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM cases WHERE status = 'active' OR status = 'in_progress'");
    $activeCases = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // ============================================================
    // RECENT ACTIVITY
    // ============================================================
    
    $recentActivity = [];
    $result = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $recentActivity[] = $row;
    }
    
    // ============================================================
    // CHARTS DATA
    // ============================================================
    
    // Revenue by Month (last 6 months)
    $revenueByMonth = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M', strtotime("-$i months"));
        $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM sales 
                                       WHERE status IN ('Completed', 'completed') 
                                       AND DATE_FORMAT(sale_date, '%Y-%m') = '$month'");
        $total = mysqli_fetch_assoc($result)['total'] ?? 0;
        $revenueByMonth[] = ['month' => $monthName, 'revenue' => floatval($total)];
    }
    
    // Leads by Status
    $leadsByStatus = [];
    $result = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM leads GROUP BY status");
    while ($row = mysqli_fetch_assoc($result)) {
        $leadsByStatus[] = $row;
    }
    
    // Sales by Service
    $salesByService = [];
    $result = mysqli_query($conn, "SELECT service, COUNT(*) as count, SUM(amount) as total FROM sales GROUP BY service");
    while ($row = mysqli_fetch_assoc($result)) {
        $salesByService[] = $row;
    }
    
    // ============================================================
    // RECENT SALES
    // ============================================================
    
    $recentSales = [];
    $result = mysqli_query($conn, "SELECT * FROM sales ORDER BY id DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $recentSales[] = $row;
    }
    
    // ============================================================
    // RECENT LEADS
    // ============================================================
    
    $recentLeads = [];
    $result = mysqli_query($conn, "SELECT * FROM leads ORDER BY id DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $recentLeads[] = $row;
    }
    
    // ============================================================
    // RESPONSE
    // ============================================================
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'total_customers' => (int)$totalCustomers,
                'active_customers' => (int)$activeCustomers,
                'total_partners' => (int)$totalPartners,
                'active_partners' => (int)$activePartners,
                'total_banks' => (int)$totalBanks,
                'total_leads' => (int)$totalLeads,
                'new_leads' => (int)$newLeads,
                'converted_leads' => (int)$convertedLeads,
                'total_sales' => (int)$totalSales,
                'total_revenue' => floatval($totalRevenue),
                'total_expenses' => floatval($totalExpenses),
                'net_profit' => floatval($netProfit),
                'total_transactions' => (int)$totalTransactions,
                'wallet_balance' => floatval($walletBalance),
                'total_quotations' => (int)$totalQuotations,
                'pending_quotations' => (int)$pendingQuotations,
                'total_requests' => (int)$totalRequests,
                'pending_requests' => (int)$pendingRequests,
                'total_cases' => (int)$totalCases,
                'active_cases' => (int)$activeCases
            ],
            'recent_activity' => $recentActivity,
            'charts' => [
                'revenue_by_month' => $revenueByMonth,
                'leads_by_status' => $leadsByStatus,
                'sales_by_service' => $salesByService
            ],
            'recent_sales' => $recentSales,
            'recent_leads' => $recentLeads,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// ============================================================
// GET RECENT ACTIVITY
// ============================================================

if ($action === 'getRecentActivity') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $result = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT $limit");
    $activity = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $activity[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $activity]);
    exit;
}

// ============================================================
// GET REVENUE CHART DATA
// ============================================================

if ($action === 'getRevenueChart') {
    $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, yearly
    $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
    
    $data = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M', strtotime("-$i months"));
        $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM sales 
                                       WHERE status IN ('Completed', 'completed') 
                                       AND DATE_FORMAT(sale_date, '%Y-%m') = '$month'");
        $total = mysqli_fetch_assoc($result)['total'] ?? 0;
        $data[] = ['month' => $monthName, 'revenue' => floatval($total)];
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ============================================================
// GET LEADS BY STATUS
// ============================================================

if ($action === 'getLeadsByStatus') {
    $result = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM leads GROUP BY status");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ============================================================
// DEFAULT RESPONSE
// ============================================================

echo json_encode([
    'success' => false,
    'error' => 'Invalid action',
    'available_actions' => [
        'getStats',
        'getRecentActivity',
        'getRevenueChart',
        'getLeadsByStatus'
    ]
]);

mysqli_close($conn);
exit;
?>