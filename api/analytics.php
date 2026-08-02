<?php
// /api/analytics.php
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    global $conn;
    
    if (!isset($conn) || !$conn instanceof mysqli) {
        if (defined('DB_HOST')) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
        } else {
            throw new Exception('Database connection not available');
        }
    }
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'overview';
    $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
    
    $response = ['success' => true];
    
    switch ($type) {
        case 'overview':
            // Total counts
            $counts = [];
            $tables = ['partners', 'customers', 'leads', 'reviews', 'services', 'followups'];
            foreach ($tables as $table) {
                $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $counts[$table] = $result->fetch_assoc()['count'];
            }
            $response['counts'] = $counts;
            break;
            
        case 'revenue':
            // Revenue analytics
            $result = $conn->query("SELECT 
                SUM(amount) as total_revenue,
                AVG(amount) as avg_amount,
                COUNT(*) as total_transactions,
                MONTH(created_at) as month,
                YEAR(created_at) as year
                FROM transactions 
                WHERE type = 'credit'
                GROUP BY YEAR(created_at), MONTH(created_at)
                ORDER BY year DESC, month DESC
                LIMIT 12");
            $revenue = [];
            while ($row = $result->fetch_assoc()) {
                $revenue[] = $row;
            }
            $response['revenue'] = $revenue;
            break;
            
        case 'partners':
            // Partner performance
            $result = $conn->query("SELECT 
                name,
                commission_rate,
                total_leads,
                total_converted,
                total_commission,
                status
                FROM partners 
                ORDER BY total_commission DESC 
                LIMIT 10");
            $partners = [];
            while ($row = $result->fetch_assoc()) {
                $partners[] = $row;
            }
            $response['top_partners'] = $partners;
            break;
            
        case 'conversion':
            // Conversion funnel
            $result = $conn->query("SELECT 
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new_leads,
                COUNT(CASE WHEN status = 'contacted' THEN 1 END) as contacted,
                COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted,
                COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost
                FROM leads");
            $funnel = $result->fetch_assoc();
            $response['funnel'] = $funnel;
            break;
            
        case 'growth':
            // Growth metrics
            $result = $conn->query("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_customers
                FROM customers 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC");
            $growth = [];
            while ($row = $result->fetch_assoc()) {
                $growth[] = $row;
            }
            $response['growth'] = $growth;
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;
?>