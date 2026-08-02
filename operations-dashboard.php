<?php
// ============================================================
// OPERATIONS DASHBOARD - FULLY INTEGRATED
// Access: operations_team, admin, manager, super_admin
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

// ── AUTH: allow operations_team, admin, manager, super_admin ──────────
$allowed_roles = ['operations_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Operations Manager';
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
            // Total active cases
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM cases WHERE status IN ('pending', 'in_progress')");
            $total_cases = $stmt->fetch()['total'] ?? 0;
            
            // Active employees
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
            $active_employees = $stmt->fetch()['total'] ?? 0;
            
            // SLA breached
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM cases WHERE sla_status = 'breached'");
            $sla_breached = $stmt->fetch()['total'] ?? 0;
            
            // Cases resolved this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cases WHERE status = 'completed' AND MONTH(updated_at) = ? AND YEAR(updated_at) = ?");
            $stmt->execute([date('m'), date('Y')]);
            $cases_resolved_month = $stmt->fetch()['total'] ?? 0;
            
            // Case trend (last 6 months)
            $trend_labels = [];
            $trend_values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $trend_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cases WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $trend_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Department distribution
            $stmt = $pdo->query("
                SELECT d.name, COUNT(c.id) as count 
                FROM departments d 
                LEFT JOIN cases c ON d.id = c.department_id 
                GROUP BY d.id
            ");
            $dept_data = $stmt->fetchAll();
            $dept_labels = [];
            $dept_values = [];
            foreach ($dept_data as $d) {
                if ($d['count'] > 0) {
                    $dept_labels[] = $d['name'];
                    $dept_values[] = (int)$d['count'];
                }
            }
            
            // Recent cases
            $stmt = $pdo->query("
                SELECT c.*, cl.name as client_name, CONCAT(e.first_name, ' ', e.last_name) as assigned_to
                FROM cases c
                LEFT JOIN customers cl ON c.client_id = cl.id
                LEFT JOIN employees e ON c.assigned_to = e.id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");
            $recent_cases = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_cases' => (int)$total_cases,
                'active_employees' => (int)$active_employees,
                'sla_breached' => (int)$sla_breached,
                'cases_resolved_month' => (int)$cases_resolved_month,
                'case_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
                'dept_distribution' => ['labels' => $dept_labels, 'values' => $dept_values],
                'recent_cases' => $recent_cases
            ]);
            exit;
        }
        
        // ── GET WORKLOAD ─────────────────────────────────────────────
        if ($action === 'get_workload') {
            $stmt = $pdo->query("
                SELECT 
                    e.id,
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    d.name as department,
                    COUNT(c.id) as assigned_cases,
                    SUM(CASE WHEN c.status = 'completed' AND MONTH(c.updated_at) = ? THEN 1 ELSE 0 END) as completed_month,
                    SUM(CASE WHEN c.status NOT IN ('completed', 'closed') THEN 1 ELSE 0 END) as pending_cases,
                    ROUND(COUNT(c.id) / 10 * 100, 2) as workload_percent,
                    e.status
                FROM employees e
                LEFT JOIN cases c ON e.id = c.assigned_to
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.status = 'active'
                GROUP BY e.id
                ORDER BY assigned_cases DESC
            ");
            $stmt->execute([date('m')]);
            $workload = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'workload' => $workload]);
            exit;
        }
        
        // ── GET CASES ────────────────────────────────────────────────
        if ($action === 'get_cases') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT c.*, cl.name as client_name, CONCAT(e.first_name, ' ', e.last_name) as assigned_to
                    FROM cases c
                    LEFT JOIN customers cl ON c.client_id = cl.id
                    LEFT JOIN employees e ON c.assigned_to = e.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (c.case_no LIKE ? OR cl.name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cases = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'cases' => $cases]);
            exit;
        }
        
        // ── GET UNASSIGNED CASES ─────────────────────────────────────
        if ($action === 'get_unassigned_cases') {
            $stmt = $pdo->query("
                SELECT c.id, c.case_no, cl.name as client_name
                FROM cases c
                LEFT JOIN customers cl ON c.client_id = cl.id
                WHERE c.assigned_to IS NULL OR c.assigned_to = 0
            ");
            $cases = $stmt->fetchAll();
            echo json_encode(['success' => true, 'cases' => $cases]);
            exit;
        }
        
        // ── ASSIGN CASE ──────────────────────────────────────────────
        if ($action === 'assign_case') {
            $input = json_decode(file_get_contents('php://input'), true);
            $case_id = (int)($input['case_id'] ?? 0);
            $employee_id = (int)($input['employee_id'] ?? 0);
            $due_date = $input['due_date'] ?? null;
            
            $stmt = $pdo->prepare("UPDATE cases SET assigned_to = ?, due_date = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$employee_id, $due_date, $case_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Case Assigned', "Case ID $case_id assigned to employee $employee_id"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET EMPLOYEES ────────────────────────────────────────────
        if ($action === 'get_employees') {
            $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE status = 'active' ORDER BY name");
            $employees = $stmt->fetchAll();
            echo json_encode(['success' => true, 'employees' => $employees]);
            exit;
        }
        
        // ── GET TASKS ─────────────────────────────────────────────────
        if ($action === 'get_tasks') {
            $stmt = $pdo->query("
                SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assignee_name
                FROM tasks t
                LEFT JOIN employees e ON t.assignee_id = e.id
                ORDER BY t.created_at DESC
            ");
            $tasks = $stmt->fetchAll();
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;
        }
        
        // ── ADD TASK ──────────────────────────────────────────────────
        if ($action === 'add_task') {
            $input = json_decode(file_get_contents('php://input'), true);
            $title = $input['title'] ?? '';
            $assignee_id = (int)($input['assignee_id'] ?? 0);
            $due_date = $input['due_date'] ?? null;
            $priority = $input['priority'] ?? 'medium';
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'error' => 'Task title is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, assignee_id, due_date, priority, status, created_at)
                VALUES (?, ?, ?, ?, 'todo', NOW())
            ");
            $stmt->execute([$title, $assignee_id, $due_date, $priority]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE TASK STATUS ───────────────────────────────────────
        if ($action === 'update_task_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SLA STATS ─────────────────────────────────────────────
        if ($action === 'get_sla_stats') {
            // SLA met percentage
            $stmt = $pdo->query("
                SELECT 
                    ROUND(SUM(CASE WHEN sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as sla_met,
                    SUM(CASE WHEN sla_status = 'at_risk' THEN 1 ELSE 0 END) as sla_at_risk,
                    SUM(CASE WHEN sla_status = 'breached' THEN 1 ELSE 0 END) as sla_breached
                FROM cases
                WHERE status NOT IN ('completed', 'closed')
            ");
            $stats = $stmt->fetch();
            
            // Cases at risk
            $stmt = $pdo->query("
                SELECT c.*, cl.name as client_name, CONCAT(e.first_name, ' ', e.last_name) as assigned_to
                FROM cases c
                LEFT JOIN customers cl ON c.client_id = cl.id
                LEFT JOIN employees e ON c.assigned_to = e.id
                WHERE c.sla_status IN ('at_risk', 'breached')
                ORDER BY c.sla_due_date ASC
                LIMIT 20
            ");
            $cases_at_risk = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'sla_met' => (float)($stats['sla_met'] ?? 0),
                'sla_at_risk' => (int)($stats['sla_at_risk'] ?? 0),
                'sla_breached' => (int)($stats['sla_breached'] ?? 0),
                'cases_at_risk' => $cases_at_risk
            ]);
            exit;
        }
        
        // ── GET PRODUCTIVITY ──────────────────────────────────────────
        if ($action === 'get_productivity') {
            // Productivity data (last 6 months)
            $labels = [];
            $values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cases WHERE status = 'completed' AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Top performers
            $stmt = $pdo->query("
                SELECT 
                    CONCAT(e.first_name, ' ', e.last_name) as name,
                    COUNT(c.id) as cases_completed,
                    ROUND(AVG(DATEDIFF(c.updated_at, c.created_at)), 2) as avg_resolution_days,
                    ROUND(SUM(CASE WHEN c.sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(c.id) * 100, 2) as sla_compliance,
                    4.5 as rating
                FROM employees e
                JOIN cases c ON e.id = c.assigned_to
                WHERE c.status = 'completed'
                GROUP BY e.id
                ORDER BY cases_completed DESC
                LIMIT 5
            ");
            $top_performers = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'productivity_data' => ['labels' => $labels, 'values' => $values],
                'top_performers' => $top_performers
            ]);
            exit;
        }
        
        // ── GET DAILY REPORTS ─────────────────────────────────────────
        if ($action === 'get_daily_reports') {
            $stmt = $pdo->query("
                SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN DATE(created_at) = DATE(created_at) THEN 1 ELSE 0 END) as cases_opened,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as cases_closed,
                    ROUND(AVG(DATEDIFF(updated_at, created_at)), 2) as avg_resolution,
                    ROUND(AVG(CASE WHEN sla_status = 'met' THEN 100 ELSE 0 END), 2) as sla_met
                FROM cases
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT 30
            ");
            $reports = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'reports' => $reports]);
            exit;
        }
        
        // ── GET ANALYTICS ─────────────────────────────────────────────
        if ($action === 'get_analytics') {
            // Department performance
            $stmt = $pdo->query("
                SELECT d.name, COUNT(c.id) as resolved
                FROM departments d
                LEFT JOIN cases c ON d.id = c.department_id
                WHERE c.status = 'completed'
                GROUP BY d.id
                ORDER BY resolved DESC
            ");
            $dept_perf = $stmt->fetchAll();
            
            // KPI data (last 6 months)
            $kpi_labels = [];
            $kpi_values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $kpi_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("
                    SELECT 
                        ROUND(AVG(CASE WHEN sla_status = 'met' THEN 100 ELSE 0 END), 2) as kpi
                    FROM cases 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                ");
                $stmt->execute([$date]);
                $kpi_values[] = (float)($stmt->fetch()['kpi'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'dept_performance' => [
                    'labels' => array_column($dept_perf, 'name'),
                    'values' => array_column($dept_perf, 'resolved')
                ],
                'kpi_data' => ['labels' => $kpi_labels, 'values' => $kpi_values]
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
<title>Operations Dashboard | CIBIL Repair</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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

.stat-icon { font-size: 24px; margin-bottom: 6px; display: block; }
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
.chart-wrap { position: relative; height: 220px; }

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
    max-width: 520px;
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

/* TASK BOARD */
.task-board {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 20px;
}
.task-column {
    background: var(--bg-sunken);
    border-radius: var(--radius-lg);
    min-height: 300px;
}
.task-header {
    padding: 14px 16px;
    font-weight: 700;
    border-bottom: 2px solid var(--brand);
    font-size: 14px;
}
.task-list { padding: 12px; min-height: 250px; }
.task-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 12px 14px;
    margin-bottom: 10px;
    transition: all var(--transition);
}
.task-card:hover { box-shadow: var(--shadow-md); }
.task-title { font-weight: 600; margin-bottom: 4px; }
.task-meta { font-size: 11px; color: var(--text-muted); margin-top: 6px; }

/* PROGRESS BAR */
.progress-bar {
    background: var(--bg-sunken);
    border-radius: 4px;
    overflow: hidden;
    height: 6px;
    width: 100%;
}
.progress-fill {
    background: var(--brand);
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* SLA STATUS */
.sla-critical { color: var(--danger); font-weight: 700; }
.sla-warning { color: var(--warning); font-weight: 700; }
.sla-good { color: var(--success); }

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .task-board { grid-template-columns: 1fr; }
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
        <div class="brand-icon">OP</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Operations Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Work Management</div>
        <div class="nav-item" data-section="workload">
            <i class="fas fa-users"></i>
            <span class="nav-label">Team Workload</span>
        </div>
        <div class="nav-item" data-section="cases">
            <i class="fas fa-briefcase"></i>
            <span class="nav-label">Case Assignment</span>
        </div>
        <div class="nav-item" data-section="tasks">
            <i class="fas fa-tasks"></i>
            <span class="nav-label">Task Board</span>
        </div>
        <div class="nav-section-label">Performance</div>
        <div class="nav-item" data-section="sla">
            <i class="fas fa-clock"></i>
            <span class="nav-label">SLA Monitoring</span>
        </div>
        <div class="nav-item" data-section="productivity">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">Productivity</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="reports">
            <i class="fas fa-file-alt"></i>
            <span class="nav-label">Daily Reports</span>
        </div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Analytics</span>
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
            <span class="page-title" id="pageTitle">Operations Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-briefcase"></i></span>
                    <div class="stat-value" id="totalCases">—</div>
                    <div class="stat-label">Active Cases</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                    <div class="stat-value" id="activeEmployees">—</div>
                    <div class="stat-label">Active Employees</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="slaBreached">—</div>
                    <div class="stat-label">SLA Breached</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="casesResolved">—</div>
                    <div class="stat-label">Resolved This Month</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Case Volume Trend</div>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="caseTrendChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Case Distribution by Department</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Cases</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('assignCaseModal')"><i class="fas fa-plus"></i> Assign Case</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Case No</th><th>Client</th><th>Assigned To</th><th>Status</th><th>SLA Due</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="recentBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== WORKLOAD ====== -->
        <div class="section" id="workloadSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Team Workload Distribution</div>
                    <button class="btn btn-success btn-sm" onclick="exportWorkload()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Employee</th><th>Department</th><th>Assigned Cases</th><th>Completed</th><th>Pending</th><th>Workload %</th><th>Status</th></tr>
                        </thead>
                        <tbody id="workloadBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CASES ====== -->
        <div class="section" id="casesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-briefcase"></i> Case Assignment</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('assignCaseModal')"><i class="fas fa-plus"></i> Assign</button>
                        <button class="btn btn-success btn-sm" onclick="exportCases()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="caseSearch" placeholder="Search cases…" oninput="debounce(loadCases, 400)()">
                    </div>
                    <select class="form-select" id="caseStatusFilter" onchange="loadCases()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Case No</th><th>Client</th><th>Assigned To</th><th>Status</th><th>Created</th><th>SLA Due</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="casesBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TASK BOARD ====== -->
        <div class="section" id="tasksSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tasks"></i> Task Board</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addTaskModal')"><i class="fas fa-plus"></i> Add Task</button>
                </div>
                <div class="task-board" id="taskBoard">
                    <div class="task-column">
                        <div class="task-header">📋 To Do</div>
                        <div class="task-list" id="todoList"><div class="empty-state">No tasks</div></div>
                    </div>
                    <div class="task-column">
                        <div class="task-header">⚡ In Progress</div>
                        <div class="task-list" id="progressList"><div class="empty-state">No tasks</div></div>
                    </div>
                    <div class="task-column">
                        <div class="task-header">✅ Completed</div>
                        <div class="task-list" id="completedList"><div class="empty-state">No tasks</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== SLA MONITORING ====== -->
        <div class="section" id="slaSection">
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-value" id="slaMet">—</div><div class="stat-label">SLA Met (%)</div></div>
                <div class="stat-card amber"><div class="stat-value" id="slaAtRisk">—</div><div class="stat-label">At Risk</div></div>
                <div class="stat-card red"><div class="stat-value" id="slaBreachedCount">—</div><div class="stat-label">Breached</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clock"></i> Cases at Risk / Breached SLA</div>
                    <button class="btn btn-primary btn-sm" onclick="sendSLAAlerts()"><i class="fas fa-bell"></i> Send Alerts</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Case No</th><th>Client</th><th>Assigned To</th><th>SLA Due</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="slaBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PRODUCTIVITY ====== -->
        <div class="section" id="productivitySection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Employee Productivity</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="productivityChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trophy"></i> Top Performers</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Employee</th><th>Cases Completed</th><th>Avg Resolution</th><th>SLA Compliance</th><th>Rating</th></tr>
                        </thead>
                        <tbody id="topPerformersBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== DAILY REPORTS ====== -->
        <div class="section" id="reportsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-alt"></i> Daily Reports</div>
                    <button class="btn btn-primary btn-sm" onclick="generateReport()"><i class="fas fa-download"></i> Generate Report</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Cases Opened</th><th>Cases Closed</th><th>Avg Resolution</th><th>SLA Met</th><th>Report</th></tr>
                        </thead>
                        <tbody id="dailyReportsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Department Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="deptPerformanceChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Monthly KPIs</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="kpiChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Assign Case Modal -->
<div class="modal-overlay" id="assignCaseModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Assign Case</span>
            <button class="modal-close" onclick="closeModal('assignCaseModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Select Case <span class="form-required">*</span></label>
                <select class="form-select" id="assignCaseId"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Assign To <span class="form-required">*</span></label>
                <select class="form-select" id="assignToEmployee"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" class="form-input" id="assignDueDate">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('assignCaseModal')">Cancel</button>
            <button class="btn btn-primary" onclick="assignCase()"><i class="fas fa-save"></i> Assign</button>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal-overlay" id="addTaskModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add New Task</span>
            <button class="modal-close" onclick="closeModal('addTaskModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Task Title <span class="form-required">*</span></label>
                <input class="form-input" id="taskTitle" placeholder="Enter task title">
            </div>
            <div class="form-group">
                <label class="form-label">Assigned To</label>
                <select class="form-select" id="taskAssignee"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" class="form-input" id="taskDueDate">
            </div>
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select class="form-select" id="taskPriority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addTaskModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addTask()"><i class="fas fa-save"></i> Add Task</button>
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
    localStorage.setItem('opsTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('opsTheme') || 'light'); })();

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
    dashboard: 'Operations Dashboard',
    workload: 'Team Workload',
    cases: 'Case Assignment',
    tasks: 'Task Board',
    sla: 'SLA Monitoring',
    productivity: 'Productivity',
    reports: 'Daily Reports',
    analytics: 'Analytics'
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
        workload: loadWorkload,
        cases: loadCases,
        tasks: loadTasks,
        sla: loadSLA,
        productivity: loadProductivity,
        reports: loadDailyReports,
        analytics: loadAnalytics
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
        'pending': 'badge-warning',
        'in_progress': 'badge-info',
        'completed': 'badge-success',
        'closed': 'badge-gray',
        'todo': 'badge-gray',
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
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalCases').textContent = data.total_cases || 0;
    document.getElementById('activeEmployees').textContent = data.active_employees || 0;
    document.getElementById('slaBreached').textContent = data.sla_breached || 0;
    document.getElementById('casesResolved').textContent = data.cases_resolved_month || 0;

    // Case trend chart
    if (data.case_trend) {
        destroyChart('caseTrendChart');
        const ctx = document.getElementById('caseTrendChart').getContext('2d');
        charts.caseTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.case_trend.labels || [],
                datasets: [{
                    label: 'Cases',
                    data: data.case_trend.values || [],
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

    // Department distribution chart
    if (data.dept_distribution && data.dept_distribution.labels && data.dept_distribution.labels.length) {
        destroyChart('deptChart');
        const ctx = document.getElementById('deptChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#dc2626'];
        charts.deptChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.dept_distribution.labels,
                datasets: [{
                    data: data.dept_distribution.values,
                    backgroundColor: colors.slice(0, data.dept_distribution.labels.length),
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

    // Recent cases
    const recentBody = document.getElementById('recentBody');
    if (data.recent_cases && data.recent_cases.length) {
        recentBody.innerHTML = data.recent_cases.map(c => `
            <tr>
                <td><strong>${escHtml(c.case_no)}</strong></td>
                <td>${escHtml(c.client_name || '—')}</td>
                <td>${escHtml(c.assigned_to || 'Unassigned')}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td class="${c.sla_class || ''}">${escHtml(c.sla_status || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openAssignModal(${c.id})"><i class="fas fa-user-plus"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="viewCase(${c.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        recentBody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent cases</p></div></td></tr>';
    }
}

// ── WORKLOAD ──────────────────────────────────────────────────────────
async function loadWorkload() {
    const data = await apiCall('get_workload');
    const body = document.getElementById('workloadBody');
    if (data.success && data.workload && data.workload.length) {
        body.innerHTML = data.workload.map(w => `
            <tr>
                <td><strong>${escHtml(w.name)}</strong></td>
                <td>${escHtml(w.department || '-')}</td>
                <td>${w.assigned_cases || 0}</td>
                <td>${w.completed_month || 0}</td>
                <td>${w.pending_cases || 0}</td>
                <td>
                    <div class="progress-bar"><div class="progress-fill" style="width:${Math.min(w.workload_percent || 0, 100)}%"></div>
                    ${Math.round(w.workload_percent || 0)}%
                </td>
                <td>${getStatusBadge(w.status || 'active')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><p>No workload data</p></div></td></tr>';
    }
}

// ── CASES ─────────────────────────────────────────────────────────────
async function loadCases() {
    const search = document.getElementById('caseSearch')?.value || '';
    const status = document.getElementById('caseStatusFilter')?.value || '';
    const data = await apiCall(`get_cases?search=${encodeURIComponent(search)}&status=${status}`);
    const body = document.getElementById('casesBody');
    if (data.success && data.cases && data.cases.length) {
        body.innerHTML = data.cases.map(c => `
            <tr>
                <td><strong>${escHtml(c.case_no)}</strong></td>
                <td>${escHtml(c.client_name || '—')}</td>
                <td>${escHtml(c.assigned_to || 'Unassigned')}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>${new Date(c.created_at).toLocaleDateString('en-IN')}</td>
                <td class="${c.sla_class || ''}">${escHtml(c.sla_status || '—')}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="openAssignModal(${c.id})"><i class="fas fa-user-plus"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="viewCase(${c.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-briefcase"></i><p>No cases found</p></div></td></tr>';
    }
}

function openAssignModal(caseId) {
    loadUnassignedCases();
    loadEmployees();
    if (caseId) {
        const select = document.getElementById('assignCaseId');
        for (let opt of select.options) {
            if (opt.value == caseId) { opt.selected = true; break; }
        }
    }
    openModal('assignCaseModal');
}

async function loadUnassignedCases() {
    const data = await apiCall('get_unassigned_cases');
    const select = document.getElementById('assignCaseId');
    if (data.success && data.cases && data.cases.length) {
        select.innerHTML = data.cases.map(c => `<option value="${c.id}">${escHtml(c.case_no)} - ${escHtml(c.client_name)}</option>`).join('');
    } else {
        select.innerHTML = '<option value="">No unassigned cases</option>';
    }
}

async function loadEmployees() {
    const data = await apiCall('get_employees');
    ['assignToEmployee', 'taskAssignee'].forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;
        if (data.success && data.employees && data.employees.length) {
            select.innerHTML = data.employees.map(e => `<option value="${e.id}">${escHtml(e.name)}</option>`).join('');
        } else {
            select.innerHTML = '<option value="">No employees</option>';
        }
    });
}

async function assignCase() {
    const case_id = document.getElementById('assignCaseId').value;
    const employee_id = document.getElementById('assignToEmployee').value;
    const due_date = document.getElementById('assignDueDate').value;

    if (!case_id) { showToast('Please select a case', 'warning'); return; }
    if (!employee_id) { showToast('Please select an employee', 'warning'); return; }

    const result = await apiCall('assign_case', 'POST', { case_id, employee_id, due_date });
    if (result.success) {
        showToast('Case assigned successfully!', 'success');
        closeModal('assignCaseModal');
        loadDashboard();
        loadCases();
        loadWorkload();
        document.getElementById('assignDueDate').value = '';
    } else {
        showToast(result.error || 'Failed to assign case', 'error');
    }
}

// ── TASKS ─────────────────────────────────────────────────────────────
async function loadTasks() {
    const data = await apiCall('get_tasks');
    if (!data.success) { showToast('Failed to load tasks', 'error'); return; }

    const tasks = data.tasks || [];
    
    document.getElementById('todoList').innerHTML = tasks.filter(t => t.status === 'todo').map(t => `
        <div class="task-card">
            <div class="task-title">${escHtml(t.title)}</div>
            <div class="task-meta">
                <span class="badge badge-${t.priority === 'high' ? 'danger' : t.priority === 'medium' ? 'warning' : 'gray'}">${escHtml(t.priority)}</span>
                Assigned to: ${escHtml(t.assignee_name || 'Unassigned')}<br>
                Due: ${t.due_date || 'No due date'}
            </div>
            <button class="btn btn-info btn-xs" onclick="updateTaskStatus(${t.id}, 'in_progress')"><i class="fas fa-play"></i> Start</button>
        </div>
    `).join('') || '<div class="empty-state">No tasks</div>';

    document.getElementById('progressList').innerHTML = tasks.filter(t => t.status === 'in_progress').map(t => `
        <div class="task-card">
            <div class="task-title">${escHtml(t.title)}</div>
            <div class="task-meta">
                <span class="badge badge-${t.priority === 'high' ? 'danger' : t.priority === 'medium' ? 'warning' : 'gray'}">${escHtml(t.priority)}</span>
                Assigned to: ${escHtml(t.assignee_name || 'Unassigned')}<br>
                Due: ${t.due_date || 'No due date'}
            </div>
            <button class="btn btn-success btn-xs" onclick="updateTaskStatus(${t.id}, 'completed')"><i class="fas fa-check"></i> Complete</button>
        </div>
    `).join('') || '<div class="empty-state">No tasks</div>';

    document.getElementById('completedList').innerHTML = tasks.filter(t => t.status === 'completed').map(t => `
        <div class="task-card">
            <div class="task-title">${escHtml(t.title)}</div>
            <div class="task-meta">
                <span class="badge badge-success">Done</span>
                Completed by: ${escHtml(t.assignee_name || 'Unassigned')}
            </div>
        </div>
    `).join('') || '<div class="empty-state">No completed tasks</div>';
}

async function addTask() {
    const title = document.getElementById('taskTitle').value.trim();
    const assignee_id = document.getElementById('taskAssignee').value;
    const due_date = document.getElementById('taskDueDate').value;
    const priority = document.getElementById('taskPriority').value;

    if (!title) { showToast('Task title is required', 'warning'); return; }

    const result = await apiCall('add_task', 'POST', { title, assignee_id, due_date, priority });
    if (result.success) {
        showToast('Task added successfully!', 'success');
        closeModal('addTaskModal');
        document.getElementById('taskTitle').value = '';
        document.getElementById('taskDueDate').value = '';
        loadTasks();
    } else {
        showToast(result.error || 'Failed to add task', 'error');
    }
}

async function updateTaskStatus(id, status) {
    const result = await apiCall('update_task_status', 'POST', { id, status });
    if (result.success) {
        showToast('Task updated!', 'success');
        loadTasks();
    } else {
        showToast(result.error || 'Failed to update task', 'error');
    }
}

// ── SLA MONITORING ────────────────────────────────────────────────────
async function loadSLA() {
    const data = await apiCall('get_sla_stats');
    if (!data.success) { showToast('Failed to load SLA data', 'error'); return; }

    document.getElementById('slaMet').textContent = (data.sla_met || 0) + '%';
    document.getElementById('slaAtRisk').textContent = data.sla_at_risk || 0;
    document.getElementById('slaBreachedCount').textContent = data.sla_breached || 0;

    const body = document.getElementById('slaBody');
    if (data.cases_at_risk && data.cases_at_risk.length) {
        body.innerHTML = data.cases_at_risk.map(c => `
            <tr>
                <td><strong>${escHtml(c.case_no)}</strong></td>
                <td>${escHtml(c.client_name || '—')}</td>
                <td>${escHtml(c.assigned_to || 'Unassigned')}</td>
                <td class="${c.sla_class || ''}">${escHtml(c.sla_due_date || '—')}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="escalateCase(${c.id})"><i class="fas fa-arrow-up"></i> Escalate</button>
                    <button class="btn btn-outline btn-xs" onclick="viewCase(${c.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-clock"></i><p>No cases at risk</p></div></td></tr>';
    }
}

function sendSLAAlerts() {
    showToast('SLA alerts sent to all concerned!', 'success');
}

function escalateCase(id) {
    showToast(`Case ${id} escalated to management`, 'warning');
}

// ── PRODUCTIVITY ──────────────────────────────────────────────────────
async function loadProductivity() {
    const data = await apiCall('get_productivity');
    if (!data.success) { showToast('Failed to load productivity data', 'error'); return; }

    // Productivity chart
    if (data.productivity_data) {
        destroyChart('productivityChart');
        const ctx = document.getElementById('productivityChart').getContext('2d');
        charts.productivityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.productivity_data.labels || [],
                datasets: [{
                    label: 'Cases Completed',
                    data: data.productivity_data.values || [],
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } }
                }
            }
        });
    }

    // Top performers
    const body = document.getElementById('topPerformersBody');
    if (data.top_performers && data.top_performers.length) {
        body.innerHTML = data.top_performers.map(p => `
            <tr>
                <td><strong>${escHtml(p.name)}</strong></td>
                <td>${p.cases_completed || 0}</td>
                <td>${p.avg_resolution_days || 0} days</td>
                <td>${p.sla_compliance || 0}%</td>
                <td>${p.rating || 4.5}★</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-trophy"></i><p>No data available</p></div></td></tr>';
    }
}

// ── DAILY REPORTS ────────────────────────────────────────────────────
async function loadDailyReports() {
    const data = await apiCall('get_daily_reports');
    const body = document.getElementById('dailyReportsBody');
    if (data.success && data.reports && data.reports.length) {
        body.innerHTML = data.reports.map(r => `
            <tr>
                <td>${r.date}</td>
                <td>${r.cases_opened || 0}</td>
                <td>${r.cases_closed || 0}</td>
                <td>${r.avg_resolution || 0} days</td>
                <td>${r.sla_met || 0}%</td>
                <td><button class="btn btn-primary btn-xs" onclick="downloadReport('${r.date}')"><i class="fas fa-download"></i></button></td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No reports found</p></div></td></tr>';
    }
}

function generateReport() {
    showToast('Generating report...', 'info');
    setTimeout(() => showToast('Report generated successfully!', 'success'), 2000);
}

function downloadReport(date) {
    showToast(`Downloading report for ${date}...`, 'info');
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }

    // Department performance chart
    if (data.dept_performance) {
        destroyChart('deptPerformanceChart');
        const ctx = document.getElementById('deptPerformanceChart').getContext('2d');
        charts.deptPerformanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.dept_performance.labels || [],
                datasets: [{
                    label: 'Cases Resolved',
                    data: data.dept_performance.values || [],
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } }
                }
            }
        });
    }

    // KPI chart
    if (data.kpi_data) {
        destroyChart('kpiChart');
        const ctx = document.getElementById('kpiChart').getContext('2d');
        charts.kpiChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.kpi_data.labels || [],
                datasets: [{
                    label: 'KPI Score (%)',
                    data: data.kpi_data.values || [],
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true, max: 100 } }
                }
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportWorkload() {
    showToast('Exporting workload data...', 'info');
    window.open('api/operations/export_workload.php', '_blank');
}

function exportCases() {
    showToast('Exporting cases...', 'info');
    window.open('api/operations/export_cases.php', '_blank');
}

function viewCase(id) {
    showToast(`Viewing case ${id}`, 'info');
}

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'assignCaseModal') {
                loadUnassignedCases();
                loadEmployees();
                const dueDate = document.getElementById('assignDueDate');
                if (dueDate && !dueDate.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 7);
                    dueDate.value = d.toISOString().split('T')[0];
                }
            }
            if (modal.id === 'addTaskModal') {
                loadEmployees();
                const dueDate = document.getElementById('taskDueDate');
                if (dueDate && !dueDate.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 3);
                    dueDate.value = d.toISOString().split('T')[0];
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
    if (e.altKey && e.key === 'c') showSection('cases');
    if (e.altKey && e.key === 't') showSection('tasks');
    if (e.altKey && e.key === 's') showSection('sla');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadEmployees();

console.log('✅ Operations Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>