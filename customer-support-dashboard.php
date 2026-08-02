<?php
// ============================================================
// CUSTOMER SUPPORT DASHBOARD - FULLY INTEGRATED
// Access: support_team, admin, manager, super_admin
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

// ── AUTH: allow support_team, admin, manager, super_admin ──────────
$allowed_roles = ['support_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Support Agent';
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
            // Total tickets
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets");
            $total_tickets = (int)($stmt->fetch()['total'] ?? 0);
            
            // Open tickets
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets WHERE status IN ('open', 'in_progress')");
            $open_tickets = (int)($stmt->fetch()['total'] ?? 0);
            
            // Average response time (in hours)
            $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg FROM support_tickets WHERE status = 'resolved'");
            $avg_response = (float)($stmt->fetch()['avg'] ?? 0);
            
            // CSAT Score (from reviews table)
            $stmt = $pdo->query("SELECT AVG(rating) as avg FROM reviews");
            $csat = (float)($stmt->fetch()['avg'] ?? 0);
            $csat_percent = $csat > 0 ? round(($csat / 5) * 100) : 0;
            
            // Ticket trends (last 7 days)
            $trend_labels = [];
            $trend_values = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $trend_labels[] = date('D', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE DATE(created_at) = ?");
                $stmt->execute([$date]);
                $trend_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Category distribution
            $stmt = $pdo->query("
                SELECT 
                    CASE 
                        WHEN subject LIKE '%payment%' OR subject LIKE '%invoice%' THEN 'Payments'
                        WHEN subject LIKE '%report%' OR subject LIKE '%credit%' THEN 'Credit Reports'
                        WHEN subject LIKE '%account%' OR subject LIKE '%login%' THEN 'Account Issues'
                        WHEN subject LIKE '%service%' OR subject LIKE '%repair%' THEN 'Services'
                        ELSE 'General'
                    END as category,
                    COUNT(*) as count
                FROM support_tickets
                GROUP BY category
            ");
            $category_data = $stmt->fetchAll();
            $cat_labels = [];
            $cat_values = [];
            foreach ($category_data as $c) {
                $cat_labels[] = $c['category'];
                $cat_values[] = (int)$c['count'];
            }
            
            // Recent tickets
            $stmt = $pdo->query("
                SELECT t.*, c.name as client_name 
                FROM support_tickets t
                LEFT JOIN customers c ON t.client_id = c.id
                ORDER BY t.created_at DESC
                LIMIT 10
            ");
            $recent_tickets = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_tickets' => $total_tickets,
                'open_tickets' => $open_tickets,
                'avg_response_time' => round($avg_response, 1),
                'csat' => $csat_percent,
                'trend_data' => ['labels' => $trend_labels, 'values' => $trend_values],
                'category_data' => ['labels' => $cat_labels, 'values' => $cat_values],
                'recent_tickets' => $recent_tickets
            ]);
            exit;
        }
        
        // ── GET TICKETS ──────────────────────────────────────────────
        if ($action === 'get_tickets') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT t.*, c.name as client_name 
                    FROM support_tickets t
                    LEFT JOIN customers c ON t.client_id = c.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (t.subject LIKE ? OR c.name LIKE ? OR t.ticket_no LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'tickets' => $tickets]);
            exit;
        }
        
        // ── ADD TICKET ──────────────────────────────────────────────
        if ($action === 'add_ticket') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $subject = trim($input['subject'] ?? '');
            $message = trim($input['message'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            
            if (!$client_id || empty($subject)) {
                echo json_encode(['success' => false, 'error' => 'Client and subject are required']);
                exit;
            }
            
            $ticket_no = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets (ticket_no, client_id, subject, message, priority, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, 'open', ?, NOW())
            ");
            $stmt->execute([$ticket_no, $client_id, $subject, $message, $priority, $user_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Support Ticket Created', "Ticket #$ticket_no: $subject"]);
            
            echo json_encode(['success' => true, 'ticket_id' => $pdo->lastInsertId()]);
            exit;
        }
        
        // ── REPLY TO TICKET ──────────────────────────────────────────
        if ($action === 'reply_ticket') {
            $input = json_decode(file_get_contents('php://input'), true);
            $ticket_id = (int)($input['ticket_id'] ?? 0);
            $message = trim($input['message'] ?? '');
            
            if (!$ticket_id || empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Ticket ID and message are required']);
                exit;
            }
            
            // Save reply
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$ticket_id, $user_id, $message]);
            
            // Update ticket status
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Support Ticket Replied', "Replied to ticket #$ticket_id"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET WHATSAPP CHATS ───────────────────────────────────────
        if ($action === 'get_whatsapp_chats') {
            $stmt = $pdo->query("
                SELECT w.*, c.name as customer_name 
                FROM whatsapp_chats w
                LEFT JOIN customers c ON w.client_id = c.id
                ORDER BY w.created_at DESC
                LIMIT 50
            ");
            $chats = $stmt->fetchAll();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_chats");
            $total = (int)($stmt->fetch()['total'] ?? 0);
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_chats WHERE is_read = FALSE");
            $unread = (int)($stmt->fetch()['total'] ?? 0);
            
            echo json_encode([
                'success' => true,
                'total' => $total,
                'unread' => $unread,
                'chats' => $chats
            ]);
            exit;
        }
        
        // ── GET EMAILS ───────────────────────────────────────────────
        if ($action === 'get_emails') {
            // Placeholder - implement email integration
            echo json_encode([
                'success' => true,
                'emails' => [
                    ['id' => 1, 'from_email' => 'client@example.com', 'subject' => 'Credit Report Issue', 'received_at' => date('Y-m-d H:i:s'), 'status' => 'open']
                ]
            ]);
            exit;
        }
        
        // ── GET CALLS ─────────────────────────────────────────────────
        if ($action === 'get_calls') {
            $stmt = $pdo->query("
                SELECT c.*, cust.name as customer_name, u.name as agent_name 
                FROM call_logs c
                LEFT JOIN customers cust ON c.client_id = cust.id
                LEFT JOIN users u ON c.agent_id = u.id
                ORDER BY c.created_at DESC
                LIMIT 50
            ");
            $calls = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'calls' => $calls]);
            exit;
        }
        
        // ── GET ESCALATIONS ──────────────────────────────────────────
        if ($action === 'get_escalations') {
            $stmt = $pdo->query("
                SELECT e.*, t.subject as ticket_subject, c.name as client_name, u.name as escalated_by_name
                FROM ticket_escalations e
                LEFT JOIN support_tickets t ON e.ticket_id = t.id
                LEFT JOIN customers c ON t.client_id = c.id
                LEFT JOIN users u ON e.escalated_by = u.id
                WHERE e.status = 'pending'
                ORDER BY e.created_at DESC
            ");
            $escalations = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'escalations' => $escalations]);
            exit;
        }
        
        // ── GET SLA STATS ────────────────────────────────────────────
        if ($action === 'get_sla_stats') {
            $stmt = $pdo->query("
                SELECT 
                    ROUND(AVG(CASE WHEN sla_met = 1 THEN 100 ELSE 0 END), 2) as sla_met,
                    SUM(CASE WHEN sla_met = 0 THEN 1 ELSE 0 END) as sla_breached
                FROM sla_metrics
            ");
            $stats = $stmt->fetch();
            
            // Breached tickets
            $stmt = $pdo->query("
                SELECT t.id as ticket_id, t.ticket_no, c.name as client_name, 
                       DATE_ADD(t.created_at, INTERVAL 24 HOUR) as due_date,
                       t.status
                FROM support_tickets t
                LEFT JOIN customers c ON t.client_id = c.id
                WHERE t.status IN ('open', 'in_progress')
                AND t.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                LIMIT 20
            ");
            $breaches = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'sla_met' => round($stats['sla_met'] ?? 0, 1),
                'sla_breached' => (int)($stats['sla_breached'] ?? 0),
                'breaches' => $breaches
            ]);
            exit;
        }
        
        // ── GET FAQS ──────────────────────────────────────────────────
        if ($action === 'get_faqs') {
            $stmt = $pdo->query("SELECT * FROM faqs ORDER BY created_at DESC");
            $faqs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'faqs' => $faqs]);
            exit;
        }
        
        // ── ADD FAQ ──────────────────────────────────────────────────
        if ($action === 'add_faq') {
            $input = json_decode(file_get_contents('php://input'), true);
            $question = trim($input['question'] ?? '');
            $answer = trim($input['answer'] ?? '');
            $category = trim($input['category'] ?? '');
            
            if (empty($question) || empty($answer)) {
                echo json_encode(['success' => false, 'error' => 'Question and answer are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, category, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$question, $answer, $category]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE FAQ ──────────────────────────────────────────────
        if ($action === 'delete_faq') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET REPLY TEMPLATES ──────────────────────────────────────
        if ($action === 'get_templates') {
            $stmt = $pdo->query("SELECT * FROM reply_templates ORDER BY created_at DESC");
            $templates = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'templates' => $templates]);
            exit;
        }
        
        // ── ADD REPLY TEMPLATE ──────────────────────────────────────
        if ($action === 'add_template') {
            $input = json_decode(file_get_contents('php://input'), true);
            $title = trim($input['title'] ?? '');
            $category = trim($input['category'] ?? '');
            $template = trim($input['template'] ?? '');
            
            if (empty($title) || empty($template)) {
                echo json_encode(['success' => false, 'error' => 'Title and template are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO reply_templates (title, category, template, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$title, $category, $template]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE REPLY TEMPLATE ────────────────────────────────────
        if ($action === 'delete_template') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM reply_templates WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET ANALYTICS ────────────────────────────────────────────
        if ($action === 'get_analytics') {
            $stmt = $pdo->query("
                SELECT u.name as agent_name, COUNT(t.id) as tickets_resolved
                FROM support_tickets t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.status = 'resolved'
                GROUP BY t.assigned_to
                ORDER BY tickets_resolved DESC
                LIMIT 10
            ");
            $agent_perf = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'agent_performance' => [
                    'labels' => array_column($agent_perf, 'agent_name'),
                    'values' => array_column($agent_perf, 'tickets_resolved')
                ]
            ]);
            exit;
        }
        
        // ── GET CLIENTS ──────────────────────────────────────────────
        if ($action === 'get_clients') {
            $stmt = $pdo->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name");
            $clients = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'clients' => $clients]);
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
<title>Customer Support | CIBIL Repair</title>

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

/* PRIORITY BADGES */
.priority-low { background: #dcfce7; color: #166534; }
.priority-medium { background: #fef3c7; color: #78350f; }
.priority-high { background: #fee2e2; color: #991b1b; }
.priority-urgent { background: #fecaca; color: #7f1d1d; }

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
        <div class="brand-icon">CS</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Customer Support</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Support Channels</div>
        <div class="nav-item" data-section="tickets">
            <i class="fas fa-ticket-alt"></i>
            <span class="nav-label">Support Tickets</span>
        </div>
        <div class="nav-item" data-section="whatsapp">
            <i class="fab fa-whatsapp"></i>
            <span class="nav-label">WhatsApp</span>
        </div>
        <div class="nav-item" data-section="email">
            <i class="fas fa-envelope"></i>
            <span class="nav-label">Email Support</span>
        </div>
        <div class="nav-item" data-section="calls">
            <i class="fas fa-phone"></i>
            <span class="nav-label">Call Logs</span>
        </div>
        <div class="nav-section-label">Management</div>
        <div class="nav-item" data-section="escalations">
            <i class="fas fa-arrow-up"></i>
            <span class="nav-label">Escalations</span>
        </div>
        <div class="nav-item" data-section="sla">
            <i class="fas fa-clock"></i>
            <span class="nav-label">SLA Tracker</span>
        </div>
        <div class="nav-section-label">Knowledge Base</div>
        <div class="nav-item" data-section="faq">
            <i class="fas fa-question-circle"></i>
            <span class="nav-label">FAQ Library</span>
        </div>
        <div class="nav-item" data-section="templates">
            <i class="fas fa-reply-all"></i>
            <span class="nav-label">Reply Templates</span>
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
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div class="stat-value" id="avgResponseTime">—</div>
                    <div class="stat-label">Avg Response (hrs)</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-smile"></i></span>
                    <div class="stat-value" id="csat">—</div>
                    <div class="stat-label">CSAT Score</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-line"></i> Ticket Trends</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Ticket Categories</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Tickets</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addTicketModal')"><i class="fas fa-plus"></i> New Ticket</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody id="recentBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TICKETS ====== -->
        <div class="section" id="ticketsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-ticket-alt"></i> All Support Tickets</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addTicketModal')"><i class="fas fa-plus"></i> New Ticket</button>
                        <button class="btn btn-success btn-sm" onclick="exportTickets()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="ticketSearch" placeholder="Search tickets…" oninput="debounce(loadTickets, 400)()">
                    </div>
                    <select class="form-select" id="ticketStatusFilter" onchange="loadTickets()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody id="ticketsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== WHATSAPP ====== -->
        <div class="section" id="whatsappSection">
            <div class="stats-grid" style="grid-template-columns: repeat(2,1fr);">
                <div class="stat-card green"><div class="stat-value" id="waTotal">—</div><div class="stat-label">Total Chats</div></div>
                <div class="stat-card amber"><div class="stat-value" id="waUnread">—</div><div class="stat-label">Unread</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fab fa-whatsapp"></i> WhatsApp Conversations</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Customer</th><th>Last Message</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="waBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== EMAIL ====== -->
        <div class="section" id="emailSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-envelope"></i> Email Support</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="emailBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CALLS ====== -->
        <div class="section" id="callsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-phone"></i> Call Logs</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Customer</th><th>Type</th><th>Duration</th><th>Agent</th><th>Time</th><th>Actions</th></tr></thead>
                        <tbody id="callsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ESCALATIONS ====== -->
        <div class="section" id="escalationsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-arrow-up"></i> Escalations</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Ticket ID</th><th>Customer</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="escBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SLA ====== -->
        <div class="section" id="slaSection">
            <div class="stats-grid" style="grid-template-columns: repeat(2,1fr);">
                <div class="stat-card green"><div class="stat-value" id="slaMet">—</div><div class="stat-label">SLA Met (%)</div></div>
                <div class="stat-card red"><div class="stat-value" id="slaBreached">—</div><div class="stat-label">SLA Breached</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clock"></i> SLA Breach Details</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Ticket</th><th>Customer</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="slaBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== FAQ ====== -->
        <div class="section" id="faqSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-question-circle"></i> FAQ Library</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addFaqModal')"><i class="fas fa-plus"></i> Add FAQ</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Question</th><th>Category</th><th>Actions</th></tr></thead>
                        <tbody id="faqBody">
                            <tr><td colspan="3"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TEMPLATES ====== -->
        <div class="section" id="templatesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-reply-all"></i> Reply Templates</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addTemplateModal')"><i class="fas fa-plus"></i> Add Template</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Category</th><th>Template</th><th>Actions</th></tr></thead>
                        <tbody id="templateBody">
                            <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Agent Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="agentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Ticket Modal -->
<div class="modal-overlay" id="addTicketModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-ticket-alt"></i> New Support Ticket</span>
            <button class="modal-close" onclick="closeModal('addTicketModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Customer <span class="form-required">*</span></label>
                <select class="form-select" id="ticketClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Subject <span class="form-required">*</span></label>
                <input class="form-input" id="ticketSubject" placeholder="Brief subject">
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="ticketPriority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Message <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="ticketMessage" rows="4" placeholder="Describe the issue..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addTicketModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addTicket()"><i class="fas fa-save"></i> Create Ticket</button>
        </div>
    </div>
</div>

<!-- Reply Ticket Modal -->
<div class="modal-overlay" id="replyTicketModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-reply"></i> Reply to Ticket</span>
            <button class="modal-close" onclick="closeModal('replyTicketModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="replyTicketId">
            <div class="form-group">
                <label class="form-label">Your Reply <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="replyMessage" rows="5" placeholder="Type your reply..."></textarea>
            </div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:8px;">
                <i class="fas fa-info-circle"></i> This reply will be sent to the customer and update the ticket status.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('replyTicketModal')">Cancel</button>
            <button class="btn btn-primary" onclick="sendReply()"><i class="fas fa-paper-plane"></i> Send Reply</button>
        </div>
    </div>
</div>

<!-- Add FAQ Modal -->
<div class="modal-overlay" id="addFaqModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add FAQ</span>
            <button class="modal-close" onclick="closeModal('addFaqModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Question <span class="form-required">*</span></label>
                <input class="form-input" id="faqQuestion" placeholder="Frequently asked question">
            </div>
            <div class="form-group">
                <label class="form-label">Answer <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="faqAnswer" rows="4" placeholder="Detailed answer"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input class="form-input" id="faqCategory" placeholder="e.g., Payments, Reports, Account">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addFaqModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addFaq()"><i class="fas fa-save"></i> Add FAQ</button>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div class="modal-overlay" id="addTemplateModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Reply Template</span>
            <button class="modal-close" onclick="closeModal('addTemplateModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Title <span class="form-required">*</span></label>
                <input class="form-input" id="templateTitle" placeholder="Template title">
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input class="form-input" id="templateCategory" placeholder="e.g., Payments, Account">
            </div>
            <div class="form-group">
                <label class="form-label">Template <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="templateContent" rows="5" placeholder="Reply template content..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addTemplateModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addTemplate()"><i class="fas fa-save"></i> Save Template</button>
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
    localStorage.setItem('supportTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
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
    tickets: 'Support Tickets',
    whatsapp: 'WhatsApp',
    email: 'Email Support',
    calls: 'Call Logs',
    escalations: 'Escalations',
    sla: 'SLA Tracker',
    faq: 'FAQ Library',
    templates: 'Reply Templates',
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
        tickets: loadTickets,
        whatsapp: loadWhatsApp,
        email: loadEmails,
        calls: loadCalls,
        escalations: loadEscalations,
        sla: loadSLA,
        faq: loadFAQ,
        templates: loadTemplates,
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

function getPriorityBadge(priority) {
    const map = {
        'low': 'priority-low',
        'medium': 'priority-medium',
        'high': 'priority-high',
        'urgent': 'priority-urgent'
    };
    const cls = map[priority?.toLowerCase()] || 'priority-medium';
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

    document.getElementById('totalTickets').textContent = data.total_tickets || 0;
    document.getElementById('openTickets').textContent = data.open_tickets || 0;
    document.getElementById('avgResponseTime').textContent = data.avg_response_time || 0;
    document.getElementById('csat').textContent = (data.csat || 0) + '%';

    // Trend chart
    if (data.trend_data) {
        destroyChart('trendChart');
        const ctx = document.getElementById('trendChart').getContext('2d');
        charts.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.trend_data.labels || [],
                datasets: [{
                    label: 'Tickets',
                    data: data.trend_data.values || [],
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

    // Category chart
    if (data.category_data) {
        destroyChart('categoryChart');
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#ec489a'];
        charts.categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.category_data.labels || [],
                datasets: [{
                    data: data.category_data.values || [],
                    backgroundColor: colors.slice(0, data.category_data.labels?.length || 0),
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

    // Recent tickets
    const body = document.getElementById('recentBody');
    if (data.recent_tickets && data.recent_tickets.length) {
        body.innerHTML = data.recent_tickets.map(t => `
            <tr>
                <td>#${t.id}</td>
                <td><strong>${escHtml(t.client_name || '—')}</strong></td>
                <td>${escHtml(t.subject)}</td>
                <td>${getPriorityBadge(t.priority)}</td>
                <td>${getStatusBadge(t.status)}</td>
                <td>${new Date(t.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openReplyModal(${t.id})"><i class="fas fa-reply"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewTicket(${t.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent tickets</p></div></td></tr>';
    }
}

function viewTicket(id) {
    showToast(`Viewing ticket #${id}`, 'info');
}

// ── TICKETS ──────────────────────────────────────────────────────────
async function loadTickets() {
    const search = document.getElementById('ticketSearch')?.value || '';
    const status = document.getElementById('ticketStatusFilter')?.value || '';
    
    const data = await apiCall(`get_tickets?search=${encodeURIComponent(search)}&status=${status}`);
    const body = document.getElementById('ticketsBody');
    
    if (data.success && data.tickets && data.tickets.length) {
        body.innerHTML = data.tickets.map(t => `
            <tr>
                <td>#${t.id}</td>
                <td><strong>${escHtml(t.client_name || '—')}</strong></td>
                <td>${escHtml(t.subject)}</td>
                <td>${getPriorityBadge(t.priority)}</td>
                <td>${getStatusBadge(t.status)}</td>
                <td>${new Date(t.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openReplyModal(${t.id})"><i class="fas fa-reply"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewTicket(${t.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No tickets found</p></div></td></tr>';
    }
}

async function addTicket() {
    const client_id = document.getElementById('ticketClient').value;
    const subject = document.getElementById('ticketSubject').value.trim();
    const priority = document.getElementById('ticketPriority').value;
    const message = document.getElementById('ticketMessage').value.trim();

    if (!client_id) { showToast('Please select a customer', 'warning'); return; }
    if (!subject) { showToast('Subject is required', 'warning'); return; }
    if (!message) { showToast('Message is required', 'warning'); return; }

    const result = await apiCall('add_ticket', 'POST', { client_id, subject, priority, message });
    if (result.success) {
        showToast('Ticket created successfully!', 'success');
        closeModal('addTicketModal');
        document.getElementById('ticketSubject').value = '';
        document.getElementById('ticketMessage').value = '';
        loadDashboard();
        loadTickets();
    } else {
        showToast(result.error || 'Failed to create ticket', 'error');
    }
}

function openReplyModal(id) {
    document.getElementById('replyTicketId').value = id;
    document.getElementById('replyMessage').value = '';
    openModal('replyTicketModal');
}

async function sendReply() {
    const id = document.getElementById('replyTicketId').value;
    const message = document.getElementById('replyMessage').value.trim();

    if (!message) { showToast('Message is required', 'warning'); return; }

    const result = await apiCall('reply_ticket', 'POST', { ticket_id: id, message });
    if (result.success) {
        showToast('Reply sent successfully!', 'success');
        closeModal('replyTicketModal');
        loadDashboard();
        loadTickets();
    } else {
        showToast(result.error || 'Failed to send reply', 'error');
    }
}

// ── WHATSAPP ─────────────────────────────────────────────────────────
async function loadWhatsApp() {
    const data = await apiCall('get_whatsapp_chats');
    if (!data.success) { showToast('Failed to load WhatsApp data', 'error'); return; }

    document.getElementById('waTotal').textContent = data.total || 0;
    document.getElementById('waUnread').textContent = data.unread || 0;

    const body = document.getElementById('waBody');
    if (data.chats && data.chats.length) {
        body.innerHTML = data.chats.map(c => `
            <tr>
                <td><strong>${escHtml(c.customer_name || '—')}</strong></td>
                <td>${escHtml(c.message)}</td>
                <td>${new Date(c.created_at).toLocaleString('en-IN')}</td>
                <td>${c.is_read ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-warning">Unread</span>'}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="showToast('Opening chat...', 'info')"><i class="fab fa-whatsapp"></i> Chat</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fab fa-whatsapp"></i><p>No WhatsApp chats found</p></div></td></tr>';
    }
}

// ── EMAIL ────────────────────────────────────────────────────────────
async function loadEmails() {
    const data = await apiCall('get_emails');
    const body = document.getElementById('emailBody');
    
    if (data.success && data.emails && data.emails.length) {
        body.innerHTML = data.emails.map(e => `
            <tr>
                <td>${escHtml(e.from_email)}</td>
                <td>${escHtml(e.subject)}</td>
                <td>${new Date(e.received_at).toLocaleDateString('en-IN')}</td>
                <td>${getStatusBadge(e.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="showToast('View email', 'info')"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-envelope"></i><p>No emails found</p></div></td></tr>';
    }
}

// ── CALLS ────────────────────────────────────────────────────────────
async function loadCalls() {
    const data = await apiCall('get_calls');
    const body = document.getElementById('callsBody');
    
    if (data.success && data.calls && data.calls.length) {
        body.innerHTML = data.calls.map(c => `
            <tr>
                <td><strong>${escHtml(c.customer_name || '—')}</strong></td>
                <td><span class="badge ${c.call_type === 'incoming' ? 'badge-success' : 'badge-info'}">${c.call_type}</span></td>
                <td>${c.duration || 0} min</td>
                <td>${escHtml(c.agent_name || '—')}</td>
                <td>${new Date(c.created_at).toLocaleString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="showToast('View call details', 'info')"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-phone"></i><p>No call logs found</p></div></td></tr>';
    }
}

// ── ESCALATIONS ──────────────────────────────────────────────────────
async function loadEscalations() {
    const data = await apiCall('get_escalations');
    const body = document.getElementById('escBody');
    
    if (data.success && data.escalations && data.escalations.length) {
        body.innerHTML = data.escalations.map(e => `
            <tr>
                <td>#${e.ticket_id}</td>
                <td><strong>${escHtml(e.client_name || '—')}</strong></td>
                <td>${escHtml(e.reason)}</td>
                <td>${getStatusBadge(e.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="showToast('Review escalation', 'info')"><i class="fas fa-check"></i> Review</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-arrow-up"></i><p>No escalations found</p></div></td></tr>';
    }
}

// ── SLA ──────────────────────────────────────────────────────────────
async function loadSLA() {
    const data = await apiCall('get_sla_stats');
    if (!data.success) { showToast('Failed to load SLA data', 'error'); return; }

    document.getElementById('slaMet').textContent = (data.sla_met || 0) + '%';
    document.getElementById('slaBreached').textContent = data.sla_breached || 0;

    const body = document.getElementById('slaBody');
    if (data.breaches && data.breaches.length) {
        body.innerHTML = data.breaches.map(b => `
            <tr>
                <td><strong>${escHtml(b.ticket_no || b.ticket_id)}</strong></td>
                <td>${escHtml(b.client_name || '—')}</td>
                <td>${new Date(b.due_date).toLocaleDateString('en-IN')}</td>
                <td>${getStatusBadge(b.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="openReplyModal(${b.ticket_id})"><i class="fas fa-reply"></i> Respond</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-clock"></i><p>No SLA breaches found</p></div></td></tr>';
    }
}

// ── FAQ ──────────────────────────────────────────────────────────────
async function loadFAQ() {
    const data = await apiCall('get_faqs');
    const body = document.getElementById('faqBody');
    
    if (data.success && data.faqs && data.faqs.length) {
        body.innerHTML = data.faqs.map(f => `
            <tr>
                <td><strong>${escHtml(f.question)}</strong></td>
                <td>${escHtml(f.category || 'General')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="showToast('Edit FAQ', 'info')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteFaq(${f.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="3"><div class="empty-state"><i class="fas fa-question-circle"></i><p>No FAQs found</p></div></td></tr>';
    }
}

async function addFaq() {
    const question = document.getElementById('faqQuestion').value.trim();
    const answer = document.getElementById('faqAnswer').value.trim();
    const category = document.getElementById('faqCategory').value.trim();

    if (!question) { showToast('Question is required', 'warning'); return; }
    if (!answer) { showToast('Answer is required', 'warning'); return; }

    const result = await apiCall('add_faq', 'POST', { question, answer, category });
    if (result.success) {
        showToast('FAQ added successfully!', 'success');
        closeModal('addFaqModal');
        document.getElementById('faqQuestion').value = '';
        document.getElementById('faqAnswer').value = '';
        document.getElementById('faqCategory').value = '';
        loadFAQ();
    } else {
        showToast(result.error || 'Failed to add FAQ', 'error');
    }
}

async function deleteFaq(id) {
    if (!confirm('Delete this FAQ?')) return;
    const result = await apiCall('delete_faq', 'POST', { id });
    if (result.success) {
        showToast('FAQ deleted', 'success');
        loadFAQ();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

// ── TEMPLATES ────────────────────────────────────────────────────────
async function loadTemplates() {
    const data = await apiCall('get_templates');
    const body = document.getElementById('templateBody');
    
    if (data.success && data.templates && data.templates.length) {
        body.innerHTML = data.templates.map(t => `
            <tr>
                <td><strong>${escHtml(t.title)}</strong></td>
                <td>${escHtml(t.category || 'General')}</td>
                <td>${escHtml(t.template.substring(0, 80))}...</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="useTemplate(${t.id})"><i class="fas fa-copy"></i> Use</button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTemplate(${t.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-reply-all"></i><p>No templates found</p></div></td></tr>';
    }
}

function useTemplate(id) {
    const template = document.querySelector(`#templateBody tr:nth-child(${id}) td:nth-child(3)`)?.textContent;
    if (template) {
        document.getElementById('replyMessage').value = template;
        closeModal('replyTicketModal');
        openModal('replyTicketModal');
        showToast('Template loaded!', 'success');
    } else {
        showToast('Template not found', 'error');
    }
}

async function addTemplate() {
    const title = document.getElementById('templateTitle').value.trim();
    const category = document.getElementById('templateCategory').value.trim();
    const content = document.getElementById('templateContent').value.trim();

    if (!title) { showToast('Title is required', 'warning'); return; }
    if (!content) { showToast('Template content is required', 'warning'); return; }

    const result = await apiCall('add_template', 'POST', { title, category, template: content });
    if (result.success) {
        showToast('Template added successfully!', 'success');
        closeModal('addTemplateModal');
        document.getElementById('templateTitle').value = '';
        document.getElementById('templateCategory').value = '';
        document.getElementById('templateContent').value = '';
        loadTemplates();
    } else {
        showToast(result.error || 'Failed to add template', 'error');
    }
}

async function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    const result = await apiCall('delete_template', 'POST', { id });
    if (result.success) {
        showToast('Template deleted', 'success');
        loadTemplates();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

// ── ANALYTICS ────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }

    if (data.agent_performance) {
        destroyChart('agentChart');
        const ctx = document.getElementById('agentChart').getContext('2d');
        charts.agentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.agent_performance.labels || [],
                datasets: [{
                    label: 'Tickets Resolved',
                    data: data.agent_performance.values || [],
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
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportTickets() { showToast('Exporting tickets...', 'info'); }

// ── LOAD CLIENTS ─────────────────────────────────────────────────────
async function loadClients() {
    const data = await apiCall('get_clients');
    if (data.success && data.clients) {
        const select = document.getElementById('ticketClient');
        if (select) {
            select.innerHTML = '<option value="">— Select Client —</option>' +
                data.clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
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
    if (e.altKey && e.key === 't') showSection('tickets');
    if (e.altKey && e.key === 'f') showSection('faq');
    if (e.altKey && e.key === 's') showSection('sla');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addTicketModal') {
                loadClients();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClients();

console.log('✅ Customer Support Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>