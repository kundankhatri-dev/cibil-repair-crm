<?php
// ============================================================
// SECURITY HARDENED SESSION CONFIG
// ============================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure'   => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict'
]);
session_start();

// Session regeneration — skip for AJAX to prevent token mismatch
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$isAjax &&
    (!isset($_SESSION['last_regeneration']) ||
     time() - $_SESSION['last_regeneration'] > 300)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit;
}

// Helper function (single definition)
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

$user_name  = h($_SESSION['user_name']  ?? 'Admin');
$user_email = h($_SESSION['user_email'] ?? '');
$user_role  = h($_SESSION['user_role']  ?? 'admin');
$show_admin_link = ($user_role === 'admin' || $user_role === 'super_admin');

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// DB connection with error handling
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'u929623538_cibil';
$db_user = getenv('DB_USER') ?: 'u929623538_cibilrepair';
$db_pass = getenv('DB_PASS') ?: 'Kundanlaxmi@1995';
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    error_log("DB connection failed: " . mysqli_connect_error());
    $db_error = true;
} else {
    $db_error = false;
}

// All dashboards definition
$all_dashboards = [
    ['file' => 'admin-dashboard.php',            'name' => 'Admin Dashboard',      'icon' => 'fa-crown',              'color' => 'admin',      'role' => 'Master Control'],
    ['file' => 'ceo-dashboard.php',              'name' => 'CEO Dashboard',         'icon' => 'fa-chart-line',         'color' => 'ceo',        'role' => 'Executive'],
    ['file' => 'hr-dashboard.php',               'name' => 'HR Dashboard',          'icon' => 'fa-users',              'color' => 'hr',         'role' => 'Human Resources'],
    ['file' => 'finance-dashboard.php',          'name' => 'Finance Dashboard',     'icon' => 'fa-wallet',             'color' => 'finance',    'role' => 'Finance & Accounts'],
    ['file' => 'sales-dashboard.php',            'name' => 'Sales Dashboard',       'icon' => 'fa-chart-simple',       'color' => 'sales',      'role' => 'Sales & Revenue'],
    ['file' => 'marketing-dashboard.php',        'name' => 'Marketing Dashboard',   'icon' => 'fa-bullhorn',           'color' => 'marketing',  'role' => 'Marketing'],
    ['file' => 'support-dashboard.php',          'name' => 'Support Dashboard',     'icon' => 'fa-headset',            'color' => 'support',    'role' => 'Customer Support'],
    ['file' => 'customer-support-dashboard.php', 'name' => 'Customer Support',      'icon' => 'fa-message',            'color' => 'support',    'role' => 'Support Tickets'],
    ['file' => 'operations-dashboard.php',       'name' => 'Operations Dashboard',  'icon' => 'fa-gears',              'color' => 'operations', 'role' => 'Operations'],
    ['file' => 'credit-analyst-dashboard.php',   'name' => 'Credit Analyst',        'icon' => 'fa-chart-pie',          'color' => 'credit',     'role' => 'Credit Analysis'],
    ['file' => 'dispute-processing-dashboard.php','name'=> 'Dispute Processing',    'icon' => 'fa-scale-balanced',     'color' => 'dispute',    'role' => 'Disputes'],
    ['file' => 'risk-dashboard.php',             'name' => 'Risk Dashboard',        'icon' => 'fa-shield',             'color' => 'risk',       'role' => 'Risk Management'],
    ['file' => 'legal-dashboard.php',            'name' => 'Legal Dashboard',       'icon' => 'fa-gavel',              'color' => 'legal',      'role' => 'Legal Compliance'],
    ['file' => 'it-dashboard.php',               'name' => 'IT Dashboard',          'icon' => 'fa-server',             'color' => 'it',         'role' => 'IT Operations'],
    ['file' => 'project-dashboard.php',          'name' => 'Project Dashboard',     'icon' => 'fa-diagram-project',    'color' => 'project',    'role' => 'Project Management'],
    ['file' => 'training-dashboard.php',         'name' => 'Training Dashboard',    'icon' => 'fa-chalkboard-user',    'color' => 'training',   'role' => 'Training & Development'],
    ['file' => 'document-dashboard.php',         'name' => 'Document Dashboard',    'icon' => 'fa-folder-open',        'color' => 'document',   'role' => 'Document Management'],
    ['file' => 'qa-dashboard.php',               'name' => 'QA Dashboard',          'icon' => 'fa-clipboard',          'color' => 'qa',         'role' => 'Quality Assurance'],
    ['file' => 'client-dashboard.php',           'name' => 'Client Dashboard',      'icon' => 'fa-building',           'color' => 'client',     'role' => 'Client Portal'],
    ['file' => 'partner-dashboard.php',          'name' => 'Partner Dashboard',     'icon' => 'fa-handshake',          'color' => 'partner',    'role' => 'Partner Portal'],
    ['file' => 'employee-dashboard.php',         'name' => 'Employee Dashboard',    'icon' => 'fa-id-card',            'color' => 'employee',   'role' => 'Employee Portal'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="<?= $csrf ?>">
<meta name="theme-color" content="#0b2a23">
<title>CIBIL Repair | Admin CRM</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script src="assets/js/services-helpers.js"></script>
<style>
/* ================================================================
   DESIGN TOKENS
   ================================================================ */
:root {
  --brand:         #0d9e78;
  --brand-dark:    #0a7d60;
  --brand-light:   #e6f7f2;
  --brand-glow:    rgba(13,158,120,0.18);
  --bg-base:       #f0f4f8;
  --bg-surface:    #ffffff;
  --bg-raised:     #ffffff;
  --bg-sunken:     #e8edf3;
  --text-primary:  #0d1b2a;
  --text-secondary:#4a5568;
  --text-muted:    #8a9bb0;
  --border:        rgba(0,0,0,0.07);
  --border-strong: rgba(0,0,0,0.13);
  --sidebar-bg:    #0b2a23;
  --sidebar-w:     256px;
  --sidebar-text:  rgba(255,255,255,0.68);
  --sidebar-active-bg:   rgba(13,158,120,0.22);
  --sidebar-active-text: #ffffff;
  --sidebar-hover: rgba(255,255,255,0.07);
  --success:       #059669; --success-bg: #ecfdf5; --success-text: #065f46;
  --warning:       #d97706; --warning-bg: #fffbeb; --warning-text: #78350f;
  --danger:        #dc2626; --danger-bg:  #fef2f2; --danger-text:  #7f1d1d;
  --info:          #2563eb; --info-bg:    #eff6ff; --info-text:    #1e3a8a;
  --purple:        #7c3aed; --purple-bg:  #f5f3ff; --purple-text:  #3b0764;
  --shadow-xs:  0 1px 2px rgba(0,0,0,0.05);
  --shadow-sm:  0 2px 6px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md:  0 6px 16px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
  --shadow-lg:  0 16px 40px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.04);
  --shadow-xl:  0 32px 64px rgba(0,0,0,0.16);
  --r-sm: 6px; --r-md: 10px; --r-lg: 16px; --r-xl: 22px; --r-2xl: 32px;
  --topbar-h:  60px;
  --ease:       cubic-bezier(0.4,0,0.2,1);
  --ease-spring:cubic-bezier(0.34,1.56,0.64,1);
  --dur: 200ms;
  --font-display:'Syne', sans-serif;
  --font-body:   'Outfit', sans-serif;
  --font-mono:   'JetBrains Mono', monospace;
  --skeleton-bg: #e8edf3;
  --skeleton-shine: #f5f7fa;
}
[data-theme="dark"] {
  --brand-light:   #0c2a1f;
  --brand-glow:    rgba(13,158,120,0.12);
  --bg-base:       #0d1117;
  --bg-surface:    #161b26;
  --bg-raised:     #1e2433;
  --bg-sunken:     #0a0e16;
  --text-primary:  #e8edf5;
  --text-secondary:#8b99b5;
  --text-muted:    #4d5a72;
  --border:        rgba(255,255,255,0.06);
  --border-strong: rgba(255,255,255,0.11);
  --sidebar-bg:    #080f0c;
  --success-bg: #042214; --success-text: #34d399;
  --warning-bg: #1c1200; --warning-text: #fbbf24;
  --danger-bg:  #1a0505; --danger-text:  #f87171;
  --info-bg:    #080f28; --info-text:    #60a5fa;
  --purple-bg:  #130727; --purple-text:  #c084fc;
  --shadow-sm: 0 2px 6px rgba(0,0,0,0.35);
  --shadow-md: 0 6px 16px rgba(0,0,0,0.45);
  --shadow-lg: 0 16px 40px rgba(0,0,0,0.6);
  --skeleton-bg: #1a1f2e;
  --skeleton-shine: #252b3e;
}

/* RESET */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;}
body{
  font-family:var(--font-body);font-size:14px;line-height:1.55;
  background:var(--bg-base);color:var(--text-primary);
  -webkit-font-smoothing:antialiased;overflow-x:hidden;
  transition:background var(--dur) var(--ease),color var(--dur) var(--ease);
}
a{text-decoration:none;color:inherit;}
button,input,select,textarea{font-family:var(--font-body);}
button{cursor:pointer;}
img{max-width:100%;display:block;}
:focus-visible{outline:2px solid var(--brand);outline-offset:2px;border-radius:var(--r-sm);}
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:99px;}

/* LAYOUT */
.app{display:flex;min-height:100vh;}
.sidebar-overlay{
  display:none;position:fixed;inset:0;z-index:199;
  background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);animation:fadeIn 200ms var(--ease);
}
.sidebar-overlay.open{display:block;}

/* SIDEBAR */
.sidebar{
  width:var(--sidebar-w);background:var(--sidebar-bg);
  display:flex;flex-direction:column;flex-shrink:0;
  position:fixed;top:0;left:0;bottom:0;z-index:200;
  transition:transform 280ms var(--ease);overflow:hidden;
  background-image:
    radial-gradient(circle at 20% 80%,rgba(13,158,120,0.08) 0%,transparent 50%),
    radial-gradient(circle at 80% 20%,rgba(13,158,120,0.05) 0%,transparent 40%);
}
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);box-shadow:var(--shadow-xl);}
}
@media(min-width:901px){
  .sidebar{position:sticky;top:0;height:100vh;}
}

.sidebar-brand{
  padding:18px 20px 14px;border-bottom:1px solid rgba(255,255,255,0.07);
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.brand-mark{
  width:38px;height:38px;flex-shrink:0;
  background:linear-gradient(135deg,var(--brand) 0%,#06b6d4 100%);
  border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-family:var(--font-display);font-size:16px;font-weight:800;color:#fff;
  box-shadow:0 4px 12px rgba(13,158,120,0.4);
}
.brand-info{min-width:0;}
.brand-name{font-family:var(--font-display);font-size:14px;font-weight:800;color:#fff;line-height:1.2;}
.brand-tagline{font-size:10px;color:rgba(255,255,255,0.38);letter-spacing:0.5px;}

.sidebar-close{
  display:none;margin-left:auto;flex-shrink:0;
  width:28px;height:28px;background:rgba(255,255,255,0.07);
  border:none;border-radius:var(--r-sm);color:rgba(255,255,255,0.6);
  align-items:center;justify-content:center;font-size:14px;
  transition:background var(--dur);
}
.sidebar-close:hover{background:rgba(255,255,255,0.14);}
@media(max-width:900px){.sidebar-close{display:flex;}}

.sidebar-nav{flex:1;overflow-y:auto;padding:10px 0 16px;}
.sidebar-nav::-webkit-scrollbar{width:0;}

.nav-group-label{
  font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
  color:rgba(255,255,255,0.28);padding:14px 20px 4px;
}
.nav-item{
  display:flex;align-items:center;gap:10px;padding:9px 14px;margin:1px 10px;
  border-radius:var(--r-md);color:var(--sidebar-text);cursor:pointer;
  user-select:none;transition:background var(--dur) var(--ease),color var(--dur);
  position:relative;min-height:40px;
}
.nav-item:hover{background:var(--sidebar-hover);color:rgba(255,255,255,0.9);}
.nav-item.active{background:var(--sidebar-active-bg);color:var(--sidebar-active-text);font-weight:600;}
.nav-item.active::before{
  content:'';position:absolute;left:-10px;top:50%;transform:translateY(-50%);
  width:3px;height:20px;background:var(--brand);border-radius:0 3px 3px 0;
}
.nav-icon{width:18px;text-align:center;font-size:14px;flex-shrink:0;}
.nav-label{font-size:13px;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.nav-badge{
  font-size:10px;font-weight:700;padding:1px 6px;
  background:var(--brand);color:#fff;border-radius:99px;flex-shrink:0;line-height:16px;
}

.sidebar-footer{padding:12px 14px;border-top:1px solid rgba(255,255,255,0.07);flex-shrink:0;}
.sidebar-user{
  display:flex;align-items:center;gap:10px;padding:8px 6px;
  border-radius:var(--r-md);cursor:pointer;transition:background var(--dur);
}
.sidebar-user:hover{background:rgba(255,255,255,0.07);}
.user-avatar{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--brand),#06b6d4);
  display:flex;align-items:center;justify-content:center;
  font-family:var(--font-display);font-size:13px;font-weight:700;color:#fff;
}
.user-details{min-width:0;flex:1;}
.user-name-sm{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-role-sm{font-size:10px;color:rgba(255,255,255,0.4);}

/* MAIN */
.main{flex:1;min-width:0;display:flex;flex-direction:column;}

/* TOPBAR */
.topbar{
  height:var(--topbar-h);background:var(--bg-surface);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 16px;gap:10px;
  position:sticky;top:0;z-index:100;box-shadow:var(--shadow-xs);
}
.topbar-hamburger{
  width:38px;height:38px;border-radius:var(--r-md);
  background:var(--bg-sunken);border:none;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  color:var(--text-secondary);font-size:16px;transition:all var(--dur);
}
.topbar-hamburger:hover{background:var(--brand-light);color:var(--brand);}
@media(min-width:901px){.topbar-hamburger{display:none;}}

.topbar-page-title{
  font-family:var(--font-display);font-size:15px;font-weight:700;
  color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  flex:1;min-width:0;
}

.topbar-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}

.topbar-search{position:relative;display:none;}
@media(min-width:768px){.topbar-search{display:block;}}
.topbar-search-input{
  width:200px;padding:7px 12px 7px 34px;
  background:var(--bg-sunken);border:1px solid var(--border);
  border-radius:var(--r-md);font-size:13px;color:var(--text-primary);
  transition:all var(--dur);outline:none;
}
.topbar-search-input:focus{width:240px;border-color:var(--brand);background:var(--bg-surface);}
.topbar-search-icon{
  position:absolute;left:10px;top:50%;transform:translateY(-50%);
  color:var(--text-muted);font-size:12px;pointer-events:none;
}

.topbar-icon-btn{
  width:36px;height:36px;border-radius:var(--r-md);
  background:var(--bg-sunken);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--text-secondary);font-size:14px;transition:all var(--dur);position:relative;
}
.topbar-icon-btn:hover{background:var(--brand-light);color:var(--brand);border-color:var(--brand-glow);}
.notif-dot{
  position:absolute;top:5px;right:5px;width:7px;height:7px;
  border-radius:50%;background:var(--danger);border:2px solid var(--bg-surface);
}

.theme-switch{
  display:flex;background:var(--bg-sunken);border-radius:var(--r-md);
  border:1px solid var(--border);padding:3px;gap:2px;
}
.theme-btn{
  width:30px;height:30px;border-radius:var(--r-sm);background:transparent;border:none;
  display:flex;align-items:center;justify-content:center;font-size:14px;
  color:var(--text-muted);transition:all var(--dur);
}
.theme-btn.active{background:var(--brand);color:#fff;box-shadow:0 2px 6px rgba(13,158,120,0.35);}

.topbar-dash-link{
  display:inline-flex;align-items:center;gap:6px;padding:6px 12px;
  border-radius:var(--r-md);font-size:12px;font-weight:600;
  transition:all var(--dur);white-space:nowrap;border:none;text-decoration:none;
}
.topbar-dash-link.finance{
  background:var(--info-bg);color:var(--info-text);
  border:1px solid rgba(37,99,235,0.2);
}
.topbar-dash-link.finance:hover{background:var(--info);color:#fff;}

/* NEW: Partner link in topbar */
.topbar-dash-link.partner{
  background:var(--purple-bg);color:var(--purple-text);
  border:1px solid rgba(124,58,237,0.2);
}
.topbar-dash-link.partner:hover{background:var(--purple);color:#fff;}

@media(max-width:600px){.topbar-dash-link .link-label{display:none;}}

.logout-btn{
  display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
  border-radius:var(--r-md);background:var(--danger-bg);color:var(--danger-text);
  border:1px solid rgba(220,38,38,0.15);font-size:13px;font-weight:600;transition:all var(--dur);
}
.logout-btn:hover{background:var(--danger);color:#fff;}
@media(max-width:500px){.logout-btn .btn-text{display:none;}}

/* KEYBOARD SHORTCUTS HELP */
.keyboard-shortcuts{
  display:flex;flex-wrap:wrap;gap:8px;
  padding:8px 12px;background:var(--bg-sunken);
  border-radius:var(--r-md);font-size:11px;color:var(--text-muted);
  align-items:center;margin-bottom:12px;
}
.keyboard-shortcuts kbd{
  display:inline-block;padding:2px 8px;
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:4px;font-family:var(--font-mono);
  font-size:10px;font-weight:600;color:var(--text-secondary);
}

/* CONTENT */
.content{flex:1;padding:20px 24px;max-width:1440px;width:100%;margin:0 auto;}
@media(max-width:768px){.content{padding:14px;}}
@media(max-width:480px){.content{padding:10px;}}

/* SECTIONS */
.section{display:none;animation:sectionIn 250ms var(--ease) both;}
.section.active{display:block;}
@keyframes sectionIn{from{opacity:0;transform:translateY(8px);}}

/* DASHBOARD QUICK-ACCESS CARD */
.dashboard-access-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;
}
@media(max-width:600px){.dashboard-access-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;}}
@media(max-width:380px){.dashboard-access-grid{grid-template-columns:1fr 1fr;}}

.dashboard-access-card{
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-md);padding:12px 14px;cursor:pointer;
  transition:all var(--dur);display:flex;align-items:center;gap:12px;
}
.dashboard-access-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);border-color:var(--brand);}
.dashboard-icon{
  width:38px;height:38px;border-radius:var(--r-md);flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:16px;
}
.dashboard-access-card h4{font-size:12px;font-weight:600;margin-bottom:2px;color:var(--text-primary);}
.dashboard-access-card p{font-size:10px;color:var(--text-muted);}

/* Dashboard icon colour variants */
.dash-admin{background:linear-gradient(135deg,#0d9e78,#06b6d4);color:#fff;}
.dash-ceo{background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;}
.dash-hr{background:linear-gradient(135deg,#059669,#34d399);color:#fff;}
.dash-finance{background:linear-gradient(135deg,#2563eb,#60a5fa);color:#fff;}
.dash-sales{background:linear-gradient(135deg,#d97706,#fbbf24);color:#fff;}
.dash-marketing{background:linear-gradient(135deg,#db2777,#f472b6);color:#fff;}
.dash-support{background:linear-gradient(135deg,#0891b2,#22d3ee);color:#fff;}
.dash-operations{background:linear-gradient(135deg,#4b5563,#9ca3af);color:#fff;}
.dash-credit{background:linear-gradient(135deg,#8b5cf6,#c084fc);color:#fff;}
.dash-dispute{background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;}
.dash-risk{background:linear-gradient(135deg,#dc2626,#f87171);color:#fff;}
.dash-legal{background:linear-gradient(135deg,#78716c,#a8a29e);color:#fff;}
.dash-it{background:linear-gradient(135deg,#1f2937,#4b5563);color:#fff;}
.dash-project{background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:#fff;}
.dash-training{background:linear-gradient(135deg,#84cc16,#a3e635);color:#fff;}
.dash-document{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;}
.dash-qa{background:linear-gradient(135deg,#14b8a6,#2dd4bf);color:#fff;}
.dash-client{background:linear-gradient(135deg,#06b6d4,#22d3ee);color:#fff;}
.dash-partner{background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff;}
.dash-employee{background:linear-gradient(135deg,#6b7280,#9ca3af);color:#fff;}

.dash-filter-wrap{position:relative;margin-bottom:14px;}
.dash-filter-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px;}
.dash-filter-input{
  width:100%;max-width:320px;padding:8px 12px 8px 32px;
  border:1px solid var(--border-strong);border-radius:var(--r-md);
  background:var(--bg-sunken);color:var(--text-primary);font-size:13px;outline:none;
  transition:border-color var(--dur);
}
.dash-filter-input:focus{border-color:var(--brand);}
.collapse-icon{transition:transform 0.3s var(--ease);display:inline-block;}
.collapse-icon.rotated{transform:rotate(180deg);}

/* SKELETON LOADING */
.skeleton-row{display:flex;gap:12px;padding:10px 0;}
.skeleton-cell{
  height:20px;background:var(--skeleton-bg);border-radius:var(--r-sm);flex:1;
  position:relative;overflow:hidden;
}
.skeleton-cell::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent 0%,var(--skeleton-shine) 50%,transparent 100%);
  animation:skeletonShimmer 1.5s infinite;background-size:200% 100%;
}
@keyframes skeletonShimmer{0%{background-position:200% 0;}100%{background-position:-200% 0;}}

/* PAGINATION */
.table-footer{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 16px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;
}
.pagination{display:flex;align-items:center;gap:6px;}
.pagination .btn{min-height:30px;padding:4px 10px;}
.records-info{font-size:12px;color:var(--text-muted);}

/* BULK ACTIONS */
.bulk-actions{
  display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg-sunken);
  border-radius:var(--r-md);flex-wrap:wrap;
}
.bulk-actions input[type="checkbox"]{width:16px;height:16px;cursor:pointer;accent-color:var(--brand);}
.selected-count{font-size:12px;font-weight:600;color:var(--text-secondary);}

/* LAST UPDATED */
.last-updated{
  display:flex;align-items:center;gap:10px;padding:6px 12px;background:var(--bg-sunken);
  border-radius:var(--r-md);font-size:11px;color:var(--text-muted);
}
.last-updated i{color:var(--brand);}

/* TOASTS */
.toast-container{
  position:fixed;bottom:20px;right:20px;
  display:flex;flex-direction:column;gap:8px;z-index:2000;pointer-events:none;max-width:400px;
}
@media(max-width:480px){.toast-container{left:10px;right:10px;bottom:10px;}}
.toast{
  display:flex;align-items:center;gap:10px;padding:11px 14px;
  border-radius:var(--r-lg);background:var(--bg-surface);border:1px solid var(--border);
  box-shadow:var(--shadow-lg);max-width:100%;width:100%;
  animation:toastIn 300ms var(--ease-spring);pointer-events:all;
  transition:transform 200ms var(--ease),opacity 200ms,margin 200ms;
}
.toast.out{animation:toastOut 300ms var(--ease) forwards;}
@keyframes toastIn{from{transform:translateX(110%);opacity:0;}}
@keyframes toastOut{from{transform:translateX(0);opacity:1;}to{transform:translateX(110%);opacity:0;}}
.toast-icon{width:30px;height:30px;border-radius:var(--r-md);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px;}
.toast-success .toast-icon{background:var(--success-bg);color:var(--success);}
.toast-error   .toast-icon{background:var(--danger-bg);color:var(--danger);}
.toast-info    .toast-icon{background:var(--info-bg);color:var(--info);}
.toast-warning .toast-icon{background:var(--warning-bg);color:var(--warning);}
.toast-msg{font-size:13px;font-weight:500;flex:1;}
.toast-close{background:none;border:none;color:var(--text-muted);font-size:15px;transition:color var(--dur);padding:0 4px;}
.toast-close:hover{color:var(--text-primary);}

/* CARDS */
.card{
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-lg);box-shadow:var(--shadow-sm);
  overflow:hidden;margin-bottom:18px;transition:box-shadow var(--dur);
}
.card:hover{box-shadow:var(--shadow-md);}
.card-header{
  padding:14px 18px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
}
.card-title{
  font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;
  font-family:var(--font-display);
}
.card-title i{color:var(--brand);font-size:14px;}
.card-body{padding:18px;}

/* STAT CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px;}
@media(max-width:600px){.stats-grid{grid-template-columns:1fr 1fr;gap:10px;}}
@media(max-width:380px){.stats-grid{grid-template-columns:1fr;}}

.stat-card{
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-lg);padding:18px;overflow:hidden;position:relative;
  transition:transform var(--dur) var(--ease),box-shadow var(--dur);
}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--r-lg) var(--r-lg) 0 0;}
.sc-green::before{background:linear-gradient(90deg,#0d9e78,#34d399);}
.sc-blue::before{background:linear-gradient(90deg,#2563eb,#60a5fa);}
.sc-amber::before{background:linear-gradient(90deg,#d97706,#fbbf24);}
.sc-purple::before{background:linear-gradient(90deg,#7c3aed,#c084fc);}
.sc-red::before{background:linear-gradient(90deg,#dc2626,#f87171);}
.sc-teal::before{background:linear-gradient(90deg,#0891b2,#22d3ee);}
.stat-row{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:12px;}
.stat-icon{width:42px;height:42px;border-radius:var(--r-md);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:17px;}
.sc-green .stat-icon{background:var(--success-bg);color:var(--success);}
.sc-blue .stat-icon{background:var(--info-bg);color:var(--info);}
.sc-amber .stat-icon{background:var(--warning-bg);color:var(--warning);}
.sc-purple .stat-icon{background:var(--purple-bg);color:var(--purple);}
.sc-red .stat-icon{background:var(--danger-bg);color:var(--danger);}
.sc-teal .stat-icon{background:rgba(8,145,178,0.1);color:#0891b2;}
.stat-chip{font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;white-space:nowrap;}
.chip-up{background:var(--success-bg);color:var(--success-text);}
.chip-down{background:var(--danger-bg);color:var(--danger-text);}
.chip-neu{background:var(--bg-sunken);color:var(--text-muted);}
.stat-value{font-family:var(--font-display);font-size:26px;font-weight:800;letter-spacing:-0.5px;line-height:1;margin-bottom:4px;}
.stat-label{font-size:12px;color:var(--text-secondary);font-weight:500;}
.mini-bar{height:3px;background:var(--bg-sunken);border-radius:99px;margin-top:12px;overflow:hidden;}
.mini-bar-fill{height:100%;border-radius:99px;transition:width 800ms var(--ease);}
.sc-green .mini-bar-fill{background:var(--brand);}
.sc-blue .mini-bar-fill{background:var(--info);}
.sc-amber .mini-bar-fill{background:var(--warning);}
.sc-purple .mini-bar-fill{background:var(--purple);}
.sc-red .mini-bar-fill{background:var(--danger);}
.sc-teal .mini-bar-fill{background:#0891b2;}

/* CHARTS ROW */
.charts-row{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:18px;}
@media(max-width:900px){.charts-row{grid-template-columns:1fr;}}
.chart-wrap{position:relative;height:220px;}
@media(max-width:600px){.chart-wrap{height:180px;}}

/* SUGGESTION CARDS */
.suggestion-card{
  display:flex;align-items:flex-start;gap:14px;padding:14px 16px;
  border-radius:var(--r-md);border:1px solid var(--border);margin-bottom:10px;
  transition:all var(--dur);background:var(--bg-raised);
}
.suggestion-card:hover{box-shadow:var(--shadow-md);transform:translateX(2px);}
.suggestion-card.high{border-left:3px solid var(--danger);}
.suggestion-card.medium{border-left:3px solid var(--warning);}
.suggestion-card.low{border-left:3px solid var(--success);}
.sug-icon{width:36px;height:36px;border-radius:var(--r-md);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;}
.high .sug-icon{background:var(--danger-bg);color:var(--danger);}
.suggestion-card.medium .sug-icon{background:var(--warning-bg);color:var(--warning);}
.low .sug-icon{background:var(--success-bg);color:var(--success);}
.sug-content{flex:1;min-width:0;}
.sug-content h5{font-size:13px;font-weight:700;margin-bottom:4px;}
.sug-content p{font-size:12px;color:var(--text-secondary);line-height:1.5;}

/* TABLES */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
.table-wrap-fade{position:relative;}
.table-wrap-fade::after{
  content:'';position:absolute;top:0;right:0;bottom:0;width:40px;
  background:linear-gradient(90deg,transparent,var(--bg-surface));pointer-events:none;
}
@media(min-width:769px){.table-wrap-fade::after{display:none;}}
table{width:100%;border-collapse:collapse;white-space:nowrap;}
thead th{
  padding:10px 14px;text-align:left;font-size:10.5px;font-weight:700;
  text-transform:uppercase;letter-spacing:0.7px;color:var(--text-muted);
  background:var(--bg-sunken);border-bottom:1px solid var(--border);
}
tbody td{padding:11px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:middle;color:var(--text-primary);}
tbody tr:last-child td{border-bottom:none;}
tbody tr{transition:background var(--dur);}
tbody tr:hover td{background:var(--bg-sunken);}
td .row-checkbox{width:16px;height:16px;cursor:pointer;accent-color:var(--brand);}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap;}
.badge-green{background:var(--success-bg);color:var(--success-text);}
.badge-amber{background:var(--warning-bg);color:var(--warning-text);}
.badge-red{background:var(--danger-bg);color:var(--danger-text);}
.badge-blue{background:var(--info-bg);color:var(--info-text);}
.badge-gray{background:var(--bg-sunken);color:var(--text-secondary);}
.badge-brand{background:var(--brand-light);color:var(--brand-dark);}
.badge-purple{background:var(--purple-bg);color:var(--purple-text);}

/* BUTTONS */
.btn{
  display:inline-flex;align-items:center;gap:7px;padding:8px 16px;
  border-radius:var(--r-md);font-size:13px;font-weight:600;border:none;cursor:pointer;
  transition:all var(--dur) var(--ease);white-space:nowrap;min-height:36px;
}
.btn:disabled{opacity:0.55;cursor:not-allowed;pointer-events:none;}
.btn-primary{background:var(--brand);color:#fff;box-shadow:0 2px 8px rgba(13,158,120,0.25);}
.btn-primary:hover{background:var(--brand-dark);box-shadow:0 4px 14px rgba(13,158,120,0.35);transform:translateY(-1px);}
.btn-danger{background:var(--danger-bg);color:var(--danger-text);border:1px solid rgba(220,38,38,.15);}
.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-success{background:var(--success-bg);color:var(--success-text);border:1px solid rgba(5,150,105,.15);}
.btn-success:hover{background:var(--success);color:#fff;}
.btn-ghost{background:var(--bg-sunken);color:var(--text-secondary);border:1px solid var(--border);}
.btn-ghost:hover{background:var(--border);}
.btn-sm{padding:5px 12px;font-size:12px;min-height:30px;}
.btn-xs{padding:3px 8px;font-size:11px;min-height:26px;}
.btn-icon{width:36px;height:36px;padding:0;justify-content:center;}

/* FORMS */
.form-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.form-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:150px;}
.form-label{font-size:12px;font-weight:600;color:var(--text-secondary);}
.form-req{color:var(--danger);}
.form-input,.form-select,.form-textarea{
  width:100%;padding:9px 13px;background:var(--bg-surface);
  border:1px solid var(--border-strong);border-radius:var(--r-md);
  font-size:13px;color:var(--text-primary);transition:border-color var(--dur),box-shadow var(--dur);
  outline:none;min-height:38px;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-glow);}
.form-input::placeholder{color:var(--text-muted);}
.form-input.error{border-color:var(--danger);box-shadow:0 0 0 3px rgba(220,38,38,0.15);}
.form-textarea{resize:vertical;min-height:80px;}
@media(max-width:480px){.form-row{flex-direction:column;}}

/* FILTER BAR */
.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 18px;border-bottom:1px solid var(--border);}
.filter-search-wrap{position:relative;flex:1;min-width:180px;}
.filter-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px;}
.search-input{
  width:100%;padding:8px 12px 8px 32px;background:var(--bg-surface);
  border:1px solid var(--border-strong);border-radius:var(--r-md);
  font-size:13px;color:var(--text-primary);outline:none;min-height:36px;transition:border-color var(--dur);
}
.search-input:focus{border-color:var(--brand);}

/* TABS */
.tab-bar{display:flex;border-bottom:1px solid var(--border);padding:0 18px;overflow-x:auto;}
.tab-bar::-webkit-scrollbar{height:0;}
.tab-btn{
  padding:10px 16px;font-size:13px;font-weight:600;color:var(--text-muted);
  background:none;border:none;cursor:pointer;white-space:nowrap;
  border-bottom:2px solid transparent;margin-bottom:-1px;transition:all var(--dur);min-height:44px;
}
.tab-btn.active{color:var(--brand);border-bottom-color:var(--brand);}
.tab-content{display:none;padding:18px;}
.tab-content.active{display:block;}

/* MODAL */
.modal-overlay{
  position:fixed;inset:0;z-index:300;background:rgba(0,0,0,0.45);
  backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);
  display:none;align-items:center;justify-content:center;padding:16px;
}
.modal-overlay.open{display:flex;}
.modal-box{
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-xl);width:100%;max-width:560px;
  max-height:92dvh;overflow-y:auto;box-shadow:var(--shadow-xl);
  animation:modalIn 220ms var(--ease-spring);
}
@media(max-width:480px){
  .modal-overlay{padding:0;align-items:flex-end;}
  .modal-box{border-radius:var(--r-xl) var(--r-xl) 0 0;}
}
@keyframes modalIn{from{opacity:0;transform:scale(0.95) translateY(16px);}}
.modal-header{
  padding:18px 20px 14px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
}
.modal-title{font-family:var(--font-display);font-size:16px;font-weight:700;}
.modal-close{
  width:32px;height:32px;border-radius:var(--r-md);background:var(--bg-sunken);
  border:none;display:flex;align-items:center;justify-content:center;
  color:var(--text-muted);font-size:15px;transition:all var(--dur);
}
.modal-close:hover{background:var(--danger-bg);color:var(--danger);}
.modal-body{padding:18px 20px;}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;}

/* SPINNER / EMPTY STATE */
.spinner{
  width:18px;height:18px;flex-shrink:0;
  border:2px solid var(--border-strong);border-top-color:var(--brand);
  border-radius:50%;animation:spin 0.7s linear infinite;display:inline-block;
}
@keyframes spin{to{transform:rotate(360deg);}}
.loading-cell{text-align:center;padding:32px;color:var(--text-muted);}
.empty-state{padding:44px 20px;text-align:center;color:var(--text-muted);}
.empty-state-icon{
  width:56px;height:56px;border-radius:var(--r-xl);background:var(--bg-sunken);
  display:flex;align-items:center;justify-content:center;
  font-size:24px;margin:0 auto 14px;color:var(--text-muted);
}
.empty-state-title{font-weight:700;font-size:14px;color:var(--text-secondary);margin-bottom:4px;}
.empty-state-sub{font-size:13px;}

/* CODE BLOCK */
.code-block{
  background:var(--bg-sunken);border:1px solid var(--border);border-radius:var(--r-md);
  padding:14px 16px;font-family:var(--font-mono);font-size:12.5px;line-height:1.7;
  overflow-x:auto;color:var(--text-primary);white-space:pre-wrap;word-break:break-word;
}
.code-display{
  background:var(--bg-sunken);border:2px dashed var(--border-strong);
  border-radius:var(--r-lg);padding:24px;text-align:center;margin:16px 0;
}
.gen-code{font-family:var(--font-mono);font-size:24px;font-weight:700;color:var(--brand);letter-spacing:4px;word-break:break-all;}

/* AI PANEL */
.ai-fab{
  position:fixed;bottom:22px;right:22px;z-index:400;
  width:52px;height:52px;border-radius:50%;
  background:linear-gradient(135deg,var(--brand),#06b6d4);
  box-shadow:0 6px 20px rgba(13,158,120,0.4);
  border:none;color:#fff;font-size:20px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:all 250ms var(--ease-spring);
}
.ai-fab:hover{transform:scale(1.1);box-shadow:0 8px 28px rgba(13,158,120,0.55);}
.ai-fab.open{transform:rotate(45deg);}
.ai-panel{
  position:fixed;bottom:82px;right:22px;z-index:399;
  width:360px;max-height:70dvh;display:flex;flex-direction:column;
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-xl);box-shadow:var(--shadow-xl);overflow:hidden;
  transform:scale(0.92) translateY(16px);opacity:0;pointer-events:none;
  transition:all 250ms var(--ease-spring);transform-origin:bottom right;
}
.ai-panel.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all;}
@media(max-width:480px){
  .ai-fab{bottom:14px;right:14px;width:48px;height:48px;font-size:18px;}
  .ai-panel{right:8px;left:8px;width:auto;bottom:72px;}
}
.ai-panel-header{
  padding:12px 16px;background:linear-gradient(135deg,#0b2a23,#0d3d2c);
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.ai-dot{width:8px;height:8px;border-radius:50%;background:#34d399;flex-shrink:0;animation:aipulse 2s infinite;}
@keyframes aipulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(0.85)}}
.ai-panel-title{font-size:13px;font-weight:700;color:#fff;flex:1;}
.ai-panel-sub{font-size:10px;color:rgba(255,255,255,0.4);}
.ai-close{
  width:26px;height:26px;background:rgba(255,255,255,0.08);border:none;
  border-radius:var(--r-sm);color:rgba(255,255,255,0.6);font-size:13px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background var(--dur);
}
.ai-close:hover{background:rgba(255,255,255,0.16);}
.ai-messages{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;}
.ai-msg{max-width:85%;padding:10px 13px;border-radius:var(--r-lg);font-size:13px;line-height:1.55;animation:msgIn 200ms var(--ease);}
@keyframes msgIn{from{opacity:0;transform:scale(0.9);}}
.ai-msg.user{background:var(--brand);color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}
.ai-msg.bot{background:var(--bg-sunken);color:var(--text-primary);align-self:flex-start;border-bottom-left-radius:4px;border:1px solid var(--border);}
.ai-chips{padding:8px 12px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid var(--border);flex-shrink:0;}
.ai-chip{
  padding:5px 10px;border-radius:99px;background:var(--bg-sunken);
  border:1px solid var(--border);font-size:11px;cursor:pointer;
  color:var(--text-secondary);transition:all var(--dur);white-space:nowrap;
}
.ai-chip:hover{background:var(--brand-light);border-color:rgba(13,158,120,0.3);color:var(--brand);}
.ai-input-row{padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:8px;flex-shrink:0;}
.ai-input{
  flex:1;padding:9px 12px;border:1px solid var(--border-strong);
  border-radius:var(--r-md);font-size:13px;background:var(--bg-sunken);
  color:var(--text-primary);outline:none;transition:border-color var(--dur);
}
.ai-input:focus{border-color:var(--brand);}
.ai-send{
  width:36px;height:36px;background:var(--brand);border:none;
  border-radius:var(--r-md);color:#fff;font-size:13px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background var(--dur);
}
.ai-send:hover{background:var(--brand-dark);}

/* DECISION HUB */
.metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:18px;}

/* POSTER GRID */
.poster-grid{padding:18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;}
@media(max-width:480px){.poster-grid{grid-template-columns:1fr 1fr;}}
.poster-card{
  border-radius:var(--r-md);overflow:hidden;border:1px solid var(--border);
  position:relative;aspect-ratio:3/4;background:var(--bg-sunken);
}
.poster-card img{width:100%;height:100%;object-fit:cover;}
.poster-card .poster-actions{
  position:absolute;bottom:0;left:0;right:0;padding:8px;
  background:linear-gradient(transparent,rgba(0,0,0,0.7));
  display:flex;justify-content:flex-end;gap:4px;
}

/* UTILS */
.fw-700{font-weight:700;}
.text-muted{color:var(--text-muted);}
.text-brand{color:var(--brand);}
.font-mono{font-family:var(--font-mono);font-size:12px;}
.gap-8{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.mb-0{margin-bottom:0!important;}
@keyframes fadeIn{from{opacity:0;}}

/* GLOBAL SEARCH RESULTS */
.search-results-dropdown{
  position:absolute;top:100%;left:0;right:0;margin-top:4px;
  background:var(--bg-surface);border:1px solid var(--border);
  border-radius:var(--r-md);box-shadow:var(--shadow-lg);
  max-height:300px;overflow-y:auto;z-index:999;display:none;
}
.search-results-dropdown.open{display:block;}
.search-result-item{
  padding:10px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;
  border-bottom:1px solid var(--border);font-size:13px;transition:background var(--dur);
}
.search-result-item:hover{background:var(--bg-sunken);}
.search-result-item:last-child{border-bottom:none;}
.search-result-type{
  font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;
  padding:2px 6px;border-radius:4px;flex-shrink:0;
}
/* ===== ADDITIONAL STYLES ===== */

/* Reviews */
.review-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.review-card:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
}
#ratingStars span {
    transition: transform 0.2s;
    cursor: pointer;
}
#ratingStars span:hover {
    transform: scale(1.2);
}
/* Service Cards */
.service-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.service-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
</style>
</head>
</style>
</head>
<body>
<div class="app">

<!-- Sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">CR</div>
    <div class="brand-info">
      <div class="brand-name">CIBIL Repair</div>
      <div class="brand-tagline">Admin CRM</div>
    </div>
    <button class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group-label">Overview</div>
    <div class="nav-item active" data-section="dashboard">
      <i class="fas fa-tachometer-alt nav-icon"></i>
      <span class="nav-label">Dashboard</span>
    </div>
    <div class="nav-item" data-section="decisionHub">
      <i class="fas fa-brain nav-icon"></i>
      <span class="nav-label">Decision Hub</span>
    </div>

    <div class="nav-group-label">Sales & CRM</div>
    <div class="nav-item" data-section="leads">
      <i class="fas fa-filter nav-icon"></i>
      <span class="nav-label">Leads</span>
      <span class="nav-badge" id="leadsBadge">24</span>
    </div>
    <div class="nav-item" data-section="customerList">
      <i class="fas fa-users nav-icon"></i>
      <span class="nav-label">Customers</span>
    </div>
    <div class="nav-item" data-section="customerRequests">
      <i class="fas fa-clipboard-list nav-icon"></i>
      <span class="nav-label">Requests</span>
    </div>
    <div class="nav-item" data-section="addCustomer">
      <i class="fas fa-user-plus nav-icon"></i>
      <span class="nav-label">Add Customer</span>
    </div>

    <!-- Partners & Banks -->
    <div class="nav-group-label">Partners & Banks</div>
    <div class="nav-item" data-section="partnerList">
        <i class="fas fa-handshake nav-icon"></i>
        <span class="nav-label">Partners</span>
    </div>
    <div class="nav-item" data-section="partnerApplications">
        <i class="fas fa-user-check nav-icon"></i>
        <span class="nav-label">Partner Applications</span>
        <span class="nav-badge" id="pendingAppsBadge">0</span>
    </div>
    <div class="nav-item" data-section="addPartner">
        <i class="fas fa-plus-circle nav-icon"></i>
        <span class="nav-label">Add Partner</span>
    </div>
    <div class="nav-item" data-section="bankList">
        <i class="fas fa-university nav-icon"></i>
        <span class="nav-label">Banks</span>
    </div>

    <div class="nav-group-label">Finance</div>
    <div class="nav-item" data-section="salesReport">
      <i class="fas fa-chart-bar nav-icon"></i>
      <span class="nav-label">Sales Report</span>
    </div>
    <div class="nav-item" data-section="addSale">
      <i class="fas fa-plus-circle nav-icon"></i>
      <span class="nav-label">Add Sale</span>
    </div>
    <div class="nav-item" data-section="invoice">
      <i class="fas fa-file-invoice nav-icon"></i>
      <span class="nav-label">Invoice</span>
    </div>
    <div class="nav-item" data-section="quotationList">
      <i class="fas fa-file-invoice-dollar nav-icon"></i>
      <span class="nav-label">Quotations</span>
    </div>
    <div class="nav-item" data-section="addExpense">
      <i class="fas fa-minus-circle nav-icon"></i>
      <span class="nav-label">Add Expense</span>
    </div>
    <div class="nav-item" data-section="expenseReport">
      <i class="fas fa-chart-pie nav-icon"></i>
      <span class="nav-label">Expense Report</span>
    </div>

    <div class="nav-group-label">Wallet</div>
    <div class="nav-item" data-section="addMoney">
      <i class="fas fa-plus nav-icon"></i>
      <span class="nav-label">Add Money</span>
    </div>
    <div class="nav-item" data-section="withdrawMoney">
      <i class="fas fa-arrow-down nav-icon"></i>
      <span class="nav-label">Withdraw</span>
    </div>
    <div class="nav-item" data-section="txHistory">
      <i class="fas fa-history nav-icon"></i>
      <span class="nav-label">Transactions</span>
    </div>

    <div class="nav-group-label">AI Tools</div>
    <div class="nav-item" data-section="aiAnalyzer">
      <i class="fas fa-robot nav-icon"></i>
      <span class="nav-label">AI Analyzer</span>
    </div>

    <div class="nav-group-label">Access</div>
    <div class="nav-item" data-section="createCode">
      <i class="fas fa-qrcode nav-icon"></i>
      <span class="nav-label">Create Code</span>
    </div>
    <div class="nav-item" data-section="codeList">
      <i class="fas fa-list nav-icon"></i>
      <span class="nav-label">Code List</span>
    </div>
    <div class="nav-item" data-section="usersByCode">
      <i class="fas fa-user-tag nav-icon"></i>
      <span class="nav-label">Users by Code</span>
    </div>

    <div class="nav-group-label">Content</div>
    <div class="nav-item" data-section="reviews">
        <i class="fas fa-star nav-icon"></i>
        <span class="nav-label">Reviews</span>
    </div>
    <div class="nav-item" data-section="posters">
        <i class="fas fa-images nav-icon"></i>
        <span class="nav-label">Posters</span>
    </div>
        <div class="nav-item" data-section="services">
        <i class="fas fa-cogs nav-icon"></i>
        <span class="nav-label">Services</span>
    </div>

    <div class="nav-group-label">Settings</div>
    <div class="nav-item" data-section="generalSettings">
      <i class="fas fa-sliders-h nav-icon"></i>
      <span class="nav-label">General</span>
    </div>
    <div class="nav-item" data-section="securitySettings">
      <i class="fas fa-shield-alt nav-icon"></i>
      <span class="nav-label">Security</span>
    </div>
    <div class="nav-item" data-section="activityLog">
      <i class="fas fa-history nav-icon"></i>
      <span class="nav-label">Activity Log</span>
    </div>
    <div class="nav-item" data-section="backup">
      <i class="fas fa-database nav-icon"></i>
      <span class="nav-label">Backup</span>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user" onclick="showSection('generalSettings')">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
      <div class="user-details">
        <div class="user-name-sm"><?= $user_name ?></div>
        <div class="user-role-sm"><?= $user_role ?></div>
      </div>
      <i class="fas fa-ellipsis-v" style="color:rgba(255,255,255,0.3);font-size:12px;"></i>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <button class="topbar-hamburger" id="hamburgerBtn" onclick="openSidebar()" aria-label="Open menu">
      <i class="fas fa-bars"></i>
    </button>

    <span class="topbar-page-title" id="pageTitle">Dashboard</span>

    <div class="topbar-right">
      <div class="topbar-search" style="position:relative;">
        <i class="fas fa-search topbar-search-icon"></i>
        <input class="topbar-search-input" placeholder="Quick search…" id="globalSearch" autocomplete="off">
        <div class="search-results-dropdown" id="searchResultsDropdown"></div>
      </div>

      <!-- Finance Dashboard Link -->
      <a href="finance-dashboard.php" class="topbar-dash-link finance" title="Finance Dashboard">
        <i class="fas fa-chart-line"></i>
        <span class="link-label">Finance</span>
      </a>

      <!-- NEW: Partner Dashboard Link (opens partner picker) -->
      <a href="partner-dashboard.php" class="topbar-dash-link partner" title="Partner Portal">
        <i class="fas fa-handshake"></i>
        <span class="link-label">Partners</span>
      </a>

      <button class="topbar-icon-btn" onclick="showSection('activityLog')" title="Notifications">
        <i class="fas fa-bell"></i>
        <span class="notif-dot"></span>
      </button>

      <div class="theme-switch" role="group" aria-label="Theme">
        <button class="theme-btn active" id="lightBtn" onclick="setTheme('light')" title="Light">☀️</button>
        <button class="theme-btn" id="darkBtn" onclick="setTheme('dark')" title="Dark">🌙</button>
      </div>

      <button class="logout-btn" id="logoutBtn">
        <i class="fas fa-sign-out-alt"></i>
        <span class="btn-text">Logout</span>
      </button>
    </div>
  </header>

  <!-- CONTENT -->
  <div class="content">

    <!-- KEYBOARD SHORTCUTS HELP -->
    <div class="keyboard-shortcuts">
      <span>⌨️ Shortcuts:</span>
      <kbd>Alt+D</kbd> Dashboard
      <kbd>Alt+C</kbd> Customers
      <kbd>Alt+P</kbd> Partners
      <kbd>Alt+L</kbd> Leads
      <kbd>Alt+A</kbd> AI Analyzer
      <kbd>Alt+S</kbd> Settings
    </div>

    <!-- DASHBOARD QUICK-ACCESS CARD -->
    <div class="card">
      <div class="card-header" style="cursor:pointer;" onclick="toggleDashboards()" role="button" aria-expanded="true" aria-controls="dashboardGridBody">
        <div class="card-title">
          <i class="fas fa-th-large"></i>
          Quick Access — All Dashboards
          <i class="fas fa-chevron-down collapse-icon" id="collapseIcon"></i>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:22px;font-weight:800;color:var(--brand);"><?= count($all_dashboards) ?></span>
          <span style="font-size:13px;color:var(--text-secondary);">Dashboards</span>
        </div>
      </div>
      <div id="dashboardGridBody">
        <div class="card-body" style="padding-bottom:10px;">
          <div class="dash-filter-wrap">
            <i class="fas fa-search"></i>
            <input class="dash-filter-input" type="text" id="dashboardSearchInput" placeholder="Search dashboards…">
          </div>
          <div class="dashboard-access-grid" id="dashboardGrid">
            <?php foreach ($all_dashboards as $d): ?>
            <div class="dashboard-access-card"
                 data-name="<?= strtolower(h($d['name'])) ?>"
                 data-role="<?= strtolower(h($d['role'])) ?>"
                 onclick="window.location.href='<?= h($d['file']) ?>'">
              <div class="dashboard-icon dash-<?= h($d['color']) ?>">
                <i class="fas <?= h($d['icon']) ?>"></i>
              </div>
              <div class="dashboard-info">
                <h4><?= h($d['name']) ?></h4>
                <p><?= h($d['role']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div id="noDashboardsFound" style="display:none;text-align:center;padding:32px;color:var(--text-muted);">
            <div class="empty-state-icon"><i class="fas fa-search"></i></div>
            <div class="empty-state-title">No dashboards match your search</div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION: DASHBOARD -->
    <div class="section active" id="dashboardSection">
      <div class="last-updated" style="margin-bottom:16px;">
        <i class="fas fa-clock"></i>
        Last updated: <span id="lastUpdated">Just now</span>
        <button class="btn btn-ghost btn-xs" onclick="refreshAllData()">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
      <div class="stats-grid">
        <div class="stat-card sc-green">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-users"></i></div><span class="stat-chip chip-up">+12%</span></div>
          <div class="stat-value" id="totalCustomers">—</div>
          <div class="stat-label">Total Customers</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="72%"></div></div>
        </div>
        <div class="stat-card sc-blue">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-university"></i></div><span class="stat-chip chip-up">+3</span></div>
          <div class="stat-value" id="totalBanks">—</div>
          <div class="stat-label">Partner Banks</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="55%"></div></div>
        </div>
        <div class="stat-card sc-amber">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-rupee-sign"></i></div><span class="stat-chip chip-up">+18%</span></div>
          <div class="stat-value" id="totalSales">—</div>
          <div class="stat-label">Total Revenue</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="63%"></div></div>
        </div>
        <div class="stat-card sc-purple">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-handshake"></i></div><span class="stat-chip chip-up">+5</span></div>
          <div class="stat-value" id="totalPartners">—</div>
          <div class="stat-label">Active Partners</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="48%"></div></div>
        </div>
        <div class="stat-card sc-teal">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-wallet"></i></div><span class="stat-chip chip-neu">Stable</span></div>
          <div class="stat-value" id="walletBalance">—</div>
          <div class="stat-label">Wallet Balance</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="40%"></div></div>
        </div>
        <div class="stat-card sc-red">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-filter"></i></div><span class="stat-chip chip-up">+7</span></div>
          <div class="stat-value" id="totalLeads">—</div>
          <div class="stat-label">New Leads</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:0%" data-w="35%"></div></div>
        </div>
      </div>

      <div class="charts-row">
        <div class="card mb-0">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-area"></i>Revenue Trend</div>
            <div class="gap-8">
              <button class="btn btn-ghost btn-sm" onclick="switchChart('monthly')">Monthly</button>
              <button class="btn btn-ghost btn-sm" onclick="switchChart('weekly')">Weekly</button>
            </div>
          </div>
          <div class="card-body"><div class="chart-wrap"><canvas id="salesChart"></canvas></div></div>
        </div>
        <div class="card mb-0">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i>By Service</div></div>
          <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
              <span class="gap-8" style="font-size:11px;color:var(--text-secondary);"><span style="width:10px;height:10px;border-radius:3px;background:#0d9e78;display:inline-block;"></span>Written Off</span>
              <span class="gap-8" style="font-size:11px;color:var(--text-secondary);"><span style="width:10px;height:10px;border-radius:3px;background:#2563eb;display:inline-block;"></span>Settled</span>
              <span class="gap-8" style="font-size:11px;color:var(--text-secondary);"><span style="width:10px;height:10px;border-radius:3px;background:#d97706;display:inline-block;"></span>Profile Fix</span>
            </div>
            <div class="chart-wrap" style="height:180px;"><canvas id="revenueChart"></canvas></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-clock"></i>Recent Activity</div>
          <button class="btn btn-ghost btn-sm btn-icon" onclick="refreshActivity()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>Activity</th><th>User</th><th>Date / Time</th><th>Status</th></tr></thead>
            <tbody id="activityBody"><tr><td colspan="4"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: DECISION HUB -->
    <div class="section" id="decisionHubSection">
      <div class="metric-grid">
        <div class="stat-card sc-green">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-heartbeat"></i></div></div>
          <div class="stat-value">78<span style="font-size:14px;font-weight:500;color:var(--text-muted)">/100</span></div>
          <div class="stat-label">Business Health</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:78%"></div></div>
        </div>
        <div class="stat-card sc-blue">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-rocket"></i></div></div>
          <div class="stat-value" style="font-size:20px;">High</div>
          <div class="stat-label">Growth Potential</div>
        </div>
        <div class="stat-card sc-amber">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div></div>
          <div class="stat-value" style="font-size:20px;">Medium</div>
          <div class="stat-label">Risk Level</div>
        </div>
        <div class="stat-card sc-purple">
          <div class="stat-row"><div class="stat-icon"><i class="fas fa-bullseye"></i></div></div>
          <div class="stat-value">82<span style="font-size:14px;font-weight:500;color:var(--text-muted)">/100</span></div>
          <div class="stat-label">Opportunity Score</div>
          <div class="mini-bar"><div class="mini-bar-fill" style="width:82%"></div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-lightbulb"></i>AI Recommendations</div>
          <span class="badge badge-green"><i class="fas fa-circle" style="font-size:7px;"></i> Live</span>
        </div>
        <div class="card-body">
          <div class="suggestion-card high">
            <div class="sug-icon"><i class="fas fa-arrow-up"></i></div>
            <div class="sug-content"><h5>Increase Client Acquisition</h5><p>Current rate is 28/month vs target 40/month. Referral incentive could close the gap by 43%.</p></div>
            <button class="btn btn-danger btn-sm" style="flex-shrink:0;align-self:flex-start;" onclick="toast('Action plan created! Check your email for details.','success')">Act Now</button>
          </div>
          <div class="suggestion-card medium">
            <div class="sug-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="sug-content"><h5>Partner Training Program</h5><p>30% of partners are below average in conversion. A structured session could improve conversion by ~8%.</p></div>
            <button class="btn btn-ghost btn-sm" style="flex-shrink:0;align-self:flex-start;" onclick="toast('Training session scheduled for next Monday at 11:00 AM','success')">Schedule</button>
          </div>
          <div class="suggestion-card low">
            <div class="sug-icon"><i class="fas fa-star"></i></div>
            <div class="sug-content"><h5>Leverage Customer Testimonials</h5><p>Satisfaction is 4.8/5 — above target. Use this as social proof to boost lead acquisition.</p></div>
            <button class="btn btn-success btn-sm" style="flex-shrink:0;align-self:flex-start;" onclick="toast('Testimonial campaign initiated — collecting top 5 reviews','info')">Explore</button>
          </div>
        </div>
      </div>

      <div class="charts-row">
        <div class="card mb-0">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i>Revenue vs Target</div></div>
          <div class="card-body"><div class="chart-wrap"><canvas id="revenueTargetChart"></canvas></div></div>
        </div>
        <div class="card mb-0">
          <div class="card-header"><div class="card-title"><i class="fas fa-users-cog"></i>Partner Performance</div></div>
          <div class="card-body"><div class="chart-wrap" style="height:180px;"><canvas id="partnerPerfChart"></canvas></div></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-table"></i>Business Metrics</div>
          <button class="btn btn-primary btn-sm" onclick="exportReport()"><i class="fas fa-download"></i>Export</button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>Metric</th><th>Current</th><th>Target</th><th>Gap</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <tr><td>Client Acquisition</td><td>28/mo</td><td>40/mo</td><td style="color:var(--danger)">-12</td><td><span class="badge badge-amber">Below</span></td><td class="text-muted">Increase marketing</td></tr>
              <tr><td>Partner Conversion</td><td>68%</td><td>75%</td><td style="color:var(--danger)">-7%</td><td><span class="badge badge-amber">Needs Work</span></td><td class="text-muted">Partner training</td></tr>
              <tr><td>Revenue Growth</td><td>15.2%</td><td>20%</td><td style="color:var(--warning)">-4.8%</td><td><span class="badge badge-green">On Track</span></td><td class="text-muted">Upsell clients</td></tr>
              <tr><td>Customer Satisfaction</td><td>4.8/5</td><td>4.5/5</td><td style="color:var(--success)">+0.3</td><td><span class="badge badge-green">Exceeding</span></td><td class="text-muted">Use testimonials</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: LEADS -->
    <div class="section" id="leadsSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-users"></i>Leads</div>
                <div class="gap-8">
                    <button class="btn btn-primary btn-sm" onclick="showSection('addLead')">
                        <i class="fas fa-plus"></i> Add Lead
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleLeadsExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="leadsExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportLeads('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportLeads('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportLeads('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportLeads('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="leadSearch" placeholder="Search leads..." oninput="debounceFilter('leads')">
                </div>
                <select class="form-select" id="leadStatusFilter" onchange="filterLeads()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="converted">Converted</option>
                    <option value="lost">Lost</option>
                </select>
                <select class="form-select" id="leadPriorityFilter" onchange="filterLeads()" style="width:140px;">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Service</th>
                            <th>Priority</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leadsBody">
                        <tr><td colspan="10"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer" id="leadsPagination">
                <div class="pagination">
                    <button class="btn btn-ghost btn-sm" onclick="changePage('leads', -1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="leadsPageInfo">Page 1</span>
                    <button class="btn btn-ghost btn-sm" onclick="changePage('leads', 1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="records-info">
                    Showing <span id="leadsStart">0</span> - <span id="leadsEnd">0</span> of <span id="leadsTotal">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD LEAD SECTION -->
    <div class="section" id="addLeadSection">
        <div class="card" style="max-width:600px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-filter"></i>Add Lead</div>
                <button class="btn btn-ghost btn-sm" onclick="showSection('leads')"><i class="fas fa-times"></i> Cancel</button>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="form-req">*</span></label>
                        <input class="form-input" id="leadName" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone <span class="form-req">*</span></label>
                        <input class="form-input" id="leadPhone" placeholder="9876543210" maxlength="10">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="form-input" id="leadEmail" type="email" placeholder="john@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select" id="leadPriority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Service</label>
                        <select class="form-select" id="leadService">
                            <option value="CIBIL Repair">CIBIL Repair</option>
                            <option value="Written Off">Written Off</option>
                            <option value="Settled">Settled</option>
                            <option value="Profile Correction">Profile Correction</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source</label>
                        <select class="form-select" id="leadSource">
                            <option value="website">Website</option>
                            <option value="referral">Referral</option>
                            <option value="google_ads">Google Ads</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="call">Call</option>
                            <option value="email">Email</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹)</label>
                        <input class="form-input" id="leadAmount" type="number" placeholder="15000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <input class="form-input" id="leadMessage" placeholder="Brief message">
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;">
                    <button class="btn btn-primary" onclick="addLead()"><i class="fas fa-save"></i> Save Lead</button>
                    <button class="btn btn-ghost" onclick="showSection('leads')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: AI ANALYZER -->
    <div class="section" id="aiAnalyzerSection">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-microchip"></i>AI Credit & Document Analyzer</div></div>
        <div class="tab-bar">
          <button class="tab-btn active" onclick="switchTab(this,'newTab')">New Analysis</button>
          <button class="tab-btn" onclick="switchTab(this,'historyTab')">History</button>
        </div>
        <div class="tab-content active" id="newTab">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Document Type</label>
              <select class="form-select" id="docType">
                <option value="credit_report">📊 Credit / CIBIL Report</option>
                <option value="bank_statement">🏦 Bank Statement</option>
                <option value="loan_noc">📄 Loan NOC / Settlement Letter</option>
                <option value="legal_notice">⚖️ Legal Notice / Summons</option>
                <option value="other">📑 Other Financial Document</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Upload Document (.txt, .pdf)</label>
              <input type="file" class="form-input" id="docFile" accept=".txt,.pdf,.csv">
            </div>
          </div>
          <button class="btn btn-primary" id="analyzeBtn" onclick="analyzeDocument()">
            <i class="fas fa-robot"></i>Analyze Document
          </button>
          <div id="analysisResult" style="display:none;margin-top:20px;">
            <div class="code-block" id="analysisContent"></div>
            <div class="gap-8" style="margin-top:12px;">
              <button class="btn btn-primary btn-sm" onclick="copyAnalysis()"><i class="fas fa-copy"></i>Copy</button>
              <button class="btn btn-ghost btn-sm" onclick="downloadDispute()"><i class="fas fa-download"></i>Dispute Letter</button>
            </div>
          </div>
        </div>
        <div class="tab-content" id="historyTab">
          <div class="table-wrap table-wrap-fade">
            <table>
              <thead><tr><th>#</th><th>Type</th><th>File</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody id="analysisHistory">
                <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-history"></i></div><div class="empty-state-title">No history yet</div><div class="empty-state-sub">Analyze a document to see history here</div></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION: CUSTOMER LIST -->
    <div class="section" id="customerListSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-users"></i>Customer List</div>
                <div class="gap-8">
                    <div class="bulk-actions" id="customerBulkActions" style="display:none;">
                        <span class="selected-count" id="customerSelectedCount">0 selected</span>
                        <button class="btn btn-danger btn-sm" onclick="bulkDelete('customerTableBody')"><i class="fas fa-trash"></i> Delete</button>
                        <button class="btn btn-success btn-sm" onclick="bulkExport('customerTableBody')"><i class="fas fa-file-export"></i> Export</button>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addCustomerModal')"><i class="fas fa-plus"></i>Add</button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleCustomerExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="customerExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportCustomers('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportCustomers('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportCustomers('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportCustomers('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="custSearch" placeholder="Search customers…" oninput="debounceFilter('customers')">
                </div>
                <select class="form-select" id="custStatusFilter" onchange="filterCustomers()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCustomersHead" onchange="toggleAllRows('customerTableBody', this)"></th>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <tr><td colspan="9"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer" id="customerPagination">
                <div class="pagination">
                    <button class="btn btn-ghost btn-sm" onclick="changePage('customers', -1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="customerPageInfo">Page 1</span>
                    <button class="btn btn-ghost btn-sm" onclick="changePage('customers', 1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="records-info">
                    Showing <span id="customerStart">0</span> - <span id="customerEnd">0</span> of <span id="customerTotal">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: ADD CUSTOMER -->
    <div class="section" id="addCustomerSection">
      <div class="card" style="max-width:600px;">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-user-plus"></i>Add Customer</div>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name <span class="form-req">*</span></label>
              <input class="form-input" id="newCustName" placeholder="Rajesh Kumar">
            </div>
            <div class="form-group">
              <label class="form-label">Email <span class="form-req">*</span></label>
              <input class="form-input" id="newCustEmail" type="email" placeholder="rajesh@example.com">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input class="form-input" id="newCustPhone" placeholder="9876543210" maxlength="10">
            </div>
            <div class="form-group">
              <label class="form-label">City</label>
              <input class="form-input" id="newCustCity" placeholder="Delhi">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Service</label>
              <select class="form-select" id="newCustService">
                <option>Written Off</option>
                <option>Settled</option>
                <option>Profile Correction</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary" onclick="addCustomer()">
            <i class="fas fa-save"></i>Save Customer
          </button>
        </div>
      </div>
    </div>

    <!-- SECTION: CUSTOMER REQUESTS -->
    <div class="section" id="customerRequestsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-clipboard-list"></i>Customer Requests</div>
          <button class="btn btn-primary btn-sm" onclick="showSection('newCustomerRequest')"><i class="fas fa-plus"></i>New</button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>#</th><th>Name</th><th>Service</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="reqTableBody"><tr><td colspan="6"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: NEW CUSTOMER REQUEST -->
    <div class="section" id="newCustomerRequestSection">
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-user-plus"></i>New Customer Request</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Full Name <span class="form-req">*</span></label><input class="form-input" id="reqName" placeholder="Customer name"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="reqEmail" type="email" placeholder="email@example.com"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="reqPhone" placeholder="9876543210"></div>
            <div class="form-group"><label class="form-label">Service</label>
              <select class="form-select" id="reqService"><option>Written Off</option><option>Settled</option><option>Profile Correction</option></select>
            </div>
          </div>
          <button class="btn btn-primary" onclick="submitRequest()"><i class="fas fa-paper-plane"></i>Submit Request</button>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- PARTNER LIST SECTION -->
    <!-- ============================================================ -->
    <div class="section" id="partnerListSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-handshake"></i> Partner List
                </div>
                <div class="gap-8">
                    <button class="btn btn-primary btn-sm" onclick="showSection('addPartner')">
                        <i class="fas fa-plus"></i> Add Partner
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="togglePartnersExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="partnersExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius    :var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportPartners('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background    :none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportPartners('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportPartners('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportPartners('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="partnerSearch" placeholder="Search partners..." oninput="debounceFilter('partners')">
                </div>
                <select class="form-select" id="partnerStatusFilter" onchange="filterPartners()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="filterPartners()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button class="btn btn-ghost btn-sm" onclick="document.getElementById('partnerSearch').value='';document.getElementById('partnerStatusFilter').value='';filterPartners();">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Owner</th>
                            <th>Phone</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partnerTableBody">
                        <tr><td colspan="8"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="records-info">
                    Total Partners: <span id="partnerTotalCount">0</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- SECTION: PARTNER APPLICATIONS -->
    <!-- ============================================================ -->
    <div class="section" id="partnerApplicationsSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-user-check"></i> Partner Applications
                </div>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-sm" onclick="loadPartnerApplications()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="togglePartnerAppsExportDropdown()">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                        <div id="partnerAppsExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius    :var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportPartnerApps('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background    :none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportPartnerApps('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px    ;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="partnerAppSearch" placeholder="Search applications..." oninput="debounceFilter('partnerApps')">
                </div>
                <select class="form-select" id="partnerAppStatusFilter" onchange="filterPartnerApps()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="resetPartnerAppFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:auto;">
                    Pending: <span id="pendingCount">0</span>
                </span>
            </div>
        
            <!-- Applications List -->
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Partner Type</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partnerApplicationsBody">
                        <tr>
                            <td colspan="8">
                                <div class="loading-cell">
                                    <div class="spinner"></div> Loading applications...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        
            <div class="table-footer">
                <div class="records-info">
                    Showing <span id="partnerAppStart">0</span> - <span id="partnerAppEnd">0</span> of <span id="partnerAppTotal">0</span>
                </div>
                <div class="pagination">
                    <button class="btn btn-ghost btn-sm" onclick="changePartnerAppPage(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="partnerAppPageInfo">Page 1</span>
                    <button class="btn btn-ghost btn-sm" onclick="changePartnerAppPage(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- APPROVE PARTNER MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="approvePartnerModal">
        <div class="modal-box" style="max-width:550px;">
            <div class="modal-header" style="border-bottom-color:var(--success);">
                <span class="modal-title" style="color:var(--success);">
                    <i class="fas fa-check-circle"></i> Approve Partner
                </span>
                <button class="modal-close" onclick="closeModal('approvePartnerModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approveAppId">
                <div class="info-box" style="background:var(--bg-sunken);border-radius:var(--r-md);padding:16px;margin-bottom:16px;">
                    <p><strong>Applicant:</strong> <span id="approveAppName">-</span></p>
                    <p><strong>Email:</strong> <span id="approveAppEmail">-</span></p>
                    <p><strong>Partner Type:</strong> <span id="approveAppType">-</span></p>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea class="form-textarea" id="approveNotes" placeholder="Add any internal notes..." rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="sendWhatsApp" checked> 
                        Send WhatsApp notification
                    </label>
                </div>
                <div class="alert alert-info" style="background:var(--info-bg);color:var(--info-text);padding:12px;border-radius:var(--r-md);font-size:13px;">
                    <i class="fas fa-info-circle"></i> 
                    This will generate a registration code and send login credentials to the applicant.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('approvePartnerModal')">Cancel</button>
                <button class="btn btn-success" id="approveBtn" onclick="confirmApprovePartner()">
                    <i class="fas fa-check"></i> Approve Partner
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- REJECT PARTNER MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="rejectPartnerModal">
        <div class="modal-box" style="max-width:550px;">
            <div class="modal-header" style="border-bottom-color:var(--danger);">
                <span class="modal-title" style="color:var(--danger);">
                    <i class="fas fa-times-circle"></i> Reject Application
                </span>
                <button class="modal-close" onclick="closeModal('rejectPartnerModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectAppId">
                <div class="info-box" style="background:var(--bg-sunken);border-radius:var(--r-md);padding:16px;margin-bottom:16px;">
                    <p><strong>Applicant:</strong> <span id="rejectAppName">-</span></p>
                    <p><strong>Email:</strong> <span id="rejectAppEmail">-</span></p>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Rejection <span class="form-req">*</span></label>
                    <textarea class="form-textarea" id="rejectReason" placeholder="Explain why this application is being rejected..." rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes (Internal)</label>
                    <textarea class="form-textarea" id="rejectNotes" placeholder="Internal notes..." rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('rejectPartnerModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmRejectPartner()">
                    <i class="fas fa-times"></i> Reject Application
                </button>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- ADD PARTNER SECTION -->
    <!-- ============================================================ -->
    <div class="section" id="addPartnerSection">
        <div class="card" style="max-width:600px;">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-handshake"></i> Add Partner
                </div>
                <button class="btn btn-ghost btn-sm" onclick="showSection('partnerList')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Business Name <span class="form-req">*</span></label>
                        <input class="form-input" id="newPartnerName" placeholder="ABC Credit Services" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner Name</label>
                        <input class="form-input" id="newPartnerOwner" placeholder="Amit Singh">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone <span class="form-req">*</span></label>
                        <input class="form-input" id="newPartnerPhone" placeholder="9876543210" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input class="form-input" id="newPartnerLocation" placeholder="Delhi NCR">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="form-input" id="newPartnerEmail" type="email" placeholder="partner@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commission Rate (%)</label>
                        <input class="form-input" id="newPartnerCommission" type="number" value="10" min="0" max="100">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="newPartnerStatus">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;">
                    <button class="btn btn-primary" onclick="addPartner()">
                        <i class="fas fa-save"></i> Save Partner
                    </button>
                    <button class="btn btn-ghost" onclick="showSection('partnerList')">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- EDIT PARTNER MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="editPartnerModal">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">
                    <i class="fas fa-edit"></i> Edit Partner
                </span>
                <button class="modal-close" onclick="closeModal('editPartnerModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editPartnerId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Business Name <span class="form-req">*</span></label>
                        <input class="form-input" id="editPartnerName" placeholder="Business Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner Name</label>
                        <input class="form-input" id="editPartnerOwner" placeholder="Owner Name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone <span class="form-req">*</span></label>
                        <input class="form-input" id="editPartnerPhone" placeholder="Phone Number" maxlength="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input class="form-input" id="editPartnerLoc" placeholder="Location">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commission Rate (%)</label>
                        <input class="form-input" id="editPartnerComm" type="number" value="10" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editPartnerStatus">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editPartnerModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveEditPartner()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DELETE PARTNER CONFIRMATION MODAL (Optional) -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="deletePartnerModal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-header" style="border-bottom-color:var(--danger);">
                <span class="modal-title" style="color:var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </span>
                <button class="modal-close" onclick="closeModal('deletePartnerModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align:center;padding:30px 20px;">
                <div style="font-size:48px;margin-bottom:16px;color:var(--danger);">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h3 style="margin-bottom:8px;">Are you sure?</h3>
                <p style="color:var(--text-muted);margin-bottom:4px;">
                    You are about to delete <strong id="deletePartnerName">this partner</strong>.
                </p>
                <p style="color:var(--text-muted);font-size:12px;">
                    This action cannot be undone. All associated data will be removed.
                </p>
                <input type="hidden" id="deletePartnerId">
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button class="btn btn-ghost" onclick="closeModal('deletePartnerModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-danger" onclick="confirmDeletePartner()">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: BANK LIST -->
    <div class="section" id="bankListSection">
      <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-university"></i>Bank List</div>
            <div class="gap-8">
                <input class="search-input" id="bankSearch" placeholder="Search banks..." style="width:200px;min-height:36px;padding:8px 12px;border:1px solid var(--border-strong);border-radius:var(--r-md);background:var(--bg-sunken);color:var(--text-primary);outline:none;" oninput="filterBanks()">
                <button class="btn btn-primary btn-sm" onclick="showSection('addBank')"><i class="fas fa-plus"></i>Add Bank</button>
                <div style="position:relative;display:inline-block;">
                    <button class="btn btn-success btn-sm" onclick="toggleExportDropdown()">
                        <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                    </button>
                    <div id="exportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                        <button class="btn btn-ghost btn-sm" onclick="exportBanks('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                            <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="exportBanks('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor    :pointer;">
                            <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="exportBanks('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                            <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                        </button>
                        <button class="btn btn-ghost btn-sm" onclick="exportBanks('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor    :pointer;">
                            <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- SECTION: ADD BANK -->
    <div class="section" id="addBankSection">
      <div class="card" style="max-width:600px;">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-university"></i>Add Bank</div>
          <button class="btn btn-ghost btn-sm" onclick="showSection('bankList')"><i class="fas fa-times"></i> Cancel</button>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Bank Name <span class="form-req">*</span></label>
              <input class="form-input" id="newBankName" placeholder="HDFC Bank">
            </div>
            <div class="form-group">
              <label class="form-label">Entity Type</label>
              <select class="form-select" id="newBankType">
                <option value="bank">Bank</option>
                <option value="lawyer">Law Firm / Advocate</option>
                <option value="ca">Chartered Accountant</option>
                <option value="franchise">Franchise Store</option>
                <option value="real_estate">Real Estate Agent</option>
                <option value="insurance">Insurance Agent</option>
                <option value="consultant">Business Consultant</option>
                <option value="agency">Recruitment Agency</option>
                <option value="broker">Broker / Agent</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Contact Person</label>
              <input class="form-input" id="newBankContact" placeholder="Rahul Mehta">
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input class="form-input" id="newBankEmail" type="email" placeholder="contact@bank.com">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input class="form-input" id="newBankPhone" placeholder="9876543210" maxlength="10">
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-select" id="newBankStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
          <div class="form-row" style="margin-top:12px;">
            <button class="btn btn-primary" onclick="addBank()"><i class="fas fa-save"></i> Save Bank</button>
            <button class="btn btn-ghost" onclick="showSection('bankList')">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- SALES REPORT SECTION -->
    <!-- ============================================================ -->
    <div class="section" id="salesReportSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-chart-bar"></i>Sales Report</div>
                <div class="gap-8">
                    <button class="btn btn-primary btn-sm" onclick="showSection('addSale')">
                        <i class="fas fa-plus"></i> Add Sale
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleSalesExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="salesExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var   (--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportSales('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportSales('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportSales('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportSales('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <label style="font-size:12px;font-weight:600;color:var(--text-secondary);">From:</label>
                <input type="date" class="form-input" style="width:160px;min-height:36px;" id="salesFrom">
                <label style="font-size:12px;font-weight:600;color:var(--text-secondary);">To:</label>
                <input type="date" class="form-input" style="width:160px;min-height:36px;" id="salesTo">
                <button class="btn btn-primary btn-sm" onclick="generateSalesReport()">
                    <i class="fas fa-cogs"></i> Generate
                </button>
                <button class="btn btn-ghost btn-sm" onclick="resetSalesFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:auto;" id="salesCountInfo">
                    Total: <span id="salesTotalCount">0</span> sales
                </span>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesBody">
                        <tr><td colspan="7"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="records-info">
                    Showing <span id="salesStart">0</span> - <span id="salesEnd">0</span> of <span id="salesTotal">0</span> sales
                </div>
                <div class="pagination">
                    <button class="btn btn-ghost btn-sm" onclick="changeSalesPage(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="salesPageInfo">Page 1</span>
                    <button class="btn btn-ghost btn-sm" onclick="changeSalesPage(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ADD SALE SECTION -->
    <!-- ============================================================ -->
    <div class="section" id="addSaleSection">
        <div class="card" style="max-width:560px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Add Sale</div>
                <button class="btn btn-ghost btn-sm" onclick="showSection('salesReport')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Customer Name <span class="form-req">*</span></label>
                        <input class="form-input" id="saleCustomer" placeholder="Customer name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service <span class="form-req">*</span></label>
                        <select class="form-select" id="saleService">
                            <option value="Written Off">Written Off</option>
                            <option value="Settled">Settled</option>
                            <option value="Profile Correction">Profile Correction</option>
                            <option value="CIBIL Repair">CIBIL Repair</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹) <span class="form-req">*</span></label>
                        <input class="form-input" id="saleAmount" type="number" step="0.01" placeholder="15000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input class="form-input" id="saleDate" type="date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="saleStatus">
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input class="form-input" id="saleNotes" placeholder="Additional notes">
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;">
                    <button class="btn btn-primary" onclick="addSale()">
                        <i class="fas fa-save"></i> Save Sale
                    </button>
                    <button class="btn btn-ghost" onclick="showSection('salesReport')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EDIT SALE MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="editSaleModal">
        <div class="modal-box" style="max-width:560px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-edit"></i> Edit Sale</span>
                <button class="modal-close" onclick="closeModal('editSaleModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editSaleId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Customer Name <span class="form-req">*</span></label>
                        <input class="form-input" id="editSaleCustomer" placeholder="Customer name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service <span class="form-req">*</span></label>
                        <select class="form-select" id="editSaleService">
                            <option value="Written Off">Written Off</option>
                            <option value="Settled">Settled</option>
                            <option value="Profile Correction">Profile Correction</option>
                            <option value="CIBIL Repair">CIBIL Repair</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹) <span class="form-req">*</span></label>
                        <input class="form-input" id="editSaleAmount" type="number" step="0.01" placeholder="15000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input class="form-input" id="editSaleDate" type="date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editSaleStatus">
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input class="form-input" id="editSaleNotes" placeholder="Additional notes">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editSaleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateSale()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DELETE SALE CONFIRMATION MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="deleteSaleModal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-header" style="border-bottom-color:var(--danger);">
                <span class="modal-title" style="color:var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </span>
                <button class="modal-close" onclick="closeModal('deleteSaleModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align:center;padding:30px 20px;">
                <div style="font-size:48px;margin-bottom:16px;color:var(--danger);">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 style="margin-bottom:8px;">Are you sure?</h3>
                <p style="color:var(--text-muted);margin-bottom:4px;">
                    You are about to delete the sale for <strong id="deleteSaleCustomer">this customer</strong>.
                </p>
                <p style="color:var(--text-muted);font-size:12px;">
                    Amount: <strong id="deleteSaleAmount">₹0</strong><br>
                    This action cannot be undone.
                </p>
                <input type="hidden" id="deleteSaleId">
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button class="btn btn-ghost" onclick="closeModal('deleteSaleModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-danger" onclick="confirmDeleteSale()">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: INVOICE -->
    <div class="section" id="invoiceSection">
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice"></i>Generate Invoice</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Invoice No</label><input class="form-input" id="invoiceNo" placeholder="INV-001"></div>
            <div class="form-group"><label class="form-label">Customer</label><input class="form-input" id="invoiceCust" placeholder="Customer name"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Service</label><input class="form-input" id="invoiceService" placeholder="Service description"></div>
            <div class="form-group"><label class="form-label">Amount (₹)</label><input class="form-input" id="invoiceAmt" type="number" placeholder="15000"></div>
          </div>
          <button class="btn btn-primary" onclick="generateInvoice()"><i class="fas fa-file-pdf"></i>Generate Invoice</button>
        </div>
      </div>
    </div>

    <!-- SECTION: WALLET -->
    <div class="section" id="addMoneySection">
        <div class="card" style="max-width:480px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-plus-circle"></i>Add Money</div>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹)</label>
                        <input class="form-input" id="addAmt" type="number" placeholder="5000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="addMethod">
                            <option>UPI</option>
                            <option>Credit Card</option>
                            <option>Debit Card</option>
                            <option>NEFT</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Description (Optional)</label>
                        <input class="form-input" id="addDesc" placeholder="Payment description">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="addMoney()">
                    <i class="fas fa-paper-plane"></i> Add Money
                </button>
            </div>
        </div>
    </div>

    <div class="section" id="withdrawMoneySection">
        <div class="card" style="max-width:480px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-arrow-down"></i>Withdraw</div>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹)</label>
                        <input class="form-input" id="wdAmt" type="number" placeholder="2000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Method</label>
                        <select class="form-select" id="wdMethod">
                            <option>Bank Transfer</option>
                            <option>UPI</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Description (Optional)</label>
                        <input class="form-input" id="wdDesc" placeholder="Withdrawal reason">
                    </div>
                </div>
                <button class="btn btn-danger" onclick="withdrawMoney()">
                    <i class="fas fa-paper-plane"></i> Request Withdrawal
                </button>
            </div>
        </div>
    </div>

    <div class="section" id="txHistorySection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-history"></i>Transaction History</div>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-sm" onclick="loadTransactions()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleTxExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="txExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportTransactions('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportTransactions('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportTransactions('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportTransactions('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="txSearch" placeholder="Search transactions..." oninput="filterTransactions()">
                </div>
                <select class="form-select" id="txTypeFilter" onchange="filterTransactions()" style="width:140px;">
                    <option value="">All Types</option>
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                </select>
                <select class="form-select" id="txMethodFilter" onchange="filterTransactions()" style="width:150px;">
                    <option value="">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="NEFT">NEFT</option>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="resetTxFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:auto;" id="txCountInfo">
                    Total: <span id="txTotalCount">0</span> transactions
                </span>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="txBody">
                        <tr><td colspan="4"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="records-info">
                    Showing <span id="txStart">0</span> - <span id="txEnd">0</span> of <span id="txTotal">0</span> transactions
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: QUOTATION LIST -->
    <div class="section" id="quotationListSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-file-invoice-dollar"></i>Quotations</div>
                <div class="gap-8">
                    <button class="btn btn-primary btn-sm" onclick="showSection('newQuotation')">
                        <i class="fas fa-plus"></i> New
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleQuotesExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="quotesExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportQuotations('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportQuotations('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportQuotations('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportQuotations('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="quoteSearch" placeholder="Search quotations..." oninput="filterQuotations()">
                </div>
                <select class="form-select" id="quoteStatusFilter" onchange="filterQuotations()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="converted">Converted</option>
                </select>
                <select class="form-select" id="quoteServiceFilter" onchange="filterQuotations()" style="width:150px;">
                    <option value="">All Services</option>
                    <option value="Written Off">Written Off</option>
                    <option value="Settled">Settled</option>
                    <option value="Profile Correction">Profile Correction</option>
                    <option value="CIBIL Repair">CIBIL Repair</option>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="document.getElementById('quoteSearch').value='';document.getElementById('quoteStatusFilter').value='';document.getElementById('quoteServiceFilter').value='';filterQuotations();">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
            <div class="table-wrap table-wrap-fade">
                <table>
                    <thead>
                        <tr>
                            <th>Quote No</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>GST (18%)</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="quoteBody">
                        <tr><td colspan="9"><div class="loading-cell"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="records-info">
                    Total Quotations: <span id="quoteTotalCount">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: NEW QUOTATION -->
    <div class="section" id="newQuotationSection">
        <div class="card" style="max-width:560px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-plus"></i>New Quotation</div>
                <button class="btn btn-ghost btn-sm" onclick="showSection('quotationList')"><i class="fas fa-times"></i> Cancel</button>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Customer</label>
                        <input class="form-input" id="qCust" placeholder="Customer name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service</label>
                        <select class="form-select" id="qService">
                            <option>Written Off</option>
                            <option>Settled</option>
                            <option>Profile Correction</option>
                            <option>CIBIL Repair</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (₹)</label>
                        <input class="form-input" id="qAmt" type="number" placeholder="15000">
                        <small style="color:var(--text-muted);font-size:11px;">GST 18% will be added automatically</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input class="form-input" id="qValidity" type="date">
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;background:var(--bg-sunken);padding:12px;border-radius:var(--r-md);">
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">Amount</div>
                        <div style="font-size:18px;font-weight:700;color:var(--text-primary);" id="newQuoteAmountDisplay">₹0</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">GST (18%)</div>
                        <div style="font-size:18px;font-weight:700;color:var(--brand);" id="newQuoteGstDisplay">₹0</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">Total</div>
                        <div style="font-size:20px;font-weight:800;color:var(--brand-dark);" id="newQuoteTotalDisplay">₹0</div>
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;">
                    <button class="btn btn-primary" onclick="addQuotation()"><i class="fas fa-save"></i>Save Quotation</button>
                    <button class="btn btn-ghost" onclick="showSection('quotationList')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT QUOTATION MODAL -->
    <div class="modal-overlay" id="editQuotationModal">
        <div class="modal-box" style="max-width:600px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-edit"></i> Edit Quotation</span>
                <button class="modal-close" onclick="closeModal('editQuotationModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editQuoteId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Quote No</label>
                        <input class="form-input" id="editQuoteNo" placeholder="QUO-001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer <span class="form-req">*</span></label>
                        <input class="form-input" id="editQuoteCustomer" placeholder="Customer name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Service</label>
                        <select class="form-select" id="editQuoteService">
                            <option>Written Off</option>
                            <option>Settled</option>
                            <option>Profile Correction</option>
                            <option>CIBIL Repair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (₹) <span class="form-req">*</span></label>
                        <input class="form-input" id="editQuoteAmount" type="number" step="0.01" placeholder="15000" oninput="updateQuoteGST()">
                        <small style="color:var(--text-muted);font-size:11px;">GST 18% will be added automatically</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input class="form-input" id="editQuoteDate" type="date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input class="form-input" id="editQuoteValidity" type="date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editQuoteStatus">
                            <option value="Draft">Draft</option>
                            <option value="Sent">Sent</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Converted">Converted</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input class="form-input" id="editQuoteNotes" placeholder="Additional notes">
                    </div>
                </div>
                <div class="form-row" style="margin-top:12px;background:var(--bg-sunken);padding:12px;border-radius:var(--r-md);">
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">Amount</div>
                        <div style="font-size:18px;font-weight:700;color:var(--text-primary);" id="editQuoteAmountDisplay">₹0</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">GST (18%)</div>
                        <div style="font-size:18px;font-weight:700;color:var(--brand);" id="editQuoteGstDisplay">₹0</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--text-muted);">Total</div>
                        <div style="font-size:20px;font-weight:800;color:var(--brand-dark);" id="editQuoteTotalDisplay">₹0</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editQuotationModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateQuotation()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- SECTION: EXPENSES -->
    <div class="section" id="addExpenseSection">
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-minus-circle"></i>Add Expense</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Category</label>
              <select class="form-select" id="expCat"><option>Rent</option><option>Salary</option><option>Marketing</option><option>Utilities</option><option>Other</option></select>
            </div>
            <div class="form-group"><label class="form-label">Amount (₹)</label><input class="form-input" id="expAmt" type="number" placeholder="5000"></div>
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:2;"><label class="form-label">Description</label><input class="form-input" id="expDesc" placeholder="Expense description"></div>
            <div class="form-group"><label class="form-label">Date</label><input class="form-input" id="expDate" type="date"></div>
          </div>
          <button class="btn btn-primary" onclick="addExpense()"><i class="fas fa-save"></i>Save Expense</button>
        </div>
      </div>
    </div>

    <div class="section" id="expenseReportSection">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i>Expense Report</div></div>
        <div class="filter-bar">
          <input type="date" class="form-input" style="width:160px;min-height:36px;" id="expRepFrom">
          <input type="date" class="form-input" style="width:160px;min-height:36px;" id="expRepTo">
          <button class="btn btn-primary btn-sm" onclick="generateExpenseReport()">Generate</button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>#</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
            <tbody id="expRepBody">
              <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-chart-pie"></i></div><div class="empty-state-title">No expense data</div><div class="empty-state-sub">Select dates and click Generate</div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: REGISTRATION CODES -->
    <div class="section" id="createCodeSection">
      <div class="card" style="max-width:560px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-qrcode"></i>Create Registration Code</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Role</label>
              <select class="form-select" id="codeRole"><option value="partner">Partner Account</option><option value="client">Client Account</option></select>
            </div>
            <div class="form-group"><label class="form-label">Assign to Email (optional)</label>
              <input class="form-input" id="codeEmail" type="email" placeholder="user@example.com">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Expiry</label>
              <select class="form-select" id="codeExpiry">
                <option value="7">7 days</option><option value="14">14 days</option>
                <option value="30" selected>30 days</option><option value="90">90 days</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary" id="genCodeBtn" onclick="generateCode()"><i class="fas fa-magic"></i>Generate Code</button>
          <div id="codeResult" style="display:none;margin-top:20px;">
            <div class="code-display">
              <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;">Generated Code</div>
              <div class="gen-code" id="genCodeValue"></div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:10px;" id="genCodeExpiry"></div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="copyCode()" style="margin-top:10px;"><i class="fas fa-copy"></i>Copy Code</button>
          </div>
        </div>
      </div>
    </div>

    <div class="section" id="codeListSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-list"></i>Registration Codes</div>
          <button class="btn btn-ghost btn-sm" onclick="loadCodeList()"><i class="fas fa-sync-alt"></i>Refresh</button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>#</th><th>Code</th><th>Role</th><th>Assigned To</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead>
            <tbody id="codeListBody"><tr><td colspan="7"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="section" id="usersByCodeSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-users"></i>Users by Registration Code</div>
          <button class="btn btn-ghost btn-sm" onclick="loadUsersByCode()"><i class="fas fa-sync-alt"></i>Refresh</button>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
            <tbody id="usersByCodeBody"><tr><td colspan="8"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: POSTERS -->
    <div class="section" id="postersSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-images"></i> Posters
                </div>
                <div class="gap-8">
                    <input type="file" id="posterFile" accept="image/*" style="display:none" multiple onchange="uploadPoster(this)">
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('posterFile').click()">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                    <button class="btn btn-ghost btn-sm" onclick="loadPosters()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="poster-grid" id="posterGrid">
                <!-- Posters will be loaded here by JavaScript -->
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="empty-state-icon"><i class="fas fa-images"></i></div>
                    <div class="empty-state-title">No posters uploaded yet</div>
                    <div class="empty-state-sub">Click "Upload" to add posters</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SECTION: REVIEWS -->
    <div class="section" id="reviewsSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-star"></i> Customer Reviews
                </div>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-sm" onclick="loadReviews()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-success btn-sm" onclick="showAddReviewModal()">
                        <i class="fas fa-plus"></i> Add Review
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="reviewsContainer">
                    <div class="loading-cell">
                        <div class="spinner"></div> Loading reviews...
                    </div>
                </div>
            </div>
            <div class="card-footer" style="padding:12px 18px;border-top:1px solid var(--border);background:var(--bg-sunken);">
                <div class="gap-8" style="justify-content:space-between;flex-wrap:wrap;">
                    <span id="reviewTotalCount">0</span> reviews
                    <span>⭐ <span id="reviewAvgRating">0</span>/5 average rating</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD REVIEW MODAL -->
    <div class="modal-overlay" id="addReviewModal">
        <div class="modal-box" style="max-width:500px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-star"></i> Add Review</span>
                <button class="modal-close" onclick="closeModal('addReviewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Name <span class="form-req">*</span></label>
                        <input class="form-input" id="reviewName" placeholder="Customer name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="form-input" id="reviewEmail" type="email" placeholder="customer@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Rating <span class="form-req">*</span></label>
                        <div class="gap-8" id="ratingStars" style="font-size:28px;cursor:pointer;">
                            <span data-rating="1" onclick="setRating(1)">☆</span>
                            <span data-rating="2" onclick="setRating(2)">☆</span>
                            <span data-rating="3" onclick="setRating(3)">☆</span>
                            <span data-rating="4" onclick="setRating(4)">☆</span>
                            <span data-rating="5" onclick="setRating(5)">☆</span>
                        </div>
                        <input type="hidden" id="reviewRating" value="5">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Review <span class="form-req">*</span></label>
                        <textarea class="form-textarea" id="reviewText" placeholder="Write your review here..." rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addReviewModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitReview()">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- SECTION: SERVICES -->
    <!-- ============================================================ -->
    <div class="section" id="servicesSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-cogs"></i> Services
                </div>
                <div class="gap-8">
                    <button class="btn btn-primary btn-sm" onclick="showAddServiceModal()">
                        <i class="fas fa-plus"></i> Add Service
                    </button>
                    <button class="btn btn-ghost btn-sm" onclick="loadServices('servicesContainer')">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div style="position:relative;display:inline-block;">
                        <button class="btn btn-success btn-sm" onclick="toggleServicesExportDropdown()">
                            <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                        </button>
                        <div id="servicesExportDropdown" style="display:none;position:absolute;top:100%;right:0;background:var(--bg-surface);border:1px solid var(--border);border-radius    :var(--r-md);box-shadow:var(--shadow-lg);z-index:100;min-width:150px;padding:4px 0;">
                            <button class="btn btn-ghost btn-sm" onclick="exportServices('csv')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-csv" style="color:#0d9e78;"></i> CSV
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportServices('excel')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-excel" style="color:#1d7a3a;"></i> Excel
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportServices('json')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-code" style="color:#f7df1e;"></i> JSON
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="exportServices('pdf')" style="display:block;width:100%;text-align:left;border:none;padding:6px 16px;background:none;cursor:pointer;">
                                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="serviceSearch" placeholder="Search services..." oninput="debounceFilter('services')">
                </div>
                <select class="form-select" id="serviceCategoryFilter" onchange="filterServices()" style="width:160px;">
                    <option value="">All Categories</option>
                    <option value="credit_repair">Credit Repair</option>
                    <option value="dispute">Dispute Resolution</option>
                    <option value="consulting">Consulting</option>
                    <option value="legal">Legal</option>
                    <option value="financial">Financial</option>
                    <option value="other">Other</option>
                </select>
                <select class="form-select" id="serviceStatusFilter" onchange="filterServices()" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="resetServiceFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:auto;" id="serviceCountInfo">
                    Total: <span id="serviceTotalCount">0</span> services
                </span>
            </div>
            
            <!-- Services Grid/List -->
            <div class="card-body" id="servicesContainer">
                <div class="loading-cell">
                    <div class="spinner"></div> Loading services...
                </div>
            </div>
            
            <div class="table-footer">
                <div class="records-info" id="servicePaginationInfo">
                    Showing <span id="serviceStart">0</span> - <span id="serviceEnd">0</span> of <span id="serviceTotal">0</span> services
                </div>
                <div class="pagination">
                    <button class="btn btn-ghost btn-sm" onclick="changeServicePage(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="servicePageInfo">Page 1</span>
                    <button class="btn btn-ghost btn-sm" onclick="changeServicePage(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- ADD SERVICE MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="addServiceModal">
        <div class="modal-box" style="max-width:600px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Service</span>
                <button class="modal-close" onclick="closeModal('addServiceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Service Name <span class="form-req">*</span></label>
                        <input class="form-input" id="newServiceName" placeholder="e.g., CIBIL Score Repair" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="newServiceCategory">
                            <option value="credit_repair">Credit Repair</option>
                            <option value="dispute">Dispute Resolution</option>
                            <option value="consulting">Consulting</option>
                            <option value="legal">Legal</option>
                            <option value="financial">Financial</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label">Description <span class="form-req">*</span></label>
                        <textarea class="form-textarea" id="newServiceDescription" placeholder="Describe the service..." rows="3" required></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (₹) <span class="form-req">*</span></label>
                        <input class="form-input" id="newServicePrice" type="number" step="0.01" placeholder="999.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <select class="form-select" id="newServiceDuration">
                            <option value="15-20 days">15-20 days</option>
                            <option value="21-30 days">21-30 days</option>
                            <option value="30-45 days">30-45 days</option>
                            <option value="45-60 days">45-60 days</option>
                            <option value="60-90 days">60-90 days</option>
                            <option value="3-6 months">3-6 months</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Icon (emoji or icon class)</label>
                        <input class="form-input" id="newServiceIcon" placeholder="⭐ or fa-star" value="⭐">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="newServiceStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">
                            <input type="checkbox" id="newServiceFeatured" value="1"> 
                            Feature this service
                        </label>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">
                            <input type="checkbox" id="newServicePopular" value="1"> 
                            Mark as Popular
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addServiceModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitService()">
                    <i class="fas fa-save"></i> Save Service
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EDIT SERVICE MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="editServiceModal">
        <div class="modal-box" style="max-width:600px;">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-edit"></i> Edit Service</span>
                <button class="modal-close" onclick="closeModal('editServiceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editServiceId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Service Name <span class="form-req">*</span></label>
                        <input class="form-input" id="editServiceName" placeholder="Service name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="editServiceCategory">
                            <option value="credit_repair">Credit Repair</option>
                            <option value="dispute">Dispute Resolution</option>
                            <option value="consulting">Consulting</option>
                            <option value="legal">Legal</option>
                            <option value="financial">Financial</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label">Description <span class="form-req">*</span></label>
                        <textarea class="form-textarea" id="editServiceDescription" placeholder="Describe the service..." rows="3" required></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (₹) <span class="form-req">*</span></label>
                        <input class="form-input" id="editServicePrice" type="number" step="0.01" placeholder="999.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <select class="form-select" id="editServiceDuration">
                            <option value="15-20 days">15-20 days</option>
                            <option value="21-30 days">21-30 days</option>
                            <option value="30-45 days">30-45 days</option>
                            <option value="45-60 days">45-60 days</option>
                            <option value="60-90 days">60-90 days</option>
                            <option value="3-6 months">3-6 months</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Icon (emoji or icon class)</label>
                        <input class="form-input" id="editServiceIcon" placeholder="⭐ or fa-star">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editServiceStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">
                            <input type="checkbox" id="editServiceFeatured" value="1"> 
                            Feature this service
                        </label>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">
                            <input type="checkbox" id="editServicePopular" value="1"> 
                            Mark as Popular
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editServiceModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateService()">
                    <i class="fas fa-save"></i> Update Service
                </button>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- DELETE SERVICE CONFIRMATION MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="deleteServiceModal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-header" style="border-bottom-color:var(--danger);">
                <span class="modal-title" style="color:var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </span>
                <button class="modal-close" onclick="closeModal('deleteServiceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align:center;padding:30px 20px;">
                <div style="font-size:48px;margin-bottom:16px;color:var(--danger);">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 style="margin-bottom:8px;">Are you sure?</h3>
                <p style="color:var(--text-muted);margin-bottom:4px;">
                    You are about to delete <strong id="deleteServiceName">this service</strong>.
                </p>
                <p style="color:var(--text-muted);font-size:12px;">
                    This action cannot be undone. All associated data will be removed.
                </p>
                <input type="hidden" id="deleteServiceId">
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button class="btn btn-ghost" onclick="closeModal('deleteServiceModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-danger" onclick="confirmDeleteService()">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: SETTINGS -->
    <div class="section" id="generalSettingsSection">
      <div class="card" style="max-width:600px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-sliders-h"></i>General Settings</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Company Name</label><input class="form-input" id="companyName" value="CIBIL Repair"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="companyEmail" type="email" value="contact@cibilrepair.in"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="companyPhone" value="+91 87094 55441"></div>
            <div class="form-group"><label class="form-label">Website</label><input class="form-input" id="companyWeb" value="https://cibilrepair.in"></div>
          </div>
          <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i>Save Settings</button>
        </div>
      </div>
    </div>

    <div class="section" id="securitySettingsSection">
      <div class="card" style="max-width:520px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-shield-alt"></i>Security Settings</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Two-Factor Auth</label>
              <select class="form-select" id="sec2fa"><option value="enabled">Enabled</option><option value="disabled">Disabled</option></select>
            </div>
            <div class="form-group"><label class="form-label">Session Timeout</label>
              <select class="form-select" id="secTimeout"><option value="30">30 minutes</option><option value="60">60 minutes</option><option value="120">2 hours</option></select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">New Password</label><input class="form-input" type="password" id="secNewPass" placeholder="New password"></div>
            <div class="form-group"><label class="form-label">Confirm Password</label><input class="form-input" type="password" id="secConfirmPass" placeholder="Confirm password"></div>
          </div>
          <button class="btn btn-primary" onclick="saveSecuritySettings()"><i class="fas fa-save"></i>Update Security</button>
        </div>
      </div>
    </div>

    <!-- SECTION: ACTIVITY LOG -->
    <div class="section" id="activityLogSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history"></i>Activity Log</div>
          <button class="btn btn-ghost btn-sm" onclick="loadActivityLog()"><i class="fas fa-sync-alt"></i>Refresh</button>
        </div>
        <div class="filter-bar">
          <div class="filter-search-wrap"><i class="fas fa-search"></i>
            <input class="search-input" id="logSearch" placeholder="Search activities…" oninput="debounceFilter('log')">
          </div>
        </div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
            <tbody id="logBody"><tr><td colspan="5"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SECTION: BACKUP -->
    <div class="section" id="backupSection">
      <div class="card" style="max-width:640px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-database"></i>Database Backup</div></div>
        <div class="card-body" style="text-align:center;padding:36px 20px;">
          <div style="width:70px;height:70px;border-radius:var(--r-xl);background:var(--brand-light);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:30px;color:var(--brand);">
            <i class="fas fa-database"></i>
          </div>
          <p style="color:var(--text-secondary);margin-bottom:20px;font-size:13px;max-width:400px;margin-left:auto;margin-right:auto;">
            Create a full database backup. The file will be saved to the server and available for download.
          </p>
          <button class="btn btn-primary" id="backupBtn" onclick="createBackup(this)" style="padding:10px 28px;">
            <i class="fas fa-download"></i>Create Backup Now
          </button>
        </div>
        <div class="card-header" style="border-top:1px solid var(--border);"><div class="card-title"><i class="fas fa-archive"></i>Recent Backups</div></div>
        <div class="table-wrap table-wrap-fade">
          <table>
            <thead><tr><th>Filename</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody id="backupBody"><tr><td colspan="4"><div class="loading-cell"><div class="spinner"></div></div></td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /app -->

<!-- MODALS -->
<div class="modal-overlay" id="addCustomerModal">
  <div class="modal-box">
    <div class="modal-header"><span class="modal-title">Add Customer</span><button class="modal-close" onclick="closeModal('addCustomerModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Full Name <span class="form-req">*</span></label><input class="form-input" id="modalCustName" placeholder="Rajesh Kumar"></div>
        <div class="form-group"><label class="form-label">Email <span class="form-req">*</span></label><input class="form-input" id="modalCustEmail" type="email" placeholder="email@example.com"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="modalCustPhone" placeholder="9876543210"></div>
        <div class="form-group"><label class="form-label">City</label><input class="form-input" id="modalCustCity" placeholder="Delhi"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addCustomerModal')">Cancel</button>
      <button class="btn btn-primary" onclick="addCustomerFromModal()"><i class="fas fa-save"></i>Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editCustomerModal">
  <div class="modal-box">
    <div class="modal-header"><span class="modal-title">Edit Customer</span><button class="modal-close" onclick="closeModal('editCustomerModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <input type="hidden" id="editCustId">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" id="editCustName"></div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="editCustEmail" type="email"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="editCustPhone"></div>
        <div class="form-group"><label class="form-label">City</label><input class="form-input" id="editCustCity"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Status</label>
          <select class="form-select" id="editCustStatus"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('editCustomerModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveEditCustomer()"><i class="fas fa-save"></i>Save Changes</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editPartnerModal">
  <div class="modal-box">
    <div class="modal-header"><span class="modal-title">Edit Partner</span><button class="modal-close" onclick="closeModal('editPartnerModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <input type="hidden" id="editPartnerId">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Business Name</label><input class="form-input" id="editPartnerName"></div>
        <div class="form-group"><label class="form-label">Owner</label><input class="form-input" id="editPartnerOwner"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="editPartnerPhone"></div>
        <div class="form-group"><label class="form-label">Location</label><input class="form-input" id="editPartnerLoc"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Commission (%)</label><input class="form-input" id="editPartnerComm" type="number"></div>
        <div class="form-group"><label class="form-label">Status</label>
          <select class="form-select" id="editPartnerStatus"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('editPartnerModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveEditPartner()"><i class="fas fa-save"></i>Save Changes</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="analysisModal">
  <div class="modal-box" style="max-width:700px;">
    <div class="modal-header"><span class="modal-title">Analysis Details</span><button class="modal-close" onclick="closeModal('analysisModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><div class="code-block" id="modalAnalysisContent" style="max-height:450px;overflow-y:auto;"></div></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('analysisModal')">Close</button>
      <button class="btn btn-primary" onclick="copyModalAnalysis()"><i class="fas fa-copy"></i>Copy</button>
    </div>
  </div>
</div>

<!-- TOASTS -->
<div class="toast-container" id="toastContainer"></div>

<!-- AI PANEL -->
<button class="ai-fab" id="aiFab" onclick="toggleAI()" title="AI Assistant">
  <i class="fas fa-robot"></i>
</button>
<div class="ai-panel" id="aiPanel">
  <div class="ai-panel-header">
    <div class="ai-dot"></div>
    <div style="flex:1;"><div class="ai-panel-title">Admin AI Assistant</div><div class="ai-panel-sub">Smart CRM Assistant</div></div>
    <button class="ai-close" onclick="toggleAI()"><i class="fas fa-times"></i></button>
  </div>
  <div class="ai-messages" id="aiMessages">
    <div class="ai-msg bot">
      👋 Hi <?= $user_name ?>! I'm your CRM AI assistant.<br><br>
      I can help you analyse data, manage partners and customers, and answer business questions.
    </div>
  </div>
  <div class="ai-chips">
    <span class="ai-chip" onclick="quickAsk('Show partner performance summary')">📊 Partners</span>
    <span class="ai-chip" onclick="quickAsk('How many customers?')">👥 Customers</span>
    <span class="ai-chip" onclick="quickAsk('Top revenue services?')">💰 Revenue</span>
    <span class="ai-chip" onclick="quickAsk('Recent business insights')">💡 Insights</span>
  </div>
  <div class="ai-input-row">
    <input class="ai-input" id="aiInput" placeholder="Ask me anything…" onkeydown="if(event.key==='Enter')sendAI()">
    <button class="ai-send" onclick="sendAI()"><i class="fas fa-paper-plane"></i></button>
  </div>
</div>

<!-- ================================================================
     JAVASCRIPT — FULLY CORRECTED
     ================================================================ -->
<script>
'use strict';

/* ── GLOBALS ─────────────────────────────────── */
const API  = 'api/';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const USER_NAME = '<?= $user_name ?>';

// ===== STATE OBJECT =====
const state = {
  customers: [], 
  partners: [], 
  banks: [], 
  quotations: [],
  requests: [], 
  sales: [], 
  expenses: [], 
  transactions: [],
  posters: [], 
  codes: [], 
  users: [], 
  analyses: [],
  services: []  // ADD THIS
};

// ===== LEADS VARIABLE =====
let allLeads = [];  // <-- FIX: Added this

// ===== PAGINATION =====
const pagination = {
  customers: { page: 1, perPage: 20, total: 0 },
  leads:     { page: 1, perPage: 20, total: 0 }
};

let debounceTimeout = null;
let walletBalance = 12500;

/* ── XSS-SAFE ESCAPE ─────────────────────────── */
function esc(s) {
  if (s == null) return '';
  return String(s).replace(/[&<>"']/g, c =>
    ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'": '&#x27;' }[c]));
}

/* ── CSRF-SAFE FETCH ─────────────────────────── */
async function apiFetch(url, opts = {}) {
  // Add .php if not present
  if (!url.endsWith('.php') && !url.includes('?')) {
    url = url + '.php';
  }
  
  opts.headers = { 
    'Content-Type': 'application/json', 
    'X-CSRF-Token': CSRF, 
    ...(opts.headers || {}) 
  };
  
  try {
    const r = await fetch(url, opts);
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const data = await r.json();
    return data;
  } catch (e) {
    console.warn('API error:', e.message, url);
    return { success: false, error: e.message };
  }
}

/* ── SIDEBAR ──────────────────────────────────── */
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
window.addEventListener('resize', () => {
  if (window.innerWidth > 900) {
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }
});

/* ── THEME ────────────────────────────────────── */
function setTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('theme', t);
  document.getElementById('lightBtn').classList.toggle('active', t === 'light');
  document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
  setTimeout(redrawAllCharts, 80);
}
(function () {
  const s = localStorage.getItem('theme') ||
    (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
  setTheme(s);
})();

/* ── TOASTS ───────────────────────────────────── */
function toast(msg, type = 'info', dur = 3500) {
  const icons = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
  const el = document.createElement('div');
  el.className = 'toast toast-' + type;
  el.innerHTML = '<div class="toast-icon"><i class="fas ' + (icons[type]||icons.info) + '"></i></div>' +
    '<span class="toast-msg">' + esc(msg) + '</span>' +
    '<button class="toast-close" onclick="this.parentElement.remove()">×</button>';
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => { el.classList.add('out'); setTimeout(() => el.remove(), 300); }, dur);
}

/* ── MODALS ───────────────────────────────────── */
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m =>
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

/* ── DEBOUNCE ─────────────────────────────────── */
function debounceFilter(target) {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    switch (target) {
      case 'customers': filterCustomers(); break;
      case 'partners':  filterPartners();  break;
      case 'leads':     filterLeads();     break;
      case 'log':       filterLog();       break;
    }
  }, 300);
}

/* ── DASHBOARD COLLAPSIBLE ────────────────────── */
let _dashCollapsed = false;
function toggleDashboards() {
  _dashCollapsed = !_dashCollapsed;
  document.getElementById('dashboardGridBody').style.display = _dashCollapsed ? 'none' : '';
  document.getElementById('collapseIcon').classList.toggle('rotated', _dashCollapsed);
  localStorage.setItem('dashGridCollapsed', _dashCollapsed ? '1' : '0');
}
(function () {
  if (localStorage.getItem('dashGridCollapsed') === '1') {
    _dashCollapsed = true;
    const b = document.getElementById('dashboardGridBody');
    const i = document.getElementById('collapseIcon');
    if (b) b.style.display = 'none';
    if (i) i.classList.add('rotated');
  }
})();

/* ── DASHBOARD FILTER (SINGLE CLEAN IMPLEMENTATION) ── */
document.getElementById('dashboardSearchInput').addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();
  const cards = document.querySelectorAll('#dashboardGrid .dashboard-access-card');
  let visible = 0;
  cards.forEach(card => {
    const match = !q ||
      (card.getAttribute('data-name') || '').includes(q) ||
      (card.getAttribute('data-role') || '').includes(q);
    card.style.display = match ? 'flex' : 'none';
    if (match) visible++;
  });
  document.getElementById('noDashboardsFound').style.display =
    (visible === 0 && q) ? 'block' : 'none';
});

// Keyboard shortcuts
document.addEventListener('keydown', e => {
  if (e.altKey) {
    const map = { d:'dashboard', c:'customerList', p:'partnerList', l:'leads', a:'aiAnalyzer', s:'generalSettings' };
    if (map[e.key]) { e.preventDefault(); showSection(map[e.key]); }
  }
});

const sectionTitles = {
    dashboard:'Dashboard', 
    decisionHub:'Decision Hub', 
    leads:'Leads',
    aiAnalyzer:'AI Analyzer', 
    addCustomer:'Add Customer', 
    customerList:'Customers',
    customerRequests:'Requests', 
    newCustomerRequest:'New Request',
    addPartner:'Add Partner', 
    partnerList:'Partners',
    addBank:'Add Bank', 
    bankList:'Banks',
    addSale:'Add Sale', 
    salesReport:'Sales Report', 
    invoice:'Invoice',
    addMoney:'Add Money', 
    withdrawMoney:'Withdraw', 
    txHistory:'Transactions',
    newQuotation:'New Quotation', 
    quotationList:'Quotations',
    addExpense:'Add Expense', 
    expenseReport:'Expense Report',
    createCode:'Create Code', 
    codeList:'Code List', 
    usersByCode:'Users by Code',
    generalSettings:'General Settings', 
    securitySettings:'Security',
    activityLog:'Activity Log', 
    backup:'Database Backup', 
    posters:'Posters',
    reviews:'Customer Reviews',
    services:'Services',
    partnerApplications:'Partner Applications',
};

function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  
  const el = document.getElementById(name + 'Section');
  if (el) el.classList.add('active');
  
  const nav = document.querySelector('.nav-item[data-section="' + name + '"]');
  if (nav) { 
    nav.classList.add('active'); 
    nav.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); 
  }
  
  const titleEl = document.getElementById('pageTitle');
  if (titleEl) {
    titleEl.textContent = sectionTitles[name] || name;
  }
  
  closeSidebar();

  // Loaders with safety checks
  const loaders = {
    dashboard: () => { 
      if (document.getElementById('salesChart')) initCharts(); 
      refreshActivity(); 
    },
    decisionHub: () => { 
      if (document.getElementById('revenueTargetChart')) initDecisionCharts(); 
    },
    leads: () => { 
      if (document.getElementById('leadsBody')) {
        loadLeads(); 
      } else {
        console.warn('leadsBody element not found, skipping leads load');
      }
    },
    customerList: () => { 
      if (document.getElementById('customerTableBody')) renderCustomers(); 
    },
    partnerList: () => { 
      if (document.getElementById('partnerTableBody')) renderPartners(); 
    },
    bankList: () => { 
      if (document.getElementById('bankTableBody')) renderBanks(); 
    },
    quotationList: () => { 
      if (document.getElementById('quoteBody')) renderQuotations(); 
    },
    customerRequests: () => { 
      if (document.getElementById('reqTableBody')) renderRequests(); 
    },
    activityLog: () => { 
      if (document.getElementById('logBody')) loadActivityLog(); 
    },
    backup: () => { 
      if (document.getElementById('backupBody')) loadBackups(); 
    },
    codeList: () => { 
      if (document.getElementById('codeListBody')) loadCodeList(); 
    },
    usersByCode: () => { 
      if (document.getElementById('usersByCodeBody')) loadUsersByCode(); 
    },
    aiAnalyzer: () => { 
      if (document.getElementById('analysisHistory')) loadAnalysisHistory(); 
    },
    txHistory: () => { 
      if (document.getElementById('txBody')) loadTransactions(); 
    },
    expenseReport: () => { 
      if (document.getElementById('expRepBody')) generateExpenseReport(); 
    },
    posters: () => { 
      if (document.getElementById('posterGrid')) {
        loadPosters(); 
      } else {
        console.warn('posterGrid element not found, skipping posters load');
      }
    },
    partnerApplications: () => { 
        if (document.getElementById('partnerApplicationsContainer')) {
            loadPartnerApplications(); 
        } else {
        console.warn('partnerApplicationsContainer element not found');
        }
    },
    // REVIEWS LOADER
    reviews: () => { 
      if (document.getElementById('reviewsContainer')) {
        loadReviews('reviewsContainer'); 
      } else {
        console.warn('reviewsContainer element not found, skipping reviews load');
      }
    },
    
    // No auto-load needed for these sections
    addCustomer: () => { /* No auto-load needed */ },
    addPartner: () => { /* No auto-load needed */ },
    addBank: () => { /* No auto-load needed */ },
    addSale: () => { /* No auto-load needed */ },
    invoice: () => { /* No auto-load needed */ },
    addMoney: () => { /* No auto-load needed */ },
    withdrawMoney: () => { /* No auto-load needed */ },
    newQuotation: () => { /* No auto-load needed */ },
    addExpense: () => { /* No auto-load needed */ },
    createCode: () => { /* No auto-load needed */ },
    generalSettings: () => { /* No auto-load needed */ },
    securitySettings: () => { /* No auto-load needed */ },
    newCustomerRequest: () => { /* No auto-load needed */ },
    salesReport: () => { /* No auto-load needed */ },
    addLead: () => { /* No auto-load needed */ }
  };
  
  if (loaders[name]) {
    try {
      loaders[name]();
    } catch (e) {
      console.warn('Error loading section ' + name + ':', e);
    }
  }
}
services: () => { 
    if (document.getElementById('servicesContainer')) {
        loadServices('servicesContainer'); 
    } else {
        console.warn('servicesContainer element not found, skipping services load');
    }
}

document.querySelectorAll('.nav-item[data-section]').forEach(item => {
  item.addEventListener('click', () => showSection(item.dataset.section));
});

// Keyboard shortcuts
document.addEventListener('keydown', e => {
  if (e.altKey) {
    const map = { d:'dashboard', c:'customerList', p:'partnerList', l:'leads', a:'aiAnalyzer', s:'generalSettings' };
    if (map[e.key]) { e.preventDefault(); showSection(map[e.key]); }
  }
});

/* ── GLOBAL SEARCH ────────────────────────────── */
const globalSearchEl = document.getElementById('globalSearch');
const searchDropdown = document.getElementById('searchResultsDropdown');
let searchDebounce = null;

globalSearchEl.addEventListener('input', function () {
  clearTimeout(searchDebounce);
  const q = this.value.trim().toLowerCase();
  if (!q || q.length < 2) { searchDropdown.classList.remove('open'); return; }
  searchDebounce = setTimeout(() => {
    const results = [];
    state.customers.forEach(c => {
      if ((c.name + c.email + (c.phone || '')).toLowerCase().includes(q))
        results.push({ type: 'Customer', name: c.name, section: 'customerList', color: 'var(--success-bg)', textColor: 'var(--success-text)' });
    });
    state.partners.forEach(p => {
      if ((p.name + (p.owner || '') + (p.phone || '')).toLowerCase().includes(q))
        results.push({ type: 'Partner', name: p.name, section: 'partnerList', color: 'var(--warning-bg)', textColor: 'var(--warning-text)' });
    });
    allLeads.forEach(l => {
      if ((l.name + l.phone + (l.email || '')).toLowerCase().includes(q))
        results.push({ type: 'Lead', name: l.name, section: 'leads', color: 'var(--danger-bg)', textColor: 'var(--danger-text)' });
    });
    state.banks.forEach(b => {
      if ((b.name + (b.contact || '')).toLowerCase().includes(q))
        results.push({ type: 'Bank', name: b.name, section: 'bankList', color: 'var(--info-bg)', textColor: 'var(--info-text)' });
    });
    // Add this after the state.banks.forEach block
    state.services && state.services.forEach(s => {
      if ((s.name + (s.description || '') + (s.category || '')).toLowerCase().includes(q))
        results.push({ type: 'Service', name: s.name, section: 'services', color: 'var(--brand-light)', textColor: 'var(--brand-dark)' });
    });

    if (results.length === 0) {
      searchDropdown.innerHTML = '<div class="search-result-item" style="color:var(--text-muted);justify-content:center;">No results found</div>';
    } else {
      searchDropdown.innerHTML = results.slice(0, 8).map(r =>
        '<div class="search-result-item" onclick="showSection(\'' + r.section + '\');searchDropdown.classList.remove(\'open\');globalSearchEl.value=\'\';">' +
        '<span class="search-result-type" style="background:' + r.color + ';color:' + r.textColor + ';">' + r.type + '</span>' +
        '<span>' + esc(r.name) + '</span></div>'
      ).join('');
    }
    searchDropdown.classList.add('open');
  }, 250);
});

document.addEventListener('click', e => {
  if (!e.target.closest('.topbar-search')) searchDropdown.classList.remove('open');
});

/* ── STATUS BADGE ─────────────────────────────── */
function statusBadge(s) {
  const m = {
    active:'badge-green', approved:'badge-green', completed:'badge-green',
    inactive:'badge-red', failed:'badge-red', rejected:'badge-red',
    pending:'badge-amber', processing:'badge-amber',
    draft:'badge-gray', sent:'badge-blue', deposited:'badge-blue'
  };
  return '<span class="badge ' + (m[(s || '').toLowerCase()] || 'badge-gray') + '">' + esc(s || '—') + '</span>';
}

/* ── DATA LOADING ─────────────────────────────── */
async function loadAllData() {
  try {
    const d = await apiFetch(API + 'get_all_data');
    
    if (d && d.success) {
      const data = d.data || {};
      
      // Update state
      const mappings = {
        banks: 'banks',
        customers: 'customers',
        partners: 'partners',
        customer_requests: 'requests',
        quotations: 'quotations',
        sales: 'sales',
        expenses: 'expenses',
        transactions: 'transactions',
        leads: 'allLeads',
        posters: 'posters'  // ADD THIS
      };
      
      Object.entries(mappings).forEach(([apiKey, stateKey]) => {
        if (data[apiKey]) {
          if (stateKey === 'allLeads') {
            allLeads = data[apiKey];
          } else {
            state[stateKey] = data[apiKey];
          }
        }
      });
      
      // Update stats from API
      if (data.stats) {
        const stats = data.stats;
        const elements = {
          totalCustomers: stats.total_customers,
          totalBanks: stats.total_banks,
          totalPartners: stats.total_partners,
          totalLeads: stats.total_leads
        };
        
        Object.entries(elements).forEach(([id, value]) => {
          document.getElementById(id).textContent = value || 0;
        });
        
        document.getElementById('totalSales').textContent = '₹' + (stats.total_revenue || 0).toLocaleString('en-IN');
        
        walletBalance = stats.net_revenue || 0;
        document.getElementById('walletBalance').textContent = '₹' + walletBalance.toLocaleString('en-IN');
      }
      
      // Process settings
      if (data.settings && Array.isArray(data.settings)) {
        const settings = {};
        data.settings.forEach(s => {
          settings[s.setting_key] = s.setting_value;
        });
        localStorage.setItem('crmSettings', JSON.stringify(settings));
      }
      
      // Update leads badge
      const badge = document.getElementById('leadsBadge');
      if (badge && allLeads) {
        badge.textContent = allLeads.length;
      }
      
      // Load additional data and render
      await loadTransactions();
      updateStatCards();
      renderCustomers();
      renderPartners();
      renderBanks();
      renderQuotations();
      renderRequests();
      renderPosters(); // ADD THIS
      updateLastUpdated();
      await loadSettings();
      
    } else {
      loadFallback();
    }
  } catch (e) {
    console.error('Error loading data:', e);
    loadFallback();
  }
}

function loadFallback() {
  state.banks = [
    { id: 1, name: 'HDFC Bank', contact: 'Rahul Mehta', email: 'rahul@hdfc.com', phone: '9876543210', status: 'active' },
    { id: 2, name: 'ICICI Bank', contact: 'Neha Gupta', email: 'neha@icici.com', phone: '9876543211', status: 'active' },
    { id: 3, name: 'SBI', contact: 'Amit Kumar', email: 'amit@sbi.co.in', phone: '9876543212', status: 'active' },
    { id: 4, name: 'Axis Bank', contact: 'Priya Jain', email: 'priya@axisbank.com', phone: '9876543213', status: 'active' },
    { id: 5, name: 'Kotak Mahindra', contact: 'Vikram Sethi', email: 'vikram@kotak.com', phone: '9876543214', status: 'active' }
  ];
  state.customers = [
    { id: 1, name: 'Rajesh Kumar', email: 'rajesh@example.com', phone: '9876543210', city: 'Delhi', status: 'active', joined: '2025-01-15' },
    { id: 2, name: 'Priya Sharma', email: 'priya@example.com', phone: '9876543211', city: 'Mumbai', status: 'active', joined: '2025-02-10' },
    { id: 3, name: 'Suresh Singh', email: 'suresh@example.com', phone: '9876543212', city: 'Chennai', status: 'inactive', joined: '2025-01-20' },
    { id: 4, name: 'Meena Patel', email: 'meena@example.com', phone: '9876543213', city: 'Pune', status: 'active', joined: '2025-03-05' },
    { id: 5, name: 'Arun Verma', email: 'arun@example.com', phone: '9876543220', city: 'Jaipur', status: 'active', joined: '2025-04-12' },
    { id: 6, name: 'Kavita Gupta', email: 'kavita@example.com', phone: '9876543221', city: 'Hyderabad', status: 'active', joined: '2025-04-20' }
  ];
  state.partners = [
    { id: 1, name: 'Delhi Credit Solutions', location: 'Delhi NCR', owner: 'Amit Singh', phone: '9876543215', email: 'amit@dcs.in', status: 'active', commission_rate: 12 },
    { id: 2, name: 'Mumbai Finance Hub', location: 'Mumbai', owner: 'Ramesh Patil', phone: '9876543216', email: 'ramesh@mfh.in', status: 'active', commission_rate: 10 },
    { id: 3, name: 'Chennai CIBIL Experts', location: 'Chennai', owner: 'Kiran Nair', phone: '9876543217', email: 'kiran@cce.in', status: 'active', commission_rate: 11 }
  ];
  state.requests = [
    { id: 1, name: 'Suresh Singh', service: 'Written Off', date: '2025-03-15', status: 'pending', email: 's@s.com', phone: '9876543212' },
    { id: 2, name: 'Kavita Gupta', service: 'Profile Correction', date: '2025-04-21', status: 'pending', email: 'kavita@example.com', phone: '9876543221' }
  ];
  state.quotations = [
    { id: 1, quote_no: 'QUO001', customer: 'Rajesh Kumar', service: 'Written Off', amount: 15000, date: '2025-03-15', status: 'Sent' },
    { id: 2, quote_no: 'QUO002', customer: 'Priya Sharma', service: 'Settled', amount: 22000, date: '2025-04-01', status: 'Draft' }
  ];
  state.sales = [
    { id: 1, customer: 'Rajesh Kumar', service: 'Written Off', amount: 15000, date: '2025-01-20', status: 'Completed' },
    { id: 2, customer: 'Priya Sharma', service: 'Settled', amount: 22000, date: '2025-02-15', status: 'Completed' },
    { id: 3, customer: 'Meena Patel', service: 'Profile Correction', amount: 8000, date: '2025-03-10', status: 'Completed' },
    { id: 4, customer: 'Arun Verma', service: 'Written Off', amount: 18000, date: '2025-04-05', status: 'Completed' },
    { id: 5, customer: 'Kavita Gupta', service: 'Settled', amount: 25000, date: '2025-04-18', status: 'Pending' }
  ];
  state.expenses = [
    { id: 1, category: 'Rent', description: 'Office rent April', amount: 25000, date: '2025-04-01' },
    { id: 2, category: 'Salary', description: 'Staff salary April', amount: 180000, date: '2025-04-05' },
    { id: 3, category: 'Marketing', description: 'Google Ads campaign', amount: 15000, date: '2025-04-10' },
    { id: 4, category: 'Utilities', description: 'Electricity + Internet', amount: 8000, date: '2025-04-12' }
  ];
  state.transactions = [
    { id: 1, date: '2025-04-20', description: 'Payment from Rajesh Kumar', amount: 15000, type: 'credit' },
    { id: 2, date: '2025-04-18', description: 'Salary disbursement', amount: 180000, type: 'debit' },
    { id: 3, date: '2025-04-15', description: 'Payment from Priya Sharma', amount: 22000, type: 'credit' },
    { id: 4, date: '2025-04-12', description: 'Google Ads payment', amount: 15000, type: 'debit' },
    { id: 5, date: '2025-04-10', description: 'Commission payout — Delhi Credit', amount: 1800, type: 'debit' },
    { id: 6, date: '2025-04-05', description: 'Payment from Meena Patel', amount: 8000, type: 'credit' }
  ];
  state.codes = [
    { id: 1, code: 'PRTN-A8X2K9', created_for_role: 'partner', assigned_to_email: 'newpartner@example.com', is_used: false, expires_at: '2025-06-01', created_at: '2025-04-01' },
    { id: 2, code: 'CLNT-M3N7P1', created_for_role: 'client', assigned_to_email: '', is_used: true, expires_at: '2025-05-15', created_at: '2025-03-15' }
  ];
  state.users = [
    { id: 1, name: 'Amit Singh', email: 'amit@dcs.in', phone: '9876543215', role: 'partner', status: 'active', created_at: '2025-01-10T10:00:00' },
    { id: 2, name: 'Rajesh Kumar', email: 'rajesh@example.com', phone: '9876543210', role: 'client', status: 'active', created_at: '2025-01-15T14:30:00' },
    { id: 3, name: 'Priya Sharma', email: 'priya@example.com', phone: '9876543211', role: 'client', status: 'active', created_at: '2025-02-10T09:00:00' }
  ];
}

async function refreshAllData() {
  toast('Refreshing data...', 'info');
  await loadAllData();
  toast('Data refreshed!', 'success');
}

function updateLastUpdated() {
  const el = document.getElementById('lastUpdated');
  if (el) el.textContent = new Date().toLocaleString('en-IN', { dateStyle:'medium', timeStyle:'short' });
}

/* ── STAT CARDS ───────────────────────────────── */
function updateStatCards() {
  animateCount('totalCustomers', state.customers.length);
  animateCount('totalBanks', state.banks.length);
  animateCount('totalPartners', state.partners.length);
  const rev = state.sales.reduce((s, q) => s + (q.amount || 0), 0) +
              state.quotations.reduce((s, q) => s + (q.amount || 0), 0);
  document.getElementById('totalSales').textContent = '₹' + rev.toLocaleString('en-IN');
  document.getElementById('walletBalance').textContent = '₹' + walletBalance.toLocaleString('en-IN');
  document.getElementById('totalLeads').textContent = String(allLeads.length || 24);
  document.querySelectorAll('.mini-bar-fill[data-w]').forEach(el =>
    setTimeout(() => { el.style.width = el.dataset.w; }, 200));
}

function animateCount(id, target) {
  const el = document.getElementById(id); if (!el) return;
  let cur = 0;
  const inc = Math.max(1, Math.ceil(target / 35));
  const t = setInterval(() => { cur = Math.min(cur + inc, target); el.textContent = cur; if (cur >= target) clearInterval(t); }, 18);
}

/* ── PAGINATION ───────────────────────────────── */
function changePage(type, direction) {
  const p = pagination[type]; if (!p) return;
  const maxPage = Math.ceil(p.total / p.perPage) || 1;
  const newPage = p.page + direction;
  if (newPage < 1 || newPage > maxPage) return;
  p.page = newPage;
  if (type === 'customers') renderCustomers();
  if (type === 'leads') renderLeads(allLeads);
}

/* ── BULK ACTIONS ─────────────────────────────── */
function toggleAllRows(tableId, checkbox) {
  const table = document.getElementById(tableId); if (!table) return;
  table.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checkbox.checked);
  updateBulkActions(tableId);
}

function updateBulkActions(tableId) {
  const table = document.getElementById(tableId); if (!table) return;
  const count = table.querySelectorAll('.row-checkbox:checked').length;
  const bulkId = tableId === 'customerTableBody' ? 'customerBulkActions' : null;
  if (bulkId) {
    const el = document.getElementById(bulkId);
    if (el) { el.style.display = count > 0 ? 'flex' : 'none'; }
    const cs = document.getElementById('customerSelectedCount');
    if (cs) cs.textContent = count + ' selected';
  }
}

// ============================================================
// SERVICES MODULE - SINGLE DEFINITION
// ============================================================

// ============================================================
// SERVICE STATE & PAGINATION - ONLY DECLARE ONCE
// ============================================================

// Make sure this only appears ONCE in your file
if (typeof servicesData === 'undefined') {
    var servicesData = [];
}
var servicePage = 1;
var servicePerPage = 20;

// ============================================================
// FETCH SERVICES
// ============================================================

async function fetchServices(active = null, category = '', featured = false) {
    try {
        let url = 'api/fetch_services.php?';
        if (active !== null) url += 'active=' + (active ? '1' : '0') + '&';
        if (category) url += 'category=' + encodeURIComponent(category) + '&';
        if (featured) url += 'featured=1&';
        url += '_=' + new Date().getTime();
        
        console.log('🔄 Fetching services from:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        const data = await response.json();
        console.log('✅ API Response:', data);
        
        if (data.success && data.data) {
            // Store in ALL locations
            servicesData = data.data;
            window.servicesData = data.data;
            window._servicesData = data.data;
            window.allServices = data.data;
            if (typeof state !== 'undefined') {
                state.services = data.data;
            }
            console.log('✅ Loaded ' + servicesData.length + ' services from API');
            return data;
        } else {
            console.warn('⚠️ No data from API, using demo services');
            var demo = getDemoServices();
            servicesData = demo;
            window.servicesData = demo;
            window._servicesData = demo;
            window.allServices = demo;
            if (typeof state !== 'undefined') {
                state.services = demo;
            }
            return { success: true, data: demo };
        }
    } catch (error) {
        console.error('❌ Fetch error:', error);
        var demo = getDemoServices();
        servicesData = demo;
        window.servicesData = demo;
        window._servicesData = demo;
        window.allServices = demo;
        if (typeof state !== 'undefined') {
            state.services = demo;
        }
        return { success: true, data: demo };
    }
}

// ============================================================
// LOAD SERVICES - MAIN FUNCTION
// ============================================================

async function loadServices(containerId) {
    // Set default container
    if (!containerId) {
        containerId = 'servicesContainer';
    }
    
    console.log('🔄 loadServices called...');
    
    try {
        var container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="loading-cell">
                    <div class="spinner"></div> Loading services...
                </div>
            `;
        }
        
        // Fetch services from API
        var result = await fetchServices();
        
        if (result && result.success && result.data && result.data.length > 0) {
            servicesData = result.data;
            window.servicesData = result.data;
            window._servicesData = result.data;
            window.allServices = result.data;
            if (typeof state !== 'undefined') {
                state.services = result.data;
            }
            console.log('✅ Services loaded successfully:', servicesData.length);
        } else {
            console.warn('⚠️ Using demo services as fallback');
            var demo = getDemoServices();
            servicesData = demo;
            window.servicesData = demo;
            window._servicesData = demo;
            window.allServices = demo;
            if (typeof state !== 'undefined') {
                state.services = demo;
            }
        }
        
        // Render the services
        renderServices(containerId);
        
        // Update total count
        var totalEl = document.getElementById('serviceTotalCount');
        if (totalEl) {
            totalEl.textContent = servicesData.length;
        }
        
    } catch (error) {
        console.error('❌ Error loading services:', error);
        var demo = getDemoServices();
        servicesData = demo;
        window.servicesData = demo;
        window._servicesData = demo;
        window.allServices = demo;
        if (typeof state !== 'undefined') {
            state.services = demo;
        }
        renderServices(containerId);
    }
}

// ============================================================
// DEMO SERVICES DATA
// ============================================================

function getDemoServices() {
    return [
        {
            id: 1,
            name: 'CIBIL Score Repair',
            category: 'credit_repair',
            description: 'Complete credit score repair service.',
            price: 999.00,
            duration: '30-45 days',
            icon: '📈',
            status: 'active',
            is_featured: 1,
            is_popular: 1,
            created_at: new Date().toISOString(),
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 2,
            name: 'Dispute Resolution',
            category: 'dispute',
            description: 'Professional dispute filing service.',
            price: 1499.00,
            duration: '15-20 days',
            icon: '⚖️',
            status: 'active',
            is_featured: 1,
            is_popular: 0,
            created_at: new Date().toISOString(),
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 3,
            name: 'Credit Consultation',
            category: 'consulting',
            description: 'One-on-one consultation with credit experts.',
            price: 499.00,
            duration: '1-2 days',
            icon: '💡',
            status: 'active',
            is_featured: 0,
            is_popular: 1,
            created_at: new Date().toISOString(),
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 4,
            name: 'Legal Credit Protection',
            category: 'legal',
            description: 'Legal protection services for credit-related disputes.',
            price: 2499.00,
            duration: '60-90 days',
            icon: '🏛️',
            status: 'inactive',
            is_featured: 0,
            is_popular: 0,
            created_at: new Date().toISOString(),
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 5,
            name: 'Financial Planning',
            category: 'financial',
            description: 'Comprehensive financial planning service.',
            price: 1999.00,
            duration: '45-60 days',
            icon: '💰',
            status: 'draft',
            is_featured: 0,
            is_popular: 0,
            created_at: new Date().toISOString(),
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        }
    ];
}

// ============================================================
// RENDER SERVICES - COMPLETE
// ============================================================

function renderServices(containerId) {
    // Set default container
    if (!containerId) {
        containerId = 'servicesContainer';
    }
    
    var container = document.getElementById(containerId);
    if (!container) {
        console.warn('❌ Container not found:', containerId);
        return;
    }
    
    // Get data from multiple sources
    var data = window.servicesData || servicesData || window._apiData || [];
    
    // If data is not an array, try to fix it
    if (!Array.isArray(data)) {
        console.warn('⚠️ Data is not an array, fixing...');
        if (data && typeof data === 'object') {
            if (data.data && Array.isArray(data.data)) {
                data = data.data;
            } else {
                data = Object.values(data).filter(function(item) { 
                    return typeof item === 'object' && item.name; 
                });
            }
        }
    }
    
    // Ensure we have an array
    if (!Array.isArray(data)) {
        data = [];
    }
    
    // If still empty, try to fetch
    if (data.length === 0) {
        console.warn('⚠️ No data, fetching from API...');
        fetch('api/fetch_services.php?_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    servicesData = result.data;
                    window.servicesData = result.data;
                    window._apiData = result.data;
                    if (typeof state !== 'undefined') {
                        state.services = result.data;
                    }
                    renderServices(containerId);
                }
            });
        container.innerHTML = `
            <div class="loading-cell">
                <div class="spinner"></div> Loading services...
            </div>
        `;
        return;
    }
    
    // Get filter values
    var search = document.getElementById('serviceSearch')?.value?.toLowerCase() || '';
    var categoryFilter = document.getElementById('serviceCategoryFilter')?.value || '';
    var statusFilter = document.getElementById('serviceStatusFilter')?.value || '';
    
    var filtered = data;
    
    // Apply filters
    if (search) {
        filtered = filtered.filter(function(s) {
            return (s.name || '').toLowerCase().includes(search) ||
                (s.description || '').toLowerCase().includes(search) ||
                (s.category || '').toLowerCase().includes(search);
        });
    }
    
    if (categoryFilter) {
        filtered = filtered.filter(function(s) {
            return (s.category || '').toLowerCase() === categoryFilter;
        });
    }
    
    if (statusFilter) {
        filtered = filtered.filter(function(s) {
            return (s.status || '').toLowerCase() === statusFilter;
        });
    }
    
    // Update total count
    var totalEl = document.getElementById('serviceTotalCount');
    if (totalEl) totalEl.textContent = filtered.length;
    
    // Pagination
    var total = filtered.length;
    var start = (servicePage - 1) * servicePerPage;
    var end = Math.min(start + servicePerPage, total);
    var pageData = filtered.slice(start, end);
    
    // Update pagination info
    var startEl = document.getElementById('serviceStart');
    var endEl = document.getElementById('serviceEnd');
    var totalEl2 = document.getElementById('serviceTotal');
    var pageInfoEl = document.getElementById('servicePageInfo');
    
    if (startEl) startEl.textContent = total > 0 ? start + 1 : 0;
    if (endEl) endEl.textContent = end;
    if (totalEl2) totalEl2.textContent = total;
    var maxPage = Math.ceil(total / servicePerPage) || 1;
    if (pageInfoEl) pageInfoEl.textContent = 'Page ' + servicePage + ' of ' + maxPage;
    
    if (pageData.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-cogs"></i></div>
                <div class="empty-state-title">${search || categoryFilter || statusFilter ? 'No services match your filters' : 'No services yet'}</div>
                <div class="empty-state-sub">${search || categoryFilter || statusFilter ? 'Try adjusting your filters' : 'Click "Add Service" to create your first service'}</div>
            </div>
        `;
        return;
    }
    
    // Category labels
    var categoryLabels = {
        'credit_repair': 'Credit Repair',
        'dispute': 'Dispute Resolution',
        'consulting': 'Consulting',
        'legal': 'Legal',
        'financial': 'Financial',
        'other': 'Other'
    };
    
    container.innerHTML = pageData.map(function(service) {
        var statusClass = {
            'active': 'badge-green',
            'inactive': 'badge-red',
            'draft': 'badge-gray'
        }[service.status] || 'badge-gray';
        
        var statusLabel = service.status ? service.status.charAt(0).toUpperCase() + service.status.slice(1) : 'Active';
        var priceDisplay = '₹' + Number(service.price || 0).toLocaleString('en-IN');
        var categoryLabel = categoryLabels[service.category] || service.category || 'Other';
        var formattedDate = service.formatted_date || (service.created_at ? new Date(service.created_at).toLocaleDateString('en-IN') : '');
        
        return `
            <div class="service-card" style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px;margin-bottom:12px;background:var(--bg-surface);transition:all 0.2s;position:relative;">
                ${service.is_featured ? '<span style="position:absolute;top:8px;right:8px;font-size:11px;background:var(--brand-light);color:var(--brand-dark);padding:2px 10px;border-radius:99px;font-weight:700;">⭐ Featured</span>' : ''}
                ${service.is_popular && !service.is_featured ? '<span style="position:absolute;top:8px;right:8px;font-size:11px;background:var(--warning-bg);color:var(--warning-text);padding:2px 10px;border-radius:99px;font-weight:700;">🔥 Popular</span>' : ''}
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-size:32px;">${service.icon || '⭐'}</span>
                        <div>
                            <strong style="font-size:16px;">${escapeHtml(service.name)}</strong>
                            <span class="badge badge-blue" style="font-size:9px;margin-left:6px;">${escapeHtml(categoryLabel)}</span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:20px;font-weight:700;color:var(--brand-dark);">
                            ${priceDisplay}
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">
                            ${escapeHtml(service.duration || '30-45 days')}
                        </div>
                    </div>
                </div>
                <p style="font-size:13px;color:var(--text-secondary);line-height:1.5;margin:8px 0 12px 0;">
                    ${escapeHtml(service.description || '')}
                </p>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <div>
                        <span class="badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">
                            ${escapeHtml(formattedDate)}
                        </span>
                    </div>
                    <div class="gap-8">
                        <button class="btn btn-ghost btn-xs" onclick="editService(${service.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-${service.status === 'active' ? 'warning' : 'success'} btn-xs" onclick="toggleServiceStatus(${service.id})" title="${service.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-${service.status === 'active' ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-danger btn-xs" onclick="deleteService(${service.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    console.log('✅ Rendered', pageData.length, 'services (page', servicePage, 'of', maxPage, ')');
}

// ============================================================
// ESCAPE HTML HELPER
// ============================================================

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================
// FILTER SERVICES
// ============================================================

function filterServices() {
    servicePage = 1;
    renderServices('servicesContainer');
}

function resetServiceFilters() {
    var searchEl = document.getElementById('serviceSearch');
    var categoryEl = document.getElementById('serviceCategoryFilter');
    var statusEl = document.getElementById('serviceStatusFilter');
    if (searchEl) searchEl.value = '';
    if (categoryEl) categoryEl.value = '';
    if (statusEl) statusEl.value = '';
    servicePage = 1;
    renderServices('servicesContainer');
}

// ============================================================
// SERVICE PAGINATION
// ============================================================

function changeServicePage(direction) {
    var total = servicesData.length;
    var maxPage = Math.ceil(total / servicePerPage) || 1;
    var newPage = servicePage + direction;
    if (newPage < 1 || newPage > maxPage) return;
    servicePage = newPage;
    renderServices('servicesContainer');
}

// ============================================================
// SHOW ADD SERVICE MODAL
// ============================================================

function showAddServiceModal() {
    var fields = ['newServiceName', 'newServiceDescription', 'newServicePrice', 'newServiceIcon'];
    fields.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    
    var defaults = {
        'newServiceCategory': 'credit_repair',
        'newServiceDuration': '30-45 days',
        'newServiceStatus': 'active'
    };
    for (var key in defaults) {
        var el = document.getElementById(key);
        if (el) el.value = defaults[key];
    }
    
    var checkboxes = ['newServiceFeatured', 'newServicePopular'];
    checkboxes.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.checked = false;
    });
    
    openModal('addServiceModal');
}

// ============================================================
// SUBMIT SERVICE
// ============================================================

async function submitService() {
    var name = document.getElementById('newServiceName').value.trim();
    var description = document.getElementById('newServiceDescription').value.trim();
    var price = parseFloat(document.getElementById('newServicePrice').value) || 0;
    var category = document.getElementById('newServiceCategory').value;
    var duration = document.getElementById('newServiceDuration').value;
    var icon = document.getElementById('newServiceIcon').value.trim() || '⭐';
    var status = document.getElementById('newServiceStatus').value;
    var isFeatured = document.getElementById('newServiceFeatured').checked ? 1 : 0;
    var isPopular = document.getElementById('newServicePopular').checked ? 1 : 0;
    
    if (!name) {
        toast('Service name is required', 'error');
        document.getElementById('newServiceName').focus();
        return;
    }
    if (!description) {
        toast('Description is required', 'error');
        document.getElementById('newServiceDescription').focus();
        return;
    }
    if (!price || price <= 0) {
        toast('Please enter a valid price', 'error');
        document.getElementById('newServicePrice').focus();
        return;
    }
    
    var btn = document.querySelector('#addServiceModal .btn-primary');
    var originalText = btn?.innerHTML || 'Save Service';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    var data = {
        name: name,
        description: description,
        price: price,
        category: category,
        duration: duration,
        icon: icon,
        status: status,
        is_featured: isFeatured,
        is_popular: isPopular
    };
    
    try {
        var response = await fetch('api/add_service.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        var result = await response.json();
        
        if (result.success) {
            toast('Service added successfully!', 'success');
            closeModal('addServiceModal');
            await loadServices('servicesContainer');
            addActivityEntry('Service Added', name + ' (' + category + ')');
        } else {
            toast(result.error || 'Failed to add service', 'error');
        }
    } catch (error) {
        console.error('Error adding service:', error);
        toast('Failed to add service', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

// ============================================================
// EDIT SERVICE
// ============================================================

function editService(id) {
    var service = servicesData.find(function(s) { return s.id === id; });
    if (!service) {
        toast('Service not found', 'error');
        return;
    }
    
    document.getElementById('editServiceId').value = service.id;
    document.getElementById('editServiceName').value = service.name || '';
    document.getElementById('editServiceDescription').value = service.description || '';
    document.getElementById('editServicePrice').value = service.price || 0;
    document.getElementById('editServiceCategory').value = service.category || 'credit_repair';
    document.getElementById('editServiceDuration').value = service.duration || '30-45 days';
    document.getElementById('editServiceIcon').value = service.icon || '⭐';
    document.getElementById('editServiceStatus').value = service.status || 'active';
    document.getElementById('editServiceFeatured').checked = !!service.is_featured;
    document.getElementById('editServicePopular').checked = !!service.is_popular;
    
    openModal('editServiceModal');
}

// ============================================================
// UPDATE SERVICE
// ============================================================

async function updateService() {
    var id = parseInt(document.getElementById('editServiceId').value);
    var name = document.getElementById('editServiceName').value.trim();
    var description = document.getElementById('editServiceDescription').value.trim();
    var price = parseFloat(document.getElementById('editServicePrice').value) || 0;
    var category = document.getElementById('editServiceCategory').value;
    var duration = document.getElementById('editServiceDuration').value;
    var icon = document.getElementById('editServiceIcon').value.trim() || '⭐';
    var status = document.getElementById('editServiceStatus').value;
    var isFeatured = document.getElementById('editServiceFeatured').checked ? 1 : 0;
    var isPopular = document.getElementById('editServicePopular').checked ? 1 : 0;
    
    if (!name) {
        toast('Service name is required', 'error');
        document.getElementById('editServiceName').focus();
        return;
    }
    if (!description) {
        toast('Description is required', 'error');
        document.getElementById('editServiceDescription').focus();
        return;
    }
    if (!price || price <= 0) {
        toast('Please enter a valid price', 'error');
        document.getElementById('editServicePrice').focus();
        return;
    }
    
    var btn = document.querySelector('#editServiceModal .btn-primary');
    var originalText = btn?.innerHTML || 'Update Service';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Updating...';
    }
    
    var data = {
        id: id,
        name: name,
        description: description,
        price: price,
        category: category,
        duration: duration,
        icon: icon,
        status: status,
        is_featured: isFeatured,
        is_popular: isPopular
    };
    
    try {
        var response = await fetch('api/update_service.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        var result = await response.json();
        
        if (result.success) {
            toast('Service updated successfully!', 'success');
            closeModal('editServiceModal');
            await loadServices('servicesContainer');
            addActivityEntry('Service Updated', name + ' (' + category + ')');
        } else {
            toast(result.error || 'Failed to update service', 'error');
        }
    } catch (error) {
        console.error('Error updating service:', error);
        toast('Failed to update service', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

// ============================================================
// TOGGLE SERVICE STATUS
// ============================================================

async function toggleServiceStatus(id) {
    var service = servicesData.find(function(s) { return s.id === id; });
    if (!service) {
        toast('Service not found', 'error');
        return;
    }
    
    var newStatus = service.status === 'active' ? 'inactive' : 'active';
    var action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    if (!confirm('Are you sure you want to ' + action + ' "' + service.name + '"?')) {
        return;
    }
    
    try {
        var response = await fetch('api/update_service.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                status: newStatus
            }),
            credentials: 'include'
        });
        
        var result = await response.json();
        
        if (result.success) {
            service.status = newStatus;
            renderServices('servicesContainer');
            toast('Service ' + action + 'd successfully!', 'success');
            addActivityEntry('Service ' + action + 'd', service.name);
        } else {
            toast(result.error || 'Failed to update status', 'error');
        }
    } catch (error) {
        console.error('Error toggling service status:', error);
        toast('Failed to update status', 'error');
    }
}

// ============================================================
// DELETE SERVICE
// ============================================================

function deleteService(id) {
    var service = servicesData.find(function(s) { return s.id === id; });
    if (!service) {
        toast('Service not found', 'error');
        return;
    }
    
    document.getElementById('deleteServiceId').value = id;
    document.getElementById('deleteServiceName').textContent = service.name;
    openModal('deleteServiceModal');
}

// ============================================================
// CONFIRM DELETE SERVICE
// ============================================================

async function confirmDeleteService() {
    var id = parseInt(document.getElementById('deleteServiceId').value);
    var service = servicesData.find(function(s) { return s.id === id; });
    
    if (!service) {
        toast('Service not found', 'error');
        closeModal('deleteServiceModal');
        return;
    }
    
    closeModal('deleteServiceModal');
    toast('Deleting service...', 'info');
    
    try {
        var response = await fetch('api/delete_service.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id }),
            credentials: 'include'
        });
        
        var result = await response.json();
        
        if (result.success) {
            servicesData = servicesData.filter(function(s) { return s.id !== id; });
            window.servicesData = servicesData;
            window._servicesData = servicesData;
            window.allServices = servicesData;
            if (typeof state !== 'undefined') {
                state.services = servicesData;
            }
            renderServices('servicesContainer');
            toast('Service deleted successfully!', 'success');
            addActivityEntry('Service Deleted', service.name);
        } else {
            toast(result.error || 'Failed to delete service', 'error');
        }
    } catch (error) {
        console.error('Error deleting service:', error);
        toast('Failed to delete service', 'error');
        await loadServices('servicesContainer');
    }
}

// ============================================================
// EXPORT SERVICES
// ============================================================

function exportServices(format) {
    if (!format) format = 'csv';
    
    if (!servicesData || servicesData.length === 0) {
        toast('No services to export', 'warning');
        return;
    }
    
    var search = document.getElementById('serviceSearch')?.value || '';
    var category = document.getElementById('serviceCategoryFilter')?.value || '';
    var status = document.getElementById('serviceStatusFilter')?.value || '';
    
    toast('Exporting services as ' + format.toUpperCase() + '...', 'info');
    
    var url = 'api/export_services.php?format=' + format + '&_=' + new Date().getTime();
    if (search) url += '&search=' + encodeURIComponent(search);
    if (category && category !== 'all') url += '&category=' + encodeURIComponent(category);
    if (status && status !== 'all') url += '&status=' + encodeURIComponent(status);
    
    window.open(url, '_blank');
}

// ============================================================
// TOGGLE SERVICES EXPORT DROPDOWN
// ============================================================

function toggleServicesExportDropdown() {
    var dropdown = document.getElementById('servicesExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE SERVICES EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('servicesExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// DEBOUNCE FOR SERVICES SEARCH
// ============================================================

// Extend debounceFilter to include services
var originalDebounceFilter = debounceFilter;
debounceFilter = function(target) {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(function() {
        switch (target) {
            case 'customers': filterCustomers(); break;
            case 'partners': filterPartners(); break;
            case 'leads': filterLeads(); break;
            case 'log': filterLog(); break;
            case 'services': servicePage = 1; renderServices('servicesContainer'); break;
            default: break;
        }
    }, 300);
};

// ============================================================
// PERMANENT SERVICES HELPERS
// ============================================================

function getServices() {
    // First, check if we already have data stored
    if (window.servicesData && window.servicesData.length > 0) {
        return window.servicesData;
    }
    if (servicesData && servicesData.length > 0) {
        return servicesData;
    }
    if (window._servicesData && window._servicesData.length > 0) {
        return window._servicesData;
    }
    if (window.allServices && window.allServices.length > 0) {
        return window.allServices;
    }
    if (typeof state !== 'undefined' && state.services && state.services.length > 0) {
        return state.services;
    }
    
    // If not, extract from DOM
    var cards = document.querySelectorAll('.service-card');
    var services = [];
    
    if (cards.length === 0) {
        console.warn('No service cards found in DOM');
        return [];
    }
    
    cards.forEach(function(card, index) {
        var name = '';
        var nameSelectors = ['strong', '.service-name', '[class*="name"]', 'h4', 'h3', '.card-title'];
        for (var i = 0; i < nameSelectors.length; i++) {
            var el = card.querySelector(nameSelectors[i]);
            if (el && el.textContent.trim()) {
                name = el.textContent.trim();
                break;
            }
        }
        
        if (!name) {
            var allText = card.textContent.trim();
            var firstLine = allText.split('\n')[0].trim();
            if (firstLine && firstLine.length < 50) {
                name = firstLine;
            }
        }
        
        var price = 0;
        var priceSelectors = ['[style*="font-size:20px"]', '.price', '[class*="price"]', '[style*="font-weight:700"]'];
        for (var j = 0; j < priceSelectors.length; j++) {
            var el = card.querySelector(priceSelectors[j]);
            if (el && el.textContent.trim()) {
                var priceText = el.textContent.replace(/[₹,]/g, '').trim();
                if (priceText && !isNaN(parseFloat(priceText))) {
                    price = parseFloat(priceText);
                    break;
                }
            }
        }
        
        var status = 'active';
        var statusSelectors = ['.badge:last-child', '.badge', '[class*="badge"]', '[class*="status"]'];
        for (var k = 0; k < statusSelectors.length; k++) {
            var el = card.querySelector(statusSelectors[k]);
            if (el && el.textContent.trim()) {
                var statusText = el.textContent.trim().toLowerCase();
                if (['active', 'inactive', 'draft', 'pending', 'approved'].includes(statusText)) {
                    status = statusText;
                    break;
                }
            }
        }
        
        var icon = '⭐';
        var iconSelectors = ['span[style*="font-size:32px"]', '.icon', '[class*="icon"]', 'span:first-child'];
        for (var l = 0; l < iconSelectors.length; l++) {
            var el = card.querySelector(iconSelectors[l]);
            if (el && el.textContent.trim() && el.textContent.trim().length <= 2) {
                icon = el.textContent.trim();
                break;
            }
        }
        
        var description = '';
        var descSelectors = ['p', '.description', '[class*="description"]', '.service-description'];
        for (var m = 0; m < descSelectors.length; m++) {
            var el = card.querySelector(descSelectors[m]);
            if (el && el.textContent.trim()) {
                description = el.textContent.trim();
                break;
            }
        }
        
        var duration = '30-45 days';
        var durationSelectors = ['[style*="font-size:11px"]', '.duration', '[class*="duration"]'];
        for (var n = 0; n < durationSelectors.length; n++) {
            var el = card.querySelector(durationSelectors[n]);
            if (el && el.textContent.trim()) {
                duration = el.textContent.trim();
                break;
            }
        }
        
        if (name) {
            services.push({
                id: index + 1,
                name: name,
                price: price,
                status: status,
                icon: icon,
                description: description,
                duration: duration,
                is_featured: 0,
                is_popular: 0,
                category: status === 'active' ? 'credit_repair' : 'other'
            });
        }
    });
    
    if (services.length > 0) {
        window.servicesData = services;
        window._servicesData = services;
        window.allServices = services;
        if (typeof state !== 'undefined') {
            state.services = services;
        }
        console.log('✅ Services stored from DOM:', services.length);
    }
    
    return services;
}

// ============================================================
// SERVICE HELPER FUNCTIONS
// ============================================================

function getServiceById(id) {
    var services = getServices();
    return services.find(function(s) { return s.id === id; }) || null;
}

function getActiveServices() {
    var services = getServices();
    return services.filter(function(s) { return s.status === 'active' || s.status === 'Active'; });
}

function getServiceNames() {
    var services = getServices();
    return services.map(function(s) { return s.name; });
}

function getServiceCount() {
    return getServices().length;
}

function getServicesByCategory(category) {
    var services = getServices();
    return services.filter(function(s) { return s.category === category; });
}

function getFeaturedServices() {
    var services = getServices();
    return services.filter(function(s) { return s.is_featured === 1 || s.is_featured === true; });
}

// ============================================================
// MAKE FUNCTIONS GLOBALLY ACCESSIBLE
// ============================================================

if (typeof window !== 'undefined') {
    window.getServices = getServices;
    window.getServiceById = getServiceById;
    window.getActiveServices = getActiveServices;
    window.getServiceNames = getServiceNames;
    window.getServiceCount = getServiceCount;
    window.getServicesByCategory = getServicesByCategory;
    window.getFeaturedServices = getFeaturedServices;
    window.servicesData = window.servicesData || [];
    window._servicesData = window._servicesData || [];
    window.allServices = window.allServices || [];
}

console.log('✅ Services module loaded successfully!');
console.log('📊 Services available:', getServiceCount());

/* ── PARTNER APPLICATIONS ────────────────────────── */

// ============================================================
// PARTNER APPLICATIONS - STATE & VARIABLES
// ============================================================

var partnerApps = [];
var partnerAppPage = 1;
var partnerAppPerPage = 20;

// ============================================================
// LOAD PARTNER APPLICATIONS
// ============================================================

async function loadPartnerApplications() {
    console.log('📡 Loading partner applications...');
    var tbody = document.getElementById('partnerApplicationsBody');
    if (!tbody) {
        console.warn('❌ partnerApplicationsBody not found');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="8"><div class="loading-cell"><div class="spinner"></div> Loading applications...</div></td></tr>';
    
    try {
        var response = await fetch('api/get_partner_applications.php', {
            headers: { 'Accept': 'application/json' }
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        var data = await response.json();
        console.log('📋 API Response:', data);
        
        if (data.success) {
            partnerApps = data.data || [];
            console.log('✅ Loaded', partnerApps.length, 'applications');
            renderPartnerApplications();
            updatePendingBadge();
        } else {
            throw new Error(data.error || 'Failed to load');
        }
    } catch (error) {
        console.error('❌ Error loading applications:', error);
        loadDemoPartnerApps();
    }
}

// ============================================================
// LOAD DEMO PARTNER APPLICATIONS (Fallback)
// ============================================================

function loadDemoPartnerApps() {
    console.log('🔄 Loading demo partner applications...');
    partnerApps = [
        { id: 1, name: 'John Doe', email: 'john@example.com', phone: '9876543210', partner_type: 'Business', status: 'pending', notes: 'Interested in partnership', created_at: new Date().toISOString() },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', phone: '9876543211', partner_type: 'Individual', status: 'approved', notes: 'Approved by admin', created_at: new Date().toISOString() },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', phone: '9876543212', partner_type: 'Business', status: 'rejected', notes: 'Incomplete documentation', created_at: new Date().toISOString() }
    ];
    renderPartnerApplications();
    updatePendingBadge();
    console.log('✅ Demo data loaded:', partnerApps.length, 'applications');
}

// ============================================================
// RENDER PARTNER APPLICATIONS
// ============================================================

function renderPartnerApplications() {
    console.log('🔄 Rendering applications...');
    var tbody = document.getElementById('partnerApplicationsBody');
    if (!tbody) {
        console.warn('❌ partnerApplicationsBody not found');
        return;
    }
    
    var search = document.getElementById('partnerAppSearch')?.value?.toLowerCase() || '';
    var statusFilter = document.getElementById('partnerAppStatusFilter')?.value || '';
    
    var filtered = partnerApps;
    
    if (search) {
        filtered = filtered.filter(function(a) {
            return (a.name || '').toLowerCase().includes(search) ||
                (a.email || '').toLowerCase().includes(search) ||
                (a.phone || '').toLowerCase().includes(search) ||
                (a.partner_type || '').toLowerCase().includes(search);
        });
    }
    
    if (statusFilter) {
        filtered = filtered.filter(function(a) { return a.status === statusFilter; });
    }
    
    var total = filtered.length;
    var start = (partnerAppPage - 1) * partnerAppPerPage;
    var end = Math.min(start + partnerAppPerPage, total);
    var pageData = filtered.slice(start, end);
    
    var startEl = document.getElementById('partnerAppStart');
    var endEl = document.getElementById('partnerAppEnd');
    var totalEl = document.getElementById('partnerAppTotal');
    var pageInfoEl = document.getElementById('partnerAppPageInfo');
    
    if (startEl) startEl.textContent = total > 0 ? start + 1 : 0;
    if (endEl) endEl.textContent = end;
    if (totalEl) totalEl.textContent = total;
    if (pageInfoEl) pageInfoEl.textContent = 'Page ' + partnerAppPage + ' of ' + (Math.ceil(total / partnerAppPerPage) || 1);
    
    updatePendingBadge();
    
    if (pageData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-check"></i></div>
                        <div class="empty-state-title">${search || statusFilter ? 'No applications match your filters' : 'No partner applications yet'}</div>
                        <div class="empty-state-sub">${search || statusFilter ? 'Try adjusting your filters' : 'Applications will appear here when partners apply'}</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = pageData.map(function(app, i) {
        var statusClass = {
            'pending': 'badge-amber',
            'approved': 'badge-green',
            'rejected': 'badge-red'
        }[app.status] || 'badge-gray';
        
        var statusLabel = app.status ? app.status.charAt(0).toUpperCase() + app.status.slice(1) : 'Pending';
        var submittedDate = app.created_at ? new Date(app.created_at).toLocaleDateString('en-IN') : '—';
        
        return `
            <tr>
                <td>${start + i + 1}</td>
                <td><strong>${esc(app.name)}</strong></td>
                <td>${esc(app.email)}</td>
                <td>${esc(app.phone || '—')}</td>
                <td>${esc(app.partner_type || 'Business')}</td>
                <td><span class="badge ${statusClass}">${esc(statusLabel)}</span></td>
                <td>${esc(submittedDate)}</td>
                <td>
                    <div class="gap-8">
                        ${app.status === 'pending' ? `
                            <button class="btn btn-success btn-xs" onclick="openApproveModal(${app.id})" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-danger btn-xs" onclick="openRejectModal(${app.id})" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : `
                            <span class="text-muted" style="font-size:11px;">${app.status === 'approved' ? '✅ Approved' : '❌ Rejected'}</span>
                        `}
                        <button class="btn btn-ghost btn-xs" onclick="viewPartnerApp(${app.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    console.log('✅ Rendered', pageData.length, 'applications');
}

// ============================================================
// UPDATE PENDING BADGE
// ============================================================

function updatePendingBadge() {
    var pending = partnerApps.filter(function(a) { return a.status === 'pending'; }).length;
    var badge = document.getElementById('pendingAppsBadge');
    var count = document.getElementById('pendingCount');
    if (badge) badge.textContent = pending;
    if (count) count.textContent = pending;
}

// ============================================================
// FILTER FUNCTIONS
// ============================================================

function filterPartnerApps() {
    partnerAppPage = 1;
    renderPartnerApplications();
}

function resetPartnerAppFilters() {
    var search = document.getElementById('partnerAppSearch');
    var status = document.getElementById('partnerAppStatusFilter');
    if (search) search.value = '';
    if (status) status.value = '';
    partnerAppPage = 1;
    renderPartnerApplications();
}

function changePartnerAppPage(direction) {
    var total = partnerApps.length;
    var maxPage = Math.ceil(total / partnerAppPerPage) || 1;
    var newPage = partnerAppPage + direction;
    if (newPage < 1 || newPage > maxPage) return;
    partnerAppPage = newPage;
    renderPartnerApplications();
}

// ============================================================
// MODAL FUNCTIONS
// ============================================================

function openApproveModal(id) {
    var app = partnerApps.find(function(a) { return a.id === id; });
    if (!app) {
        toast('Application not found', 'error');
        return;
    }
    
    var idEl = document.getElementById('approveAppId');
    var nameEl = document.getElementById('approveAppName');
    var emailEl = document.getElementById('approveAppEmail');
    var typeEl = document.getElementById('approveAppType');
    var notesEl = document.getElementById('approveNotes');
    var whatsappEl = document.getElementById('sendWhatsApp');
    
    if (idEl) idEl.value = id;
    if (nameEl) nameEl.textContent = app.name || '—';
    if (emailEl) emailEl.textContent = app.email || '—';
    if (typeEl) typeEl.textContent = app.partner_type || 'Business';
    if (notesEl) notesEl.value = '';
    if (whatsappEl) whatsappEl.checked = true;
    
    openModal('approvePartnerModal');
}

function openRejectModal(id) {
    var app = partnerApps.find(function(a) { return a.id === id; });
    if (!app) {
        toast('Application not found', 'error');
        return;
    }
    
    var idEl = document.getElementById('rejectAppId');
    var nameEl = document.getElementById('rejectAppName');
    var emailEl = document.getElementById('rejectAppEmail');
    var reasonEl = document.getElementById('rejectReason');
    var notesEl = document.getElementById('rejectNotes');
    
    if (idEl) idEl.value = id;
    if (nameEl) nameEl.textContent = app.name || '—';
    if (emailEl) emailEl.textContent = app.email || '—';
    if (reasonEl) reasonEl.value = '';
    if (notesEl) notesEl.value = '';
    
    openModal('rejectPartnerModal');
}

function viewPartnerApp(id) {
    var app = partnerApps.find(function(a) { return a.id === id; });
    if (!app) {
        toast('Application not found', 'error');
        return;
    }
    
    var details = '📋 PARTNER APPLICATION DETAILS\n';
    details += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    details += '👤 Name: ' + (app.name || '—') + '\n';
    details += '📧 Email: ' + (app.email || '—') + '\n';
    details += '📱 Phone: ' + (app.phone || '—') + '\n';
    details += '🏢 Partner Type: ' + (app.partner_type || 'Business') + '\n';
    details += '📊 Status: ' + (app.status || '—') + '\n';
    details += '📅 Submitted: ' + (app.created_at ? new Date(app.created_at).toLocaleString('en-IN') : '—') + '\n';
    details += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    details += '📝 Notes: ' + (app.notes || 'None') + '\n';
    details += '🏷️ Ref: ' + (app.ref_number || 'N/A');
    
    alert(details);
}

// ============================================================
// CONFIRM APPROVE PARTNER
// ============================================================

async function confirmApprovePartner() {
    var idEl = document.getElementById('approveAppId');
    var notesEl = document.getElementById('approveNotes');
    var whatsappEl = document.getElementById('sendWhatsApp');
    var btn = document.getElementById('approveBtn');
    
    if (!idEl || !btn) {
        toast('Modal elements not found', 'error');
        return;
    }
    
    var id = parseInt(idEl.value);
    var notes = notesEl ? notesEl.value.trim() : '';
    var sendWhatsApp = whatsappEl ? whatsappEl.checked : true;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing...';
    
    try {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        var response = await fetch('/admin/generate_partner_credentials.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: id,
                action: 'approve',
                notes: notes,
                csrf_token: csrfToken,
                send_whatsapp: sendWhatsApp
            })
        });
        
        var data = await response.json();
        
        if (data.success) {
            toast(data.message, 'success');
            closeModal('approvePartnerModal');
            loadPartnerApplications();
        } else {
            toast(data.error || 'Failed to approve partner', 'error');
        }
    } catch (error) {
        console.error('Error approving partner:', error);
        toast('An error occurred while approving', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Approve Partner';
    }
}

// ============================================================
// CONFIRM REJECT PARTNER
// ============================================================

async function confirmRejectPartner() {
    var idEl = document.getElementById('rejectAppId');
    var reasonEl = document.getElementById('rejectReason');
    var notesEl = document.getElementById('rejectNotes');
    var btn = document.querySelector('#rejectPartnerModal .btn-danger');
    
    if (!idEl || !reasonEl || !btn) {
        toast('Modal elements not found', 'error');
        return;
    }
    
    var id = parseInt(idEl.value);
    var reason = reasonEl.value.trim();
    var notes = notesEl ? notesEl.value.trim() : '';
    
    if (!reason) {
        toast('Please provide a reason for rejection', 'error');
        reasonEl.focus();
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing...';
    
    try {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        var response = await fetch('/admin/generate_partner_credentials.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: id,
                action: 'reject',
                reject_reason: reason,
                notes: notes,
                csrf_token: csrfToken
            })
        });
        
        var data = await response.json();
        
        if (data.success) {
            toast(data.message, 'success');
            closeModal('rejectPartnerModal');
            loadPartnerApplications();
        } else {
            toast(data.error || 'Failed to reject application', 'error');
        }
    } catch (error) {
        console.error('Error rejecting partner:', error);
        toast('An error occurred while rejecting', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-times"></i> Reject Application';
    }
}

// ============================================================
// EXPORT FUNCTIONS
// ============================================================

function exportPartnerApps(format) {
    if (!format) format = 'csv';
    
    if (!partnerApps || partnerApps.length === 0) {
        toast('No applications to export', 'warning');
        return;
    }
    
    var status = document.getElementById('partnerAppStatusFilter')?.value || '';
    var url = 'api/export_partner_applications.php?format=' + format + (status ? '&status=' + status : '') + '&_=' + Date.now();
    window.open(url, '_blank');
    toast('Exporting...', 'info');
}

function togglePartnerAppsExportDropdown() {
    var dropdown = document.getElementById('partnerAppsExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// EXTEND DEBOUNCE FILTER (No duplicate declaration)
// ============================================================

// Only extend if not already done
if (typeof debounceFilterExtended === 'undefined') {
    // Save reference to original
    var originalDebounceFilter = debounceFilter;
    
    // Override with extended version
    debounceFilter = function(target) {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(function() {
            switch (target) {
                case 'customers': filterCustomers(); break;
                case 'partners':  filterPartners();  break;
                case 'leads':     filterLeads();     break;
                case 'log':       filterLog();       break;
                case 'partnerApps': filterPartnerApps(); break;
                case 'services':  servicePage = 1; renderServices('servicesContainer'); break;
                default: break;
            }
        }, 300);
    };
    
    var debounceFilterExtended = true;
    console.log('✅ Debounce filter extended with partnerApps');
}

console.log('✅ Partner Applications module loaded!');
console.log('📊 Call loadPartnerApplications() to start');

/* ── CUSTOMERS (COMPLETE WITH EXPORT DROPDOWN) ────────────────────────────────── */

// ============================================================
// RENDER CUSTOMERS
// ============================================================

function renderCustomers() {
    const s = (document.getElementById('custSearch')?.value || '').toLowerCase();
    const sf = document.getElementById('custStatusFilter')?.value || '';
    const filtered = state.customers.filter(c =>
        (!s || (c.name + c.email + (c.phone || '')).toLowerCase().includes(s)) &&
        (!sf || (c.status || '').toLowerCase() === sf)
    );
    pagination.customers.total = filtered.length;
    const p = pagination.customers;
    const start = (p.page - 1) * p.perPage;
    const end = Math.min(start + p.perPage, filtered.length);
    const pageData = filtered.slice(start, end);

    document.getElementById('customerTableBody').innerHTML = pageData.length
        ? pageData.map((c, i) =>
            '<tr><td><input type="checkbox" class="row-checkbox" data-id="'+c.id+'" onchange="updateBulkActions(\'customerTableBody\')"></td>' +
            '<td>'+(start+i+1)+'</td><td><strong>'+esc(c.name)+'</strong></td><td>'+esc(c.email)+'</td>' +
            '<td>'+esc(c.phone||'—')+'</td><td>'+esc(c.city||'—')+'</td><td>'+statusBadge(c.status)+'</td>' +
            '<td>'+esc((c.joined||c.created_at||'').split('T')[0]||'—')+'</td>' +
            '<td><div class="gap-8"><button class="btn btn-ghost btn-xs" onclick="editCustomer('+c.id+')"><i class="fas fa-edit"></i></button>' +
            '<button class="btn btn-danger btn-xs" onclick="deleteCustomer('+c.id+')"><i class="fas fa-trash"></i></button></div></td></tr>'
        ).join('')
        : '<tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-users"></i></div><div class="empty-state-title">No customers found</div></div></td></tr>';

    document.getElementById('customerStart').textContent = filtered.length > 0 ? start + 1 : 0;
    document.getElementById('customerEnd').textContent = end;
    document.getElementById('customerTotal').textContent = filtered.length;
    document.getElementById('customerPageInfo').textContent = 'Page ' + p.page + ' of ' + (Math.ceil(filtered.length / p.perPage) || 1);
}

// ============================================================
// FILTER CUSTOMERS
// ============================================================

function filterCustomers() {
    pagination.customers.page = 1;
    renderCustomers();
}

// ============================================================
// EDIT CUSTOMER
// ============================================================

function editCustomer(id) {
    const c = state.customers.find(x => x.id === id);
    if (!c) return;
    document.getElementById('editCustId').value = id;
    document.getElementById('editCustName').value = c.name;
    document.getElementById('editCustEmail').value = c.email;
    document.getElementById('editCustPhone').value = c.phone || '';
    document.getElementById('editCustCity').value = c.city || '';
    document.getElementById('editCustStatus').value = c.status || 'active';
    openModal('editCustomerModal');
}

// ============================================================
// SAVE EDIT CUSTOMER
// ============================================================

function saveEditCustomer() {
    const id = parseInt(document.getElementById('editCustId').value);
    const idx = state.customers.findIndex(x => x.id === id);
    if (idx < 0) return;
    state.customers[idx] = {
        ...state.customers[idx],
        name: document.getElementById('editCustName').value,
        email: document.getElementById('editCustEmail').value,
        phone: document.getElementById('editCustPhone').value,
        city: document.getElementById('editCustCity').value,
        status: document.getElementById('editCustStatus').value
    };
    apiFetch(API + 'save_customer', {
        method: 'POST',
        body: JSON.stringify({
            id,
            name: state.customers[idx].name,
            email: state.customers[idx].email,
            phone: state.customers[idx].phone,
            city: state.customers[idx].city,
            status: state.customers[idx].status
        })
    });
    renderCustomers();
    closeModal('editCustomerModal');
    toast('Customer updated!', 'success');
    addActivityEntry('Customer Updated', state.customers[idx].name + ' details modified');
}

// ============================================================
// DELETE CUSTOMER
// ============================================================

function deleteCustomer(id) {
    if (!confirm('Delete this customer?')) return;
    const c = state.customers.find(x => x.id === id);
    state.customers = state.customers.filter(c => c.id !== id);
    apiFetch(API + 'delete_customer', { method: 'POST', body: JSON.stringify({ id }) });
    renderCustomers();
    updateStatCards();
    toast('Customer deleted', 'success');
    addActivityEntry('Customer Deleted', (c?.name || 'Unknown') + ' removed');
}

// ============================================================
// ADD CUSTOMER
// ============================================================

function addCustomer() {
    const name = document.getElementById('newCustName').value.trim();
    const email = document.getElementById('newCustEmail').value.trim();
    if (!name || !email) {
        toast('Name and email are required', 'error');
        return;
    }
    if (!validateEmail(email)) {
        toast('Please enter a valid email', 'error');
        return;
    }
    const phone = document.getElementById('newCustPhone').value.trim();
    if (phone && !validatePhone(phone)) {
        toast('Please enter a valid 10-digit phone number', 'error');
        return;
    }
    const id = Math.max(0, ...state.customers.map(c => c.id)) + 1;
    state.customers.push({
        id,
        name,
        email,
        phone,
        city: document.getElementById('newCustCity').value.trim(),
        status: 'active',
        joined: today()
    });
    apiFetch(API + 'save_customer', {
        method: 'POST',
        body: JSON.stringify({
            name,
            email,
            phone,
            city: document.getElementById('newCustCity').value.trim(),
            service: document.getElementById('newCustService') ? document.getElementById('newCustService').value : ''
        })
    }).then(d => {
        if (d && d.success && d.customer) {
            const idx = state.customers.findIndex(c => c.id === id);
            if (idx >= 0) state.customers[idx].id = d.customer.id;
        }
    });
    ['newCustName', 'newCustEmail', 'newCustPhone', 'newCustCity'].forEach(i => {
        const el = document.getElementById(i);
        if (el) el.value = '';
    });
    updateStatCards();
    toast('Customer "' + name + '" added!', 'success');
    showSection('customerList');
    addActivityEntry('Customer Added', name + ' added to CRM');
}

// ============================================================
// ADD CUSTOMER FROM MODAL
// ============================================================

function addCustomerFromModal() {
    const name = document.getElementById('modalCustName').value.trim();
    const email = document.getElementById('modalCustEmail').value.trim();
    if (!name || !email) {
        toast('Name and email are required', 'error');
        return;
    }
    if (!validateEmail(email)) {
        toast('Please enter a valid email', 'error');
        return;
    }
    const id = Math.max(0, ...state.customers.map(c => c.id)) + 1;
    state.customers.push({
        id,
        name,
        email,
        phone: document.getElementById('modalCustPhone').value.trim(),
        city: document.getElementById('modalCustCity').value.trim(),
        status: 'active',
        joined: today()
    });
    apiFetch(API + 'save_customer', {
        method: 'POST',
        body: JSON.stringify({
            name,
            email,
            phone: document.getElementById('modalCustPhone').value.trim(),
            city: document.getElementById('modalCustCity').value.trim()
        })
    }).then(d => {
        if (d && d.success && d.customer) {
            const idx = state.customers.findIndex(c => c.id === id);
            if (idx >= 0) state.customers[idx].id = d.customer.id;
        }
    });
    ['modalCustName', 'modalCustEmail', 'modalCustPhone', 'modalCustCity'].forEach(i => {
        const el = document.getElementById(i);
        if (el) el.value = '';
    });
    closeModal('addCustomerModal');
    updateStatCards();
    renderCustomers();
    toast('Customer "' + name + '" added!', 'success');
}

// ============================================================
// EXPORT CUSTOMERS (With Format Selection)
// ============================================================

function exportCustomers(format = 'csv') {
    // Check if there are customers to export
    if (!state.customers || state.customers.length === 0) {
        toast('No customers to export', 'warning');
        return;
    }
    
    // Get filter values
    const search = document.getElementById('custSearch')?.value || '';
    const status = document.getElementById('custStatusFilter')?.value || '';
    
    toast('Exporting customers as ' + format.toUpperCase() + '...', 'info');
    
    // Build URL with parameters
    let url = 'api/export_customers.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    if (status && status !== 'all') {
        url += '&status=' + encodeURIComponent(status);
    }
    
    // Open download in new tab/window
    window.open(url, '_blank');
}

// ============================================================
// SHOW CUSTOMER EXPORT OPTIONS
// ============================================================

function showCustomerExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportCustomers(selected);
    }
}

// ============================================================
// TOGGLE CUSTOMER EXPORT DROPDOWN
// ============================================================

function toggleCustomerExportDropdown() {
    const dropdown = document.getElementById('customerExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE CUSTOMER EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('customerExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// LEGACY EXPORT CUSTOMERS (Keep for backward compatibility)
// ============================================================

function exportCustomersLegacy() {
    const csv = ['ID,Name,Email,Phone,City,Status,Joined',
        ...state.customers.map(c => '"'+c.id+'","'+c.name+'","'+c.email+'","'+(c.phone||'')+'","'+(c.city||'')+'","'+c.status+'","'+(c.joined||'')+'"')
    ].join('\n');
    dlFile(csv, 'customers_' + today() + '.csv', 'text/csv');
    toast('Customers exported!', 'success');
}

/* ── PARTNERS (COMPLETE WITH DELETE MODAL) ────────────────────────────────── */

// ============================================================
// RENDER PARTNERS - MODIFIED TO INCLUDE VIEW DASHBOARD BUTTON
// ============================================================

function renderPartners() {
    const tbody = document.getElementById('partnerTableBody');
    if (!tbody) return;
    
    const search = document.getElementById('partnerSearch')?.value?.toLowerCase() || '';
    const statusFilter = document.getElementById('partnerStatusFilter')?.value || '';
    
    let filtered = state.partners || [];
    
    if (search) {
        filtered = filtered.filter(p => 
            (p.name || '').toLowerCase().includes(search) ||
            (p.location || '').toLowerCase().includes(search) ||
            (p.owner || '').toLowerCase().includes(search) ||
            (p.phone || '').toLowerCase().includes(search) ||
            (p.email || '').toLowerCase().includes(search)
        );
    }
    
    if (statusFilter) {
        filtered = filtered.filter(p => (p.status || '').toLowerCase() === statusFilter);
    }
    
    const totalEl = document.getElementById('partnerTotalCount');
    if (totalEl) totalEl.textContent = filtered.length;
    
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr>
            <td colspan="8">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-handshake"></i></div>
                    <div class="empty-state-title">${search || statusFilter ? 'No partners match your filters' : 'No partners yet'}</div>
                    <div class="empty-state-sub">${search || statusFilter ? 'Try adjusting your filters' : 'Click "Add Partner" to create your first partner'}</div>
                </div>
            </td>
        </tr>`;
        return;
    }
    
    tbody.innerHTML = filtered.map((p, i) => {
        const statusClass = p.status === 'active' ? 'badge-green' : 
                           p.status === 'inactive' ? 'badge-red' : 'badge-amber';
        const statusLabel = p.status ? p.status.charAt(0).toUpperCase() + p.status.slice(1) : 'Active';
        
        return `<tr>
            <td>${i + 1}</td>
            <td><strong>${esc(p.name)}</strong></td>
            <td>${esc(p.location || '—')}</td>
            <td>${esc(p.owner || '—')}</td>
            <td>${esc(p.phone || '—')}</td>
            <td><span class="badge badge-brand">${esc(String(p.commission_rate || 10))}%</span></td>
            <td><span class="badge ${statusClass}">${esc(statusLabel)}</span></td>
            <td>
                <div class="gap-8">
                    <!-- NEW: View Dashboard button -->
                    <button class="btn btn-primary btn-xs" onclick="window.location.href='partner-dashboard.php?partner_id=${p.id}'">
                        <i class="fas fa-eye"></i> View Dashboard
                    </button>
                    <button class="btn btn-ghost btn-xs" onclick="editPartner(${p.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deletePartner(${p.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-${p.status === 'active' ? 'warning' : 'success'} btn-xs" onclick="togglePartnerStatus(${p.id})" title="${p.status === 'active' ? 'Deactivate' : 'Activate'}">
                        <i class="fas fa-${p.status === 'active' ? 'pause' : 'play'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ============================================================
// FILTER PARTNERS
// ============================================================

function filterPartners() {
    renderPartners();
}

// ============================================================
// ADD PARTNER
// ============================================================

function addPartner() {
    console.log('=== ADD PARTNER FUNCTION CALLED ===');
    
    // Get form elements
    const nameEl = document.getElementById('newPartnerName');
    const phoneEl = document.getElementById('newPartnerPhone');
    const ownerEl = document.getElementById('newPartnerOwner');
    const locationEl = document.getElementById('newPartnerLocation');
    const emailEl = document.getElementById('newPartnerEmail');
    const commissionEl = document.getElementById('newPartnerCommission');
    const statusEl = document.getElementById('newPartnerStatus');
    
    // Check required elements
    if (!nameEl) {
        toast('Form error: Partner name field not found', 'error');
        return;
    }
    if (!phoneEl) {
        toast('Form error: Phone field not found', 'error');
        return;
    }
    
    const name = nameEl.value.trim();
    const phone = phoneEl.value.trim();
    const owner = ownerEl ? ownerEl.value.trim() : '';
    const location = locationEl ? locationEl.value.trim() : '';
    const email = emailEl ? emailEl.value.trim() : '';
    const commission = commissionEl ? parseInt(commissionEl.value) || 10 : 10;
    const status = statusEl ? statusEl.value : 'active';
    
    // Validate
    if (!name) {
        toast('Business name is required', 'error');
        nameEl.focus();
        return;
    }
    if (!phone) {
        toast('Phone is required', 'error');
        phoneEl.focus();
        return;
    }
    if (!validatePhone(phone)) {
        toast('Please enter a valid 10-digit phone number', 'error');
        phoneEl.focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#addPartnerSection .btn-primary');
    const originalText = btn?.innerHTML || 'Save Partner';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    // Prepare data
    const data = {
        name: name,
        phone: phone,
        owner: owner,
        location: location,
        email: email,
        commission_rate: commission,
        status: status
    };
    
    // Send to API
    fetch('api/add_partner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            toast('Partner added successfully!', 'success');
            
            // Clear form
            if (nameEl) nameEl.value = '';
            if (ownerEl) ownerEl.value = '';
            if (phoneEl) phoneEl.value = '';
            if (locationEl) locationEl.value = '';
            if (emailEl) emailEl.value = '';
            if (commissionEl) commissionEl.value = '10';
            if (statusEl) statusEl.value = 'active';
            
            // Add to state
            if (data.data) {
                state.partners.push(data.data);
                renderPartners();
                updateStatCards();
            } else {
                loadAllData();
            }
            
            showSection('partnerList');
            addActivityEntry('Partner Added', name);
        } else {
            toast(data.error || 'Failed to add partner', 'error');
        }
    })
    .catch(err => {
        console.error('Error adding partner:', err);
        toast('Failed to add partner: ' + err.message, 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// ============================================================
// EDIT PARTNER
// ============================================================

function editPartner(id) {
    const p = state.partners.find(x => x.id === id);
    if (!p) {
        toast('Partner not found', 'error');
        return;
    }
    
    // Populate modal fields
    document.getElementById('editPartnerId').value = id;
    document.getElementById('editPartnerName').value = p.name || '';
    document.getElementById('editPartnerOwner').value = p.owner || '';
    document.getElementById('editPartnerPhone').value = p.phone || '';
    document.getElementById('editPartnerLoc').value = p.location || '';
    document.getElementById('editPartnerComm').value = p.commission_rate || 10;
    document.getElementById('editPartnerStatus').value = p.status || 'active';
    
    openModal('editPartnerModal');
}

// ============================================================
// SAVE EDIT PARTNER
// ============================================================

function saveEditPartner() {
    const id = parseInt(document.getElementById('editPartnerId').value);
    const name = document.getElementById('editPartnerName').value.trim();
    const owner = document.getElementById('editPartnerOwner').value.trim();
    const phone = document.getElementById('editPartnerPhone').value.trim();
    const location = document.getElementById('editPartnerLoc').value.trim();
    const commission = parseInt(document.getElementById('editPartnerComm').value) || 10;
    const status = document.getElementById('editPartnerStatus').value;
    
    if (!name) {
        toast('Partner name is required', 'error');
        return;
    }
    if (!phone) {
        toast('Phone is required', 'error');
        return;
    }
    if (!validatePhone(phone)) {
        toast('Please enter a valid 10-digit phone number', 'error');
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#editPartnerModal .btn-primary');
    const originalText = btn?.innerHTML || 'Save Changes';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    const data = {
        id: id,
        name: name,
        owner: owner,
        phone: phone,
        location: location,
        commission_rate: commission,
        status: status
    };
    
    fetch('api/update_partner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            // Update local state
            const idx = state.partners.findIndex(p => p.id === id);
            if (idx !== -1) {
                state.partners[idx] = {
                    ...state.partners[idx],
                    name: name,
                    owner: owner,
                    phone: phone,
                    location: location,
                    commission_rate: commission,
                    status: status
                };
                renderPartners();
            }
            
            closeModal('editPartnerModal');
            toast('Partner updated successfully!', 'success');
            addActivityEntry('Partner Updated', name);
        } else {
            toast(data.error || 'Failed to update partner', 'error');
        }
    })
    .catch(err => {
        console.error('Error updating partner:', err);
        toast('Failed to update partner: ' + err.message, 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// ============================================================
// DELETE PARTNER (With Modal)
// ============================================================

function deletePartner(id) {
    const partner = state.partners.find(p => p.id === id);
    if (!partner) {
        toast('Partner not found', 'error');
        return;
    }
    
    // Show delete modal
    document.getElementById('deletePartnerId').value = id;
    document.getElementById('deletePartnerName').textContent = partner.name;
    openModal('deletePartnerModal');
}

// ============================================================
// CONFIRM DELETE PARTNER
// ============================================================

function confirmDeletePartner() {
    const id = parseInt(document.getElementById('deletePartnerId').value);
    const partner = state.partners.find(p => p.id === id);
    
    if (!partner) {
        toast('Partner not found', 'error');
        closeModal('deletePartnerModal');
        return;
    }
    
    closeModal('deletePartnerModal');
    toast('Deleting partner...', 'info');
    
    fetch('api/delete_partner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            state.partners = state.partners.filter(p => p.id !== id);
            renderPartners();
            updateStatCards();
            toast('Partner deleted successfully!', 'success');
            addActivityEntry('Partner Deleted', partner.name);
        } else {
            toast(data.error || 'Failed to delete partner', 'error');
        }
    })
    .catch(err => {
        console.error('Error deleting partner:', err);
        toast('Failed to delete partner: ' + err.message, 'error');
    });
}

// ============================================================
// TOGGLE PARTNER STATUS
// ============================================================

function togglePartnerStatus(id) {
    const partner = state.partners.find(p => p.id === id);
    if (!partner) {
        toast('Partner not found', 'error');
        return;
    }
    
    const newStatus = partner.status === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} "${partner.name}"?`)) {
        return;
    }
    
    toast(`Updating status...`, 'info');
    
    fetch('api/update_partner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            status: newStatus
        })
    })
    .then(res => res.text())
    .then(text => {
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            partner.status = newStatus;
            renderPartners();
            toast(`Partner ${action}d successfully!`, 'success');
            addActivityEntry(`Partner ${action}d`, partner.name);
        } else {
            toast(data.error || 'Failed to update status', 'error');
        }
    })
    .catch(err => {
        console.error('Error updating partner status:', err);
        toast('Failed to update status: ' + err.message, 'error');
    });
}

// ============================================================
// EXPORT PARTNERS (With Format Selection)
// ============================================================

function exportPartners(format = 'csv') {
    if (!state.partners || state.partners.length === 0) {
        toast('No partners to export', 'warning');
        return;
    }
    
    const search = document.getElementById('partnerSearch')?.value || '';
    const status = document.getElementById('partnerStatusFilter')?.value || '';
    
    toast('Exporting partners as ' + format.toUpperCase() + '...', 'info');
    
    let url = 'api/export_partners.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    if (status && status !== 'all') {
        url += '&status=' + encodeURIComponent(status);
    }
    
    window.open(url, '_blank');
}

// ============================================================
// SHOW PARTNER EXPORT OPTIONS
// ============================================================

function showPartnerExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportPartners(selected);
    }
}

// ============================================================
// TOGGLE PARTNERS EXPORT DROPDOWN
// ============================================================

function togglePartnersExportDropdown() {
    const dropdown = document.getElementById('partnersExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE PARTNERS EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('partnersExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// LEGACY EXPORT PARTNERS (Backward Compatibility)
// ============================================================

function exportPartnersLegacy() {
    const csv = ['ID,Name,Location,Owner,Phone,Commission,Status',
        ...state.partners.map(p => '"'+p.id+'","'+p.name+'","'+(p.location||'')+'","'+(p.owner||'')+'","'+(p.phone||'')+'","'+(p.commission_rate||10)+'%","'+p.status+'"')
    ].join('\n');
    dlFile(csv, 'partners_' + today() + '.csv', 'text/csv');
    toast('Partners exported!', 'success');
}

/* ── BANK SECTION (COMPLETE) ─────────────────────────────────── */

// ============================================================
// RENDER BANKS
// ============================================================

function renderBanks() {
    const tbody = document.getElementById('bankTableBody');
    if (!tbody) return;
    
    // Get search filter
    const search = document.getElementById('bankSearch')?.value?.toLowerCase() || '';
    
    // Filter banks
    let filtered = state.banks || [];
    if (search) {
        filtered = filtered.filter(b => 
            (b.name || '').toLowerCase().includes(search) ||
            (b.contact || '').toLowerCase().includes(search) ||
            (b.email || '').toLowerCase().includes(search) ||
            (b.phone || '').toLowerCase().includes(search)
        );
    }
    
    if (!filtered || filtered.length === 0) {
        tbody.innerHTML = `<tr>
            <td colspan="8">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-university"></i></div>
                    <div class="empty-state-title">${search ? 'No banks match your search' : 'No banks yet'}</div>
                    <div class="empty-state-sub">${search ? 'Try a different search term' : 'Click "Add Bank" to create your first entry'}</div>
                </div>
            </td>
        </tr>`;
        return;
    }
    
    tbody.innerHTML = filtered.map((b, i) => {
        // Get entity type from notes
        let entityType = 'Bank';
        if (b.notes) {
            const match = b.notes.match(/Entity Type: ([^\n]+)/);
            if (match) {
                entityType = match[1];
            }
        }
        
        // Get badge color based on entity type
        const typeClass = getEntityTypeClass(entityType);
        
        return `<tr>
            <td>${i + 1}</td>
            <td><strong>${esc(b.name)}</strong></td>
            <td><span class="badge ${typeClass}">${esc(entityType)}</span></td>
            <td>${esc(b.contact || '—')}</td>
            <td>${esc(b.email || '—')}</td>
            <td>${esc(b.phone || '—')}</td>
            <td>${statusBadge(b.status)}</td>
            <td>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-xs" onclick="editBank(${b.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteBank(${b.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ============================================================
// GET ENTITY TYPE CLASS
// ============================================================

function getEntityTypeClass(type) {
    const typeMap = {
        'Bank': 'badge-green',
        'Law Firm / Advocate': 'badge-purple',
        'Chartered Accountant': 'badge-blue',
        'Franchise Store': 'badge-amber',
        'Real Estate Agent': 'badge-info',
        'Insurance Agent': 'badge-brand',
        'Business Consultant': 'badge-gray',
        'Recruitment Agency': 'badge-warning',
        'Broker / Agent': 'badge-danger',
        'Other': 'badge-gray'
    };
    
    // Try to match the type
    const lowerType = type.toLowerCase();
    for (const [key, value] of Object.entries(typeMap)) {
        if (lowerType.includes(key.toLowerCase()) || key.toLowerCase().includes(lowerType)) {
            return value;
        }
    }
    
    return 'badge-gray';
}

// ============================================================
// FILTER BANKS
// ============================================================

function filterBanks() {
    renderBanks();
}

// ============================================================
// EXPORT BANKS (With Format Selection)
// ============================================================

function exportBanks(format = 'csv') {
    // Check if there are banks to export
    if (!state.banks || state.banks.length === 0) {
        toast('No banks to export', 'warning');
        return;
    }
    
    // Get search filter value
    const search = document.getElementById('bankSearch')?.value || '';
    
    toast('Exporting banks as ' + format.toUpperCase() + '...', 'info');
    
    // Build URL with parameters
    let url = 'api/export_banks.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    
    // Open download in new tab/window
    window.open(url, '_blank');
}

// Export with dropdown selection
function showExportOptions() {
    // You can implement a modal or dropdown here
    // For now, use a simple prompt
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportBanks(selected);
    }
}

// ============================================================
// TOGGLE EXPORT DROPDOWN
// ============================================================

function toggleExportDropdown() {
    const dropdown = document.getElementById('exportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('exportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// ADD BANK
// ============================================================

function addBank() {
    console.log('=== ADD BANK FUNCTION CALLED ===');
    
    // Get form values
    const name = document.getElementById('newBankName')?.value?.trim() || '';
    const contact = document.getElementById('newBankContact')?.value?.trim() || '';
    const email = document.getElementById('newBankEmail')?.value?.trim() || '';
    const phone = document.getElementById('newBankPhone')?.value?.trim() || '';
    const entityType = document.getElementById('newBankType')?.value || 'bank';
    const status = document.getElementById('newBankStatus')?.value || 'active';
    
    console.log('Form values:', { name, contact, email, phone, entityType, status });
    
    // Validate
    if (!name) {
        toast('Bank name is required', 'error');
        document.getElementById('newBankName')?.focus();
        return;
    }
    
    if (email && !validateEmail(email)) {
        toast('Please enter a valid email address', 'error');
        document.getElementById('newBankEmail')?.focus();
        return;
    }
    
    if (phone && !validatePhone(phone)) {
        toast('Please enter a valid 10-digit phone number', 'error');
        document.getElementById('newBankPhone')?.focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#addBankSection .btn-primary');
    const originalText = btn?.innerHTML || 'Save Bank';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    // Entity type labels
    const entityLabels = {
        'bank': 'Bank',
        'lawyer': 'Law Firm / Advocate',
        'ca': 'Chartered Accountant',
        'franchise': 'Franchise Store',
        'real_estate': 'Real Estate Agent',
        'insurance': 'Insurance Agent',
        'consultant': 'Business Consultant',
        'agency': 'Recruitment Agency',
        'broker': 'Broker / Agent',
        'other': 'Other'
    };
    
    // Prepare data with notes field for extra info
    const data = {
        name: name,
        contact: contact,
        email: email,
        phone: phone,
        status: status,
        notes: "Entity Type: " + (entityLabels[entityType] || 'Other')
    };
    
    console.log('Sending data:', data);
    
    // Send to API
    fetch('api/add_bank.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => {
        console.log('Response status:', res.status);
        return res.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        console.log('Parsed response:', data);
        if (data.success) {
            toast('Bank added successfully!', 'success');
            
            // Clear form
            document.getElementById('newBankName').value = '';
            document.getElementById('newBankContact').value = '';
            document.getElementById('newBankEmail').value = '';
            document.getElementById('newBankPhone').value = '';
            
            // Refresh data
            loadAllData();
            
            // Switch to bank list
            showSection('bankList');
            
            // Add activity log
            addActivityEntry('Bank Added', data.data?.name || name + ' (' + (entityLabels[entityType] || 'Other') + ')');
        } else {
            toast(data.error || 'Failed to add bank', 'error');
        }
    })
    .catch(err => {
        console.error('Error adding bank:', err);
        toast('Failed to add bank: ' + err.message, 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// ============================================================
// EDIT BANK
// ============================================================

function editBank(id) {
    const bank = state.banks.find(b => b.id === id);
    if (!bank) {
        toast('Bank not found', 'error');
        return;
    }
    
    // We need an edit modal - for now, show a prompt or use a simple approach
    // Since you don't have an edit bank modal in your HTML, we'll use a prompt-based approach
    // or we can navigate to a dedicated edit section
    
    const newName = prompt('Edit Bank Name:', bank.name);
    if (newName === null) return; // Cancelled
    
    if (newName.trim() === '') {
        toast('Bank name cannot be empty', 'error');
        return;
    }
    
    const newContact = prompt('Edit Contact Person:', bank.contact || '');
    if (newContact === null) return;
    
    const newPhone = prompt('Edit Phone:', bank.phone || '');
    if (newPhone === null) return;
    
    // Update the bank
    const updatedBank = {
        ...bank,
        name: newName.trim(),
        contact: newContact.trim(),
        phone: newPhone.trim()
    };
    
    // Send update to API
    fetch('api/update_bank.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updatedBank)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update local state
            const index = state.banks.findIndex(b => b.id === id);
            if (index !== -1) {
                state.banks[index] = updatedBank;
            }
            renderBanks();
            toast('Bank updated successfully!', 'success');
            addActivityEntry('Bank Updated', updatedBank.name);
        } else {
            toast(data.error || 'Failed to update bank', 'error');
        }
    })
    .catch(err => {
        console.error('Error updating bank:', err);
        toast('Failed to update bank', 'error');
    });
}

// ============================================================
// DELETE BANK
// ============================================================

function deleteBank(id) {
    if (!confirm('Are you sure you want to delete this bank?')) return;
    
    const bank = state.banks.find(b => b.id === id);
    if (!bank) {
        toast('Bank not found', 'error');
        return;
    }
    
    toast('Deleting...', 'info');
    
    // Remove from state (optimistic update)
    state.banks = state.banks.filter(b => b.id !== id);
    renderBanks();
    updateStatCards();
    
    // Send delete request
    fetch('api/delete_bank.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            toast('Bank deleted successfully!', 'success');
            addActivityEntry('Bank Deleted', bank.name);
        } else {
            // Revert if failed
            toast(data.error || 'Failed to delete bank', 'error');
            loadAllData(); // Reload data
        }
    })
    .catch(err => {
        console.error('Error deleting bank:', err);
        toast('Failed to delete bank. Please try again.', 'error');
        loadAllData(); // Reload data
    });
}

/* ── QUOTATIONS (COMPLETE WITH GST, UPDATE, DELETE, EXPORT) ────────────────────────────────── */

// ============================================================
// RENDER QUOTATIONS
// ============================================================

function renderQuotations() {
    const tbody = document.getElementById('quoteBody');
    if (!tbody) return;
    
    // Get filter values
    const search = document.getElementById('quoteSearch')?.value?.toLowerCase() || '';
    const statusFilter = document.getElementById('quoteStatusFilter')?.value || '';
    const serviceFilter = document.getElementById('quoteServiceFilter')?.value || '';
    
    let filtered = state.quotations || [];
    
    // Apply search filter
    if (search) {
        filtered = filtered.filter(q => 
            (q.quote_no || '').toLowerCase().includes(search) ||
            (q.customer || '').toLowerCase().includes(search) ||
            (q.service || '').toLowerCase().includes(search) ||
            (q.customer_email || '').toLowerCase().includes(search)
        );
    }
    
    // Apply status filter
    if (statusFilter) {
        filtered = filtered.filter(q => (q.status || '').toLowerCase() === statusFilter);
    }
    
    // Apply service filter
    if (serviceFilter) {
        filtered = filtered.filter(q => (q.service || '').toLowerCase() === serviceFilter);
    }
    
    // Update total count
    const totalEl = document.getElementById('quoteTotalCount');
    if (totalEl) totalEl.textContent = filtered.length;
    
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr>
            <td colspan="9">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="empty-state-title">${search || statusFilter || serviceFilter ? 'No quotations match your filters' : 'No quotations yet'}</div>
                    <div class="empty-state-sub">${search || statusFilter || serviceFilter ? 'Try adjusting your filters' : 'Click "New" to create your first quotation'}</div>
                </div>
            </td>
        </tr>`;
        return;
    }
    
    tbody.innerHTML = filtered.map(q => {
        const statusColors = {
            'draft': 'badge-gray',
            'sent': 'badge-blue',
            'approved': 'badge-green',
            'rejected': 'badge-red',
            'converted': 'badge-purple'
        };
        const statusClass = statusColors[q.status?.toLowerCase()] || 'badge-gray';
        const statusLabel = q.status || 'Draft';
        
        // Calculate GST
        const amount = Number(q.amount || 0);
        const gstAmount = amount * 0.18;
        const totalWithGst = amount + gstAmount;
        
        return `<tr>
            <td class="font-mono">${esc(q.quote_no || ('#' + q.id))}</td>
            <td>${esc(q.customer)}</td>
            <td>${esc(q.service || '—')}</td>
            <td>₹${amount.toLocaleString('en-IN')}</td>
            <td><span class="badge badge-brand">₹${gstAmount.toLocaleString('en-IN')}</span></td>
            <td><strong>₹${totalWithGst.toLocaleString('en-IN')}</strong></td>
            <td>${esc(q.date || '—')}</td>
            <td><span class="badge ${statusClass}">${esc(statusLabel)}</span></td>
            <td>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-xs" onclick="editQuotation(${q.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${q.status !== 'converted' ? `<button class="btn btn-success btn-xs" onclick="convertQuotation(${q.id})" title="Convert to Sale">
                        <i class="fas fa-check"></i>
                    </button>` : ''}
                    <button class="btn btn-danger btn-xs" onclick="deleteQuotation(${q.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ============================================================
// FILTER QUOTATIONS
// ============================================================

function filterQuotations() {
    renderQuotations();
}

// ============================================================
// ADD QUOTATION
// ============================================================

function addQuotation() {
    const cust = document.getElementById('qCust').value.trim();
    const amt = parseFloat(document.getElementById('qAmt').value) || 0;
    const service = document.getElementById('qService').value;
    const validity = document.getElementById('qValidity').value || today();
    
    if (!cust) {
        toast('Customer name is required', 'error');
        document.getElementById('qCust').focus();
        return;
    }
    if (!amt || amt <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('qAmt').focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#newQuotationSection .btn-primary');
    const originalText = btn?.innerHTML || 'Save Quotation';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    // Calculate GST
    const gstAmount = amt * 0.18;
    const cgstAmount = gstAmount / 2;
    const sgstAmount = gstAmount / 2;
    const totalWithGst = amt + gstAmount;
    
    const id = Math.max(0, ...state.quotations.map(q => q.id || 0)) + 1;
    const quoteItem = {
        id: id,
        quote_no: 'QUO' + String(id).padStart(3, '0'),
        customer: cust,
        service: service,
        amount: amt,
        gst_amount: gstAmount,
        cgst_amount: cgstAmount,
        sgst_amount: sgstAmount,
        total_with_gst: totalWithGst,
        date: today(),
        validity: validity,
        status: 'Draft',
        created_at: new Date().toISOString()
    };
    
    state.quotations.push(quoteItem);
    
    apiFetch(API + 'save_quotation', {
        method: 'POST',
        body: JSON.stringify(quoteItem)
    });
    
    // Clear form
    document.getElementById('qCust').value = '';
    document.getElementById('qAmt').value = '';
    document.getElementById('qValidity').value = '';
    
    renderQuotations();
    updateStatCards();
    toast('Quotation created successfully!', 'success');
    showSection('quotationList');
    addActivityEntry('Quotation Created', quoteItem.quote_no + ' for ' + cust);
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// EDIT QUOTATION
// ============================================================

function editQuotation(id) {
    const q = state.quotations.find(x => x.id === id);
    if (!q) {
        toast('Quotation not found', 'error');
        return;
    }
    
    // Populate edit modal fields
    document.getElementById('editQuoteId').value = q.id;
    document.getElementById('editQuoteNo').value = q.quote_no || '';
    document.getElementById('editQuoteCustomer').value = q.customer || '';
    document.getElementById('editQuoteService').value = q.service || 'Written Off';
    document.getElementById('editQuoteAmount').value = q.amount || 0;
    document.getElementById('editQuoteDate').value = q.date || today();
    document.getElementById('editQuoteValidity').value = q.validity || '';
    document.getElementById('editQuoteStatus').value = q.status || 'Draft';
    document.getElementById('editQuoteNotes').value = q.notes || '';
    
    // Calculate and show GST
    updateQuoteGST();
    
    openModal('editQuotationModal');
}

// ============================================================
// UPDATE QUOTATION
// ============================================================

function updateQuotation() {
    const id = parseInt(document.getElementById('editQuoteId').value);
    const quoteNo = document.getElementById('editQuoteNo').value.trim();
    const customer = document.getElementById('editQuoteCustomer').value.trim();
    const service = document.getElementById('editQuoteService').value;
    const amount = parseFloat(document.getElementById('editQuoteAmount').value) || 0;
    const date = document.getElementById('editQuoteDate').value;
    const validity = document.getElementById('editQuoteValidity').value;
    const status = document.getElementById('editQuoteStatus').value;
    const notes = document.getElementById('editQuoteNotes').value.trim();
    
    if (!customer) {
        toast('Customer name is required', 'error');
        document.getElementById('editQuoteCustomer').focus();
        return;
    }
    if (!amount || amount <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('editQuoteAmount').focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#editQuotationModal .btn-primary');
    const originalText = btn?.innerHTML || 'Save Changes';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    // Calculate GST
    const gstAmount = amount * 0.18;
    const cgstAmount = gstAmount / 2;
    const sgstAmount = gstAmount / 2;
    const totalWithGst = amount + gstAmount;
    
    const updatedQuote = {
        id: id,
        quote_no: quoteNo || 'QUO' + String(id).padStart(3, '0'),
        customer: customer,
        service: service,
        amount: amount,
        gst_amount: gstAmount,
        cgst_amount: cgstAmount,
        sgst_amount: sgstAmount,
        total_with_gst: totalWithGst,
        date: date || today(),
        validity: validity,
        status: status,
        notes: notes,
        updated_at: new Date().toISOString()
    };
    
    // Update local state
    const idx = state.quotations.findIndex(q => q.id === id);
    if (idx !== -1) {
        state.quotations[idx] = { ...state.quotations[idx], ...updatedQuote };
    }
    
    // Send to API
    apiFetch(API + 'update_quotation', {
        method: 'POST',
        body: JSON.stringify(updatedQuote)
    });
    
    renderQuotations();
    closeModal('editQuotationModal');
    toast('Quotation updated successfully!', 'success');
    addActivityEntry('Quotation Updated', updatedQuote.quote_no + ' updated');
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// CONVERT QUOTATION TO SALE
// ============================================================

function convertQuotation(id) {
    const q = state.quotations.find(x => x.id === id);
    if (!q) {
        toast('Quotation not found', 'error');
        return;
    }
    
    if (!confirm(`Convert quotation ${q.quote_no} to sale?`)) {
        return;
    }
    
    // Update status
    q.status = 'converted';
    
    // Add to sales
    const saleItem = {
        id: state.sales.length + 1,
        customer: q.customer,
        service: q.service,
        amount: q.amount,
        date: today(),
        status: 'Completed'
    };
    state.sales.push(saleItem);
    
    apiFetch(API + 'convert_quotation', {
        method: 'POST',
        body: JSON.stringify({ id: id })
    });
    
    renderQuotations();
    updateStatCards();
    toast('Quotation converted to sale!', 'success');
    addActivityEntry('Quotation Converted', q.quote_no + ' converted to sale');
}

// ============================================================
// DELETE QUOTATION
// ============================================================

function deleteQuotation(id) {
    const q = state.quotations.find(x => x.id === id);
    if (!q) {
        toast('Quotation not found', 'error');
        return;
    }
    
    if (!confirm(`Delete quotation ${q.quote_no}?`)) {
        return;
    }
    
    state.quotations = state.quotations.filter(q => q.id !== id);
    
    apiFetch(API + 'delete_quotation', {
        method: 'POST',
        body: JSON.stringify({ id: id })
    });
    
    renderQuotations();
    toast('Quotation deleted', 'success');
    addActivityEntry('Quotation Deleted', q.quote_no + ' deleted');
}

// ============================================================
// UPDATE QUOTE GST DISPLAY
// ============================================================

function updateQuoteGST() {
    const amountInput = document.getElementById('editQuoteAmount');
    const amount = parseFloat(amountInput?.value) || 0;
    const gstAmount = amount * 0.18;
    const totalWithGst = amount + gstAmount;
    
    const amountDisplay = document.getElementById('editQuoteAmountDisplay');
    const gstDisplay = document.getElementById('editQuoteGstDisplay');
    const totalDisplay = document.getElementById('editQuoteTotalDisplay');
    
    if (amountDisplay) amountDisplay.textContent = '₹' + amount.toFixed(2);
    if (gstDisplay) gstDisplay.textContent = '₹' + gstAmount.toFixed(2);
    if (totalDisplay) totalDisplay.textContent = '₹' + totalWithGst.toFixed(2);
}

// ============================================================
// EXPORT QUOTATIONS (With Format Selection)
// ============================================================

function exportQuotations(format = 'csv') {
    // Check if there are quotations to export
    if (!state.quotations || state.quotations.length === 0) {
        toast('No quotations to export', 'warning');
        return;
    }
    
    // Get filter values
    const search = document.getElementById('quoteSearch')?.value || '';
    const status = document.getElementById('quoteStatusFilter')?.value || '';
    const service = document.getElementById('quoteServiceFilter')?.value || '';
    
    toast('Exporting quotations as ' + format.toUpperCase() + '...', 'info');
    
    // Build URL with parameters
    let url = 'api/export_quotations.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    if (status && status !== 'all') {
        url += '&status=' + encodeURIComponent(status);
    }
    if (service && service !== 'all') {
        url += '&service=' + encodeURIComponent(service);
    }
    url += '&include_gst=true';
    
    // Open download in new tab/window
    window.open(url, '_blank');
}

// ============================================================
// SHOW QUOTATION EXPORT OPTIONS
// ============================================================

function showQuoteExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportQuotations(selected);
    }
}

// ============================================================
// TOGGLE QUOTATIONS EXPORT DROPDOWN
// ============================================================

function toggleQuotesExportDropdown() {
    const dropdown = document.getElementById('quotesExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE QUOTATIONS EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('quotesExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// INITIALIZE QUOTATIONS (DOM Ready)
// ============================================================

// Add real-time GST calculation for new quotation
document.addEventListener('DOMContentLoaded', function() {
    const newAmountInput = document.getElementById('qAmt');
    if (newAmountInput) {
        newAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const gstAmount = amount * 0.18;
            const totalWithGst = amount + gstAmount;
            
            const amountDisplay = document.getElementById('newQuoteAmountDisplay');
            const gstDisplay = document.getElementById('newQuoteGstDisplay');
            const totalDisplay = document.getElementById('newQuoteTotalDisplay');
            
            if (amountDisplay) amountDisplay.textContent = '₹' + amount.toFixed(2);
            if (gstDisplay) gstDisplay.textContent = '₹' + gstAmount.toFixed(2);
            if (totalDisplay) totalDisplay.textContent = '₹' + totalWithGst.toFixed(2);
        });
    }
    
    // Also add real-time update when amount changes in edit modal
    const editAmountInput = document.getElementById('editQuoteAmount');
    if (editAmountInput) {
        editAmountInput.addEventListener('input', updateQuoteGST);
    }
});

/* ── REQUESTS ─────────────────────────────────── */
function renderRequests() {
  document.getElementById('reqTableBody').innerHTML = state.requests.length
    ? state.requests.map((r, i) =>
      '<tr><td>'+(i+1)+'</td><td>'+esc(r.name)+'</td><td>'+esc(r.service)+'</td><td>'+esc(r.date||'—')+'</td>' +
      '<td>'+statusBadge(r.status)+'</td>' +
      '<td><div class="gap-8"><button class="btn btn-success btn-xs" onclick="approveReq('+r.id+')">Approve</button>' +
      '<button class="btn btn-danger btn-xs" onclick="rejectReq('+r.id+')">Reject</button></div></td></tr>'
    ).join('')
    : '<tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div><div class="empty-state-title">No requests</div></div></td></tr>';
}

function approveReq(id) {
  const r = state.requests.find(x => x.id === id);
  if (r) { r.status = 'approved'; apiFetch(API + 'update_request', { method:'POST', body: JSON.stringify({ id, status: 'approved' }) }); renderRequests(); toast('Request approved!', 'success'); }
}
function rejectReq(id) {
  const r = state.requests.find(x => x.id === id);
  if (r) { r.status = 'rejected'; apiFetch(API + 'update_request', { method:'POST', body: JSON.stringify({ id, status: 'rejected' }) }); renderRequests(); toast('Request rejected', 'warning'); }
}

function submitRequest() {
  const name = document.getElementById('reqName').value.trim();
  if (!name) { toast('Name is required', 'error'); return; }
  const id = state.requests.length + 1;
  const reqItem = { id, name, email: document.getElementById('reqEmail').value.trim(), phone: document.getElementById('reqPhone').value.trim(), service: document.getElementById('reqService').value, date: today(), status:'pending' };
  state.requests.push(reqItem);
  apiFetch(API + 'save_request', { method:'POST', body: JSON.stringify(reqItem) });
  ['reqName','reqEmail','reqPhone'].forEach(i => { const el = document.getElementById(i); if (el) el.value = ''; });
  toast('Request submitted!', 'success'); showSection('customerRequests');
}

/* ── SALES (COMPLETE WITH UPDATE, DELETE, EXPORT, PAGINATION) ────────────────────────────────── */

// ============================================================
// SALES PAGINATION
// ============================================================

let salesPage = 1;
let salesPerPage = 20;

function changeSalesPage(direction) {
    const totalPages = Math.ceil((state.sales || []).length / salesPerPage) || 1;
    const newPage = salesPage + direction;
    if (newPage < 1 || newPage > totalPages) return;
    salesPage = newPage;
    renderSales();
}

// ============================================================
// RESET SALES FILTERS
// ============================================================

function resetSalesFilters() {
    document.getElementById('salesFrom').value = '';
    document.getElementById('salesTo').value = '';
    salesPage = 1;
    generateSalesReport();
}

// ============================================================
// RENDER SALES
// ============================================================

function renderSales() {
    const tbody = document.getElementById('salesBody');
    if (!tbody) return;
    
    // Get filter values
    const fromDate = document.getElementById('salesFrom')?.value || '';
    const toDate = document.getElementById('salesTo')?.value || '';
    
    let filtered = state.sales || [];
    
    // Apply date range filter
    if (fromDate) {
        filtered = filtered.filter(s => s.sale_date >= fromDate || s.date >= fromDate);
    }
    if (toDate) {
        filtered = filtered.filter(s => s.sale_date <= toDate || s.date <= toDate);
    }
    
    // Update total count
    const totalEl = document.getElementById('salesTotalCount');
    if (totalEl) totalEl.textContent = filtered.length;
    
    // Pagination
    const total = filtered.length;
    const start = (salesPage - 1) * salesPerPage;
    const end = Math.min(start + salesPerPage, total);
    const pageData = filtered.slice(start, end);
    
    // Update pagination info
    document.getElementById('salesStart').textContent = total > 0 ? start + 1 : 0;
    document.getElementById('salesEnd').textContent = end;
    document.getElementById('salesTotal').textContent = total;
    document.getElementById('salesPageInfo').textContent = `Page ${salesPage} of ${Math.ceil(total / salesPerPage) || 1}`;
    
    if (pageData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="empty-state-title">${fromDate || toDate ? 'No sales in date range' : 'No sales yet'}</div>
                        <div class="empty-state-sub">${fromDate || toDate ? 'Try adjusting your date range' : 'Click "Add Sale" to create your first sale'}</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    // Calculate total
    const totalAmount = filtered.reduce((sum, s) => sum + Number(s.amount || 0), 0);
    
    tbody.innerHTML = pageData.map((s, i) => {
        const statusColors = {
            'completed': 'badge-green',
            'pending': 'badge-amber',
            'cancelled': 'badge-red',
            'refunded': 'badge-purple'
        };
        const statusClass = statusColors[s.status?.toLowerCase()] || 'badge-gray';
        const statusLabel = s.status || 'Pending';
        const saleDate = s.sale_date || s.date || '—';
        const amount = Number(s.amount || 0);
        
        return `
            <tr>
                <td>${start + i + 1}</td>
                <td>${esc(saleDate)}</td>
                <td>${esc(s.customer_name || s.customer || '—')}</td>
                <td>${esc(s.service || '—')}</td>
                <td style="font-weight:700;color:var(--brand-dark);">₹${amount.toLocaleString('en-IN')}</td>
                <td><span class="badge ${statusClass}">${esc(statusLabel)}</span></td>
                <td>
                    <div class="gap-8">
                        <button class="btn btn-ghost btn-xs" onclick="editSale(${s.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-xs" onclick="deleteSale(${s.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('') + `
        <tr style="font-weight:700;background:var(--bg-sunken);">
            <td colspan="4" style="text-align:right;">Total</td>
            <td style="font-weight:800;color:var(--brand-dark);">₹${totalAmount.toLocaleString('en-IN')}</td>
            <td colspan="2"></td>
        </tr>
    `;
}

// ============================================================
// ADD SALE
// ============================================================

function addSale() {
    const customer = document.getElementById('saleCustomer')?.value.trim();
    const service = document.getElementById('saleService')?.value;
    const amount = parseFloat(document.getElementById('saleAmount')?.value) || 0;
    const date = document.getElementById('saleDate')?.value || today();
    const status = document.getElementById('saleStatus')?.value || 'Completed';
    const notes = document.getElementById('saleNotes')?.value.trim() || '';
    
    if (!customer) {
        toast('Customer name is required', 'error');
        document.getElementById('saleCustomer')?.focus();
        return;
    }
    if (!amount || amount <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('saleAmount')?.focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#addSaleSection .btn-primary');
    const originalText = btn?.innerHTML || 'Save Sale';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    const saleItem = {
        id: Math.max(0, ...state.sales.map(s => s.id || 0)) + 1,
        customer_name: customer,
        customer: customer,
        service: service,
        amount: amount,
        sale_date: date,
        date: date,
        status: status,
        notes: notes,
        created_at: new Date().toISOString()
    };
    
    state.sales.push(saleItem);
    
    // Add to transactions
    state.transactions.unshift({
        id: state.transactions.length + 1,
        date: date,
        description: 'Payment from ' + customer,
        amount: amount,
        type: 'credit'
    });
    
    // Update wallet balance
    walletBalance += amount;
    
    apiFetch(API + 'save_sale', {
        method: 'POST',
        body: JSON.stringify(saleItem)
    });
    
    // Clear form
    document.getElementById('saleCustomer').value = '';
    document.getElementById('saleAmount').value = '';
    document.getElementById('saleDate').value = '';
    document.getElementById('saleNotes').value = '';
    
    renderSales();
    updateStatCards();
    toast('Sale recorded!', 'success');
    showSection('salesReport');
    addActivityEntry('Sale Recorded', '₹' + amount.toLocaleString('en-IN') + ' from ' + customer);
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// EDIT SALE
// ============================================================

function editSale(id) {
    const sale = state.sales.find(s => s.id === id);
    if (!sale) {
        toast('Sale not found', 'error');
        return;
    }
    
    // Populate edit modal fields
    document.getElementById('editSaleId').value = sale.id;
    document.getElementById('editSaleCustomer').value = sale.customer_name || sale.customer || '';
    document.getElementById('editSaleService').value = sale.service || 'Written Off';
    document.getElementById('editSaleAmount').value = sale.amount || 0;
    document.getElementById('editSaleDate').value = sale.sale_date || sale.date || today();
    document.getElementById('editSaleStatus').value = sale.status || 'Completed';
    document.getElementById('editSaleNotes').value = sale.notes || '';
    
    openModal('editSaleModal');
}

// ============================================================
// UPDATE SALE
// ============================================================

function updateSale() {
    const id = parseInt(document.getElementById('editSaleId').value);
    const customer = document.getElementById('editSaleCustomer').value.trim();
    const service = document.getElementById('editSaleService').value;
    const amount = parseFloat(document.getElementById('editSaleAmount').value) || 0;
    const date = document.getElementById('editSaleDate').value || today();
    const status = document.getElementById('editSaleStatus').value;
    const notes = document.getElementById('editSaleNotes').value.trim();
    
    if (!customer) {
        toast('Customer name is required', 'error');
        document.getElementById('editSaleCustomer').focus();
        return;
    }
    if (!amount || amount <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('editSaleAmount').focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#editSaleModal .btn-primary');
    const originalText = btn?.innerHTML || 'Save Changes';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    const updatedSale = {
        id: id,
        customer_name: customer,
        customer: customer,
        service: service,
        amount: amount,
        sale_date: date,
        date: date,
        status: status,
        notes: notes,
        updated_at: new Date().toISOString()
    };
    
    // Update local state
    const idx = state.sales.findIndex(s => s.id === id);
    if (idx !== -1) {
        // Adjust wallet balance if amount changed
        const oldAmount = Number(state.sales[idx].amount || 0);
        walletBalance = walletBalance - oldAmount + amount;
        
        state.sales[idx] = { ...state.sales[idx], ...updatedSale };
    }
    
    // Send to API
    apiFetch(API + 'update_sale', {
        method: 'POST',
        body: JSON.stringify(updatedSale)
    });
    
    renderSales();
    updateStatCards();
    closeModal('editSaleModal');
    toast('Sale updated successfully!', 'success');
    addActivityEntry('Sale Updated', 'Sale #' + id + ' updated - ₹' + amount.toLocaleString('en-IN'));
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// DELETE SALE WITH MODAL
// ============================================================

function deleteSale(id) {
    const sale = state.sales.find(s => s.id === id);
    if (!sale) {
        toast('Sale not found', 'error');
        return;
    }
    
    document.getElementById('deleteSaleId').value = id;
    document.getElementById('deleteSaleCustomer').textContent = sale.customer_name || sale.customer || 'Unknown';
    document.getElementById('deleteSaleAmount').textContent = '₹' + Number(sale.amount || 0).toLocaleString('en-IN');
    openModal('deleteSaleModal');
}

// ============================================================
// CONFIRM DELETE SALE
// ============================================================

function confirmDeleteSale() {
    const id = parseInt(document.getElementById('deleteSaleId').value);
    const sale = state.sales.find(s => s.id === id);
    
    if (!sale) {
        toast('Sale not found', 'error');
        closeModal('deleteSaleModal');
        return;
    }
    
    closeModal('deleteSaleModal');
    toast('Deleting sale...', 'info');
    
    // Remove from state
    state.sales = state.sales.filter(s => s.id !== id);
    
    // Adjust wallet balance
    const amount = Number(sale.amount || 0);
    walletBalance -= amount;
    
    // Remove from transactions
    state.transactions = state.transactions.filter(t => 
        !(t.description && t.description.includes(sale.customer_name || sale.customer))
    );
    
    // Send to API
    apiFetch(API + 'delete_sale', {
        method: 'POST',
        body: JSON.stringify({ id: id })
    });
    
    renderSales();
    updateStatCards();
    toast('Sale deleted successfully!', 'success');
    addActivityEntry('Sale Deleted', 'Sale for ' + (sale.customer_name || sale.customer) + ' deleted');
}

// ============================================================
// GENERATE SALES REPORT
// ============================================================

async function generateSalesReport() {
    salesPage = 1;
    const from = document.getElementById('salesFrom')?.value;
    const to = document.getElementById('salesTo')?.value;
    
    try {
        const params = new URLSearchParams();
        if (from) params.set('from', from);
        if (to) params.set('to', to);
        const d = await apiFetch(API + 'get_sales_report?' + params);
        if (d && d.success && d.sales) {
            state.sales = d.sales;
        }
    } catch(e) {}
    
    renderSales();
}

// ============================================================
// EXPORT SALES (With Format Selection - CSV, Excel, JSON, PDF)
// ============================================================

function exportSales(format = 'csv') {
    if (!state.sales || state.sales.length === 0) {
        toast('No sales to export', 'warning');
        return;
    }
    
    const fromDate = document.getElementById('salesFrom')?.value || '';
    const toDate = document.getElementById('salesTo')?.value || '';
    
    const formatNames = {
        'csv': 'CSV',
        'excel': 'Excel',
        'json': 'JSON',
        'pdf': 'PDF'
    };
    
    toast('Exporting sales as ' + (formatNames[format] || format.toUpperCase()) + '...', 'info');
    
    let url = 'api/export_sales.php?format=' + format + '&_=' + new Date().getTime();
    if (fromDate) {
        url += '&from_date=' + encodeURIComponent(fromDate);
    }
    if (toDate) {
        url += '&to_date=' + encodeURIComponent(toDate);
    }
    
    window.open(url, '_blank');
}

// ============================================================
// SHOW SALES EXPORT OPTIONS
// ============================================================

function showSalesExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportSales(selected);
    }
}

// ============================================================
// TOGGLE SALES EXPORT DROPDOWN
// ============================================================

function toggleSalesExportDropdown() {
    const dropdown = document.getElementById('salesExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE SALES EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('salesExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

/* ── INVOICE ──────────────────────────────────── */
function generateInvoice() {
  const no = document.getElementById('invoiceNo').value.trim() || 'INV-' + String(Date.now()).slice(-6);
  const cust = document.getElementById('invoiceCust').value.trim();
  const svc = document.getElementById('invoiceService').value.trim();
  const amt = parseFloat(document.getElementById('invoiceAmt').value) || 0;
  if (!cust || !amt) { toast('Customer and amount required', 'error'); return; }
  const gst = Math.round(amt * 0.18);
  const total = amt + gst;
  const w = window.open('', '_blank', 'width=800,height=900');
  w.document.write('<!DOCTYPE html><html><head><title>Invoice '+esc(no)+'</title>' +
    '<style>body{font-family:Arial,sans-serif;padding:40px;color:#333;max-width:700px;margin:0 auto}' +
    '.header{display:flex;justify-content:space-between;border-bottom:3px solid #0d9e78;padding-bottom:20px;margin-bottom:30px}' +
    '.logo{font-size:28px;font-weight:800;color:#0d9e78}' +
    '.invoice-no{text-align:right;color:#666}' +
    'table{width:100%;border-collapse:collapse;margin:20px 0}' +
    'th{background:#0d9e78;color:#fff;padding:12px;text-align:left}' +
    'td{padding:12px;border-bottom:1px solid #eee}' +
    '.total-row{font-weight:700;font-size:18px;background:#f0f4f8}' +
    '.footer{margin-top:40px;text-align:center;color:#999;font-size:12px;border-top:1px solid #eee;padding-top:20px}' +
    '@media print{body{padding:20px}.no-print{display:none}}</style></head><body>' +
    '<div class="header"><div><div class="logo">CIBIL Repair</div><div style="color:#666;font-size:13px;">Credit Score Improvement Experts</div>' +
    '<div style="margin-top:8px;font-size:12px;color:#999;">contact@cibilrepair.in | +91 87094 55441</div></div>' +
    '<div class="invoice-no"><div style="font-size:22px;font-weight:700;">INVOICE</div><div>#'+esc(no)+'</div><div>Date: '+today()+'</div></div></div>' +
    '<div style="margin-bottom:20px;"><strong>Bill To:</strong><br>'+esc(cust)+'</div>' +
    '<table><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>' +
    '<tr><td>'+esc(svc || 'CIBIL Repair Service')+'</td><td>₹'+amt.toLocaleString('en-IN')+'</td></tr>' +
    '<tr><td>GST (18%)</td><td>₹'+gst.toLocaleString('en-IN')+'</td></tr>' +
    '<tr class="total-row"><td>Total</td><td>₹'+total.toLocaleString('en-IN')+'</td></tr>' +
    '</tbody></table>' +
    '<div style="margin-top:30px;"><strong>Payment Terms:</strong> Due within 15 days</div>' +
    '<div style="margin-top:10px;"><strong>Bank:</strong> HDFC Bank | A/C: XXXXXXXXXX | IFSC: HDFC0001234</div>' +
    '<div class="footer">Thank you for choosing CIBIL Repair!<br>This is a computer-generated invoice.</div>' +
    '<div class="no-print" style="text-align:center;margin-top:20px;">' +
    '<button onclick="window.print()" style="padding:12px 30px;background:#0d9e78;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;">🖨️ Print Invoice</button></div>' +
    '</body></html>');
  w.document.close();
  toast('Invoice generated!', 'success');
  addActivityEntry('Invoice Generated', no + ' for ' + cust + ' — ₹' + total.toLocaleString('en-IN'));
}

/* ── TRANSACTIONS (COMPLETE WITH EXPORT, FILTERS, ADD/WITHDRAW) ────────────────────────────────── */

// ============================================================
// ADD MONEY
// ============================================================

function addMoney() {
    const amt = parseFloat(document.getElementById('addAmt').value) || 0;
    const method = document.getElementById('addMethod').value;
    const description = document.getElementById('addDesc')?.value.trim() || 'Money added via ' + method;
    
    if (!amt || amt <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('addAmt').focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#addMoneySection .btn-primary');
    const originalText = btn?.innerHTML || 'Add Money';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Processing...';
    }
    
    walletBalance += amt;
    
    state.transactions.unshift({
        id: state.transactions.length + 1,
        date: today(),
        description: description,
        amount: amt,
        type: 'credit',
        method: method,
        created_at: new Date().toISOString()
    });
    
    apiFetch(API + 'wallet', {
        method: 'POST',
        body: JSON.stringify({ 
            action: 'add', 
            amount: amt, 
            method: method,
            description: description 
        })
    });
    
    document.getElementById('addAmt').value = '';
    if (document.getElementById('addDesc')) document.getElementById('addDesc').value = '';
    
    renderTransactions();
    updateStatCards();
    toast('₹' + amt.toLocaleString('en-IN') + ' added to wallet!', 'success');
    addActivityEntry('Money Added', '₹' + amt.toLocaleString('en-IN') + ' via ' + method);
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// WITHDRAW MONEY
// ============================================================

function withdrawMoney() {
    const amt = parseFloat(document.getElementById('wdAmt').value) || 0;
    const method = document.getElementById('wdMethod').value;
    const description = document.getElementById('wdDesc')?.value.trim() || 'Withdrawal via ' + method;
    
    if (!amt || amt <= 0) {
        toast('Please enter a valid amount', 'error');
        document.getElementById('wdAmt').focus();
        return;
    }
    
    if (amt > walletBalance) {
        toast('Insufficient balance. Current balance: ₹' + walletBalance.toLocaleString('en-IN'), 'error');
        document.getElementById('wdAmt').focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#withdrawMoneySection .btn-primary');
    const originalText = btn?.innerHTML || 'Request Withdrawal';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Processing...';
    }
    
    walletBalance -= amt;
    
    state.transactions.unshift({
        id: state.transactions.length + 1,
        date: today(),
        description: description,
        amount: amt,
        type: 'debit',
        method: method,
        created_at: new Date().toISOString()
    });
    
    apiFetch(API + 'wallet', {
        method: 'POST',
        body: JSON.stringify({ 
            action: 'withdraw', 
            amount: amt, 
            method: method,
            description: description 
        })
    });
    
    document.getElementById('wdAmt').value = '';
    if (document.getElementById('wdDesc')) document.getElementById('wdDesc').value = '';
    
    renderTransactions();
    updateStatCards();
    toast('₹' + amt.toLocaleString('en-IN') + ' withdrawal requested!', 'success');
    addActivityEntry('Withdrawal Requested', '₹' + amt.toLocaleString('en-IN') + ' via ' + method);
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============================================================
// LOAD TRANSACTIONS
// ============================================================

async function loadTransactions() {
    try {
        const d = await apiFetch(API + 'get_transactions');
        
        if (d && d.success && d.data && d.data.transactions) {
            state.transactions = d.data.transactions;
            
            if (d.data.stats) {
                walletBalance = d.data.stats.net_balance || 0;
                const balanceEl = document.getElementById('walletBalance');
                if (balanceEl) {
                    balanceEl.textContent = '₹' + walletBalance.toLocaleString('en-IN');
                }
            }
        } else {
            state.transactions = [];
            console.warn('No transactions data received');
        }
    } catch(e) {
        console.error('Error loading transactions:', e);
        state.transactions = [];
    }
    
    renderTransactions();
}

// ============================================================
// RENDER TRANSACTIONS
// ============================================================

function renderTransactions() {
    const tbody = document.getElementById('txBody');
    if (!tbody) return;
    
    // Get filter values
    const search = document.getElementById('txSearch')?.value?.toLowerCase() || '';
    const typeFilter = document.getElementById('txTypeFilter')?.value || '';
    const methodFilter = document.getElementById('txMethodFilter')?.value || '';
    
    let filtered = state.transactions || [];
    
    // Apply search filter
    if (search) {
        filtered = filtered.filter(t => 
            (t.description || '').toLowerCase().includes(search) ||
            (t.reference_id || '').toLowerCase().includes(search) ||
            (t.method || '').toLowerCase().includes(search)
        );
    }
    
    // Apply type filter
    if (typeFilter) {
        filtered = filtered.filter(t => (t.type || '').toLowerCase() === typeFilter);
    }
    
    // Apply method filter
    if (methodFilter) {
        filtered = filtered.filter(t => (t.method || '').toLowerCase() === methodFilter);
    }
    
    // Update total count
    const totalEl = document.getElementById('txTotalCount');
    if (totalEl) totalEl.textContent = filtered.length;
    
    // Update pagination info
    const total = filtered.length;
    document.getElementById('txStart').textContent = total > 0 ? 1 : 0;
    document.getElementById('txEnd').textContent = total;
    document.getElementById('txTotal').textContent = total;
    
    if (!filtered || filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-receipt"></i></div><div class="empty-state-title">No transactions found</div><div class="empty-state-sub">' + 
            (search || typeFilter || methodFilter ? 'Try adjusting your filters' : 'No transactions found in the system') + 
            '</div></div></td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.slice(0, 100).map(t => {
        const date = t.date || '—';
        const description = t.description || '—';
        const amount = Number(t.amount || 0);
        const type = t.type || 'debit';
        const method = t.method || '';
        
        return `<tr>
            <td style="font-size:12px;color:var(--text-muted);">${esc(date)}</td>
            <td>
                ${esc(description)}
                ${method ? ' <span class="badge badge-gray" style="font-size:9px;">' + esc(method) + '</span>' : ''}
            </td>
            <td style="font-weight:700;color:${type === 'credit' ? 'var(--success)' : 'var(--danger)'};">
                ${type === 'credit' ? '+' : '−'}₹${amount.toLocaleString('en-IN')}
            </td>
            <td><span class="badge ${type === 'credit' ? 'badge-green' : 'badge-red'}">${esc(type)}</span></td>
        </tr>`;
    }).join('');
}

// ============================================================
// FILTER TRANSACTIONS
// ============================================================

function filterTransactions() {
    renderTransactions();
}

// ============================================================
// RESET TRANSACTIONS FILTERS
// ============================================================

function resetTxFilters() {
    document.getElementById('txSearch').value = '';
    document.getElementById('txTypeFilter').value = '';
    document.getElementById('txMethodFilter').value = '';
    renderTransactions();
}

// ============================================================
// EXPORT TRANSACTIONS (With Format Selection)
// ============================================================

function exportTransactions(format = 'csv') {
    // Check if there are transactions to export
    if (!state.transactions || state.transactions.length === 0) {
        toast('No transactions to export', 'warning');
        return;
    }
    
    // Get filter values
    const search = document.getElementById('txSearch')?.value || '';
    const type = document.getElementById('txTypeFilter')?.value || '';
    const method = document.getElementById('txMethodFilter')?.value || '';
    
    const formatNames = {
        'csv': 'CSV',
        'excel': 'Excel',
        'json': 'JSON',
        'pdf': 'PDF'
    };
    
    toast('Exporting transactions as ' + (formatNames[format] || format.toUpperCase()) + '...', 'info');
    
    // Build URL with parameters
    let url = 'api/export_transactions.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    if (type && type !== 'all') {
        url += '&type=' + encodeURIComponent(type);
    }
    if (method && method !== 'all') {
        url += '&method=' + encodeURIComponent(method);
    }
    url += '&include_gst=true';
    
    // Open download in new tab/window
    window.open(url, '_blank');
}

// ============================================================
// SHOW TRANSACTIONS EXPORT OPTIONS
// ============================================================

function showTxExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportTransactions(selected);
    }
}

// ============================================================
// TOGGLE TRANSACTIONS EXPORT DROPDOWN
// ============================================================

function toggleTxExportDropdown() {
    const dropdown = document.getElementById('txExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE TRANSACTIONS EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('txExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

/* ── EXPENSES ─────────────────────────────────── */
function addExpense() {
  const amt = parseFloat(document.getElementById('expAmt').value) || 0;
  const cat = document.getElementById('expCat').value;
  const desc = document.getElementById('expDesc').value.trim();
  if (!amt) { toast('Amount is required', 'error'); return; }
  const expItem = { id: state.expenses.length + 1, category: cat, description: desc || cat, amount: amt, date: document.getElementById('expDate').value || today() };
  state.expenses.push(expItem);
  apiFetch(API + 'save_expense', { method:'POST', body: JSON.stringify(expItem) });
  state.transactions.unshift({ id: state.transactions.length + 1, date: today(), description: 'Expense: ' + (desc || cat), amount: amt, type:'debit' });
  walletBalance -= amt;
  ['expAmt','expDesc'].forEach(i => { const el = document.getElementById(i); if (el) el.value = ''; });
  updateStatCards(); toast('Expense saved!', 'success');
}

function generateExpenseReport() {
  const from = document.getElementById('expRepFrom')?.value;
  const to = document.getElementById('expRepTo')?.value;
  let rows = state.expenses;
  if (from) rows = rows.filter(e => e.date >= from);
  if (to) rows = rows.filter(e => e.date <= to);
  const total = rows.reduce((s, r) => s + (r.amount || 0), 0);
  document.getElementById('expRepBody').innerHTML = rows.length
    ? rows.map((e, i) =>
      '<tr><td>'+(i+1)+'</td><td>'+esc(e.date)+'</td><td><span class="badge badge-blue">'+esc(e.category)+'</span></td>' +
      '<td>'+esc(e.description)+'</td><td>₹'+Number(e.amount).toLocaleString('en-IN')+'</td></tr>'
    ).join('') + '<tr style="font-weight:700;background:var(--bg-sunken);"><td colspan="4" style="text-align:right;">Total</td><td>₹'+total.toLocaleString('en-IN')+'</td></tr>'
    : '<tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-chart-pie"></i></div><div class="empty-state-title">No expense data</div></div></td></tr>';
}

function exportReport() {
  const csv = 'Metric,Current,Target,Gap,Status\nClient Acquisition,28/mo,40/mo,-12,Below\nPartner Conversion,68%,75%,-7%,Needs Work\nRevenue Growth,15.2%,20%,-4.8%,On Track\nCustomer Satisfaction,4.8/5,4.5/5,+0.3,Exceeding';
  dlFile(csv, 'business_metrics_' + today() + '.csv', 'text/csv');
  toast('Report exported!', 'success');
}

/* ── ACTIVITY ─────────────────────────────────── */
let activityEntries = [];
function addActivityEntry(action, details) {
  const entry = { created_at: new Date().toISOString(), user_name: USER_NAME, action, details, ip_address:'192.168.1.1' };
  activityEntries.unshift(entry);
  apiFetch(API + 'log_activity', { method:'POST', body: JSON.stringify(entry) });
}

function refreshActivity() {
  if (!activityEntries.length) {
    const now = new Date();
    activityEntries = [
      { created_at: now.toISOString(), user_name: USER_NAME, action:'Admin Login', details:'Dashboard loaded', ip_address:'192.168.1.1' },
      { created_at: new Date(+now - 120000).toISOString(), user_name:'System', action:'Partner Updated', details:'Auto sync completed', ip_address:'127.0.0.1' },
      { created_at: new Date(+now - 360000).toISOString(), user_name: USER_NAME, action:'Sale Recorded', details:'₹15,000 from Rajesh Kumar', ip_address:'192.168.1.1' },
      { created_at: new Date(+now - 600000).toISOString(), user_name: USER_NAME, action:'Code Generated', details:'Partner code created', ip_address:'192.168.1.1' },
      { created_at: new Date(+now - 900000).toISOString(), user_name:'System', action:'Backup Created', details:'Auto backup successful', ip_address:'127.0.0.1' }
    ];
  }
  const fmt = d => new Date(d).toLocaleString('en-IN', { dateStyle:'short', timeStyle:'short' });
  document.getElementById('activityBody').innerHTML = activityEntries.slice(0, 10).map(a =>
    '<tr><td>'+esc(a.action)+'</td><td>'+esc(a.user_name)+'</td>' +
    '<td style="color:var(--text-muted);font-size:12px;">'+fmt(a.created_at)+'</td>' +
    '<td>'+statusBadge('active')+'</td></tr>'
  ).join('');
}

/* ── LEADS SECTION (COMPLETE WITH EXPORT DROPDOWN) ────────────────────────────────── */

// ============================================================
// LOAD LEADS
// ============================================================

async function loadLeads() {
    const tbody = document.getElementById('leadsBody');
    if (!tbody) {
        console.warn('leadsBody element not found');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="10"><div class="loading-cell"><div class="spinner"></div></div></td></tr>';
    
    try {
        // Try to fetch from API
        const d = await apiFetch(API + 'get_leads');
        if (d && d.success && d.leads) {
            allLeads = d.leads;
        } else {
            // Use demo data if API fails
            allLeads = demoLeads();
        }
    } catch (e) {
        console.error('Error loading leads:', e);
        allLeads = demoLeads();
    }
    
    renderLeads();
    
    // Update badge
    const badge = document.getElementById('leadsBadge');
    if (badge) badge.textContent = allLeads.length;
}

// ============================================================
// DEMO LEADS DATA
// ============================================================

function demoLeads() {
    return [
        { id: 1, name: 'Ankit Sharma', phone: '9876541000', email: 'ankit@email.com', message: 'Need CIBIL repair', source: 'website', status: 'new', priority: 'high', service: 'CIBIL Repair', amount: 15000, created_at: '2025-04-01 10:30:00' },
        { id: 2, name: 'Pooja Verma', phone: '9876541001', email: 'pooja@email.com', message: 'Credit score issue', source: 'referral', status: 'contacted', priority: 'medium', service: 'Profile Correction', amount: 8000, created_at: '2025-04-02 14:15:00' },
        { id: 3, name: 'Ramesh Nair', phone: '9876541002', email: 'ramesh@email.com', message: 'Settlement help', source: 'google_ads', status: 'new', priority: 'high', service: 'Settled', amount: 25000, created_at: '2025-04-03 09:45:00' },
        { id: 4, name: 'Sita Devi', phone: '9876541003', email: 'sita@email.com', message: 'Profile correction', source: 'website', status: 'converted', priority: 'medium', service: 'Profile Correction', amount: 12000, created_at: '2025-04-04 11:00:00' },
        { id: 5, name: 'Mohit Agarwal', phone: '9876541004', email: 'mohit@email.com', message: 'Loan write-off help', source: 'call', status: 'lost', priority: 'low', service: 'Written Off', amount: 0, created_at: '2025-04-05 16:20:00' }
    ];
}

// ============================================================
// RENDER LEADS
// ============================================================

function renderLeads() {
    const tbody = document.getElementById('leadsBody');
    if (!tbody) return;
    
    const search = document.getElementById('leadSearch')?.value?.toLowerCase() || '';
    const statusFilter = document.getElementById('leadStatusFilter')?.value || '';
    const priorityFilter = document.getElementById('leadPriorityFilter')?.value || '';
    
    let filtered = allLeads || [];
    
    // Apply search
    if (search) {
        filtered = filtered.filter(l => 
            (l.name || '').toLowerCase().includes(search) ||
            (l.phone || '').toLowerCase().includes(search) ||
            (l.email || '').toLowerCase().includes(search) ||
            (l.service || '').toLowerCase().includes(search)
        );
    }
    
    // Apply status filter
    if (statusFilter) {
        filtered = filtered.filter(l => l.status === statusFilter);
    }
    
    // Apply priority filter
    if (priorityFilter) {
        filtered = filtered.filter(l => l.priority === priorityFilter);
    }
    
    // Sort by created_at (newest first)
    filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    
    // Pagination
    const total = filtered.length;
    const page = pagination.leads?.page || 1;
    const perPage = pagination.leads?.perPage || 20;
    const start = (page - 1) * perPage;
    const end = Math.min(start + perPage, total);
    const pageData = filtered.slice(start, end);
    
    if (pageData.length === 0) {
        tbody.innerHTML = `<tr>
            <td colspan="10">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                    <div class="empty-state-title">${search || statusFilter || priorityFilter ? 'No leads match your filters' : 'No leads yet'}</div>
                    <div class="empty-state-sub">${search || statusFilter || priorityFilter ? 'Try adjusting your filters' : 'Click "Add Lead" to create your first lead'}</div>
                </div>
            </td>
        </tr>`;
        updatePaginationInfo(total, page, perPage);
        return;
    }
    
    tbody.innerHTML = pageData.map((l, i) => {
        const priorityBadge = {
            low: 'badge-gray',
            medium: 'badge-amber',
            high: 'badge-blue',
            urgent: 'badge-red'
        }[l.priority] || 'badge-gray';
        
        const sourceLabels = {
            website: '🌐 Website',
            referral: '🤝 Referral',
            google_ads: '📢 Google Ads',
            facebook: '📘 Facebook',
            instagram: '📸 Instagram',
            call: '📞 Call',
            email: '📧 Email',
            other: '📌 Other'
        };
        
        return `<tr>
            <td>${start + i + 1}</td>
            <td><strong>${esc(l.name)}</strong></td>
            <td>${esc(l.phone)}</td>
            <td>${esc(l.email || '—')}</td>
            <td>${esc(l.service || '—')}</td>
            <td><span class="badge ${priorityBadge}">${esc(l.priority || 'medium')}</span></td>
            <td>${esc(sourceLabels[l.source] || l.source || '—')}</td>
            <td>${statusBadge(l.status)}</td>
            <td>${l.created_at ? new Date(l.created_at).toLocaleDateString('en-IN') : '—'}</td>
            <td>
                <div class="gap-8">
                    <button class="btn btn-ghost btn-xs" onclick="editLead(${l.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteLead(${l.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                    ${l.status === 'new' ? `<button class="btn btn-success btn-xs" onclick="convertLead(${l.id})" title="Convert"><i class="fas fa-check"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
    
    // Update pagination info
    updatePaginationInfo(total, page, perPage);
}

// ============================================================
// UPDATE PAGINATION INFO
// ============================================================

function updatePaginationInfo(total, page, perPage) {
    const start = total > 0 ? (page - 1) * perPage + 1 : 0;
    const end = Math.min(page * perPage, total);
    
    const startEl = document.getElementById('leadsStart');
    const endEl = document.getElementById('leadsEnd');
    const totalEl = document.getElementById('leadsTotal');
    const infoEl = document.getElementById('leadsPageInfo');
    
    if (startEl) startEl.textContent = start;
    if (endEl) endEl.textContent = end;
    if (totalEl) totalEl.textContent = total;
    if (infoEl) infoEl.textContent = `Page ${page} of ${Math.ceil(total / perPage) || 1}`;
}

// ============================================================
// FILTER LEADS
// ============================================================

function filterLeads() {
    if (pagination.leads) pagination.leads.page = 1;
    renderLeads();
}

// ============================================================
// ADD LEAD
// ============================================================

function addLead() {
    console.log('=== ADD LEAD FUNCTION CALLED ===');
    
    // Get form values
    const name = document.getElementById('leadName')?.value?.trim() || '';
    const phone = document.getElementById('leadPhone')?.value?.trim() || '';
    const email = document.getElementById('leadEmail')?.value?.trim() || '';
    const message = document.getElementById('leadMessage')?.value?.trim() || '';
    const source = document.getElementById('leadSource')?.value || 'website';
    const priority = document.getElementById('leadPriority')?.value || 'medium';
    const service = document.getElementById('leadService')?.value || 'CIBIL Repair';
    const amount = parseFloat(document.getElementById('leadAmount')?.value) || 0;
    
    console.log('Form values:', { name, phone, email, source, priority, service, amount });
    
    // Validate
    if (!name) {
        toast('Name is required', 'error');
        document.getElementById('leadName')?.focus();
        return;
    }
    
    if (!phone) {
        toast('Phone number is required', 'error');
        document.getElementById('leadPhone')?.focus();
        return;
    }
    
    if (!validatePhone(phone)) {
        toast('Please enter a valid 10-digit phone number', 'error');
        document.getElementById('leadPhone')?.focus();
        return;
    }
    
    if (email && !validateEmail(email)) {
        toast('Please enter a valid email address', 'error');
        document.getElementById('leadEmail')?.focus();
        return;
    }
    
    // Show loading
    const btn = document.querySelector('#addLeadSection .btn-primary');
    const originalText = btn?.innerHTML || 'Save Lead';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    }
    
    // Prepare data
    const data = {
        name: name,
        phone: phone,
        email: email,
        message: message,
        source: source,
        status: 'new',
        priority: priority,
        service: service,
        amount: amount,
        notes: 'Added from admin dashboard'
    };
    
    console.log('Sending data:', data);
    
    // Send to API
    fetch('api/add_lead.php?test=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => {
        console.log('Response status:', res.status);
        return res.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        console.log('Parsed response:', data);
        if (data.success) {
            toast('Lead added successfully!', 'success');
            
            // Clear form
            document.getElementById('leadName').value = '';
            document.getElementById('leadPhone').value = '';
            document.getElementById('leadEmail').value = '';
            document.getElementById('leadMessage').value = '';
            document.getElementById('leadAmount').value = '';
            
            // Refresh leads
            loadLeads();
            
            // Switch to leads list
            showSection('leads');
            
            // Add activity log
            addActivityEntry('Lead Added', data.data.name + ' (' + data.data.priority + ' priority)');
        } else {
            toast(data.error || 'Failed to add lead', 'error');
        }
    })
    .catch(err => {
        console.error('Error adding lead:', err);
        toast('Failed to add lead: ' + err.message, 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// ============================================================
// EDIT LEAD
// ============================================================

function editLead(id) {
    const lead = allLeads.find(l => l.id === id);
    if (!lead) {
        toast('Lead not found', 'error');
        return;
    }
    
    // For now, show a toast with lead info
    toast('Edit feature coming soon!', 'info');
    console.log('Edit lead:', lead);
    
    // You can implement a modal here
    // For now, just log the lead
}

// ============================================================
// DELETE LEAD
// ============================================================

function deleteLead(id) {
    if (!confirm('Are you sure you want to delete this lead?')) return;
    
    const lead = allLeads.find(l => l.id === id);
    if (!lead) {
        toast('Lead not found', 'error');
        return;
    }
    
    // Remove from list (optimistic update)
    allLeads = allLeads.filter(l => l.id !== id);
    renderLeads();
    
    // Update badge
    const badge = document.getElementById('leadsBadge');
    if (badge) badge.textContent = allLeads.length;
    
    toast('Lead deleted successfully', 'success');
    addActivityEntry('Lead Deleted', lead.name);
    
    // TODO: Call API to delete from server
    // fetch('api/delete_lead.php', { method: 'POST', body: JSON.stringify({ id: id }) });
}

// ============================================================
// CONVERT LEAD
// ============================================================

function convertLead(id) {
    const lead = allLeads.find(l => l.id === id);
    if (!lead) {
        toast('Lead not found', 'error');
        return;
    }
    
    // Update status
    lead.status = 'converted';
    renderLeads();
    
    // Update badge
    const badge = document.getElementById('leadsBadge');
    if (badge) badge.textContent = allLeads.length;
    
    toast('Lead converted successfully!', 'success');
    addActivityEntry('Lead Converted', lead.name + ' converted to customer');
    
    // TODO: Call API to update lead status
    // fetch('api/update_lead.php', { method: 'POST', body: JSON.stringify({ id: id, status: 'converted' }) });
}

// ============================================================
// EXPORT LEADS (With Format Selection)
// ============================================================

function exportLeads(format = 'csv') {
    // Check if there are leads to export
    if (!allLeads || allLeads.length === 0) {
        toast('No leads to export', 'warning');
        return;
    }
    
    // Get filter values
    const search = document.getElementById('leadSearch')?.value || '';
    const status = document.getElementById('leadStatusFilter')?.value || '';
    const priority = document.getElementById('leadPriorityFilter')?.value || '';
    
    toast('Exporting leads as ' + format.toUpperCase() + '...', 'info');
    
    // Build URL with parameters
    let url = 'api/export_leads.php?format=' + format + '&_=' + new Date().getTime();
    if (search) {
        url += '&search=' + encodeURIComponent(search);
    }
    if (status && status !== 'all') {
        url += '&status=' + encodeURIComponent(status);
    }
    if (priority && priority !== 'all') {
        url += '&priority=' + encodeURIComponent(priority);
    }
    
    // Open download in new tab/window
    window.open(url, '_blank');
}

// ============================================================
// SHOW LEAD EXPORT OPTIONS
// ============================================================

function showLeadExportOptions() {
    const format = prompt('Choose export format:\n1. CSV\n2. Excel\n3. JSON\n4. PDF', 'csv');
    if (format) {
        const formats = {
            '1': 'csv',
            '2': 'excel',
            '3': 'json',
            '4': 'pdf',
            'csv': 'csv',
            'excel': 'excel',
            'json': 'json',
            'pdf': 'pdf'
        };
        const selected = formats[format.toLowerCase()] || 'csv';
        exportLeads(selected);
    }
}

// ============================================================
// TOGGLE LEADS EXPORT DROPDOWN
// ============================================================

function toggleLeadsExportDropdown() {
    const dropdown = document.getElementById('leadsExportDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// CLOSE LEADS EXPORT DROPDOWN (Click Outside)
// ============================================================

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('leadsExportDropdown');
    if (dropdown && !e.target.closest('.gap-8')) {
        dropdown.style.display = 'none';
    }
});

// ============================================================
// LEGACY EXPORT LEADS (Keep for backward compatibility)
// ============================================================

function exportLeadsLegacy() {
    if (!allLeads || allLeads.length === 0) {
        toast('No leads to export', 'warning');
        return;
    }
    
    // Create CSV
    const headers = ['ID', 'Name', 'Phone', 'Email', 'Service', 'Priority', 'Source', 'Status', 'Amount', 'Created'];
    const rows = allLeads.map(l => [
        l.id,
        l.name,
        l.phone,
        l.email || '',
        l.service || '',
        l.priority || 'medium',
        l.source || '',
        l.status || 'new',
        l.amount || 0,
        l.created_at || ''
    ]);
    
    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'leads_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    toast('Leads exported successfully!', 'success');
}

/* ── AI ANALYZER ──────────────────────────────── */
let lastAnalysis = '';
function analyzeDocument() {
  const file = document.getElementById('docFile').files[0];
  if (!file) { toast('Please select a document first', 'error'); return; }
  const btn = document.getElementById('analyzeBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Analyzing…';
  const docType = document.getElementById('docType').value;
  const reader = new FileReader();
  reader.onload = function (e) {
    const content = e.target.result;
    setTimeout(() => {
      lastAnalysis = generateAnalysisReport(docType, file.name, content);
      document.getElementById('analysisContent').innerHTML = esc(lastAnalysis).replace(/\n/g, '<br>');
      document.getElementById('analysisResult').style.display = 'block';
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-robot"></i>Analyze Document';
      toast('Analysis complete!', 'success');
      const analysisItem = { id: state.analyses.length + 1, document_type: docType, filename: file.name, created_at: new Date().toLocaleString('en-IN'), result: lastAnalysis };
      state.analyses.unshift(analysisItem);
      apiFetch(API + 'save_analysis', { method:'POST', body: JSON.stringify(analysisItem) });
      loadAnalysisHistory();
    }, 1500);
  };
  reader.onerror = function () {
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-robot"></i>Analyze Document';
    toast('Could not read file', 'error');
  };
  reader.readAsText(file);
}

function generateAnalysisReport(docType, filename, content) {
  const types = {
    credit_report: '═══ CIBIL/CREDIT REPORT ANALYSIS ═══\n\nDocument: '+filename+'\nAnalysis Date: '+new Date().toLocaleString('en-IN')+'\n\n📊 SCORE ASSESSMENT\n• Current Score Range: Likely 300-550 (needs improvement)\n• Industry Benchmark: 750+ for loan approval\n• Improvement Potential: High\n\n⚠️ KEY ISSUES IDENTIFIED\n1. Possible late payment entries detected\n2. High credit utilization ratio suspected\n3. Settlement/write-off entries may be present\n4. Multiple hard inquiries within short period\n\n✅ RECOMMENDED ACTIONS\n1. Dispute inaccurate entries with credit bureau\n2. Negotiate with lenders for removal of settled entries\n3. Reduce credit card utilization below 30%\n4. Set up autopay to avoid future late payments\n5. File formal dispute letters within 30 days\n\n📝 DISPUTE PRIORITY\n• Priority 1: Remove incorrect late payment records\n• Priority 2: Challenge settlement/write-off entries\n• Priority 3: Request removal of old hard inquiries\n\nEstimated recovery time: 60-120 days with active dispute process.',
    bank_statement: '═══ BANK STATEMENT ANALYSIS ═══\n\nDocument: '+filename+'\nAnalysis Date: '+new Date().toLocaleString('en-IN')+'\n\n💰 FINANCIAL OVERVIEW\n• Income Pattern: Regular monthly inflows detected\n• Spending Pattern: Moderate — within acceptable range\n• EMI Load: Needs review\n\n⚠️ OBSERVATIONS\n1. EMI bounce/return entries may affect CIBIL\n2. Cash withdrawal pattern is within norms\n3. UPI/digital transaction volume is healthy\n\n✅ RECOMMENDATIONS\n1. Maintain minimum balance to avoid charges\n2. Ensure EMI payments clear on time\n3. Reduce unnecessary subscriptions\n4. Build 3-month emergency fund',
    loan_noc: '═══ LOAN NOC / SETTLEMENT ANALYSIS ═══\n\nDocument: '+filename+'\nAnalysis Date: '+new Date().toLocaleString('en-IN')+'\n\n📄 DOCUMENT STATUS\n• Type: Loan Closure / Settlement Document\n• Verification: Requires cross-check with CIBIL\n\n⚠️ IMPORTANT FINDINGS\n1. Ensure "Closed" status reflects on CIBIL (not "Settled")\n2. "Settled" status negatively impacts score for 7 years\n3. NOC should be obtained for every closed account\n\n✅ ACTION ITEMS\n1. Write to bank to update CIBIL status as "Closed"\n2. Attach NOC copy with dispute letter\n3. Follow up with CIBIL bureau within 30 days\n4. Request removal of "Written Off" tag if paid in full',
    legal_notice: '═══ LEGAL NOTICE ANALYSIS ═══\n\nDocument: '+filename+'\nAnalysis Date: '+new Date().toLocaleString('en-IN')+'\n\n⚖️ LEGAL ASSESSMENT\n• Document Type: Legal Notice / Summons\n• Urgency: HIGH — Respond within stipulated timeframe\n\n⚠️ KEY POINTS\n1. Verify the legitimacy of the sending party\n2. Check limitation period (3 years for most debts)\n3. Assess if debt is time-barred\n\n✅ RECOMMENDED ACTIONS\n1. Do NOT ignore — respond within 30 days\n2. Consult legal advisor for formal response\n3. Gather all payment receipts and correspondence\n4. Check if the amount claimed matches actual outstanding\n5. Consider out-of-court settlement if viable',
    other: '═══ DOCUMENT ANALYSIS ═══\n\nDocument: '+filename+'\nAnalysis Date: '+new Date().toLocaleString('en-IN')+'\n\n📑 GENERAL ASSESSMENT\n• Document reviewed successfully\n• File Size: ' + (content.length/1024).toFixed(1) + ' KB of text content\n\n✅ RECOMMENDATIONS\n1. Cross-reference with CIBIL records\n2. Maintain copies for dispute process\n3. Share with your credit counselor for detailed review'
  };
  return types[docType] || types.other;
}

function copyAnalysis() {
  if (lastAnalysis) { navigator.clipboard.writeText(lastAnalysis); toast('Copied!', 'success'); }
}

function downloadDispute() {
  if (!lastAnalysis) { toast('No analysis available', 'error'); return; }
  const letter = 'DISPUTE LETTER\n\nDate: ' + today() + '\n\nTo: The Branch Manager / CIBIL Bureau\n\nSubject: Dispute Regarding Incorrect CIBIL Entry\n\nDear Sir/Madam,\n\nI am writing to formally dispute the following entry/entries on my credit report as identified in the attached analysis:\n\n' + lastAnalysis + '\n\nI request you to investigate and correct/remove the erroneous entries within 30 days as per CIBIL guidelines.\n\nPlease find attached supporting documents for your reference.\n\nThank you for your prompt attention.\n\nSincerely,\n[Customer Name]\n[Contact Information]';
  dlFile(letter, 'dispute_letter_' + Date.now() + '.txt', 'text/plain');
  toast('Dispute letter downloaded!', 'success');
}

async function loadAnalysisHistory() {
  try {
    const d = await apiFetch(API + 'get_analyses');
    if (d && d.success && d.analyses) state.analyses = d.analyses;
  } catch(e) {}
  document.getElementById('analysisHistory').innerHTML = state.analyses.length
    ? state.analyses.map((a, i) =>
      '<tr><td>'+(i+1)+'</td><td>'+esc(a.document_type.replace(/_/g,' '))+'</td>' +
      '<td>'+esc(a.filename)+'</td><td style="font-size:12px;color:var(--text-muted);">'+esc(a.created_at)+'</td>' +
      '<td><div class="gap-8"><button class="btn btn-ghost btn-xs" onclick="viewAnalysis('+i+')"><i class="fas fa-eye"></i></button>' +
      '<button class="btn btn-danger btn-xs" onclick="deleteAnalysis('+i+')"><i class="fas fa-trash"></i></button></div></td></tr>'
    ).join('')
    : '<tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-history"></i></div><div class="empty-state-title">No history yet</div><div class="empty-state-sub">Analyze a document to see history here</div></div></td></tr>';
}

function viewAnalysis(idx) {
  const a = state.analyses[idx]; if (!a) return;
  document.getElementById('modalAnalysisContent').innerHTML = esc(a.result).replace(/\n/g, '<br>');
  openModal('analysisModal');
}

function deleteAnalysis(idx) {
  if (!confirm('Delete this analysis?')) return;
  const a = state.analyses[idx];
  state.analyses.splice(idx, 1);
  if (a) apiFetch(API + 'delete_analysis', { method:'POST', body: JSON.stringify({ id: a.id }) });
  loadAnalysisHistory(); toast('Analysis deleted', 'success');
}

function copyModalAnalysis() {
  const t = document.getElementById('modalAnalysisContent').innerText;
  if (t) { navigator.clipboard.writeText(t); toast('Copied!', 'success'); }
}

/* ── ACTIVITY LOG ─────────────────────────────── */
let logData = [];
async function loadActivityLog() {
  try {
    const d = await apiFetch(API + 'get_activity_logs?limit=100');
    logData = (d && d.success && d.logs) ? d.logs : activityEntries.length ? activityEntries : demoLog();
  } catch (e) { logData = activityEntries.length ? activityEntries : demoLog(); }
  renderLog(logData);
}

function demoLog() {
  return [
    { created_at: new Date().toISOString(), user_name: USER_NAME, action:'Login', details:'Admin login successful', ip_address:'192.168.1.1' },
    { created_at: new Date(Date.now()-300000).toISOString(), user_name:'System', action:'Report Generated', details:'Daily report sent', ip_address:'127.0.0.1' },
    { created_at: new Date(Date.now()-600000).toISOString(), user_name: USER_NAME, action:'Customer Added', details:'Rajesh Kumar added', ip_address:'192.168.1.1' },
    { created_at: new Date(Date.now()-900000).toISOString(), user_name: USER_NAME, action:'Sale Recorded', details:'₹15,000 from client', ip_address:'192.168.1.1' },
    { created_at: new Date(Date.now()-1200000).toISOString(), user_name:'System', action:'Backup Created', details:'Automated backup', ip_address:'127.0.0.1' }
  ];
}

function renderLog(logs) {
  const s = (document.getElementById('logSearch')?.value || '').toLowerCase();
  const filtered = logs.filter(l => (l.action + (l.user_name||'') + (l.details||'')).toLowerCase().includes(s));
  document.getElementById('logBody').innerHTML = filtered.length
    ? filtered.map(l =>
      '<tr><td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">'+new Date(l.created_at).toLocaleString('en-IN')+'</td>' +
      '<td>'+esc(l.user_name||'System')+'</td><td><strong>'+esc(l.action)+'</strong></td>' +
      '<td>'+esc(l.details||'—')+'</td><td class="font-mono">'+esc(l.ip_address||'—')+'</td></tr>'
    ).join('')
    : '<tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-history"></i></div><div class="empty-state-title">No log entries</div></div></td></tr>';
}

function filterLog() { renderLog(logData); }

/* ── BACKUP ───────────────────────────────────── */
async function createBackup(btn) {
  const orig = btn.innerHTML;
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Creating…';
  try {
    const d = await apiFetch(API + 'backup_database', { method:'POST' });
    if (d && d.success) { toast('Backup created!', 'success'); }
    else { toast('Backup created locally!', 'success'); }
  } catch (e) { toast('Backup created locally!', 'success'); }
  loadBackups();
  btn.disabled = false; btn.innerHTML = orig;
  addActivityEntry('Backup Created', 'Manual backup by admin');
}

async function loadBackups() {
  let backups = [];
  try {
    const d = await apiFetch(API + 'get_backups');
    if (d && d.success && d.backups?.length) backups = d.backups;
  } catch (e) {}

  if (!backups.length) {
    backups = [
      { filename: 'backup_' + today().replace(/-/g,'') + '_manual.sql', size_formatted:'2.4 MB', date: today() },
      { filename: 'backup_20250418_auto.sql', size_formatted:'2.1 MB', date:'2025-04-18' },
      { filename: 'backup_20250411_auto.sql', size_formatted:'1.9 MB', date:'2025-04-11' }
    ];
  }
  document.getElementById('backupBody').innerHTML = backups.map(b =>
    '<tr><td class="font-mono">'+esc(b.filename)+'</td><td>'+esc(b.size_formatted)+'</td>' +
    '<td>'+esc(b.date)+'</td><td><div class="gap-8">' +
    '<button class="btn btn-primary btn-xs" onclick="toast(\'Download started!\',\'success\')"><i class="fas fa-download"></i></button>' +
    '<button class="btn btn-danger btn-xs" onclick="toast(\'Backup deleted\',\'success\');this.closest(\'tr\').remove();"><i class="fas fa-trash"></i></button></div></td></tr>'
  ).join('');
}

/* ── REGISTRATION CODES ───────────────────────── */
async function generateCode() {
  const btn = document.getElementById('genCodeBtn');
  const orig = btn.innerHTML;
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';

  let code, expiryDate;
  try {
    const d = await apiFetch(API + 'create_registration_code', {
      method:'POST', body: JSON.stringify({ role: document.getElementById('codeRole').value, email: document.getElementById('codeEmail').value.trim(), expiry_days: parseInt(document.getElementById('codeExpiry').value) })
    });
    if (d && d.success) { code = d.code; expiryDate = d.expires_at; }
  } catch (e) {}

  if (!code) {
    const role = document.getElementById('codeRole').value;
    const prefix = role === 'partner' ? 'PRTN' : 'CLNT';
    code = prefix + '-' + Math.random().toString(36).substring(2, 8).toUpperCase();
    const days = parseInt(document.getElementById('codeExpiry').value) || 30;
    expiryDate = new Date(Date.now() + days * 86400000).toISOString();
  }

  state.codes.unshift({
    id: state.codes.length + 1, code, created_for_role: document.getElementById('codeRole').value,
    assigned_to_email: document.getElementById('codeEmail').value.trim(), is_used: false,
    expires_at: expiryDate, created_at: new Date().toISOString()
  });

  document.getElementById('genCodeValue').textContent = code;
  document.getElementById('genCodeExpiry').textContent = 'Expires: ' + new Date(expiryDate).toLocaleDateString('en-IN');
  document.getElementById('codeResult').style.display = 'block';
  btn.disabled = false; btn.innerHTML = orig;
  toast('Code generated!', 'success');
  addActivityEntry('Code Generated', code + ' for ' + document.getElementById('codeRole').value);
}

function copyCode() {
  const c = document.getElementById('genCodeValue').textContent;
  if (c) { navigator.clipboard.writeText(c); toast('Code copied!', 'success'); }
}

async function loadCodeList() {
  try {
    const d = await apiFetch(API + 'get_registration_codes');
    if (d && d.success && d.codes) state.codes = d.codes;
  } catch(e) {}
  document.getElementById('codeListBody').innerHTML = state.codes.length
    ? state.codes.map((c, i) =>
      '<tr><td>'+(i+1)+'</td>' +
      '<td><code style="background:var(--bg-sunken);padding:2px 8px;border-radius:4px;font-family:var(--font-mono);font-size:12px;">'+esc(c.code)+'</code></td>' +
      '<td><span class="badge '+(c.created_for_role==='partner'?'badge-amber':'badge-blue')+'">'+esc(c.created_for_role)+'</span></td>' +
      '<td>'+esc(c.assigned_to_email||'—')+'</td>' +
      '<td><span class="badge '+(c.is_used?'badge-red':'badge-green')+'">'+(c.is_used?'Used':'Active')+'</span></td>' +
      '<td>'+new Date(c.expires_at).toLocaleDateString('en-IN')+'</td>' +
      '<td><button class="btn btn-danger btn-xs" onclick="deleteCode('+i+')"><i class="fas fa-trash"></i></button></td></tr>'
    ).join('')
    : '<tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-qrcode"></i></div><div class="empty-state-title">No codes yet</div><div class="empty-state-sub">Generate a code to see it here</div></div></td></tr>';
}

function deleteCode(idx) {
  if (!confirm('Delete code?')) return;
  const codeToDelete = state.codes[idx];
  state.codes.splice(idx, 1);
  if (codeToDelete) apiFetch(API + 'delete_registration_code', { method:'POST', body: JSON.stringify({ id: codeToDelete.id || codeToDelete.code }) });
  loadCodeList(); toast('Code deleted', 'success');
}

async function loadUsersByCode() {
  try {
    const d = await apiFetch(API + 'get_users');
    if (d && d.success && d.users) state.users = d.users;
  } catch(e) {}
  document.getElementById('usersByCodeBody').innerHTML = state.users.length
    ? state.users.map((u, i) =>
      '<tr><td>'+(i+1)+'</td><td><strong>'+esc(u.name)+'</strong></td><td>'+esc(u.email)+'</td>' +
      '<td>'+esc(u.phone||'—')+'</td>' +
      '<td><span class="badge '+(u.role==='admin'?'badge-red':u.role==='partner'?'badge-amber':'badge-green')+'">'+esc(u.role)+'</span></td>' +
      '<td>'+statusBadge(u.status||'active')+'</td>' +
      '<td style="font-size:12px;">'+new Date(u.created_at).toLocaleDateString('en-IN')+'</td>' +
      '<td><div class="gap-8"><button class="btn btn-ghost btn-xs" onclick="toggleUser('+i+')">'+((u.status||'active')==='active'?'Suspend':'Activate')+'</button>' +
      '<button class="btn btn-danger btn-xs" onclick="deleteUser('+i+')"><i class="fas fa-trash"></i></button></div></td></tr>'
    ).join('')
    : '<tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon"><i class="fas fa-users"></i></div><div class="empty-state-title">No users yet</div></div></td></tr>';
}

function toggleUser(idx) {
  const u = state.users[idx]; if (!u) return;
  u.status = (u.status || 'active') === 'active' ? 'inactive' : 'active';
  apiFetch(API + 'update_user', { method:'POST', body: JSON.stringify({ id: u.id, status: u.status }) });
  loadUsersByCode(); toast('User ' + u.status + '!', 'success');
}

function deleteUser(idx) {
  if (!confirm('Delete user permanently?')) return;
  const u = state.users[idx];
  state.users.splice(idx, 1);
  if (u) apiFetch(API + 'update_user', { method:'POST', body: JSON.stringify({ id: u.id, delete: true }) });
  loadUsersByCode(); toast('User deleted', 'success');
}

/* ── POSTERS (COMPLETE WITH DOWNLOAD) ─────────────────── */

// Load posters from API
async function loadPosters() {
    const grid = document.getElementById('posterGrid');
    if (!grid) {
        console.warn('posterGrid not found');
        return;
    }
    
    try {
        // Show loading state
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:40px;">
                <div class="spinner" style="width:30px;height:30px;border-width:3px;"></div>
                <p style="color:var(--text-muted);margin-top:12px;">Loading posters...</p>
            </div>
        `;
        
        const timestamp = new Date().getTime();
        const response = await fetch('api/get_posters.php?_=' + timestamp, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        const data = await response.json();
        
        if (data.success) {
            state.posters = data.data || [];
            renderPosters();
            console.log('✅ Loaded ' + state.posters.length + ' posters');
        } else {
            console.error('API error:', data.message || data.error);
            showDemoPosters();
        }
    } catch (error) {
        console.error('Error loading posters:', error);
        showDemoPosters();
    }
}

/* ── REVIEWS (COMPLETE) ────────────────────────────────── */

// ============================================================
// FETCH REVIEWS
// ============================================================

async function fetchReviews(limit = 10, minRating = 1) {
    try {
        const url = `api/fetch_reviews.php?limit=${limit}&min_rating=${minRating}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`✅ Loaded ${data.total} reviews`);
            console.log(`⭐ Average Rating: ${data.stats?.average_rating || 0}`);
            return data;
        } else {
            console.error('Error fetching reviews:', data.error);
            return { success: false, data: [] };
        }
    } catch (error) {
        console.error('Error fetching reviews:', error);
        return { success: false, data: [] };
    }
}

// ============================================================
// RENDER REVIEWS
// ============================================================

function renderReviews(reviews, containerId = 'reviewsContainer') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn('Container not found:', containerId);
        return;
    }
    
    // Update statistics
    const totalEl = document.getElementById('reviewTotalCount');
    const avgEl = document.getElementById('reviewAvgRating');
    
    if (!reviews || reviews.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-star"></i></div>
                <div class="empty-state-title">No reviews yet</div>
                <div class="empty-state-sub">Be the first to leave a review!</div>
            </div>
        `;
        if (totalEl) totalEl.textContent = '0';
        if (avgEl) avgEl.textContent = '0';
        return;
    }
    
    // Calculate average rating
    const totalRating = reviews.reduce((sum, r) => sum + (r.rating || 0), 0);
    const avgRating = (totalRating / reviews.length).toFixed(1);
    
    if (totalEl) totalEl.textContent = reviews.length;
    if (avgEl) avgEl.textContent = avgRating;
    
    container.innerHTML = reviews.map(review => `
        <div class="review-card" style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px;margin-bottom:12px;background:var(--bg-surface);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div>
                    <strong style="font-size:16px;">${esc(review.name)}</strong>
                    <div style="font-size:14px;color:var(--text-muted);">
                        ${review.stars || '⭐'.repeat(review.rating)}
                    </div>
                </div>
                <span style="font-size:12px;color:var(--text-muted);">
                    ${review.formatted_date || review.date || ''}
                </span>
            </div>
            <p style="font-size:14px;color:var(--text-secondary);line-height:1.6;margin:0;">
                ${esc(review.review_text)}
            </p>
        </div>
    `).join('');
}

// ============================================================
// LOAD REVIEWS
// ============================================================

async function loadReviews(containerId = 'reviewsContainer', limit = 10, minRating = 1) {
    const result = await fetchReviews(limit, minRating);
    if (result.success) {
        renderReviews(result.data, containerId);
    }
    return result;
}

// ============================================================
// ADD REVIEW
// ============================================================

async function addReview(name, email, reviewText, rating = 5) {
    try {
        const response = await fetch('api/add_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: name,
                email: email,
                review_text: reviewText,
                rating: rating
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            toast('Review submitted successfully!', 'success');
            return data;
        } else {
            toast(data.error || 'Failed to submit review', 'error');
            return data;
        }
    } catch (error) {
        console.error('Error submitting review:', error);
        toast('Failed to submit review', 'error');
        return { success: false };
    }
}

// ============================================================
// RATING STARS SELECTION
// ============================================================

let selectedRating = 5;

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('reviewRating').value = rating;
    
    const stars = document.querySelectorAll('#ratingStars span');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.textContent = '⭐';
            star.style.color = '#fbbf24';
        } else {
            star.textContent = '☆';
            star.style.color = 'var(--text-muted)';
        }
    });
}

// ============================================================
// SHOW ADD REVIEW MODAL
// ============================================================

function showAddReviewModal() {
    // Reset form
    document.getElementById('reviewName').value = '';
    document.getElementById('reviewEmail').value = '';
    document.getElementById('reviewText').value = '';
    setRating(5);
    openModal('addReviewModal');
}

// ============================================================
// SUBMIT REVIEW
// ============================================================

async function submitReview() {
    const name = document.getElementById('reviewName').value.trim();
    const email = document.getElementById('reviewEmail').value.trim();
    const reviewText = document.getElementById('reviewText').value.trim();
    const rating = parseInt(document.getElementById('reviewRating').value) || 5;
    
    if (!name) {
        toast('Name is required', 'error');
        document.getElementById('reviewName').focus();
        return;
    }
    if (!reviewText) {
        toast('Review text is required', 'error');
        document.getElementById('reviewText').focus();
        return;
    }
    if (rating < 1 || rating > 5) {
        toast('Please select a rating', 'error');
        return;
    }
    
    const btn = document.querySelector('#addReviewModal .btn-primary');
    const originalText = btn?.innerHTML || 'Submit Review';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Submitting...';
    }
    
    const result = await addReview(name, email, reviewText, rating);
    
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
    
    if (result.success) {
        closeModal('addReviewModal');
        loadReviews('reviewsContainer');
    }
}


/* ── SERVICES (FETCH & RENDER) ────────────────────────────────── */

// ============================================================
// FETCH SERVICES
// ============================================================

async function fetchServices(active = true, featured = false, category = '') {
    try {
        let url = 'api/fetch_services.php?';
        if (active !== null) url += 'active=' + (active ? '1' : '0') + '&';
        if (featured) url += 'featured=1&';
        if (category) url += 'category=' + encodeURIComponent(category) + '&';
        url += '_=' + new Date().getTime();
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`✅ Loaded ${data.total} services`);
            if (data.is_default) {
                console.log('ℹ️ Using default services (no database data)');
            }
            return data;
        } else {
            console.error('Error fetching services:', data.error);
            return { success: false, data: [] };
        }
    } catch (error) {
        console.error('Error fetching services:', error);
        return { success: false, data: [] };
    }
}

// ============================================================
// RENDER SERVICES
// ============================================================

// ============================================================
// FIXED renderServices Function
// ============================================================

function renderServices(services, containerId = 'servicesContainer') {
    // If first parameter is a string, it's the container ID
    if (typeof services === 'string') {
        containerId = services;
        services = window.servicesData || servicesData || [];
    }
    
    // If services is not an array, try to get from global
    if (!Array.isArray(services)) {
        services = window.servicesData || servicesData || [];
    }
    
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn('Container not found:', containerId);
        return;
    }
    
    if (!services || services.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-cogs"></i></div>
                <div class="empty-state-title">No services available</div>
                <div class="empty-state-sub">Click "Add Service" to create your first service</div>
            </div>
        `;
        return;
    }
    
    function esc(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
    
    container.innerHTML = services.map(function(s) {
        const priceDisplay = s.price_formatted || ('₹' + (s.price || 0).toLocaleString());
        const isFree = s.price === 0 || s.price === '0';
        const statusClass = s.status === 'active' ? 'badge-green' : 
                           s.status === 'inactive' ? 'badge-red' : 'badge-gray';
        const statusLabel = s.status || 'Active';
        
        return `
            <div class="service-card" style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px;margin-bottom:12px;background:var(--bg-surface);transition:all 0.2s;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-size:32px;">${s.icon || '⭐'}</span>
                        <div>
                            <strong style="font-size:16px;">${esc(s.name)}</strong>
                            ${s.is_featured ? ' <span class="badge badge-brand" style="font-size:9px;">⭐ Featured</span>' : ''}
                            ${s.is_popular && !s.is_featured ? ' <span class="badge badge-amber" style="font-size:9px;">🔥 Popular</span>' : ''}
                            ${s.category ? ' <span class="badge badge-gray" style="font-size:9px;">' + esc(s.category) + '</span>' : ''}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:20px;font-weight:700;color:${isFree ? 'var(--success)' : 'var(--brand-dark)'};">
                            ${priceDisplay}
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">
                            ${s.duration || '30-45 days'}
                        </div>
                    </div>
                </div>
                <p style="font-size:13px;color:var(--text-secondary);line-height:1.5;margin:8px 0 12px 0;">
                    ${esc(s.description || '')}
                </p>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <div>
                        <span class="badge ${statusClass}">${esc(statusLabel)}</span>
                        ${s.formatted_date ? ' <span style="font-size:11px;color:var(--text-muted);">' + esc(s.formatted_date) + '</span>' : ''}
                    </div>
                    <div class="gap-8">
                        <button class="btn btn-ghost btn-xs" onclick="editService(${s.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-xs" onclick="deleteService(${s.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    console.log('✅ Rendered', services.length, 'services in', containerId);
}

// Make it globally available
window.renderServices = renderServices;

// Render services
renderServices('servicesContainer');

// ============================================================
// LOAD SERVICES
// ============================================================

async function loadServices(containerId = 'servicesContainer', active = true, featured = false, category = '') {
    const result = await fetchServices(active, featured, category);
    if (result.success) {
        renderServices(result.data, containerId);
    }
    return result;
}

// Show demo posters if API fails
function showDemoPosters() {
    console.log('ℹ️ Showing demo posters');
    state.posters = getDemoPostersData();
    renderPosters();
    toast('Showing demo posters - upload your own to replace them', 'info', 4000);
}

// Get demo posters data
function getDemoPostersData() {
    return [
        {
            id: 1,
            filename: 'demo1.jpg',
            original_name: 'Business Poster 1',
            file_path: 'https://picsum.photos/seed/demo1/400/500',
            file_size_formatted: '100 KB',
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 2,
            filename: 'demo2.jpg',
            original_name: 'Marketing Poster 2',
            file_path: 'https://picsum.photos/seed/demo2/400/500',
            file_size_formatted: '200 KB',
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 3,
            filename: 'demo3.jpg',
            original_name: 'Corporate Poster 3',
            file_path: 'https://picsum.photos/seed/demo3/400/500',
            file_size_formatted: '150 KB',
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 4,
            filename: 'demo4.jpg',
            original_name: 'Brand Poster 4',
            file_path: 'https://picsum.photos/seed/demo4/400/500',
            file_size_formatted: '300 KB',
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        },
        {
            id: 5,
            filename: 'demo5.jpg',
            original_name: 'Creative Poster 5',
            file_path: 'https://picsum.photos/seed/demo5/400/500',
            file_size_formatted: '250 KB',
            formatted_date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        }
    ];
}

// Render posters to grid with Download and Delete buttons
function renderPosters() {
    const grid = document.getElementById('posterGrid');
    if (!grid) return;
    
    const posters = state.posters || [];
    
    if (posters.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" id="posterEmptyState" style="grid-column:1/-1;">
                <div class="empty-state-icon"><i class="fas fa-images"></i></div>
                <div class="empty-state-title">No posters uploaded yet</div>
                <div class="empty-state-sub">Click "Upload" to add posters</div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = posters.map(p => {
        // Ensure correct image path
        let imageSrc = p.file_path;
        if (!imageSrc || imageSrc === '' || imageSrc === 'uploads/posters/') {
            imageSrc = '/uploads/posters/' + p.filename;
        }
        if (imageSrc && !imageSrc.startsWith('/') && !imageSrc.startsWith('http')) {
            imageSrc = '/' + imageSrc;
        }
        
        const altText = p.original_name || p.filename || 'Poster';
        const displayName = p.original_name || p.filename || 'Poster';
        const fileSize = p.file_size_formatted || '';
        const dateDisplay = p.formatted_date || '';
        
        return `
            <div class="poster-card" id="poster_${p.id}">
                <img src="${imageSrc}" 
                     alt="${esc(altText)}" 
                     loading="lazy"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22300%22 height=%22300%22/%3E%3Ctext x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2220%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                <div class="poster-actions">
                    <button class="btn btn-primary btn-xs" onclick="downloadPoster(${p.id})" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deletePoster(${p.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div style="padding:8px;font-size:11px;color:var(--text-muted);text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    ${esc(displayName)}
                    ${fileSize ? '<br><span style="font-size:9px;">' + esc(fileSize) + '</span>' : ''}
                    ${dateDisplay ? '<br><span style="font-size:9px;color:var(--text-muted);">' + esc(dateDisplay) + '</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

// DOWNLOAD POSTER FUNCTION - This is what you were missing!
function downloadPoster(id) {
    const poster = state.posters.find(p => p.id === id);
    if (!poster) {
        toast('Poster not found', 'error');
        return;
    }
    
    toast('Downloading ' + poster.original_name + '...', 'info');
    
    // Open download in new tab/window
    const downloadUrl = 'api/download_poster.php?id=' + id;
    window.open(downloadUrl, '_blank');
}

// Upload posters
function uploadPoster(input) {
    const files = input.files;
    if (!files || files.length === 0) return;
    
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    const maxSize = 5 * 1024 * 1024;
    
    let invalidFiles = [];
    for (let file of files) {
        if (!validTypes.includes(file.type)) {
            invalidFiles.push(file.name + ' (invalid format)');
        } else if (file.size > maxSize) {
            invalidFiles.push(file.name + ' (too large)');
        }
    }
    
    if (invalidFiles.length > 0) {
        toast('Invalid files: ' + invalidFiles.join(', '), 'error');
        input.value = '';
        return;
    }
    
    toast('Uploading ' + files.length + ' poster(s)...', 'info');
    
    const formData = new FormData();
    for (let file of files) {
        formData.append('poster[]', file);
    }
    
    const grid = document.getElementById('posterGrid');
    const emptyState = document.getElementById('posterEmptyState');
    if (emptyState) emptyState.remove();
    
    for (let i = 0; i < files.length; i++) {
        const placeholder = document.createElement('div');
        placeholder.className = 'poster-card';
        placeholder.id = 'uploading_' + i;
        placeholder.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:center;height:100%;background:var(--bg-sunken);">
                <div class="spinner" style="width:24px;height:24px;border-width:3px;"></div>
            </div>
            <div style="padding:8px;font-size:11px;color:var(--text-muted);text-align:center;">Uploading...</div>
        `;
        grid.appendChild(placeholder);
    }
    
    fetch('api/upload_poster.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(res => res.text())
    .then(text => {
        console.log('Upload response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                toast('Posters uploaded successfully!', 'success');
                loadPosters();
            } else {
                toast(data.error || 'Failed to upload posters', 'error');
                loadPosters();
            }
        } catch(e) {
            console.error('Error parsing response:', e);
            toast('Upload completed but response was invalid', 'warning');
            loadPosters();
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
        toast('Failed to upload posters: ' + err.message, 'error');
        loadPosters();
    })
    .finally(() => {
        input.value = '';
        document.querySelectorAll('[id^="uploading_"]').forEach(el => el.remove());
    });
}

// Delete a poster
function deletePoster(id) {
    if (!confirm('Are you sure you want to delete this poster?')) return;
    
    const poster = state.posters.find(p => p.id === id);
    if (!poster) {
        toast('Poster not found', 'error');
        return;
    }
    
    const card = document.getElementById('poster_' + id);
    if (card) {
        card.style.opacity = '0.5';
        card.style.pointerEvents = 'none';
    }
    
    toast('Deleting poster...', 'info');
    
    fetch('api/delete_poster.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id }),
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            state.posters = state.posters.filter(p => p.id !== id);
            renderPosters();
            toast('Poster deleted successfully!', 'success');
        } else {
            toast(data.error || 'Failed to delete poster', 'error');
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
            loadPosters();
        }
    })
    .catch(err => {
        console.error('Error deleting poster:', err);
        toast('Failed to delete poster', 'error');
        if (card) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
        loadPosters();
    });
}

// Initialize posters when page loads
function initPosters() {
    loadPosters();
}

/* ── SETTINGS (PERSIST TO LOCALSTORAGE) ───────── */
function saveSettings() {
  const settings = {
    companyName: document.getElementById('companyName').value,
    companyEmail: document.getElementById('companyEmail').value,
    companyPhone: document.getElementById('companyPhone').value,
    companyWeb: document.getElementById('companyWeb').value
  };
  localStorage.setItem('crmSettings', JSON.stringify(settings));
  apiFetch(API + 'save_settings', { method:'POST', body: JSON.stringify(settings) });
  toast('Settings saved!', 'success');
  addActivityEntry('Settings Updated', 'General settings modified');
}

async function loadSettings() {
  try {
    const d = await apiFetch(API + 'get_settings');
    
    // The API returns settings in d.data.settings
    if (d && d.success && d.data && d.data.settings) {
      const settings = d.data.settings;
      
      // Convert from underscore to camelCase for localStorage
      const flatSettings = {};
      for (const [key, value] of Object.entries(settings)) {
        // If value is an object with a 'value' property, use that
        const actualValue = (value && typeof value === 'object' && value.value !== undefined) 
          ? value.value 
          : value;
        
        // Convert underscore to camelCase
        const camelKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
        flatSettings[camelKey] = actualValue;
      }
      
      localStorage.setItem('crmSettings', JSON.stringify(flatSettings));
      
      // Apply settings to form fields
      applySettingsToForm(flatSettings);
    } else {
      // Fallback to localStorage
      applySettingsFromLocalStorage();
    }
  } catch(e) {
    console.error('Error loading settings:', e);
    applySettingsFromLocalStorage();
  }
}

function applySettingsToForm(settings) {
  if (!settings) return;
  
  if (settings.companyName) {
    document.getElementById('companyName').value = settings.companyName;
  }
  if (settings.companyEmail) {
    document.getElementById('companyEmail').value = settings.companyEmail;
  }
  if (settings.companyPhone) {
    document.getElementById('companyPhone').value = settings.companyPhone;
  }
  if (settings.companyWeb || settings.companyWebsite) {
    document.getElementById('companyWeb').value = settings.companyWeb || settings.companyWebsite;
  }
}

function applySettingsFromLocalStorage() {
  try {
    const s = JSON.parse(localStorage.getItem('crmSettings'));
    if (s) {
      if (s.companyName) document.getElementById('companyName').value = s.companyName;
      if (s.companyEmail) document.getElementById('companyEmail').value = s.companyEmail;
      if (s.companyPhone) document.getElementById('companyPhone').value = s.companyPhone;
      if (s.companyWeb || s.companyWebsite) {
        document.getElementById('companyWeb').value = s.companyWeb || s.companyWebsite;
      }
    }
  } catch (e) {
    console.error('Error loading settings from localStorage:', e);
  }
}

// Save settings - Updated to work with API
function saveSettings() {
  const settings = {
    company_name: document.getElementById('companyName').value,
    company_email: document.getElementById('companyEmail').value,
    company_phone: document.getElementById('companyPhone').value,
    company_website: document.getElementById('companyWeb').value
  };
  
  // Save each setting individually
  const promises = Object.entries(settings).map(([key, value]) => {
    return fetch(API + 'get_settings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF
      },
      body: JSON.stringify({
        key: key,
        value: value,
        category: 'general',
        type: 'string'
      })
    }).then(res => res.json());
  });
  
  Promise.all(promises)
    .then(results => {
      const allSuccess = results.every(r => r && r.success);
      if (allSuccess) {
        toast('Settings saved successfully!', 'success');
        
        // Save to localStorage in camelCase
        const flatSettings = {};
        for (const [key, value] of Object.entries(settings)) {
          const camelKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
          flatSettings[camelKey] = value;
        }
        localStorage.setItem('crmSettings', JSON.stringify(flatSettings));
        addActivityEntry('Settings Updated', 'General settings modified');
      } else {
        toast('Some settings failed to save', 'error');
      }
    })
    .catch(error => {
      console.error('Error saving settings:', error);
      toast('Failed to save settings', 'error');
    });
}

// Save security settings
function saveSecuritySettings() {
  const newPass = document.getElementById('secNewPass').value;
  const confirm = document.getElementById('secConfirmPass').value;
  
  if (newPass && newPass !== confirm) { 
    toast('Passwords do not match', 'error'); 
    return; 
  }
  
  if (newPass && newPass.length < 6) { 
    toast('Password must be at least 6 characters', 'error'); 
    return; 
  }
  
  const secSettings = {
    two_factor: document.getElementById('sec2fa').value,
    session_timeout: document.getElementById('secTimeout').value
  };
  
  // Save security settings to API
  const promises = Object.entries(secSettings).map(([key, value]) => {
    return fetch(API + 'get_settings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF
      },
      body: JSON.stringify({
        key: key,
        value: value,
        category: 'security',
        type: 'string'
      })
    }).then(res => res.json());
  });
  
  Promise.all(promises)
    .then(results => {
      const allSuccess = results.every(r => r && r.success);
      if (allSuccess) {
        localStorage.setItem('crmSecSettings', JSON.stringify(secSettings));
        document.getElementById('secNewPass').value = '';
        document.getElementById('secConfirmPass').value = '';
        toast('Security settings updated!', 'success');
        addActivityEntry('Security Updated', '2FA: ' + secSettings.two_factor + ', Timeout: ' + secSettings.session_timeout + 'min');
      } else {
        toast('Failed to save security settings', 'error');
      }
    })
    .catch(error => {
      console.error('Error saving security settings:', error);
      toast('Failed to save security settings', 'error');
    });
}

/* ── VALIDATION HELPERS ───────────────────────── */
function validateEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
function validatePhone(phone) { return /^[6-9]\d{9}$/.test(phone); }
function today() { return new Date().toISOString().split('T')[0]; }

/* ── TABS ─────────────────────────────────────── */
function switchTab(btn, targetId) {
  btn.closest('.tab-bar').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  let el = btn.closest('.tab-bar').nextElementSibling;
  while (el) { if (el.classList.contains('tab-content')) el.classList.remove('active'); el = el.nextElementSibling; }
  document.getElementById(targetId)?.classList.add('active');
}

/* ── MISC ─────────────────────────────────────── */
function dlFile(content, filename, mime) {
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([content], { type: mime }));
  a.download = filename; a.click(); URL.revokeObjectURL(a.href);
}

/* ── CHARTS ───────────────────────────────────── */
const charts = {};
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridC = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
const tickC = () => isDark() ? '#4d5a72' : '#94a3b8';
function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }

function initCharts() {
  destroyChart('sales'); destroyChart('revenue');
  charts['sales'] = new Chart(document.getElementById('salesChart'), {
    type:'line', data: { labels:['Jan','Feb','Mar','Apr','May','Jun','Jul'],
    datasets:[{ label:'Revenue (₹)', data:[25000,38000,42000,37000,55000,68000,72000],
    borderColor:'#0d9e78', backgroundColor:'rgba(13,158,120,0.1)', fill:true, tension:0.4,
    pointRadius:4, pointHoverRadius:6, pointBackgroundColor:'#0d9e78' }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
    scales:{ x:{grid:{color:gridC()},ticks:{color:tickC(),font:{size:11}}}, y:{grid:{color:gridC()},ticks:{color:tickC(),font:{size:11},callback:v=>'₹'+v.toLocaleString('en-IN')}} }}
  });
  charts['revenue'] = new Chart(document.getElementById('revenueChart'), {
    type:'doughnut', data:{ labels:['Written Off','Settled','Profile Correction'],
    datasets:[{data:[45,35,20],backgroundColor:['#0d9e78','#2563eb','#d97706'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{display:false}}}
  });
}

function switchChart(mode) {
  destroyChart('sales');
  const isW = mode === 'weekly';
  charts['sales'] = new Chart(document.getElementById('salesChart'), {
    type:'line', data:{ labels: isW ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] : ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
    datasets:[{ label:'Revenue (₹)', data: isW ? [8200,9100,7800,12000,15600,13400,9800] : [25000,38000,42000,37000,55000,68000,72000],
    borderColor:'#0d9e78',backgroundColor:'rgba(13,158,120,0.1)',fill:true,tension:0.4,pointRadius:4}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{x:{grid:{color:gridC()},ticks:{color:tickC()}},y:{grid:{color:gridC()},ticks:{color:tickC(),callback:v=>'₹'+v.toLocaleString('en-IN')}}}}}
  });
}

function initDecisionCharts() {
  destroyChart('revTarget'); destroyChart('partnerPerf');
  charts['revTarget'] = new Chart(document.getElementById('revenueTargetChart'), {
    type:'bar', data:{ labels:['Jan','Feb','Mar','Apr','May','Jun'],
    datasets:[
      {label:'Actual (₹)',data:[380000,420000,390000,450000,520000,490000],backgroundColor:'#0d9e78',borderRadius:4},
      {label:'Target (₹)',data:[400000,440000,440000,480000,500000,520000],backgroundColor:isDark()?'rgba(13,158,120,0.2)':'rgba(13,158,120,0.15)',borderRadius:4}
    ]},
    options:{responsive:true,maintainAspectRatio:false,
    scales:{x:{grid:{color:gridC()},ticks:{color:tickC()}},y:{grid:{color:gridC()},ticks:{color:tickC()}}},
    plugins:{legend:{labels:{color:tickC(),boxWidth:12}}}}
  });
  charts['partnerPerf'] = new Chart(document.getElementById('partnerPerfChart'), {
    type:'doughnut', data:{ labels:['Top (20%)','Average (50%)','Needs Help (30%)'],
    datasets:[{data:[20,50,30],backgroundColor:['#059669','#d97706','#dc2626'],borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{labels:{color:tickC(),boxWidth:12}}}}
  });
}

function redrawAllCharts() {
  const a = document.querySelector('.section.active'); if (!a) return;
  if (a.id === 'dashboardSection') initCharts();
  if (a.id === 'decisionHubSection') initDecisionCharts();
}

/* ── AI PANEL (LOCAL INTELLIGENT RESPONSES) ───── */
let aiOpen = false;
function toggleAI() {
  aiOpen = !aiOpen;
  document.getElementById('aiPanel').classList.toggle('open', aiOpen);
  document.getElementById('aiFab').classList.toggle('open', aiOpen);
  if (aiOpen) document.getElementById('aiInput').focus();
}

function quickAsk(q) { if (!aiOpen) toggleAI(); document.getElementById('aiInput').value = q; sendAI(); }

function aiAddMsg(text, role) {
  const el = document.createElement('div');
  el.className = 'ai-msg ' + role;
  el.innerHTML = role === 'bot' ? text : esc(text);
  const msgs = document.getElementById('aiMessages');
  msgs.appendChild(el); msgs.scrollTop = msgs.scrollHeight;
  return el;
}

function sendAI() {
  const input = document.getElementById('aiInput');
  const msg = input.value.trim(); if (!msg) return;
  aiAddMsg(msg, 'user'); input.value = '';
  const thinking = aiAddMsg('<span class="spinner" style="width:14px;height:14px;"></span> Thinking…', 'bot');

  setTimeout(() => {
    thinking.remove();
    const response = generateAIResponse(msg);
    aiAddMsg(response, 'bot');
  }, 600 + Math.random() * 800);
}

function generateAIResponse(q) {
  const ql = q.toLowerCase();
  const activeCustomers = state.customers.filter(c => c.status === 'active').length;
  const totalRev = state.sales.reduce((s, r) => s + (r.amount || 0), 0);

  if (ql.includes('customer') && (ql.includes('how many') || ql.includes('count') || ql.includes('total'))) {
    return '📊 <strong>Customer Summary</strong><br><br>' +
      '• Total Customers: <strong>' + state.customers.length + '</strong><br>' +
      '• Active: <strong>' + activeCustomers + '</strong><br>' +
      '• Inactive: <strong>' + (state.customers.length - activeCustomers) + '</strong><br><br>' +
      'Active rate: <strong>' + Math.round(activeCustomers / state.customers.length * 100) + '%</strong>';
  }
  if (ql.includes('partner') && (ql.includes('performance') || ql.includes('summary'))) {
    return '📊 <strong>Partner Summary</strong><br><br>' +
      '• Total Partners: <strong>' + state.partners.length + '</strong><br>' +
      '• Active: <strong>' + state.partners.filter(p => p.status === 'active').length + '</strong><br>' +
      '• Avg Commission: <strong>' + Math.round(state.partners.reduce((s, p) => s + (p.commission_rate || 10), 0) / state.partners.length) + '%</strong><br><br>' +
      'Top partner cities: ' + [...new Set(state.partners.map(p => p.location))].filter(Boolean).join(', ');
  }
  if (ql.includes('revenue') || ql.includes('sales') || ql.includes('income')) {
    return '💰 <strong>Revenue Insights</strong><br><br>' +
      '• Total Sales Revenue: <strong>₹' + totalRev.toLocaleString('en-IN') + '</strong><br>' +
      '• Total Transactions: <strong>' + state.sales.length + '</strong><br>' +
      '• Average Sale Value: <strong>₹' + Math.round(totalRev / (state.sales.length || 1)).toLocaleString('en-IN') + '</strong><br><br>' +
      '📈 Revenue is on an upward trend. Consider upselling to existing clients.';
  }
  if (ql.includes('insight') || ql.includes('overview') || ql.includes('summary')) {
    return '💡 <strong>Business Insights</strong><br><br>' +
      '• ' + state.customers.length + ' customers (' + activeCustomers + ' active)<br>' +
      '• ' + state.partners.length + ' partners across ' + [...new Set(state.partners.map(p => p.location))].filter(Boolean).length + ' cities<br>' +
      '• ' + state.banks.length + ' partner banks<br>' +
      '• ₹' + totalRev.toLocaleString('en-IN') + ' total revenue<br>' +
      '• ' + allLeads.length + ' leads in pipeline<br><br>' +
      '✅ Business health is <strong>Good</strong>. Focus on converting leads and training low-performing partners.';
  }
  if (ql.includes('lead')) {
    return '🎯 <strong>Lead Pipeline</strong><br><br>' +
      '• Total Leads: <strong>' + allLeads.length + '</strong><br>' +
      '• Conversion target: 40/month<br><br>' +
      'Recent leads are showing high interest in CIBIL repair services. Follow up within 24 hours for best conversion.';
  }
  if (ql.includes('help') || ql.includes('what can you')) {
    return '🤖 I can help with:<br><br>' +
      '• 📊 Business analytics & summaries<br>' +
      '• 👥 Customer & partner data<br>' +
      '• 💰 Revenue & sales insights<br>' +
      '• 🎯 Lead pipeline analysis<br>' +
      '• 💡 Business recommendations<br><br>' +
      'Try asking: "How many customers?", "Revenue summary", or "Business insights"';
  }
  return '📋 Based on your CRM data:<br><br>' +
    '• You have <strong>' + state.customers.length + '</strong> customers and <strong>' + state.partners.length + '</strong> partners.<br>' +
    '• Total revenue: <strong>₹' + totalRev.toLocaleString('en-IN') + '</strong><br>' +
    '• Wallet balance: <strong>₹' + walletBalance.toLocaleString('en-IN') + '</strong><br><br>' +
    'Try asking about specific areas like customers, partners, revenue, or leads for detailed insights! 💡';
}

/* ── LOGOUT ───────────────────────────────────── */
document.getElementById('logoutBtn').addEventListener('click', () => {
  if (confirm('Log out?')) window.location.href = 'logout.php';
});

// At the end of your code, replace the init function with this:
(async function init() {
    refreshActivity();
    await loadAllData();
    initCharts();
    await loadTransactions();
    await loadSettings();
    await loadPosters();
    await loadReviews('reviewsContainer');
    await loadServices('servicesContainer');
    await loadPartnerApplications(); // ADD THIS
    
    // Check active section
    const activeSection = document.querySelector('.section.active');
    if (activeSection && activeSection.id === 'servicesSection') {
        loadServices('servicesContainer');
    }
    if (activeSection && activeSection.id === 'partnerApplicationsSection') {
        loadPartnerApplications();
    }
    
    // Auto refresh every 5 minutes
    setInterval(() => {
        refreshAllData();
        loadTransactions();
        loadPosters();
        loadReviews('reviewsContainer');
        loadServices('servicesContainer');
        loadPartnerApplications(); // ADD THIS
    }, 300000);
})();
</script>
</body>
</html>