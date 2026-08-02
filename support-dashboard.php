<?php
// ============================================================
// SUPPORT DASHBOARD - FULLY INTEGRATED
// Access: partner, admin, manager, super_admin
// Purpose: Support ticket management for partners and admins
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

// ── AUTH: allow partner, admin, manager, super_admin ──────────────────
$allowed_roles = ['partner', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$is_partner = ($user_role === 'partner');
$is_manager = ($user_role === 'manager');
$csrf = $_SESSION['csrf_token'];

// ── Get partner ID if partner ────────────────────────────────────────
$partner_id = 0;
if ($is_partner) {
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $partner = $stmt->fetch();
    $partner_id = $partner['id'] ?? 0;
}

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
        // ── GET TICKETS ──────────────────────────────────────────────
        if ($action === 'get_tickets') {
            $viewing_partner_id = (int)($_GET['partner_id'] ?? $partner_id);
            
            if ($is_admin || $is_manager) {
                // Admin can view all tickets
                $stmt = $pdo->query("
                    SELECT t.*, 
                           CONCAT(u.name, ' (', u.role, ')') as created_by_name,
                           p.name as partner_name
                    FROM support_tickets t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN partners p ON t.partner_id = p.id
                    ORDER BY t.created_at DESC
                ");
                $tickets = $stmt->fetchAll();
            } else if ($is_partner && $viewing_partner_id) {
                // Partner can view only their tickets
                $stmt = $pdo->prepare("
                    SELECT t.*, 
                           CONCAT(u.name, ' (', u.role, ')') as created_by_name,
                           p.name as partner_name
                    FROM support_tickets t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN partners p ON t.partner_id = p.id
                    WHERE t.partner_id = ? OR t.created_by = ?
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute([$viewing_partner_id, $user_id]);
                $tickets = $stmt->fetchAll();
            } else {
                $tickets = [];
            }
            
            echo json_encode(['success' => true, 'tickets' => $tickets]);
            exit;
        }
        
        // ── GET TICKET DETAIL ────────────────────────────────────────
        if ($action === 'get_ticket_detail') {
            $ticket_id = (int)($_GET['ticket_id'] ?? 0);
            
            if ($is_admin || $is_manager) {
                $stmt = $pdo->prepare("
                    SELECT t.*, 
                           CONCAT(u.name, ' (', u.role, ')') as created_by_name,
                           p.name as partner_name
                    FROM support_tickets t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN partners p ON t.partner_id = p.id
                    WHERE t.id = ?
                ");
                $stmt->execute([$ticket_id]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT t.*, 
                           CONCAT(u.name, ' (', u.role, ')') as created_by_name,
                           p.name as partner_name
                    FROM support_tickets t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN partners p ON t.partner_id = p.id
                    WHERE t.id = ? AND (t.partner_id = ? OR t.created_by = ?)
                ");
                $stmt->execute([$ticket_id, $partner_id, $user_id]);
            }
            $ticket = $stmt->fetch();
            
            if ($ticket) {
                echo json_encode(['success' => true, 'ticket' => $ticket]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Ticket not found or unauthorized']);
            }
            exit;
        }
        
        // ── CREATE TICKET ────────────────────────────────────────────
        if ($action === 'create_ticket') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $partner_id_val = (int)($input['partner_id'] ?? $partner_id);
            $subject = trim($input['subject'] ?? '');
            $message = trim($input['message'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            
            if (empty($subject) || empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
                exit;
            }
            
            // Generate ticket number
            $ticket_no = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets (ticket_no, partner_id, created_by, subject, message, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            $stmt->execute([$ticket_no, $partner_id_val, $user_id, $subject, $message, $priority]);
            
            $ticket_id = $pdo->lastInsertId();
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Support Ticket Created', "Ticket #$ticket_no: $subject"]);
            
            echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'ticket_no' => $ticket_no]);
            exit;
        }
        
        // ── UPDATE TICKET STATUS ────────────────────────────────────
        if ($action === 'update_ticket_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $ticket_id = (int)($input['ticket_id'] ?? 0);
            $status = $input['status'] ?? '';
            $admin_reply = trim($input['admin_reply'] ?? '');
            
            if (!$is_admin && !$is_manager) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            if (empty($status)) {
                echo json_encode(['success' => false, 'error' => 'Status is required']);
                exit;
            }
            
            if ($status === 'resolved' && empty($admin_reply)) {
                // Allow resolution without reply, but warn
            }
            
            $sql = "UPDATE support_tickets SET status = ?, updated_at = NOW()";
            $params = [$status];
            
            if (!empty($admin_reply)) {
                $sql .= ", admin_reply = ?";
                $params[] = $admin_reply;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $ticket_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Support Ticket Updated', "Ticket ID $ticket_id status changed to $status"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── ADD ADMIN REPLY ──────────────────────────────────────────
        if ($action === 'add_admin_reply') {
            $input = json_decode(file_get_contents('php://input'), true);
            $ticket_id = (int)($input['ticket_id'] ?? 0);
            $admin_reply = trim($input['admin_reply'] ?? '');
            
            if (!$is_admin && !$is_manager) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            if (empty($admin_reply)) {
                echo json_encode(['success' => false, 'error' => 'Reply message is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE support_tickets 
                SET admin_reply = CONCAT(admin_reply, ?), updated_at = NOW(), status = 'in_progress'
                WHERE id = ?
            ");
            $stmt->execute(["\n---\n" . date('Y-m-d H:i') . ":\n" . $admin_reply, $ticket_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Support Ticket Replied', "Admin replied to ticket ID $ticket_id"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET TICKET STATS ─────────────────────────────────────────
        if ($action === 'get_ticket_stats') {
            if ($is_admin || $is_manager) {
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as open,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                        SUM(CASE WHEN priority = 'high' OR priority = 'urgent' THEN 1 ELSE 0 END) as urgent
                    FROM support_tickets
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as open,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                        SUM(CASE WHEN priority = 'high' OR priority = 'urgent' THEN 1 ELSE 0 END) as urgent
                    FROM support_tickets
                    WHERE partner_id = ? OR created_by = ?
                ");
                $stmt->execute([$partner_id, $user_id]);
            }
            $stats = $stmt->fetch();
            
            echo json_encode(['success' => true, 'stats' => $stats]);
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
<title>Support Dashboard | CIBIL Repair</title>

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

/* PRIORITY BADGES */
.priority-urgent { background: #fecaca; color: #991b1b; }
.priority-high { background: #fee2e2; color: #b91c1c; }
.priority-medium { background: #fef3c7; color: #b45309; }
.priority-low { background: #dcfce7; color: #166534; }

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
        <div class="brand-icon">SD</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Support Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Tickets</div>
        <div class="nav-item" data-section="tickets">
            <i class="fas fa-ticket-alt"></i>
            <span class="nav-label">My Tickets</span>
        </div>
        <div class="nav-item" data-section="newTicket">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Ticket</span>
        </div>
        <?php if ($is_admin || $is_manager): ?>
        <div class="nav-section-label">Admin</div>
        <div class="nav-item" data-section="adminTickets">
            <i class="fas fa-users-cog"></i>
            <span class="nav-label">All Tickets</span>
        </div>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="window.location.href='<?= $is_partner ? 'partner-dashboard.php' : 'admin-dashboard.php' ?>'">
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
            <span class="page-title" id="pageTitle">Support Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-ticket-alt"></i></span>
                    <div class="stat-value" id="totalTickets">—</div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="openTickets">—</div>
                    <div class="stat-label">Open Tickets</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="resolvedTickets">—</div>
                    <div class="stat-label">Resolved Tickets</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="urgentTickets">—</div>
                    <div class="stat-label">Urgent</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Tickets</div>
                    <button class="btn btn-primary btn-sm" onclick="showNewTicket()"><i class="fas fa-plus"></i> New Ticket</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="recentTicketsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== MY TICKETS ====== -->
        <div class="section" id="ticketsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-ticket-alt"></i> My Support Tickets</div>
                    <button class="btn btn-primary btn-sm" onclick="showNewTicket()"><i class="fas fa-plus"></i> New Ticket</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Updated</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="allTicketsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== NEW TICKET ====== -->
        <div class="section" id="newTicketSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-plus-circle"></i> Create New Ticket</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('dashboard')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Subject <span class="form-required">*</span></label>
                        <input class="form-input" id="ticketSubject" placeholder="Brief description of your issue">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" id="ticketPriority">
                            <option value="low">Low - General inquiry</option>
                            <option value="medium" selected>Medium - Important issue</option>
                            <option value="high">High - Urgent problem</option>
                            <option value="urgent">Urgent - Critical issue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message <span class="form-required">*</span></label>
                        <textarea class="form-textarea" id="ticketMessage" rows="6" placeholder="Describe your issue in detail..."></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="createTicket()"><i class="fas fa-paper-plane"></i> Submit Ticket</button>
                </div>
            </div>
        </div>

        <!-- ====== ADMIN ALL TICKETS ====== -->
        <?php if ($is_admin || $is_manager): ?>
        <div class="section" id="adminTicketsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users-cog"></i> All Support Tickets</div>
                    <div style="display:flex;gap:8px;">
                        <select class="form-select" id="adminTicketStatusFilter" onchange="loadAdminTickets()" style="width:150px;padding:8px 12px;">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button class="btn btn-success btn-sm" onclick="exportTickets()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Ticket No</th><th>Partner</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="adminTicketsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Ticket Detail Modal -->
<div class="modal-overlay" id="ticketDetailModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-ticket-alt"></i> Ticket Details</span>
            <button class="modal-close" onclick="closeModal('ticketDetailModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="ticketDetailContent">
            <div class="empty-state"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('ticketDetailModal')">Close</button>
        </div>
    </div>
</div>

<!-- Admin Reply Modal -->
<div class="modal-overlay" id="adminReplyModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-reply"></i> Reply to Ticket</span>
            <button class="modal-close" onclick="closeModal('adminReplyModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="replyTicketId">
            <div class="form-group">
                <label class="form-label">Reply Message <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="adminReplyMessage" rows="5" placeholder="Type your response..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Update Status</label>
                <select class="form-select" id="adminReplyStatus">
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('adminReplyModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitAdminReply()"><i class="fas fa-paper-plane"></i> Send Reply</button>
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
const USER_ROLE = <?= json_encode($user_role) ?>;
const IS_ADMIN = <?= json_encode($is_admin) ?>;
const IS_MANAGER = <?= json_encode($is_manager) ?>;
const IS_PARTNER = <?= json_encode($is_partner) ?>;

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('supportTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
}
(() => { setTheme(localStorage.getItem('supportTheme') || 'light'); })();

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
    dashboard: 'Support Dashboard',
    tickets: 'My Tickets',
    newTicket: 'New Ticket',
    adminTickets: 'All Tickets'
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
        tickets: loadAllTickets,
        adminTickets: loadAdminTickets
    };
    if (loaders[name]) loaders[name]();

    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('mobile-open');
    }
}

function showNewTicket() {
    showSection('newTicket');
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
function getPriorityBadge(priority) {
    const map = {
        'urgent': 'priority-urgent',
        'high': 'priority-high',
        'medium': 'priority-medium',
        'low': 'priority-low'
    };
    const labels = {
        'urgent': 'Urgent',
        'high': 'High',
        'medium': 'Medium',
        'low': 'Low'
    };
    const cls = map[priority?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[priority] || priority}</span>`;
}

function getStatusBadge(status) {
    const map = {
        'open': 'badge-warning',
        'in_progress': 'badge-info',
        'resolved': 'badge-success',
        'closed': 'badge-gray'
    };
    const labels = {
        'open': 'Open',
        'in_progress': 'In Progress',
        'resolved': 'Resolved',
        'closed': 'Closed'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function formatDate(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateString;
    }
}

function formatDateTime(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
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

// ── LOAD DASHBOARD ──────────────────────────────────────────────────
async function loadDashboard() {
    const body = document.getElementById('recentTicketsBody');
    if (body) body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>';

    try {
        // Load stats
        const statsData = await apiCall('get_ticket_stats');
        if (statsData.success && statsData.stats) {
            document.getElementById('totalTickets').textContent = statsData.stats.total || 0;
            document.getElementById('openTickets').textContent = statsData.stats.open || 0;
            document.getElementById('resolvedTickets').textContent = statsData.stats.resolved || 0;
            document.getElementById('urgentTickets').textContent = statsData.stats.urgent || 0;
        }

        // Load recent tickets
        const data = await apiCall(`get_tickets`);
        if (data.success && data.tickets && data.tickets.length) {
            const recent = data.tickets.slice(0, 5);
            body.innerHTML = recent.map(t => `
                <tr>
                    <td>#${t.id}</td>
                    <td><strong>${escHtml(t.subject)}</strong></td>
                    <td>${getPriorityBadge(t.priority)}</td>
                    <td>${getStatusBadge(t.status)}</td>
                    <td>${formatDate(t.created_at)}</td>
                    <td>
                        <button class="btn btn-outline btn-xs" onclick="viewTicket(${t.id})"><i class="fas fa-eye"></i></button>
                        ${(IS_ADMIN || IS_MANAGER) ? `<button class="btn btn-primary btn-xs" onclick="openAdminReply(${t.id})"><i class="fas fa-reply"></i></button>` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No tickets found</p></div></td></tr>';
        }
    } catch (e) {
        console.error('Dashboard error:', e);
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading tickets</p></div></td></tr>';
    }
}

// ── LOAD ALL TICKETS ────────────────────────────────────────────────
async function loadAllTickets() {
    const body = document.getElementById('allTicketsBody');
    if (body) body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>';

    try {
        const data = await apiCall(`get_tickets`);
        if (data.success && data.tickets && data.tickets.length) {
            body.innerHTML = data.tickets.map(t => `
                <tr>
                    <td>#${t.id}</td>
                    <td><strong>${escHtml(t.subject)}</strong></td>
                    <td>${getPriorityBadge(t.priority)}</td>
                    <td>${getStatusBadge(t.status)}</td>
                    <td>${formatDate(t.created_at)}</td>
                    <td>${formatDate(t.updated_at || t.created_at)}</td>
                    <td>
                        <button class="btn btn-outline btn-xs" onclick="viewTicket(${t.id})"><i class="fas fa-eye"></i></button>
                        ${(IS_ADMIN || IS_MANAGER) ? `<button class="btn btn-primary btn-xs" onclick="openAdminReply(${t.id})"><i class="fas fa-reply"></i></button>` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No tickets found</p></div></td></tr>';
        }
    } catch (e) {
        console.error('Load tickets error:', e);
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading tickets</p></div></td></tr>';
    }
}

// ── LOAD ADMIN TICKETS ──────────────────────────────────────────────
async function loadAdminTickets() {
    if (!IS_ADMIN && !IS_MANAGER) return;
    const body = document.getElementById('adminTicketsBody');
    if (body) body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>';

    const status = document.getElementById('adminTicketStatusFilter')?.value || '';

    try {
        const data = await apiCall(`get_tickets`);
        if (data.success && data.tickets && data.tickets.length) {
            let tickets = data.tickets;
            if (status) {
                tickets = tickets.filter(t => t.status === status);
            }
            body.innerHTML = tickets.map(t => `
                <tr>
                    <td>#${t.id}</td>
                    <td><strong>${escHtml(t.ticket_no || 'TKT-' + t.id)}</strong></td>
                    <td>${escHtml(t.partner_name || '—')}</td>
                    <td>${escHtml(t.subject)}</td>
                    <td>${getPriorityBadge(t.priority)}</td>
                    <td>${getStatusBadge(t.status)}</td>
                    <td>${formatDate(t.created_at)}</td>
                    <td>
                        <button class="btn btn-outline btn-xs" onclick="viewTicket(${t.id})"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-primary btn-xs" onclick="openAdminReply(${t.id})"><i class="fas fa-reply"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No tickets found</p></div></td></tr>';
        }
    } catch (e) {
        console.error('Load admin tickets error:', e);
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading tickets</p></div></td></tr>';
    }
}

// ── VIEW TICKET DETAIL ──────────────────────────────────────────────
async function viewTicket(id) {
    const content = document.getElementById('ticketDetailContent');
    if (content) content.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
    openModal('ticketDetailModal');

    try {
        const data = await apiCall(`get_ticket_detail?ticket_id=${id}`);
        if (data.success && data.ticket) {
            const t = data.ticket;
            const hasReply = t.admin_reply && t.admin_reply.trim() !== '';

            content.innerHTML = `
                <div style="margin-bottom:20px;">
                    <div style="background:var(--bg-sunken);padding:16px;border-radius:var(--radius-md);margin-bottom:16px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
                            <div><strong>Ticket #:</strong> ${t.ticket_no || '#' + t.id}</div>
                            <div><strong>Status:</strong> ${getStatusBadge(t.status)}</div>
                            <div><strong>Priority:</strong> ${getPriorityBadge(t.priority)}</div>
                            <div><strong>Created:</strong> ${formatDateTime(t.created_at)}</div>
                            ${t.updated_at ? `<div><strong>Last Updated:</strong> ${formatDateTime(t.updated_at)}</div>` : ''}
                            ${t.partner_name ? `<div><strong>Partner:</strong> ${escHtml(t.partner_name)}</div>` : ''}
                        </div>
                    </div>

                    <div style="background:var(--bg-sunken);padding:16px;border-radius:var(--radius-md);margin-bottom:16px;">
                        <strong><i class="fas fa-user"></i> ${escHtml(t.created_by_name || 'User')} wrote:</strong>
                        <p style="margin-top:8px;white-space:pre-wrap;">${escHtml(t.message)}</p>
                    </div>

                    ${hasReply ? `
                    <div style="background:var(--success-bg);padding:16px;border-radius:var(--radius-md);border-left:3px solid var(--success);">
                        <strong><i class="fas fa-user-headset"></i> Support Team:</strong>
                        <p style="margin-top:8px;white-space:pre-wrap;">${escHtml(t.admin_reply)}</p>
                        ${t.updated_at ? `<small style="display:block;margin-top:8px;color:var(--text-muted);">${formatDateTime(t.updated_at)}</small>` : ''}
                    </div>
                    ` : `
                    <div style="background:var(--warning-bg);padding:16px;border-radius:var(--radius-md);text-align:center;color:var(--warning);">
                        <i class="fas fa-clock"></i> No response yet. Our team will get back to you soon.
                    </div>
                    `}
                </div>
                ${(IS_ADMIN || IS_MANAGER) ? `
                <div style="margin-top:12px;text-align:right;">
                    <button class="btn btn-primary" onclick="closeModal('ticketDetailModal');openAdminReply(${t.id})"><i class="fas fa-reply"></i> Reply</button>
                </div>
                ` : ''}
            `;
        } else {
            content.innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger);">Error loading ticket details</div>';
            showToast(data.error || 'Failed to load ticket details', 'error');
        }
    } catch (e) {
        console.error('View ticket error:', e);
        content.innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger);">Error loading ticket details</div>';
        showToast('Failed to load ticket details', 'error');
    }
}

// ── CREATE TICKET ────────────────────────────────────────────────────
async function createTicket() {
    const subject = document.getElementById('ticketSubject')?.value.trim();
    const message = document.getElementById('ticketMessage')?.value.trim();
    const priority = document.getElementById('ticketPriority')?.value || 'medium';

    if (!subject) { showToast('Subject is required', 'warning'); return; }
    if (!message) { showToast('Message is required', 'warning'); return; }

    const btn = document.querySelector('#newTicketSection .btn-primary');
    const origText = btn?.innerHTML;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Submitting...';
    }

    const result = await apiCall('create_ticket', 'POST', { subject, message, priority });
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = origText;
    }

    if (result.success) {
        showToast('Ticket created successfully!', 'success');
        document.getElementById('ticketSubject').value = '';
        document.getElementById('ticketMessage').value = '';
        showSection('dashboard');
        loadDashboard();
        loadAllTickets();
        if (IS_ADMIN || IS_MANAGER) loadAdminTickets();
    } else {
        showToast(result.error || 'Failed to create ticket', 'error');
    }
}

// ── ADMIN REPLY ──────────────────────────────────────────────────────
function openAdminReply(id) {
    if (!IS_ADMIN && !IS_MANAGER) {
        showToast('Unauthorized', 'error');
        return;
    }
    document.getElementById('replyTicketId').value = id;
    document.getElementById('adminReplyMessage').value = '';
    document.getElementById('adminReplyStatus').value = 'in_progress';
    openModal('adminReplyModal');
}

async function submitAdminReply() {
    const id = document.getElementById('replyTicketId').value;
    const message = document.getElementById('adminReplyMessage').value.trim();
    const status = document.getElementById('adminReplyStatus').value;

    if (!message) { showToast('Reply message is required', 'warning'); return; }

    const btn = document.querySelector('#adminReplyModal .btn-primary');
    const origText = btn?.innerHTML;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Sending...';
    }

    const result = await apiCall('add_admin_reply', 'POST', {
        ticket_id: id,
        admin_reply: message
    });

    if (result.success) {
        // Also update status
        await apiCall('update_ticket_status', 'POST', {
            ticket_id: id,
            status: status,
            admin_reply: message
        });

        showToast('Reply sent successfully!', 'success');
        closeModal('adminReplyModal');
        loadDashboard();
        loadAllTickets();
        if (IS_ADMIN || IS_MANAGER) loadAdminTickets();
    } else {
        showToast(result.error || 'Failed to send reply', 'error');
    }

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// ── EXPORT TICKETS ──────────────────────────────────────────────────
function exportTickets() {
    showToast('Exporting tickets...', 'info');
    window.open('api/support/export_tickets.php', '_blank');
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 't') showSection('tickets');
    if (e.altKey && e.key === 'n') showNewTicket();
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();

console.log('✅ Support Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
console.log('👑 Is Admin:', <?= json_encode($is_admin) ?>);
</script>
</body>
</html>