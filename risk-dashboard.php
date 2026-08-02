<?php
// ============================================================
// RISK DASHBOARD - FULLY INTEGRATED
// Access: risk_team, admin, manager, super_admin
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
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ── AUTH: allow risk_team, admin, manager, super_admin ──────────────
$allowed_roles = ['risk_team', 'admin', 'manager', 'super_admin'];
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

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Risk Officer';
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
            // Total assessments
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM risk_assessments");
            $total_assessments = (int)($stmt->fetch()['total'] ?? 0);
            
            // Pending assessments
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM risk_assessments WHERE status = 'pending'");
            $pending_assessments = (int)($stmt->fetch()['total'] ?? 0);
            
            // Critical risks
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM risk_assessments WHERE risk_level = 'critical'");
            $critical_risks = (int)($stmt->fetch()['total'] ?? 0);
            
            // Active fraud alerts
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM fraud_alerts WHERE status IN ('new', 'investigating')");
            $active_alerts = (int)($stmt->fetch()['total'] ?? 0);
            
            // Average risk score
            $stmt = $pdo->query("SELECT AVG(risk_score) as avg FROM risk_assessments");
            $avg_score = (int)($stmt->fetch()['avg'] ?? 0);
            
            // Risk level distribution
            $stmt = $pdo->query("
                SELECT risk_level, COUNT(*) as count 
                FROM risk_assessments 
                GROUP BY risk_level
            ");
            $levels = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
            while ($row = $stmt->fetch()) {
                $levels[$row['risk_level']] = (int)$row['count'];
            }
            
            // Recent assessments
            $stmt = $pdo->query("
                SELECT r.*, c.name as client_name, u.name as assessor_name 
                FROM risk_assessments r
                LEFT JOIN customers c ON r.client_id = c.id
                LEFT JOIN users u ON r.assessed_by = u.id
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $recent_assessments = $stmt->fetchAll();
            
            // Recent fraud alerts
            $stmt = $pdo->query("
                SELECT f.*, c.name as client_name 
                FROM fraud_alerts f
                LEFT JOIN customers c ON f.client_id = c.id
                ORDER BY f.detected_at DESC
                LIMIT 10
            ");
            $recent_alerts = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_assessments' => $total_assessments,
                'pending_assessments' => $pending_assessments,
                'critical_risks' => $critical_risks,
                'active_alerts' => $active_alerts,
                'avg_risk_score' => $avg_score,
                'risk_distribution' => [
                    'labels' => ['Low', 'Medium', 'High', 'Critical'],
                    'values' => [$levels['low'], $levels['medium'], $levels['high'], $levels['critical']]
                ],
                'recent_assessments' => $recent_assessments,
                'recent_alerts' => $recent_alerts
            ]);
            exit;
        }
        
        // ── GET RISK METRICS ─────────────────────────────────────────
        if ($action === 'get_risk_metrics') {
            $stmt = $pdo->query("
                SELECT 
                    metric_name,
                    metric_value,
                    target_value,
                    ROUND((metric_value / target_value) * 100, 2) as percentage
                FROM risk_metrics
                ORDER BY category, metric_name
            ");
            $metrics = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'metrics' => $metrics]);
            exit;
        }
        
        // ── GET ASSESSMENTS ──────────────────────────────────────────
        if ($action === 'get_assessments') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $level = $_GET['level'] ?? '';
            
            $sql = "SELECT r.*, c.name as client_name, u.name as assessor_name 
                    FROM risk_assessments r
                    LEFT JOIN customers c ON r.client_id = c.id
                    LEFT JOIN users u ON r.assessed_by = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (c.name LIKE ? OR r.risk_level LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }
            if ($level) {
                $sql .= " AND r.risk_level = ?";
                $params[] = $level;
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $assessments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'assessments' => $assessments]);
            exit;
        }
        
        // ── ADD ASSESSMENT ───────────────────────────────────────────
        if ($action === 'add_assessment') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $risk_score = (int)($input['risk_score'] ?? 0);
            $credit_risk_score = (int)($input['credit_risk_score'] ?? 0);
            $fraud_risk_score = (int)($input['fraud_risk_score'] ?? 0);
            $compliance_risk_score = (int)($input['compliance_risk_score'] ?? 0);
            $operational_risk_score = (int)($input['operational_risk_score'] ?? 0);
            $risk_level = $input['risk_level'] ?? 'medium';
            $recommendations = trim($input['recommendations'] ?? '');
            
            if (!$client_id) {
                echo json_encode(['success' => false, 'error' => 'Client is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO risk_assessments (
                    client_id, assessment_date, risk_score, risk_level,
                    credit_risk_score, fraud_risk_score, compliance_risk_score,
                    operational_risk_score, recommendations, assessed_by, status, created_at
                ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $client_id, $risk_score, $risk_level,
                $credit_risk_score, $fraud_risk_score, $compliance_risk_score,
                $operational_risk_score, $recommendations, $user_id
            ]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Risk Assessment Created', "Assessment for client ID $client_id with score $risk_score"]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }
        
        // ── UPDATE ASSESSMENT STATUS ─────────────────────────────────
        if ($action === 'update_assessment') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE risk_assessments SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET FRAUD ALERTS ──────────────────────────────────────────
        if ($action === 'get_fraud_alerts') {
            $status = $_GET['status'] ?? '';
            $severity = $_GET['severity'] ?? '';
            
            $sql = "SELECT f.*, c.name as client_name, u.name as resolved_by_name 
                    FROM fraud_alerts f
                    LEFT JOIN customers c ON f.client_id = c.id
                    LEFT JOIN users u ON f.resolved_by = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND f.status = ?";
                $params[] = $status;
            }
            if ($severity) {
                $sql .= " AND f.severity = ?";
                $params[] = $severity;
            }
            
            $sql .= " ORDER BY f.detected_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $alerts = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'alerts' => $alerts]);
            exit;
        }
        
        // ── UPDATE FRAUD ALERT ───────────────────────────────────────
        if ($action === 'update_alert') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE fraud_alerts SET status = ?";
            $params = [$status];
            
            if ($status === 'resolved' || $status === 'confirmed' || $status === 'false_alarm') {
                $sql .= ", resolved_at = NOW(), resolved_by = ?";
                $params[] = $user_id;
            }
            if ($notes) {
                $sql .= ", notes = CONCAT(notes, ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Fraud Alert Updated', "Alert ID $id status changed to $status"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET COMPLIANCE CHECKS ────────────────────────────────────
        if ($action === 'get_compliance_checks') {
            $stmt = $pdo->query("
                SELECT c.*, cl.name as client_name, u.name as checked_by_name 
                FROM compliance_checks c
                LEFT JOIN customers cl ON c.client_id = cl.id
                LEFT JOIN users u ON c.checked_by = u.id
                ORDER BY c.check_date DESC
                LIMIT 50
            ");
            $checks = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'checks' => $checks]);
            exit;
        }
        
        // ── GET MITIGATION PLANS ──────────────────────────────────────
        if ($action === 'get_mitigation_plans') {
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT m.*, c.name as client_name, u.name as assigned_to_name 
                    FROM risk_mitigation_plans m
                    LEFT JOIN customers c ON m.client_id = c.id
                    LEFT JOIN users u ON m.assigned_to = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND m.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY m.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $plans = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'plans' => $plans]);
            exit;
        }
        
        // ── ADD MITIGATION PLAN ──────────────────────────────────────
        if ($action === 'add_mitigation_plan') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $risk_type = trim($input['risk_type'] ?? '');
            $plan = trim($input['plan'] ?? '');
            $timeline = trim($input['timeline'] ?? '');
            $assigned_to = (int)($input['assigned_to'] ?? 0);
            
            if (!$client_id || empty($risk_type) || empty($plan)) {
                echo json_encode(['success' => false, 'error' => 'Client, risk type and plan are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO risk_mitigation_plans (client_id, risk_type, plan, timeline, status, assigned_to, created_at)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([$client_id, $risk_type, $plan, $timeline, $assigned_to]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE MITIGATION PLAN ────────────────────────────────────
        if ($action === 'update_mitigation_plan') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE risk_mitigation_plans SET status = ?";
            $params = [$status];
            
            if ($status === 'completed') {
                $sql .= ", completed_at = NOW()";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET CLIENTS ──────────────────────────────────────────────
        if ($action === 'get_clients') {
            $stmt = $pdo->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name");
            $clients = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'clients' => $clients]);
            exit;
        }
        
        // ── GET USERS ─────────────────────────────────────────────────
        if ($action === 'get_users') {
            $stmt = $pdo->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
            $users = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'users' => $users]);
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
<title>Risk Dashboard | CIBIL Repair</title>

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
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--radius-md);
    background: rgba(13,158,120,0.12);
    color: var(--brand);
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all var(--transition);
}
.back-btn:hover { background: rgba(13,158,120,0.25); transform: translateX(-2px); }

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

/* RISK LEVEL BADGES */
.risk-critical { background: #fecaca; color: #7f1d1d; }
.risk-high { background: #fee2e2; color: #991b1b; }
.risk-medium { background: #fef3c7; color: #78350f; }
.risk-low { background: #dcfce7; color: #166534; }

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
    max-width: 700px;
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
        <div class="brand-icon">RD</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Risk Management</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Risk Assessment</div>
        <div class="nav-item" data-section="assessments">
            <i class="fas fa-clipboard-check"></i>
            <span class="nav-label">Assessments</span>
        </div>
        <div class="nav-item" data-section="newAssessment">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Assessment</span>
        </div>
        <div class="nav-section-label">Fraud Detection</div>
        <div class="nav-item" data-section="fraudAlerts">
            <i class="fas fa-shield-alt"></i>
            <span class="nav-label">Fraud Alerts</span>
        </div>
        <div class="nav-section-label">Compliance</div>
        <div class="nav-item" data-section="compliance">
            <i class="fas fa-gavel"></i>
            <span class="nav-label">Compliance Checks</span>
        </div>
        <div class="nav-section-label">Mitigation</div>
        <div class="nav-item" data-section="mitigation">
            <i class="fas fa-tasks"></i>
            <span class="nav-label">Mitigation Plans</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Risk Analytics</span>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
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
            <a href="admin-dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Admin
            </a>
            <span class="page-title" id="pageTitle">Risk Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-clipboard-check"></i></span>
                    <div class="stat-value" id="totalAssessments">—</div>
                    <div class="stat-label">Total Assessments</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="pendingAssessments">—</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="criticalRisks">—</div>
                    <div class="stat-label">Critical Risks</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-shield-alt"></i></span>
                    <div class="stat-value" id="activeAlerts">—</div>
                    <div class="stat-label">Active Fraud Alerts</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Risk Distribution</div>
                    </div>
                    <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                        <canvas id="riskDistributionChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-line"></i> Risk Metrics</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="riskMetricsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Assessments</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('newAssessment')"><i class="fas fa-plus"></i> New Assessment</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Risk Score</th><th>Level</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody id="recentAssessmentsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bell"></i> Recent Fraud Alerts</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('fraudAlerts')"><i class="fas fa-bell"></i> View All</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Alert Type</th><th>Severity</th><th>Status</th><th>Detected</th><th>Actions</th></tr></thead>
                        <tbody id="recentAlertsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ASSESSMENTS ====== -->
        <div class="section" id="assessmentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clipboard-check"></i> Risk Assessments</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="showSection('newAssessment')"><i class="fas fa-plus"></i> New</button>
                        <button class="btn btn-success btn-sm" onclick="exportAssessments()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="assessmentSearch" placeholder="Search…" oninput="debounce(loadAssessments, 400)()">
                    </div>
                    <select class="form-select" id="assessmentStatus" onchange="loadAssessments()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select class="form-select" id="assessmentLevel" onchange="loadAssessments()" style="width:140px;padding:8px 12px;">
                        <option value="">All Levels</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Risk Score</th><th>Credit</th><th>Fraud</th><th>Compliance</th><th>Level</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody id="assessmentsBody">
                            <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== NEW ASSESSMENT ====== -->
        <div class="section" id="newAssessmentSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-plus-circle"></i> New Risk Assessment</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('assessments')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Client <span class="form-required">*</span></label>
                        <select class="form-select" id="assessmentClient"></select>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Risk Score (0-100)</label>
                            <input class="form-input" id="assessmentScore" type="number" min="0" max="100" value="50">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Risk Level</label>
                            <select class="form-select" id="assessmentLevelNew">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Credit Risk (0-100)</label>
                            <input class="form-input" id="assessmentCredit" type="number" min="0" max="100" value="50">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Fraud Risk (0-100)</label>
                            <input class="form-input" id="assessmentFraud" type="number" min="0" max="100" value="30">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Compliance Risk (0-100)</label>
                            <input class="form-input" id="assessmentCompliance" type="number" min="0" max="100" value="40">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Operational Risk (0-100)</label>
                            <input class="form-input" id="assessmentOperational" type="number" min="0" max="100" value="30">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Recommendations</label>
                        <textarea class="form-textarea" id="assessmentRecommendations" rows="3" placeholder="Risk mitigation recommendations…"></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="addAssessment()"><i class="fas fa-save"></i> Save Assessment</button>
                </div>
            </div>
        </div>

        <!-- ====== FRAUD ALERTS ====== -->
        <div class="section" id="fraudAlertsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-shield-alt"></i> Fraud Alerts</div>
                    <button class="btn btn-success btn-sm" onclick="exportAlerts()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="alertStatus" onchange="loadFraudAlerts()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="investigating">Investigating</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="false_alarm">False Alarm</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <select class="form-select" id="alertSeverity" onchange="loadFraudAlerts()" style="width:150px;padding:8px 12px;">
                        <option value="">All Severity</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Alert Type</th><th>Description</th><th>Severity</th><th>Status</th><th>Detected</th><th>Actions</th></tr></thead>
                        <tbody id="alertsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== COMPLIANCE ====== -->
        <div class="section" id="complianceSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-gavel"></i> Compliance Checks</div>
                    <button class="btn btn-success btn-sm" onclick="exportCompliance()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Check Type</th><th>Check Date</th><th>Status</th><th>Findings</th><th>Checked By</th></tr></thead>
                        <tbody id="complianceBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== MITIGATION ====== -->
        <div class="section" id="mitigationSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tasks"></i> Risk Mitigation Plans</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addMitigationModal')"><i class="fas fa-plus"></i> Add Plan</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="mitigationStatus" onchange="loadMitigationPlans()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Risk Type</th><th>Plan</th><th>Timeline</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="mitigationBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Risk Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Assessment Status Modal -->
<div class="modal-overlay" id="updateAssessmentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Assessment</span>
            <button class="modal-close" onclick="closeModal('updateAssessmentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateAssessmentId">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-select" id="updateAssessmentStatus">
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateAssessmentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateAssessmentStatus()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Update Alert Status Modal -->
<div class="modal-overlay" id="updateAlertModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Alert</span>
            <button class="modal-close" onclick="closeModal('updateAlertModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateAlertId">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-select" id="updateAlertStatus">
                    <option value="new">New</option>
                    <option value="investigating">Investigating</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="false_alarm">False Alarm</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateAlertNotes" rows="3" placeholder="Add notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateAlertModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateAlertStatus()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Add Mitigation Plan Modal -->
<div class="modal-overlay" id="addMitigationModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Mitigation Plan</span>
            <button class="modal-close" onclick="closeModal('addMitigationModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Client <span class="form-required">*</span></label>
                <select class="form-select" id="mitigationClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Risk Type <span class="form-required">*</span></label>
                <select class="form-select" id="mitigationRiskType">
                    <option value="Credit Risk">Credit Risk</option>
                    <option value="Fraud Risk">Fraud Risk</option>
                    <option value="Compliance Risk">Compliance Risk</option>
                    <option value="Operational Risk">Operational Risk</option>
                    <option value="Reputation Risk">Reputation Risk</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Plan <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="mitigationPlan" rows="4" placeholder="Detailed mitigation plan..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Timeline</label>
                    <input class="form-input" id="mitigationTimeline" placeholder="e.g., 7 days, 2 weeks">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Assigned To</label>
                    <select class="form-select" id="mitigationAssigned"></select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addMitigationModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addMitigationPlan()"><i class="fas fa-save"></i> Add Plan</button>
        </div>
    </div>
</div>

<!-- Update Mitigation Plan Modal -->
<div class="modal-overlay" id="updateMitigationModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Plan</span>
            <button class="modal-close" onclick="closeModal('updateMitigationModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateMitigationId">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-select" id="updateMitigationStatus">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateMitigationModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateMitigationPlan()"><i class="fas fa-save"></i> Update</button>
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
    localStorage.setItem('riskTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('riskTheme') || 'light'); })();

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
    dashboard: 'Risk Dashboard',
    assessments: 'Risk Assessments',
    newAssessment: 'New Assessment',
    fraudAlerts: 'Fraud Alerts',
    compliance: 'Compliance Checks',
    mitigation: 'Mitigation Plans',
    analytics: 'Risk Analytics'
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
        assessments: loadAssessments,
        fraudAlerts: loadFraudAlerts,
        compliance: loadCompliance,
        mitigation: loadMitigationPlans,
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
function getRiskBadge(level) {
    const map = {
        'low': 'risk-low',
        'medium': 'risk-medium',
        'high': 'risk-high',
        'critical': 'risk-critical'
    };
    const labels = {
        'low': 'Low',
        'medium': 'Medium',
        'high': 'High',
        'critical': 'Critical'
    };
    const cls = map[level?.toLowerCase()] || 'risk-medium';
    return `<span class="badge ${cls}">${labels[level] || level}</span>`;
}

function getSeverityBadge(severity) {
    const map = {
        'low': 'badge-success',
        'medium': 'badge-warning',
        'high': 'badge-danger',
        'critical': 'risk-critical'
    };
    const cls = map[severity?.toLowerCase()] || 'badge-warning';
    return `<span class="badge ${cls}">${severity || 'medium'}</span>`;
}

function getStatusBadge(status) {
    const map = {
        'pending': 'badge-warning',
        'reviewed': 'badge-info',
        'approved': 'badge-success',
        'rejected': 'badge-danger',
        'new': 'badge-warning',
        'investigating': 'badge-info',
        'confirmed': 'badge-danger',
        'false_alarm': 'badge-gray',
        'resolved': 'badge-success',
        'in_progress': 'badge-info',
        'completed': 'badge-success',
        'cancelled': 'badge-gray'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${status || '—'}</span>`;
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

    document.getElementById('totalAssessments').textContent = data.total_assessments || 0;
    document.getElementById('pendingAssessments').textContent = data.pending_assessments || 0;
    document.getElementById('criticalRisks').textContent = data.critical_risks || 0;
    document.getElementById('activeAlerts').textContent = data.active_alerts || 0;

    // Risk distribution chart
    if (data.risk_distribution) {
        destroyChart('riskDistributionChart');
        const ctx = document.getElementById('riskDistributionChart').getContext('2d');
        const colors = ['#059669', '#d97706', '#dc2626', '#7f1d1d'];
        charts.riskDistributionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.risk_distribution.labels || [],
                datasets: [{
                    data: data.risk_distribution.values || [],
                    backgroundColor: colors.slice(0, data.risk_distribution.labels?.length || 0),
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

    // Risk metrics chart
    const metricsData = await apiCall('get_risk_metrics');
    if (metricsData.success && metricsData.metrics) {
        destroyChart('riskMetricsChart');
        const ctx = document.getElementById('riskMetricsChart').getContext('2d');
        const labels = metricsData.metrics.map(m => m.metric_name);
        const values = metricsData.metrics.map(m => m.metric_value);
        const targets = metricsData.metrics.map(m => m.target_value);
        
        charts.riskMetricsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current',
                        data: values,
                        backgroundColor: '#0d9e78',
                        borderRadius: 4
                    },
                    {
                        label: 'Target',
                        data: targets,
                        backgroundColor: 'rgba(13,158,120,0.2)',
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

    // Recent assessments
    const body = document.getElementById('recentAssessmentsBody');
    if (data.recent_assessments && data.recent_assessments.length) {
        body.innerHTML = data.recent_assessments.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name || '—')}</strong></td>
                <td><strong>${a.risk_score || 0}</strong></td>
                <td>${getRiskBadge(a.risk_level)}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>${new Date(a.assessment_date || a.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateAssessment(${a.id}, '${a.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewAssessment(${a.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No assessments found</p></div></td></tr>';
    }

    // Recent alerts
    const alertsBody = document.getElementById('recentAlertsBody');
    if (data.recent_alerts && data.recent_alerts.length) {
        alertsBody.innerHTML = data.recent_alerts.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name || '—')}</strong></td>
                <td>${escHtml(a.alert_type)}</td>
                <td>${getSeverityBadge(a.severity)}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>${new Date(a.detected_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateAlert(${a.id}, '${a.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewAlert(${a.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        alertsBody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-bell"></i><p>No alerts found</p></div></td></tr>';
    }
}

function viewAssessment(id) {
    showToast(`Viewing assessment #${id}`, 'info');
}

function viewAlert(id) {
    showToast(`Viewing alert #${id}`, 'info');
}

// ── ASSESSMENTS ───────────────────────────────────────────────────────
async function loadAssessments() {
    const search = document.getElementById('assessmentSearch')?.value || '';
    const status = document.getElementById('assessmentStatus')?.value || '';
    const level = document.getElementById('assessmentLevel')?.value || '';
    
    const data = await apiCall(`get_assessments?search=${encodeURIComponent(search)}&status=${status}&level=${level}`);
    const body = document.getElementById('assessmentsBody');
    
    if (data.success && data.assessments && data.assessments.length) {
        body.innerHTML = data.assessments.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name || '—')}</strong></td>
                <td><strong>${a.risk_score || 0}</strong></td>
                <td>${a.credit_risk_score || 0}</td>
                <td>${a.fraud_risk_score || 0}</td>
                <td>${a.compliance_risk_score || 0}</td>
                <td>${getRiskBadge(a.risk_level)}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>${new Date(a.assessment_date || a.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateAssessment(${a.id}, '${a.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewAssessment(${a.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No assessments found</p></div></td></tr>';
    }
}

function openUpdateAssessment(id, status) {
    document.getElementById('updateAssessmentId').value = id;
    document.getElementById('updateAssessmentStatus').value = status || 'pending';
    openModal('updateAssessmentModal');
}

async function updateAssessmentStatus() {
    const id = document.getElementById('updateAssessmentId').value;
    const status = document.getElementById('updateAssessmentStatus').value;
    
    const result = await apiCall('update_assessment', 'POST', { id, status });
    if (result.success) {
        showToast('Assessment updated!', 'success');
        closeModal('updateAssessmentModal');
        loadDashboard();
        loadAssessments();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

// ── NEW ASSESSMENT ───────────────────────────────────────────────────
async function addAssessment() {
    const client_id = document.getElementById('assessmentClient').value;
    const risk_score = parseInt(document.getElementById('assessmentScore').value) || 0;
    const risk_level = document.getElementById('assessmentLevelNew').value;
    const credit_risk_score = parseInt(document.getElementById('assessmentCredit').value) || 0;
    const fraud_risk_score = parseInt(document.getElementById('assessmentFraud').value) || 0;
    const compliance_risk_score = parseInt(document.getElementById('assessmentCompliance').value) || 0;
    const operational_risk_score = parseInt(document.getElementById('assessmentOperational').value) || 0;
    const recommendations = document.getElementById('assessmentRecommendations').value.trim();
    
    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    
    const result = await apiCall('add_assessment', 'POST', {
        client_id, risk_score, risk_level, credit_risk_score,
        fraud_risk_score, compliance_risk_score, operational_risk_score, recommendations
    });
    
    if (result.success) {
        showToast('Assessment created!', 'success');
        showSection('assessments');
        loadDashboard();
        loadAssessments();
    } else {
        showToast(result.error || 'Failed to create assessment', 'error');
    }
}

// ── FRAUD ALERTS ─────────────────────────────────────────────────────
async function loadFraudAlerts() {
    const status = document.getElementById('alertStatus')?.value || '';
    const severity = document.getElementById('alertSeverity')?.value || '';
    
    const data = await apiCall(`get_fraud_alerts?status=${status}&severity=${severity}`);
    const body = document.getElementById('alertsBody');
    
    if (data.success && data.alerts && data.alerts.length) {
        body.innerHTML = data.alerts.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name || '—')}</strong></td>
                <td>${escHtml(a.alert_type)}</td>
                <td>${escHtml(a.description)}</td>
                <td>${getSeverityBadge(a.severity)}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>${new Date(a.detected_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateAlert(${a.id}, '${a.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewAlert(${a.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-shield-alt"></i><p>No alerts found</p></div></td></tr>';
    }
}

function openUpdateAlert(id, status) {
    document.getElementById('updateAlertId').value = id;
    document.getElementById('updateAlertStatus').value = status || 'new';
    document.getElementById('updateAlertNotes').value = '';
    openModal('updateAlertModal');
}

async function updateAlertStatus() {
    const id = document.getElementById('updateAlertId').value;
    const status = document.getElementById('updateAlertStatus').value;
    const notes = document.getElementById('updateAlertNotes').value.trim();
    
    const result = await apiCall('update_alert', 'POST', { id, status, notes });
    if (result.success) {
        showToast('Alert updated!', 'success');
        closeModal('updateAlertModal');
        loadDashboard();
        loadFraudAlerts();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

// ── COMPLIANCE ───────────────────────────────────────────────────────
async function loadCompliance() {
    const data = await apiCall('get_compliance_checks');
    const body = document.getElementById('complianceBody');
    
    if (data.success && data.checks && data.checks.length) {
        body.innerHTML = data.checks.map(c => `
            <tr>
                <td><strong>${escHtml(c.client_name || '—')}</strong></td>
                <td>${escHtml(c.check_type)}</td>
                <td>${new Date(c.check_date).toLocaleDateString('en-IN')}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>${escHtml(c.findings || '—')}</td>
                <td>${escHtml(c.checked_by_name || '—')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-gavel"></i><p>No compliance checks found</p></div></td></tr>';
    }
}

// ── MITIGATION ───────────────────────────────────────────────────────
async function loadMitigationPlans() {
    const status = document.getElementById('mitigationStatus')?.value || '';
    
    const data = await apiCall(`get_mitigation_plans?status=${status}`);
    const body = document.getElementById('mitigationBody');
    
    if (data.success && data.plans && data.plans.length) {
        body.innerHTML = data.plans.map(p => `
            <tr>
                <td><strong>${escHtml(p.client_name || '—')}</strong></td>
                <td>${escHtml(p.risk_type)}</td>
                <td>${escHtml(p.plan)}</td>
                <td>${escHtml(p.timeline || '—')}</td>
                <td>${getStatusBadge(p.status)}</td>
                <td>${escHtml(p.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateMitigation(${p.id}, '${p.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-tasks"></i><p>No mitigation plans found</p></div></td></tr>';
    }
}

function openUpdateMitigation(id, status) {
    document.getElementById('updateMitigationId').value = id;
    document.getElementById('updateMitigationStatus').value = status || 'pending';
    openModal('updateMitigationModal');
}

async function updateMitigationPlan() {
    const id = document.getElementById('updateMitigationId').value;
    const status = document.getElementById('updateMitigationStatus').value;
    
    const result = await apiCall('update_mitigation_plan', 'POST', { id, status });
    if (result.success) {
        showToast('Plan updated!', 'success');
        closeModal('updateMitigationModal');
        loadMitigationPlans();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function addMitigationPlan() {
    const client_id = document.getElementById('mitigationClient').value;
    const risk_type = document.getElementById('mitigationRiskType').value;
    const plan = document.getElementById('mitigationPlan').value.trim();
    const timeline = document.getElementById('mitigationTimeline').value.trim();
    const assigned_to = document.getElementById('mitigationAssigned').value;
    
    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!plan) { showToast('Plan is required', 'warning'); return; }
    
    const result = await apiCall('add_mitigation_plan', 'POST', { client_id, risk_type, plan, timeline, assigned_to });
    if (result.success) {
        showToast('Mitigation plan added!', 'success');
        closeModal('addMitigationModal');
        loadMitigationPlans();
    } else {
        showToast(result.error || 'Failed to add plan', 'error');
    }
}

// ── ANALYTICS ────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_risk_metrics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    if (data.metrics) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const labels = data.metrics.map(m => m.metric_name);
        const values = data.metrics.map(m => m.metric_value);
        const targets = data.metrics.map(m => m.target_value);
        
        charts.analyticsChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current',
                        data: values,
                        backgroundColor: 'rgba(13,158,120,0.2)',
                        borderColor: '#0d9e78',
                        pointBackgroundColor: '#0d9e78'
                    },
                    {
                        label: 'Target',
                        data: targets,
                        backgroundColor: 'rgba(37,99,235,0.1)',
                        borderColor: '#2563eb',
                        borderDash: [5, 5],
                        pointBackgroundColor: '#2563eb'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
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
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportAssessments() { showToast('Exporting assessments...', 'info'); }
function exportAlerts() { showToast('Exporting alerts...', 'info'); }
function exportCompliance() { showToast('Exporting compliance checks...', 'info'); }

// ── LOAD DROPDOWNS ──────────────────────────────────────────────────
async function loadClients() {
    const data = await apiCall('get_clients');
    if (data.success && data.clients) {
        const selectIds = ['assessmentClient', 'mitigationClient'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Client —</option>' +
                    data.clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            }
        });
    }
}

async function loadUsers() {
    const data = await apiCall('get_users');
    if (data.success && data.users) {
        const select = document.getElementById('mitigationAssigned');
        if (select) {
            select.innerHTML = '<option value="">— Unassigned —</option>' +
                data.users.map(u => `<option value="${u.id}">${escHtml(u.name)}</option>`).join('');
        }
    }
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'a') showSection('assessments');
    if (e.altKey && e.key === 'f') showSection('fraudAlerts');
    if (e.altKey && e.key === 'm') showSection('mitigation');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addMitigationModal') {
                loadClients();
                loadUsers();
            }
            if (modal.id === 'newAssessmentSection') {
                loadClients();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClients();
loadUsers();

console.log('✅ Risk Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>