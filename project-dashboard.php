<?php
// ============================================================
// PROJECT DASHBOARD - FULLY INTEGRATED
// Access: project_team, admin, manager, super_admin
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

// ── AUTH: allow project_team, admin, manager, super_admin ──────────
$allowed_roles = ['project_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Project Manager';
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
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
            $total_projects = (int)($stmt->fetch()['total'] ?? 0);
            
            // Active projects
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects WHERE status = 'active'");
            $active_projects = (int)($stmt->fetch()['total'] ?? 0);
            
            // Completed projects
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects WHERE status = 'completed'");
            $completed_projects = (int)($stmt->fetch()['total'] ?? 0);
            
            // Overdue tasks
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM project_tasks WHERE due_date < CURDATE() AND status NOT IN ('done', 'blocked')");
            $overdue_tasks = (int)($stmt->fetch()['total'] ?? 0);
            
            // Tasks by status
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count 
                FROM project_tasks 
                GROUP BY status
            ");
            $task_status = ['todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0, 'blocked' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($task_status[$row['status']])) {
                    $task_status[$row['status']] = (int)$row['count'];
                }
            }
            
            // Project status distribution
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count 
                FROM projects 
                GROUP BY status
            ");
            $project_status = ['planning' => 0, 'active' => 0, 'on_hold' => 0, 'completed' => 0, 'cancelled' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($project_status[$row['status']])) {
                    $project_status[$row['status']] = (int)$row['count'];
                }
            }
            
            // Recent projects
            $stmt = $pdo->query("
                SELECT p.*, c.name as client_name, u.name as manager_name 
                FROM projects p
                LEFT JOIN customers c ON p.client_id = c.id
                LEFT JOIN users u ON p.project_manager = u.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $recent_projects = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_projects' => $total_projects,
                'active_projects' => $active_projects,
                'completed_projects' => $completed_projects,
                'overdue_tasks' => $overdue_tasks,
                'task_status' => [
                    'labels' => ['To Do', 'In Progress', 'Review', 'Done', 'Blocked'],
                    'values' => [$task_status['todo'], $task_status['in_progress'], $task_status['review'], $task_status['done'], $task_status['blocked']]
                ],
                'project_status' => [
                    'labels' => ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'],
                    'values' => [$project_status['planning'], $project_status['active'], $project_status['on_hold'], $project_status['completed'], $project_status['cancelled']]
                ],
                'recent_projects' => $recent_projects
            ]);
            exit;
        }
        
        // ── GET PROJECTS ──────────────────────────────────────────────
        if ($action === 'get_projects') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT p.*, c.name as client_name, u.name as manager_name 
                    FROM projects p
                    LEFT JOIN customers c ON p.client_id = c.id
                    LEFT JOIN users u ON p.project_manager = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (p.project_name LIKE ? OR p.project_code LIKE ? OR p.project_id LIKE ?)";
                $params[] = "%$search%";
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
        
        // ── CREATE PROJECT ────────────────────────────────────────────
        if ($action === 'create_project') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_name = trim($input['project_name'] ?? '');
            $project_code = trim($input['project_code'] ?? '');
            $description = trim($input['description'] ?? '');
            $client_id = (int)($input['client_id'] ?? 0);
            $project_type = $input['project_type'] ?? 'internal';
            $priority = $input['priority'] ?? 'medium';
            $start_date = $input['start_date'] ?? null;
            $end_date = $input['end_date'] ?? null;
            $budget = (float)($input['budget'] ?? 0);
            $project_manager = (int)($input['project_manager'] ?? 0);
            
            if (empty($project_name) || empty($project_code)) {
                echo json_encode(['success' => false, 'error' => 'Project name and code are required']);
                exit;
            }
            
            $project_id = 'PRJ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    project_id, project_name, project_code, description, client_id, project_type,
                    priority, start_date, end_date, budget, project_manager, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planning', ?, NOW())
            ");
            $stmt->execute([
                $project_id, $project_name, $project_code, $description, $client_id, $project_type,
                $priority, $start_date, $end_date, $budget, $project_manager, $user_id
            ]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Project Created', "Project #$project_id: $project_name"]);
            
            echo json_encode(['success' => true, 'project_id' => $project_id]);
            exit;
        }
        
        // ── UPDATE PROJECT ────────────────────────────────────────────
        if ($action === 'update_project') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $progress = (int)($input['progress'] ?? 0);
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE projects SET status = ?, progress = ?";
            $params = [$status, $progress];
            
            if ($status === 'completed') {
                $sql .= ", actual_end_date = CURDATE()";
            }
            if ($notes) {
                $sql .= ", notes = CONCAT(notes, ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE PROJECT ────────────────────────────────────────────
        if ($action === 'delete_project') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET TASKS ──────────────────────────────────────────────────
        if ($action === 'get_tasks') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT t.*, u.name as assigned_to_name, u2.name as assigned_by_name 
                    FROM project_tasks t
                    LEFT JOIN users u ON t.assigned_to = u.id
                    LEFT JOIN users u2 ON t.assigned_by = u2.id
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
            
            $sql .= " ORDER BY t.due_date ASC, t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;
        }
        
        // ── CREATE TASK ────────────────────────────────────────────────
        if ($action === 'create_task') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $task_name = trim($input['task_name'] ?? '');
            $description = trim($input['description'] ?? '');
            $assigned_to = (int)($input['assigned_to'] ?? 0);
            $priority = $input['priority'] ?? 'medium';
            $estimated_hours = (float)($input['estimated_hours'] ?? 0);
            $due_date = $input['due_date'] ?? null;
            
            if (!$project_id || empty($task_name)) {
                echo json_encode(['success' => false, 'error' => 'Project and task name are required']);
                exit;
            }
            
            $task_id = 'TASK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO project_tasks (
                    project_id, task_id, task_name, description, assigned_to, assigned_by,
                    priority, estimated_hours, due_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'todo', NOW())
            ");
            $stmt->execute([
                $project_id, $task_id, $task_name, $description, $assigned_to, $user_id,
                $priority, $estimated_hours, $due_date
            ]);
            
            echo json_encode(['success' => true, 'task_id' => $task_id]);
            exit;
        }
        
        // ── UPDATE TASK ────────────────────────────────────────────────
        if ($action === 'update_task') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            $actual_hours = (float)($input['actual_hours'] ?? 0);
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE project_tasks SET status = ?";
            $params = [$status];
            
            if ($status === 'done') {
                $sql .= ", completed_at = NOW()";
            }
            if ($actual_hours > 0) {
                $sql .= ", actual_hours = ?";
                $params[] = $actual_hours;
            }
            if ($notes) {
                $sql .= ", notes = CONCAT(notes, ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE TASK ────────────────────────────────────────────────
        if ($action === 'delete_task') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM project_tasks WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET MILESTONES ─────────────────────────────────────────────
        if ($action === 'get_milestones') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            
            $sql = "SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$project_id]);
            $milestones = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'milestones' => $milestones]);
            exit;
        }
        
        // ── CREATE MILESTONE ───────────────────────────────────────────
        if ($action === 'create_milestone') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $milestone_name = trim($input['milestone_name'] ?? '');
            $description = trim($input['description'] ?? '');
            $due_date = $input['due_date'] ?? null;
            
            if (!$project_id || empty($milestone_name) || !$due_date) {
                echo json_encode(['success' => false, 'error' => 'Project, milestone name and due date are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO project_milestones (project_id, milestone_name, description, due_date, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$project_id, $milestone_name, $description, $due_date]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE MILESTONE ───────────────────────────────────────────
        if ($action === 'update_milestone') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE project_milestones SET status = ?";
            $params = [$status];
            
            if ($status === 'completed') {
                $sql .= ", completed_date = CURDATE()";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PROJECT TEAM ───────────────────────────────────────────
        if ($action === 'get_team') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            
            $sql = "SELECT pt.*, u.name as user_name, u.role as user_role 
                    FROM project_team pt
                    LEFT JOIN users u ON pt.user_id = u.id
                    WHERE pt.project_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$project_id]);
            $team = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'team' => $team]);
            exit;
        }
        
        // ── ADD TEAM MEMBER ────────────────────────────────────────────
        if ($action === 'add_team_member') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $user_id = (int)($input['user_id'] ?? 0);
            $role = trim($input['role'] ?? '');
            
            if (!$project_id || !$user_id || empty($role)) {
                echo json_encode(['success' => false, 'error' => 'Project, user and role are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO project_team (project_id, user_id, role, assigned_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE role = ?, assigned_at = NOW()
            ");
            $stmt->execute([$project_id, $user_id, $role, $role]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── REMOVE TEAM MEMBER ─────────────────────────────────────────
        if ($action === 'remove_team_member') {
            $input = json_decode(file_get_contents('php://input'), true);
            $project_id = (int)($input['project_id'] ?? 0);
            $user_id = (int)($input['user_id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM project_team WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PROJECT COMMENTS ──────────────────────────────────────
        if ($action === 'get_comments') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            
            $sql = "SELECT c.*, u.name as user_name 
                    FROM project_comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.project_id = ? AND c.parent_id IS NULL
                    ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$project_id]);
            $comments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'comments' => $comments]);
            exit;
        }
        
        // ── ADD COMMENT ────────────────────────────────────────────────
        if ($action === 'add_comment') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $project_id = (int)($input['project_id'] ?? 0);
            $comment = trim($input['comment'] ?? '');
            $parent_id = (int)($input['parent_id'] ?? 0);
            
            if (!$project_id || empty($comment)) {
                echo json_encode(['success' => false, 'error' => 'Project and comment are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO project_comments (project_id, user_id, comment, parent_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$project_id, $user_id, $comment, $parent_id ?: null]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET CLIENTS ────────────────────────────────────────────────
        if ($action === 'get_clients') {
            $stmt = $pdo->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name");
            $clients = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'clients' => $clients]);
            exit;
        }
        
        // ── GET USERS ──────────────────────────────────────────────────
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
<title>Project Dashboard | CIBIL Repair</title>

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

/* TASK STATUS BADGES */
.status-todo { background: #e5e7eb; color: #4b5563; }
.status-in_progress { background: #dbeafe; color: #1e40af; }
.status-review { background: #fef3c7; color: #78350f; }
.status-done { background: #d1fae5; color: #065f46; }
.status-blocked { background: #fee2e2; color: #991b1b; }

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
        <div class="brand-icon">PM</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Project Management</div>
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
            <span class="nav-label">All Projects</span>
        </div>
        <div class="nav-item" data-section="createProject">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Project</span>
        </div>
        <div class="nav-section-label">Tasks</div>
        <div class="nav-item" data-section="tasks">
            <i class="fas fa-tasks"></i>
            <span class="nav-label">All Tasks</span>
        </div>
        <div class="nav-item" data-section="myTasks">
            <i class="fas fa-user-check"></i>
            <span class="nav-label">My Tasks</span>
        </div>
        <div class="nav-section-label">Team</div>
        <div class="nav-item" data-section="team">
            <i class="fas fa-users"></i>
            <span class="nav-label">Team Members</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
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
            <span class="page-title" id="pageTitle">Project Dashboard</span>
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
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="completedProjects">—</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="overdueTasks">—</div>
                    <div class="stat-label">Overdue Tasks</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Task Status</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-doughnut"></i> Project Status</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="projectStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Projects</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createProject')"><i class="fas fa-plus"></i> New Project</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Project ID</th><th>Name</th><th>Client</th><th>Manager</th><th>Progress</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="recentProjectsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ALL PROJECTS ====== -->
        <div class="section" id="projectsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-folder-open"></i> All Projects</div>
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
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Project ID</th><th>Name</th><th>Client</th><th>Manager</th><th>Progress</th><th>Budget</th><th>Status</th><th>Actions</th></tr></thead>
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
                    <div class="card-title"><i class="fas fa-plus-circle"></i> Create New Project</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('projects')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Project Name <span class="form-required">*</span></label>
                            <input class="form-input" id="projectName" placeholder="Enter project name">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Project Code <span class="form-required">*</span></label>
                            <input class="form-input" id="projectCode" placeholder="e.g., WEB-2024-001">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="projectDesc" rows="3" placeholder="Project description..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Client</label>
                            <select class="form-select" id="projectClient"></select>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Project Type</label>
                            <select class="form-select" id="projectType">
                                <option value="internal">Internal</option>
                                <option value="client">Client</option>
                                <option value="development">Development</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="support">Support</option>
                            </select>
                        </div>
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
                            <label class="form-label">Project Manager</label>
                            <select class="form-select" id="projectManager"></select>
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
                    <div class="form-group">
                        <label class="form-label">Budget (₹)</label>
                        <input type="number" class="form-input" id="projectBudget" placeholder="0">
                    </div>
                    <button class="btn btn-primary" onclick="createProject()"><i class="fas fa-save"></i> Create Project</button>
                </div>
            </div>
        </div>

        <!-- ====== ALL TASKS ====== -->
        <div class="section" id="tasksSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tasks"></i> All Tasks</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('createTaskModal')"><i class="fas fa-plus"></i> New Task</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="taskProjectFilter" onchange="loadAllTasks()" style="width:180px;padding:8px 12px;">
                        <option value="">All Projects</option>
                    </select>
                    <select class="form-select" id="taskStatusFilter" onchange="loadAllTasks()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="review">Review</option>
                        <option value="done">Done</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Task ID</th><th>Task Name</th><th>Project</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Due Date</th><th>Actions</th></tr></thead>
                        <tbody id="allTasksBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== MY TASKS ====== -->
        <div class="section" id="myTasksSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-user-check"></i> My Tasks</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Task ID</th><th>Task Name</th><th>Project</th><th>Priority</th><th>Status</th><th>Due Date</th><th>Actions</th></tr></thead>
                        <tbody id="myTasksBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TEAM ====== -->
        <div class="section" id="teamSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Project Team Members</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addTeamMemberModal')"><i class="fas fa-plus"></i> Add Member</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Project</th><th>Member</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                        <tbody id="teamBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Project Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Project Modal -->
<div class="modal-overlay" id="updateProjectModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Project</span>
            <button class="modal-close" onclick="closeModal('updateProjectModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateProjectId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateProjectStatus">
                    <option value="planning">Planning</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Progress (%)</label>
                <input type="number" class="form-input" id="updateProjectProgress" min="0" max="100" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateProjectNotes" rows="3" placeholder="Update notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateProjectModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateProject()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal-overlay" id="createTaskModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Create Task</span>
            <button class="modal-close" onclick="closeModal('createTaskModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Project <span class="form-required">*</span></label>
                <select class="form-select" id="taskProject"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Task Name <span class="form-required">*</span></label>
                <input class="form-input" id="taskName" placeholder="Enter task name">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" id="taskDesc" rows="2" placeholder="Task description..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Assigned To</label>
                    <select class="form-select" id="taskAssigned"></select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="taskPriority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Est. Hours</label>
                    <input type="number" class="form-input" id="taskHours" placeholder="0" step="0.5">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-input" id="taskDueDate">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createTaskModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createTask()"><i class="fas fa-save"></i> Create Task</button>
        </div>
    </div>
</div>

<!-- Update Task Modal -->
<div class="modal-overlay" id="updateTaskModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Task</span>
            <button class="modal-close" onclick="closeModal('updateTaskModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateTaskId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateTaskStatus">
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="review">Review</option>
                    <option value="done">Done</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Actual Hours</label>
                <input type="number" class="form-input" id="updateTaskHours" placeholder="0" step="0.5">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateTaskNotes" rows="3" placeholder="Update notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateTaskModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateTask()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Add Team Member Modal -->
<div class="modal-overlay" id="addTeamMemberModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Add Team Member</span>
            <button class="modal-close" onclick="closeModal('addTeamMemberModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Project <span class="form-required">*</span></label>
                <select class="form-select" id="teamProject"></select>
            </div>
            <div class="form-group">
                <label class="form-label">User <span class="form-required">*</span></label>
                <select class="form-select" id="teamUser"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Role <span class="form-required">*</span></label>
                <input class="form-input" id="teamRole" placeholder="e.g., Developer, Designer, Tester">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addTeamMemberModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addTeamMember()"><i class="fas fa-save"></i> Add Member</button>
        </div>
    </div>
</div>

<!-- View Project Modal -->
<div class="modal-overlay" id="viewProjectModal">
    <div class="modal" style="max-width:800px;">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-folder-open"></i> Project Details</span>
            <button class="modal-close" onclick="closeModal('viewProjectModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="projectDetailContent">
            <div class="empty-state"><div class="spinner"></div></div>
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
const USER_ID = <?= json_encode($user_id) ?>;
const USER_ROLE = <?= json_encode($user_role) ?>;

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('projectTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('projectTheme') || 'light'); })();

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
    dashboard: 'Project Dashboard',
    projects: 'All Projects',
    createProject: 'Create Project',
    tasks: 'All Tasks',
    myTasks: 'My Tasks',
    team: 'Team Members',
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
        tasks: loadAllTasks,
        myTasks: loadMyTasks,
        team: loadTeam,
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
function getProjectStatusBadge(status) {
    const map = {
        'planning': 'badge-gray',
        'active': 'badge-success',
        'on_hold': 'badge-warning',
        'completed': 'badge-brand',
        'cancelled': 'badge-danger'
    };
    const labels = {
        'planning': 'Planning',
        'active': 'Active',
        'on_hold': 'On Hold',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getTaskStatusBadge(status) {
    const map = {
        'todo': 'status-todo',
        'in_progress': 'status-in_progress',
        'review': 'status-review',
        'done': 'status-done',
        'blocked': 'status-blocked'
    };
    const labels = {
        'todo': 'To Do',
        'in_progress': 'In Progress',
        'review': 'Review',
        'done': 'Done',
        'blocked': 'Blocked'
    };
    const cls = map[status?.toLowerCase()] || 'status-todo';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getPriorityBadge(priority) {
    const map = {
        'low': 'badge-gray',
        'medium': 'badge-info',
        'high': 'badge-warning',
        'urgent': 'badge-danger',
        'critical': 'badge-danger'
    };
    const cls = map[priority?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${priority || 'medium'}</span>`;
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
    document.getElementById('completedProjects').textContent = data.completed_projects || 0;
    document.getElementById('overdueTasks').textContent = data.overdue_tasks || 0;

    // Task status chart
    if (data.task_status) {
        destroyChart('taskStatusChart');
        const ctx = document.getElementById('taskStatusChart').getContext('2d');
        const colors = ['#9ca3af', '#3b82f6', '#d97706', '#059669', '#dc2626'];
        charts.taskStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.task_status.labels || [],
                datasets: [{
                    data: data.task_status.values || [],
                    backgroundColor: colors.slice(0, data.task_status.labels?.length || 0),
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

    // Project status chart
    if (data.project_status) {
        destroyChart('projectStatusChart');
        const ctx = document.getElementById('projectStatusChart').getContext('2d');
        const colors = ['#9ca3af', '#059669', '#d97706', '#0d9e78', '#dc2626'];
        charts.projectStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.project_status.labels || [],
                datasets: [{
                    data: data.project_status.values || [],
                    backgroundColor: colors.slice(0, data.project_status.labels?.length || 0),
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

    // Recent projects
    const body = document.getElementById('recentProjectsBody');
    if (data.recent_projects && data.recent_projects.length) {
        body.innerHTML = data.recent_projects.map(p => `
            <tr>
                <td><span class="font-mono">${escHtml(p.project_id)}</span></td>
                <td><strong>${escHtml(p.project_name)}</strong></td>
                <td>${escHtml(p.client_name || '—')}</td>
                <td>${escHtml(p.manager_name || '—')}</td>
                <td>
                    <div class="progress-bar" style="height:6px;width:100px;display:inline-block;vertical-align:middle;background:var(--bg-sunken);border-radius:99px;overflow:hidden;">
                        <div class="progress-fill" style="width:${p.progress || 0}%;background:var(--brand);height:100%;border-radius:99px;"></div>
                    </div>
                    <span style="margin-left:8px;font-size:12px;">${p.progress || 0}%</span>
                </td>
                <td>${getProjectStatusBadge(p.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewProject(${p.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateProject(${p.id}, '${p.status}', ${p.progress || 0})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteProject(${p.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No projects found</p></div></td></tr>';
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
                <td>${escHtml(p.client_name || '—')}</td>
                <td>${escHtml(p.manager_name || '—')}</td>
                <td>
                    <div class="progress-bar" style="height:6px;width:80px;display:inline-block;vertical-align:middle;background:var(--bg-sunken);border-radius:99px;overflow:hidden;">
                        <div class="progress-fill" style="width:${p.progress || 0}%;background:var(--brand);height:100%;border-radius:99px;"></div>
                    </div>
                    <span style="margin-left:8px;font-size:12px;">${p.progress || 0}%</span>
                </td>
                <td>₹${(p.budget || 0).toLocaleString('en-IN')}</td>
                <td>${getProjectStatusBadge(p.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewProject(${p.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateProject(${p.id}, '${p.status}', ${p.progress || 0})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteProject(${p.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No projects found</p></div></td></tr>';
    }
}

function openUpdateProject(id, status, progress) {
    document.getElementById('updateProjectId').value = id;
    document.getElementById('updateProjectStatus').value = status || 'planning';
    document.getElementById('updateProjectProgress').value = progress || 0;
    document.getElementById('updateProjectNotes').value = '';
    openModal('updateProjectModal');
}

async function updateProject() {
    const id = document.getElementById('updateProjectId').value;
    const status = document.getElementById('updateProjectStatus').value;
    const progress = parseInt(document.getElementById('updateProjectProgress').value) || 0;
    const notes = document.getElementById('updateProjectNotes').value.trim();
    
    const result = await apiCall('update_project', 'POST', { id, status, progress, notes });
    if (result.success) {
        showToast('Project updated!', 'success');
        closeModal('updateProjectModal');
        loadDashboard();
        loadProjects();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function deleteProject(id) {
    if (!confirm('Delete this project?')) return;
    const result = await apiCall('delete_project', 'POST', { id });
    if (result.success) {
        showToast('Project deleted', 'success');
        loadDashboard();
        loadProjects();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

async function viewProject(id) {
    const content = document.getElementById('projectDetailContent');
    content.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
    openModal('viewProjectModal');
    
    // Get project details
    const data = await apiCall(`get_projects?search=&status=&id=${id}`);
    if (data.success && data.projects && data.projects.length) {
        const p = data.projects[0];
        content.innerHTML = `
            <div style="margin-bottom:16px;">
                <h3 style="font-size:18px;font-weight:700;">${escHtml(p.project_name)}</h3>
                <p style="color:var(--text-secondary);">${escHtml(p.description || 'No description')}</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div><strong>Project ID:</strong> ${escHtml(p.project_id)}</div>
                <div><strong>Status:</strong> ${getProjectStatusBadge(p.status)}</div>
                <div><strong>Client:</strong> ${escHtml(p.client_name || '—')}</div>
                <div><strong>Manager:</strong> ${escHtml(p.manager_name || '—')}</div>
                <div><strong>Progress:</strong> ${p.progress || 0}%</div>
                <div><strong>Budget:</strong> ₹${(p.budget || 0).toLocaleString('en-IN')}</div>
                <div><strong>Start:</strong> ${p.start_date || '—'}</div>
                <div><strong>End:</strong> ${p.end_date || '—'}</div>
            </div>
            <hr style="border-color:var(--border);margin:16px 0;">
            <h4 style="font-weight:700;margin-bottom:8px;">Tasks</h4>
            <div id="projectTasksList"><div class="spinner"></div></div>
        `;
        
        // Load tasks for this project
        const tasksData = await apiCall(`get_tasks?project_id=${id}`);
        const tasksList = document.getElementById('projectTasksList');
        if (tasksData.success && tasksData.tasks && tasksData.tasks.length) {
            tasksList.innerHTML = tasksData.tasks.map(t => `
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;">
                    <span>${escHtml(t.task_name)}</span>
                    <span>${getTaskStatusBadge(t.status)}</span>
                </div>
            `).join('');
        } else {
            tasksList.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">No tasks found</p>';
        }
    } else {
        content.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Project not found</p></div>';
    }
}

// ── CREATE PROJECT ───────────────────────────────────────────────────
async function createProject() {
    const project_name = document.getElementById('projectName').value.trim();
    const project_code = document.getElementById('projectCode').value.trim();
    const description = document.getElementById('projectDesc').value.trim();
    const client_id = document.getElementById('projectClient').value;
    const project_type = document.getElementById('projectType').value;
    const priority = document.getElementById('projectPriority').value;
    const project_manager = document.getElementById('projectManager').value;
    const start_date = document.getElementById('projectStart').value;
    const end_date = document.getElementById('projectEnd').value;
    const budget = parseFloat(document.getElementById('projectBudget').value) || 0;
    
    if (!project_name) { showToast('Project name is required', 'warning'); return; }
    if (!project_code) { showToast('Project code is required', 'warning'); return; }
    
    const result = await apiCall('create_project', 'POST', {
        project_name, project_code, description, client_id, project_type,
        priority, project_manager, start_date, end_date, budget
    });
    
    if (result.success) {
        showToast('Project created!', 'success');
        showSection('projects');
        document.getElementById('projectName').value = '';
        document.getElementById('projectCode').value = '';
        document.getElementById('projectDesc').value = '';
        document.getElementById('projectBudget').value = '';
        loadDashboard();
        loadProjects();
    } else {
        showToast(result.error || 'Failed to create project', 'error');
    }
}

// ── TASKS ─────────────────────────────────────────────────────────────
async function loadAllTasks() {
    const project_id = document.getElementById('taskProjectFilter')?.value || '';
    const status = document.getElementById('taskStatusFilter')?.value || '';
    
    const data = await apiCall(`get_tasks?project_id=${project_id}&status=${status}`);
    const body = document.getElementById('allTasksBody');
    
    if (data.success && data.tasks && data.tasks.length) {
        body.innerHTML = data.tasks.map(t => `
            <tr>
                <td><span class="font-mono">${escHtml(t.task_id)}</span></td>
                <td><strong>${escHtml(t.task_name)}</strong></td>
                <td>${escHtml(t.project_name || '—')}</td>
                <td>${escHtml(t.assigned_to_name || '—')}</td>
                <td>${getPriorityBadge(t.priority)}</td>
                <td>${getTaskStatusBadge(t.status)}</td>
                <td>${t.due_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateTask(${t.id}, '${t.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTask(${t.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-tasks"></i><p>No tasks found</p></div></td></tr>';
    }
}

async function loadMyTasks() {
    const data = await apiCall(`get_tasks?assigned_to=${USER_ID}`);
    const body = document.getElementById('myTasksBody');
    
    if (data.success && data.tasks && data.tasks.length) {
        body.innerHTML = data.tasks.map(t => `
            <tr>
                <td><span class="font-mono">${escHtml(t.task_id)}</span></td>
                <td><strong>${escHtml(t.task_name)}</strong></td>
                <td>${escHtml(t.project_name || '—')}</td>
                <td>${getPriorityBadge(t.priority)}</td>
                <td>${getTaskStatusBadge(t.status)}</td>
                <td>${t.due_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateTask(${t.id}, '${t.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-user-check"></i><p>No tasks assigned to you</p></div></td></tr>';
    }
}

function openUpdateTask(id, status) {
    document.getElementById('updateTaskId').value = id;
    document.getElementById('updateTaskStatus').value = status || 'todo';
    document.getElementById('updateTaskHours').value = '';
    document.getElementById('updateTaskNotes').value = '';
    openModal('updateTaskModal');
}

async function updateTask() {
    const id = document.getElementById('updateTaskId').value;
    const status = document.getElementById('updateTaskStatus').value;
    const actual_hours = parseFloat(document.getElementById('updateTaskHours').value) || 0;
    const notes = document.getElementById('updateTaskNotes').value.trim();
    
    const result = await apiCall('update_task', 'POST', { id, status, actual_hours, notes });
    if (result.success) {
        showToast('Task updated!', 'success');
        closeModal('updateTaskModal');
        loadAllTasks();
        loadMyTasks();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;
    const result = await apiCall('delete_task', 'POST', { id });
    if (result.success) {
        showToast('Task deleted', 'success');
        loadAllTasks();
        loadMyTasks();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

async function createTask() {
    const project_id = document.getElementById('taskProject').value;
    const task_name = document.getElementById('taskName').value.trim();
    const description = document.getElementById('taskDesc').value.trim();
    const assigned_to = document.getElementById('taskAssigned').value;
    const priority = document.getElementById('taskPriority').value;
    const estimated_hours = parseFloat(document.getElementById('taskHours').value) || 0;
    const due_date = document.getElementById('taskDueDate').value;
    
    if (!project_id) { showToast('Please select a project', 'warning'); return; }
    if (!task_name) { showToast('Task name is required', 'warning'); return; }
    
    const result = await apiCall('create_task', 'POST', {
        project_id, task_name, description, assigned_to, priority, estimated_hours, due_date
    });
    
    if (result.success) {
        showToast('Task created!', 'success');
        closeModal('createTaskModal');
        document.getElementById('taskName').value = '';
        document.getElementById('taskDesc').value = '';
        document.getElementById('taskHours').value = '';
        document.getElementById('taskDueDate').value = '';
        loadAllTasks();
        loadMyTasks();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create task', 'error');
    }
}

// ── TEAM ─────────────────────────────────────────────────────────────
async function loadTeam() {
    const data = await apiCall('get_projects');
    const body = document.getElementById('teamBody');
    
    if (data.success && data.projects) {
        let allTeam = [];
        for (const p of data.projects.slice(0, 5)) {
            const teamData = await apiCall(`get_team?project_id=${p.id}`);
            if (teamData.success && teamData.team) {
                allTeam = allTeam.concat(teamData.team.map(t => ({ ...t, project_name: p.project_name })));
            }
        }
        
        if (allTeam.length) {
            body.innerHTML = allTeam.map(t => `
                <tr>
                    <td><strong>${escHtml(t.project_name)}</strong></td>
                    <td>${escHtml(t.user_name || '—')}</td>
                    <td><span class="badge badge-info">${escHtml(t.role)}</span></td>
                    <td>${new Date(t.assigned_at).toLocaleDateString('en-IN')}</td>
                    <td>
                        <button class="btn btn-danger btn-xs" onclick="removeTeamMember(${t.project_id}, ${t.user_id})"><i class="fas fa-user-minus"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-users"></i><p>No team members found</p></div></td></tr>';
        }
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-users"></i><p>No team members found</p></div></td></tr>';
    }
}

async function addTeamMember() {
    const project_id = document.getElementById('teamProject').value;
    const user_id = document.getElementById('teamUser').value;
    const role = document.getElementById('teamRole').value.trim();
    
    if (!project_id) { showToast('Please select a project', 'warning'); return; }
    if (!user_id) { showToast('Please select a user', 'warning'); return; }
    if (!role) { showToast('Role is required', 'warning'); return; }
    
    const result = await apiCall('add_team_member', 'POST', { project_id, user_id, role });
    if (result.success) {
        showToast('Team member added!', 'success');
        closeModal('addTeamMemberModal');
        document.getElementById('teamRole').value = '';
        loadTeam();
    } else {
        showToast(result.error || 'Failed to add member', 'error');
    }
}

async function removeTeamMember(project_id, user_id) {
    if (!confirm('Remove this team member?')) return;
    const result = await apiCall('remove_team_member', 'POST', { project_id, user_id });
    if (result.success) {
        showToast('Team member removed', 'success');
        loadTeam();
    } else {
        showToast(result.error || 'Failed to remove', 'error');
    }
}

// ── ANALYTICS ────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    if (data.project_status) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const colors = ['#9ca3af', '#059669', '#d97706', '#0d9e78', '#dc2626'];
        charts.analyticsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.project_status.labels || [],
                datasets: [{
                    label: 'Projects',
                    data: data.project_status.values || [],
                    backgroundColor: colors.slice(0, data.project_status.labels?.length || 0),
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
    // Load clients
    const clientsData = await apiCall('get_clients');
    if (clientsData.success && clientsData.clients) {
        const clientSelect = document.getElementById('projectClient');
        if (clientSelect) {
            clientSelect.innerHTML = '<option value="">— Select Client —</option>' +
                clientsData.clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
        }
    }
    
    // Load users
    const usersData = await apiCall('get_users');
    if (usersData.success && usersData.users) {
        const selectIds = ['projectManager', 'taskAssigned', 'teamUser'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select User —</option>' +
                    usersData.users.map(u => `<option value="${u.id}">${escHtml(u.name)} (${escHtml(u.role)})</option>`).join('');
            }
        });
    }
    
    // Load projects for task dropdown
    const projectsData = await apiCall('get_projects');
    if (projectsData.success && projectsData.projects) {
        const selectIds = ['taskProject', 'taskProjectFilter', 'teamProject'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Project —</option>' +
                    projectsData.projects.map(p => `<option value="${p.id}">${escHtml(p.project_name)}</option>`).join('');
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportProjects() { showToast('Exporting projects...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'p') showSection('projects');
    if (e.altKey && e.key === 't') showSection('tasks');
    if (e.altKey && e.key === 'm') showSection('myTasks');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'createTaskModal' || modal.id === 'addTeamMemberModal') {
                loadDropdowns();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadDropdowns();

console.log('✅ Project Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>