<?php
// ============================================================
// IT DASHBOARD - FULLY INTEGRATED
// Access: it_team, admin, manager, super_admin
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

// ── AUTH: allow it_team, admin, manager, super_admin ──────────────
$allowed_roles = ['it_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'IT Administrator';
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
            // System components
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_status");
            $total_components = (int)($stmt->fetch()['total'] ?? 0);
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_status WHERE status = 'online'");
            $online_components = (int)($stmt->fetch()['total'] ?? 0);
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_status WHERE status IN ('offline', 'error')");
            $offline_components = (int)($stmt->fetch()['total'] ?? 0);
            
            // Security logs
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM security_logs WHERE resolved = FALSE AND severity IN ('warning', 'error', 'critical')");
            $unresolved_alerts = (int)($stmt->fetch()['total'] ?? 0);
            
            // Backup jobs
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM backup_jobs WHERE status = 'running'");
            $running_backups = (int)($stmt->fetch()['total'] ?? 0);
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM backup_jobs WHERE status = 'failed'");
            $failed_backups = (int)($stmt->fetch()['total'] ?? 0);
            
            // Maintenance
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM maintenance_schedule WHERE status IN ('scheduled', 'in_progress')");
            $pending_maintenance = (int)($stmt->fetch()['total'] ?? 0);
            
            // Component status distribution
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM system_status GROUP BY status");
            $status_data = ['online' => 0, 'offline' => 0, 'maintenance' => 0, 'warning' => 0, 'error' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($status_data[$row['status']])) {
                    $status_data[$row['status']] = (int)$row['count'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'total_components' => $total_components,
                'online_components' => $online_components,
                'offline_components' => $offline_components,
                'unresolved_alerts' => $unresolved_alerts,
                'running_backups' => $running_backups,
                'failed_backups' => $failed_backups,
                'pending_maintenance' => $pending_maintenance,
                'status_distribution' => [
                    'labels' => ['Online', 'Offline', 'Maintenance', 'Warning', 'Error'],
                    'values' => [$status_data['online'], $status_data['offline'], $status_data['maintenance'], $status_data['warning'], $status_data['error']]
                ]
            ]);
            exit;
        }
        
        // ── GET SYSTEM STATUS ─────────────────────────────────────────
        if ($action === 'get_system_status') {
            $stmt = $pdo->query("SELECT * FROM system_status ORDER BY component_name");
            $components = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'components' => $components]);
            exit;
        }
        
        // ── UPDATE SYSTEM STATUS ──────────────────────────────────────
        if ($action === 'update_system_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $details = trim($input['details'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE system_status SET status = ?, details = ?, last_checked = NOW() WHERE id = ?");
            $stmt->execute([$status, $details, $id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'System Status Updated', "Component ID $id status changed to $status"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SERVER METRICS ────────────────────────────────────────
        if ($action === 'get_server_metrics') {
            $stmt = $pdo->query("
                SELECT * FROM server_metrics 
                ORDER BY recorded_at DESC 
                LIMIT 50
            ");
            $metrics = $stmt->fetchAll();
            
            // Get latest metrics for dashboard
            $stmt = $pdo->query("
                SELECT 
                    AVG(cpu_usage) as avg_cpu,
                    AVG(memory_usage) as avg_memory,
                    AVG(disk_usage) as avg_disk,
                    MAX(recorded_at) as last_record
                FROM server_metrics 
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $latest = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'metrics' => $metrics,
                'avg_cpu' => round($latest['avg_cpu'] ?? 0, 2),
                'avg_memory' => round($latest['avg_memory'] ?? 0, 2),
                'avg_disk' => round($latest['avg_disk'] ?? 0, 2),
                'last_record' => $latest['last_record']
            ]);
            exit;
        }
        
        // ── ADD SERVER METRIC ─────────────────────────────────────────
        if ($action === 'add_server_metric') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $server_name = $input['server_name'] ?? 'main-server';
            $cpu_usage = (float)($input['cpu_usage'] ?? 0);
            $memory_usage = (float)($input['memory_usage'] ?? 0);
            $disk_usage = (float)($input['disk_usage'] ?? 0);
            $load_avg = (float)($input['load_avg'] ?? 0);
            
            $stmt = $pdo->prepare("
                INSERT INTO server_metrics (server_name, cpu_usage, memory_usage, disk_usage, load_avg, recorded_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$server_name, $cpu_usage, $memory_usage, $disk_usage, $load_avg]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SECURITY LOGS ──────────────────────────────────────────
        if ($action === 'get_security_logs') {
            $severity = $_GET['severity'] ?? '';
            $resolved = $_GET['resolved'] ?? '';
            
            $sql = "SELECT s.*, u.name as user_name 
                    FROM security_logs s
                    LEFT JOIN users u ON s.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($severity) {
                $sql .= " AND s.severity = ?";
                $params[] = $severity;
            }
            if ($resolved !== '') {
                $sql .= " AND s.resolved = ?";
                $params[] = (int)$resolved;
            }
            
            $sql .= " ORDER BY s.created_at DESC LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }
        
        // ── RESOLVE SECURITY LOG ──────────────────────────────────────
        if ($action === 'resolve_security_log') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE security_logs SET resolved = TRUE WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET BACKUP JOBS ────────────────────────────────────────────
        if ($action === 'get_backup_jobs') {
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT b.*, u.name as created_by_name 
                    FROM backup_jobs b
                    LEFT JOIN users u ON b.created_by = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY b.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $jobs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'jobs' => $jobs]);
            exit;
        }
        
        // ── CREATE BACKUP JOB ──────────────────────────────────────────
        if ($action === 'create_backup_job') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $job_name = trim($input['job_name'] ?? '');
            $backup_type = $input['backup_type'] ?? 'full';
            $schedule = trim($input['schedule'] ?? '');
            $notes = trim($input['notes'] ?? '');
            
            if (empty($job_name)) {
                echo json_encode(['success' => false, 'error' => 'Job name is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO backup_jobs (job_name, backup_type, status, schedule, notes, created_by, created_at)
                VALUES (?, ?, 'pending', ?, ?, ?, NOW())
            ");
            $stmt->execute([$job_name, $backup_type, $schedule, $notes, $user_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Backup Job Created', "Backup job: $job_name"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE BACKUP JOB STATUS ──────────────────────────────────
        if ($action === 'update_backup_job') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $file_path = trim($input['file_path'] ?? '');
            $file_size = (int)($input['file_size'] ?? 0);
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE backup_jobs SET status = ?";
            $params = [$status];
            
            if ($status === 'running') {
                $sql .= ", started_at = NOW()";
            }
            if ($status === 'completed') {
                $sql .= ", completed_at = NOW()";
            }
            if ($file_path) {
                $sql .= ", file_path = ?";
                $params[] = $file_path;
            }
            if ($file_size > 0) {
                $sql .= ", file_size = ?";
                $params[] = $file_size;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE BACKUP JOB ──────────────────────────────────────────
        if ($action === 'delete_backup_job') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            // Get file path
            $stmt = $pdo->prepare("SELECT file_path FROM backup_jobs WHERE id = ?");
            $stmt->execute([$id]);
            $job = $stmt->fetch();
            
            if ($job && $job['file_path'] && file_exists($job['file_path'])) {
                unlink($job['file_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM backup_jobs WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET AUDIT TRAIL ─────────────────────────────────────────────
        if ($action === 'get_audit_trail') {
            $user_id_filter = (int)($_GET['user_id'] ?? 0);
            $action_filter = $_GET['action'] ?? '';
            
            $sql = "SELECT a.*, u.name as user_name 
                    FROM audit_trail a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($user_id_filter > 0) {
                $sql .= " AND a.user_id = ?";
                $params[] = $user_id_filter;
            }
            if ($action_filter) {
                $sql .= " AND a.action LIKE ?";
                $params[] = "%$action_filter%";
            }
            
            $sql .= " ORDER BY a.created_at DESC LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $audit = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'audit' => $audit]);
            exit;
        }
        
        // ── GET MAINTENANCE SCHEDULE ──────────────────────────────────
        if ($action === 'get_maintenance') {
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT m.*, u1.name as assigned_to_name, u2.name as created_by_name 
                    FROM maintenance_schedule m
                    LEFT JOIN users u1 ON m.assigned_to = u1.id
                    LEFT JOIN users u2 ON m.created_by = u2.id
                    WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND m.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY m.scheduled_start ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $maintenance = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'maintenance' => $maintenance]);
            exit;
        }
        
        // ── CREATE MAINTENANCE ─────────────────────────────────────────
        if ($action === 'create_maintenance') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $component = trim($input['component'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $scheduled_start = $input['scheduled_start'] ?? null;
            $scheduled_end = $input['scheduled_end'] ?? null;
            $assigned_to = (int)($input['assigned_to'] ?? 0);
            
            if (empty($title) || !$scheduled_start || !$scheduled_end) {
                echo json_encode(['success' => false, 'error' => 'Title, start and end dates are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_schedule (
                    title, description, component, priority, status, scheduled_start, scheduled_end,
                    assigned_to, created_by, created_at
                ) VALUES (?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$title, $description, $component, $priority, $scheduled_start, $scheduled_end, $assigned_to, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE MAINTENANCE STATUS ──────────────────────────────────
        if ($action === 'update_maintenance') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE maintenance_schedule SET status = ?";
            $params = [$status];
            
            if ($status === 'in_progress') {
                $sql .= ", actual_start = NOW()";
            }
            if ($status === 'completed' || $status === 'cancelled') {
                $sql .= ", actual_end = NOW()";
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
        
        // ── GET API LOGS ───────────────────────────────────────────────
        if ($action === 'get_api_logs') {
            $stmt = $pdo->query("
                SELECT a.*, u.name as user_name 
                FROM api_logs a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT 100
            ");
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
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
<title>IT Dashboard | CIBIL Repair</title>

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
.stat-change { font-size: 12px; color: var(--text-muted); margin-top: 6px; }

/* METRIC GAUGES */
.metric-gauge {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
}
.metric-circle {
    position: relative;
    width: 80px;
    height: 80px;
}
.metric-circle svg {
    transform: rotate(-90deg);
}
.metric-circle .bg {
    fill: none;
    stroke: var(--bg-sunken);
    stroke-width: 8;
}
.metric-circle .progress {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.8s ease;
}
.metric-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 18px;
    font-weight: 800;
}
.metric-label {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 4px;
}

/* STATUS BADGES */
.status-online { background: #dcfce7; color: #166534; }
.status-offline { background: #fecaca; color: #991b1b; }
.status-maintenance { background: #fef3c7; color: #78350f; }
.status-warning { background: #ffedd5; color: #9a3412; }
.status-error { background: #fee2e2; color: #7f1d1d; }

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
    max-width: 650px;
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
        <div class="brand-icon">IT</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">IT Operations</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Infrastructure</div>
        <div class="nav-item" data-section="systemStatus">
            <i class="fas fa-server"></i>
            <span class="nav-label">System Status</span>
        </div>
        <div class="nav-item" data-section="metrics">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">Server Metrics</span>
        </div>
        <div class="nav-section-label">Security</div>
        <div class="nav-item" data-section="security">
            <i class="fas fa-shield-alt"></i>
            <span class="nav-label">Security Logs</span>
        </div>
        <div class="nav-item" data-section="audit">
            <i class="fas fa-clipboard-list"></i>
            <span class="nav-label">Audit Trail</span>
        </div>
        <div class="nav-section-label">Operations</div>
        <div class="nav-item" data-section="backups">
            <i class="fas fa-database"></i>
            <span class="nav-label">Backup Management</span>
        </div>
        <div class="nav-item" data-section="maintenance">
            <i class="fas fa-tools"></i>
            <span class="nav-label">Maintenance</span>
        </div>
        <div class="nav-section-label">API</div>
        <div class="nav-item" data-section="apiLogs">
            <i class="fas fa-code"></i>
            <span class="nav-label">API Logs</span>
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
            <span class="page-title" id="pageTitle">IT Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-server"></i></span>
                    <div class="stat-value" id="totalComponents">—</div>
                    <div class="stat-label">Total Components</div>
                    <div class="stat-change" id="onlineComponents">Online: —</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="offlineComponents">—</div>
                    <div class="stat-label">Offline / Error</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-bell"></i></span>
                    <div class="stat-value" id="unresolvedAlerts">—</div>
                    <div class="stat-label">Unresolved Alerts</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-database"></i></span>
                    <div class="stat-value" id="backupStatus">—</div>
                    <div class="stat-label">Running / Failed</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Component Status Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-server"></i> System Components</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Component</th><th>Status</th><th>Last Checked</th><th>Details</th><th>Actions</th></tr></thead>
                        <tbody id="componentsBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-shield-alt"></i> Recent Security Logs</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('security')"><i class="fas fa-eye"></i> View All</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Event</th><th>Severity</th><th>User</th><th>IP Address</th><th>Status</th><th>Time</th></tr></thead>
                        <tbody id="securityBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SYSTEM STATUS ====== -->
        <div class="section" id="systemStatusSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-server"></i> System Components</div>
                    <button class="btn btn-primary btn-sm" onclick="loadSystemStatus()"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Component</th><th>Status</th><th>Last Checked</th><th>Details</th><th>Actions</th></tr></thead>
                        <tbody id="systemStatusBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SERVER METRICS ====== -->
        <div class="section" id="metricsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Server Metrics</div>
                    <button class="btn btn-primary btn-sm" onclick="refreshMetrics()"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
                        <div class="stat-card green"><div class="stat-value" id="avgCpu">—%</div><div class="stat-label">Avg CPU Usage</div></div>
                        <div class="stat-card blue"><div class="stat-value" id="avgMemory">—%</div><div class="stat-label">Avg Memory Usage</div></div>
                        <div class="stat-card amber"><div class="stat-value" id="avgDisk">—%</div><div class="stat-label">Avg Disk Usage</div></div>
                    </div>
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="metricsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== SECURITY LOGS ====== -->
        <div class="section" id="securitySection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-shield-alt"></i> Security Logs</div>
                    <button class="btn btn-success btn-sm" onclick="exportSecurityLogs()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="securitySeverity" onchange="loadSecurityLogs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Severity</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="critical">Critical</option>
                    </select>
                    <select class="form-select" id="securityResolved" onchange="loadSecurityLogs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="0">Unresolved</option>
                        <option value="1">Resolved</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Event</th><th>Severity</th><th>User</th><th>IP Address</th><th>Details</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
                        <tbody id="securityLogsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== AUDIT TRAIL ====== -->
        <div class="section" id="auditSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clipboard-list"></i> Audit Trail</div>
                    <button class="btn btn-success btn-sm" onclick="exportAudit()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="auditSearch" placeholder="Search actions…" oninput="debounce(loadAuditTrail, 400)()">
                    </div>
                    <select class="form-select" id="auditUser" onchange="loadAuditTrail()" style="width:150px;padding:8px 12px;">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>User</th><th>Action</th><th>Resource</th><th>IP Address</th><th>Changes</th><th>Time</th></tr></thead>
                        <tbody id="auditBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== BACKUPS ====== -->
        <div class="section" id="backupsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-database"></i> Backup Management</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('createBackupModal')"><i class="fas fa-plus"></i> Create Backup</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="backupStatusFilter" onchange="loadBackupJobs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Job Name</th><th>Type</th><th>Status</th><th>Size</th><th>Schedule</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody id="backupBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== MAINTENANCE ====== -->
        <div class="section" id="maintenanceSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tools"></i> Maintenance Schedule</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('createMaintenanceModal')"><i class="fas fa-plus"></i> Schedule Maintenance</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="maintenanceStatusFilter" onchange="loadMaintenance()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Component</th><th>Priority</th><th>Status</th><th>Start</th><th>End</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody id="maintenanceBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== API LOGS ====== -->
        <div class="section" id="apiLogsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-code"></i> API Logs</div>
                    <button class="btn btn-success btn-sm" onclick="exportApiLogs()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Endpoint</th><th>Method</th><th>User</th><th>Status</th><th>Response Time</th><th>IP Address</th><th>Time</th></tr></thead>
                        <tbody id="apiLogsBody">
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
                    <div class="card-title"><i class="fas fa-chart-bar"></i> IT Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Component Status Modal -->
<div class="modal-overlay" id="updateComponentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Component</span>
            <button class="modal-close" onclick="closeModal('updateComponentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="componentId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="componentStatus">
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Details</label>
                <textarea class="form-textarea" id="componentDetails" rows="3" placeholder="Additional details..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateComponentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateComponent()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal-overlay" id="createBackupModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-database"></i> Create Backup Job</span>
            <button class="modal-close" onclick="closeModal('createBackupModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Job Name <span class="form-required">*</span></label>
                <input class="form-input" id="backupJobName" placeholder="e.g., Daily Database Backup">
            </div>
            <div class="form-group">
                <label class="form-label">Backup Type</label>
                <select class="form-select" id="backupType">
                    <option value="full">Full</option>
                    <option value="incremental">Incremental</option>
                    <option value="differential">Differential</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Schedule</label>
                <input class="form-input" id="backupSchedule" placeholder="e.g., Daily at 2:00 AM">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="backupNotes" rows="3" placeholder="Additional notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createBackupModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createBackupJob()"><i class="fas fa-save"></i> Create</button>
        </div>
    </div>
</div>

<!-- Create Maintenance Modal -->
<div class="modal-overlay" id="createMaintenanceModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-tools"></i> Schedule Maintenance</span>
            <button class="modal-close" onclick="closeModal('createMaintenanceModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Title <span class="form-required">*</span></label>
                <input class="form-input" id="maintenanceTitle" placeholder="e.g., Server Upgrade">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" id="maintenanceDesc" rows="3" placeholder="Description..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Component</label>
                    <input class="form-input" id="maintenanceComponent" placeholder="e.g., Database Server">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="maintenancePriority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Start Time <span class="form-required">*</span></label>
                    <input type="datetime-local" class="form-input" id="maintenanceStart">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">End Time <span class="form-required">*</span></label>
                    <input type="datetime-local" class="form-input" id="maintenanceEnd">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Assigned To</label>
                <select class="form-select" id="maintenanceAssigned"></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createMaintenanceModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createMaintenance()"><i class="fas fa-save"></i> Schedule</button>
        </div>
    </div>
</div>

<!-- Update Maintenance Status Modal -->
<div class="modal-overlay" id="updateMaintenanceModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Maintenance</span>
            <button class="modal-close" onclick="closeModal('updateMaintenanceModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="maintenanceUpdateId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="maintenanceUpdateStatus">
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="maintenanceUpdateNotes" rows="3" placeholder="Update notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateMaintenanceModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateMaintenanceStatus()"><i class="fas fa-save"></i> Update</button>
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
    localStorage.setItem('itTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('itTheme') || 'light'); })();

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
    dashboard: 'IT Dashboard',
    systemStatus: 'System Status',
    metrics: 'Server Metrics',
    security: 'Security Logs',
    audit: 'Audit Trail',
    backups: 'Backup Management',
    maintenance: 'Maintenance Schedule',
    apiLogs: 'API Logs',
    analytics: 'IT Analytics'
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
        systemStatus: loadSystemStatus,
        metrics: loadMetrics,
        security: loadSecurityLogs,
        audit: loadAuditTrail,
        backups: loadBackupJobs,
        maintenance: loadMaintenance,
        apiLogs: loadApiLogs,
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
        'online': 'status-online',
        'offline': 'status-offline',
        'maintenance': 'status-maintenance',
        'warning': 'status-warning',
        'error': 'status-error'
    };
    const labels = {
        'online': '🟢 Online',
        'offline': '🔴 Offline',
        'maintenance': '🟡 Maintenance',
        'warning': '🟠 Warning',
        'error': '🔴 Error'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getSeverityBadge(severity) {
    const map = {
        'info': 'badge-info',
        'warning': 'badge-warning',
        'error': 'badge-danger',
        'critical': 'status-error'
    };
    const cls = map[severity?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${severity || 'info'}</span>`;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

    document.getElementById('totalComponents').textContent = data.total_components || 0;
    document.getElementById('onlineComponents').textContent = `Online: ${data.online_components || 0}`;
    document.getElementById('offlineComponents').textContent = data.offline_components || 0;
    document.getElementById('unresolvedAlerts').textContent = data.unresolved_alerts || 0;
    document.getElementById('backupStatus').textContent = `${data.running_backups || 0} running / ${data.failed_backups || 0} failed`;

    // Status distribution chart
    if (data.status_distribution) {
        destroyChart('statusChart');
        const ctx = document.getElementById('statusChart').getContext('2d');
        const colors = ['#059669', '#dc2626', '#d97706', '#f97316', '#dc2626'];
        charts.statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.status_distribution.labels || [],
                datasets: [{
                    data: data.status_distribution.values || [],
                    backgroundColor: colors.slice(0, data.status_distribution.labels?.length || 0),
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

    // System components
    await loadSystemComponents();

    // Recent security logs
    await loadRecentSecurityLogs();
}

async function loadSystemComponents() {
    const data = await apiCall('get_system_status');
    const body = document.getElementById('componentsBody') || document.getElementById('systemStatusBody');
    
    if (data.success && data.components && data.components.length) {
        body.innerHTML = data.components.map(c => `
            <tr>
                <td><strong>${escHtml(c.component_name)}</strong></td>
                <td>${getStatusBadge(c.status)}</td>
                <td>${new Date(c.last_checked).toLocaleString('en-IN')}</td>
                <td>${escHtml(c.details || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateComponent(${c.id}, '${c.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-server"></i><p>No components found</p></div></td></tr>';
    }
}

function openUpdateComponent(id, status) {
    document.getElementById('componentId').value = id;
    document.getElementById('componentStatus').value = status || 'online';
    document.getElementById('componentDetails').value = '';
    openModal('updateComponentModal');
}

async function updateComponent() {
    const id = document.getElementById('componentId').value;
    const status = document.getElementById('componentStatus').value;
    const details = document.getElementById('componentDetails').value.trim();
    
    const result = await apiCall('update_system_status', 'POST', { id, status, details });
    if (result.success) {
        showToast('Component updated!', 'success');
        closeModal('updateComponentModal');
        loadDashboard();
        loadSystemStatus();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function loadRecentSecurityLogs() {
    const data = await apiCall('get_security_logs?severity=warning,error,critical');
    const body = document.getElementById('securityBody');
    
    if (data.success && data.logs && data.logs.length) {
        body.innerHTML = data.logs.slice(0, 5).map(l => `
            <tr>
                <td>${escHtml(l.event_type)}</td>
                <td>${getSeverityBadge(l.severity)}</td>
                <td>${escHtml(l.user_name || '—')}</td>
                <td>${escHtml(l.ip_address || '—')}</td>
                <td>${l.resolved ? '✅ Resolved' : '⚠️ Pending'}</td>
                <td>${new Date(l.created_at).toLocaleString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-shield-alt"></i><p>No security logs found</p></div></td></tr>';
    }
}

// ── SYSTEM STATUS ────────────────────────────────────────────────────
async function loadSystemStatus() {
    await loadSystemComponents();
}

// ── SERVER METRICS ──────────────────────────────────────────────────
async function loadMetrics() {
    const data = await apiCall('get_server_metrics');
    if (!data.success) { showToast('Failed to load metrics', 'error'); return; }

    document.getElementById('avgCpu').textContent = (data.avg_cpu || 0) + '%';
    document.getElementById('avgMemory').textContent = (data.avg_memory || 0) + '%';
    document.getElementById('avgDisk').textContent = (data.avg_disk || 0) + '%';

    if (data.metrics && data.metrics.length) {
        destroyChart('metricsChart');
        const ctx = document.getElementById('metricsChart').getContext('2d');
        const labels = data.metrics.slice(0, 20).map(m => new Date(m.recorded_at).toLocaleTimeString());
        const cpu = data.metrics.slice(0, 20).map(m => m.cpu_usage);
        const memory = data.metrics.slice(0, 20).map(m => m.memory_usage);
        const disk = data.metrics.slice(0, 20).map(m => m.disk_usage);
        
        charts.metricsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'CPU Usage (%)',
                        data: cpu,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2
                    },
                    {
                        label: 'Memory Usage (%)',
                        data: memory,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2
                    },
                    {
                        label: 'Disk Usage (%)',
                        data: disk,
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217,119,6,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: {
                    x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true, max: 100 } }
                }
            }
        });
    }
}

function refreshMetrics() { loadMetrics(); }

// ── SECURITY LOGS ──────────────────────────────────────────────────
async function loadSecurityLogs() {
    const severity = document.getElementById('securitySeverity')?.value || '';
    const resolved = document.getElementById('securityResolved')?.value || '';
    
    const data = await apiCall(`get_security_logs?severity=${severity}&resolved=${resolved}`);
    const body = document.getElementById('securityLogsBody');
    
    if (data.success && data.logs && data.logs.length) {
        body.innerHTML = data.logs.map(l => `
            <tr>
                <td>${escHtml(l.event_type)}</td>
                <td>${getSeverityBadge(l.severity)}</td>
                <td>${escHtml(l.user_name || '—')}</td>
                <td>${escHtml(l.ip_address || '—')}</td>
                <td>${escHtml(l.details || '—')}</td>
                <td>${l.resolved ? '✅ Resolved' : '<span class="badge badge-warning">Pending</span>'}</td>
                <td>${new Date(l.created_at).toLocaleString('en-IN')}</td>
                <td>
                    ${!l.resolved ? `<button class="btn btn-success btn-xs" onclick="resolveLog(${l.id})"><i class="fas fa-check"></i></button>` : ''}
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-shield-alt"></i><p>No security logs found</p></div></td></tr>';
    }
}

async function resolveLog(id) {
    const result = await apiCall('resolve_security_log', 'POST', { id });
    if (result.success) {
        showToast('Log resolved!', 'success');
        loadSecurityLogs();
        loadDashboard();
    }
}

// ── AUDIT TRAIL ──────────────────────────────────────────────────────
async function loadAuditTrail() {
    const search = document.getElementById('auditSearch')?.value || '';
    const user_id = document.getElementById('auditUser')?.value || '';
    
    const data = await apiCall(`get_audit_trail?user_id=${user_id}&action=${encodeURIComponent(search)}`);
    const body = document.getElementById('auditBody');
    
    if (data.success && data.audit && data.audit.length) {
        body.innerHTML = data.audit.map(a => `
            <tr>
                <td>${escHtml(a.user_name || '—')}</td>
                <td><span class="badge badge-info">${escHtml(a.action)}</span></td>
                <td>${escHtml(a.resource_type || '—')}</td>
                <td>${escHtml(a.ip_address || '—')}</td>
                <td>${a.changes ? JSON.stringify(a.changes) : '—'}</td>
                <td>${new Date(a.created_at).toLocaleString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No audit records found</p></div></td></tr>';
    }
}

// ── BACKUP JOBS ──────────────────────────────────────────────────────
async function loadBackupJobs() {
    const status = document.getElementById('backupStatusFilter')?.value || '';
    
    const data = await apiCall(`get_backup_jobs?status=${status}`);
    const body = document.getElementById('backupBody');
    
    if (data.success && data.jobs && data.jobs.length) {
        body.innerHTML = data.jobs.map(j => `
            <tr>
                <td><strong>${escHtml(j.job_name)}</strong></td>
                <td><span class="badge badge-brand">${escHtml(j.backup_type)}</span></td>
                <td>${getStatusBadge(j.status)}</td>
                <td>${j.file_size ? formatFileSize(j.file_size) : '—'}</td>
                <td>${escHtml(j.schedule || '—')}</td>
                <td>${new Date(j.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="showToast('View details', 'info')"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteBackupJob(${j.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-database"></i><p>No backup jobs found</p></div></td></tr>';
    }
}

async function createBackupJob() {
    const job_name = document.getElementById('backupJobName').value.trim();
    const backup_type = document.getElementById('backupType').value;
    const schedule = document.getElementById('backupSchedule').value.trim();
    const notes = document.getElementById('backupNotes').value.trim();
    
    if (!job_name) { showToast('Job name is required', 'warning'); return; }
    
    const result = await apiCall('create_backup_job', 'POST', { job_name, backup_type, schedule, notes });
    if (result.success) {
        showToast('Backup job created!', 'success');
        closeModal('createBackupModal');
        document.getElementById('backupJobName').value = '';
        document.getElementById('backupSchedule').value = '';
        document.getElementById('backupNotes').value = '';
        loadBackupJobs();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create job', 'error');
    }
}

async function deleteBackupJob(id) {
    if (!confirm('Delete this backup job?')) return;
    const result = await apiCall('delete_backup_job', 'POST', { id });
    if (result.success) {
        showToast('Backup job deleted', 'success');
        loadBackupJobs();
    }
}

// ── MAINTENANCE ──────────────────────────────────────────────────────
async function loadMaintenance() {
    const status = document.getElementById('maintenanceStatusFilter')?.value || '';
    
    const data = await apiCall(`get_maintenance?status=${status}`);
    const body = document.getElementById('maintenanceBody');
    
    if (data.success && data.maintenance && data.maintenance.length) {
        body.innerHTML = data.maintenance.map(m => `
            <tr>
                <td><strong>${escHtml(m.title)}</strong></td>
                <td>${escHtml(m.component || '—')}</td>
                <td>${getSeverityBadge(m.priority)}</td>
                <td>${getStatusBadge(m.status)}</td>
                <td>${new Date(m.scheduled_start).toLocaleString('en-IN')}</td>
                <td>${new Date(m.scheduled_end).toLocaleString('en-IN')}</td>
                <td>${escHtml(m.assigned_to_name || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateMaintenance(${m.id}, '${m.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-tools"></i><p>No maintenance records found</p></div></td></tr>';
    }
}

function openUpdateMaintenance(id, status) {
    document.getElementById('maintenanceUpdateId').value = id;
    document.getElementById('maintenanceUpdateStatus').value = status || 'scheduled';
    document.getElementById('maintenanceUpdateNotes').value = '';
    openModal('updateMaintenanceModal');
}

async function updateMaintenanceStatus() {
    const id = document.getElementById('maintenanceUpdateId').value;
    const status = document.getElementById('maintenanceUpdateStatus').value;
    const notes = document.getElementById('maintenanceUpdateNotes').value.trim();
    
    const result = await apiCall('update_maintenance', 'POST', { id, status, notes });
    if (result.success) {
        showToast('Maintenance updated!', 'success');
        closeModal('updateMaintenanceModal');
        loadMaintenance();
        loadDashboard();
    }
}

async function createMaintenance() {
    const title = document.getElementById('maintenanceTitle').value.trim();
    const description = document.getElementById('maintenanceDesc').value.trim();
    const component = document.getElementById('maintenanceComponent').value.trim();
    const priority = document.getElementById('maintenancePriority').value;
    const scheduled_start = document.getElementById('maintenanceStart').value;
    const scheduled_end = document.getElementById('maintenanceEnd').value;
    const assigned_to = document.getElementById('maintenanceAssigned').value;
    
    if (!title || !scheduled_start || !scheduled_end) {
        showToast('Title, start and end dates are required', 'warning');
        return;
    }
    
    const result = await apiCall('create_maintenance', 'POST', {
        title, description, component, priority, scheduled_start, scheduled_end, assigned_to
    });
    if (result.success) {
        showToast('Maintenance scheduled!', 'success');
        closeModal('createMaintenanceModal');
        document.getElementById('maintenanceTitle').value = '';
        document.getElementById('maintenanceDesc').value = '';
        document.getElementById('maintenanceComponent').value = '';
        loadMaintenance();
    } else {
        showToast(result.error || 'Failed to schedule', 'error');
    }
}

// ── API LOGS ─────────────────────────────────────────────────────────
async function loadApiLogs() {
    const data = await apiCall('get_api_logs');
    const body = document.getElementById('apiLogsBody');
    
    if (data.success && data.logs && data.logs.length) {
        body.innerHTML = data.logs.map(l => `
            <tr>
                <td><span class="font-mono">${escHtml(l.endpoint)}</span></td>
                <td><span class="badge badge-brand">${escHtml(l.method)}</span></td>
                <td>${escHtml(l.user_name || '—')}</td>
                <td>${l.response_status ? `<span class="badge ${l.response_status >= 400 ? 'badge-danger' : 'badge-success'}">${l.response_status}</span>` : '—'}</td>
                <td>${l.response_time || 0}ms</td>
                <td>${escHtml(l.ip_address || '—')}</td>
                <td>${new Date(l.created_at).toLocaleString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-code"></i><p>No API logs found</p></div></td></tr>';
    }
}

// ── ANALYTICS ────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    if (data.status_distribution) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const colors = ['#059669', '#dc2626', '#d97706', '#f97316', '#dc2626'];
        charts.analyticsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.status_distribution.labels || [],
                datasets: [{
                    label: 'Components',
                    data: data.status_distribution.values || [],
                    backgroundColor: colors.slice(0, data.status_distribution.labels?.length || 0),
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

// ── LOAD USERS ──────────────────────────────────────────────────────
async function loadUsers() {
    const data = await apiCall('get_users');
    if (data.success && data.users) {
        const select = document.getElementById('maintenanceAssigned');
        if (select) {
            select.innerHTML = '<option value="">— Unassigned —</option>' +
                data.users.map(u => `<option value="${u.id}">${escHtml(u.name)} (${escHtml(u.role)})</option>`).join('');
        }
        const auditSelect = document.getElementById('auditUser');
        if (auditSelect) {
            auditSelect.innerHTML = '<option value="">All Users</option>' +
                data.users.map(u => `<option value="${u.id}">${escHtml(u.name)}</option>`).join('');
        }
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportSecurityLogs() { showToast('Exporting security logs...', 'info'); }
function exportAudit() { showToast('Exporting audit trail...', 'info'); }
function exportApiLogs() { showToast('Exporting API logs...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 's') showSection('systemStatus');
    if (e.altKey && e.key === 'm') showSection('metrics');
    if (e.altKey && e.key === 'b') showSection('backups');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'createMaintenanceModal') {
                loadUsers();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadUsers();

console.log('✅ IT Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>