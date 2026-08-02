<?php
// ============================================================
// MARKETING DASHBOARD - FULLY INTEGRATED
// Access: marketing_team, marketing_manager, admin, super_admin
// Purpose: Campaign management, lead generation, analytics, ROI tracking
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

// ── AUTH: allow marketing roles ──────────────────────────────────────
$allowed_roles = ['marketing_team', 'marketing_manager', 'admin', 'super_admin', 'marketing'];
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
$user_name = $_SESSION['user_name'] ?? 'Marketing Manager';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$csrf = $_SESSION['csrf_token'];

// ── Get marketing team member ID ────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND department_id IN (SELECT id FROM departments WHERE name LIKE '%marketing%')");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();
$employee_id = $employee['id'] ?? 0;

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
            // Total leads from marketing
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads WHERE source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral')");
            $total_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // New leads this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral') AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
            $stmt->execute([date('m'), date('Y')]);
            $new_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Conversions from marketing
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads WHERE stage = 'won' AND source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral')");
            $conversions = (int)($stmt->fetch()['total'] ?? 0);
            
            // Conversion rate
            $conversion_rate = $total_leads > 0 ? round(($conversions / $total_leads) * 100) : 0;
            
            // Revenue from marketing
            $stmt = $pdo->query("SELECT SUM(l.expected_amount) as total FROM leads l WHERE l.stage = 'won' AND l.source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral')");
            $revenue = (float)($stmt->fetch()['total'] ?? 0);
            
            // Campaign performance
            $stmt = $pdo->query("
                SELECT 
                    c.name, 
                    c.status,
                    c.budget,
                    COUNT(l.id) as leads,
                    SUM(CASE WHEN l.stage = 'won' THEN 1 ELSE 0 END) as conversions,
                    ROUND(SUM(CASE WHEN l.stage = 'won' THEN l.expected_amount ELSE 0 END), 2) as revenue
                FROM campaigns c
                LEFT JOIN leads l ON l.campaign_id = c.id
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");
            $campaigns = $stmt->fetchAll();
            
            // Lead sources distribution
            $stmt = $pdo->query("
                SELECT 
                    source,
                    COUNT(*) as count,
                    SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) as conversions
                FROM leads
                WHERE source IS NOT NULL
                GROUP BY source
                ORDER BY count DESC
            ");
            $source_distribution = $stmt->fetchAll();
            
            // Weekly lead trend
            $trend_labels = [];
            $trend_values = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $trend_labels[] = date('D', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE DATE(created_at) = ? AND source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral')");
                $stmt->execute([$date]);
                $trend_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'total_leads' => $total_leads,
                'new_leads' => $new_leads,
                'conversions' => $conversions,
                'conversion_rate' => $conversion_rate,
                'revenue' => $revenue,
                'campaigns' => $campaigns,
                'source_distribution' => $source_distribution,
                'lead_trend' => ['labels' => $trend_labels, 'values' => $trend_values]
            ]);
            exit;
        }
        
        // ── GET CAMPAIGNS ─────────────────────────────────────────────
        if ($action === 'get_campaigns') {
            $status = $_GET['status'] ?? '';
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM leads WHERE campaign_id = c.id) as lead_count,
                    (SELECT COUNT(*) FROM leads WHERE campaign_id = c.id AND stage = 'won') as conversion_count,
                    (SELECT COALESCE(SUM(expected_amount), 0) FROM leads WHERE campaign_id = c.id AND stage = 'won') as revenue
                    FROM campaigns c";
            if ($status) {
                $sql .= " WHERE c.status = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$status]);
            } else {
                $stmt = $pdo->query($sql);
            }
            $campaigns = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'campaigns' => $campaigns]);
            exit;
        }
        
        // ── ADD CAMPAIGN ─────────────────────────────────────────────
        if ($action === 'add_campaign') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = $input['name'] ?? '';
            $type = $input['type'] ?? '';
            $budget = (float)($input['budget'] ?? 0);
            $start_date = $input['start_date'] ?? date('Y-m-d');
            $end_date = $input['end_date'] ?? null;
            $objective = $input['objective'] ?? '';
            $target_audience = $input['target_audience'] ?? '';
            $channel = $input['channel'] ?? '';
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Campaign name is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO campaigns (name, type, budget, start_date, end_date, objective, target_audience, channel, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
            ");
            $stmt->execute([$name, $type, $budget, $start_date, $end_date, $objective, $target_audience, $channel]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE CAMPAIGN STATUS ──────────────────────────────────
        if ($action === 'update_campaign_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $campaign_id = (int)($input['campaign_id'] ?? 0);
            $status = $input['status'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE campaigns SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $campaign_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE CAMPAIGN ──────────────────────────────────────────
        if ($action === 'delete_campaign') {
            $input = json_decode(file_get_contents('php://input'), true);
            $campaign_id = (int)($input['campaign_id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET EMAIL CAMPAIGNS ──────────────────────────────────────
        if ($action === 'get_email_campaigns') {
            $stmt = $pdo->query("
                SELECT * FROM email_campaigns 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $emails = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'emails' => $emails]);
            exit;
        }
        
        // ── ADD EMAIL CAMPAIGN ──────────────────────────────────────
        if ($action === 'add_email_campaign') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $subject = $input['subject'] ?? '';
            $content = $input['content'] ?? '';
            $recipients = $input['recipients'] ?? '';
            $status = $input['status'] ?? 'draft';
            
            if (empty($subject)) {
                echo json_encode(['success' => false, 'error' => 'Email subject is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO email_campaigns (subject, content, recipients, status, sent_count, open_count, click_count, created_at)
                VALUES (?, ?, ?, ?, 0, 0, 0, NOW())
            ");
            $stmt->execute([$subject, $content, $recipients, $status]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SOCIAL MEDIA STATS ──────────────────────────────────
        if ($action === 'get_social_media_stats') {
            $stmt = $pdo->query("
                SELECT 
                    platform,
                    SUM(followers) as followers,
                    SUM(engagement) as engagement,
                    SUM(reach) as reach,
                    SUM(impressions) as impressions,
                    SUM(posts) as posts
                FROM social_media_stats
                GROUP BY platform
                ORDER BY followers DESC
            ");
            $stats = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        }
        
        // ── GET ANALYTICS ────────────────────────────────────────────
        if ($action === 'get_analytics') {
            // Monthly lead generation
            $monthly_labels = [];
            $monthly_leads = [];
            $monthly_conversions = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $monthly_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral') AND DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $monthly_leads[] = (int)($stmt->fetch()['count'] ?? 0);
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE stage = 'won' AND source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral') AND DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $monthly_conversions[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // ROI by channel
            $stmt = $pdo->query("
                SELECT 
                    source as channel,
                    COUNT(*) as leads,
                    SUM(CASE WHEN stage = 'won' THEN expected_amount ELSE 0 END) as revenue,
                    COUNT(CASE WHEN stage = 'won' THEN 1 END) as conversions
                FROM leads
                WHERE source IN ('Website', 'Social Media', 'Google Ads', 'Facebook', 'Instagram', 'Email Campaign', 'Referral')
                GROUP BY source
                ORDER BY revenue DESC
            ");
            $roi = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'monthly_data' => [
                    'labels' => $monthly_labels,
                    'leads' => $monthly_leads,
                    'conversions' => $monthly_conversions
                ],
                'roi_data' => $roi
            ]);
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
<title>Marketing Dashboard | CIBIL Repair</title>

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
.stat-card.red::after { background: linear-gradient(90deg, #dc2626, #f87171); }

.stat-icon { font-size: 20px; margin-bottom: 4px; display: block; }
.stat-value { font-size: 28px; font-weight: 800; line-height: 1.2; }
.stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }
.stat-change { font-size: 12px; color: var(--text-muted); margin-top: 6px; }

/* CHARTS */
.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
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
.badge-purple { background: var(--purple-bg); color: var(--purple); }

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
.btn-danger { background: var(--danger-bg); color: var(--danger-text); border: 1px solid rgba(220,38,38,0.15); }
.btn-danger:hover { background: var(--danger); color: #fff; }
.btn-success { background: var(--success-bg); color: var(--success-text); border: 1px solid rgba(5,150,105,0.15); }
.btn-success:hover { background: var(--success); color: #fff; }
.btn-sm { padding: 5px 12px; font-size: 12px; }
.btn-xs { padding: 3px 8px; font-size: 11px; }

/* FORMS */
.form-group { margin-bottom: 16px; }
.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-secondary);
}
.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-primary);
    transition: border-color var(--transition);
    outline: none;
    font-size: 13px;
}
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--brand); }
.form-textarea { resize: vertical; min-height: 80px; }
.form-row { display: flex; gap: 12px; flex-wrap: wrap; }
.form-group.flex-1 { flex: 1; min-width: 150px; }

/* FILTER BAR */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}
.search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
}
.search-wrap i {
    position: absolute;
    left: 10px; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}
.search-input {
    width: 100%;
    padding: 8px 12px 8px 32px;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    font-size: 13px;
    color: var(--text-primary);
    outline: none;
}
.search-input:focus { border-color: var(--brand); }

/* MODAL */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 16px;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--bg-surface);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 550px;
    max-height: 92vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    animation: modalIn 0.25s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(16px); } }
.modal-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-title { font-size: 16px; font-weight: 700; }
.modal-close {
    width: 32px; height: 32px;
    background: var(--bg-sunken);
    border: none;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-secondary);
    font-size: 16px;
}
.modal-close:hover { background: var(--danger-bg); color: var(--danger); }
.modal-body { padding: 20px; }
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

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
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .charts-row { grid-template-columns: 1fr; }
    .topbar-right .clock-badge { display: none; }
}
@media (max-width: 600px) {
    .content { padding: 14px; }
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .stat-value { font-size: 22px; }
    .form-row { flex-direction: column; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">MD</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Marketing Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Campaigns</div>
        <div class="nav-item" data-section="campaigns">
            <i class="fas fa-bullhorn"></i>
            <span class="nav-label">Campaigns</span>
        </div>
        <div class="nav-item" data-section="email">
            <i class="fas fa-envelope"></i>
            <span class="nav-label">Email Marketing</span>
        </div>
        <div class="nav-item" data-section="social">
            <i class="fas fa-share-alt"></i>
            <span class="nav-label">Social Media</span>
        </div>
        <div class="nav-section-label">Analytics</div>
        <div class="nav-item" data-section="leads">
            <i class="fas fa-users"></i>
            <span class="nav-label">Lead Generation</span>
        </div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Analytics & ROI</span>
        </div>
        <div class="nav-section-label">Content</div>
        <div class="nav-item" data-section="content">
            <i class="fas fa-file-alt"></i>
            <span class="nav-label">Content Library</span>
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
            <span class="page-title" id="pageTitle">Marketing Dashboard</span>
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
            <div class="stats-grid">
                <div class="stat-card green">
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="stat-value" id="totalLeads">—</div>
                    <div class="stat-label">Total Leads (Marketing)</div>
                    <div class="stat-change" id="newLeads">New this month: —</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="conversions">—</div>
                    <div class="stat-label">Conversions</div>
                    <div class="stat-change" id="conversionRate">Rate: —%</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="stat-value" id="revenue">—</div>
                    <div class="stat-label">Revenue Generated</div>
                    <div class="stat-change">From marketing leads</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-bullhorn"></i></span>
                    <div class="stat-value" id="activeCampaigns">—</div>
                    <div class="stat-label">Active Campaigns</div>
                    <div class="stat-change">Running now</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Lead Generation Trend</div>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="leadTrendChart"></canvas>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Lead Sources</div>
                    </div>
                    <div class="card-body chart-wrap" style="max-height:200px;">
                        <canvas id="sourceChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-bar"></i> Top Campaigns</div>
                    </div>
                    <div class="card-body chart-wrap" style="max-height:200px;">
                        <canvas id="campaignChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Campaigns</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addCampaignModal')"><i class="fas fa-plus"></i> New Campaign</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Campaign</th><th>Type</th><th>Budget</th><th>Leads</th><th>Conversions</th><th>Revenue</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="recentCampaignsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CAMPAIGNS ====== -->
        <div class="section" id="campaignsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bullhorn"></i> All Campaigns</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addCampaignModal')"><i class="fas fa-plus"></i> New Campaign</button>
                        <button class="btn btn-success btn-sm" onclick="exportCampaigns()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="campaignStatusFilter" onchange="loadCampaigns()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                    </select>
                    <button class="btn btn-outline btn-sm" onclick="document.getElementById('campaignStatusFilter').value='';loadCampaigns();"><i class="fas fa-undo"></i> Reset</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Channel</th><th>Budget</th><th>Leads</th><th>Conversions</th><th>Revenue</th><th>ROI</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="campaignsBody">
                            <tr><td colspan="10"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== EMAIL MARKETING ====== -->
        <div class="section" id="emailSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-envelope"></i> Email Campaigns</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addEmailModal')"><i class="fas fa-plus"></i> New Email</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Subject</th><th>Recipients</th><th>Sent</th><th>Opens</th><th>Clicks</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="emailBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SOCIAL MEDIA ====== -->
        <div class="section" id="socialSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-share-alt"></i> Social Media Performance</div>
                    <button class="btn btn-success btn-sm" onclick="exportSocial()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Platform</th><th>Followers</th><th>Engagement</th><th>Reach</th><th>Impressions</th><th>Posts</th></tr>
                        </thead>
                        <tbody id="socialBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== LEAD GENERATION ====== -->
        <div class="section" id="leadsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Lead Generation Overview</div>
                    <button class="btn btn-success btn-sm" onclick="exportLeadsReport()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card green"><div class="stat-value" id="leadTotal">—</div><div class="stat-label">Total Leads</div></div>
                    <div class="stat-card amber"><div class="stat-value" id="leadQualified">—</div><div class="stat-label">Qualified</div></div>
                    <div class="stat-card purple"><div class="stat-value" id="leadConverted">—</div><div class="stat-label">Converted</div></div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Source</th><th>Leads</th><th>Conversions</th><th>Conversion Rate</th><th>Revenue</th></tr>
                        </thead>
                        <tbody id="leadSourceBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS & ROI ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Monthly Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> ROI by Channel</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Channel</th><th>Leads</th><th>Conversions</th><th>Revenue</th><th>ROI</th></tr>
                        </thead>
                        <tbody id="roiBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CONTENT LIBRARY ====== -->
        <div class="section" id="contentSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-alt"></i> Content Library</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addContentModal')"><i class="fas fa-plus"></i> Add Content</button>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="contentSearch" placeholder="Search content…" oninput="filterContent()">
                    </div>
                    <select class="form-select" id="contentTypeFilter" onchange="filterContent()" style="width:150px;padding:8px 12px;">
                        <option value="">All Types</option>
                        <option value="blog">Blog</option>
                        <option value="social">Social Post</option>
                        <option value="email">Email Template</option>
                        <option value="video">Video</option>
                        <option value="whitepaper">Whitepaper</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Title</th><th>Type</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="contentBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Campaign Modal -->
<div class="modal-overlay" id="addCampaignModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-bullhorn"></i> New Campaign</span>
            <button class="modal-close" onclick="closeModal('addCampaignModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Campaign Name <span class="form-required">*</span></label>
                <input class="form-input" id="campaignName" placeholder="e.g., Summer Sale 2024">
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="campaignType">
                        <option value="email">Email</option>
                        <option value="social">Social Media</option>
                        <option value="paid">Paid Ads</option>
                        <option value="content">Content</option>
                        <option value="referral">Referral</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Channel</label>
                    <select class="form-select" id="campaignChannel">
                        <option value="Website">Website</option>
                        <option value="Facebook">Facebook</option>
                        <option value="Instagram">Instagram</option>
                        <option value="Google Ads">Google Ads</option>
                        <option value="Email">Email</option>
                        <option value="LinkedIn">LinkedIn</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Budget (₹)</label>
                    <input type="number" class="form-input" id="campaignBudget" placeholder="0">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Target Audience</label>
                    <input class="form-input" id="campaignAudience" placeholder="e.g., Homeowners, 25-45">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-input" id="campaignStart">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-input" id="campaignEnd">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Objective</label>
                <textarea class="form-textarea" id="campaignObjective" rows="2" placeholder="Campaign objective…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addCampaignModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addCampaign()"><i class="fas fa-save"></i> Create Campaign</button>
        </div>
    </div>
</div>

<!-- Add Email Modal -->
<div class="modal-overlay" id="addEmailModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-envelope"></i> New Email Campaign</span>
            <button class="modal-close" onclick="closeModal('addEmailModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Subject <span class="form-required">*</span></label>
                <input class="form-input" id="emailSubject" placeholder="Email subject line">
            </div>
            <div class="form-group">
                <label class="form-label">Recipients</label>
                <select class="form-select" id="emailRecipients">
                    <option value="all">All Leads</option>
                    <option value="qualified">Qualified Leads</option>
                    <option value="customers">Customers</option>
                    <option value="past_clients">Past Clients</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea class="form-textarea" id="emailContent" rows="5" placeholder="Email content…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addEmailModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addEmail()"><i class="fas fa-save"></i> Create Email</button>
        </div>
    </div>
</div>

<!-- Add Content Modal -->
<div class="modal-overlay" id="addContentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-file-alt"></i> Add Content</span>
            <button class="modal-close" onclick="closeModal('addContentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Title <span class="form-required">*</span></label>
                <input class="form-input" id="contentTitle" placeholder="Content title">
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="contentType">
                        <option value="blog">Blog</option>
                        <option value="social">Social Post</option>
                        <option value="email">Email Template</option>
                        <option value="video">Video</option>
                        <option value="whitepaper">Whitepaper</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="contentStatus">
                        <option value="draft">Draft</option>
                        <option value="review">In Review</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea class="form-textarea" id="contentBodyText" rows="4" placeholder="Content body…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addContentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addContent()"><i class="fas fa-save"></i> Add Content</button>
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
    localStorage.setItem('marketingTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('marketingTheme') || 'light'); })();

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
    dashboard: 'Marketing Dashboard',
    campaigns: 'Campaigns',
    email: 'Email Marketing',
    social: 'Social Media',
    leads: 'Lead Generation',
    analytics: 'Analytics & ROI',
    content: 'Content Library'
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
        campaigns: loadCampaigns,
        email: loadEmails,
        social: loadSocial,
        leads: loadLeadSources,
        analytics: loadAnalytics,
        content: loadContent
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

// ── MODAL ─────────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.onclick = (e) => { if (e.target === m) m.classList.remove('open'); };
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ── HELPERS ───────────────────────────────────────────────────────────
function getStatusBadge(status) {
    const map = {
        'active': 'badge-success',
        'draft': 'badge-gray',
        'paused': 'badge-warning',
        'completed': 'badge-info',
        'published': 'badge-success',
        'review': 'badge-warning',
        'archived': 'badge-gray'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${escHtml(status)}</span>`;
}

function getROI(revenue, budget) {
    if (!budget || budget <= 0) return '0%';
    return Math.round((revenue / budget) * 100) + '%';
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

// ── DASHBOARD ─────────────────────────────────────────────────────────
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalLeads').textContent = data.total_leads || 0;
    document.getElementById('conversions').textContent = data.conversions || 0;
    document.getElementById('revenue').textContent = '₹' + (data.revenue || 0).toLocaleString();
    document.getElementById('newLeads').innerHTML = `New this month: ${data.new_leads || 0}`;
    document.getElementById('conversionRate').innerHTML = `Rate: ${data.conversion_rate || 0}%`;

    // Active campaigns count
    const active = data.campaigns?.filter(c => c.status === 'active').length || 0;
    document.getElementById('activeCampaigns').textContent = active;

    // Lead trend chart
    if (data.lead_trend) {
        destroyChart('leadTrendChart');
        const ctx = document.getElementById('leadTrendChart').getContext('2d');
        charts.leadTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.lead_trend.labels || [],
                datasets: [{
                    label: 'Leads',
                    data: data.lead_trend.values || [],
                    borderColor: '#0d9e78',
                    backgroundColor: 'rgba(13,158,120,0.1)',
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } }
                }
            }
        });
    }

    // Source distribution chart
    if (data.source_distribution && data.source_distribution.length) {
        destroyChart('sourceChart');
        const ctx = document.getElementById('sourceChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#ec489a', '#14b8a6', '#f97316'];
        charts.sourceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.source_distribution.map(s => s.source || 'Unknown'),
                datasets: [{
                    data: data.source_distribution.map(s => s.count || 0),
                    backgroundColor: colors.slice(0, data.source_distribution.length),
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

    // Campaign chart
    if (data.campaigns && data.campaigns.length) {
        destroyChart('campaignChart');
        const ctx = document.getElementById('campaignChart').getContext('2d');
        charts.campaignChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.campaigns.map(c => c.name || 'Unnamed').slice(0, 5),
                datasets: [{
                    label: 'Leads Generated',
                    data: data.campaigns.map(c => c.leads || 0).slice(0, 5),
                    backgroundColor: '#0d9e78',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor() } }
                }
            }
        });
    }

    // Recent campaigns table
    const body = document.getElementById('recentCampaignsBody');
    if (data.campaigns && data.campaigns.length) {
        body.innerHTML = data.campaigns.slice(0, 5).map(c => `
            <tr>
                <td><strong>${escHtml(c.name)}</strong></td>
                <td>${escHtml(c.type || '—')}</td>
                <td>₹${(c.budget || 0).toLocaleString()}</td>
                <td>${c.leads || 0}</td>
                <td>${c.conversions || 0}</td>
                <td>₹${(c.revenue || 0).toLocaleString()}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    <button class="btn btn-ghost btn-xs" onclick="toggleCampaign(${c.id}, '${c.status}')"><i class="fas fa-play"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteCampaign(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-bullhorn"></i><p>No campaigns found</p></div></td></tr>';
    }
}

// ── CAMPAIGNS ─────────────────────────────────────────────────────────
async function loadCampaigns() {
    const status = document.getElementById('campaignStatusFilter')?.value || '';
    const data = await apiCall(`get_campaigns?status=${status}`);
    const body = document.getElementById('campaignsBody');
    if (data.success && data.campaigns && data.campaigns.length) {
        body.innerHTML = data.campaigns.map(c => `
            <tr>
                <td><strong>${escHtml(c.name)}</strong></td>
                <td>${escHtml(c.type || '—')}</td>
                <td>${escHtml(c.channel || '—')}</td>
                <td>₹${(c.budget || 0).toLocaleString()}</td>
                <td>${c.lead_count || 0}</td>
                <td>${c.conversion_count || 0}</td>
                <td>₹${(c.revenue || 0).toLocaleString()}</td>
                <td>${getROI(c.revenue || 0, c.budget || 0)}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    <button class="btn btn-ghost btn-xs" onclick="toggleCampaign(${c.id}, '${c.status}')"><i class="fas fa-play"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteCampaign(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="10"><div class="empty-state"><i class="fas fa-bullhorn"></i><p>No campaigns found</p></div></td></tr>';
    }
}

// ── ADD CAMPAIGN ─────────────────────────────────────────────────────
async function addCampaign() {
    const data = {
        name: document.getElementById('campaignName').value.trim(),
        type: document.getElementById('campaignType').value,
        channel: document.getElementById('campaignChannel').value,
        budget: parseFloat(document.getElementById('campaignBudget').value) || 0,
        target_audience: document.getElementById('campaignAudience').value.trim(),
        start_date: document.getElementById('campaignStart').value,
        end_date: document.getElementById('campaignEnd').value,
        objective: document.getElementById('campaignObjective').value.trim()
    };

    if (!data.name) { showToast('Campaign name is required', 'warning'); return; }

    const result = await apiCall('add_campaign', 'POST', data);
    if (result.success) {
        showToast('Campaign created successfully!', 'success');
        closeModal('addCampaignModal');
        document.getElementById('campaignName').value = '';
        document.getElementById('campaignBudget').value = '';
        document.getElementById('campaignAudience').value = '';
        document.getElementById('campaignObjective').value = '';
        loadDashboard();
        loadCampaigns();
    } else {
        showToast(result.error || 'Failed to create campaign', 'error');
    }
}

// ── UPDATE CAMPAIGN STATUS ──────────────────────────────────────────
async function toggleCampaign(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    const result = await apiCall('update_campaign_status', 'POST', { campaign_id: id, status: newStatus });
    if (result.success) {
        showToast(`Campaign ${newStatus}`, 'success');
        loadDashboard();
        loadCampaigns();
    } else {
        showToast(result.error || 'Failed to update campaign', 'error');
    }
}

// ── DELETE CAMPAIGN ──────────────────────────────────────────────────
async function deleteCampaign(id) {
    if (!confirm('Delete this campaign?')) return;
    const result = await apiCall('delete_campaign', 'POST', { campaign_id: id });
    if (result.success) {
        showToast('Campaign deleted', 'success');
        loadDashboard();
        loadCampaigns();
    } else {
        showToast(result.error || 'Failed to delete campaign', 'error');
    }
}

// ── EMAIL MARKETING ──────────────────────────────────────────────────
async function loadEmails() {
    const data = await apiCall('get_email_campaigns');
    const body = document.getElementById('emailBody');
    if (data.success && data.emails && data.emails.length) {
        body.innerHTML = data.emails.map(e => `
            <tr>
                <td><strong>${escHtml(e.subject)}</strong></td>
                <td>${escHtml(e.recipients || '—')}</td>
                <td>${e.sent_count || 0}</td>
                <td>${e.open_count || 0}</td>
                <td>${e.click_count || 0}</td>
                <td>${getStatusBadge(e.status)}</td>
                <td>
                    <button class="btn btn-ghost btn-xs" onclick="showToast('Email preview coming soon','info')"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-envelope"></i><p>No email campaigns</p></div></td></tr>';
    }
}

// ── ADD EMAIL ────────────────────────────────────────────────────────
async function addEmail() {
    const data = {
        subject: document.getElementById('emailSubject').value.trim(),
        content: document.getElementById('emailContent').value.trim(),
        recipients: document.getElementById('emailRecipients').value,
        status: 'draft'
    };

    if (!data.subject) { showToast('Email subject is required', 'warning'); return; }

    const result = await apiCall('add_email_campaign', 'POST', data);
    if (result.success) {
        showToast('Email campaign created!', 'success');
        closeModal('addEmailModal');
        document.getElementById('emailSubject').value = '';
        document.getElementById('emailContent').value = '';
        loadEmails();
    } else {
        showToast(result.error || 'Failed to create email', 'error');
    }
}

// ── SOCIAL MEDIA ─────────────────────────────────────────────────────
async function loadSocial() {
    const data = await apiCall('get_social_media_stats');
    const body = document.getElementById('socialBody');
    if (data.success && data.stats && data.stats.length) {
        body.innerHTML = data.stats.map(s => `
            <tr>
                <td><strong>${escHtml(s.platform)}</strong></td>
                <td>${(s.followers || 0).toLocaleString()}</td>
                <td>${(s.engagement || 0).toLocaleString()}</td>
                <td>${(s.reach || 0).toLocaleString()}</td>
                <td>${(s.impressions || 0).toLocaleString()}</td>
                <td>${s.posts || 0}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-share-alt"></i><p>No social media data</p></div></td></tr>';
    }
}

// ── LEAD GENERATION ──────────────────────────────────────────────────
async function loadLeadSources() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load data', 'error'); return; }

    // Update stats
    document.getElementById('leadTotal').textContent = data.total_leads || 0;
    document.getElementById('leadQualified').textContent = data.conversions || 0;
    document.getElementById('leadConverted').textContent = data.conversions || 0;

    // Source table
    const body = document.getElementById('leadSourceBody');
    if (data.source_distribution && data.source_distribution.length) {
        body.innerHTML = data.source_distribution.map(s => {
            const conv = s.conversions || 0;
            const rate = s.count > 0 ? Math.round((conv / s.count) * 100) : 0;
            return `
                <tr>
                    <td><strong>${escHtml(s.source || 'Unknown')}</strong></td>
                    <td>${s.count || 0}</td>
                    <td>${conv}</td>
                    <td>${rate}%</td>
                    <td>—</td>
                </tr>
            `;
        }).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-users"></i><p>No lead data</p></div></td></tr>';
    }
}

// ── ANALYTICS & ROI ──────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }

    // Monthly chart
    if (data.monthly_data) {
        destroyChart('monthlyChart');
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        charts.monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.monthly_data.labels || [],
                datasets: [
                    {
                        label: 'Leads',
                        data: data.monthly_data.leads || [],
                        backgroundColor: '#0d9e78',
                        borderRadius: 4
                    },
                    {
                        label: 'Conversions',
                        data: data.monthly_data.conversions || [],
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } }
                }
            }
        });
    }

    // ROI table
    const body = document.getElementById('roiBody');
    if (data.roi_data && data.roi_data.length) {
        body.innerHTML = data.roi_data.map(r => `
            <tr>
                <td><strong>${escHtml(r.channel || 'Unknown')}</strong></td>
                <td>${r.leads || 0}</td>
                <td>${r.conversions || 0}</td>
                <td>₹${(r.revenue || 0).toLocaleString()}</td>
                <td>${r.revenue > 0 ? '✅ Positive' : '—'}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-chart-line"></i><p>No ROI data</p></div></td></tr>';
    }
}

// ── CONTENT LIBRARY ──────────────────────────────────────────────────
let allContent = [];

async function loadContent() {
    // This would normally fetch from an API
    // For demo, we'll use sample data
    allContent = [
        { id: 1, title: 'How to Improve CIBIL Score', type: 'blog', status: 'published', created_at: '2024-01-15' },
        { id: 2, title: '5 Common Credit Report Errors', type: 'blog', status: 'draft', created_at: '2024-01-20' },
        { id: 3, title: 'Credit Repair Success Stories', type: 'social', status: 'published', created_at: '2024-01-22' },
    ];
    renderContent(allContent);
}

function renderContent(list) {
    const body = document.getElementById('contentBody');
    if (list && list.length) {
        body.innerHTML = list.map(c => `
            <tr>
                <td><strong>${escHtml(c.title)}</strong></td>
                <td><span class="badge badge-gray">${escHtml(c.type)}</span></td>
                <td>${getStatusBadge(c.status)}</td>
                <td>${c.created_at || '—'}</td>
                <td>
                    <button class="btn btn-ghost btn-xs"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No content found</p></div></td></tr>';
    }
}

function filterContent() {
    const search = document.getElementById('contentSearch')?.value?.toLowerCase() || '';
    const type = document.getElementById('contentTypeFilter')?.value || '';
    let filtered = allContent;
    if (search) {
        filtered = filtered.filter(c => c.title.toLowerCase().includes(search));
    }
    if (type) {
        filtered = filtered.filter(c => c.type === type);
    }
    renderContent(filtered);
}

async function addContent() {
    showToast('Content added successfully!', 'success');
    closeModal('addContentModal');
    document.getElementById('contentTitle').value = '';
    document.getElementById('contentBodyText').value = '';
    loadContent();
}

// ── EXPORT FUNCTIONS ──────────────────────────────────────────────────
function exportCampaigns() { showToast('Exporting campaigns...', 'info'); window.open('api/marketing/export_campaigns.php', '_blank'); }
function exportSocial() { showToast('Exporting social media data...', 'info'); window.open('api/marketing/export_social.php', '_blank'); }
function exportLeadsReport() { showToast('Exporting leads report...', 'info'); window.open('api/marketing/export_leads.php', '_blank'); }

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addCampaignModal') {
                const start = document.getElementById('campaignStart');
                const end = document.getElementById('campaignEnd');
                if (start && !start.value) start.value = new Date().toISOString().split('T')[0];
                if (end) {
                    const d = new Date();
                    d.setMonth(d.getMonth() + 1);
                    end.value = d.toISOString().split('T')[0];
                }
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'c') showSection('campaigns');
    if (e.altKey && e.key === 'a') showSection('analytics');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadContent();

console.log('✅ Marketing Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>