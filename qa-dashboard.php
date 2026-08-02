<?php
// ============================================================
// QUALITY ASSURANCE DASHBOARD - FULLY INTEGRATED
// Access: qa_team, admin, manager, super_admin
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

// ── AUTH: allow qa_team, admin, manager, super_admin ──────────────
$allowed_roles = ['qa_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'QA Lead';
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
            // Total projects
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM qa_projects WHERE status != 'archived'");
            $total_projects = (int)($stmt->fetch()['total'] ?? 0);
            
            // Active projects
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM qa_projects WHERE status = 'active'");
            $active_projects = (int)($stmt->fetch()['total'] ?? 0);
            
            // Total test cases
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_cases");
            $total_testcases = (int)($stmt->fetch()['total'] ?? 0);
            
            // Passed test cases
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM test_cases WHERE status = 'passed'");
            $passed_testcases = (int)($stmt->fetch()['total'] ?? 0);
            
            // Open bugs
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM qa_bugs WHERE status IN ('new', 'open', 'in_progress')");
            $open_bugs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Critical bugs
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM qa_bugs WHERE severity = 'critical' AND status NOT IN ('closed', 'verified')");
            $critical_bugs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Test case status distribution
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count 
                FROM test_cases 
                GROUP BY status
            ");
            $status_data = ['draft' => 0, 'pending' => 0, 'approved' => 0, 'passed' => 0, 'failed' => 0, 'blocked' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($status_data[$row['status']])) {
                    $status_data[$row['status']] = (int)$row['count'];
                }
            }
            
            // Recent test cases
            $stmt = $pdo->query("
                SELECT t.*, p.project_name, u.name as assigned_to_name 
                FROM test_cases t
                LEFT JOIN qa_projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                ORDER BY t.created_at DESC
                LIMIT 10
            ");
            $recent_testcases = $stmt->fetchAll();
            
            // Recent bugs
            $stmt = $pdo->query("
                SELECT b.*, p.project_name, u.name as assigned_to_name, r.name as reported_by_name 
                FROM qa_bugs b
                LEFT JOIN qa_projects p ON b.project_id = p.id
                LEFT JOIN users u ON b.assigned_to = u.id
                LEFT JOIN users r ON b.reported_by = r.id
                ORDER BY b.created_at DESC
                LIMIT 10
            ");
            $recent_bugs = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_projects' => $total_projects,
                'active_projects' => $active_projects,
                'total_testcases' => $total_testcases,
                'passed_testcases' => $passed_testcases,
                'open_bugs' => $open_bugs,
                'critical_bugs' => $critical_bugs,
                'testcase_status' => [
                    'labels' => ['Draft', 'Pending', 'Approved', 'Passed', 'Failed', 'Blocked'],
                    'values' => [$status_data['draft'], $status_data['pending'], $status_data['approved'], $status_data['passed'], $status_data['failed'], $status_data['blocked']]
                ],
                'recent_testcases' => $recent_testcases,
                'recent_bugs' => $recent_bugs
            ]);
            exit;
        }
        
        // ── GET PROJECTS ─────────────────────────────────────────────
        if ($action === 'get_projects') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT p.*, u.name as qa_lead_name 
                    FROM qa_projects p
                    LEFT JOIN users u ON p.qa_lead = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (p.project_name LIKE ? OR p.project_id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND p.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'projects' => $projects]);
            exit;
        }
        
        // ── CREATE PROJECT ───────────────────────────────────────────
        if ($action === 'create_project') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_name = trim($input['project_name'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $start_date = $input['start_date'] ?? null;
            $end_date = $input['end_date'] ?? null;
            $qa_lead = (int)($input['qa_lead'] ?? 0);
            
            if (empty($project_name)) {
                echo json_encode(['success' => false, 'error' => 'Project name is required']);
                exit;
            }
            
            $project_id = 'QA-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO qa_projects (project_id, project_name, description, priority, start_date, end_date, qa_lead, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$project_id, $project_name, $description, $priority, $start_date, $end_date, $qa_lead, $user_id]);
            
            echo json_encode(['success' => true, 'project_id' => $project_id]);
            exit;
        }
        
        // ── GET TEST CASES ───────────────────────────────────────────
        if ($action === 'get_testcases') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';
            
            $sql = "SELECT t.*, p.project_name, u.name as assigned_to_name 
                    FROM test_cases t
                    LEFT JOIN qa_projects p ON t.project_id = p.id
                    LEFT JOIN users u ON t.assigned_to = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($project_id > 0) {
                $sql .= " AND t.project_id = ?";
                $params[] = $project_id;
            }
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            if ($type) {
                $sql .= " AND t.test_type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $testcases = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'testcases' => $testcases]);
            exit;
        }
        
        // ── CREATE TEST CASE ─────────────────────────────────────────
        if ($action === 'create_testcase') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $test_name = trim($input['test_name'] ?? '');
            $description = trim($input['description'] ?? '');
            $test_type = $input['test_type'] ?? 'functional';
            $priority = $input['priority'] ?? 'medium';
            $preconditions = trim($input['preconditions'] ?? '');
            $test_steps = trim($input['test_steps'] ?? '');
            $expected_result = trim($input['expected_result'] ?? '');
            $assigned_to = (int)($input['assigned_to'] ?? 0);
            
            if (!$project_id || empty($test_name)) {
                echo json_encode(['success' => false, 'error' => 'Project and test name are required']);
                exit;
            }
            
            $test_id = 'TC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO test_cases (
                    test_id, project_id, test_name, description, test_type, priority,
                    preconditions, test_steps, expected_result, status, created_by, assigned_to, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW())
            ");
            $stmt->execute([
                $test_id, $project_id, $test_name, $description, $test_type, $priority,
                $preconditions, $test_steps, $expected_result, $user_id, $assigned_to
            ]);
            
            echo json_encode(['success' => true, 'test_id' => $test_id]);
            exit;
        }
        
        // ── UPDATE TEST CASE ─────────────────────────────────────────
        if ($action === 'update_testcase') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $actual_result = trim($input['actual_result'] ?? '');
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE test_cases SET status = ?";
            $params = [$status];
            
            if ($actual_result) {
                $sql .= ", actual_result = ?";
                $params[] = $actual_result;
            }
            if ($notes) {
                $sql .= ", actual_result = CONCAT(actual_result, ?)";
                $params[] = "\nNotes: " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Record execution if status is passed/failed
            if ($status === 'passed' || $status === 'failed' || $status === 'blocked') {
                $stmt = $pdo->prepare("
                    INSERT INTO test_executions (test_case_id, executed_by, status, actual_result, execution_date)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$id, $user_id, $status, $actual_result]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE TEST CASE ─────────────────────────────────────────
        if ($action === 'delete_testcase') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM test_cases WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET BUGS ──────────────────────────────────────────────────
        if ($action === 'get_bugs') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            $severity = $_GET['severity'] ?? '';
            
            $sql = "SELECT b.*, p.project_name, u.name as assigned_to_name, r.name as reported_by_name 
                    FROM qa_bugs b
                    LEFT JOIN qa_projects p ON b.project_id = p.id
                    LEFT JOIN users u ON b.assigned_to = u.id
                    LEFT JOIN users r ON b.reported_by = r.id
                    WHERE 1=1";
            $params = [];
            
            if ($project_id > 0) {
                $sql .= " AND b.project_id = ?";
                $params[] = $project_id;
            }
            if ($status) {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
            if ($severity) {
                $sql .= " AND b.severity = ?";
                $params[] = $severity;
            }
            
            $sql .= " ORDER BY b.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bugs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'bugs' => $bugs]);
            exit;
        }
        
        // ── CREATE BUG ──────────────────────────────────────────────
        if ($action === 'create_bug') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $bug_title = trim($input['bug_title'] ?? '');
            $description = trim($input['description'] ?? '');
            $steps_to_reproduce = trim($input['steps_to_reproduce'] ?? '');
            $severity = $input['severity'] ?? 'major';
            $priority = $input['priority'] ?? 'medium';
            $assigned_to = (int)($input['assigned_to'] ?? 0);
            $environment = trim($input['environment'] ?? '');
            $browser = trim($input['browser'] ?? '');
            $os = trim($input['os'] ?? '');
            
            if (!$project_id || empty($bug_title)) {
                echo json_encode(['success' => false, 'error' => 'Project and bug title are required']);
                exit;
            }
            
            $bug_id = 'BUG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO qa_bugs (
                    bug_id, project_id, bug_title, description, steps_to_reproduce,
                    severity, priority, status, assigned_to, reported_by,
                    environment, browser, os, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $bug_id, $project_id, $bug_title, $description, $steps_to_reproduce,
                $severity, $priority, $assigned_to, $user_id,
                $environment, $browser, $os
            ]);
            
            echo json_encode(['success' => true, 'bug_id' => $bug_id]);
            exit;
        }
        
        // ── UPDATE BUG ──────────────────────────────────────────────
        if ($action === 'update_bug') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE qa_bugs SET status = ?";
            $params = [$status];
            
            if ($status === 'fixed') {
                $sql .= ", fixed_by = ?, resolved_at = NOW()";
                $params[] = $user_id;
            }
            if ($status === 'verified') {
                $sql .= ", verified_by = ?, verified_at = NOW()";
                $params[] = $user_id;
            }
            if ($notes) {
                $sql .= ", description = CONCAT(description, ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET METRICS ──────────────────────────────────────────────
        if ($action === 'get_metrics') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            
            $sql = "SELECT * FROM qa_metrics WHERE 1=1";
            $params = [];
            
            if ($project_id > 0) {
                $sql .= " AND project_id = ?";
                $params[] = $project_id;
            }
            
            $sql .= " ORDER BY recorded_at DESC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $metrics = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'metrics' => $metrics]);
            exit;
        }
        
        // ── GET USERS ─────────────────────────────────────────────────
        if ($action === 'get_users') {
            $stmt = $pdo->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name");
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
<title>QA Dashboard | CIBIL Repair</title>

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
.badge-purple { background: var(--purple-bg); color: var(--purple); }

/* TEST STATUS BADGES */
.test-draft { background: #e5e7eb; color: #4b5563; }
.test-pending { background: #fef3c7; color: #78350f; }
.test-approved { background: #dbeafe; color: #1e40af; }
.test-passed { background: #d1fae5; color: #065f46; }
.test-failed { background: #fee2e2; color: #991b1b; }
.test-blocked { background: #fecaca; color: #7f1d1d; }

/* BUG SEVERITY */
.sev-critical { background: #fecaca; color: #7f1d1d; }
.sev-major { background: #fee2e2; color: #991b1b; }
.sev-minor { background: #fef3c7; color: #78350f; }
.sev-trivial { background: #e5e7eb; color: #4b5563; }

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
        <div class="brand-icon">QA</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Quality Assurance</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Projects</div>
        <div class="nav-item" data-section="projects">
            <i class="fas fa-folder-open"></i>
            <span class="nav-label">QA Projects</span>
        </div>
        <div class="nav-item" data-section="createProject">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Project</span>
        </div>
        <div class="nav-section-label">Testing</div>
        <div class="nav-item" data-section="testcases">
            <i class="fas fa-check-double"></i>
            <span class="nav-label">Test Cases</span>
        </div>
        <div class="nav-item" data-section="createTestcase">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Test Case</span>
        </div>
        <div class="nav-section-label">Bugs</div>
        <div class="nav-item" data-section="bugs">
            <i class="fas fa-bug"></i>
            <span class="nav-label">Bug Tracking</span>
        </div>
        <div class="nav-item" data-section="createBug">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">Report Bug</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="metrics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">QA Metrics</span>
        </div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">Analytics</span>
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
            <span class="page-title" id="pageTitle">QA Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-folder-open"></i></span>
                    <div class="stat-value" id="totalProjects">—</div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-play-circle"></i></span>
                    <div class="stat-value" id="activeProjects">—</div>
                    <div class="stat-label">Active Projects</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-check-double"></i></span>
                    <div class="stat-value" id="totalTestcases">—</div>
                    <div class="stat-label">Test Cases</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="passedTestcases">—</div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-bug"></i></span>
                    <div class="stat-value" id="openBugs">—</div>
                    <div class="stat-label">Open Bugs</div>
                </div>
                <div class="stat-card danger">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="criticalBugs">—</div>
                    <div class="stat-label">Critical Bugs</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Test Case Status</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="testcaseChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-doughnut"></i> Bug Severity</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="bugSeverityChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Test Cases</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createTestcase')"><i class="fas fa-plus"></i> New Test Case</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Test ID</th><th>Name</th><th>Project</th><th>Type</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="recentTestcasesBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bug"></i> Recent Bugs</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createBug')"><i class="fas fa-bug"></i> Report Bug</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Bug ID</th><th>Title</th><th>Project</th><th>Severity</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="recentBugsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PROJECTS ====== -->
        <div class="section" id="projectsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-folder-open"></i> QA Projects</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="showSection('createProject')"><i class="fas fa-plus"></i> New Project</button>
                        <button class="btn btn-success btn-sm" onclick="exportProjects()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="projectSearch" placeholder="Search projects…" oninput="debounce(loadProjects, 400)()">
                    </div>
                    <select class="form-select" id="projectStatusFilter" onchange="loadProjects()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Project ID</th><th>Name</th><th>Priority</th><th>Status</th><th>QA Lead</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
                        <tbody id="projectsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CREATE PROJECT ====== -->
        <div class="section" id="createProjectSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-plus-circle"></i> Create QA Project</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('projects')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Project Name <span class="form-required">*</span></label>
                        <input class="form-input" id="projectName" placeholder="Enter project name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="projectDesc" rows="3" placeholder="Project description..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Priority</label>
                            <select class="form-select" id="projectPriority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">QA Lead</label>
                            <select class="form-select" id="projectQALead"></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-input" id="projectStart">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-input" id="projectEnd">
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="createProject()"><i class="fas fa-save"></i> Create Project</button>
                </div>
            </div>
        </div>

        <!-- ====== TEST CASES ====== -->
        <div class="section" id="testcasesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-check-double"></i> Test Cases</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createTestcase')"><i class="fas fa-plus"></i> New Test Case</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="testcaseProject" onchange="loadTestcases()" style="width:200px;padding:8px 12px;">
                        <option value="">All Projects</option>
                    </select>
                    <select class="form-select" id="testcaseStatus" onchange="loadTestcases()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="passed">Passed</option>
                        <option value="failed">Failed</option>
                        <option value="blocked">Blocked</option>
                    </select>
                    <select class="form-select" id="testcaseType" onchange="loadTestcases()" style="width:150px;padding:8px 12px;">
                        <option value="">All Types</option>
                        <option value="functional">Functional</option>
                        <option value="performance">Performance</option>
                        <option value="security">Security</option>
                        <option value="usability">Usability</option>
                        <option value="integration">Integration</option>
                        <option value="regression">Regression</option>
                        <option value="smoke">Smoke</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Test ID</th><th>Name</th><th>Project</th><th>Type</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="testcasesBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CREATE TEST CASE ====== -->
        <div class="section" id="createTestcaseSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-plus-circle"></i> Create Test Case</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('testcases')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Project <span class="form-required">*</span></label>
                        <select class="form-select" id="testcaseProjectNew"></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Test Name <span class="form-required">*</span></label>
                        <input class="form-input" id="testcaseName" placeholder="Enter test name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="testcaseDesc" rows="2" placeholder="Test description..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Test Type</label>
                            <select class="form-select" id="testcaseTypeNew">
                                <option value="functional">Functional</option>
                                <option value="performance">Performance</option>
                                <option value="security">Security</option>
                                <option value="usability">Usability</option>
                                <option value="integration">Integration</option>
                                <option value="regression">Regression</option>
                                <option value="smoke">Smoke</option>
                            </select>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Priority</label>
                            <select class="form-select" id="testcasePriority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preconditions</label>
                        <textarea class="form-textarea" id="testcasePreconditions" rows="2" placeholder="Preconditions..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Test Steps</label>
                        <textarea class="form-textarea" id="testcaseSteps" rows="3" placeholder="Step-by-step test instructions..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Result</label>
                        <textarea class="form-textarea" id="testcaseExpected" rows="2" placeholder="Expected result..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" id="testcaseAssigned"></select>
                    </div>
                    <button class="btn btn-primary" onclick="createTestcase()"><i class="fas fa-save"></i> Create Test Case</button>
                </div>
            </div>
        </div>

        <!-- ====== BUGS ====== -->
        <div class="section" id="bugsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bug"></i> Bug Tracking</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createBug')"><i class="fas fa-bug"></i> Report Bug</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="bugProjectFilter" onchange="loadBugs()" style="width:200px;padding:8px 12px;">
                        <option value="">All Projects</option>
                    </select>
                    <select class="form-select" id="bugStatusFilter" onchange="loadBugs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="fixed">Fixed</option>
                        <option value="verified">Verified</option>
                        <option value="closed">Closed</option>
                        <option value="reopened">Reopened</option>
                    </select>
                    <select class="form-select" id="bugSeverityFilter" onchange="loadBugs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Severity</option>
                        <option value="critical">Critical</option>
                        <option value="major">Major</option>
                        <option value="minor">Minor</option>
                        <option value="trivial">Trivial</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Bug ID</th><th>Title</th><th>Project</th><th>Severity</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="bugsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CREATE BUG ====== -->
        <div class="section" id="createBugSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bug"></i> Report Bug</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('bugs')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Project <span class="form-required">*</span></label>
                        <select class="form-select" id="bugProjectNew"></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bug Title <span class="form-required">*</span></label>
                        <input class="form-input" id="bugTitle" placeholder="Brief bug title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="bugDesc" rows="2" placeholder="Detailed description..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Steps to Reproduce</label>
                        <textarea class="form-textarea" id="bugSteps" rows="3" placeholder="Step-by-step reproduction steps..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Severity</label>
                            <select class="form-select" id="bugSeverity">
                                <option value="critical">Critical</option>
                                <option value="major" selected>Major</option>
                                <option value="minor">Minor</option>
                                <option value="trivial">Trivial</option>
                            </select>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Priority</label>
                            <select class="form-select" id="bugPriority">
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium" selected>Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" id="bugAssigned"></select>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Environment</label>
                            <input class="form-input" id="bugEnvironment" placeholder="e.g., Production, Staging">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Browser</label>
                            <input class="form-input" id="bugBrowser" placeholder="e.g., Chrome 120">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">OS</label>
                        <input class="form-input" id="bugOS" placeholder="e.g., Windows 11, macOS 14">
                    </div>
                    <button class="btn btn-primary" onclick="createBug()"><i class="fas fa-bug"></i> Report Bug</button>
                </div>
            </div>
        </div>

        <!-- ====== METRICS ====== -->
        <div class="section" id="metricsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> QA Metrics</div>
                    <button class="btn btn-success btn-sm" onclick="exportMetrics()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Project</th><th>Metric</th><th>Value</th><th>Target</th><th>Category</th><th>Recorded</th></tr></thead>
                        <tbody id="metricsBody">
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
                    <div class="card-title"><i class="fas fa-chart-line"></i> QA Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Test Case Modal -->
<div class="modal-overlay" id="updateTestcaseModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Test Case</span>
            <button class="modal-close" onclick="closeModal('updateTestcaseModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateTestcaseId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateTestcaseStatus">
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="passed">Passed</option>
                    <option value="failed">Failed</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Actual Result</label>
                <textarea class="form-textarea" id="updateTestcaseResult" rows="3" placeholder="Actual result..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateTestcaseNotes" rows="2" placeholder="Additional notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateTestcaseModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateTestcase()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Update Bug Modal -->
<div class="modal-overlay" id="updateBugModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Bug</span>
            <button class="modal-close" onclick="closeModal('updateBugModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateBugId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateBugStatus">
                    <option value="new">New</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="fixed">Fixed</option>
                    <option value="verified">Verified</option>
                    <option value="closed">Closed</option>
                    <option value="reopened">Reopened</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateBugNotes" rows="3" placeholder="Update notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateBugModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateBug()"><i class="fas fa-save"></i> Update</button>
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
    localStorage.setItem('qaTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('qaTheme') || 'light'); })();

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
    dashboard: 'QA Dashboard',
    projects: 'QA Projects',
    createProject: 'Create Project',
    testcases: 'Test Cases',
    createTestcase: 'Create Test Case',
    bugs: 'Bug Tracking',
    createBug: 'Report Bug',
    metrics: 'QA Metrics',
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
        projects: loadProjects,
        testcases: loadTestcases,
        bugs: loadBugs,
        metrics: loadMetrics,
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
function getTestStatusBadge(status) {
    const map = {
        'draft': 'test-draft',
        'pending': 'test-pending',
        'approved': 'test-approved',
        'passed': 'test-passed',
        'failed': 'test-failed',
        'blocked': 'test-blocked'
    };
    const labels = {
        'draft': 'Draft',
        'pending': 'Pending',
        'approved': 'Approved',
        'passed': 'Passed',
        'failed': 'Failed',
        'blocked': 'Blocked'
    };
    const cls = map[status?.toLowerCase()] || 'test-draft';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getBugSeverityBadge(severity) {
    const map = {
        'critical': 'sev-critical',
        'major': 'sev-major',
        'minor': 'sev-minor',
        'trivial': 'sev-trivial'
    };
    const cls = map[severity?.toLowerCase()] || 'sev-major';
    return `<span class="badge ${cls}">${severity || 'major'}</span>`;
}

function getBugStatusBadge(status) {
    const map = {
        'new': 'badge-warning',
        'open': 'badge-info',
        'in_progress': 'badge-brand',
        'fixed': 'badge-success',
        'verified': 'badge-purple',
        'closed': 'badge-gray',
        'reopened': 'badge-danger'
    };
    const labels = {
        'new': 'New',
        'open': 'Open',
        'in_progress': 'In Progress',
        'fixed': 'Fixed',
        'verified': 'Verified',
        'closed': 'Closed',
        'reopened': 'Reopened'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
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

    document.getElementById('totalProjects').textContent = data.total_projects || 0;
    document.getElementById('activeProjects').textContent = data.active_projects || 0;
    document.getElementById('totalTestcases').textContent = data.total_testcases || 0;
    document.getElementById('passedTestcases').textContent = data.passed_testcases || 0;
    document.getElementById('openBugs').textContent = data.open_bugs || 0;
    document.getElementById('criticalBugs').textContent = data.critical_bugs || 0;

    // Test case status chart
    if (data.testcase_status) {
        destroyChart('testcaseChart');
        const ctx = document.getElementById('testcaseChart').getContext('2d');
        const colors = ['#9ca3af', '#d97706', '#3b82f6', '#059669', '#dc2626', '#f97316'];
        charts.testcaseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.testcase_status.labels || [],
                datasets: [{
                    data: data.testcase_status.values || [],
                    backgroundColor: colors.slice(0, data.testcase_status.labels?.length || 0),
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

    // Bug severity chart
    const bugData = await apiCall('get_bugs');
    if (bugData.success && bugData.bugs) {
        const severityCounts = { critical: 0, major: 0, minor: 0, trivial: 0 };
        bugData.bugs.forEach(b => {
            if (severityCounts.hasOwnProperty(b.severity)) severityCounts[b.severity]++;
        });
        
        destroyChart('bugSeverityChart');
        const ctx = document.getElementById('bugSeverityChart').getContext('2d');
        const colors2 = ['#dc2626', '#f97316', '#d97706', '#9ca3af'];
        charts.bugSeverityChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'Major', 'Minor', 'Trivial'],
                datasets: [{
                    data: [severityCounts.critical, severityCounts.major, severityCounts.minor, severityCounts.trivial],
                    backgroundColor: colors2,
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

    // Recent test cases
    const body = document.getElementById('recentTestcasesBody');
    if (data.recent_testcases && data.recent_testcases.length) {
        body.innerHTML = data.recent_testcases.map(t => `
            <tr>
                <td><span class="font-mono">${escHtml(t.test_id)}</span></td>
                <td><strong>${escHtml(t.test_name)}</strong></td>
                <td>${escHtml(t.project_name || '—')}</td>
                <td><span class="badge badge-info">${escHtml(t.test_type)}</span></td>
                <td>${getTestStatusBadge(t.status)}</td>
                <td>${escHtml(t.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateTestcase(${t.id}, '${t.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTestcase(${t.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No test cases found</p></div></td></tr>';
    }

    // Recent bugs
    const bugsBody = document.getElementById('recentBugsBody');
    if (data.recent_bugs && data.recent_bugs.length) {
        bugsBody.innerHTML = data.recent_bugs.map(b => `
            <tr>
                <td><span class="font-mono">${escHtml(b.bug_id)}</span></td>
                <td><strong>${escHtml(b.bug_title)}</strong></td>
                <td>${escHtml(b.project_name || '—')}</td>
                <td>${getBugSeverityBadge(b.severity)}</td>
                <td>${getBugStatusBadge(b.status)}</td>
                <td>${escHtml(b.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateBug(${b.id}, '${b.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        bugsBody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-bug"></i><p>No bugs found</p></div></td></tr>';
    }
}

// ── PROJECTS ──────────────────────────────────────────────────────────
async function loadProjects() {
    const search = document.getElementById('projectSearch')?.value || '';
    const status = document.getElementById('projectStatusFilter')?.value || '';
    
    const data = await apiCall(`get_projects?search=${encodeURIComponent(search)}&status=${status}`);
    const body = document.getElementById('projectsBody');
    
    if (data.success && data.projects && data.projects.length) {
        body.innerHTML = data.projects.map(p => `
            <tr>
                <td><span class="font-mono">${escHtml(p.project_id)}</span></td>
                <td><strong>${escHtml(p.project_name)}</strong></td>
                <td>${getBugSeverityBadge(p.priority)}</td>
                <td>${getTestStatusBadge(p.status)}</td>
                <td>${escHtml(p.qa_lead_name || '—')}</td>
                <td>${p.start_date || '—'}</td>
                <td>${p.end_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No projects found</p></div></td></tr>';
    }
}

// ── CREATE PROJECT ──────────────────────────────────────────────────
async function createProject() {
    const project_name = document.getElementById('projectName').value.trim();
    const description = document.getElementById('projectDesc').value.trim();
    const priority = document.getElementById('projectPriority').value;
    const qa_lead = document.getElementById('projectQALead').value;
    const start_date = document.getElementById('projectStart').value;
    const end_date = document.getElementById('projectEnd').value;
    
    if (!project_name) { showToast('Project name is required', 'warning'); return; }
    
    const result = await apiCall('create_project', 'POST', {
        project_name, description, priority, qa_lead, start_date, end_date
    });
    
    if (result.success) {
        showToast('Project created!', 'success');
        showSection('projects');
        document.getElementById('projectName').value = '';
        document.getElementById('projectDesc').value = '';
        loadProjects();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create project', 'error');
    }
}

// ── TEST CASES ──────────────────────────────────────────────────────
async function loadTestcases() {
    const project_id = document.getElementById('testcaseProject')?.value || '';
    const status = document.getElementById('testcaseStatus')?.value || '';
    const type = document.getElementById('testcaseType')?.value || '';
    
    const data = await apiCall(`get_testcases?project_id=${project_id}&status=${status}&type=${type}`);
    const body = document.getElementById('testcasesBody');
    
    if (data.success && data.testcases && data.testcases.length) {
        body.innerHTML = data.testcases.map(t => `
            <tr>
                <td><span class="font-mono">${escHtml(t.test_id)}</span></td>
                <td><strong>${escHtml(t.test_name)}</strong></td>
                <td>${escHtml(t.project_name || '—')}</td>
                <td><span class="badge badge-info">${escHtml(t.test_type)}</span></td>
                <td>${getBugSeverityBadge(t.priority)}</td>
                <td>${getTestStatusBadge(t.status)}</td>
                <td>${escHtml(t.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateTestcase(${t.id}, '${t.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTestcase(${t.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-check-double"></i><p>No test cases found</p></div></td></tr>';
    }
}

function openUpdateTestcase(id, status) {
    document.getElementById('updateTestcaseId').value = id;
    document.getElementById('updateTestcaseStatus').value = status || 'draft';
    document.getElementById('updateTestcaseResult').value = '';
    document.getElementById('updateTestcaseNotes').value = '';
    openModal('updateTestcaseModal');
}

async function updateTestcase() {
    const id = document.getElementById('updateTestcaseId').value;
    const status = document.getElementById('updateTestcaseStatus').value;
    const actual_result = document.getElementById('updateTestcaseResult').value.trim();
    const notes = document.getElementById('updateTestcaseNotes').value.trim();
    
    const result = await apiCall('update_testcase', 'POST', { id, status, actual_result, notes });
    if (result.success) {
        showToast('Test case updated!', 'success');
        closeModal('updateTestcaseModal');
        loadTestcases();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function deleteTestcase(id) {
    if (!confirm('Delete this test case?')) return;
    const result = await apiCall('delete_testcase', 'POST', { id });
    if (result.success) {
        showToast('Test case deleted', 'success');
        loadTestcases();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

async function createTestcase() {
    const project_id = document.getElementById('testcaseProjectNew').value;
    const test_name = document.getElementById('testcaseName').value.trim();
    const description = document.getElementById('testcaseDesc').value.trim();
    const test_type = document.getElementById('testcaseTypeNew').value;
    const priority = document.getElementById('testcasePriority').value;
    const preconditions = document.getElementById('testcasePreconditions').value.trim();
    const test_steps = document.getElementById('testcaseSteps').value.trim();
    const expected_result = document.getElementById('testcaseExpected').value.trim();
    const assigned_to = document.getElementById('testcaseAssigned').value;
    
    if (!project_id) { showToast('Please select a project', 'warning'); return; }
    if (!test_name) { showToast('Test name is required', 'warning'); return; }
    
    const result = await apiCall('create_testcase', 'POST', {
        project_id, test_name, description, test_type, priority,
        preconditions, test_steps, expected_result, assigned_to
    });
    
    if (result.success) {
        showToast('Test case created!', 'success');
        showSection('testcases');
        document.getElementById('testcaseName').value = '';
        document.getElementById('testcaseDesc').value = '';
        document.getElementById('testcasePreconditions').value = '';
        document.getElementById('testcaseSteps').value = '';
        document.getElementById('testcaseExpected').value = '';
        loadTestcases();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create test case', 'error');
    }
}

// ── BUGS ──────────────────────────────────────────────────────────────
async function loadBugs() {
    const project_id = document.getElementById('bugProjectFilter')?.value || '';
    const status = document.getElementById('bugStatusFilter')?.value || '';
    const severity = document.getElementById('bugSeverityFilter')?.value || '';
    
    const data = await apiCall(`get_bugs?project_id=${project_id}&status=${status}&severity=${severity}`);
    const body = document.getElementById('bugsBody');
    
    if (data.success && data.bugs && data.bugs.length) {
        body.innerHTML = data.bugs.map(b => `
            <tr>
                <td><span class="font-mono">${escHtml(b.bug_id)}</span></td>
                <td><strong>${escHtml(b.bug_title)}</strong></td>
                <td>${escHtml(b.project_name || '—')}</td>
                <td>${getBugSeverityBadge(b.severity)}</td>
                <td>${getBugSeverityBadge(b.priority)}</td>
                <td>${getBugStatusBadge(b.status)}</td>
                <td>${escHtml(b.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateBug(${b.id}, '${b.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-bug"></i><p>No bugs found</p></div></td></tr>';
    }
}

function openUpdateBug(id, status) {
    document.getElementById('updateBugId').value = id;
    document.getElementById('updateBugStatus').value = status || 'new';
    document.getElementById('updateBugNotes').value = '';
    openModal('updateBugModal');
}

async function updateBug() {
    const id = document.getElementById('updateBugId').value;
    const status = document.getElementById('updateBugStatus').value;
    const notes = document.getElementById('updateBugNotes').value.trim();
    
    const result = await apiCall('update_bug', 'POST', { id, status, notes });
    if (result.success) {
        showToast('Bug updated!', 'success');
        closeModal('updateBugModal');
        loadBugs();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to update bug', 'error');
    }
}

async function createBug() {
    const project_id = document.getElementById('bugProjectNew').value;
    const bug_title = document.getElementById('bugTitle').value.trim();
    const description = document.getElementById('bugDesc').value.trim();
    const steps_to_reproduce = document.getElementById('bugSteps').value.trim();
    const severity = document.getElementById('bugSeverity').value;
    const priority = document.getElementById('bugPriority').value;
    const assigned_to = document.getElementById('bugAssigned').value;
    const environment = document.getElementById('bugEnvironment').value.trim();
    const browser = document.getElementById('bugBrowser').value.trim();
    const os = document.getElementById('bugOS').value.trim();
    
    if (!project_id) { showToast('Please select a project', 'warning'); return; }
    if (!bug_title) { showToast('Bug title is required', 'warning'); return; }
    
    const result = await apiCall('create_bug', 'POST', {
        project_id, bug_title, description, steps_to_reproduce,
        severity, priority, assigned_to, environment, browser, os
    });
    
    if (result.success) {
        showToast('Bug reported!', 'success');
        showSection('bugs');
        document.getElementById('bugTitle').value = '';
        document.getElementById('bugDesc').value = '';
        document.getElementById('bugSteps').value = '';
        document.getElementById('bugEnvironment').value = '';
        document.getElementById('bugBrowser').value = '';
        document.getElementById('bugOS').value = '';
        loadBugs();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to report bug', 'error');
    }
}

// ── METRICS ──────────────────────────────────────────────────────────
async function loadMetrics() {
    const data = await apiCall('get_metrics');
    const body = document.getElementById('metricsBody');
    
    if (data.success && data.metrics && data.metrics.length) {
        body.innerHTML = data.metrics.map(m => `
            <tr>
                <td><strong>${escHtml(m.project_name || '—')}</strong></td>
                <td>${escHtml(m.metric_name)}</td>
                <td>${m.metric_value || 0}</td>
                <td>${m.target_value || 0}</td>
                <td><span class="badge badge-info">${escHtml(m.category)}</span></td>
                <td>${new Date(m.recorded_at).toLocaleDateString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-chart-bar"></i><p>No metrics found</p></div></td></tr>';
    }
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    if (data.testcase_status) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const colors = ['#9ca3af', '#d97706', '#3b82f6', '#059669', '#dc2626', '#f97316'];
        charts.analyticsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.testcase_status.labels || [],
                datasets: [{
                    label: 'Test Cases',
                    data: data.testcase_status.values || [],
                    backgroundColor: colors.slice(0, data.testcase_status.labels?.length || 0),
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
}

// ── LOAD DROPDOWNS ──────────────────────────────────────────────────
async function loadDropdowns() {
    // Load projects
    const projectsData = await apiCall('get_projects');
    if (projectsData.success && projectsData.projects) {
        const selectIds = ['testcaseProject', 'testcaseProjectNew', 'bugProjectFilter', 'bugProjectNew'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Project —</option>' +
                    projectsData.projects.map(p => `<option value="${p.id}">${escHtml(p.project_name)}</option>`).join('');
            }
        });
    }
    
    // Load users
    const usersData = await apiCall('get_users');
    if (usersData.success && usersData.users) {
        const selectIds = ['projectQALead', 'testcaseAssigned', 'bugAssigned'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select User —</option>' +
                    usersData.users.map(u => `<option value="${u.id}">${escHtml(u.name)} (${escHtml(u.role)})</option>`).join('');
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportProjects() { showToast('Exporting projects...', 'info'); }
function exportMetrics() { showToast('Exporting metrics...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'p') showSection('projects');
    if (e.altKey && e.key === 't') showSection('testcases');
    if (e.altKey && e.key === 'b') showSection('bugs');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (['createTestcaseModal', 'createBugModal'].includes(modal.id)) {
                loadDropdowns();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadDropdowns();

console.log('✅ QA Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>