<?php
// generate-report.php - Generate daily/weekly/monthly reports

function generateDailyReport() {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    
    // Get today's stats
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // New customers today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $new_customers = $stmt->fetchColumn();
    
    // New leads today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $new_leads = $stmt->fetchColumn();
    
    // Sales today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM sales WHERE DATE(sale_date) = ?");
    $stmt->execute([$today]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Active employees
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $active_employees = $stmt->fetchColumn();
    
    // Today's attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'present'");
    $stmt->execute([$today]);
    $present_today = $stmt->fetchColumn();
    
    // Last backup
    $backups = glob('backups/production/*.sql');
    $last_backup = $backups ? basename(max($backups)) : 'No backup';
    
    $report = [
        'date' => date('Y-m-d'),
        'new_customers' => $new_customers,
        'new_leads' => $new_leads,
        'sales_count' => $sales['count'],
        'sales_total' => $sales['total'],
        'active_employees' => $active_employees,
        'present_today' => $present_today,
        'last_backup' => $last_backup,
        'system_health' => '✅ All systems operational'
    ];
    
    return $report;
}

function generateWeeklyReport() {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $today = date('Y-m-d');
    
    // Weekly stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$week_ago, $today]);
    $new_customers = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
    $stmt->execute([$week_ago, $today]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Top performing partners
    $stmt = $pdo->query("SELECT name, total_conversions FROM partners ORDER BY total_conversions DESC LIMIT 5");
    $top_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'date_range' => "$week_ago to $today",
        'new_customers' => $new_customers,
        'sales_count' => $sales['count'],
        'sales_total' => $sales['total'],
        'top_partners' => $top_partners
    ];
}

function generateMonthlyReport() {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    
    $month_ago = date('Y-m-d', strtotime('-30 days'));
    $today = date('Y-m-d');
    
    // Monthly stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$month_ago, $today]);
    $new_customers = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
    $stmt->execute([$month_ago, $today]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM partners");
    $total_partners = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $total_employees = $stmt->fetchColumn();
    
    return [
        'date_range' => "$month_ago to $today",
        'new_customers' => $new_customers,
        'sales_count' => $sales['count'],
        'sales_total' => $sales['total'],
        'total_partners' => $total_partners,
        'total_employees' => $total_employees
    ];
}
?>
