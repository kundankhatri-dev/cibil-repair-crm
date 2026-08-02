<?php
// ============================================================
// CLIENT DASHBOARD - FULLY INTEGRATED
// Access: client (own data) | partner (their clients only) | admin (all)
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

// ── AUTH: allow client, partner, admin ──────────────────────────────
$allowed_roles = ['client', 'partner', 'admin', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: login.php'); exit;
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

$viewer_role = $_SESSION['user_role'];
$viewer_id   = (int)$_SESSION['user_id'];
$is_admin = in_array($viewer_role, ['admin', 'super_admin']);

// ── Determine which client to show ──────────────────────────────────
$client_id = null;
$show_picker = false;

if ($viewer_role === 'client') {
    // Client sees their own data
    $client_id = $viewer_id;
} elseif ($is_admin) {
    // Admin can view any client
    $client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
    if (!$client_id) {
        $show_picker = true;
    }
} elseif ($viewer_role === 'partner') {
    // Partner can only view clients linked to them via leads
    if (isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        // Check if this client belongs to this partner via leads or customers table
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM leads 
             WHERE partner_id = ? AND customer_id = ? 
             UNION ALL
             SELECT COUNT(*) FROM customers 
             WHERE partner_id = ? AND user_id = ?"
        );
        $chk->execute([$viewer_id, $cid, $viewer_id, $cid]);
        $count = $chk->fetchColumn() + $chk->fetchColumn();
        if ($count > 0) {
            $client_id = $cid;
        }
    }
    if (!$client_id) {
        $show_picker = true;
    }
}

// ── Load Client Data ──────────────────────────────────────────────
$client = null;
if ($client_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    if (!$client) {
        $client_id = null;
        $show_picker = true;
    }
}

// ── Load Partner Clients (for partner picker) ─────────────────────
$partner_clients = [];
if ($viewer_role === 'partner') {
    $stmt = $pdo->prepare(
        "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.created_at
         FROM users u
         LEFT JOIN leads l ON l.customer_id = u.id AND l.partner_id = ?
         LEFT JOIN customers c ON c.user_id = u.id AND c.partner_id = ?
         WHERE u.role = 'client' 
         AND (l.id IS NOT NULL OR c.id IS NOT NULL)
         ORDER BY u.name"
    );
    $stmt->execute([$viewer_id, $viewer_id]);
    $partner_clients = $stmt->fetchAll();
}

// ── Load Admin Clients ─────────────────────────────────────────────
$admin_clients = [];
if ($is_admin) {
    $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users WHERE role = 'client' ORDER BY name");
    $admin_clients = $stmt->fetchAll();
}

$csrf     = $_SESSION['csrf_token'];
$initials = $client ? strtoupper(substr($client['name'] ?? 'C', 0, 2)) : '??';
$cname    = $client ? h($client['name'] ?? 'Client') : '';
$cemail   = $client ? h($client['email'] ?? '') : '';
$cphone   = $client ? h($client['phone'] ?? '') : '';
$caddress = $client ? h($client['address'] ?? '') : '';
$ccity    = $client ? h($client['city'] ?? '') : '';
$cstate   = $client ? h($client['state'] ?? '') : '';
$cpin     = $client ? h($client['postal_code'] ?? $client['pincode'] ?? '') : '';

// Viewer info for topbar
$viewer_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$viewer_stmt->execute([$viewer_id]);
$viewer_data = $viewer_stmt->fetch();
$viewer_name = h($viewer_data['name'] ?? ucfirst($viewer_role));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrf ?>">
<title>Client Dashboard | CIBIL Repair</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

<style>
/* ================================================================
   DESIGN TOKENS — identical to admin + partner dashboards
   ================================================================ */
:root {
  --brand:        #0d9e78;
  --brand-dark:   #0a7d60;
  --brand-light:  #e6f7f2;
  --brand-muted:  #b2e4d6;

  --bg-base:      #f4f6f9;
  --bg-surface:   #ffffff;
  --bg-sunken:    #eef0f4;

  --text-primary:   #111827;
  --text-secondary: #4b5563;
  --text-muted:     #9ca3af;

  --border:         rgba(0,0,0,0.08);
  --border-strong:  rgba(0,0,0,0.15);

  --sidebar-bg:     #0b2a23;
  --sidebar-hover:  rgba(255,255,255,0.08);
  --sidebar-active: rgba(13,158,120,0.25);
  --sidebar-text:   rgba(255,255,255,0.75);
  --sidebar-active-text: #ffffff;

  --success-bg: #ecfdf5; --success:  #059669; --success-text: #065f46;
  --warning-bg: #fffbeb; --warning:  #d97706; --warning-text: #92400e;
  --danger-bg:  #fef2f2; --danger:   #dc2626; --danger-text:  #991b1b;
  --info-bg:    #eff6ff; --info:     #2563eb; --info-text:    #1e40af;

  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --shadow-lg: 0 12px 32px rgba(0,0,0,0.12);

  --radius-sm: 6px; --radius-md: 10px; --radius-lg: 16px; --radius-xl: 24px;
  --sidebar-w: 260px; --topbar-h: 64px;
  --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
  --font-main: 'Plus Jakarta Sans', sans-serif;
  --font-mono: 'DM Mono', monospace;
}
[data-theme="dark"] {
  --brand-light: #0f2e26; --brand-muted: #1a4a38;
  --bg-base: #0f1117; --bg-surface: #1a1d27; --bg-sunken: #13161f;
  --text-primary: #f1f5f9; --text-secondary: #94a3b8; --text-muted: #64748b;
  --border: rgba(255,255,255,0.07); --border-strong: rgba(255,255,255,0.12);
  --sidebar-bg: #080e0b;
  --success-bg: #052e1c; --success-text: #34d399;
  --warning-bg: #1c1204; --warning-text: #fbbf24;
  --danger-bg:  #1f0808; --danger-text:  #f87171;
  --info-bg:    #0c1a33; --info-text:    #60a5fa;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
  --shadow-lg: 0 12px 32px rgba(0,0,0,0.5);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-main);font-size:14px;background:var(--bg-base);color:var(--text-primary);overflow-x:hidden;transition:background var(--transition),color var(--transition);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button{font-family:inherit;cursor:pointer}
input,select,textarea{font-family:inherit}
:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:99px}

/* SIDEBAR */
.sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar-w);background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;transition:width var(--transition),transform var(--transition);overflow:hidden}
.sidebar.collapsed{width:64px}
.sidebar-brand{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:10px;min-height:68px}
.brand-icon{width:36px;height:36px;flex-shrink:0;background:linear-gradient(135deg,var(--brand),#06b6d4);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;font-weight:800}
.brand-text{overflow:hidden;white-space:nowrap}
.brand-name{font-weight:800;font-size:15px;color:#fff;letter-spacing:-0.3px}
.brand-sub{font-size:11px;color:rgba(255,255,255,0.45);margin-top:1px}
.sidebar-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:12px 0}
.sidebar-nav::-webkit-scrollbar{width:0}
.nav-section-label{font-size:10px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,0.3);padding:14px 18px 4px;white-space:nowrap;overflow:hidden}
.sidebar.collapsed .nav-section-label{opacity:0;pointer-events:none}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 18px;margin:1px 8px;border-radius:var(--radius-md);color:var(--sidebar-text);cursor:pointer;transition:all var(--transition);position:relative;white-space:nowrap;font-size:13.5px;font-weight:500}
.nav-item:hover{background:var(--sidebar-hover);color:#fff}
.nav-item.active{background:var(--sidebar-active);color:var(--sidebar-active-text)}
.nav-item.active::before{content:'';position:absolute;left:-8px;top:50%;transform:translateY(-50%);width:3px;height:20px;background:var(--brand);border-radius:0 3px 3px 0}
.nav-item i{width:20px;text-align:center;flex-shrink:0;font-size:15px}
.nav-label{flex:1;overflow:hidden;text-overflow:ellipsis}
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-section-label{display:none}
.sidebar.collapsed .nav-item{justify-content:center;padding:10px;margin:1px 8px}
.sidebar.collapsed .nav-item.active::before{left:-8px}
.sidebar.collapsed .nav-item::after{content:attr(data-tooltip);position:absolute;left:68px;background:#1a2e28;color:#fff;padding:5px 10px;border-radius:var(--radius-sm);font-size:12px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity var(--transition);z-index:300}
.sidebar.collapsed .nav-item:hover::after{opacity:1}
.sidebar-footer{padding:12px 8px;border-top:1px solid rgba(255,255,255,0.06)}
.sidebar-user{display:flex;align-items:center;gap:10px;padding:10px;border-radius:var(--radius-md);cursor:pointer;transition:background var(--transition);white-space:nowrap;overflow:hidden}
.sidebar-user:hover{background:var(--sidebar-hover)}
.user-avatar{width:32px;height:32px;flex-shrink:0;background:linear-gradient(135deg,var(--brand),#0891b2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff}
.user-name{font-size:13px;font-weight:600;color:#fff}
.user-role{font-size:11px;color:rgba(255,255,255,0.45)}
.sidebar.collapsed .user-details{display:none}
.sidebar-toggle{position:fixed;top:20px;left:calc(var(--sidebar-w) - 14px);width:28px;height:28px;background:var(--bg-surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:201;transition:left var(--transition);box-shadow:var(--shadow-sm);color:var(--text-secondary);font-size:11px}
.sidebar.collapsed ~ .sidebar-toggle{left:50px}

/* MAIN */
.main{flex:1;margin-left:var(--sidebar-w);display:flex;flex-direction:column;transition:margin-left var(--transition);min-width:0}
.sidebar.collapsed ~ .sidebar-toggle ~ .main{margin-left:64px}

/* TOPBAR */
.topbar{height:var(--topbar-h);background:var(--bg-surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:100;gap:16px}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.page-breadcrumb{font-size:12px;color:var(--text-muted);white-space:nowrap}
.page-title-top{font-size:16px;font-weight:700;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.clock-badge{font-family:var(--font-mono);font-size:12px;background:var(--bg-sunken);border:1px solid var(--border);padding:5px 10px;border-radius:99px;color:var(--text-secondary);white-space:nowrap}
.icon-btn{width:36px;height:36px;background:transparent;border:1px solid var(--border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition);color:var(--text-secondary);font-size:15px;position:relative}
.icon-btn:hover{background:var(--bg-sunken);color:var(--text-primary)}
.notif-badge{position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:99px;display:none;min-width:16px;text-align:center}
.theme-toggle{display:flex;align-items:center;gap:6px;background:var(--bg-sunken);border:1px solid var(--border);border-radius:99px;padding:4px}
.theme-btn{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition);font-size:13px;color:var(--text-muted);background:transparent;border:none}
.theme-btn.active{background:var(--bg-surface);color:var(--text-primary);box-shadow:var(--shadow-sm)}
.logout-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--radius-md);background:var(--danger-bg);color:var(--danger-text);border:1px solid rgba(220,38,38,0.2);font-size:13px;font-weight:600;transition:all var(--transition)}
.logout-btn:hover{background:var(--danger);color:#fff}
.back-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--radius-md);background:var(--info-bg);color:var(--info-text);border:1px solid rgba(37,99,235,0.2);font-size:13px;font-weight:600;transition:all var(--transition);text-decoration:none}
.back-btn:hover{background:var(--info);color:#fff}

/* CONTENT */
.content{padding:24px;flex:1}

/* CARDS */
.card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:20px}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.card-title{font-size:14px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:7px}
.card-title i{color:var(--brand);font-size:15px}
.card-body{padding:20px}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:20px}
.stat-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;display:flex;flex-direction:column;gap:10px;transition:transform var(--transition),box-shadow var(--transition);position:relative;overflow:hidden;cursor:pointer}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.stat-card.green::after{background:linear-gradient(90deg,var(--brand),#34d399)}
.stat-card.blue::after{background:linear-gradient(90deg,#2563eb,#60a5fa)}
.stat-card.amber::after{background:linear-gradient(90deg,#d97706,#fbbf24)}
.stat-card.purple::after{background:linear-gradient(90deg,#7c3aed,#a78bfa)}
.stat-card.red::after{background:linear-gradient(90deg,#dc2626,#f87171)}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between}
.stat-icon-wrap{width:40px;height:40px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:17px}
.stat-card.green .stat-icon-wrap{background:var(--brand-light);color:var(--brand)}
.stat-card.blue  .stat-icon-wrap{background:var(--info-bg);color:var(--info)}
.stat-card.amber .stat-icon-wrap{background:var(--warning-bg);color:var(--warning)}
.stat-card.purple .stat-icon-wrap{background:#f3f0ff;color:#7c3aed}
.stat-card.red   .stat-icon-wrap{background:var(--danger-bg);color:var(--danger)}
[data-theme="dark"] .stat-card.purple .stat-icon-wrap{background:#1a0a2e;color:#a78bfa}
.stat-change{font-size:11px;font-weight:600;padding:3px 8px;border-radius:99px}
.stat-change.up{background:var(--success-bg);color:var(--success-text)}
.stat-change.down{background:var(--danger-bg);color:var(--danger-text)}
.stat-change.neu{background:var(--bg-sunken);color:var(--text-muted)}
.stat-value{font-size:26px;font-weight:800;letter-spacing:-0.5px}
.stat-label{font-size:12px;color:var(--text-secondary);font-weight:500}
.progress-bar{height:6px;background:var(--bg-sunken);border-radius:99px;overflow:hidden;margin-top:4px}
.progress-fill{height:100%;border-radius:99px;transition:width 0.8s ease}

/* CREDIT SCORE WIDGET */
.score-widget{background:linear-gradient(135deg,var(--sidebar-bg),#0e3d30);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;color:#fff;position:relative;overflow:hidden;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.score-widget::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(13,158,120,0.15)}
.score-widget::after{content:'';position:absolute;bottom:-60px;right:60px;width:120px;height:120px;border-radius:50%;background:rgba(13,158,120,0.1)}
.score-circle{width:110px;height:110px;border-radius:50%;border:6px solid rgba(255,255,255,0.15);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;position:relative;z-index:1}
.score-circle-val{font-size:28px;font-weight:800;line-height:1}
.score-circle-lbl{font-size:10px;opacity:0.7;margin-top:2px;text-transform:uppercase;letter-spacing:0.5px}
.score-circle.excellent{border-color:#34d399;box-shadow:0 0 20px rgba(52,211,153,0.3)}
.score-circle.good{border-color:#60a5fa;box-shadow:0 0 20px rgba(96,165,250,0.3)}
.score-circle.average{border-color:#fbbf24;box-shadow:0 0 20px rgba(251,191,36,0.3)}
.score-circle.poor{border-color:#f87171;box-shadow:0 0 20px rgba(248,113,113,0.3)}
.score-info{flex:1;position:relative;z-index:1}
.score-info h2{font-size:20px;font-weight:800;margin-bottom:4px}
.score-info p{font-size:13px;opacity:0.7;margin-bottom:12px}
.score-change{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:99px;font-size:12px;font-weight:600}
.score-change.up{background:rgba(52,211,153,0.2);color:#34d399}
.score-change.down{background:rgba(248,113,113,0.2);color:#f87171}
.score-change.same{background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7)}
.score-gauge{flex:1;min-width:200px;position:relative;z-index:1}
.score-range-bar{height:8px;border-radius:99px;background:linear-gradient(90deg,#f87171 0%,#fbbf24 30%,#60a5fa 60%,#34d399 100%);position:relative;margin:12px 0 6px}
.score-indicator{position:absolute;top:-4px;width:16px;height:16px;border-radius:50%;background:#fff;border:3px solid;transform:translateX(-50%);transition:left 0.8s ease}
.score-range-labels{display:flex;justify-content:space-between;font-size:10px;opacity:0.6}

/* CHARTS */
.charts-row{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px}

/* TABLE */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-muted);background:var(--bg-sunken);border-bottom:1px solid var(--border);white-space:nowrap}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text-primary);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--bg-sunken)}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.badge-green{background:var(--success-bg);color:var(--success-text)}
.badge-amber{background:var(--warning-bg);color:var(--warning-text)}
.badge-red{background:var(--danger-bg);color:var(--danger-text)}
.badge-blue{background:var(--info-bg);color:var(--info-text)}
.badge-gray{background:var(--bg-sunken);color:var(--text-secondary)}
.badge-brand{background:var(--brand-light);color:var(--brand-dark)}
.badge-purple{background:var(--purple-bg);color:var(--purple-text)}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-md);font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all var(--transition);white-space:nowrap}
.btn-primary{background:var(--brand);color:#fff}
.btn-primary:hover{background:var(--brand-dark)}
.btn-danger{background:var(--danger-bg);color:var(--danger-text);border:1px solid rgba(220,38,38,.2)}
.btn-danger:hover{background:var(--danger);color:#fff}
.btn-success{background:var(--success-bg);color:var(--success-text);border:1px solid rgba(5,150,105,.2)}
.btn-success:hover{background:var(--success);color:#fff}
.btn-ghost{background:var(--bg-sunken);color:var(--text-secondary);border:1px solid var(--border)}
.btn-ghost:hover{background:var(--border)}
.btn-sm{padding:5px 11px;font-size:12px}
.btn-xs{padding:3px 8px;font-size:11px}
.btn:disabled{opacity:0.6;cursor:not-allowed}

/* FORMS */
.form-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.form-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:160px}
.form-label{font-size:12px;font-weight:600;color:var(--text-secondary)}
.form-input,.form-select,.form-textarea{width:100%;padding:8px 12px;background:var(--bg-surface);border:1px solid var(--border-strong);border-radius:var(--radius-md);font-size:13px;color:var(--text-primary);transition:border-color var(--transition);outline:none}
.form-textarea{resize:vertical;min-height:80px}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--brand)}
.form-input::placeholder,.form-textarea::placeholder{color:var(--text-muted)}

/* SECTIONS */
.section{display:none}
.section.active{display:block;animation:fadeIn 0.25s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}}

/* MODALS */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px}
.modal-overlay.open{display:flex}
.modal-box{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-xl);width:100%;max-width:560px;max-height:92vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:modalIn 0.25s cubic-bezier(0.34,1.56,0.64,1)}
@keyframes modalIn{from{transform:scale(0.9) translateY(20px);opacity:0}}
.modal-header{padding:20px 24px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)}
.modal-title{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px}
.modal-title i{color:var(--brand)}
.modal-close{width:32px;height:32px;background:var(--bg-sunken);border:none;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-secondary);font-size:16px;transition:all var(--transition)}
.modal-close:hover{background:var(--danger-bg);color:var(--danger)}
.modal-body{padding:20px 24px}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}

/* TOAST */
.toast-container{position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:10px;z-index:2000}
.toast{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:var(--radius-lg);background:var(--bg-surface);border:1px solid var(--border);box-shadow:var(--shadow-lg);min-width:280px;max-width:380px;animation:toastIn 0.3s cubic-bezier(0.34,1.56,0.64,1);transition:all 0.2s}
@keyframes toastIn{from{transform:translateX(100%);opacity:0}}
.toast.leaving{transform:translateX(110%);opacity:0}
.toast-icon{width:32px;height:32px;border-radius:var(--radius-md);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px}
.toast-success .toast-icon{background:var(--success-bg);color:var(--success)}
.toast-error   .toast-icon{background:var(--danger-bg);color:var(--danger)}
.toast-info    .toast-icon{background:var(--info-bg);color:var(--info)}
.toast-warning .toast-icon{background:var(--warning-bg);color:var(--warning)}
.toast-msg{font-size:13px;font-weight:500;flex:1}
.toast-close{background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px}

/* SPINNER */
.spinner{width:18px;height:18px;border:2px solid var(--border);border-top-color:var(--brand);border-radius:50%;animation:spin 0.7s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

/* EMPTY */
.empty-state{padding:48px 20px;text-align:center;color:var(--text-muted)}
.empty-state i{font-size:40px;margin-bottom:12px;display:block}
.empty-state p{font-size:14px}

/* FILTER BAR */
.filter-bar{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap}
.search-wrap{position:relative;flex:1;min-width:200px}
.search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px}
.search-input{width:100%;padding:8px 12px 8px 32px;border:1px solid var(--border-strong);border-radius:var(--radius-md);background:var(--bg-surface);font-size:13px;color:var(--text-primary);outline:none}
.search-input:focus{border-color:var(--brand)}

/* TABS */
.tab-bar{display:flex;border-bottom:1px solid var(--border);padding:0 20px;gap:4px}
.tab-btn{padding:10px 16px;font-size:13px;font-weight:600;color:var(--text-muted);background:none;border:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color var(--transition)}
.tab-btn.active{color:var(--brand);border-bottom-color:var(--brand)}
.tab-content{display:none;padding:20px}
.tab-content.active{display:block}

/* NOTIF PANEL */
.notif-panel{position:absolute;top:calc(100% + 8px);right:0;width:340px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);display:none;z-index:500;overflow:hidden}
.notif-panel.open{display:block}
.notif-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.notif-header h4{font-size:14px;font-weight:700}
.notif-mark{font-size:12px;color:var(--brand);cursor:pointer;background:none;border:none}
.notif-list{max-height:300px;overflow-y:auto}
.notif-item{padding:12px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:background var(--transition);display:flex;gap:10px;align-items:flex-start}
.notif-item:hover{background:var(--bg-sunken)}
.notif-item.unread{background:var(--brand-light)}
.notif-dot{width:8px;height:8px;border-radius:50%;background:var(--brand);flex-shrink:0;margin-top:5px}
.notif-item:not(.unread) .notif-dot{background:transparent}
.notif-title{font-size:13px;font-weight:600;margin-bottom:2px}
.notif-msg{font-size:12px;color:var(--text-secondary)}
.notif-time{font-size:11px;color:var(--text-muted);margin-top:3px}

/* VIEWER BANNER */
.viewer-banner{background:linear-gradient(135deg,#1e3a8a,#1e40af);color:#fff;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13px;flex-wrap:wrap}
.viewer-banner strong{font-weight:700}
.viewer-banner a{color:#bfdbfe;text-decoration:underline;font-size:12px}

/* KPI ROW */
.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:16px}
.kpi-box{background:var(--bg-sunken);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center}
.kpi-val{font-size:20px;font-weight:800;color:var(--brand)}
.kpi-lbl{font-size:11px;color:var(--text-secondary);margin-top:3px}

/* AI PANEL */
.ai-panel{position:fixed;bottom:20px;right:20px;width:380px;z-index:900;display:flex;flex-direction:column;border-radius:var(--radius-xl);background:var(--bg-surface);border:1px solid var(--border);box-shadow:var(--shadow-lg);transition:all var(--transition);overflow:hidden}
.ai-panel.mini{width:auto}
.ai-header{padding:12px 16px;background:linear-gradient(135deg,var(--sidebar-bg),#0e3d30);display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.ai-status-dot{width:8px;height:8px;border-radius:50%;background:#34d399;flex-shrink:0;box-shadow:0 0 6px rgba(52,211,153,0.6);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
.ai-title{color:#fff;font-size:13px;font-weight:700;flex:1}
.ai-subtitle{color:rgba(255,255,255,0.45);font-size:11px}
.ai-chevron{color:rgba(255,255,255,0.6);font-size:12px;transition:transform var(--transition)}
.ai-panel.mini .ai-chevron{transform:rotate(180deg)}
.ai-body{display:flex;flex-direction:column;height:380px}
.ai-panel.mini .ai-body{display:none}
.ai-messages{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px}
.ai-msg{max-width:85%;padding:10px 13px;border-radius:var(--radius-lg);font-size:13px;line-height:1.5}
.ai-msg.user{background:var(--brand);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.ai-msg.bot{background:var(--bg-sunken);color:var(--text-primary);align-self:flex-start;border-bottom-left-radius:4px;border:1px solid var(--border)}
.ai-chips{padding:8px 14px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid var(--border)}
.ai-chip{padding:5px 10px;border-radius:99px;background:var(--bg-sunken);border:1px solid var(--border);font-size:11px;cursor:pointer;transition:all var(--transition);color:var(--text-secondary);white-space:nowrap}
.ai-chip:hover{background:var(--brand-light);border-color:var(--brand-muted);color:var(--brand)}
.ai-input-row{padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:8px}
.ai-input{flex:1;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-md);font-size:13px;background:var(--bg-sunken);color:var(--text-primary);outline:none}
.ai-input:focus{border-color:var(--brand)}
.ai-send{width:36px;height:36px;background:var(--brand);border:none;border-radius:var(--radius-md);color:#fff;font-size:14px;cursor:pointer;transition:background var(--transition);display:flex;align-items:center;justify-content:center}
.ai-send:hover{background:var(--brand-dark)}

/* PICKER */
.picker-overlay{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.picker-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);width:100%;max-width:680px;overflow:hidden}
.picker-header{background:linear-gradient(135deg,var(--sidebar-bg),#0e3d30);padding:24px;color:#fff}
.picker-header h2{font-size:20px;font-weight:800}
.picker-header p{font-size:13px;opacity:0.7;margin-top:4px}

/* UPLOAD AREA */
.upload-area{border:2px dashed var(--border-strong);border-radius:var(--radius-lg);padding:24px;text-align:center;cursor:pointer;transition:all var(--transition)}
.upload-area:hover{border-color:var(--brand);background:var(--brand-light)}
.upload-area i{font-size:32px;color:var(--brand);margin-bottom:8px;display:block}

@media(max-width:900px){
  .sidebar{transform:translateX(-100%);width:var(--sidebar-w) !important}
  .sidebar.mobile-open{transform:translateX(0)}
  .main{margin-left:0 !important}
  .sidebar-toggle{left:12px !important}
  .charts-row{grid-template-columns:1fr}
  .ai-panel{width:calc(100vw - 32px);right:16px}
  .topbar-right .clock-badge{display:none}
}
@media(max-width:600px){
  .content{padding:14px}
  .stats-grid{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<?php if ($show_picker): ?>
<!-- ================================================================
     CLIENT PICKER (partner/admin must choose a client first)
     ================================================================ -->
<div style="background:var(--bg-base);min-height:100vh;">
  <div class="topbar" style="margin-left:0;">
    <div class="topbar-left">
      <div class="brand-icon" style="width:32px;height:32px;background:linear-gradient(135deg,var(--brand),#06b6d4);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;">CR</div>
      <div>
        <div class="page-breadcrumb">CIBIL Repair</div>
        <div class="page-title-top">Client Dashboard — <?= ucfirst($viewer_role) ?> View</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="theme-toggle">
        <button class="theme-btn active" id="lightBtnPicker" onclick="setTheme('light')"><i class="fas fa-sun"></i></button>
        <button class="theme-btn" id="darkBtnPicker" onclick="setTheme('dark')"><i class="fas fa-moon"></i></button>
      </div>
      <?php if ($viewer_role === 'partner'): ?>
      <a href="partner-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i>My Dashboard</a>
      <?php elseif ($is_admin): ?>
      <a href="admin-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i>Admin Dashboard</a>
      <?php endif; ?>
      <button class="logout-btn" onclick="if(confirm('Logout?')) window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i>Logout</button>
    </div>
  </div>
  <div class="picker-overlay">
    <div class="picker-card">
      <div class="picker-header">
        <h2>👤 Select a Client</h2>
        <p>Choose which client's dashboard you want to view<?= $viewer_role === 'partner' ? ' (your assigned clients only)' : '' ?></p>
      </div>
      <div class="filter-bar">
        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input class="search-input" id="pickerSearch" placeholder="Search by name, email or phone…" oninput="filterPicker()">
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Client Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Action</th></tr></thead>
          <tbody id="pickerBody">
            <?php
            $list = $is_admin ? $admin_clients : $partner_clients;
            if (empty($list)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-users"></i><p>No clients found<?= $viewer_role === 'partner' ? ' linked to your account' : '' ?>.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $cl): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= h($cl['name'] ?? 'Unknown') ?></strong></td>
              <td><?= h($cl['email'] ?? '—') ?></td>
              <td><?= h($cl['phone'] ?? '—') ?></td>
              <td><?= isset($cl['created_at']) ? date('d M Y', strtotime($cl['created_at'])) : '—' ?></td>
              <td>
                <a href="client-dashboard.php?client_id=<?= (int)$cl['id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-eye"></i>View Dashboard
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script>
function setTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('clientTheme',t);document.getElementById('lightBtnPicker').classList.toggle('active',t==='light');document.getElementById('darkBtnPicker').classList.toggle('active',t==='dark');}
(function(){setTheme(localStorage.getItem('clientTheme')||'light');})();
function filterPicker(){const s=document.getElementById('pickerSearch').value.toLowerCase();document.querySelectorAll('#pickerBody tr').forEach(r=>{r.style.display=(r.textContent.toLowerCase().includes(s)||!s)?'':'none'});}
function toast(m,t='info',d=3500){const icons={success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle',warning:'fa-exclamation-triangle'};const el=document.createElement('div');el.className=`toast toast-${t}`;el.innerHTML=`<div class="toast-icon"><i class="fas ${icons[t]}"></i></div><span class="toast-msg">${m}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;document.getElementById('toastContainer').appendChild(el);setTimeout(()=>{el.classList.add('leaving');setTimeout(()=>el.remove(),200)},d);}
</script>
</body></html>
<?php exit; endif; ?>

<!-- ================================================================
     MAIN CLIENT DASHBOARD (client resolved)
     ================================================================ -->

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">CR</div>
    <div class="brand-text">
      <div class="brand-name">CIBIL Repair</div>
      <div class="brand-sub">Client Portal</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <div class="nav-item active" data-section="dashboard" data-tooltip="Dashboard"><i class="fas fa-home"></i><span class="nav-label">Dashboard</span></div>
    <div class="nav-item" data-section="creditHistory" data-tooltip="Credit History"><i class="fas fa-chart-line"></i><span class="nav-label">Credit History</span></div>

    <div class="nav-section-label">My Cases</div>
    <div class="nav-item" data-section="cases" data-tooltip="Cases"><i class="fas fa-briefcase"></i><span class="nav-label">All Cases</span></div>
    <div class="nav-item" data-section="disputes" data-tooltip="Disputes"><i class="fas fa-gavel"></i><span class="nav-label">Disputes</span></div>
    <div class="nav-item" data-section="timeline" data-tooltip="Timeline"><i class="fas fa-stream"></i><span class="nav-label">Case Timeline</span></div>

    <div class="nav-section-label">Finance</div>
    <div class="nav-item" data-section="payments" data-tooltip="Payments"><i class="fas fa-credit-card"></i><span class="nav-label">Payments</span></div>
    <div class="nav-item" data-section="invoices" data-tooltip="Invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></div>

    <div class="nav-section-label">Documents & Support</div>
    <div class="nav-item" data-section="documents" data-tooltip="Documents"><i class="fas fa-file-alt"></i><span class="nav-label">Documents</span></div>
    <div class="nav-item" data-section="tickets" data-tooltip="Tickets"><i class="fas fa-headset"></i><span class="nav-label">Support Tickets</span></div>
    <div class="nav-item" data-section="notifications" data-tooltip="Notifications"><i class="fas fa-bell"></i><span class="nav-label">Notifications</span></div>

    <?php if ($viewer_role === 'client'): ?>
    <div class="nav-section-label">Account</div>
    <div class="nav-item" data-section="profile" data-tooltip="Profile"><i class="fas fa-user-cog"></i><span class="nav-label">My Profile</span></div>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-details">
        <div class="user-name"><?= $cname ?></div>
        <div class="user-role">Client Account</div>
      </div>
    </div>
  </div>
</aside>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle">
  <i class="fas fa-chevron-left" id="sidebarToggleIcon"></i>
</button>

<!-- MAIN -->
<div class="main" id="main">

  <?php if ($viewer_role !== 'client'): ?>
  <div class="viewer-banner">
    <span>
      <?= $viewer_role === 'partner' ? '🤝 Partner View' : '🛡️ Admin View' ?> —
      Viewing client: <strong><?= $cname ?></strong> (<?= $cemail ?>)
    </span>
    <div style="display:flex;gap:12px;align-items:center;">
      <?php if ($viewer_role === 'partner'): ?>
      <a href="client-dashboard.php">← All My Clients</a>
      <a href="partner-dashboard.php" class="back-btn btn-sm" style="padding:5px 12px;border-radius:8px;background:rgba(255,255,255,0.15);color:#fff;border:none;font-size:12px;text-decoration:none;font-weight:600;">My Dashboard</a>
      <?php else: ?>
      <a href="client-dashboard.php">← All Clients</a>
      <a href="admin-dashboard.php" class="back-btn btn-sm" style="padding:5px 12px;border-radius:8px;background:rgba(255,255,255,0.15);color:#fff;border:none;font-size:12px;text-decoration:none;font-weight:600;">Admin Panel</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div>
        <div class="page-breadcrumb">CIBIL Repair — <?= $viewer_role === 'client' ? 'Client Portal' : ucfirst($viewer_role).' View' ?></div>
        <div class="page-title-top" id="pageTitle">Dashboard</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="clock-badge" id="liveClock">--:--:--</div>
      <div class="theme-toggle">
        <button class="theme-btn active" id="lightBtn" onclick="setTheme('light')"><i class="fas fa-sun"></i></button>
        <button class="theme-btn" id="darkBtn" onclick="setTheme('dark')"><i class="fas fa-moon"></i></button>
      </div>
      <div style="position:relative;">
        <button class="icon-btn" id="notifBtn" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="notif-badge" id="notifBadge">0</span>
        </button>
        <div class="notif-panel" id="notifPanel">
          <div class="notif-header">
            <h4><i class="fas fa-bell" style="color:var(--brand);margin-right:6px;"></i>Notifications</h4>
            <button class="notif-mark" onclick="markAllRead()">Mark all read</button>
          </div>
          <div class="notif-list" id="notifList">
            <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
              <i class="fas fa-check-circle" style="font-size:24px;color:var(--success);display:block;margin-bottom:8px;"></i>All caught up!
            </div>
          </div>
        </div>
      </div>
      <span style="font-size:13px;color:var(--text-secondary);"><?= $viewer_name ?></span>
      <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</button>
    </div>
  </div>

  <div class="content">

    <!-- ====== DASHBOARD ====== -->
    <div class="section active" id="dashboardSection">

      <!-- Credit Score Widget -->
      <div class="score-widget" id="scoreWidget">
        <div class="score-circle" id="scoreCircle">
          <div class="score-circle-val" id="scoreVal">—</div>
          <div class="score-circle-lbl">CIBIL</div>
        </div>
        <div class="score-info">
          <h2 id="scoreLabel">Your CIBIL Score</h2>
          <p id="scoreDesc">Loading your credit information…</p>
          <span class="score-change same" id="scoreChange"><i class="fas fa-minus"></i> No change</span>
        </div>
        <div class="score-gauge">
          <div style="font-size:11px;opacity:0.7;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Score Range</div>
          <div class="score-range-bar">
            <div class="score-indicator" id="scoreIndicator" style="left:40%;border-color:#fbbf24;"></div>
          </div>
          <div class="score-range-labels">
            <span>300 Poor</span><span>550 Average</span><span>750 Excellent</span><span>900</span>
          </div>
          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <div style="font-size:11px;opacity:0.7;">🏦 Bank Eligibility: <strong id="bankEligibility">—</strong></div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card green" onclick="showSection('cases')">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-briefcase"></i></div><span class="stat-change up" id="scTotalCases">Active</span></div>
          <div class="stat-value" id="stTotalCases">—</div>
          <div class="stat-label">Total Cases</div>
          <div class="progress-bar"><div class="progress-fill" id="pbCases" style="width:0%;background:var(--brand);"></div></div>
        </div>
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div><span class="stat-change up" id="scCompleted">Done</span></div>
          <div class="stat-value" id="stCompleted">—</div>
          <div class="stat-label">Cases Resolved</div>
          <div class="progress-bar"><div class="progress-fill" id="pbCompleted" style="width:0%;background:var(--brand);"></div></div>
        </div>
        <div class="stat-card amber" onclick="showSection('payments')">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-rupee-sign"></i></div><span class="stat-change neu">Paid</span></div>
          <div class="stat-value" id="stTotalSpent">—</div>
          <div class="stat-label">Total Paid</div>
          <div class="progress-bar"><div class="progress-fill" style="width:60%;background:#d97706;"></div></div>
        </div>
        <div class="stat-card red" onclick="showSection('payments')">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-clock"></i></div><span class="stat-change down">Due</span></div>
          <div class="stat-value" id="stPending">—</div>
          <div class="stat-label">Pending Payment</div>
          <div class="progress-bar"><div class="progress-fill" style="width:30%;background:#dc2626;"></div></div>
        </div>
        <div class="stat-card blue" onclick="showSection('documents')">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-file-alt"></i></div><span class="stat-change neu">Uploaded</span></div>
          <div class="stat-value" id="stDocCount">—</div>
          <div class="stat-label">Documents</div>
          <div class="progress-bar"><div class="progress-fill" style="width:50%;background:#2563eb;"></div></div>
        </div>
        <div class="stat-card purple" onclick="showSection('disputes')">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-gavel"></i></div><span class="stat-change neu">Filed</span></div>
          <div class="stat-value" id="stDisputes">—</div>
          <div class="stat-label">Active Disputes</div>
          <div class="progress-bar"><div class="progress-fill" style="width:40%;background:#7c3aed;"></div></div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-row">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-area"></i>CIBIL Score Trend</div>
            <div style="display:flex;gap:8px;">
              <button class="btn btn-ghost btn-sm" onclick="switchScoreChart('6m')">6M</button>
              <button class="btn btn-ghost btn-sm" onclick="switchScoreChart('1y')">1Y</button>
            </div>
          </div>
          <div class="card-body"><div style="position:relative;height:220px;"><canvas id="scoreChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i>Case Breakdown</div></div>
          <div class="card-body"><div style="position:relative;height:200px;"><canvas id="caseChart"></canvas></div></div>
        </div>
      </div>

      <!-- Repair Progress -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-tasks"></i>Credit Repair Progress</div></div>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
            <span id="progressLabel">Overall Progress</span>
            <span id="progressPct">0%</span>
          </div>
          <div class="progress-bar" style="height:10px;">
            <div class="progress-fill" id="repairProgress" style="width:0%;background:linear-gradient(90deg,var(--brand),#34d399);"></div>
          </div>
          <p id="progressMsg" style="font-size:12px;color:var(--text-muted);margin-top:8px;"></p>
        </div>
      </div>

      <!-- Recent Cases -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-briefcase"></i>Recent Cases</div>
          <button class="btn btn-ghost btn-sm" onclick="showSection('cases')">View All <i class="fas fa-arrow-right"></i></button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Case No</th><th>Service</th><th>Status</th><th>Amount</th><th>Date</th><th>Progress</th></tr></thead>
            <tbody id="recentCasesBody">
              <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== CREDIT HISTORY ====== -->
    <div class="section" id="creditHistorySection">
      <div class="kpi-row" id="creditKPIs">
        <div class="kpi-box"><div class="kpi-val" id="khCurrent">—</div><div class="kpi-lbl">Current Score</div></div>
        <div class="kpi-box"><div class="kpi-val" id="khStart">—</div><div class="kpi-lbl">Starting Score</div></div>
        <div class="kpi-box"><div class="kpi-val" id="khChange">—</div><div class="kpi-lbl">Total Improvement</div></div>
        <div class="kpi-box"><div class="kpi-val" id="khBest">—</div><div class="kpi-lbl">Best Score</div></div>
        <div class="kpi-box"><div class="kpi-val" id="khGoal">750</div><div class="kpi-lbl">Target Score</div></div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-chart-line"></i>CIBIL Score History</div>
          <button class="btn btn-success btn-sm" onclick="exportScoreHistory()"><i class="fas fa-download"></i>Export</button>
        </div>
        <div class="card-body"><div style="position:relative;height:280px;"><canvas id="fullScoreChart"></canvas></div></div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-history"></i>Score Log</div></div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Date</th><th>Score</th><th>Change</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody id="scoreLogBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== CASES ====== -->
    <div class="section" id="casesSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-briefcase"></i>All Cases</div>
          <button class="btn btn-success btn-sm" onclick="exportCasesExcel()"><i class="fas fa-file-excel"></i>Export</button>
        </div>
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input class="search-input" id="caseSearch" placeholder="Search case, service…" oninput="filterCases()">
          </div>
          <select class="form-select" id="caseStatusFilter" onchange="filterCases()" style="width:150px;padding:8px 12px;">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in-progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="table-wrap">
          <table id="casesTable">
            <thead><tr><th>#</th><th>Case No</th><th>Service</th><th>Status</th><th>Amount</th><th>Date Opened</th><th>Last Update</th><th>Progress</th></tr></thead>
            <tbody id="casesBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== DISPUTES ====== -->
    <div class="section" id="disputesSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-gavel"></i>Disputes & Objections</div>
          <?php if ($viewer_role === 'client'): ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('addDisputeModal')"><i class="fas fa-plus"></i>File Dispute</button>
          <?php endif; ?>
        </div>
        <div class="tab-bar">
          <button class="tab-btn active" onclick="switchTab(this,'dispActive')">Active</button>
          <button class="tab-btn" onclick="switchTab(this,'dispResolved')">Resolved</button>
          <button class="tab-btn" onclick="switchTab(this,'dispAll')">All</button>
        </div>
        <div class="tab-content active" id="dispActive">
          <div class="table-wrap">
            <table><thead><tr><th>#</th><th>Dispute ID</th><th>Bank/Lender</th><th>Issue Type</th><th>Filed</th><th>Status</th><th>Expected Resolution</th></tr></thead>
              <tbody id="dispActiveBody"></tbody>
            </table>
          </div>
        </div>
        <div class="tab-content" id="dispResolved">
          <div class="table-wrap">
            <table><thead><tr><th>#</th><th>Dispute ID</th><th>Bank/Lender</th><th>Issue Type</th><th>Resolution</th><th>Resolved On</th></tr></thead>
              <tbody id="dispResolvedBody"></tbody>
            </table>
          </div>
        </div>
        <div class="tab-content" id="dispAll">
          <div class="table-wrap">
            <table><thead><tr><th>#</th><th>Dispute ID</th><th>Bank/Lender</th><th>Issue</th><th>Filed</th><th>Status</th></tr></thead>
              <tbody id="dispAllBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== CASE TIMELINE ====== -->
    <div class="section" id="timelineSection">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-stream"></i>Case Activity Timeline</div></div>
        <div class="card-body" id="timelineBody" style="padding:0 20px;">
          <div class="empty-state"><div class="spinner"></div></div>
        </div>
      </div>
    </div>

    <!-- ====== PAYMENTS ====== -->
    <div class="section" id="paymentsSection">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-value" id="payTotalPaid">—</div>
          <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card red">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-clock"></i></div></div>
          <div class="stat-value" id="payPendingAmt">—</div>
          <div class="stat-label">Pending Amount</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-receipt"></i></div></div>
          <div class="stat-value" id="payInvoiceCount">—</div>
          <div class="stat-label">Total Invoices</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-credit-card"></i>Payment History</div>
          <div style="display:flex;gap:8px;">
            <?php if ($viewer_role === 'client'): ?>
            <button class="btn btn-primary btn-sm" onclick="openModal('makePaymentModal')"><i class="fas fa-rupee-sign"></i>Make Payment</button>
            <?php endif; ?>
            <button class="btn btn-success btn-sm" onclick="exportPaymentsExcel()"><i class="fas fa-file-excel"></i>Export</button>
          </div>
        </div>
        <div class="table-wrap">
          <table id="paymentsTable">
            <thead><tr><th>#</th><th>Transaction ID</th><th>Date</th><th>Service</th><th>Case No</th><th>Amount</th><th>Status</th><th>Invoice</th></tr></thead>
            <tbody id="paymentsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== INVOICES ====== -->
    <div class="section" id="invoicesSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-file-invoice"></i>Invoices</div>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Invoice No</th><th>Date</th><th>Service</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="invoicesBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== DOCUMENTS ====== -->
    <div class="section" id="documentsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-file-alt"></i>My Documents</div>
          <?php if ($viewer_role === 'client'): ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i>Upload Document</button>
          <?php endif; ?>
        </div>
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input class="search-input" id="docSearch" placeholder="Search documents…" oninput="filterDocs()">
          </div>
          <select class="form-select" id="docTypeFilter" onchange="filterDocs()" style="width:160px;padding:8px 12px;">
            <option value="">All Types</option>
            <option value="Aadhar">Aadhar</option>
            <option value="PAN">PAN Card</option>
            <option value="Bank Statement">Bank Statement</option>
            <option value="Income Proof">Income Proof</option>
            <option value="Credit Report">Credit Report</option>
            <option value="Loan NOC">Loan NOC</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Document Name</th><th>Type</th><th>Uploaded On</th><th>Size</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="docsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== SUPPORT TICKETS ====== -->
    <div class="section" id="ticketsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-headset"></i>Support Tickets</div>
          <?php if ($viewer_role === 'client'): ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('ticketModal')"><i class="fas fa-plus"></i>New Ticket</button>
          <?php endif; ?>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Ticket No</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Updated</th><th>Actions</th></tr></thead>
            <tbody id="ticketsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== NOTIFICATIONS CENTER ====== -->
    <div class="section" id="notificationsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-bell"></i>All Notifications</div>
          <button class="btn btn-ghost btn-sm" onclick="markAllRead()"><i class="fas fa-check-double"></i>Mark All Read</button>
        </div>
        <div id="notifCenterBody" style="padding:0;">
          <div class="empty-state"><div class="spinner"></div></div>
        </div>
      </div>
    </div>

    <!-- ====== PROFILE (client only) ====== -->
    <?php if ($viewer_role === 'client'): ?>
    <div class="section" id="profileSection">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-user"></i>Personal Information</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" id="profName" value="<?= $cname ?>"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="profEmail" value="<?= $cemail ?>" readonly></div>
              <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="profPhone" value="<?= $cphone ?>" placeholder="Mobile number"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Address</label><input class="form-input" id="profAddress" value="<?= $caddress ?>" placeholder="Street address"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">City</label><input class="form-input" id="profCity" value="<?= $ccity ?>" placeholder="City"></div>
              <div class="form-group"><label class="form-label">State</label><input class="form-input" id="profState" value="<?= $cstate ?>" placeholder="State"></div>
              <div class="form-group"><label class="form-label">Pincode</label><input class="form-input" id="profPin" value="<?= $cpin ?>" placeholder="Pincode"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">PAN Number</label><input class="form-input" id="profPAN" placeholder="ABCDE1234F"></div>
              <div class="form-group"><label class="form-label">Aadhar (last 4)</label><input class="form-input" id="profAadhar" placeholder="XXXX" maxlength="4"></div>
            </div>
            <button class="btn btn-primary" onclick="updateProfile()"><i class="fas fa-save"></i>Save Changes</button>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-lock"></i>Change Password</div></div>
          <div class="card-body">
            <div class="form-row"><div class="form-group"><label class="form-label">Current Password</label><input class="form-input" type="password" id="curPwd"></div></div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">New Password</label><input class="form-input" type="password" id="newPwd"></div>
              <div class="form-group"><label class="form-label">Confirm</label><input class="form-input" type="password" id="conPwd"></div>
            </div>
            <button class="btn btn-primary" onclick="changePassword()"><i class="fas fa-key"></i>Change Password</button>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i>Notification Preferences</div></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach(['Email on case update','SMS on payment due','Score improvement alerts','Document request alerts','Ticket replies'] as $pref): ?>
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;">
                <input type="checkbox" checked style="accent-color:var(--brand);width:16px;height:16px;"><?= h($pref) ?>
              </label>
              <?php endforeach; ?>
            </div>
            <button class="btn btn-primary" style="margin-top:16px;" onclick="toast('Preferences saved!','success')"><i class="fas fa-save"></i>Save Preferences</button>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-shield-alt"></i>Account Security</div></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:14px;">
              <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg-sunken);border-radius:var(--radius-md);">
                <div>
                  <div style="font-size:13px;font-weight:600;">Two-Factor Authentication</div>
                  <div style="font-size:12px;color:var(--text-muted);">Add extra security to your account</div>
                </div>
                <button class="btn btn-primary btn-sm" onclick="toast('2FA setup — coming soon','info')"><i class="fas fa-qrcode"></i>Setup 2FA</button>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg-sunken);border-radius:var(--radius-md);">
                <div>
                  <div style="font-size:13px;font-weight:600;">Login History</div>
                  <div style="font-size:12px;color:var(--text-muted);">View recent login activity</div>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="toast('Login history — coming soon','info')"><i class="fas fa-history"></i>View</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ================================================================
     MODALS
     ================================================================ -->

<!-- Upload Document -->
<div class="modal-overlay" id="uploadDocModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-upload"></i>Upload Document</span>
      <button class="modal-close" onclick="closeModal('uploadDocModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Document Name *</label><input class="form-input" id="docName" placeholder="e.g. Aadhar Card Front"></div>
        <div class="form-group">
          <label class="form-label">Document Type *</label>
          <select class="form-select" id="docType">
            <option value="Aadhar">Aadhar Card</option>
            <option value="PAN">PAN Card</option>
            <option value="Bank Statement">Bank Statement</option>
            <option value="Income Proof">Income Proof</option>
            <option value="Credit Report">Credit Report</option>
            <option value="Loan NOC">Loan NOC / Settlement Letter</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <div class="upload-area" onclick="document.getElementById('docFile').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:4px;">Click to choose file or drag & drop</p>
        <p style="font-size:11px;color:var(--text-muted);">Supports PDF, JPG, PNG — max 5MB</p>
        <input type="file" id="docFile" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="onDocFileSelected()">
        <p id="docFileName" style="font-size:12px;color:var(--brand);margin-top:8px;font-weight:600;"></p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('uploadDocModal')">Cancel</button>
      <button class="btn btn-primary" id="uploadDocBtn" onclick="uploadDocument()"><i class="fas fa-upload"></i>Upload</button>
    </div>
  </div>
</div>

<!-- File Dispute -->
<div class="modal-overlay" id="addDisputeModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-gavel"></i>File a Dispute</span>
      <button class="modal-close" onclick="closeModal('addDisputeModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Bank / Lender Name *</label><input class="form-input" id="dispBank" placeholder="e.g. HDFC Bank"></div>
        <div class="form-group">
          <label class="form-label">Issue Type *</label>
          <select class="form-select" id="dispType">
            <option>Written Off Entry</option>
            <option>Settled Entry</option>
            <option>Wrong Late Payment</option>
            <option>Duplicate Account</option>
            <option>Incorrect Personal Info</option>
            <option>Fraudulent Loan</option>
            <option>Other Error</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Account / Loan Number</label><input class="form-input" id="dispAccount" placeholder="Account number"></div>
        <div class="form-group"><label class="form-label">Amount in Dispute (₹)</label><input class="form-input" id="dispAmount" type="number" placeholder="e.g. 150000"></div>
      </div>
      <div class="form-group"><label class="form-label">Description *</label><textarea class="form-textarea" id="dispDesc" rows="4" placeholder="Describe the error or issue in detail…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addDisputeModal')">Cancel</button>
      <button class="btn btn-primary" onclick="fileDispute()"><i class="fas fa-paper-plane"></i>File Dispute</button>
    </div>
  </div>
</div>

<!-- Make Payment -->
<div class="modal-overlay" id="makePaymentModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-rupee-sign"></i>Make Payment</span>
      <button class="modal-close" onclick="closeModal('makePaymentModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:var(--brand-light);border:1px solid var(--brand-muted);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;font-size:13px;">
        Pending amount: <strong id="modalPendingAmt">—</strong>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Amount (₹) *</label><input class="form-input" id="payAmount" type="number" placeholder="Enter amount" min="100"></div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select class="form-select" id="payMethodSel">
            <option>UPI</option>
            <option>Credit Card</option>
            <option>Debit Card</option>
            <option>Net Banking</option>
            <option>NEFT/RTGS</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Select Case</label>
        <select class="form-select" id="payCaseSelect"></select>
      </div>
      <div class="form-group"><label class="form-label">Reference / UTR Number</label><input class="form-input" id="payRef" placeholder="Payment reference or UTR"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('makePaymentModal')">Cancel</button>
      <button class="btn btn-primary" id="paySubmitBtn" onclick="submitPayment()"><i class="fas fa-paper-plane"></i>Submit Payment</button>
    </div>
  </div>
</div>

<!-- Support Ticket -->
<div class="modal-overlay" id="ticketModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-ticket-alt"></i>New Support Ticket</span>
      <button class="modal-close" onclick="closeModal('ticketModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Subject *</label><input class="form-input" id="tSubject" placeholder="Brief description"></div>
        <div class="form-group">
          <label class="form-label">Priority</label>
          <select class="form-select" id="tPriority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Message *</label><textarea class="form-textarea" id="tMessage" rows="5" placeholder="Describe your issue in detail…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('ticketModal')">Cancel</button>
      <button class="btn btn-primary" onclick="createTicket()"><i class="fas fa-paper-plane"></i>Submit Ticket</button>
    </div>
  </div>
</div>

<!-- ================================================================
     TOAST
     ================================================================ -->
<div class="toast-container" id="toastContainer"></div>

<!-- ================================================================
     AI ASSISTANT
     ================================================================ -->
<div class="ai-panel" id="aiPanel">
  <div class="ai-header" id="aiHeader">
    <div class="ai-status-dot"></div>
    <div>
      <div class="ai-title">CIBIL AI Assistant</div>
      <div class="ai-subtitle">Powered by Claude</div>
    </div>
    <i class="fas fa-chevron-down ai-chevron" id="aiChevron"></i>
  </div>
  <div class="ai-body" id="aiBody">
    <div class="ai-messages" id="aiMessages">
      <div class="ai-msg bot">
        👋 Hi <?= $cname ?>! I'm your CIBIL AI assistant.<br><br>
        I can help with credit score tips, case updates, dispute guidance, and payment questions. What would you like to know?
      </div>
    </div>
    <div class="ai-chips">
      <span class="ai-chip" onclick="quickAsk('How can I improve my CIBIL score?')">📈 Improve Score</span>
      <span class="ai-chip" onclick="quickAsk('What is a good CIBIL score for a home loan?')">🏦 Loan Eligibility</span>
      <span class="ai-chip" onclick="quickAsk('How to dispute a wrong entry in CIBIL report?')">⚖️ Dispute Guide</span>
      <span class="ai-chip" onclick="quickAsk('How long does credit repair take?')">⏱️ Timeline</span>
      <span class="ai-chip" onclick="quickAsk('What are written-off accounts and how to clear them?')">📄 Written-Off</span>
    </div>
    <div class="ai-input-row">
      <input class="ai-input" id="aiInput" placeholder="Ask me anything about your credit…" onkeydown="if(event.key==='Enter') sendAI()">
      <button class="ai-send" onclick="sendAI()"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>
</div>

<!-- ================================================================
     JAVASCRIPT - Fully Integrated
     ================================================================ -->
<script>
// ── CONFIG ───────────────────────────────────────────────────────────
const API        = 'api/client/';
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const CLIENT_ID  = <?= $client_id ?>;
const VIEWER_ROLE= <?= json_encode($viewer_role) ?>;
const IS_CLIENT  = VIEWER_ROLE === 'client';

// ── XSS ESCAPE ───────────────────────────────────────────────────────
function esc(s){ if(s==null)return'';return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#x27;'}[c])); }

// ── CSRF FETCH ────────────────────────────────────────────────────────
async function apiFetch(url, opts={}) {
  opts.headers = { 'Content-Type':'application/json', 'X-CSRF-Token':CSRF, ...(opts.headers||{}) };
  opts.credentials = 'include';
  try {
    const r = await fetch(url, opts);
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.json();
  } catch(e) {
    console.error('API error:', url, e);
    return { success:false, error:e.message };
  }
}

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('clientTheme', t);
  document.getElementById('lightBtn').classList.toggle('active', t==='light');
  document.getElementById('darkBtn').classList.toggle('active', t==='dark');
  setTimeout(()=>{ Object.values(charts).forEach(c=>{ if(c) c.update(); }); }, 100);
}
(()=>{ setTheme(localStorage.getItem('clientTheme')||'light'); })();

// ── CLOCK ─────────────────────────────────────────────────────────────
(function tick() {
  const el = document.getElementById('liveClock');
  if(el) el.textContent = new Date().toLocaleTimeString('en-IN',{hour12:false});
  setTimeout(tick, 1000);
})();

// ── SIDEBAR ───────────────────────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const toggleIcon = document.getElementById('sidebarToggleIcon');
let sidebarCollapsed = localStorage.getItem('clientSidebarCollapsed') === 'true';
function applySidebar() {
  sidebar.classList.toggle('collapsed', sidebarCollapsed);
  toggleIcon.className = sidebarCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
  document.getElementById('main').style.marginLeft = sidebarCollapsed ? '64px' : 'var(--sidebar-w)';
}
applySidebar();
document.getElementById('sidebarToggle').onclick = () => {
  sidebarCollapsed = !sidebarCollapsed;
  localStorage.setItem('clientSidebarCollapsed', sidebarCollapsed);
  applySidebar();
};
document.querySelectorAll('.nav-item[data-section]').forEach(item => {
  item.addEventListener('click', () => {
    showSection(item.dataset.section);
    if(window.innerWidth < 900) sidebar.classList.remove('mobile-open');
  });
});

// ── SECTIONS ──────────────────────────────────────────────────────────
const sectionTitles = {
  dashboard:'Dashboard', creditHistory:'Credit Score History', cases:'All Cases',
  disputes:'Disputes & Objections', timeline:'Case Timeline',
  payments:'Payments', invoices:'Invoices',
  documents:'My Documents', tickets:'Support Tickets',
  notifications:'Notifications', profile:'My Profile'
};
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  const el = document.getElementById(name + 'Section');
  if(el) el.classList.add('active');
  document.getElementById('pageTitle').textContent = sectionTitles[name] || name;
  const nav = document.querySelector(`.nav-item[data-section="${name}"]`);
  if(nav) nav.classList.add('active');

  if(name === 'dashboard')      loadDashboard();
  if(name === 'creditHistory')  loadCreditHistory();
  if(name === 'cases')          loadCases();
  if(name === 'disputes')       loadDisputes();
  if(name === 'timeline')       loadTimeline();
  if(name === 'payments')       loadPayments();
  if(name === 'invoices')       loadInvoices();
  if(name === 'documents')      loadDocuments();
  if(name === 'tickets')        loadTickets();
  if(name === 'notifications')  loadNotifCenter();
  if(name === 'profile' && IS_CLIENT) loadProfile();
}

// ── TOAST ─────────────────────────────────────────────────────────────
function toast(msg, type='info', duration=3500) {
  const icons={success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle',warning:'fa-exclamation-triangle'};
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<div class="toast-icon"><i class="fas ${icons[type]}"></i></div><span class="toast-msg">${esc(msg)}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(()=>{ t.classList.add('leaving'); setTimeout(()=>t.remove(),200); }, duration);
}

// ── MODAL ─────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});
document.addEventListener('keydown', e => {
  if(e.key==='Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ── HELPERS ───────────────────────────────────────────────────────────
function statusBadge(s) {
  const map = {
    'pending':'badge-amber','in-progress':'badge-blue','completed':'badge-green',
    'closed':'badge-gray','paid':'badge-green','failed':'badge-red',
    'active':'badge-blue','resolved':'badge-green','open':'badge-amber','urgent':'badge-red'
  };
  return `<span class="badge ${map[(s||'').toLowerCase()]||'badge-gray'}">${esc(s||'—')}</span>`;
}

function scoreClass(score) {
  if(score >= 750) return 'excellent';
  if(score >= 650) return 'good';
  if(score >= 550) return 'average';
  return 'poor';
}

function scoreLabel(score) {
  if(score >= 750) return '✅ Excellent';
  if(score >= 650) return '📈 Good';
  if(score >= 550) return '⚠️ Average';
  return '🔴 Poor';
}

function bankEligibility(score) {
  if(score >= 750) return 'All major banks';
  if(score >= 700) return 'Most banks';
  if(score >= 650) return 'Selected banks';
  if(score >= 600) return 'NBFCs only';
  return 'Not eligible';
}

// ── CHARTS ────────────────────────────────────────────────────────────
const charts = {};
function destroyChart(id) { if(charts[id]){ charts[id].destroy(); delete charts[id]; } }
const isDark  = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridClr = () => isDark() ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
const txtClr  = () => isDark() ? '#94a3b8' : '#6b7280';

// ── DASHBOARD ─────────────────────────────────────────────────────────
let dashData = {};
async function loadDashboard() {
  try {
    const data = await apiFetch(`${API}get_client_dashboard.php?client_id=${CLIENT_ID}`);
    dashData = data.success ? data : {};
    const d = dashData;

    // Credit score widget
    const score    = d.current_score || 0;
    const prevScore= d.previous_score || score;
    const diff     = score - prevScore;
    const cls      = scoreClass(score);
    const circ     = document.getElementById('scoreCircle');
    circ.className = `score-circle ${cls}`;
    document.getElementById('scoreVal').textContent    = score || '—';
    document.getElementById('scoreLabel').textContent  = 'Your CIBIL Score';
    document.getElementById('scoreDesc').textContent   = score ? `${scoreLabel(score)} — Updated ${d.score_date || 'recently'}` : 'No score data yet';
    document.getElementById('bankEligibility').textContent = score ? bankEligibility(score) : '—';

    const chgEl = document.getElementById('scoreChange');
    if(diff > 0) {
      chgEl.className = 'score-change up';
      chgEl.innerHTML = `<i class="fas fa-arrow-up"></i> +${diff} points from last check`;
    } else if(diff < 0) {
      chgEl.className = 'score-change down';
      chgEl.innerHTML = `<i class="fas fa-arrow-down"></i> ${diff} points from last check`;
    } else {
      chgEl.className = 'score-change same';
      chgEl.innerHTML = '<i class="fas fa-minus"></i> No change';
    }

    // Score indicator on gauge
    if(score) {
      const pct = Math.max(0, Math.min(100, ((score - 300) / 600) * 100));
      document.getElementById('scoreIndicator').style.left = pct + '%';
      const indColor = cls==='excellent' ? '#34d399' : cls==='good' ? '#60a5fa' : cls==='average' ? '#fbbf24' : '#f87171';
      document.getElementById('scoreIndicator').style.borderColor = indColor;
    }

    // Stats
    const total = d.total_cases || 0;
    const comp  = d.completed_cases || 0;
    document.getElementById('stTotalCases').textContent = total;
    document.getElementById('stCompleted').textContent  = comp;
    document.getElementById('stTotalSpent').textContent = '₹' + (d.total_paid || 0).toLocaleString('en-IN');
    document.getElementById('stPending').textContent    = '₹' + (d.pending_amount || 0).toLocaleString('en-IN');
    document.getElementById('stDocCount').textContent   = d.document_count || 0;
    document.getElementById('stDisputes').textContent   = d.active_disputes || 0;
    document.getElementById('modalPendingAmt').textContent = '₹' + (d.pending_amount || 0).toLocaleString('en-IN');

    document.getElementById('pbCases').style.width     = Math.min(total, 100) + '%';
    document.getElementById('pbCompleted').style.width = total > 0 ? Math.round((comp/total)*100) + '%' : '0%';

    // Repair progress
    const pct = total > 0 ? Math.round((comp/total)*100) : 0;
    document.getElementById('repairProgress').style.width = pct + '%';
    document.getElementById('progressPct').textContent = pct + '%';
    document.getElementById('progressLabel').textContent = `${comp} of ${total} cases resolved`;
    document.getElementById('progressMsg').textContent = pct === 100 ? '🎉 All cases resolved! Your credit repair is complete.' :
      pct > 50 ? `Good progress! ${total-comp} more case${total-comp!==1?'s':''} to go.` :
      `Keep going — ${total-comp} case${total-comp!==1?'s':''} still in progress.`;

    // Recent cases
    const cases = d.recent_cases || [];
    document.getElementById('recentCasesBody').innerHTML = cases.length
      ? cases.map(c => `
        <tr>
          <td><strong>${esc(c.case_no)}</strong></td>
          <td>${esc(c.service_type||c.service||'—')}</td>
          <td>${statusBadge(c.status)}</td>
          <td>₹${Number(c.amount||0).toLocaleString('en-IN')}</td>
          <td>${c.created_at ? new Date(c.created_at).toLocaleDateString('en-IN') : '—'}</td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div style="flex:1;height:5px;background:var(--bg-sunken);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:${c.progress||0}%;background:var(--brand);border-radius:99px;"></div>
              </div>
              <span style="font-size:11px;color:var(--text-muted);">${c.progress||0}%</span>
            </div>
          </td>
        </tr>`).join('')
      : `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-briefcase"></i><p>No cases yet</p></div></td></tr>`;

    // Dashboard charts
    const hist = d.score_history || [];
    const labels = hist.map(h => h.month || new Date(h.date).toLocaleDateString('en-IN',{month:'short'}));
    const scores = hist.map(h => h.score);
    initDashCharts(labels, scores, d);

    // Populate case select for payment
    const caseList = d.open_cases || cases.filter(c=>c.status!=='completed');
    const sel = document.getElementById('payCaseSelect');
    if(sel) sel.innerHTML = '<option value="">Select case…</option>' + caseList.map(c=>`<option value="${c.case_no}">${esc(c.case_no)} — ${esc(c.service_type||c.service||'')}</option>`).join('');

  } catch(e) { console.error('Dashboard error:', e); }
}

function initDashCharts(labels, scores, d) {
  destroyChart('scoreChart'); destroyChart('caseChart');

  charts['scoreChart'] = new Chart(document.getElementById('scoreChart'), {
    type: 'line',
    data: {
      labels: labels.length ? labels : ['Jan','Feb','Mar','Apr','May','Jun'],
      datasets: [{ label:'CIBIL Score', data: scores.length ? scores : [650,660,670,685,700,715],
        borderColor:'#0d9e78', backgroundColor:'rgba(13,158,120,0.08)',
        fill:true, tension:0.4, pointRadius:4, pointHoverRadius:6 }]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ display:false } },
      scales:{ x:{ grid:{ color:gridClr() }, ticks:{ color:txtClr() } },
        y:{ grid:{ color:gridClr() }, ticks:{ color:txtClr() }, min:300, max:900 } }
    }
  });

  const completed = d.completed_cases || 0;
  const inProgress = d.in_progress_cases || 0;
  const pending = (d.total_cases||0) - completed - inProgress;
  charts['caseChart'] = new Chart(document.getElementById('caseChart'), {
    type: 'doughnut',
    data: {
      labels: ['Completed','In Progress','Pending'],
      datasets:[{ data:[completed, inProgress, Math.max(0,pending)], backgroundColor:['#059669','#2563eb','#d97706'], borderWidth:0 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'65%',
      plugins:{ legend:{ labels:{ color:txtClr(), font:{ size:11 } } } } }
  });
}

function switchScoreChart(mode) {
  toast('Loading '+mode+' score history…', 'info');
  loadDashboard();
}

// ── CREDIT HISTORY ────────────────────────────────────────────────────
async function loadCreditHistory() {
  const data = await apiFetch(`${API}get_score_history.php?client_id=${CLIENT_ID}`);
  const history = Array.isArray(data) ? data : (data.history || []);

  if(history.length) {
    const scores = history.map(h => h.score || 0);
    const current = scores[scores.length-1] || 0;
    const start   = scores[0] || 0;
    const best    = Math.max(...scores);
    const change  = current - start;

    document.getElementById('khCurrent').textContent = current;
    document.getElementById('khStart').textContent   = start;
    document.getElementById('khChange').textContent  = (change >= 0 ? '+' : '') + change;
    document.getElementById('khBest').textContent    = best;
  }

  // Full chart
  destroyChart('fullScoreChart');
  const labels = history.map(h => h.date ? new Date(h.date).toLocaleDateString('en-IN',{month:'short',year:'2-digit'}) : '—');
  const scores = history.map(h => h.score || 0);

  charts['fullScoreChart'] = new Chart(document.getElementById('fullScoreChart'), {
    type:'line',
    data:{ labels: labels.length ? labels : ['Jan','Feb','Mar'], datasets:[{
      label:'CIBIL Score', data: scores.length ? scores : [650,670,685],
      borderColor:'#0d9e78', backgroundColor:'rgba(13,158,120,0.06)',
      fill:true, tension:0.4, pointRadius:5, pointHoverRadius:8,
      pointBackgroundColor:'#0d9e78'
    }] },
    options:{ responsive:true, maintainAspectRatio:false,
      scales:{ x:{ grid:{color:gridClr()}, ticks:{color:txtClr()} }, y:{ grid:{color:gridClr()}, ticks:{color:txtClr()}, min:300, max:900 } },
      plugins:{ legend:{display:false} } }
  });

  // Score log table
  document.getElementById('scoreLogBody').innerHTML = history.length
    ? history.slice().reverse().map((h,i,arr) => {
        const prev = arr[i+1];
        const diff = prev ? (h.score - prev.score) : 0;
        return `<tr>
          <td>${history.length - i}</td>
          <td>${h.date ? new Date(h.date).toLocaleDateString('en-IN') : '—'}</td>
          <td><strong>${h.score}</strong></td>
          <td>${diff !== 0 ? `<span style="color:${diff>0?'var(--success)':'var(--danger)'};">${diff>0?'+':''}${diff}</span>` : '—'}</td>
          <td>${statusBadge(scoreClass(h.score))}</td>
          <td>${esc(h.notes || '—')}</td>
        </tr>`;
      }).join('')
    : `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-chart-line"></i><p>No score history available</p></div></td></tr>`;
}

function exportScoreHistory() {
  toast('Exporting score history…', 'info');
  const table = document.querySelector('#creditHistorySection table');
  if(!table) return;
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Score_History');
  XLSX.writeFile(wb, `cibil_score_history_${new Date().toISOString().slice(0,10)}.xlsx`);
  toast('Exported!', 'success');
}

// ── CASES ─────────────────────────────────────────────────────────────
let allCases = [];
async function loadCases() {
  document.getElementById('casesBody').innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>`;
  const data = await apiFetch(`${API}get_cases.php?client_id=${CLIENT_ID}`);
  allCases = Array.isArray(data) ? data : (data.cases || []);
  renderCases(allCases);
}

function renderCases(list) {
  document.getElementById('casesBody').innerHTML = list.length
    ? list.map((c,i) => `
      <tr>
        <td>${i+1}</td>
        <td><strong>${esc(c.case_no)}</strong></td>
        <td>${esc(c.service_type||c.service||'—')}</td>
        <td>${statusBadge(c.status)}</td>
        <td>₹${Number(c.amount||0).toLocaleString('en-IN')}</td>
        <td>${c.created_at ? new Date(c.created_at).toLocaleDateString('en-IN') : '—'}</td>
        <td>${c.updated_at ? new Date(c.updated_at).toLocaleDateString('en-IN') : '—'}</td>
        <td>
          <div style="display:flex;align-items:center;gap:6px;min-width:80px;">
            <div style="flex:1;height:5px;background:var(--bg-sunken);border-radius:99px;overflow:hidden;">
              <div style="height:100%;width:${c.progress||0}%;background:var(--brand);border-radius:99px;"></div>
            </div>
            <span style="font-size:11px;color:var(--text-muted);">${c.progress||0}%</span>
          </div>
        </td>
      </tr>`).join('')
    : `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-briefcase"></i><p>No cases found</p></div></td></tr>`;
}

function filterCases() {
  const s   = document.getElementById('caseSearch').value.toLowerCase();
  const st  = document.getElementById('caseStatusFilter').value;
  renderCases(allCases.filter(c => {
    const match = !s || (c.case_no+(c.service_type||c.service||'')).toLowerCase().includes(s);
    const stMatch = !st || (c.status||'').toLowerCase() === st;
    return match && stMatch;
  }));
}

function exportCasesExcel() {
  const table = document.getElementById('casesTable');
  if(!table) return;
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Cases');
  XLSX.writeFile(wb, `cases_${new Date().toISOString().slice(0,10)}.xlsx`);
  toast('Cases exported!', 'success');
}

// ── DISPUTES ──────────────────────────────────────────────────────────
let allDisputes = [];
async function loadDisputes() {
  const data = await apiFetch(`${API}get_disputes.php?client_id=${CLIENT_ID}`);
  allDisputes = Array.isArray(data) ? data : (data.disputes || []);
  const active   = allDisputes.filter(d => !['resolved','closed'].includes(d.status?.toLowerCase()));
  const resolved = allDisputes.filter(d => ['resolved','closed'].includes(d.status?.toLowerCase()));

  const row = (d,i) => `
    <tr>
      <td>${i+1}</td>
      <td><strong>${esc(d.dispute_id||('#DISP'+d.id))}</strong></td>
      <td>${esc(d.bank_name||'—')}</td>
      <td>${esc(d.issue_type||'—')}</td>
      <td>${d.filed_date ? new Date(d.filed_date).toLocaleDateString('en-IN') : '—'}</td>
      <td>${statusBadge(d.status||'active')}</td>
      <td>${d.expected_resolution ? new Date(d.expected_resolution).toLocaleDateString('en-IN') : '—'}</td>
    </tr>`;
  const resRow = (d,i) => `
    <tr>
      <td>${i+1}</td>
      <td>${esc(d.dispute_id||('#DISP'+d.id))}</td>
      <td>${esc(d.bank_name||'—')}</td>
      <td>${esc(d.issue_type||'—')}</td>
      <td>${esc(d.resolution||'—')}</td>
      <td>${d.resolved_date ? new Date(d.resolved_date).toLocaleDateString('en-IN') : '—'}</td>
    </tr>`;
  const allRow = (d,i) => `
    <tr>
      <td>${i+1}</td>
      <td>${esc(d.dispute_id||('#DISP'+d.id))}</td>
      <td>${esc(d.bank_name||'—')}</td>
      <td>${esc(d.issue_type||'—')}</td>
      <td>${d.filed_date ? new Date(d.filed_date).toLocaleDateString('en-IN') : '—'}</td>
      <td>${statusBadge(d.status||'active')}</td>
    </tr>`;

  document.getElementById('dispActiveBody').innerHTML   = active.length   ? active.map(row).join('')   : `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-gavel"></i><p>No active disputes</p></div></td></tr>`;
  document.getElementById('dispResolvedBody').innerHTML = resolved.length ? resolved.map(resRow).join('') : `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-check-circle"></i><p>No resolved disputes yet</p></div></td></tr>`;
  document.getElementById('dispAllBody').innerHTML      = allDisputes.length ? allDisputes.map(allRow).join('') : `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-gavel"></i><p>No disputes filed yet</p></div></td></tr>`;
}

async function fileDispute() {
  const bank = document.getElementById('dispBank').value.trim();
  const desc = document.getElementById('dispDesc').value.trim();
  if(!bank || !desc) { toast('Bank name and description are required', 'error'); return; }
  const data = await apiFetch(`${API}add_dispute.php`, {
    method:'POST',
    body: JSON.stringify({
      client_id: CLIENT_ID, bank_name: bank,
      issue_type: document.getElementById('dispType').value,
      account_number: document.getElementById('dispAccount').value,
      amount: document.getElementById('dispAmount').value,
      description: desc
    })
  });
  if(data.success) {
    toast('Dispute filed successfully!', 'success');
    closeModal('addDisputeModal');
    ['dispBank','dispAccount','dispAmount','dispDesc'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
    loadDisputes(); loadDashboard();
  } else { toast(data.error || 'Failed to file dispute', 'error'); }
}

// ── TIMELINE ──────────────────────────────────────────────────────────
async function loadTimeline() {
  const data = await apiFetch(`${API}get_case_timeline.php?client_id=${CLIENT_ID}`);
  const events = Array.isArray(data) ? data : (data.events || []);
  const container = document.getElementById('timelineBody');

  if(!events.length) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-stream"></i><p>No timeline events yet</p></div>';
    return;
  }

  const statusColors = {
    'opened':'#2563eb','updated':'#d97706','completed':'#059669',
    'document':'#7c3aed','payment':'#d97706','note':'#9ca3af','dispute':'#dc2626'
  };
  container.innerHTML = `<div style="padding:20px;">` + events.map((ev, i) => {
    const color = statusColors[ev.type] || '#9ca3af';
    return `
      <div style="display:flex;gap:16px;margin-bottom:${i < events.length-1 ? '0' : '0'};padding-bottom:20px;position:relative;">
        ${i < events.length-1 ? `<div style="position:absolute;left:15px;top:32px;bottom:0;width:2px;background:var(--border);"></div>` : ''}
        <div style="width:32px;height:32px;border-radius:50%;background:${color};display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1;">
          <i class="fas fa-${ev.icon||'circle'}" style="color:#fff;font-size:12px;"></i>
        </div>
        <div style="flex:1;padding-top:4px;">
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);">${esc(ev.title||'Update')}</div>
          <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">${esc(ev.description||'')}</div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">${ev.date ? new Date(ev.date).toLocaleString('en-IN') : '—'} · ${esc(ev.case_no||'')}</div>
        </div>
        <div>${statusBadge(ev.type||'update')}</div>
      </div>`;
  }).join('') + `</div>`;
}

// ── PAYMENTS ──────────────────────────────────────────────────────────
let allPayments = [];
async function loadPayments() {
  const data = await apiFetch(`${API}get_payments.php?client_id=${CLIENT_ID}`);
  allPayments = Array.isArray(data) ? data : (data.payments || []);

  const totalPaid  = allPayments.filter(p=>['paid','completed'].includes(p.status?.toLowerCase())).reduce((s,p)=>s+Number(p.amount||0),0);
  const pending    = allPayments.filter(p=>p.status?.toLowerCase()==='pending').reduce((s,p)=>s+Number(p.amount||0),0);
  const invoiceCount = allPayments.filter(p=>['paid','completed'].includes(p.status?.toLowerCase())).length;

  document.getElementById('payTotalPaid').textContent   = '₹' + totalPaid.toLocaleString('en-IN');
  document.getElementById('payPendingAmt').textContent  = '₹' + pending.toLocaleString('en-IN');
  document.getElementById('payInvoiceCount').textContent= invoiceCount;

  document.getElementById('paymentsBody').innerHTML = allPayments.length
    ? allPayments.map((p,i) => `
      <tr>
        <td>${i+1}</td>
        <td><strong>${esc(p.transaction_id||p.id||'—')}</strong></td>
        <td>${p.payment_date||p.created_at ? new Date(p.payment_date||p.created_at).toLocaleDateString('en-IN') : '—'}</td>
        <td>${esc(p.service_name||p.service||'—')}</td>
        <td>${esc(p.case_number||p.case_no||'—')}</td>
        <td><strong>₹${Number(p.amount||0).toLocaleString('en-IN')}</strong></td>
        <td>${statusBadge(p.status)}</td>
        <td>${['paid','completed'].includes(p.status?.toLowerCase())
          ? `<button class="btn btn-ghost btn-xs" onclick="downloadInvoice('${esc(p.transaction_id||p.id)}')"><i class="fas fa-download"></i>Invoice</button>`
          : '—'}</td>
      </tr>`).join('')
    : `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-credit-card"></i><p>No payment records yet</p></div></td></tr>`;
}

function exportPaymentsExcel() {
  const table = document.getElementById('paymentsTable');
  if(!table) return;
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Payments');
  XLSX.writeFile(wb, `payments_${new Date().toISOString().slice(0,10)}.xlsx`);
  toast('Payments exported!', 'success');
}

function downloadInvoice(txnId) { toast(`Invoice ${txnId} — download feature coming soon`, 'info'); }

async function submitPayment() {
  const amount = parseFloat(document.getElementById('payAmount').value);
  if(!amount || amount < 100) { toast('Please enter a valid amount (min ₹100)', 'error'); return; }
  const btn = document.getElementById('paySubmitBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Processing…';
  const data = await apiFetch(`${API}add_payment.php`, {
    method:'POST',
    body: JSON.stringify({
      client_id: CLIENT_ID, amount,
      method: document.getElementById('payMethodSel').value,
      case_no: document.getElementById('payCaseSelect').value,
      reference: document.getElementById('payRef').value
    })
  });
  btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i>Submit Payment';
  if(data.success) {
    toast('Payment recorded! Awaiting verification.', 'success');
    closeModal('makePaymentModal');
    document.getElementById('payAmount').value = '';
    document.getElementById('payRef').value = '';
    loadPayments(); loadDashboard();
  } else { toast(data.error || 'Payment submission failed', 'error'); }
}

// ── INVOICES ──────────────────────────────────────────────────────────
async function loadInvoices() {
  const data = await apiFetch(`${API}get_invoices.php?client_id=${CLIENT_ID}`);
  const invoices = Array.isArray(data) ? data : (data.invoices || []);
  document.getElementById('invoicesBody').innerHTML = invoices.length
    ? invoices.map((inv,i) => `
      <tr>
        <td>${i+1}</td>
        <td><strong>${esc(inv.invoice_no||('#INV'+inv.id))}</strong></td>
        <td>${inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('en-IN') : '—'}</td>
        <td>${esc(inv.service_name||inv.service||'—')}</td>
        <td><strong>₹${Number(inv.amount||0).toLocaleString('en-IN')}</strong></td>
        <td>${statusBadge(inv.status||'issued')}</td>
        <td>
          <button class="btn btn-ghost btn-xs" onclick="downloadInvoice('${esc(inv.invoice_no||inv.id)}')"><i class="fas fa-download"></i>Download</button>
          <button class="btn btn-ghost btn-xs" onclick="toast('Invoice preview — coming soon','info')"><i class="fas fa-eye"></i>View</button>
        </td>
      </tr>`).join('')
    : `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-invoice"></i><p>No invoices yet</p></div></td></tr>`;
}

// ── DOCUMENTS ─────────────────────────────────────────────────────────
let allDocs = [];
async function loadDocuments() {
  const data = await apiFetch(`${API}get_documents.php?client_id=${CLIENT_ID}`);
  allDocs = Array.isArray(data) ? data : (data.documents || []);
  renderDocs(allDocs);
}

function renderDocs(list) {
  document.getElementById('docsBody').innerHTML = list.length
    ? list.map((d,i) => `
      <tr>
        <td>${i+1}</td>
        <td><i class="fas fa-file-pdf" style="color:var(--danger);margin-right:6px;"></i><strong>${esc(d.document_name||d.name)}</strong></td>
        <td><span class="badge badge-gray">${esc(d.document_type||d.type||'—')}</span></td>
        <td>${d.uploaded_at||d.uploaded ? new Date(d.uploaded_at||d.uploaded).toLocaleDateString('en-IN') : '—'}</td>
        <td>${esc(d.file_size||'—')}</td>
        <td>${statusBadge(d.status||'pending')}</td>
        <td style="white-space:nowrap;">
          <button class="btn btn-ghost btn-xs" onclick="viewDocument(${d.id})"><i class="fas fa-eye"></i>View</button>
          ${IS_CLIENT ? `<button class="btn btn-danger btn-xs" onclick="deleteDocument(${d.id})"><i class="fas fa-trash"></i></button>` : ''}
        </td>
      </tr>`).join('')
    : `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No documents uploaded yet</p></div></td></tr>`;
}

function filterDocs() {
  const s  = document.getElementById('docSearch').value.toLowerCase();
  const tp = document.getElementById('docTypeFilter').value;
  renderDocs(allDocs.filter(d => {
    const match = !s || ((d.document_name||d.name||'')+(d.document_type||d.type||'')).toLowerCase().includes(s);
    const tMatch = !tp || (d.document_type||d.type) === tp;
    return match && tMatch;
  }));
}

function onDocFileSelected() {
  const file = document.getElementById('docFile').files[0];
  if(file) document.getElementById('docFileName').textContent = '📎 ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
}

async function uploadDocument() {
  if(!IS_CLIENT) { toast('Only the client can upload documents', 'error'); return; }
  const name = document.getElementById('docName').value.trim();
  const file = document.getElementById('docFile').files[0];
  if(!name) { toast('Please enter a document name', 'error'); return; }

  const btn = document.getElementById('uploadDocBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Uploading…';

  const formData = new FormData();
  formData.append('client_id', CLIENT_ID);
  formData.append('document_name', name);
  formData.append('document_type', document.getElementById('docType').value);
  if(file) formData.append('document', file);

  try {
    const r = await fetch(`${API}upload_document.php`, {
      method:'POST', credentials:'include',
      headers:{ 'X-CSRF-Token': CSRF },
      body: formData
    });
    const data = await r.json();
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-upload"></i>Upload';
    if(data.success) {
      toast('Document uploaded!', 'success');
      closeModal('uploadDocModal');
      document.getElementById('docName').value = '';
      document.getElementById('docFileName').textContent = '';
      document.getElementById('docFile').value = '';
      loadDocuments(); loadDashboard();
    } else { toast(data.error || 'Upload failed', 'error'); }
  } catch(e) {
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-upload"></i>Upload';
    toast('Upload error: ' + e.message, 'error');
  }
}

function viewDocument(id) { toast(`Document viewer — coming soon (ID: ${id})`, 'info'); }

async function deleteDocument(id) {
  if(!confirm('Delete this document? This cannot be undone.')) return;
  const data = await apiFetch(`${API}delete_document.php`, { method:'POST', body: JSON.stringify({ document_id:id, client_id:CLIENT_ID }) });
  if(data.success) { toast('Document deleted', 'success'); loadDocuments(); loadDashboard(); }
  else { toast(data.error || 'Delete failed', 'error'); }
}

// ── TICKETS ───────────────────────────────────────────────────────────
async function loadTickets() {
  const data = await apiFetch(`${API}get_tickets.php?client_id=${CLIENT_ID}`);
  const tickets = Array.isArray(data) ? data : (data.tickets || []);
  document.getElementById('ticketsBody').innerHTML = tickets.length
    ? tickets.map((t,i) => `
      <tr>
        <td>${i+1}</td>
        <td>#TKT${t.id}</td>
        <td><strong>${esc(t.subject)}</strong></td>
        <td><span class="badge ${t.priority==='urgent'?'badge-red':t.priority==='high'?'badge-amber':'badge-gray'}">${esc(t.priority)}</span></td>
        <td>${statusBadge(t.status||'open')}</td>
        <td>${t.created_at ? new Date(t.created_at).toLocaleDateString('en-IN') : '—'}</td>
        <td>${t.updated_at ? new Date(t.updated_at).toLocaleDateString('en-IN') : '—'}</td>
        <td><button class="btn btn-ghost btn-xs" onclick="toast('Ticket view — coming soon','info')"><i class="fas fa-eye"></i>View</button></td>
      </tr>`).join('')
    : `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-headset"></i><p>No support tickets yet</p></div></td></tr>`;
}

async function createTicket() {
  if(!IS_CLIENT) { toast('Only the client can create tickets', 'error'); return; }
  const subject = document.getElementById('tSubject').value.trim();
  const message = document.getElementById('tMessage').value.trim();
  if(!subject || !message) { toast('Subject and message required', 'error'); return; }
  const data = await apiFetch(`${API}create_ticket.php`, {
    method:'POST',
    body: JSON.stringify({ client_id:CLIENT_ID, subject, message, priority: document.getElementById('tPriority').value })
  });
  if(data.success) {
    toast('Ticket submitted!', 'success');
    closeModal('ticketModal');
    document.getElementById('tSubject').value = '';
    document.getElementById('tMessage').value = '';
    loadTickets();
  } else { toast(data.error || 'Failed', 'error'); }
}

// ── NOTIFICATIONS ─────────────────────────────────────────────────────
document.getElementById('notifBtn').addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('notifPanel').classList.toggle('open');
});
document.addEventListener('click', () => document.getElementById('notifPanel').classList.remove('open'));
document.getElementById('notifPanel').addEventListener('click', e => e.stopPropagation());

function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
  document.getElementById('notifBadge').style.display = 'none';
  toast('All notifications marked as read', 'success');
}

async function loadNotifications() {
  try {
    const data = await apiFetch(`${API}get_notifications.php?client_id=${CLIENT_ID}`);
    if(!data.success) return;
    const badge = document.getElementById('notifBadge');
    const unread = data.unread_count || 0;
    if(unread > 0) { badge.style.display='inline-block'; badge.textContent = unread > 9 ? '9+' : unread; }
    else badge.style.display='none';
    if(data.notifications && data.notifications.length) {
      document.getElementById('notifList').innerHTML = data.notifications.map(n => `
        <div class="notif-item${n.is_read ? '' : ' unread'}">
          <div class="notif-dot"></div>
          <div>
            <div class="notif-title">${esc(n.title)}</div>
            <div class="notif-msg">${esc(n.message)}</div>
            <div class="notif-time">${new Date(n.created_at).toLocaleString('en-IN')}</div>
          </div>
        </div>`).join('');
    }
  } catch(e) {}
}
setInterval(loadNotifications, 30000);

async function loadNotifCenter() {
  const data = await apiFetch(`${API}get_notifications.php?client_id=${CLIENT_ID}&limit=50`);
  const notifs = data.notifications || [];
  const container = document.getElementById('notifCenterBody');
  if(!notifs.length) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications yet</p></div>';
    return;
  }
  container.innerHTML = notifs.map(n => `
    <div class="notif-item${n.is_read ? '' : ' unread'}" style="cursor:default;">
      <div class="notif-dot"></div>
      <div style="flex:1;">
        <div class="notif-title">${esc(n.title)}</div>
        <div class="notif-msg">${esc(n.message)}</div>
        <div class="notif-time">${new Date(n.created_at).toLocaleString('en-IN')}</div>
      </div>
      ${!n.is_read ? '<span class="badge badge-brand" style="font-size:9px;">NEW</span>' : ''}
    </div>`).join('');
}

// ── PROFILE ───────────────────────────────────────────────────────────
async function loadProfile() {
  if(!IS_CLIENT) return;
  const data = await apiFetch(`${API}get_profile.php?client_id=${CLIENT_ID}`);
  const p = data.profile || data;
  if(p.phone)   document.getElementById('profPhone').value   = p.phone   || '';
  if(p.address) document.getElementById('profAddress').value = p.address || '';
  if(p.city)    document.getElementById('profCity').value    = p.city    || '';
  if(p.state)   document.getElementById('profState').value   = p.state   || '';
  if(p.pincode) document.getElementById('profPin').value     = p.pincode || '';
  if(p.pan)     document.getElementById('profPAN').value     = p.pan     || '';
}

async function updateProfile() {
  if(!IS_CLIENT) return;
  const data = await apiFetch(`${API}update_profile.php`, {
    method:'POST',
    body: JSON.stringify({
      client_id: CLIENT_ID,
      name:    document.getElementById('profName').value,
      phone:   document.getElementById('profPhone').value,
      address: document.getElementById('profAddress').value,
      city:    document.getElementById('profCity').value,
      state:   document.getElementById('profState').value,
      pincode: document.getElementById('profPin').value,
      pan:     document.getElementById('profPAN').value
    })
  });
  if(data.success) { toast('Profile updated!', 'success'); }
  else { toast(data.error || 'Update failed', 'error'); }
}

async function changePassword() {
  if(!IS_CLIENT) return;
  const cur = document.getElementById('curPwd').value;
  const nw  = document.getElementById('newPwd').value;
  const cn  = document.getElementById('conPwd').value;
  if(!cur || !nw) { toast('Please fill all password fields', 'error'); return; }
  if(nw !== cn) { toast('New passwords do not match', 'error'); return; }
  if(nw.length < 8) { toast('Password must be at least 8 characters', 'error'); return; }
  const data = await apiFetch(`${API}change_password.php`, {
    method:'POST',
    body: JSON.stringify({ client_id:CLIENT_ID, current_password:cur, new_password:nw })
  });
  if(data.success) {
    toast('Password changed! Logging out…', 'success');
    setTimeout(()=>{ window.location.href='logout.php'; }, 2000);
  } else { toast(data.error || 'Password change failed', 'error'); }
}

// ── TAB HELPER ────────────────────────────────────────────────────────
function switchTab(btn, targetId) {
  const bar = btn.closest('.tab-bar');
  bar.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  btn.closest('.card').querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.getElementById(targetId)?.classList.add('active');
}

// ── AI ASSISTANT ──────────────────────────────────────────────────────
let aiCollapsed = false;
document.getElementById('aiHeader').addEventListener('click', () => {
  aiCollapsed = !aiCollapsed;
  document.getElementById('aiPanel').classList.toggle('mini', aiCollapsed);
  document.getElementById('aiChevron').className = aiCollapsed ? 'fas fa-chevron-up ai-chevron' : 'fas fa-chevron-down ai-chevron';
});

function quickAsk(q) { document.getElementById('aiInput').value = q; sendAI(); }

function aiAddMsg(text, role) {
  const el = document.createElement('div');
  el.className = `ai-msg ${role}`;
  el.innerHTML = role === 'bot' ? text.replace(/\n/g,'<br>') : esc(text);
  document.getElementById('aiMessages').appendChild(el);
  document.getElementById('aiMessages').scrollTop = 99999;
  return el;
}

async function sendAI() {
  const input = document.getElementById('aiInput');
  const msg = input.value.trim(); if(!msg) return;
  aiAddMsg(msg, 'user'); input.value = '';
  if(aiCollapsed) { aiCollapsed=false; document.getElementById('aiPanel').classList.remove('mini'); }
  const thinking = aiAddMsg('Thinking…', 'bot');

  const score = document.getElementById('scoreVal').textContent;
  const cases = document.getElementById('stTotalCases').textContent;
  const ctx = `You are a helpful AI assistant for a CIBIL Repair client dashboard.
Client: <?= $cname ?>
CIBIL Score: ${score}
Total Cases: ${cases}
Viewer Role: <?= $viewer_role ?>

Help with: credit score improvement, dispute guidance, case understanding, payment questions, CIBIL report errors, loan eligibility.
Be practical, empathetic, concise. Target Indian users. No markdown, plain text only.`;

  try {
    const resp = await fetch('https://api.anthropic.com/v1/messages', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ model:'claude-sonnet-4-20250514', max_tokens:500, system:ctx, messages:[{role:'user',content:msg}] })
    });
    const data = await resp.json();
    thinking.remove();
    aiAddMsg(data.content?.[0]?.text || 'Could not get a response. Please try again.', 'bot');
  } catch(e) {
    thinking.remove();
    aiAddMsg('Connection error. Please check your network.', 'bot');
  }
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').addEventListener('click', () => {
  if(confirm('Are you sure you want to log out?')) window.location.href = 'logout.php';
});

// Keyboard shortcuts
document.addEventListener('keydown', e => {
  if(e.altKey && e.key === 'd') showSection('dashboard');
  if(e.altKey && e.key === 'c') showSection('cases');
  if(e.altKey && e.key === 'p') showSection('payments');
});

// ── INIT ──────────────────────────────────────────────────────────────
(async function init() {
  showSection('dashboard');
  await loadNotifications();
})();
</script>
</body>
</html>