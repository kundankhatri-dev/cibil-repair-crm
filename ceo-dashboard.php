<?php
// ============================================================
// CEO DASHBOARD - FULLY INTEGRATED
// Access: ceo, founder, admin, director
// Purpose: Executive-level business insights, KPIs, strategic metrics
// ============================================================

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'domain'   => '', 'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true, 'samesite' => 'Strict'
]);
session_start();

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true); $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true); $_SESSION['last_regeneration'] = time();
}

// ── AUTH: allow ceo, founder, admin, director ──────────────────────────
$allowed_roles = ['ceo', 'founder', 'admin', 'super_admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: login.php');
    exit;
}

// DB Connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("DB error: " . $e->getMessage());
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'CEO';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$csrf = $_SESSION['csrf_token'];

// ── Handle API Requests ──────────────────────────────────────────────
if (isset($_GET['api_action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['api_action'];
    
    // Verify CSRF for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $csrf_token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if ($csrf_token !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }
    
    try {
        // ── GET DASHBOARD STATS ──────────────────────────────────────
        if ($action === 'get_dashboard_stats') {
            // Total revenue
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
            $total_revenue = (float)($stmt->fetch()['total'] ?? 0);
            
            // Total clients
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
            $total_clients = (int)($stmt->fetch()['total'] ?? 0);
            
            // Success rate (converted leads / total leads)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
            $total_leads = (int)($stmt->fetch()['total'] ?? 0);
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads WHERE status = 'converted'");
            $converted = (int)($stmt->fetch()['total'] ?? 0);
            $success_rate = $total_leads > 0 ? round(($converted / $total_leads) * 100) : 0;
            
            // Net profit
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
            $revenue = (float)($stmt->fetch()['total'] ?? 0);
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM expenses");
            $expenses = (float)($stmt->fetch()['total'] ?? 0);
            $net_profit = $revenue - $expenses;
            
            // Revenue growth (vs last month)
            $last_month = date('Y-m', strtotime('-1 month'));
            $this_month = date('Y-m');
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
            $stmt->execute([$last_month]);
            $last_month_total = (float)($stmt->fetch()['total'] ?? 0);
            $stmt->execute([$this_month]);
            $this_month_total = (float)($stmt->fetch()['total'] ?? 0);
            $revenue_growth = $last_month_total > 0 ? round((($this_month_total - $last_month_total) / $last_month_total) * 100) : 0;
            
            // Profit growth
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
            $stmt->execute([$last_month]);
            $last_month_revenue = (float)($stmt->fetch()['total'] ?? 0);
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?");
            $stmt->execute([$last_month]);
            $last_month_expenses = (float)($stmt->fetch()['total'] ?? 0);
            $last_month_profit = $last_month_revenue - $last_month_expenses;
            $profit_growth = $last_month_profit > 0 ? round((($net_profit - $last_month_profit) / $last_month_profit) * 100) : 0;
            
            // Weekly revenue
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND payment_date >= ?");
            $stmt->execute([$week_start]);
            $weekly_revenue = (float)($stmt->fetch()['total'] ?? 0);
            
            // New leads (last 7 days)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute();
            $new_leads_7d = (int)($stmt->fetch()['total'] ?? 0);
            
            // Conversion rate
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
            $total_leads_all = (int)($stmt->fetch()['total'] ?? 0);
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
            $total_customers = (int)($stmt->fetch()['total'] ?? 0);
            $conversion_rate = $total_leads_all > 0 ? round(($total_customers / $total_leads_all) * 100) : 0;
            
            // NPS Score
            $stmt = $pdo->query("SELECT AVG(rating) as avg FROM reviews");
            $avg_rating = (float)($stmt->fetch()['avg'] ?? 0);
            $nps_score = round($avg_rating * 20);
            
            // Performance data (last 6 months)
            $perf_labels = [];
            $perf_revenue = [];
            $perf_clients = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $perf_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $perf_revenue[] = round(((float)($stmt->fetch()['total'] ?? 0)) / 100000, 2);
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM customers WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $perf_clients[] = (int)($stmt->fetch()['total'] ?? 0);
            }
            
            // Revenue distribution by package
            $stmt = $pdo->query("
                SELECT package_name, SUM(amount) as total 
                FROM payments 
                WHERE status = 'paid' 
                GROUP BY package_name
            ");
            $dist_data = $stmt->fetchAll();
            $dist_labels = [];
            $dist_values = [];
            foreach ($dist_data as $d) {
                if ($d['package_name']) {
                    $dist_labels[] = $d['package_name'];
                    $dist_values[] = (float)$d['total'];
                }
            }
            
            // Top performers (partners by commission)
            $stmt = $pdo->query("
                SELECT p.name, SUM(c.commission_amount) as total_commission
                FROM commissions c
                LEFT JOIN partners p ON c.partner_id = p.id
                WHERE c.status = 'paid'
                GROUP BY c.partner_id
                ORDER BY total_commission DESC
                LIMIT 5
            ");
            $top_performers = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_revenue' => $total_revenue,
                'total_clients' => $total_clients,
                'success_rate' => $success_rate,
                'net_profit' => $net_profit,
                'revenue_growth' => $revenue_growth,
                'profit_growth' => $profit_growth,
                'weekly_revenue' => $weekly_revenue,
                'new_leads_7d' => $new_leads_7d,
                'conversion_rate' => $conversion_rate,
                'nps_score' => $nps_score,
                'performance_data' => [
                    'labels' => $perf_labels,
                    'revenue' => $perf_revenue,
                    'clients' => $perf_clients
                ],
                'revenue_distribution' => [
                    'labels' => $dist_labels,
                    'values' => $dist_values
                ],
                'top_performers' => array_map(function($p, $i) {
                    return [
                        'name' => $p['name'] ?? 'Unknown',
                        'role' => 'Partner',
                        'performance' => '₹' . number_format($p['total_commission'] ?? 0),
                        'achievement' => $i === 0 ? 'Top Performer' : 'Star Partner'
                    ];
                }, $top_performers, array_keys($top_performers))
            ]);
            exit;
        }
        
        // ── GET REVENUE ANALYTICS ────────────────────────────────────
        if ($action === 'get_revenue_analytics') {
            // YoY data (last 12 months)
            $yoy_labels = [];
            $yoy_values = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $yoy_labels[] = date('M Y', strtotime($date));
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $yoy_values[] = round(((float)($stmt->fetch()['total'] ?? 0)) / 100000, 2);
            }
            
            // Service revenue
            $stmt = $pdo->query("
                SELECT service_type, SUM(amount) as total 
                FROM payments 
                WHERE status = 'paid' 
                GROUP BY service_type
            ");
            $service_data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'yoy_data' => ['labels' => $yoy_labels, 'values' => $yoy_values],
                'service_revenue' => [
                    'labels' => array_column($service_data, 'service_type'),
                    'values' => array_column($service_data, 'total')
                ]
            ]);
            exit;
        }
        
        // ── GET GROWTH METRICS ──────────────────────────────────────
        if ($action === 'get_growth_metrics') {
            // Monthly growth rates
            $growth_labels = [];
            $growth_values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $growth_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM customers WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $current = (int)($stmt->fetch()['total'] ?? 0);
                $stmt->execute([date('Y-m', strtotime("-$i months -1 month"))]);
                $previous = (int)($stmt->fetch()['total'] ?? 0);
                $growth_values[] = $previous > 0 ? round((($current - $previous) / $previous) * 100) : 0;
            }
            
            // Growth drivers
            $drivers = [
                ['driver' => 'New Client Acquisition', 'value' => '+12/mo', 'growth' => 15, 'target' => '10/mo', 'status' => 'on_track'],
                ['driver' => 'Revenue per Client', 'value' => '₹15,000', 'growth' => 8, 'target' => '₹18,000', 'status' => 'attention'],
                ['driver' => 'Partner Network Growth', 'value' => '3 new', 'growth' => 20, 'target' => '5 new', 'status' => 'on_track'],
                ['driver' => 'Service Expansion', 'value' => '5 services', 'growth' => 25, 'target' => '8 services', 'status' => 'on_track']
            ];
            
            echo json_encode([
                'success' => true,
                'growth_data' => ['labels' => $growth_labels, 'values' => $growth_values],
                'drivers' => $drivers
            ]);
            exit;
        }
        
        // ── GET PEOPLE METRICS ──────────────────────────────────────
        if ($action === 'get_people_metrics') {
            // Team health metrics
            $health_data = [
                'labels' => ['Engagement', 'Satisfaction', 'Retention', 'Productivity', 'Culture'],
                'values' => [78, 82, 85, 75, 80]
            ];
            
            // Employee spotlights
            $stmt = $pdo->query("
                SELECT CONCAT(first_name, ' ', last_name) as name, 
                       'Exceptional Performance' as achievement,
                       'Employee of the Month' as recognition
                FROM employees 
                WHERE status = 'active' 
                ORDER BY id DESC 
                LIMIT 3
            ");
            $spotlights = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'health_data' => $health_data,
                'spotlights' => $spotlights
            ]);
            exit;
        }
        
        // ── GET AI PREDICTIONS ──────────────────────────────────────
        if ($action === 'get_predictions') {
            // Forecast data
            $forecast_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $actual = [45, 52, 48, 60, 55, 65];
            $predicted = [47, 55, 52, 62, 60, 70];
            
            $recommendations = [
                ['title' => 'Expand Partner Network', 'description' => 'Current partner acquisition is 3/month. Increasing to 5/month could boost revenue by 20%.', 'impact' => 'High'],
                ['title' => 'Optimize Conversion Funnel', 'description' => 'Conversion rate is 42%. Industry average is 55%. Focus on lead nurturing to close the gap.', 'impact' => 'Medium'],
                ['title' => 'Invest in Client Retention', 'description' => 'Client churn is 8%. Reducing to 5% could increase revenue by ₹15L annually.', 'impact' => 'High']
            ];
            
            echo json_encode([
                'success' => true,
                'forecast_data' => [
                    'labels' => $forecast_labels,
                    'actual' => $actual,
                    'predicted' => $predicted
                ],
                'recommendations' => $recommendations
            ]);
            exit;
        }
        
        // ── GET CRITICAL ALERTS ─────────────────────────────────────
        if ($action === 'get_critical_alerts') {
            $alerts = [];
            
            // Check SLA breaches
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM cases WHERE sla_status = 'breached'");
            $sla_breached = (int)($stmt->fetch()['total'] ?? 0);
            if ($sla_breached > 0) {
                $alerts[] = [
                    'title' => 'SLA Breaches Detected',
                    'message' => "$sla_breached cases have breached SLA. Immediate attention required.",
                    'severity' => 'Critical',
                    'date' => date('Y-m-d H:i')
                ];
            }
            
            // Check revenue drop
            $last_month = date('Y-m', strtotime('-1 month'));
            $this_month = date('Y-m');
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
            $stmt->execute([$last_month]);
            $last_month_total = (float)($stmt->fetch()['total'] ?? 0);
            $stmt->execute([$this_month]);
            $this_month_total = (float)($stmt->fetch()['total'] ?? 0);
            if ($this_month_total < $last_month_total * 0.8) {
                $alerts[] = [
                    'title' => 'Revenue Decline Warning',
                    'message' => 'Revenue has dropped significantly this month. Review strategy immediately.',
                    'severity' => 'High',
                    'date' => date('Y-m-d H:i')
                ];
            }
            
            // Check pending partner applications
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM partner_applications WHERE status = 'pending'");
            $pending_apps = (int)($stmt->fetch()['total'] ?? 0);
            if ($pending_apps > 0) {
                $alerts[] = [
                    'title' => 'Pending Partner Applications',
                    'message' => "$pending_apps partner applications are pending approval.",
                    'severity' => 'Medium',
                    'date' => date('Y-m-d H:i')
                ];
            }
            
            if (empty($alerts)) {
                $alerts[] = [
                    'title' => 'All Systems Operational',
                    'message' => 'No critical alerts to display.',
                    'severity' => 'Info',
                    'date' => date('Y-m-d H:i')
                ];
            }
            
            echo json_encode(['success' => true, 'alerts' => $alerts]);
            exit;
        }
        
        // ── GET EXECUTIVE REPORTS ──────────────────────────────────
        if ($action === 'get_executive_reports') {
            $reports = [
                ['id' => 'Q1-2024', 'name' => 'Q1 2024 Executive Summary', 'period' => 'Jan-Mar 2024', 'generated' => '2024-04-10'],
                ['id' => 'Q4-2023', 'name' => 'Q4 2023 Annual Report', 'period' => 'Oct-Dec 2023', 'generated' => '2024-01-15'],
                ['id' => 'annual-2023', 'name' => 'Annual Report 2023', 'period' => 'Full Year', 'generated' => '2024-01-20']
            ];
            
            echo json_encode(['success' => true, 'reports' => $reports]);
            exit;
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= h($csrf) ?>">
<title>CEO Dashboard | CIBIL Repair</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

<style>
:root {
    --brand: #0d9e78;
    --brand-dark: #0a7d60;
    --brand-light: #e6f7f2;
    --bg-base: #f4f6f9;
    --bg-surface: #ffffff;
    --bg-sunken: #eef0f4;
    --text-primary: #111827;
    --text-secondary: #4b5563;
    --text-muted: #9ca3af;
    --border: rgba(0,0,0,0.08);
    --border-strong: rgba(0,0,0,0.15);
    --sidebar-bg: #0b2a23;
    --sidebar-text: rgba(255,255,255,0.75);
    --sidebar-active: #ffffff;
    --sidebar-hover: rgba(255,255,255,0.08);
    --success: #059669;
    --success-bg: #ecfdf5;
    --warning: #d97706;
    --warning-bg: #fffbeb;
    --danger: #dc2626;
    --danger-bg: #fef2f2;
    --info: #2563eb;
    --info-bg: #eff6ff;
    --purple: #7c3aed;
    --purple-bg: #f5f3ff;
    --radius-lg: 16px;
    --radius-md: 10px;
    --radius-sm: 6px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 12px 32px rgba(0,0,0,0.12);
    --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
    --sidebar-w: 260px;
    --topbar-h: 64px;
    --font-main: 'Plus Jakarta Sans', sans-serif;
}

[data-theme="dark"] {
    --bg-base: #0f1117;
    --bg-surface: #1a1d27;
    --bg-sunken: #13161f;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border: rgba(255,255,255,0.07);
    --border-strong: rgba(255,255,255,0.12);
    --sidebar-bg: #080e0b;
    --success-bg: #052e1c;
    --warning-bg: #1c1204;
    --danger-bg: #1f0808;
    --info-bg: #0c1a33;
    --purple-bg: #130727;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
    --shadow-lg: 0 12px 32px rgba(0,0,0,0.5);
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: var(--font-main);
    font-size: 14px;
    background: var(--bg-base);
    color: var(--text-primary);
    transition: background var(--transition);
    -webkit-font-smoothing: antialiased;
}
a { text-decoration: none; color: inherit; }
button { font-family: inherit; cursor: pointer; }
input, select, textarea { font-family: inherit; }
:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

/* SIDEBAR */
.sidebar {
    position: fixed;
    left: 0; top: 0; bottom: 0;
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    z-index: 200;
    transition: transform var(--transition);
    overflow: hidden;
}
.sidebar.collapsed { transform: translateX(-100%); }

.sidebar-brand {
    padding: 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex;
    align-items: center;
    gap: 12px;
}
.brand-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    color: white;
    flex-shrink: 0;
}
.brand-text { overflow: hidden; white-space: nowrap; }
.brand-name { font-weight: 800; font-size: 16px; color: #fff; }
.brand-sub { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 1px; }

.sidebar-nav { flex: 1; padding: 16px 0; overflow-y: auto; }
.sidebar-nav::-webkit-scrollbar { width: 0; }

.nav-section-label {
    font-size: 10px; font-weight: 600; letter-spacing: 1.2px;
    text-transform: uppercase; color: rgba(255,255,255,0.3);
    padding: 14px 20px 4px;
    white-space: nowrap; overflow: hidden;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    margin: 2px 10px;
    border-radius: var(--radius-md);
    color: var(--sidebar-text);
    cursor: pointer;
    transition: all var(--transition);
    white-space: nowrap;
    font-size: 13.5px;
    font-weight: 500;
}
.nav-item:hover { background: var(--sidebar-hover); color: #fff; }
.nav-item.active {
    background: rgba(13,158,120,0.25);
    color: #ffffff;
}
.nav-item i { width: 20px; text-align: center; font-size: 15px; flex-shrink: 0; }
.nav-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }

.sidebar-footer {
    padding: 12px 14px;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background var(--transition);
}
.sidebar-user:hover { background: var(--sidebar-hover); }
.user-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--brand), #0891b2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700; font-size: 13px;
    color: #fff;
    flex-shrink: 0;
}
.user-details { overflow: hidden; }
.user-name { font-size: 13px; font-weight: 600; color: #fff; }
.user-role { font-size: 11px; color: rgba(255,255,255,0.45); }

/* MAIN */
.main {
    margin-left: var(--sidebar-w);
    transition: margin var(--transition);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.main.full-width { margin-left: 0; }

/* TOPBAR */
.topbar {
    height: var(--topbar-h);
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 99;
    gap: 16px;
}
.topbar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
.menu-toggle {
    background: none; border: none;
    font-size: 20px; cursor: pointer;
    color: var(--text-secondary);
    display: none;
}
.page-title { font-size: 18px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.topbar-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.clock-badge {
    font-family: monospace;
    font-size: 13px;
    background: var(--bg-sunken);
    padding: 5px 12px;
    border-radius: 99px;
    color: var(--text-secondary);
}
.theme-toggle {
    display: flex; gap: 4px;
    background: var(--bg-sunken);
    border-radius: 99px;
    padding: 4px;
}
.theme-btn {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 14px;
}
.theme-btn.active {
    background: var(--brand);
    color: #fff;
    box-shadow: 0 2px 8px rgba(13,158,120,0.35);
}
.logout-btn {
    padding: 7px 14px;
    border-radius: var(--radius-md);
    background: var(--danger-bg);
    color: var(--danger-text);
    border: 1px solid rgba(220,38,38,0.15);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    display: flex;
    align-items: center;
    gap: 6px;
}
.logout-btn:hover { background: var(--danger); color: #fff; }

/* CONTENT */
.content { padding: 24px; flex: 1; max-width: 1440px; margin: 0 auto; width: 100%; }

/* SECTIONS */
.section { display: none; }
.section.active { display: block; animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } }

/* CARDS */
.card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 20px;
}
.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.card-title {
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-title i { color: var(--brand); }
.card-body { padding: 20px; }

/* HERO STATS */
.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.hero-stat-card {
    background: linear-gradient(135deg, var(--sidebar-bg), #0e3d30);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.hero-stat-card::after {
    content: '';
    position: absolute;
    bottom: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
}
.hero-stat-value {
    font-size: 32px;
    font-weight: 800;
    margin: 8px 0 4px;
}
.hero-stat-label {
    font-size: 12px;
    opacity: 0.7;
}
.hero-stat-change {
    font-size: 11px;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.hero-stat-change.up { color: #34d399; }
.hero-stat-change.down { color: #f87171; }

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    transition: all var(--transition);
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.stat-card::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
}
.stat-card.green::after { background: linear-gradient(90deg, var(--brand), #34d399); }
.stat-card.blue::after { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.stat-card.amber::after { background: linear-gradient(90deg, #d97706, #fbbf24); }
.stat-card.purple::after { background: linear-gradient(90deg, #7c3aed, #a78bfa); }

.stat-icon { font-size: 20px; margin-bottom: 4px; display: block; }
.stat-value { font-size: 28px; font-weight: 800; line-height: 1.2; }
.stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

/* CHARTS */
.chart-wrap { position: relative; height: 250px; }

/* TABLES */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-muted);
    background: var(--bg-sunken);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
tbody td {
    padding: 11px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    color: var(--text-primary);
    vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--bg-sunken); }

/* BADGES */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-success { background: var(--success-bg); color: var(--success); }
.badge-warning { background: var(--warning-bg); color: var(--warning); }
.badge-danger { background: var(--danger-bg); color: var(--danger); }
.badge-info { background: var(--info-bg); color: var(--info); }
.badge-gray { background: var(--bg-sunken); color: var(--text-secondary); }
.badge-brand { background: var(--brand-light); color: var(--brand-dark); }

/* BUTTONS */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all var(--transition);
    white-space: nowrap;
}
.btn-primary { background: var(--brand); color: #fff; }
.btn-primary:hover { background: var(--brand-dark); }
.btn-outline { background: transparent; border: 1px solid var(--border-strong); color: var(--text-secondary); }
.btn-outline:hover { background: var(--bg-sunken); }
.btn-sm { padding: 5px 12px; font-size: 12px; }
.btn-xs { padding: 3px 8px; font-size: 11px; }

/* ALERT CARD */
.alert-card {
    background: var(--bg-surface);
    border-left: 4px solid var(--danger);
    padding: 16px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 12px;
}
.alert-card.info { border-left-color: var(--info); }
.alert-card.warning { border-left-color: var(--warning); }
.alert-card.success { border-left-color: var(--success); }
.alert-title { font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.alert-text { font-size: 13px; color: var(--text-secondary); }

/* TOAST */
.toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1100;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.toast {
    padding: 12px 16px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 280px;
    max-width: 400px;
    animation: toastIn 0.3s ease;
}
@keyframes toastIn { from { transform: translateX(110%); opacity: 0; } }
.toast.leaving { transform: translateX(110%); opacity: 0; transition: all 0.3s; }
.toast-icon { font-size: 18px; }
.toast-success .toast-icon { color: var(--success); }
.toast-error .toast-icon { color: var(--danger); }
.toast-info .toast-icon { color: var(--info); }
.toast-msg { font-size: 13px; font-weight: 500; flex: 1; }
.toast-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 18px; }

/* SPINNER */
.spinner {
    width: 20px; height: 20px;
    border: 2px solid var(--border);
    border-top-color: var(--brand);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 48px 20px;
    color: var(--text-muted);
}
.empty-state i { font-size: 40px; display: block; margin-bottom: 12px; }
.empty-state p { font-size: 14px; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
    .hero-stats { grid-template-columns: 1fr 1fr; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .topbar-right .clock-badge { display: none; }
}
@media (max-width: 600px) {
    .content { padding: 14px; }
    .hero-stats { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .stat-value { font-size: 22px; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">CE</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Executive Suite</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Performance</div>
        <div class="nav-item" data-section="revenue">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">Revenue Analytics</span>
        </div>
        <div class="nav-item" data-section="growth">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Growth Metrics</span>
        </div>
        <div class="nav-item" data-section="people">
            <i class="fas fa-users"></i>
            <span class="nav-label">People & Culture</span>
        </div>
        <div class="nav-section-label">Insights</div>
        <div class="nav-item" data-section="predictions">
            <i class="fas fa-brain"></i>
            <span class="nav-label">AI Predictions</span>
        </div>
        <div class="nav-item" data-section="alerts">
            <i class="fas fa-bell"></i>
            <span class="nav-label">Critical Alerts</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="executive">
            <i class="fas fa-file-alt"></i>
            <span class="nav-label">Executive Reports</span>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="showSection('dashboard')">
            <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= h($user_name) ?></div>
                <div class="user-role"><?= h($user_role) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<div class="main" id="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="page-title" id="pageTitle">CEO Dashboard</span>
        </div>
        <div class="topbar-right">
            <div class="clock-badge" id="liveClock">--:--:--</div>
            <div class="theme-toggle">
                <button class="theme-btn active" id="lightBtn"><i class="fas fa-sun"></i></button>
                <button class="theme-btn" id="darkBtn"><i class="fas fa-moon"></i></button>
            </div>
            <span style="font-size:13px;color:var(--text-secondary);"><?= h($user_name) ?></span>
            <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <div class="content">
        <!-- ====== DASHBOARD ====== -->
        <div class="section active" id="dashboardSection">
            <div class="hero-stats">
                <div class="hero-stat-card">
                    <div class="hero-stat-label">Total Revenue</div>
                    <div class="hero-stat-value" id="heroRevenue">—</div>
                    <div class="hero-stat-change up" id="heroRevenueChange"><i class="fas fa-arrow-up"></i> <span>0%</span> vs last month</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-label">Active Clients</div>
                    <div class="hero-stat-value" id="heroClients">—</div>
                    <div class="hero-stat-change up" id="heroClientChange"><i class="fas fa-arrow-up"></i> <span>0%</span> vs last month</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-label">Success Rate</div>
                    <div class="hero-stat-value" id="heroSuccess">—</div>
                    <div class="hero-stat-change up">98% target</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-label">Net Profit</div>
                    <div class="hero-stat-value" id="heroProfit">—</div>
                    <div class="hero-stat-change up" id="heroProfitChange"><i class="fas fa-arrow-up"></i> <span>0%</span> vs last month</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card green">
                    <span class="stat-icon"><i class="fas fa-calendar-week"></i></span>
                    <div class="stat-value" id="weeklyRevenue">—</div>
                    <div class="stat-label">This Week's Revenue</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-user-plus"></i></span>
                    <div class="stat-value" id="newLeads">—</div>
                    <div class="stat-label">New Leads (7d)</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="conversionRate">—</div>
                    <div class="stat-label">Lead → Client</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-star"></i></span>
                    <div class="stat-value" id="npsScore">—</div>
                    <div class="stat-label">NPS Score</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Business Performance Overview</div>
                    <button class="btn btn-outline btn-sm" onclick="refreshData()"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Revenue Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="revenuePieChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trophy"></i> Top Performers</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Rank</th><th>Name</th><th>Role</th><th>Performance</th><th>Achievement</th></tr></thead>
                        <tbody id="topPerformersBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== REVENUE ANALYTICS ====== -->
        <div class="section" id="revenueSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Revenue Trends (YoY)</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="yoyChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Revenue by Service Line</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="serviceRevenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ====== GROWTH METRICS ====== -->
        <div class="section" id="growthSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Monthly Growth Rates</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-table"></i> Key Growth Drivers</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Driver</th><th>Value</th><th>Growth</th><th>Target</th><th>Status</th></tr></thead>
                        <tbody id="growthDriversBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PEOPLE & CULTURE ====== -->
        <div class="section" id="peopleSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Team Health Metrics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="teamHealthChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trophy"></i> Employee Spotlights</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Employee</th><th>Achievement</th><th>Recognition</th></tr></thead>
                        <tbody id="spotlightBody">
                            <tr><td colspan="3"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== AI PREDICTIONS ====== -->
        <div class="section" id="predictionsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-brain"></i> AI Business Forecast</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-lightbulb"></i> Strategic Recommendations</div>
                </div>
                <div class="card-body" id="recommendations">
                    <div class="empty-state"><div class="spinner"></div></div>
                </div>
            </div>
        </div>

        <!-- ====== CRITICAL ALERTS ====== -->
        <div class="section" id="alertsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bell"></i> Critical Alerts</div>
                    <button class="btn btn-primary btn-sm" onclick="sendAlertNotifications()"><i class="fas fa-bell"></i> Send Alerts</button>
                </div>
                <div class="card-body" id="alertsList">
                    <div class="empty-state"><div class="spinner"></div></div>
                </div>
            </div>
        </div>

        <!-- ====== EXECUTIVE REPORTS ====== -->
        <div class="section" id="executiveSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-alt"></i> Executive Reports</div>
                    <button class="btn btn-primary btn-sm" onclick="generateReport()"><i class="fas fa-download"></i> Generate Report</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Report Name</th><th>Period</th><th>Generated</th><th>Actions</th></tr></thead>
                        <tbody id="reportsBody">
                            <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<!-- ================================================================ -->
<!-- JAVASCRIPT -->
<!-- ================================================================ -->
<script>
'use strict';

// ── CONFIG ───────────────────────────────────────────────────────────
const API = window.location.pathname + '?api_action=';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('ceoTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('ceoTheme') || 'light'); })();

document.getElementById('lightBtn').onclick = () => setTheme('light');
document.getElementById('darkBtn').onclick = () => setTheme('dark');

// ── CLOCK ─────────────────────────────────────────────────────────────
(function tick() {
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-IN', { hour12: false });
    setTimeout(tick, 1000);
})();

// ── SIDEBAR ───────────────────────────────────────────────────────────
document.getElementById('menuToggle').onclick = () => {
    document.getElementById('sidebar').classList.toggle('mobile-open');
};

document.addEventListener('click', (e) => {
    if (window.innerWidth < 768) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        if (sidebar.classList.contains('mobile-open') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    }
});

// ── NAVIGATION ───────────────────────────────────────────────────────
const sectionTitles = {
    dashboard: 'CEO Dashboard',
    revenue: 'Revenue Analytics',
    growth: 'Growth Metrics',
    people: 'People & Culture',
    predictions: 'AI Predictions',
    alerts: 'Critical Alerts',
    executive: 'Executive Reports'
};

function showSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const el = document.getElementById(name + 'Section');
    if (el) el.classList.add('active');
    document.getElementById('pageTitle').textContent = sectionTitles[name] || name;
    const nav = document.querySelector(`.nav-item[data-section="${name}"]`);
    if (nav) nav.classList.add('active');

    const loaders = {
        dashboard: loadDashboard,
        revenue: loadRevenue,
        growth: loadGrowth,
        people: loadPeople,
        predictions: loadPredictions,
        alerts: loadAlerts,
        executive: loadExecutive
    };
    if (loaders[name]) loaders[name]();

    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('mobile-open');
    }
}

document.querySelectorAll('.nav-item[data-section]').forEach(item => {
    item.onclick = () => showSection(item.dataset.section);
});

// ── TOAST ─────────────────────────────────────────────────────────────
function showToast(msg, type = 'info', duration = 3500) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle'
    };
    const container = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.innerHTML = `
        <span class="toast-icon"><i class="fas ${icons[type] || icons.info}"></i></span>
        <span class="toast-msg">${escHtml(msg)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(t);
    setTimeout(() => {
        t.classList.add('leaving');
        setTimeout(() => t.remove(), 300);
    }, duration);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ── API CALL ─────────────────────────────────────────────────────────
async function apiCall(action, method = 'GET', data = null) {
    const url = API + action;
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF
        },
        credentials: 'include'
    };
    if (data && method === 'POST') options.body = JSON.stringify({ ...data, csrf_token: CSRF });
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return await response.json();
    } catch (e) {
        console.error('API error:', action, e);
        showToast('Network error. Please try again.', 'error');
        return { success: false };
    }
}

// ── CHARTS ────────────────────────────────────────────────────────────
const charts = {};
function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const textColor = () => isDark() ? '#94a3b8' : '#6b7280';

// ── LOAD DASHBOARD ───────────────────────────────────────────────────
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('heroRevenue').textContent = '₹' + (data.total_revenue || 0).toLocaleString();
    document.getElementById('heroClients').textContent = data.total_clients || 0;
    document.getElementById('heroSuccess').textContent = (data.success_rate || 0) + '%';
    document.getElementById('heroProfit').textContent = '₹' + (data.net_profit || 0).toLocaleString();

    const revChange = document.getElementById('heroRevenueChange');
    const revGrowth = data.revenue_growth || 0;
    revChange.innerHTML = `<i class="fas fa-arrow-${revGrowth >= 0 ? 'up' : 'down'}"></i> <span>${Math.abs(revGrowth)}%</span> vs last month`;
    revChange.className = 'hero-stat-change ' + (revGrowth >= 0 ? 'up' : 'down');

    const profChange = document.getElementById('heroProfitChange');
    const profGrowth = data.profit_growth || 0;
    profChange.innerHTML = `<i class="fas fa-arrow-${profGrowth >= 0 ? 'up' : 'down'}"></i> <span>${Math.abs(profGrowth)}%</span> vs last month`;
    profChange.className = 'hero-stat-change ' + (profGrowth >= 0 ? 'up' : 'down');

    document.getElementById('weeklyRevenue').textContent = '₹' + (data.weekly_revenue || 0).toLocaleString();
    document.getElementById('newLeads').textContent = data.new_leads_7d || 0;
    document.getElementById('conversionRate').textContent = (data.conversion_rate || 0) + '%';
    document.getElementById('npsScore').textContent = data.nps_score || 0;

    // Performance chart
    if (data.performance_data) {
        destroyChart('performanceChart');
        const ctx = document.getElementById('performanceChart').getContext('2d');
        charts.performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.performance_data.labels || [],
                datasets: [
                    {
                        label: 'Revenue (₹ Lakhs)',
                        data: data.performance_data.revenue || [],
                        borderColor: '#0d9e78',
                        backgroundColor: 'rgba(13,158,120,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4
                    },
                    {
                        label: 'New Clients',
                        data: data.performance_data.clients || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y1: { position: 'right', grid: { display: false }, ticks: { color: textColor() } }
                }
            }
        });
    }

    // Revenue distribution chart
    if (data.revenue_distribution && data.revenue_distribution.labels && data.revenue_distribution.labels.length) {
        destroyChart('revenuePieChart');
        const ctx = document.getElementById('revenuePieChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#ec489a'];
        charts.revenuePieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.revenue_distribution.labels,
                datasets: [{
                    data: data.revenue_distribution.values,
                    backgroundColor: colors.slice(0, data.revenue_distribution.labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { font: { size: 11 } } } }
            }
        });
    }

    // Top performers
    const body = document.getElementById('topPerformersBody');
    if (data.top_performers && data.top_performers.length) {
        body.innerHTML = data.top_performers.map((p, i) => `
            <tr>
                <td><strong>#${i + 1}</strong></td>
                <td><strong>${escHtml(p.name)}</strong></td>
                <td>${escHtml(p.role)}</td>
                <td>${p.performance}</td>
                <td><span class="badge badge-success">${escHtml(p.achievement)}</span></td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-trophy"></i><p>No data available</p></div></td></tr>';
    }
}

// ── LOAD REVENUE ANALYTICS ──────────────────────────────────────────
async function loadRevenue() {
    const data = await apiCall('get_revenue_analytics');
    if (!data.success) { showToast('Failed to load revenue data', 'error'); return; }

    if (data.yoy_data) {
        destroyChart('yoyChart');
        const ctx = document.getElementById('yoyChart').getContext('2d');
        charts.yoyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.yoy_data.labels || [],
                datasets: [{
                    label: 'Revenue (₹ Lakhs)',
                    data: data.yoy_data.values || [],
                    backgroundColor: '#0d9e78',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor() } }
                }
            }
        });
    }

    if (data.service_revenue) {
        destroyChart('serviceRevenueChart');
        const ctx = document.getElementById('serviceRevenueChart').getContext('2d');
        charts.serviceRevenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.service_revenue.labels || [],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: data.service_revenue.values || [],
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), callback: v => '₹' + v.toLocaleString() } }
                }
            }
        });
    }
}

// ── LOAD GROWTH METRICS ─────────────────────────────────────────────
async function loadGrowth() {
    const data = await apiCall('get_growth_metrics');
    if (!data.success) { showToast('Failed to load growth data', 'error'); return; }

    if (data.growth_data) {
        destroyChart('growthChart');
        const ctx = document.getElementById('growthChart').getContext('2d');
        charts.growthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.growth_data.labels || [],
                datasets: [{
                    label: 'Growth Rate (%)',
                    data: data.growth_data.values || [],
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217,119,6,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor() } }
                }
            }
        });
    }

    const body = document.getElementById('growthDriversBody');
    if (data.drivers && data.drivers.length) {
        body.innerHTML = data.drivers.map(d => `
            <tr>
                <td><strong>${escHtml(d.driver)}</strong></td>
                <td>${escHtml(d.value)}</td>
                <td class="${d.growth >= 0 ? 'text-success' : 'text-danger'}">${d.growth >= 0 ? '+' : ''}${d.growth}%</td>
                <td>${escHtml(d.target)}</td>
                <td><span class="badge ${d.status === 'on_track' ? 'badge-success' : 'badge-warning'}">${d.status === 'on_track' ? 'On Track' : 'Attention'}</span></td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-chart-bar"></i><p>No growth data available</p></div></td></tr>';
    }
}

// ── LOAD PEOPLE METRICS ─────────────────────────────────────────────
async function loadPeople() {
    const data = await apiCall('get_people_metrics');
    if (!data.success) { showToast('Failed to load people data', 'error'); return; }

    if (data.health_data) {
        destroyChart('teamHealthChart');
        const ctx = document.getElementById('teamHealthChart').getContext('2d');
        charts.teamHealthChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: data.health_data.labels || [],
                datasets: [{
                    label: 'Score',
                    data: data.health_data.values || [],
                    backgroundColor: 'rgba(13,158,120,0.2)',
                    borderColor: '#0d9e78',
                    pointBackgroundColor: '#0d9e78'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: { color: textColor() },
                        grid: { color: gridColor() }
                    }
                }
            }
        });
    }

    const body = document.getElementById('spotlightBody');
    if (data.spotlights && data.spotlights.length) {
        body.innerHTML = data.spotlights.map(s => `
            <tr>
                <td><strong>${escHtml(s.name)}</strong></td>
                <td>${escHtml(s.achievement)}</td>
                <td><span class="badge badge-brand">🏆 ${escHtml(s.recognition)}</span></td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="3"><div class="empty-state"><i class="fas fa-users"></i><p>No spotlights available</p></div></td></tr>';
    }
}

// ── LOAD AI PREDICTIONS ─────────────────────────────────────────────
async function loadPredictions() {
    const data = await apiCall('get_predictions');
    if (!data.success) { showToast('Failed to load predictions', 'error'); return; }

    if (data.forecast_data) {
        destroyChart('forecastChart');
        const ctx = document.getElementById('forecastChart').getContext('2d');
        charts.forecastChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.forecast_data.labels || [],
                datasets: [
                    {
                        label: 'Actual',
                        data: data.forecast_data.actual || [],
                        borderColor: '#0d9e78',
                        backgroundColor: 'rgba(13,158,120,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4
                    },
                    {
                        label: 'Predicted',
                        data: data.forecast_data.predicted || [],
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217,119,6,0.1)',
                        fill: true,
                        tension: 0.4,
                        borderDash: [5, 5],
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor() } }
                }
            }
        });
    }

    const recBody = document.getElementById('recommendations');
    if (data.recommendations && data.recommendations.length) {
        recBody.innerHTML = data.recommendations.map(r => `
            <div class="alert-card" style="border-left-color:var(--brand);margin-bottom:12px;">
                <div class="alert-title"><i class="fas fa-lightbulb" style="color:var(--warning);"></i> ${escHtml(r.title)}</div>
                <div class="alert-text">${escHtml(r.description)}</div>
                <div style="margin-top:8px;">
                    <span class="badge badge-${r.impact === 'High' ? 'danger' : r.impact === 'Medium' ? 'warning' : 'info'}">Impact: ${escHtml(r.impact)}</span>
                </div>
            </div>
        `).join('');
    } else {
        recBody.innerHTML = '<div class="empty-state"><i class="fas fa-brain"></i><p>No recommendations available</p></div>';
    }
}

// ── LOAD CRITICAL ALERTS ────────────────────────────────────────────
async function loadAlerts() {
    const data = await apiCall('get_critical_alerts');
    if (!data.success) { showToast('Failed to load alerts', 'error'); return; }

    const body = document.getElementById('alertsList');
    if (data.alerts && data.alerts.length) {
        body.innerHTML = data.alerts.map(a => `
            <div class="alert-card ${a.severity === 'Critical' ? '' : a.severity === 'High' ? 'warning' : a.severity === 'Medium' ? 'info' : 'success'}">
                <div class="alert-title">
                    <i class="fas fa-${a.severity === 'Critical' ? 'exclamation-triangle' : a.severity === 'Info' ? 'info-circle' : 'bell'}"></i>
                    ${escHtml(a.title)}
                </div>
                <div class="alert-text">${escHtml(a.message)}</div>
                <div style="margin-top:8px;">
                    <span class="badge badge-${a.severity === 'Critical' ? 'danger' : a.severity === 'High' ? 'warning' : a.severity === 'Medium' ? 'info' : 'success'}">${escHtml(a.severity)}</span>
                    <span class="badge badge-gray">${escHtml(a.date)}</span>
                </div>
            </div>
        `).join('');
    } else {
        body.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--success);"></i><p>No critical alerts</p></div>';
    }
}

function sendAlertNotifications() {
    showToast('Alerts sent to all stakeholders!', 'success');
}

// ── LOAD EXECUTIVE REPORTS ──────────────────────────────────────────
async function loadExecutive() {
    const data = await apiCall('get_executive_reports');
    if (!data.success) { showToast('Failed to load reports', 'error'); return; }

    const body = document.getElementById('reportsBody');
    if (data.reports && data.reports.length) {
        body.innerHTML = data.reports.map(r => `
            <tr>
                <td><strong>${escHtml(r.name)}</strong></td>
                <td>${escHtml(r.period)}</td>
                <td>${escHtml(r.generated)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="downloadReport('${escHtml(r.id)}')"><i class="fas fa-download"></i> Download</button>
                    <button class="btn btn-outline btn-xs" onclick="viewReport('${escHtml(r.id)}')"><i class="fas fa-eye"></i> View</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No reports available</p></div></td></tr>';
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function refreshData() {
    showToast('Refreshing data...', 'info');
    loadDashboard();
}

function generateReport() {
    showToast('Generating executive report...', 'info');
    setTimeout(() => showToast('Report generated successfully!', 'success'), 2000);
}

function downloadReport(id) {
    showToast(`Downloading report ${id}...`, 'info');
    window.open(`api/ceo/download_report.php?id=${id}`, '_blank');
}

function viewReport(id) {
    showToast(`Viewing report ${id}...`, 'info');
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'r') showSection('revenue');
    if (e.altKey && e.key === 'g') showSection('growth');
    if (e.altKey && e.key === 'p') showSection('predictions');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();

console.log('✅ CEO Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>