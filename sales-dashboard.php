<?php
// ============================================================
// SALES DASHBOARD - FULLY INTEGRATED
// Access: sales_team, admin, manager, super_admin
// Purpose: Lead management, sales funnel, commissions, targets
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

// ── AUTH: allow sales_team, admin, manager, super_admin ──────────────
$allowed_roles = ['sales_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Sales Executive';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$csrf = $_SESSION['csrf_token'];

// ── Get Employee ID ──────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
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
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            // Total leads
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ?");
            $stmt->execute([$emp_id]);
            $total_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Won leads
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ? AND stage = 'won'");
            $stmt->execute([$emp_id]);
            $won_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Total revenue
            $stmt = $pdo->prepare("SELECT SUM(expected_amount) as total FROM leads WHERE assigned_to = ? AND stage = 'won'");
            $stmt->execute([$emp_id]);
            $total_revenue = (float)($stmt->fetch()['total'] ?? 0);
            
            // Pipeline value
            $stmt = $pdo->prepare("SELECT SUM(expected_amount) as total FROM leads WHERE assigned_to = ? AND stage NOT IN ('won', 'lost')");
            $stmt->execute([$emp_id]);
            $pipeline_value = (float)($stmt->fetch()['total'] ?? 0);
            
            // New leads this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
            $stmt->execute([$emp_id, date('m'), date('Y')]);
            $new_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Conversion rate
            $conversion_rate = $total_leads > 0 ? round(($won_leads / $total_leads) * 100) : 0;
            
            // Average deal size
            $avg_deal_size = $won_leads > 0 ? round($total_revenue / $won_leads) : 0;
            
            // Lead trend (last 6 months)
            $trend_labels = [];
            $trend_values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $trend_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE assigned_to = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$emp_id, $date]);
                $trend_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Recent leads
            $stmt = $pdo->prepare("
                SELECT l.*, c.name as client_name 
                FROM leads l
                LEFT JOIN customers c ON l.customer_id = c.id
                WHERE l.assigned_to = ?
                ORDER BY l.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$emp_id]);
            $recent_leads = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_leads' => $total_leads,
                'won_leads' => $won_leads,
                'total_revenue' => $total_revenue,
                'pipeline_value' => $pipeline_value,
                'new_leads' => $new_leads,
                'conversion_rate' => $conversion_rate,
                'avg_deal_size' => $avg_deal_size,
                'lead_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
                'recent_leads' => $recent_leads
            ]);
            exit;
        }
        
        // ── GET LEADS ────────────────────────────────────────────────
        if ($action === 'get_leads') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            $search = $_GET['search'] ?? '';
            $stage = $_GET['stage'] ?? '';
            
            $sql = "SELECT l.*, c.name as client_name 
                    FROM leads l
                    LEFT JOIN customers c ON l.customer_id = c.id
                    WHERE l.assigned_to = ?";
            $params = [$emp_id];
            
            if ($search) {
                $sql .= " AND (c.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($stage) {
                $sql .= " AND l.stage = ?";
                $params[] = $stage;
            }
            
            $sql .= " ORDER BY l.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'leads' => $leads]);
            exit;
        }
        
        // ── ADD LEAD ─────────────────────────────────────────────────
        if ($action === 'add_lead') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $employee_id = (int)($input['employee_id'] ?? 0);
            $client_name = $input['client_name'] ?? '';
            $client_phone = $input['client_phone'] ?? '';
            $client_email = $input['client_email'] ?? '';
            $service_interest = $input['service_interest'] ?? '';
            $expected_amount = (float)($input['expected_amount'] ?? 0);
            $probability = (int)($input['probability'] ?? 60);
            $source = $input['source'] ?? 'Website';
            $expected_close_date = $input['expected_close_date'] ?? null;
            $notes = $input['notes'] ?? '';
            
            if (empty($client_name)) {
                echo json_encode(['success' => false, 'error' => 'Client name is required']);
                exit;
            }
            
            // Check if customer exists, create if not
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND phone = ?");
            $stmt->execute([$client_name, $client_phone]);
            $customer = $stmt->fetch();
            
            if ($customer) {
                $customer_id = $customer['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                $stmt->execute([$client_name, $client_phone, $client_email]);
                $customer_id = $pdo->lastInsertId();
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO leads (customer_id, assigned_to, service_type, expected_amount, 
                                   probability, source, expected_close_date, notes, stage, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())
            ");
            $stmt->execute([
                $customer_id, $employee_id, $service_interest, $expected_amount,
                $probability, $source, $expected_close_date, $notes
            ]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Lead Added', "New lead: $client_name"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE LEAD STAGE ────────────────────────────────────────
        if ($action === 'update_lead_stage') {
            $input = json_decode(file_get_contents('php://input'), true);
            $lead_id = (int)($input['lead_id'] ?? 0);
            $stage = $input['stage'] ?? '';
            $notes = $input['notes'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE leads SET stage = ?, notes = CONCAT(notes, ?), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$stage, "\n[Stage Update] " . date('Y-m-d H:i') . ": " . $notes, $lead_id]);
            
            // If won, create customer record
            if ($stage === 'won') {
                $stmt = $pdo->prepare("SELECT customer_id FROM leads WHERE id = ?");
                $stmt->execute([$lead_id]);
                $lead = $stmt->fetch();
                if ($lead && $lead['customer_id']) {
                    $stmt = $pdo->prepare("UPDATE customers SET status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$lead['customer_id']]);
                }
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE LEAD ──────────────────────────────────────────────
        if ($action === 'delete_lead') {
            $input = json_decode(file_get_contents('php://input'), true);
            $lead_id = (int)($input['lead_id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE leads SET status = 'deleted', deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$lead_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SALES FUNNEL ──────────────────────────────────────────
        if ($action === 'get_sales_funnel') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
            $stage_counts = [];
            
            foreach ($stages as $stage) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE assigned_to = ? AND stage = ?");
                $stmt->execute([$emp_id, $stage]);
                $stage_counts[$stage] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            echo json_encode(['success' => true, 'stages' => $stage_counts]);
            exit;
        }
        
        // ── GET ACTIVITIES ────────────────────────────────────────────
        if ($action === 'get_activities') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            $stmt = $pdo->prepare("
                SELECT a.*, l.customer_id, c.name as lead_name
                FROM activities a
                LEFT JOIN leads l ON a.lead_id = l.id
                LEFT JOIN customers c ON l.customer_id = c.id
                WHERE a.employee_id = ?
                ORDER BY a.activity_date DESC
                LIMIT 50
            ");
            $stmt->execute([$emp_id]);
            $activities = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'activities' => $activities]);
            exit;
        }
        
        // ── ADD ACTIVITY ──────────────────────────────────────────────
        if ($action === 'add_activity') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $employee_id = (int)($input['employee_id'] ?? 0);
            $lead_id = (int)($input['lead_id'] ?? 0);
            $activity_type = $input['activity_type'] ?? '';
            $subject = $input['subject'] ?? '';
            $description = $input['description'] ?? '';
            $activity_date = $input['activity_date'] ?? date('Y-m-d H:i:s');
            $outcome = $input['outcome'] ?? '';
            
            if (!$lead_id) {
                echo json_encode(['success' => false, 'error' => 'Please select a lead']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO activities (employee_id, lead_id, activity_type, subject, description, activity_date, outcome, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$employee_id, $lead_id, $activity_type, $subject, $description, $activity_date, $outcome]);
            
            // Update lead last_contact
            $stmt = $pdo->prepare("UPDATE leads SET last_contact = NOW() WHERE id = ?");
            $stmt->execute([$lead_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE ACTIVITY ───────────────────────────────────────────
        if ($action === 'delete_activity') {
            $input = json_decode(file_get_contents('php://input'), true);
            $activity_id = (int)($input['activity_id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
            $stmt->execute([$activity_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET TARGETS ───────────────────────────────────────────────
        if ($action === 'get_targets') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            // Current month target
            $stmt = $pdo->prepare("SELECT * FROM sales_targets WHERE employee_id = ? AND month = ? AND year = ?");
            $stmt->execute([$emp_id, date('m'), date('Y')]);
            $current_target = $stmt->fetch();
            
            // Achieved amount
            $stmt = $pdo->prepare("SELECT SUM(expected_amount) as achieved FROM leads WHERE assigned_to = ? AND stage = 'won' AND MONTH(updated_at) = ? AND YEAR(updated_at) = ?");
            $stmt->execute([$emp_id, date('m'), date('Y')]);
            $achieved = (float)($stmt->fetch()['achieved'] ?? 0);
            
            // History
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       (SELECT SUM(expected_amount) FROM leads WHERE assigned_to = ? AND stage = 'won' AND MONTH(updated_at) = t.month AND YEAR(updated_at) = t.year) as achieved
                FROM sales_targets t
                WHERE t.employee_id = ?
                ORDER BY t.year DESC, t.month DESC
                LIMIT 12
            ");
            $stmt->execute([$emp_id, $emp_id]);
            $history = $stmt->fetchAll();
            
            // Calculate percentages
            foreach ($history as &$h) {
                $h['percentage'] = $h['amount'] > 0 ? round(($h['achieved'] ?? 0) / $h['amount'] * 100) : 0;
            }
            
            echo json_encode([
                'success' => true,
                'current_target' => $current_target,
                'achieved' => $achieved,
                'history' => $history
            ]);
            exit;
        }
        
        // ── SET TARGET ─────────────────────────────────────────────────
        if ($action === 'set_target') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $employee_id = (int)($input['employee_id'] ?? 0);
            $month = (int)($input['month'] ?? date('m'));
            $year = (int)($input['year'] ?? date('Y'));
            $target_amount = (float)($input['target_amount'] ?? 0);
            
            $stmt = $pdo->prepare("
                INSERT INTO sales_targets (employee_id, month, year, amount, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE amount = ?, updated_at = NOW()
            ");
            $stmt->execute([$employee_id, $month, $year, $target_amount, $target_amount]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET COMMISSIONS ───────────────────────────────────────────
        if ($action === 'get_commissions') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            // Totals
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(commission_amount) as total,
                    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid
                FROM commissions
                WHERE employee_id = ?
            ");
            $stmt->execute([$emp_id]);
            $totals = $stmt->fetch();
            
            // Commission list
            $stmt = $pdo->prepare("
                SELECT c.*, l.customer_id, cust.name as client_name
                FROM commissions c
                LEFT JOIN leads l ON c.lead_id = l.id
                LEFT JOIN customers cust ON l.customer_id = cust.id
                WHERE c.employee_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$emp_id]);
            $commissions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total' => (float)($totals['total'] ?? 0),
                'pending' => (float)($totals['pending'] ?? 0),
                'paid' => (float)($totals['paid'] ?? 0),
                'commissions' => $commissions
            ]);
            exit;
        }
        
        // ── GET PERFORMANCE ───────────────────────────────────────────
        if ($action === 'get_performance') {
            $emp_id = (int)($_GET['employee_id'] ?? $employee_id);
            
            $labels = [];
            $revenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT SUM(expected_amount) as total FROM leads WHERE assigned_to = ? AND stage = 'won' AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
                $stmt->execute([$emp_id, $date]);
                $revenue[] = (float)($stmt->fetch()['total'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'monthly_performance' => ['labels' => $labels, 'revenue' => $revenue]
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
<title>Sales Dashboard | CIBIL Repair</title>

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

/* STAGE BADGES */
.stage-new { background: #e0f2fe; color: #0369a1; }
.stage-contacted { background: #fef3c7; color: #b45309; }
.stage-qualified { background: #dcfce7; color: #166534; }
.stage-proposal { background: #f3e8ff; color: #6b21a5; }
.stage-negotiation { background: #fee2e2; color: #b91c1c; }
.stage-won { background: #d1fae5; color: #065f46; }
.stage-lost { background: #fecaca; color: #991b1b; }

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

/* FUNNEL */
.funnel-container { display: flex; flex-direction: column; gap: 12px; margin-top: 16px; }
.funnel-stage {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px; background: var(--bg-sunken);
    border-radius: var(--radius-md);
    transition: all var(--transition);
}
.funnel-stage:hover { background: var(--bg-surface); box-shadow: var(--shadow-sm); }
.funnel-stage-name { width: 110px; font-weight: 600; font-size: 13px; }
.funnel-stage-bar { flex: 1; height: 28px; background: var(--border); border-radius: 99px; overflow: hidden; position: relative; }
.funnel-stage-fill {
    height: 100%;
    display: flex; align-items: center; justify-content: flex-end;
    padding-right: 12px; color: white; font-size: 12px; font-weight: 700;
    border-radius: 99px;
    transition: width 0.8s ease;
}
.funnel-stage-count { width: 50px; text-align: right; font-weight: 700; font-size: 14px; }

/* PROGRESS */
.progress-bar { height: 8px; background: var(--border); border-radius: 99px; overflow: hidden; margin-top: 4px; }
.progress-fill { height: 100%; border-radius: 99px; transition: width 0.8s ease; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .topbar-right .clock-badge { display: none; }
}
@media (max-width: 600px) {
    .content { padding: 14px; }
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .stat-value { font-size: 22px; }
    .form-row { flex-direction: column; }
    .funnel-stage-name { width: 80px; font-size: 12px; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">SD</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Sales Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Leads</div>
        <div class="nav-item" data-section="leads">
            <i class="fas fa-filter"></i>
            <span class="nav-label">My Leads</span>
        </div>
        <div class="nav-item" data-section="funnel">
            <i class="fas fa-chart-pie"></i>
            <span class="nav-label">Sales Funnel</span>
        </div>
        <div class="nav-section-label">Activities</div>
        <div class="nav-item" data-section="activities">
            <i class="fas fa-history"></i>
            <span class="nav-label">Activities</span>
        </div>
        <div class="nav-section-label">Performance</div>
        <div class="nav-item" data-section="targets">
            <i class="fas fa-bullseye"></i>
            <span class="nav-label">Targets</span>
        </div>
        <div class="nav-item" data-section="commissions">
            <i class="fas fa-rupee-sign"></i>
            <span class="nav-label">Commissions</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="reports">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Reports</span>
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
            <span class="page-title" id="pageTitle">Sales Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                    <div class="stat-value" id="totalLeads">—</div>
                    <div class="stat-label">Total Leads</div>
                    <div class="stat-change" id="newLeads">New this month: —</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-trophy"></i></span>
                    <div class="stat-value" id="wonLeads">—</div>
                    <div class="stat-label">Won Deals</div>
                    <div class="stat-change" id="conversionRate">Conversion Rate: —%</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="stat-value" id="totalRevenue">—</div>
                    <div class="stat-label">Revenue Generated</div>
                    <div class="stat-change" id="revenueTarget">Target: —</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="stat-value" id="pipelineValue">—</div>
                    <div class="stat-label">Pipeline Value</div>
                    <div class="stat-change" id="avgDealSize">Avg Deal: —</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Lead Trend</div>
                    <select id="trendPeriod" class="form-select" style="width:110px;padding:8px 12px;" onchange="loadDashboard()">
                        <option value="week">Weekly</option>
                        <option value="month" selected>Monthly</option>
                    </select>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="leadTrendChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Leads</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addLeadModal')"><i class="fas fa-plus"></i> Add Lead</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Service</th><th>Stage</th><th>Amount</th><th>Expected Close</th><th>Actions</th></tr></thead>
                        <tbody id="recentLeadsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== LEADS ====== -->
        <div class="section" id="leadsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-filter"></i> All Leads</div>
                    <button class="btn btn-success btn-sm" onclick="exportLeads()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="leadSearch" placeholder="Search leads…" oninput="debounce(loadLeads, 400)()">
                    </div>
                    <select class="form-select" id="stageFilter" onchange="loadLeads()" style="width:150px;padding:8px 12px;">
                        <option value="">All Stages</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="proposal">Proposal</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="won">Won</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Client</th><th>Phone</th><th>Service</th><th>Stage</th><th>Amount</th><th>Probability</th><th>Expected Close</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="leadsBody">
                            <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SALES FUNNEL ====== -->
        <div class="section" id="funnelSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Sales Funnel</div>
                </div>
                <div class="card-body">
                    <div class="funnel-container" id="funnelContainer">
                        <div class="empty-state"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== ACTIVITIES ====== -->
        <div class="section" id="activitiesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> My Activities</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addActivityModal')"><i class="fas fa-plus"></i> Log Activity</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Type</th><th>Lead</th><th>Subject</th><th>Outcome</th><th>Actions</th></tr></thead>
                        <tbody id="activitiesBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TARGETS ====== -->
        <div class="section" id="targetsSection">
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-value" id="monthlyTarget">—</div><div class="stat-label">Monthly Target</div></div>
                <div class="stat-card blue"><div class="stat-value" id="targetAchieved">—</div><div class="stat-label">Achieved</div></div>
                <div class="stat-card amber"><div class="stat-value" id="targetRemaining">—</div><div class="stat-label">Remaining</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bullseye"></i> Target Progress</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('setTargetModal')"><i class="fas fa-plus"></i> Set Target</button>
                </div>
                <div class="card-body">
                    <div class="progress-bar" style="height:10px;">
                        <div class="progress-fill" id="targetProgressFill" style="width:0%;background:linear-gradient(90deg,var(--brand),#34d399);"></div>
                    </div>
                    <p id="targetMessage" style="margin-top:12px;font-size:13px;color:var(--text-secondary);"></p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> Target History</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Month</th><th>Year</th><th>Target Amount</th><th>Achieved</th><th>Percentage</th></tr></thead>
                        <tbody id="targetHistoryBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== COMMISSIONS ====== -->
        <div class="section" id="commissionsSection">
            <div class="stats-grid">
                <div class="stat-card purple"><div class="stat-value" id="totalCommission">—</div><div class="stat-label">Total Commission</div></div>
                <div class="stat-card amber"><div class="stat-value" id="pendingCommission">—</div><div class="stat-label">Pending</div></div>
                <div class="stat-card green"><div class="stat-value" id="paidCommission">—</div><div class="stat-label">Paid</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-rupee-sign"></i> Commission Details</div>
                    <button class="btn btn-success btn-sm" onclick="exportCommissions()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Client</th><th>Sale Amount</th><th>Commission Rate</th><th>Commission Amount</th><th>Status</th></tr></thead>
                        <tbody id="commissionBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== REPORTS ====== -->
        <div class="section" id="reportsSection">
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card green" onclick="exportReport('leads')"><span class="stat-icon"><i class="fas fa-file-excel"></i></span><div class="stat-label">Export Leads</div></div>
                <div class="stat-card blue" onclick="exportReport('activities')"><span class="stat-icon"><i class="fas fa-file-excel"></i></span><div class="stat-label">Export Activities</div></div>
                <div class="stat-card purple" onclick="exportReport('performance')"><span class="stat-icon"><i class="fas fa-chart-line"></i></span><div class="stat-label">Performance Report</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Sales Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Lead Modal -->
<div class="modal-overlay" id="addLeadModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Add New Lead</span>
            <button class="modal-close" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Client Name <span class="form-required">*</span></label>
                    <input class="form-input" id="leadName" placeholder="Full name">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Phone</label>
                    <input class="form-input" id="leadPhone" placeholder="Phone number">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Email</label>
                    <input class="form-input" id="leadEmail" type="email" placeholder="email@example.com">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Service Interest</label>
                    <select class="form-select" id="leadService">
                        <option value="Written Off Clearance">Written Off Clearance</option>
                        <option value="Settled Clearance">Settled Clearance</option>
                        <option value="Suit Filed Clearance">Suit Filed Clearance</option>
                        <option value="Credit Report Analysis">Credit Report Analysis</option>
                        <option value="Profile Correction">Profile Correction</option>
                        <option value="Wrong Entry Clearance">Wrong Entry Clearance</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Expected Amount (₹)</label>
                    <input type="number" class="form-input" id="leadAmount" placeholder="0">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Probability (%)</label>
                    <select class="form-select" id="leadProbability">
                        <option value="20">20%</option>
                        <option value="40">40%</option>
                        <option value="60" selected>60%</option>
                        <option value="80">80%</option>
                        <option value="100">100%</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Source</label>
                    <select class="form-select" id="leadSource">
                        <option value="Website">Website</option>
                        <option value="Referral">Referral</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Cold Call">Cold Call</option>
                        <option value="Walk-in">Walk-in</option>
                        <option value="Email">Email</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Expected Close Date</label>
                    <input type="date" class="form-input" id="leadCloseDate">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="leadNotes" rows="2" placeholder="Additional notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addLeadModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addLead()"><i class="fas fa-save"></i> Save Lead</button>
        </div>
    </div>
</div>

<!-- Update Lead Modal -->
<div class="modal-overlay" id="updateLeadModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Lead</span>
            <button class="modal-close" onclick="closeModal('updateLeadModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateLeadId">
            <div class="form-group">
                <label class="form-label">Stage</label>
                <select class="form-select" id="updateStage">
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal">Proposal</option>
                    <option value="negotiation">Negotiation</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateNotes" rows="2" placeholder="Update notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateLeadModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateLead()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<div class="modal-overlay" id="addActivityModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-clipboard-list"></i> Log Activity</span>
            <button class="modal-close" onclick="closeModal('addActivityModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Lead</label>
                <select class="form-select" id="activityLead">
                    <option value="">Select lead…</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Activity Type</label>
                <select class="form-select" id="activityType">
                    <option value="call">📞 Call</option>
                    <option value="meeting">🤝 Meeting</option>
                    <option value="email">✉️ Email</option>
                    <option value="demo">📊 Demo</option>
                    <option value="follow_up">🔄 Follow-up</option>
                    <option value="proposal">📄 Proposal</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input class="form-input" id="activitySubject" placeholder="Brief subject">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" id="activityDesc" rows="2" placeholder="Activity details…"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Date & Time</label>
                    <input type="datetime-local" class="form-input" id="activityDateTime">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Outcome</label>
                    <input class="form-input" id="activityOutcome" placeholder="e.g., Interested, Not interested, Follow-up scheduled">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addActivityModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addActivity()"><i class="fas fa-save"></i> Log Activity</button>
        </div>
    </div>
</div>

<!-- Set Target Modal -->
<div class="modal-overlay" id="setTargetModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-bullseye"></i> Set Monthly Target</span>
            <button class="modal-close" onclick="closeModal('setTargetModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Month</label>
                    <select class="form-select" id="targetMonth">
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Year</label>
                    <select class="form-select" id="targetYear"></select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Target Amount (₹) <span class="form-required">*</span></label>
                <input type="number" class="form-input" id="targetAmount" placeholder="Enter target amount">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('setTargetModal')">Cancel</button>
            <button class="btn btn-primary" onclick="setTarget()"><i class="fas fa-save"></i> Set Target</button>
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
const EMPLOYEE_ID = <?= json_encode($employee_id) ?>;
const IS_ADMIN = <?= json_encode($is_admin) ?>;

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('salesTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('salesTheme') || 'light'); })();

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
    dashboard: 'Sales Dashboard',
    leads: 'My Leads',
    funnel: 'Sales Funnel',
    activities: 'Activities',
    targets: 'Targets',
    commissions: 'Commissions',
    reports: 'Reports'
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
        leads: loadLeads,
        funnel: loadFunnel,
        activities: loadActivities,
        targets: loadTargets,
        commissions: loadCommissions,
        reports: loadPerformanceChart
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
function getStageBadge(stage) {
    const map = {
        'new': 'stage-new',
        'contacted': 'stage-contacted',
        'qualified': 'stage-qualified',
        'proposal': 'stage-proposal',
        'negotiation': 'stage-negotiation',
        'won': 'stage-won',
        'lost': 'stage-lost'
    };
    const labels = {
        'new': 'New',
        'contacted': 'Contacted',
        'qualified': 'Qualified',
        'proposal': 'Proposal',
        'negotiation': 'Negotiation',
        'won': 'Won',
        'lost': 'Lost'
    };
    const cls = map[stage?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[stage] || stage}</span>`;
}

function statusBadge(status) {
    const map = {
        'paid': 'badge-success',
        'pending': 'badge-warning',
        'completed': 'badge-success',
        'active': 'badge-success'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${escHtml(status)}</span>`;
}

function debounce(fn, delay) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
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
    const data = await apiCall(`get_dashboard_stats?employee_id=${EMPLOYEE_ID}`);
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalLeads').textContent = data.total_leads || 0;
    document.getElementById('wonLeads').textContent = data.won_leads || 0;
    document.getElementById('totalRevenue').textContent = '₹' + (data.total_revenue || 0).toLocaleString();
    document.getElementById('pipelineValue').textContent = '₹' + (data.pipeline_value || 0).toLocaleString();
    document.getElementById('newLeads').innerHTML = `New this month: ${data.new_leads || 0}`;
    document.getElementById('conversionRate').innerHTML = `Conversion Rate: ${data.conversion_rate || 0}%`;
    document.getElementById('revenueTarget').innerHTML = `Target: ₹${(data.revenue_target || 0).toLocaleString()}`;
    document.getElementById('avgDealSize').innerHTML = `Avg Deal: ₹${(data.avg_deal_size || 0).toLocaleString()}`;

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

    // Recent leads
    const body = document.getElementById('recentLeadsBody');
    if (data.recent_leads && data.recent_leads.length) {
        body.innerHTML = data.recent_leads.map(l => `
            <tr>
                <td><strong>${escHtml(l.client_name || l.name || '—')}</strong></td>
                <td>${escHtml(l.service_type || l.service_interest || '—')}</td>
                <td>${getStageBadge(l.stage)}</td>
                <td>₹${(l.expected_amount || 0).toLocaleString()}</td>
                <td>${l.expected_close_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateLead(${l.id}, '${l.stage || 'new'}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="scheduleFollowup(${l.id}, '${escHtml(l.client_name || l.name)}')"><i class="fas fa-calendar-plus"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent leads</p></div></td></tr>';
    }
}

// ── LEADS ─────────────────────────────────────────────────────────────
async function loadLeads() {
    const search = document.getElementById('leadSearch')?.value || '';
    const stage = document.getElementById('stageFilter')?.value || '';
    const data = await apiCall(`get_leads?employee_id=${EMPLOYEE_ID}&search=${encodeURIComponent(search)}&stage=${stage}`);
    const body = document.getElementById('leadsBody');
    if (data.success && data.leads && data.leads.length) {
        body.innerHTML = data.leads.map(l => `
            <tr>
                <td>#${l.id}</td>
                <td><strong>${escHtml(l.client_name || l.name || '—')}</strong></td>
                <td>${escHtml(l.phone || l.client_phone || '—')}</td>
                <td>${escHtml(l.service_type || l.service_interest || '—')}</td>
                <td>${getStageBadge(l.stage)}</td>
                <td>₹${(l.expected_amount || 0).toLocaleString()}</td>
                <td>${l.probability || 0}%</td>
                <td>${l.expected_close_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateLead(${l.id}, '${l.stage || 'new'}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="scheduleFollowup(${l.id}, '${escHtml(l.client_name || l.name)}')"><i class="fas fa-calendar-plus"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteLead(${l.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');

        // Populate activity lead dropdown
        const activitySelect = document.getElementById('activityLead');
        if (activitySelect) {
            activitySelect.innerHTML = '<option value="">Select lead…</option>' +
                data.leads.filter(l => l.stage !== 'lost' && l.stage !== 'won')
                    .map(l => `<option value="${l.id}">${escHtml(l.client_name || l.name)}</option>`).join('');
        }
    } else {
        body.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-filter"></i><p>No leads found</p></div></td></tr>';
    }
}

// ── ADD LEAD ─────────────────────────────────────────────────────────
async function addLead() {
    const data = {
        employee_id: EMPLOYEE_ID,
        client_name: document.getElementById('leadName').value.trim(),
        client_phone: document.getElementById('leadPhone').value.trim(),
        client_email: document.getElementById('leadEmail').value.trim(),
        service_interest: document.getElementById('leadService').value,
        expected_amount: parseFloat(document.getElementById('leadAmount').value) || 0,
        probability: parseInt(document.getElementById('leadProbability').value) || 60,
        source: document.getElementById('leadSource').value,
        expected_close_date: document.getElementById('leadCloseDate').value,
        notes: document.getElementById('leadNotes').value.trim()
    };

    if (!data.client_name) { showToast('Client name is required', 'warning'); return; }

    const result = await apiCall('add_lead', 'POST', data);
    if (result.success) {
        showToast('Lead added successfully!', 'success');
        closeModal('addLeadModal');
        document.getElementById('leadName').value = '';
        document.getElementById('leadPhone').value = '';
        document.getElementById('leadEmail').value = '';
        document.getElementById('leadAmount').value = '';
        document.getElementById('leadNotes').value = '';
        loadDashboard();
        loadLeads();
        loadFunnel();
    } else {
        showToast(result.error || 'Failed to add lead', 'error');
    }
}

// ── UPDATE LEAD ─────────────────────────────────────────────────────
function openUpdateLead(id, stage) {
    document.getElementById('updateLeadId').value = id;
    document.getElementById('updateStage').value = stage || 'new';
    document.getElementById('updateNotes').value = '';
    openModal('updateLeadModal');
}

async function updateLead() {
    const id = document.getElementById('updateLeadId').value;
    const stage = document.getElementById('updateStage').value;
    const notes = document.getElementById('updateNotes').value.trim();

    const result = await apiCall('update_lead_stage', 'POST', { lead_id: id, stage, notes });
    if (result.success) {
        showToast('Lead updated successfully!', 'success');
        closeModal('updateLeadModal');
        loadDashboard();
        loadLeads();
        loadFunnel();
        if (stage === 'won') {
            loadCommissions();
        }
    } else {
        showToast(result.error || 'Failed to update lead', 'error');
    }
}

// ── DELETE LEAD ─────────────────────────────────────────────────────
async function deleteLead(id) {
    if (!confirm('Delete this lead? This action cannot be undone.')) return;
    const result = await apiCall('delete_lead', 'POST', { lead_id: id });
    if (result.success) {
        showToast('Lead deleted', 'success');
        loadDashboard();
        loadLeads();
        loadFunnel();
    } else {
        showToast(result.error || 'Failed to delete lead', 'error');
    }
}

// ── SALES FUNNEL ─────────────────────────────────────────────────────
async function loadFunnel() {
    const data = await apiCall(`get_sales_funnel?employee_id=${EMPLOYEE_ID}`);
    if (!data.success) { showToast('Failed to load funnel', 'error'); return; }

    const stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    const labels = {
        'new': 'New',
        'contacted': 'Contacted',
        'qualified': 'Qualified',
        'proposal': 'Proposal',
        'negotiation': 'Negotiation',
        'won': 'Won',
        'lost': 'Lost'
    };
    const colors = {
        'new': '#3b82f6',
        'contacted': '#d97706',
        'qualified': '#059669',
        'proposal': '#7c3aed',
        'negotiation': '#dc2626',
        'won': '#059669',
        'lost': '#dc2626'
    };

    const stageCounts = data.stages || {};
    const maxCount = Math.max(...stages.map(s => stageCounts[s] || 0), 1);

    const container = document.getElementById('funnelContainer');
    container.innerHTML = stages.map(s => {
        const count = stageCounts[s] || 0;
        const percent = Math.max(1, (count / maxCount) * 100);
        return `
            <div class="funnel-stage">
                <div class="funnel-stage-name">${labels[s]}</div>
                <div class="funnel-stage-bar">
                    <div class="funnel-stage-fill" style="width:${percent}%;background:${colors[s]};">
                        ${count > 0 ? count : ''}
                    </div>
                </div>
                <div class="funnel-stage-count">${count}</div>
            </div>
        `;
    }).join('');
}

// ── ACTIVITIES ──────────────────────────────────────────────────────
async function loadActivities() {
    const data = await apiCall(`get_activities?employee_id=${EMPLOYEE_ID}`);
    const body = document.getElementById('activitiesBody');
    if (data.success && data.activities && data.activities.length) {
        body.innerHTML = data.activities.map(a => `
            <tr>
                <td>${a.activity_date ? new Date(a.activity_date).toLocaleString('en-IN') : '—'}</td>
                <td><span class="badge badge-brand">${escHtml(a.activity_type)}</span></td>
                <td><strong>${escHtml(a.lead_name || '—')}</strong></td>
                <td>${escHtml(a.subject || '—')}</td>
                <td>${escHtml(a.outcome || '—')}</td>
                <td>
                    <button class="btn btn-danger btn-xs" onclick="deleteActivity(${a.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-history"></i><p>No activities logged yet</p></div></td></tr>';
    }
}

// ── ADD ACTIVITY ────────────────────────────────────────────────────
async function addActivity() {
    const data = {
        employee_id: EMPLOYEE_ID,
        lead_id: document.getElementById('activityLead').value,
        activity_type: document.getElementById('activityType').value,
        subject: document.getElementById('activitySubject').value.trim(),
        description: document.getElementById('activityDesc').value.trim(),
        activity_date: document.getElementById('activityDateTime').value || new Date().toISOString().slice(0, 16),
        outcome: document.getElementById('activityOutcome').value.trim()
    };

    if (!data.lead_id) { showToast('Please select a lead', 'warning'); return; }
    if (!data.subject) { showToast('Subject is required', 'warning'); return; }

    const result = await apiCall('add_activity', 'POST', data);
    if (result.success) {
        showToast('Activity logged successfully!', 'success');
        closeModal('addActivityModal');
        document.getElementById('activitySubject').value = '';
        document.getElementById('activityDesc').value = '';
        document.getElementById('activityOutcome').value = '';
        loadActivities();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to log activity', 'error');
    }
}

// ── DELETE ACTIVITY ─────────────────────────────────────────────────
async function deleteActivity(id) {
    if (!confirm('Delete this activity?')) return;
    const result = await apiCall('delete_activity', 'POST', { activity_id: id });
    if (result.success) {
        showToast('Activity deleted', 'success');
        loadActivities();
    } else {
        showToast(result.error || 'Failed to delete activity', 'error');
    }
}

// ── TARGETS ─────────────────────────────────────────────────────────
async function loadTargets() {
    const data = await apiCall(`get_targets?employee_id=${EMPLOYEE_ID}`);
    if (!data.success) { showToast('Failed to load targets', 'error'); return; }

    if (data.current_target) {
        document.getElementById('monthlyTarget').textContent = '₹' + (data.current_target.amount || 0).toLocaleString();
        document.getElementById('targetAchieved').textContent = '₹' + (data.achieved || 0).toLocaleString();
        const remaining = (data.current_target.amount || 0) - (data.achieved || 0);
        document.getElementById('targetRemaining').textContent = '₹' + (remaining > 0 ? remaining.toLocaleString() : '0');
        const percent = data.current_target.amount > 0 ? Math.min(100, Math.round((data.achieved || 0) / data.current_target.amount * 100)) : 0;
        document.getElementById('targetProgressFill').style.width = percent + '%';
        document.getElementById('targetMessage').textContent = percent >= 100 ? '🎉 Target achieved! Great work!' : `${100 - percent}% remaining to reach monthly target.`;
    }

    const body = document.getElementById('targetHistoryBody');
    if (data.history && data.history.length) {
        body.innerHTML = data.history.map(h => `
            <tr>
                <td>${['January','February','March','April','May','June','July','August','September','October','November','December'][h.month - 1]}</td>
                <td>${h.year}</td>
                <td>₹${(h.amount || 0).toLocaleString()}</td>
                <td>₹${(h.achieved || 0).toLocaleString()}</td>
                <td>
                    <div class="progress-bar" style="height:6px;width:100px;display:inline-block;vertical-align:middle;">
                        <div class="progress-fill" style="width:${Math.min(h.percentage || 0, 100)}%;background:var(--brand);"></div>
                    </div>
                    <span style="margin-left:8px;font-size:12px;">${h.percentage || 0}%</span>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-bullseye"></i><p>No target history available</p></div></td></tr>';
    }
}

// ── SET TARGET ─────────────────────────────────────────────────────
async function setTarget() {
    const data = {
        employee_id: EMPLOYEE_ID,
        month: document.getElementById('targetMonth').value,
        year: document.getElementById('targetYear').value,
        target_amount: parseFloat(document.getElementById('targetAmount').value) || 0
    };

    if (!data.target_amount || data.target_amount <= 0) {
        showToast('Please enter a valid target amount', 'warning');
        return;
    }

    const result = await apiCall('set_target', 'POST', data);
    if (result.success) {
        showToast('Target set successfully!', 'success');
        closeModal('setTargetModal');
        document.getElementById('targetAmount').value = '';
        loadTargets();
    } else {
        showToast(result.error || 'Failed to set target', 'error');
    }
}

// ── COMMISSIONS ─────────────────────────────────────────────────────
async function loadCommissions() {
    const data = await apiCall(`get_commissions?employee_id=${EMPLOYEE_ID}`);
    if (!data.success) { showToast('Failed to load commissions', 'error'); return; }

    document.getElementById('totalCommission').textContent = '₹' + (data.total || 0).toLocaleString();
    document.getElementById('pendingCommission').textContent = '₹' + (data.pending || 0).toLocaleString();
    document.getElementById('paidCommission').textContent = '₹' + (data.paid || 0).toLocaleString();

    const body = document.getElementById('commissionBody');
    if (data.commissions && data.commissions.length) {
        body.innerHTML = data.commissions.map(c => `
            <tr>
                <td>${c.created_at ? new Date(c.created_at).toLocaleDateString('en-IN') : '—'}</td>
                <td><strong>${escHtml(c.client_name || '—')}</strong></td>
                <td>₹${(c.sale_amount || 0).toLocaleString()}</td>
                <td>${c.commission_rate || 0}%</td>
                <td><strong>₹${(c.commission_amount || 0).toLocaleString()}</strong></td>
                <td>${statusBadge(c.status)}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-rupee-sign"></i><p>No commissions yet</p></div></td></tr>';
    }
}

// ── PERFORMANCE CHART ──────────────────────────────────────────────
async function loadPerformanceChart() {
    const data = await apiCall(`get_performance?employee_id=${EMPLOYEE_ID}`);
    if (!data.success) { showToast('Failed to load performance data', 'error'); return; }

    if (data.monthly_performance) {
        destroyChart('performanceChart');
        const ctx = document.getElementById('performanceChart').getContext('2d');
        charts.performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.monthly_performance.labels || [],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: data.monthly_performance.revenue || [],
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true, callback: v => '₹' + v.toLocaleString() } }
                }
            }
        });
    }
}

// ── SCHEDULE FOLLOW-UP ─────────────────────────────────────────────
function scheduleFollowup(leadId, leadName) {
    const select = document.getElementById('activityLead');
    if (select) select.value = leadId;
    document.getElementById('activityType').value = 'follow_up';
    document.getElementById('activitySubject').value = `Follow-up with ${leadName}`;
    const now = new Date();
    now.setDate(now.getDate() + 1);
    document.getElementById('activityDateTime').value = now.toISOString().slice(0, 16);
    openModal('addActivityModal');
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportLeads() {
    showToast('Exporting leads...', 'info');
    window.open('api/sales/export_leads.php', '_blank');
}

function exportCommissions() {
    showToast('Exporting commissions...', 'info');
    window.open('api/sales/export_commissions.php', '_blank');
}

function exportReport(type) {
    showToast(`Exporting ${type} report...`, 'info');
    window.open(`api/sales/export_report.php?type=${type}`, '_blank');
}

// ── INIT YEAR SELECTOR ─────────────────────────────────────────────
const yearSelect = document.getElementById('targetYear');
const currentYear = new Date().getFullYear();
for (let y = currentYear - 1; y <= currentYear + 2; y++) {
    yearSelect.innerHTML += `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`;
}

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addLeadModal') {
                const closeDate = document.getElementById('leadCloseDate');
                if (closeDate && !closeDate.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 30);
                    closeDate.value = d.toISOString().split('T')[0];
                }
            }
            if (modal.id === 'addActivityModal') {
                const dt = document.getElementById('activityDateTime');
                if (dt && !dt.value) {
                    const now = new Date();
                    dt.value = now.toISOString().slice(0, 16);
                }
                loadLeads();
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
    if (e.altKey && e.key === 'l') showSection('leads');
    if (e.altKey && e.key === 'f') showSection('funnel');
    if (e.altKey && e.key === 'c') showSection('commissions');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();

console.log('✅ Sales Dashboard initialized');
console.log('👤 Employee ID:', <?= json_encode($employee_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>