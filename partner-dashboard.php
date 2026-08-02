<?php
// ============================================================
// PARTNER DASHBOARD — WORLD CLASS CRM
// Matching admin theme: Plus Jakarta Sans, design tokens, sidebar
// ============================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ============================================================
// DETERMINE USER ROLE AND TARGET PARTNER
// ============================================================

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$viewer_role = $_SESSION['user_role'];
$viewer_id = (int)$_SESSION['user_id'];
$target_partner_id = $viewer_id; // Default: view own profile
$is_admin_view = false;

// Database connection
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

// Admin can view any partner via URL parameters
if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && in_array($viewer_role, ['admin', 'super_admin'])) {
    $is_admin_view = true;
    
    // Get the partner ID from URL
    if (isset($_GET['user_id'])) {
        $target_partner_id = (int)$_GET['user_id'];
    } elseif (isset($_GET['partner_id'])) {
        $target_partner_id = (int)$_GET['partner_id'];
    } else {
        // If no partner ID specified, try to get first partner
        try {
            $stmt = $pdo->query("SELECT id FROM users WHERE role = 'partner' LIMIT 1");
            $first = $stmt->fetch();
            if ($first) {
                $target_partner_id = (int)$first['id'];
            }
        } catch (Exception $e) {
            die('No partners found in the system.');
        }
    }
} else {
    // Normal partner access - must be a partner
    if ($viewer_role !== 'partner') {
        header('Location: login.php');
        exit;
    }
    $target_partner_id = $viewer_id;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ============================================================
// LOAD PARTNER DATA
// ============================================================

// Load partner from users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'partner'");
$stmt->execute([$target_partner_id]);
$partner = $stmt->fetch();

// If partner not found and this is admin view, try to find any partner
if (!$partner && $is_admin_view) {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'partner' LIMIT 1");
    $partner = $stmt->fetch();
    if ($partner) {
        $target_partner_id = (int)$partner['id'];
    }
}

if (!$partner) {
    if ($is_admin_view) {
        die('No partner found. Please create a partner first.');
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Load partner profile
$stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->execute([$target_partner_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
if (!$profile && $partner) {
    $stmt = $pdo->prepare("INSERT INTO partners (user_id, name, email, phone, status, commission_rate, created_at) VALUES (?, ?, ?, ?, 'active', 10, NOW())");
    $stmt->execute([$target_partner_id, $partner['name'], $partner['email'], $partner['phone'] ?? '']);
    // Reload profile
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
    $stmt->execute([$target_partner_id]);
    $profile = $stmt->fetch();
}

// ============================================================
// SET VARIABLES FOR DISPLAY
// ============================================================

$user_name = h($partner['name'] ?? 'Partner');
$user_email = h($partner['email'] ?? '');
$user_id = $target_partner_id;
$csrf = $_SESSION['csrf_token'];
$initials = strtoupper(substr($partner['name'] ?? 'P', 0, 2));

// Determine which leads table to use
$leadsTable = 'leads';
$check = $pdo->query("SHOW TABLES LIKE 'partner_leads'");
if ($check->rowCount() > 0) $leadsTable = 'partner_leads';

// Count conversions for tier
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM $leadsTable WHERE partner_id = ? AND status = 'converted'");
$stmt->execute([$user_id]);
$conversions = (int)$stmt->fetch()['c'];

// If no conversions in partner_leads, try leads table
if ($conversions === 0 && $leadsTable === 'partner_leads') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM leads WHERE partner_id = ? AND status = 'converted'");
    $stmt->execute([$user_id]);
    $conversions = (int)$stmt->fetch()['c'];
}

// Tier definitions with benefits
$tiers = [
    1 => ['name'=>'Bronze',   'icon'=>'🥉','commission'=>20,'min'=>0,   'max'=>5,   'color'=>'#cd7f32', 'benefits'=>['20% commission','Standard support','Monthly payout','Email support']],
    2 => ['name'=>'Silver',   'icon'=>'🥈','commission'=>25,'min'=>6,   'max'=>15,  'color'=>'#9ea1a3', 'benefits'=>['25% commission','Priority support','Monthly payout','Email & Chat support']],
    3 => ['name'=>'Gold',     'icon'=>'🥇','commission'=>30,'min'=>16,  'max'=>30,  'color'=>'#ffd700', 'benefits'=>['30% commission','Priority support','Bi-weekly payout','Dedicated account manager']],
    4 => ['name'=>'Platinum', 'icon'=>'💎','commission'=>35,'min'=>31,  'max'=>50,  'color'=>'#e5e4e2', 'benefits'=>['35% commission','VIP support','Weekly payout','Dedicated account manager','Exclusive training']],
    5 => ['name'=>'Diamond',  'icon'=>'👑','commission'=>40,'min'=>51, 'max'=>999999,'color'=>'#b9f2ff', 'benefits'=>['40% commission','24/7 VIP support','Daily payout','Dedicated account manager','Exclusive training','Invitation to partner events']],
];

$current_tier = 1;
foreach ($tiers as $lvl => $t) {
    if ($conversions >= $t['min']) $current_tier = $lvl;
}
$tier_info = $tiers[$current_tier];
$next_tier = $tiers[$current_tier + 1] ?? null;
$tier_progress = $next_tier ? min(100, round(($conversions - $tier_info['min']) / ($next_tier['min'] - $tier_info['min']) * 100)) : 100;

// Admin view badge for sidebar
$admin_view_badge = $is_admin_view ? '<span style="font-size:10px;background:rgba(255,255,255,0.1);padding:2px 10px;border-radius:20px;color:rgba(255,255,255,0.8);margin-left:8px;"><i class="fas fa-eye"></i> Admin View</span>' : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrf ?>">
<title>Partner Portal | CIBIL Repair</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>

<style>
/* ================================================================
   DESIGN TOKENS — matches admin dashboard exactly
   ================================================================ */
:root {
  --brand:        #0d9e78;
  --brand-dark:   #0a7d60;
  --brand-light:  #e6f7f2;
  --brand-muted:  #b2e4d6;

  --bg-base:      #f4f6f9;
  --bg-surface:   #ffffff;
  --bg-raised:    #ffffff;
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

  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
  --shadow-lg: 0 12px 32px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.04);

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 16px;
  --radius-xl: 24px;

  --sidebar-w:  260px;
  --topbar-h:   64px;
  --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);

  --font-main: 'Plus Jakarta Sans', sans-serif;
  --font-mono: 'DM Mono', monospace;
}

[data-theme="dark"] {
  --brand-light:    #0f2e26;
  --brand-muted:    #1a4a38;
  --bg-base:        #0f1117;
  --bg-surface:     #1a1d27;
  --bg-raised:      #22263a;
  --bg-sunken:      #13161f;
  --text-primary:   #f1f5f9;
  --text-secondary: #94a3b8;
  --text-muted:     #64748b;
  --border:         rgba(255,255,255,0.07);
  --border-strong:  rgba(255,255,255,0.12);
  --sidebar-bg:     #080e0b;
  --success-bg: #052e1c; --success-text: #34d399;
  --warning-bg: #1c1204; --warning-text: #fbbf24;
  --danger-bg:  #1f0808; --danger-text:  #f87171;
  --info-bg:    #0c1a33; --info-text:    #60a5fa;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
  --shadow-lg: 0 12px 32px rgba(0,0,0,0.5);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-main);
  font-size: 14px;
  background: var(--bg-base);
  color: var(--text-primary);
  overflow-x: hidden;
  transition: background var(--transition), color var(--transition);
  -webkit-font-smoothing: antialiased;
}
a { text-decoration: none; color: inherit; }
button { font-family: inherit; cursor: pointer; }
input, select, textarea { font-family: inherit; }
:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 99px; }

/* ── SIDEBAR ── */
.sidebar {
  position: fixed; inset: 0 auto 0 0;
  width: var(--sidebar-w);
  background: var(--sidebar-bg);
  display: flex; flex-direction: column;
  z-index: 200;
  transition: width var(--transition), transform var(--transition);
  overflow: hidden;
}
.sidebar.collapsed { width: 64px; }

.sidebar-brand {
  padding: 20px 18px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex; align-items: center; gap: 10px;
  min-height: 68px;
}
.brand-icon {
  width: 36px; height: 36px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--brand), #06b6d4);
  border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; color: #fff; font-weight: 800;
}
.brand-text { overflow: hidden; white-space: nowrap; }
.brand-name { font-weight: 800; font-size: 15px; color: #fff; letter-spacing: -0.3px; }
.brand-sub  { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 1px; }

.sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 12px 0; }
.sidebar-nav::-webkit-scrollbar { width: 0; }

.nav-section-label {
  font-size: 10px; font-weight: 600; letter-spacing: 1.2px;
  text-transform: uppercase; color: rgba(255,255,255,0.3);
  padding: 14px 18px 4px;
  white-space: nowrap; overflow: hidden;
}
.sidebar.collapsed .nav-section-label { opacity: 0; pointer-events: none; }

.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 18px; margin: 1px 8px;
  border-radius: var(--radius-md);
  color: var(--sidebar-text);
  cursor: pointer; transition: all var(--transition);
  position: relative; white-space: nowrap;
  font-size: 13.5px; font-weight: 500;
}
.nav-item:hover  { background: var(--sidebar-hover); color: #fff; }
.nav-item.active { background: var(--sidebar-active); color: var(--sidebar-active-text); }
.nav-item.active::before {
  content: '';
  position: absolute; left: -8px; top: 50%;
  transform: translateY(-50%);
  width: 3px; height: 20px;
  background: var(--brand); border-radius: 0 3px 3px 0;
}
.nav-item i { width: 20px; text-align: center; flex-shrink: 0; font-size: 15px; }
.nav-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }
.nav-badge {
  background: var(--brand); color: #fff;
  font-size: 10px; font-weight: 700;
  padding: 1px 6px; border-radius: 99px; flex-shrink: 0;
}
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section-label,
.sidebar.collapsed .nav-badge { display: none; }
.sidebar.collapsed .nav-item { justify-content: center; padding: 10px; margin: 1px 8px; }
.sidebar.collapsed .nav-item.active::before { left: -8px; }
.sidebar.collapsed .nav-item::after {
  content: attr(data-tooltip);
  position: absolute; left: 68px;
  background: #1a2e28; color: #fff;
  padding: 5px 10px; border-radius: var(--radius-sm);
  font-size: 12px; white-space: nowrap;
  opacity: 0; pointer-events: none;
  transition: opacity var(--transition); z-index: 300;
}
.sidebar.collapsed .nav-item:hover::after { opacity: 1; }

.sidebar-footer {
  padding: 12px 8px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.sidebar-user {
  display: flex; align-items: center; gap: 10px;
  padding: 10px; border-radius: var(--radius-md);
  cursor: pointer; transition: background var(--transition);
  white-space: nowrap; overflow: hidden;
}
.sidebar-user:hover { background: var(--sidebar-hover); }
.user-avatar {
  width: 32px; height: 32px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--brand), #0891b2);
  border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-weight: 700; font-size: 12px; color: #fff;
}
.user-details { overflow: hidden; }
.user-name { font-size: 13px; font-weight: 600; color: #fff; }
.user-role { font-size: 11px; color: rgba(255,255,255,0.45); }
.sidebar.collapsed .user-details { display: none; }

.sidebar-toggle {
  position: fixed; top: 20px; left: calc(var(--sidebar-w) - 14px);
  width: 28px; height: 28px;
  background: var(--bg-surface); border: 1px solid var(--border);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  cursor: pointer; z-index: 201; transition: left var(--transition);
  box-shadow: var(--shadow-sm); color: var(--text-secondary); font-size: 11px;
}
.sidebar.collapsed ~ .sidebar-toggle { left: 50px; }

/* ── MAIN ── */
.main {
  flex: 1; margin-left: var(--sidebar-w);
  display: flex; flex-direction: column;
  transition: margin-left var(--transition);
  min-width: 0;
}
.sidebar.collapsed ~ .sidebar-toggle ~ .main { margin-left: 64px; }

/* ── TOPBAR ── */
.topbar {
  height: var(--topbar-h);
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px; position: sticky; top: 0; z-index: 100; gap: 16px;
}
.topbar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
.page-breadcrumb { font-size: 12px; color: var(--text-muted); white-space: nowrap; }
.page-title-top {
  font-size: 16px; font-weight: 700; color: var(--text-primary);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.topbar-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.clock-badge {
  font-family: var(--font-mono); font-size: 12px;
  background: var(--bg-sunken); border: 1px solid var(--border);
  padding: 5px 10px; border-radius: 99px; color: var(--text-secondary);
  white-space: nowrap;
}
.icon-btn {
  width: 36px; height: 36px;
  background: transparent; border: 1px solid var(--border);
  border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all var(--transition);
  color: var(--text-secondary); font-size: 15px; position: relative;
}
.icon-btn:hover { background: var(--bg-sunken); color: var(--text-primary); }
.notif-badge {
  position: absolute; top: -4px; right: -4px;
  background: var(--danger); color: #fff;
  font-size: 9px; font-weight: 700; padding: 1px 5px;
  border-radius: 99px; display: none; min-width: 16px; text-align: center;
}

.theme-toggle {
  display: flex; align-items: center; gap: 6px;
  background: var(--bg-sunken); border: 1px solid var(--border);
  border-radius: 99px; padding: 4px;
}
.theme-btn {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all var(--transition);
  font-size: 13px; color: var(--text-muted);
  background: transparent; border: none;
}
.theme-btn.active { background: var(--bg-surface); color: var(--text-primary); box-shadow: var(--shadow-sm); }

.logout-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: var(--radius-md);
  background: var(--danger-bg); color: var(--danger-text);
  border: 1px solid rgba(220,38,38,0.2);
  font-size: 13px; font-weight: 600;
  transition: all var(--transition);
}
.logout-btn:hover { background: var(--danger); color: #fff; }

/* ── CONTENT ── */
.content { padding: 24px; flex: 1; }

/* ── CARDS ── */
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
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; flex-wrap: wrap;
}
.card-title {
  font-size: 14px; font-weight: 700; color: var(--text-primary);
  display: flex; align-items: center; gap: 7px;
}
.card-title i { color: var(--brand); font-size: 15px; }
.card-body { padding: 20px; }

/* ── STATS ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px; margin-bottom: 20px;
}
.stat-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  display: flex; flex-direction: column; gap: 10px;
  transition: transform var(--transition), box-shadow var(--transition);
  position: relative; overflow: hidden; cursor: pointer;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-card::after {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
}
.stat-card.green::after  { background: linear-gradient(90deg, var(--brand), #34d399); }
.stat-card.blue::after   { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.stat-card.amber::after  { background: linear-gradient(90deg, #d97706, #fbbf24); }
.stat-card.purple::after { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
.stat-card.red::after    { background: linear-gradient(90deg, #dc2626, #f87171); }

.stat-top { display: flex; align-items: flex-start; justify-content: space-between; }
.stat-icon-wrap {
  width: 40px; height: 40px; border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center; font-size: 17px;
}
.stat-card.green  .stat-icon-wrap { background: var(--brand-light); color: var(--brand); }
.stat-card.blue   .stat-icon-wrap { background: var(--info-bg);  color: var(--info); }
.stat-card.amber  .stat-icon-wrap { background: var(--warning-bg); color: var(--warning); }
.stat-card.purple .stat-icon-wrap { background: #f3f0ff; color: #7c3aed; }
.stat-card.red    .stat-icon-wrap { background: var(--danger-bg); color: var(--danger); }
[data-theme="dark"] .stat-card.purple .stat-icon-wrap { background: #1a0a2e; color: #a78bfa; }

.stat-change {
  font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 99px;
}
.stat-change.up   { background: var(--success-bg); color: var(--success-text); }
.stat-change.down { background: var(--danger-bg);  color: var(--danger-text); }
.stat-change.neu  { background: var(--bg-sunken);  color: var(--text-muted); }

.stat-value { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
.stat-label { font-size: 12px; color: var(--text-secondary); font-weight: 500; }
.progress-bar { height: 6px; background: var(--bg-sunken); border-radius: 99px; overflow: hidden; margin-top: 4px; }
.progress-fill { height: 100%; border-radius: 99px; transition: width 0.8s ease; }

/* ── CHARTS ROW ── */
.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px; margin-bottom: 20px;
}

/* ── TABLE ── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th {
  padding: 10px 14px; text-align: left;
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.6px; color: var(--text-muted);
  background: var(--bg-sunken); border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
tbody td {
  padding: 11px 14px; border-bottom: 1px solid var(--border);
  font-size: 13px; color: var(--text-primary); vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--bg-sunken); }

/* ── BADGES ── */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 9px; border-radius: 99px;
  font-size: 11px; font-weight: 700; white-space: nowrap;
}
.badge-green  { background: var(--success-bg); color: var(--success-text); }
.badge-amber  { background: var(--warning-bg); color: var(--warning-text); }
.badge-red    { background: var(--danger-bg);  color: var(--danger-text); }
.badge-blue   { background: var(--info-bg);    color: var(--info-text); }
.badge-gray   { background: var(--bg-sunken);  color: var(--text-secondary); }
.badge-brand  { background: var(--brand-light); color: var(--brand-dark); }
.badge-purple { background: #f3f0ff; color: #7c3aed; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: var(--radius-md);
  font-size: 13px; font-weight: 600; border: none;
  cursor: pointer; transition: all var(--transition); white-space: nowrap;
}
.btn-primary { background: var(--brand); color: #fff; }
.btn-primary:hover { background: var(--brand-dark); }
.btn-danger  { background: var(--danger-bg);  color: var(--danger-text);  border: 1px solid rgba(220,38,38,.2); }
.btn-danger:hover  { background: var(--danger); color: #fff; }
.btn-success { background: var(--success-bg); color: var(--success-text); border: 1px solid rgba(5,150,105,.2); }
.btn-success:hover { background: var(--success); color: #fff; }
.btn-ghost   { background: var(--bg-sunken); color: var(--text-secondary); border: 1px solid var(--border); }
.btn-ghost:hover   { background: var(--border); }
.btn-sm { padding: 5px 11px; font-size: 12px; }
.btn-xs { padding: 3px 8px;  font-size: 11px; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── FORM ── */
.form-row   { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.form-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 160px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); }
.form-input, .form-select, .form-textarea {
  width: 100%; padding: 8px 12px;
  background: var(--bg-surface); border: 1px solid var(--border-strong);
  border-radius: var(--radius-md); font-size: 13px;
  color: var(--text-primary); transition: border-color var(--transition); outline: none;
}
.form-textarea { resize: vertical; min-height: 80px; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--brand); }
.form-input::placeholder, .form-textarea::placeholder { color: var(--text-muted); }

/* ── SECTIONS ── */
.section { display: none; }
.section.active { display: block; animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } }

/* ── MODAL ── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
  z-index: 1000; display: none;
  align-items: center; justify-content: center; padding: 16px;
}
.modal-overlay.open { display: flex; }
.modal-box {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  width: 100%; max-width: 560px;
  max-height: 92vh; overflow-y: auto;
  box-shadow: var(--shadow-lg);
  animation: modalIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-box.wide { max-width: 720px; }
@keyframes modalIn { from { transform: scale(0.9) translateY(20px); opacity: 0; } }
.modal-header {
  padding: 20px 24px 16px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border);
}
.modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.modal-title i { color: var(--brand); }
.modal-close {
  width: 32px; height: 32px;
  background: var(--bg-sunken); border: none; border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--text-secondary); font-size: 16px;
  transition: all var(--transition);
}
.modal-close:hover { background: var(--danger-bg); color: var(--danger); }
.modal-body   { padding: 20px 24px; }
.modal-footer {
  padding: 16px 24px; border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end; gap: 10px;
}

/* ── TOAST ── */
.toast-container {
  position: fixed; bottom: 20px; right: 20px;
  display: flex; flex-direction: column; gap: 10px; z-index: 2000;
}
.toast {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-radius: var(--radius-lg);
  background: var(--bg-surface); border: 1px solid var(--border);
  box-shadow: var(--shadow-lg); min-width: 280px; max-width: 380px;
  animation: toastIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  transition: all 0.2s;
}
@keyframes toastIn { from { transform: translateX(100%); opacity: 0; } }
.toast.leaving { transform: translateX(110%); opacity: 0; }
.toast-icon {
  width: 32px; height: 32px; border-radius: var(--radius-md); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.toast-success .toast-icon { background: var(--success-bg); color: var(--success); }
.toast-error   .toast-icon { background: var(--danger-bg);  color: var(--danger); }
.toast-info    .toast-icon { background: var(--info-bg);    color: var(--info); }
.toast-warning .toast-icon { background: var(--warning-bg); color: var(--warning); }
.toast-msg { font-size: 13px; font-weight: 500; flex: 1; }
.toast-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px; }

/* ── SPINNER ── */
.spinner {
  width: 18px; height: 18px; border: 2px solid var(--border);
  border-top-color: var(--brand); border-radius: 50%;
  animation: spin 0.7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── EMPTY STATE ── */
.empty-state {
  padding: 48px 20px; text-align: center; color: var(--text-muted);
}
.empty-state i { font-size: 40px; margin-bottom: 12px; display: block; }
.empty-state p { font-size: 14px; }

/* ── FILTER BAR ── */
.filter-bar {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 20px; border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.search-input {
  width: 100%; padding: 8px 12px 8px 32px;
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md); background: var(--bg-surface);
  font-size: 13px; color: var(--text-primary); outline: none;
}
.search-input:focus { border-color: var(--brand); }

/* ── TIER WIDGET ── */
.tier-widget {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px; margin-bottom: 20px;
  position: relative; overflow: hidden;
}
.tier-widget::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--brand), #fbbf24, #a78bfa);
}
.tier-top { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.tier-badge {
  width: 64px; height: 64px; border-radius: 50%;
  background: linear-gradient(135deg, var(--brand-light), var(--brand-muted));
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(13,158,120,0.2);
}
.tier-info h3 { font-size: 18px; font-weight: 800; color: var(--text-primary); }
.tier-info p  { font-size: 13px; color: var(--text-secondary); margin-top: 2px; }
.tier-commission {
  margin-left: auto;
  background: var(--brand-light);
  border: 1px solid var(--brand-muted);
  border-radius: var(--radius-lg);
  padding: 10px 18px; text-align: center;
}
.tier-commission .pct { font-size: 24px; font-weight: 800; color: var(--brand); }
.tier-commission .lbl { font-size: 11px; color: var(--text-secondary); }

.tier-progress-section { margin-top: 16px; }
.tier-progress-label {
  display: flex; justify-content: space-between;
  font-size: 12px; color: var(--text-secondary); margin-bottom: 6px;
}
.tier-benefits-row {
  display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;
}
.benefit-chip {
  display: flex; align-items: center; gap: 5px;
  background: var(--bg-sunken); border: 1px solid var(--border);
  padding: 4px 10px; border-radius: 99px; font-size: 11px; color: var(--text-secondary);
}
.benefit-chip i { color: var(--brand); font-size: 10px; }

/* ── REFERRAL CARD ── */
.referral-card {
  background: linear-gradient(135deg, var(--sidebar-bg), #0e3d30);
  border-radius: var(--radius-lg);
  padding: 20px; color: #fff; margin-bottom: 20px;
  position: relative; overflow: hidden;
}
.referral-card::after {
  content: ''; position: absolute; top: -30px; right: -30px;
  width: 120px; height: 120px; border-radius: 50%;
  background: rgba(13,158,120,0.2);
}
.ref-code {
  font-family: var(--font-mono); font-size: 20px; font-weight: 700;
  letter-spacing: 3px; color: #34d399;
  background: rgba(255,255,255,0.08);
  padding: 8px 16px; border-radius: var(--radius-md);
  display: inline-block; margin: 10px 0;
}

/* ── LEAD SCORING ── */
.score-bar-wrap { display: flex; align-items: center; gap: 8px; }
.score-bar { flex: 1; height: 6px; background: var(--bg-sunken); border-radius: 99px; overflow: hidden; }
.score-bar-fill { height: 100%; border-radius: 99px; }
.score-num { font-weight: 700; font-size: 13px; min-width: 32px; text-align: right; }

/* ── AI ASSISTANT ── */
.ai-panel {
  position: fixed; bottom: 20px; right: 20px;
  width: 380px; z-index: 900;
  display: flex; flex-direction: column;
  border-radius: var(--radius-xl);
  background: var(--bg-surface);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  transition: all var(--transition);
  overflow: hidden;
}
.ai-panel.mini { width: auto; }

.ai-header {
  padding: 12px 16px;
  background: linear-gradient(135deg, var(--sidebar-bg), #0e3d30);
  display: flex; align-items: center; gap: 10px;
  cursor: pointer; user-select: none;
}
.ai-status-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #34d399; flex-shrink: 0;
  box-shadow: 0 0 6px rgba(52,211,153,0.6);
  animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
.ai-title    { color: #fff; font-size: 13px; font-weight: 700; flex: 1; }
.ai-subtitle { color: rgba(255,255,255,0.45); font-size: 11px; }
.ai-chevron  { color: rgba(255,255,255,0.6); font-size: 12px; transition: transform var(--transition); }
.ai-panel.mini .ai-chevron { transform: rotate(180deg); }

.ai-body { display: flex; flex-direction: column; height: 400px; }
.ai-panel.mini .ai-body { display: none; }

.ai-messages {
  flex: 1; overflow-y: auto; padding: 14px;
  display: flex; flex-direction: column; gap: 10px;
}
.ai-msg {
  max-width: 85%; padding: 10px 13px;
  border-radius: var(--radius-lg); font-size: 13px; line-height: 1.5;
}
.ai-msg.user {
  background: var(--brand); color: #fff;
  align-self: flex-end; border-bottom-right-radius: 4px;
}
.ai-msg.bot {
  background: var(--bg-sunken); color: var(--text-primary);
  align-self: flex-start; border-bottom-left-radius: 4px;
  border: 1px solid var(--border);
}
.ai-chips {
  padding: 8px 14px; display: flex; flex-wrap: wrap; gap: 6px;
  border-top: 1px solid var(--border);
}
.ai-chip {
  padding: 5px 10px; border-radius: 99px;
  background: var(--bg-sunken); border: 1px solid var(--border);
  font-size: 11px; cursor: pointer; transition: all var(--transition);
  color: var(--text-secondary); white-space: nowrap;
}
.ai-chip:hover { background: var(--brand-light); border-color: var(--brand-muted); color: var(--brand); }
.ai-input-row {
  padding: 10px 12px; border-top: 1px solid var(--border);
  display: flex; gap: 8px;
}
.ai-input {
  flex: 1; padding: 9px 12px; border: 1px solid var(--border);
  border-radius: var(--radius-md); font-size: 13px;
  background: var(--bg-sunken); color: var(--text-primary); outline: none;
}
.ai-input:focus { border-color: var(--brand); }
.ai-send {
  width: 36px; height: 36px; background: var(--brand); border: none;
  border-radius: var(--radius-md); color: #fff; font-size: 14px;
  cursor: pointer; transition: background var(--transition);
  display: flex; align-items: center; justify-content: center;
}
.ai-send:hover { background: var(--brand-dark); }

/* Password requirements */
.password-requirements {
    font-size: 12px;
    color: var(--text-muted);
    padding: 8px 12px;
    background: var(--bg-sunken);
    border-radius: var(--r-md);
    margin-top: 8px;
}

.password-requirements ul {
    margin: 4px 0 0 16px;
    padding: 0;
}

.password-requirements li {
    list-style: none;
    padding: 2px 0;
}

.password-requirements li.valid {
    color: var(--success);
}

.password-requirements li.invalid {
    color: var(--danger);
}

.password-requirements li::before {
    content: '• ';
}

.password-requirements li.valid::before {
    content: '✅ ';
}

.password-requirements li.invalid::before {
    content: '❌ ';
}

/* ── NOTIF PANEL ── */
.notif-panel {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 340px; background: var(--bg-surface);
  border: 1px solid var(--border); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg); display: none; z-index: 500; overflow: hidden;
}
.notif-panel.open { display: block; }
.notif-header {
  padding: 14px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.notif-header h4 { font-size: 14px; font-weight: 700; }
.notif-mark { font-size: 12px; color: var(--brand); cursor: pointer; background: none; border: none; }
.notif-list { max-height: 300px; overflow-y: auto; }
.notif-item {
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  cursor: pointer; transition: background var(--transition);
  display: flex; gap: 10px; align-items: flex-start;
}
.notif-item:hover { background: var(--bg-sunken); }
.notif-item.unread { background: var(--brand-light); }
.notif-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--brand); flex-shrink: 0; margin-top: 5px; }
.notif-item:not(.unread) .notif-dot { background: transparent; }
.notif-title { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
.notif-msg   { font-size: 12px; color: var(--text-secondary); }
.notif-time  { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

/* ── TABS ── */
.tab-bar { display: flex; border-bottom: 1px solid var(--border); padding: 0 20px; gap: 4px; }
.tab-btn {
  padding: 10px 16px; font-size: 13px; font-weight: 600;
  color: var(--text-muted); background: none; border: none;
  cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px;
  transition: color var(--transition);
}
.tab-btn.active { color: var(--brand); border-bottom-color: var(--brand); }
.tab-content { display: none; padding: 20px; }
.tab-content.active { display: block; }

/* ── CONTACT CATEGORY CHIPS ── */
.cat-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.cat-chip {
  padding: 6px 14px; border-radius: 99px;
  background: var(--bg-sunken); border: 1px solid var(--border);
  font-size: 12px; font-weight: 600; cursor: pointer;
  transition: all var(--transition); color: var(--text-secondary);
}
.cat-chip.active {
  background: var(--brand); color: #fff; border-color: var(--brand);
}

/* ── PERFORMANCE ── */
.kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px; }
.kpi-box {
  background: var(--bg-sunken); border: 1px solid var(--border);
  border-radius: var(--radius-md); padding: 14px; text-align: center;
}
.kpi-val { font-size: 22px; font-weight: 800; color: var(--brand); }
.kpi-lbl { font-size: 11px; color: var(--text-secondary); margin-top: 3px; }

/* ── RESPONSIVE ── */
@media(max-width:900px) {
  .sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; }
  .sidebar.mobile-open { transform: translateX(0); }
  .main { margin-left: 0 !important; }
  .sidebar-toggle { left: 12px !important; }
  .charts-row { grid-template-columns: 1fr; }
  .ai-panel { width: calc(100vw - 32px); right: 16px; }
  .topbar-right .clock-badge { display: none; }
}
@media(max-width:600px) {
  .content { padding: 14px; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .tier-commission { display: none; }
}

/* Add to your CSS to show clickable labels */
label[for] {
    cursor: pointer;
    transition: color 0.2s;
}
label[for]:hover {
    color: #0056b3;
}

/* ── STATS ROW ── */
.stats-row {
    margin-bottom: 20px;
}

/* ── STAT CARDS ── */
.stat-card {
    padding: 20px 15px;
    border-radius: 12px;
    text-align: center;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card .stat-icon {
    font-size: 24px;
    margin-bottom: 4px;
    opacity: 0.9;
}

.stat-card .stat-number {
    font-size: 30px;
    font-weight: 700;
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── BADGE IN TABS ── */
.tab-btn .badge {
    margin-left: 5px;
    font-size: 10px;
    padding: 2px 6px;
}

@font-face {
  font-family: 'Plus Jakarta Sans';
  font-display: swap; /* Tells the browser to display fallback text instantly */
  src: url('path-to-font.woff2') format('woff2');
}

/* ── EMPTY STATE ── */
.empty-state {
    padding: 30px 20px;
    text-align: center;
    color: #6c757d;
}

.empty-state .spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid #e9ecef;
    border-top-color: #0d6efd;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* ── OVERDUE ROW ── */
.table-danger td {
    background-color: #f8d7da !important;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .stats-row .col-md-3 {
        margin-bottom: 10px;
    }
    .stat-card {
        min-height: 70px;
        padding: 12px;
    }
    .stat-card .stat-number {
        font-size: 22px;
    }
}
/* ── ADMIN VIEW BADGE ── */
.admin-badge {
  background: #dc3545;
  color: #fff;
  padding: 3px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.admin-badge i { font-size: 10px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">CR</div>
    <div class="brand-text">
      <div class="brand-name">CIBIL Repair</div>
      <div class="brand-sub">Partner Portal</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <div class="nav-item active" data-section="dashboard" data-tooltip="Dashboard"><i class="fas fa-home"></i><span class="nav-label">Dashboard</span></div>
    <div class="nav-item" data-section="performance" data-tooltip="Analytics"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></div>

    <div class="nav-section-label">Leads & Customers</div>
    <div class="nav-item" data-section="leads" data-tooltip="My Leads"><i class="fas fa-filter"></i><span class="nav-label">My Leads</span><span class="nav-badge" id="sideLeadBadge">0</span></div>
    <div class="nav-item" data-section="scoring" data-tooltip="Lead Scoring"><i class="fas fa-bullseye"></i><span class="nav-label">Lead Scoring</span></div>
    <div class="nav-item" data-section="customers" data-tooltip="Customers"><i class="fas fa-users"></i><span class="nav-label">Customers</span></div>
    <div class="nav-item" data-section="followups" data-tooltip="Follow-ups"><i class="fas fa-calendar-check"></i><span class="nav-label">Follow-ups</span></div>

    <div class="nav-section-label">Finance</div>
    <div class="nav-item" data-section="commission" data-tooltip="Commission"><i class="fas fa-rupee-sign"></i><span class="nav-label">Commission</span></div>
    <div class="nav-item" data-section="payouts" data-tooltip="Payouts"><i class="fas fa-wallet"></i><span class="nav-label">Payouts</span></div>

    <div class="nav-section-label">Network</div>
    <div class="nav-item" data-section="connectors" data-tooltip="My Connectors"><i class="fas fa-plug"></i><span class="nav-label">My Connectors</span></div>
    <div class="nav-item" data-section="contacts" data-tooltip="Contacts"><i class="fas fa-address-book"></i><span class="nav-label">Contact Directory</span></div>
    <div class="nav-item" data-section="referral" data-tooltip="Referral"><i class="fas fa-share-alt"></i><span class="nav-label">Referral Program</span></div>

    <div class="nav-section-label">Support</div>
    <div class="nav-item" data-section="tickets" data-tooltip="Support"><i class="fas fa-headset"></i><span class="nav-label">Support Tickets</span></div>
    <div class="nav-item" data-section="profile" data-tooltip="Profile"><i class="fas fa-user-cog"></i><span class="nav-label">My Profile</span></div>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-details">
        <div class="user-name"><?= $user_name ?></div>
        <div class="user-role"><?= $tier_info['icon'] ?> <?= $tier_info['name'] ?> Partner</div>
      </div>
    </div>
  </div>
</aside>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
  <i class="fas fa-chevron-left" id="sidebarToggleIcon"></i>
</button>

<!-- MAIN -->
<div class="main" id="main">

  <!-- VIEWER BANNER (Admin viewing) -->
  <?php if ($viewer_role === 'admin'): ?>
  <div class="viewer-banner">
    <span>🛡️ Admin View — Viewing partner: <strong><?= h($partner['name']) ?></strong> (<?= h($partner['email']) ?>)</span>
    <div style="display:flex;gap:12px;align-items:center;">
      <a href="admin-dashboard.php" style="color:#bfdbfe;text-decoration:underline;">← Back to Admin</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div>
        <div class="page-breadcrumb">CIBIL Repair Partner CRM</div>
        <div class="page-title-top" id="pageTitle">Dashboard</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="clock-badge" id="liveClock">--:--:--</div>

      <div class="theme-toggle">
        <button class="theme-btn active" id="lightBtn" onclick="setTheme('light')" title="Light"><i class="fas fa-sun"></i></button>
        <button class="theme-btn" id="darkBtn" onclick="setTheme('dark')" title="Dark"><i class="fas fa-moon"></i></button>
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

      <?php if ($viewer_role === 'partner'): ?>
      <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</button>
      <?php else: ?>
      <a href="admin-dashboard.php" class="back-btn" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--radius-md);background:var(--info-bg);color:var(--info-text);border:1px solid rgba(37,99,235,0.2);font-size:13px;font-weight:600;transition:all var(--transition);text-decoration:none;"><i class="fas fa-arrow-left"></i> Admin Panel</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="content">

    <!-- ====== DASHBOARD ====== -->
    <div class="section active" id="dashboardSection">

      <!-- Tier Widget -->
      <div class="tier-widget">
        <div class="tier-top">
          <div class="tier-badge" id="tierBadgeIcon"><?= $tier_info['icon'] ?></div>
          <div class="tier-info">
            <h3 id="tierName"><?= $tier_info['name'] ?> Partner</h3>
            <p id="tierDesc"><?= $conversions ?> conversions · Next tier<?= $next_tier ? ': '.$next_tier['name'] : ': Max!' ?></p>
          </div>
          <div class="tier-commission">
            <div class="pct" id="tierPct"><?= $tier_info['commission'] ?>%</div>
            <div class="lbl">Commission</div>
          </div>
        </div>
        <div class="tier-progress-section">
          <div class="tier-progress-label">
            <span><?= $tier_info['name'] ?> (<?= $tier_info['min'] ?>+)</span>
            <?php if ($next_tier): ?>
            <span><?= $conversions ?> / <?= $next_tier['min'] ?> to <?= $next_tier['name'] ?></span>
            <?php else: ?>
            <span>Diamond — Maximum Tier 🎉</span>
            <?php endif; ?>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $tier_progress ?>%;background:linear-gradient(90deg,var(--brand),#34d399);"></div>
          </div>
        </div>
        <div class="tier-benefits-row">
          <?php foreach($tier_info['benefits'] ?? ['{commission}% commission','Standard support','Monthly payout'] as $b): ?>
          <div class="benefit-chip"><i class="fas fa-check-circle"></i><?= h(str_replace('{commission}', $tier_info['commission'], $b)) ?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card green" onclick="showSection('leads')">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-filter"></i></div>
            <span class="stat-change up" id="statLeadChange">—</span>
          </div>
          <div class="stat-value" id="statTotalLeads">—</div>
          <div class="stat-label">Total Leads</div>
          <div class="progress-bar"><div class="progress-fill" id="statLeadBar" style="width:0%;background:var(--brand);"></div></div>
        </div>
        <div class="stat-card green" onclick="showSection('customers')">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-user-check"></i></div>
            <span class="stat-change up" id="statConvChange">—</span>
          </div>
          <div class="stat-value" id="statConverted">—</div>
          <div class="stat-label">Converted</div>
          <div class="progress-bar"><div class="progress-fill" id="statConvBar" style="width:0%;background:var(--brand);"></div></div>
        </div>
        <div class="stat-card amber" onclick="showSection('commission')">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-rupee-sign"></i></div>
            <span class="stat-change up">Earned</span>
          </div>
          <div class="stat-value" id="statCommission">—</div>
          <div class="stat-label">Total Commission</div>
          <div class="progress-bar"><div class="progress-fill" style="width:65%;background:#d97706;"></div></div>
        </div>
        <div class="stat-card blue" onclick="showSection('payouts')">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-wallet"></i></div>
            <span class="stat-change neu">Pending</span>
          </div>
          <div class="stat-value" id="statPending">—</div>
          <div class="stat-label">Pending Payout</div>
          <div class="progress-bar"><div class="progress-fill" style="width:40%;background:#2563eb;"></div></div>
        </div>
        <div class="stat-card purple">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-percent"></i></div>
            <span class="stat-change up" id="statCrChange">—</span>
          </div>
          <div class="stat-value" id="statConvRate">—</div>
          <div class="stat-label">Conversion Rate</div>
          <div class="progress-bar"><div class="progress-fill" id="statCrBar" style="width:0%;background:#7c3aed;"></div></div>
        </div>
        <div class="stat-card red" onclick="showSection('followups')">
          <div class="stat-top">
            <div class="stat-icon-wrap"><i class="fas fa-calendar-exclamation"></i></div>
            <span class="stat-change down">Due</span>
          </div>
          <div class="stat-value" id="statFollowups">—</div>
          <div class="stat-label">Follow-ups Due</div>
          <div class="progress-bar"><div class="progress-fill" style="width:30%;background:#dc2626;"></div></div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-row">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-area"></i>Commission Trend</div>
            <div style="display:flex;gap:8px;">
              <button class="btn btn-ghost btn-sm" onclick="switchDashChart('monthly')">Monthly</button>
              <button class="btn btn-ghost btn-sm" onclick="switchDashChart('weekly')">Weekly</button>
            </div>
          </div>
          <div class="card-body">
            <div style="position:relative;height:220px;">
              <canvas id="commissionChart"></canvas>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i>Lead Status</div></div>
          <div class="card-body">
            <div style="position:relative;height:180px;">
              <canvas id="leadStatusChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-clock"></i>Recent Activity</div>
          <button class="btn btn-ghost btn-sm" onclick="loadDashboard()"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Activity</th><th>Customer</th><th>Date</th><th>Status</th><th>Amount</th></tr></thead>
            <tbody id="recentActivityBody">
              <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- LEADS SECTION - Displays the table -->
    <!-- ================================================================ -->
    <div class="section" id="leadsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-filter"></i>My Leads</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-primary btn-sm" onclick="openModal('addLeadModal')"><i class="fas fa-plus"></i>Add Lead</button>
            <button class="btn btn-ghost btn-sm" onclick="openModal('bulkImportModal')"><i class="fas fa-file-upload"></i>Bulk Import</button>
            <button class="btn btn-success btn-sm" onclick="exportLeadsExcel()"><i class="fas fa-file-excel"></i>Export</button>
          </div>
        </div>
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input class="search-input" id="leadSearch" placeholder="Search by name, phone…" oninput="filterLeads()">
          </div>
          <select class="form-select" id="leadStatusFilter" onchange="filterLeads()" style="width:140px;padding:8px 12px;">
            <option value="">All Status</option>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="converted">Converted</option>
            <option value="lost">Lost</option>
          </select>
          <select class="form-select" id="leadServiceFilter" onchange="filterLeads()" style="width:160px;padding:8px 12px;">
            <option value="">All Services</option>
            <option value="Written Off Clearance">Written Off</option>
            <option value="Settled Clearance">Settled</option>
            <option value="Suit Filed Clearance">Suit Filed</option>
            <option value="Credit Report Analysis">Credit Report</option>
            <option value="Profile Correction">Profile Correction</option>
          </select>
          <select class="form-select" id="leadSourceFilter" onchange="filterLeads()" style="width:160px;padding:8px 12px;">
            <option value="">All Sources</option>
            <option value="direct">📌 Direct</option>
            <option value="referral">🔗 Referral</option>
            <option value="connector">🔌 Connector</option>
          </select>
        </div>
        <div class="table-wrap">
          <table id="leadsTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Service</th>
                <th>Source</th>
                <th>Status</th>
                <th>Date</th>
                <th>Commission</th>
                <th>Score</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="leadsBody">
              <tr><td colspan="10"><div class="empty-state"><div class="spinner"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- LEAD SCORING SECTION - Shows AI scored leads -->
    <!-- ================================================================ -->
    <div class="section" id="scoringSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-bullseye"></i>AI Lead Scoring</div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-primary btn-sm" onclick="refreshAllScores()"><i class="fas fa-sync-alt"></i>Refresh Scores</button>
            <button class="btn btn-success btn-sm" onclick="exportScoredLeads()"><i class="fas fa-download"></i>Export</button>
          </div>
        </div>
        <div class="filter-bar">
          <select class="form-select" id="scoreFilter" onchange="loadScoredLeads()" style="width:180px;padding:8px 12px;">
            <option value="all">All Priorities</option>
            <option value="urgent">🔴 Urgent (70-100)</option>
            <option value="high">🟠 High (50-69)</option>
            <option value="medium">🟡 Medium (30-49)</option>
            <option value="low">⚪ Low (0-29)</option>
          </select>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Service</th>
                <th>Source</th>
                <th>Status</th>
                <th>Score</th>
                <th>Priority</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="scoredLeadsBody">
              <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- ADD LEAD MODAL - FIXED VERSION -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="addLeadModal">
        <div class="modal-box wide">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-plus-circle"></i>Add New Lead</span>
                <button class="modal-close" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <!-- Customer Info -->
                    <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leadName">Customer Name *</label>
                        <input class="form-input" id="leadName" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadPhone">Phone *</label>
                        <input class="form-input" id="leadPhone" placeholder="Mobile number" type="tel">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leadEmail">Email</label>
                        <input class="form-input" id="leadEmail" placeholder="Email" type="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadCity">City</label>
                        <input class="form-input" id="leadCity" placeholder="City">
                    </div>
                </div>
            
                <!-- Service & Source -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leadService">Service *</label>
                        <select class="form-select" id="leadService">
                            <option>Written Off Clearance</option>
                            <option>Settled Clearance</option>
                            <option>Suit Filed Clearance</option>
                            <option>Credit Report Analysis</option>
                            <option>Profile Correction</option>
                            <option>Wrong Entry Clearance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadSource">Lead Source *</label>
                        <select class="form-select" id="leadSource" onchange="toggleSourceFields()">
                            <option value="direct">📌 Direct Lead</option>
                            <option value="referral">🔗 Referral</option>
                            <option value="connector">🔌 Connector</option>
                        </select>
                    </div>
                </div>
                
                <!-- Referral Selection (hidden by default) -->
                <div class="form-row" id="leadReferralFields" style="display:none;">
                    <div class="form-group">
                        <label class="form-label" for="leadReferralSelect">Select Referral *</label>
                        <select class="form-select" id="leadReferralSelect" onchange="updateLeadReferralCommission()">
                            <option value="">Select referral...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadReferralCommission">Referral Commission</label>
                        <input class="form-input" id="leadReferralCommission" value="10%" readonly style="background:var(--bg-sunken);">
                    </div>
                </div>
            
                <!-- Connector Selection (hidden by default) -->
                <div class="form-row" id="leadConnectorFields" style="display:none;">
                    <div class="form-group">
                        <label class="form-label" for="leadConnectorSelect">Select Connector *</label>
                        <select class="form-select" id="leadConnectorSelect" onchange="updateLeadConnectorCommission()">
                            <option value="">Select connector...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadConnectorCommission">Connector Commission</label>
                        <input class="form-input" id="leadConnectorCommission" value="15%" readonly style="background:var(--bg-sunken);">
                    </div>
                </div>
            
                <!-- Additional Info -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leadScore">CIBIL Score (approx)</label>
                        <input class="form-input" id="leadScore" type="number" placeholder="e.g. 620" min="300" max="900">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="leadLoanAmt">Loan Amount (₹)</label>
                        <input class="form-input" id="leadLoanAmt" type="number" placeholder="e.g. 500000">
                    </div>
                </div>
            
                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label" for="leadNotes">Notes</label>
                    <textarea class="form-textarea" id="leadNotes" placeholder="Additional notes…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addLeadModal')">Cancel</button>
                <button class="btn btn-primary" id="addLeadBtn" onclick="addLead()"><i class="fas fa-save"></i>Save Lead</button>
            </div>
        </div>
    </div>

    <!-- ====== CUSTOMERS ====== -->
    <div class="section" id="customersSection">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-users"></i>My Customers</div>
                <button class="btn btn-success btn-sm" onclick="exportCustomersExcel()"><i class="fas fa-file-excel"></i>Export</button>
            </div>
            <div class="filter-bar">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input class="search-input" id="custSearch" placeholder="Search customers…" oninput="filterCustomers()">
                </div>
            </div>
            <div class="table-wrap">
                <table id="customersTable">
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Service</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody id="customersBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====== FOLLOW-UPS ====== -->
    <div class="section" id="followupsSection">
        
        <!-- ── STATS CARDS ── -->
        <div class="row stats-row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number" id="fuPendingCount">0</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger text-white">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-number" id="fuOverdueCount">0</div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number" id="fuCompletedCount">0</div>
                    <div class="stat-label">Completed</div>
                    </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white" style="cursor:pointer;" onclick="openModal('addFollowupModal')">
                    <div class="stat-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="stat-label">Schedule New</div>
                </div>
            </div>
        </div>
    
        <!-- ── MAIN CARD ── -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-calendar-check"></i> Follow-up Manager</div>
                <button class="btn btn-primary btn-sm" onclick="openModal('addFollowupModal')">
                    <i class="fas fa-plus"></i> Schedule Follow-up
                </button>
            </div>
        
            <!-- Tab Navigation -->
            <div class="tab-bar">
                <button class="tab-btn active" onclick="switchTab(this,'fuPending')">
                    Pending <span class="badge bg-warning" id="fuPendingBadge">0</span>
                </button>
                <button class="tab-btn" onclick="switchTab(this,'fuDone')">
                    Completed <span class="badge bg-success" id="fuCompletedBadge">0</span>
                </button>
                <button class="tab-btn" onclick="switchTab(this,'fuAll')">
                    All <span class="badge bg-secondary" id="fuAllBadge">0</span>
                </button>
            </div>
        
            <!-- Pending Tab -->
            <div class="tab-content active" id="fuPending">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lead/Customer</th>
                                <th>Phone</th>
                                <th>Due Date</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="fuPendingBody">
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="spinner"></div>
                                        <p>Loading pending follow-ups...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        
            <!-- Completed Tab -->
            <div class="tab-content" id="fuDone">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lead/Customer</th>
                                <th>Done At</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="fuDoneBody">
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <div class="spinner"></div>
                                        <p>Loading completed follow-ups...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        
            <!-- All Tab -->
            <div class="tab-content" id="fuAll">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lead/Customer</th>
                                <th>Phone</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="fuAllBody">
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="spinner"></div>
                                        <p>Loading all follow-ups...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== COMMISSION ====== -->
    <div class="section" id="commissionSection">
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><div class="card-title"><i class="fas fa-rupee-sign"></i>Commission Summary</div></div>
            <div class="kpi-row" style="padding:20px;">
                <div class="kpi-box"><div class="kpi-val" id="kpiTotalComm">—</div><div class="kpi-lbl">Total Earned</div></div>
                <div class="kpi-box"><div class="kpi-val" id="kpiPaidComm">—</div><div class="kpi-lbl">Paid Out</div></div>
                <div class="kpi-box"><div class="kpi-val" id="kpiPendingComm">—</div><div class="kpi-lbl">Pending</div></div>
                <div class="kpi-box"><div class="kpi-val" id="kpiAvgComm">—</div><div class="kpi-lbl">Avg per Deal</div></div>
                <div class="kpi-box"><div class="kpi-val" id="kpiRate"><?= $tier_info['commission'] ?>%</div><div class="kpi-lbl">Current Rate</div></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-history"></i>Commission History</div>
                <button class="btn btn-success btn-sm" onclick="exportCommissionExcel()"><i class="fas fa-file-excel"></i>Export</button>
            </div>
            <div class="table-wrap">
                <table><thead><tr><th>#</th><th>Customer</th><th>Service</th><th>Sale Amount</th><th>Commission</th><th>Rate</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody id="commissionBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====== PAYOUTS ====== -->
    <div class="section" id="payoutsSection">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-value" id="payAvailable">—</div>
          <div class="stat-label">Available for Payout</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-clock"></i></div></div>
          <div class="stat-value" id="payProcessing">—</div>
          <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-history"></i></div></div>
          <div class="stat-value" id="payTotalPaid">—</div>
          <div class="stat-label">Total Paid Out</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-wallet"></i>Payout Requests</div>
          <button class="btn btn-primary" onclick="openModal('payoutModal')"><i class="fas fa-plus"></i>Request Payout</button>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Request Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Paid Date</th><th>Reference</th></tr></thead>
            <tbody id="payoutsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== CONNECTORS ====== -->
    <div class="section" id="connectorsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-plug"></i>My Connectors</div>
          <button class="btn btn-primary btn-sm" onclick="openModal('addConnectorModal')"><i class="fas fa-plus"></i>Add Connector</button>
        </div>
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input class="search-input" id="connSearch" placeholder="Search connectors…" oninput="filterConnectors()">
          </div>
          <select class="form-select" id="connTypeFilter" onchange="filterConnectors()" style="width:160px;padding:8px 12px;">
            <option value="">All Types</option>
            <option value="bank">Bank</option>
            <option value="ca">CA/Accountant</option>
            <option value="lawyer">Lawyer</option>
            <option value="property">Property Dealer</option>
            <option value="vehicle">Vehicle Showroom</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Name</th><th>Type</th><th>Company</th><th>Phone</th><th>Leads Referred</th><th>Commission Due</th><th>Actions</th></tr></thead>
            <tbody id="connectorsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== CONTACT DIRECTORY ====== -->
    <div class="section" id="contactsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-address-book"></i>Professional Contact Directory
            <span class="badge badge-brand" id="contactCountBadge" style="display:none;margin-left:8px;font-size:11px;">0</span>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-primary btn-sm" onclick="openModal('addContactModal')"><i class="fas fa-plus"></i>Add Contact</button>
            <button class="btn btn-success btn-sm" onclick="exportContactsExcel()"><i class="fas fa-file-excel"></i>Export</button>
          </div>
        </div>
        <div class="card-body" style="padding-bottom:0;">
          <div class="cat-chips" id="catChips">
            <span class="cat-chip active" data-cat="all" onclick="selectCat(this,'all')">📋 All</span>
            <span class="cat-chip" data-cat="bank" onclick="selectCat(this,'bank')">🏦 Bank</span>
            <span class="cat-chip" data-cat="ca" onclick="selectCat(this,'ca')">📊 CA / Accountant</span>
            <span class="cat-chip" data-cat="lawyer" onclick="selectCat(this,'lawyer')">⚖️ Lawyer</span>
            <span class="cat-chip" data-cat="property" onclick="selectCat(this,'property')">🏠 Property Dealer</span>
            <span class="cat-chip" data-cat="vehicle" onclick="selectCat(this,'vehicle')">🚗 Vehicle Showroom</span>
            <span class="cat-chip" data-cat="others" onclick="selectCat(this,'others')">📁 Others</span>
          </div>
        </div>
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input class="search-input" id="contactSearch" placeholder="Search name, phone, city, company…" oninput="filterContacts()">
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Category</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Company</th>
                <th>City</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="contactsBody">
              <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- ================================================================ -->
    <!-- ADD CONTACT MODAL - WITH UNIQUE IDs -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="addContactModal">
        <div class="modal-box wide">
            <div class="modal-header">
                <span class="modal-title" id="contactModalTitle"><i class="fas fa-user-plus"></i>Add Contact</span>
                <button class="modal-close" onclick="closeModal('addContactModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <!-- Hidden field for edit mode -->
                <input type="hidden" id="editContactId">
            
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactCategory">Category</label>
                        <select class="form-select" id="contactCategory">
                            <option value="bank">🏦 Bank</option>
                            <option value="ca">📊 CA / Accountant</option>
                            <option value="lawyer">⚖️ Lawyer</option>
                            <option value="property">🏠 Property Dealer</option>
                            <option value="vehicle">🚗 Vehicle Showroom</option>
                            <option value="others">📁 Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactName">Full Name *</label>
                        <input class="form-input" id="contactName" placeholder="Full name">
                    </div>
                </div>
            
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactRole">Role / Designation</label>
                        <input class="form-input" id="contactRole" placeholder="e.g. Branch Manager">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactPhone">Phone *</label>
                        <input class="form-input" id="contactPhone" placeholder="Phone number" type="tel">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactEmail">Email</label>
                        <input class="form-input" id="contactEmail" placeholder="Email" type="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactCompany">Company / Firm</label>
                        <input class="form-input" id="contactCompany" placeholder="Organization name">
                    </div>
                </div>
            
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactCity">City</label>
                        <input class="form-input" id="contactCity" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactState">State</label>
                        <input class="form-input" id="contactState" placeholder="State">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactPincode">Pincode</label>
                        <input class="form-input" id="contactPincode" placeholder="Pincode">
                    </div>
                </div>
            
                <div class="form-group">
                    <label class="form-label" for="contactNotes">Notes</label>
                    <textarea class="form-textarea" id="contactNotes" placeholder="Specialization, notes…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addContactModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveContact()"><i class="fas fa-save"></i>Save Contact</button>
            </div>
        </div>
    </div>

    <!-- ====== REFERRAL SECTION ====== -->
    <div class="section" id="referralSection">
        <!-- Referral Card -->
        <div class="referral-card">
            <div style="font-size:13px;opacity:0.7;margin-bottom:4px;">Your Referral Code</div>
            <div class="ref-code" id="myRefCode">PART-<?= strtoupper(substr(md5($target_partner_id), 0, 8)) ?></div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);" onclick="copyRefCode()"><i class="fas fa-copy"></i>Copy Code</button>
                <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);" onclick="shareRefLink()"><i class="fas fa-share-alt"></i>Share Link</button>
                <button class="btn btn-sm" style="background:rgba(34,197,94,0.2);color:#22c55e;border:1px solid rgba(34,197,94,0.3);" onclick="openModal('addReferralModal')"><i class="fas fa-user-plus"></i>Add Referral</button>
            </div>
            <div style="margin-top:12px;font-size:12px;opacity:0.6;">Share with bank managers, CAs, lawyers to earn connector commissions on every deal they refer.</div>
        </div>

        <!-- Referral Stats -->
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="stat-card green">
                <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-user-plus"></i></div></div>
                <div class="stat-value" id="refTotalSignups">0</div>
                <div class="stat-label">Total Signups via Code</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-handshake"></i></div></div>
                <div class="stat-value" id="refConversions">0</div>
                <div class="stat-label">Conversions from Refs</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-rupee-sign"></i></div></div>
                <div class="stat-value" id="refEarnings">₹0</div>
                <div class="stat-label">Referral Earnings</div>
                </div>
            <div class="stat-card purple">
                <div class="stat-top"><div class="stat-icon-wrap"><i class="fas fa-star"></i></div></div>
                <div class="stat-value" id="refRank">—</div>
                <div class="stat-label">Referral Rank</div>
            </div>
        </div>

        <!-- Referral List -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i>Referred Partners / Connectors</div>
                <button class="btn btn-primary btn-sm" onclick="openModal('addReferralModal')"><i class="fas fa-plus"></i>Add Referral</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Commission</th>
                            <th>Score</th>
                            <th>Earnings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="refListBody">
                        <tr><td colspan="9"><div class="empty-state"><i class="fas fa-share-alt"></i><p>No referrals yet. Share your code!</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====== MODALS ====== -->

    <!-- Add Referral Modal -->
    <div class="modal-overlay" id="addReferralModal">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-user-plus"></i>Add Referral</span>
                <button class="modal-close" onclick="closeModal('addReferralModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input class="form-input" id="refName" placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input class="form-input" id="refEmail" type="email" placeholder="email@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-input" id="refPhone" placeholder="9876543210">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="refType">
                            <option value="partner">🤝 Partner</option>
                            <option value="connector">🔌 Connector</option>
                            <option value="client">👤 Client</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" id="refNotes" placeholder="Additional notes…"></textarea>
                </div>
                <div style="background:var(--brand-light);border:1px solid var(--brand-muted);border-radius:var(--radius-md);padding:12px 16px;font-size:13px;margin-top:8px;">
                    <i class="fas fa-info-circle" style="color:var(--brand);"></i>
                    This person will be added as a referral using your code: <strong id="modalRefCode">PART-<?= strtoupper(substr(md5($target_partner_id), 0, 8)) ?></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addReferralModal')">Cancel</button>
                <button class="btn btn-primary" onclick="addReferral()"><i class="fas fa-save"></i>Add Referral</button>
            </div>
        </div>
    </div>

    <!-- Edit Referral Modal -->
    <div class="modal-overlay" id="editReferralModal">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title"><i class="fas fa-edit"></i>Edit Referral</span>
                <button class="modal-close" onclick="closeModal('editReferralModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editRefId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input class="form-input" id="editRefName" placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input class="form-input" id="editRefEmail" type="email" placeholder="email@example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-input" id="editRefPhone" placeholder="9876543210">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="editRefType">   
                            <option value="partner">🤝 Partner</option>
                            <option value="connector">🔌 Connector</option>
                            <option value="client">👤 Client</option>
                        </select>
                    </div>
                </div>
            
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editRefStatus">
                            <option value="registered">Registered</option>
                            <option value="converted">Converted</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- ========== ADD COMMISSION FIELDS HERE ========== -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commission Rate (%)</label>
                        <input class="form-input" id="editRefCommission" type="number" value="10" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Earnings (₹)</label>
                        <input class="form-input" id="editRefEarnings" type="number" value="0" min="0" step="0.01">
                    </div>
                </div>
                <!-- ================================================ -->

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" id="editRefNotes" placeholder="Additional notes…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editReferralModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateReferral()"><i class="fas fa-save"></i>Update Referral</button>
            </div>
        </div>
    </div>

    <!-- ====== PERFORMANCE ====== -->
    <div class="section" id="performanceSection">
      <div class="kpi-row">
        <div class="kpi-box"><div class="kpi-val" id="perfConvRate">—</div><div class="kpi-lbl">Conversion Rate</div></div>
        <div class="kpi-box"><div class="kpi-val" id="perfAvgDeal">—</div><div class="kpi-lbl">Avg Deal Value</div></div>
        <div class="kpi-box"><div class="kpi-val" id="perfResponseTime">—</div><div class="kpi-lbl">Avg Response (hrs)</div></div>
        <div class="kpi-box"><div class="kpi-val" id="perfRank">#—</div><div class="kpi-lbl">Leaderboard Rank</div></div>
        <div class="kpi-box"><div class="kpi-val" id="perfRating">—★</div><div class="kpi-lbl">Partner Rating</div></div>
      </div>

      <div class="charts-row">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i>Monthly Performance</div></div>
          <div class="card-body"><div style="position:relative;height:220px;"><canvas id="perfChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i>Lead Sources</div></div>
          <div class="card-body"><div style="position:relative;height:220px;"><canvas id="sourceChart"></canvas></div></div>
        </div>
      </div>

      <!-- Target Progress -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-bullseye"></i>Monthly Target</div></div>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
            <span>Leads this month</span>
            <span id="targetText">0 / 20</span>
          </div>
          <div class="progress-bar" style="height:10px;">
            <div class="progress-fill" id="targetProgress" style="width:0%;background:linear-gradient(90deg,var(--brand),#34d399);"></div>
          </div>
          <p id="targetMsg" style="font-size:12px;color:var(--text-muted);margin-top:8px;"></p>
        </div>
      </div>

      <!-- Leaderboard -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-trophy"></i>Partner Leaderboard</div></div>
        <div class="table-wrap">
          <table><thead><tr><th>Rank</th><th>Partner</th><th>Leads</th><th>Converted</th><th>Commission</th><th>Tier</th></tr></thead>
            <tbody id="leaderboardBody"></tbody>
          </table>
        </div>
      </div>

      <!-- Badges -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-medal"></i>Achievements</div></div>
        <div class="card-body">
          <div style="display:flex;flex-wrap:wrap;gap:12px;" id="badgesGrid"></div>
        </div>
      </div>
    </div>

    <!-- ====== TICKETS ====== -->
    <div class="section" id="ticketsSection">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-headset"></i>Support Tickets</div>
          <button class="btn btn-primary btn-sm" onclick="openModal('ticketModal')"><i class="fas fa-plus"></i>New Ticket</button>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>#</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th>Updated</th><th>Actions</th></tr></thead>
            <tbody id="ticketsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ====== PROFILE ====== -->
    <div class="section" id="profileSection">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-user"></i>Personal Info</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" id="profName" value="<?= $user_name ?>"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="profEmail" value="<?= $user_email ?>" readonly></div>
              <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="profPhone" placeholder="Mobile number"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Company / Store</label><input class="form-input" id="profCompany" placeholder="Business name"></div>
              <div class="form-group"><label class="form-label">City</label><input class="form-input" id="profCity" placeholder="Your city"></div>
            </div>
            <?php if ($viewer_role === 'partner'): ?>
            <button class="btn btn-primary" onclick="updateProfile()"><i class="fas fa-save"></i>Save Changes</button>
            <?php else: ?>
            <span style="color: var(--text-muted); font-size: 12px;">Profile editing is disabled in admin view.</span>
            <?php endif; ?>
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
            <?php if ($viewer_role === 'partner'): ?>
            <button class="btn btn-primary" onclick="changePassword()"><i class="fas fa-key"></i>Change Password</button>
            <?php else: ?>
            <span style="color: var(--text-muted); font-size: 12px;">Password change is disabled in admin view.</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-university"></i>Bank Details</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group"><label class="form-label">Bank Name</label><input class="form-input" id="bankName" placeholder="HDFC Bank"></div>
              <div class="form-group"><label class="form-label">Account Number</label><input class="form-input" id="bankAcc" placeholder="Account number"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">IFSC Code</label><input class="form-input" id="bankIFSC" placeholder="HDFC0001234"></div>
              <div class="form-group"><label class="form-label">Account Holder</label><input class="form-input" id="bankHolder" placeholder="Name as in bank"></div>
            </div>
            <?php if ($viewer_role === 'partner'): ?>
            <button class="btn btn-primary" onclick="saveBankDetails()"><i class="fas fa-save"></i>Save Bank Details</button>
            <?php else: ?>
            <span style="color: var(--text-muted); font-size: 12px;">Bank details editing is disabled in admin view.</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-lock"></i>Change Password</div></div>
          <div class="card-body">
            <?php if ($viewer_role === 'partner'): ?>
            <form id="changePasswordForm">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Current Password <span style="color:#dc3545;">*</span></label>
                  <input class="form-input" type="password" id="currentPassword" placeholder="Enter current password" required>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">New Password <span style="color:#dc3545;">*</span></label>
                  <input class="form-input" type="password" id="newPassword" placeholder="Enter new password (min 8 chars)" required minlength="8">
                  <small style="color:var(--text-muted);font-size:11px;">Password must be at least 8 characters</small>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Confirm New Password <span style="color:#dc3545;">*</span></label>
                  <input class="form-input" type="password" id="confirmPassword" placeholder="Confirm new password" required>
                </div>
              </div>
      
              <!-- Password Strength Indicator -->
              <div id="passwordStrength" style="display:none;margin-top:8px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:13px;">
                  <span>Password Strength:</span>
                  <span id="strengthText" style="font-weight:600;color:#dc2626;">Weak</span>
                </div>
                <div class="progress-bar" style="height:4px;margin-top:4px;background:var(--bg-sunken);border-radius:99px;overflow:hidden;">
                  <div id="strengthBar" class="progress-fill" style="width:0%;background:#dc2626;height:100%;border-radius:99px;transition:width 0.3s ease;"></div>
                </div>
              </div>
      
              <!-- Password Match Indicator -->
              <div id="passwordMatch" style="display:none;margin-top:8px;font-size:13px;font-weight:500;"></div>
      
              <!-- Password Requirements -->
              <div class="password-requirements" style="font-size:12px;color:var(--text-muted);padding:8px 12px;background:var(--bg-sunken);border-radius:var(--r-md);margin-top:8px;">
                <strong>Password must contain:</strong>
                <ul style="margin:4px 0 0 16px;padding:0;">
                  <li id="reqLength" style="list-style:none;padding:2px 0;">❌ At least 8 characters</li>
                  <li id="reqUppercase" style="list-style:none;padding:2px 0;">❌ At least 1 uppercase letter</li>
                  <li id="reqLowercase" style="list-style:none;padding:2px 0;">❌ At least 1 lowercase letter</li>
                  <li id="reqNumber" style="list-style:none;padding:2px 0;">❌ At least 1 number</li>
                  <li id="reqSpecial" style="list-style:none;padding:2px 0;">❌ At least 1 special character (!@#$%^&*)</li>
                </ul>
              </div>
      
              <button type="submit" class="btn btn-primary" style="margin-top:12px;">
                <i class="fas fa-key"></i> Change Password
              </button>
            </form>
            <?php else: ?>
            <span style="color: var(--text-muted); font-size: 12px;">Password change is disabled in admin view.</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ================================================================
     MODALS
     ================================================================ -->

<!-- Add Lead -->
<div class="modal-overlay" id="addLeadModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-plus-circle"></i>Add New Lead</span>
      <button class="modal-close" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Customer Name *</label><input class="form-input" id="lName" placeholder="Full name"></div>
        <div class="form-group"><label class="form-label">Phone *</label><input class="form-input" id="lPhone" placeholder="Mobile number" type="tel"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="lEmail" placeholder="Email" type="email"></div>
        <div class="form-group"><label class="form-label">City</label><input class="form-input" id="lCity" placeholder="City"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Service *</label>
          <select class="form-select" id="lService">
            <option>Written Off Clearance</option>
            <option>Settled Clearance</option>
            <option>Suit Filed Clearance</option>
            <option>Credit Report Analysis</option>
            <option>Profile Correction</option>
            <option>Wrong Entry Clearance</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Source</label>
          <select class="form-select" id="lSource">
            <option>Website</option>
            <option>Referral</option>
            <option>Social Media</option>
            <option>Cold Call</option>
            <option>Walk-in</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">CIBIL Score (approx)</label><input class="form-input" id="lScore" type="number" placeholder="e.g. 620" min="300" max="900"></div>
        <div class="form-group"><label class="form-label">Loan Amount (₹)</label><input class="form-input" id="lLoanAmt" type="number" placeholder="e.g. 500000"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" id="lNotes" placeholder="Additional notes…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addLeadModal')">Cancel</button>
      <button class="btn btn-primary" id="addLeadBtn" onclick="addLead()"><i class="fas fa-save"></i>Save Lead</button>
    </div>
  </div>
</div>

<!-- Update Lead Status -->
<div class="modal-overlay" id="updateLeadModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-edit"></i>Update Lead</span>
      <button class="modal-close" onclick="closeModal('updateLeadModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="updateLeadId">
      <div class="form-group" style="margin-bottom:12px;">
        <label class="form-label">Status</label>
        <select class="form-select" id="updateLeadStatus">
          <option value="new">New</option>
          <option value="contacted">Contacted</option>
          <option value="converted">Converted</option>
          <option value="lost">Lost</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Update Note</label>
        <textarea class="form-textarea" id="updateLeadNote" placeholder="What happened with this lead?"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('updateLeadModal')">Cancel</button>
      <button class="btn btn-primary" onclick="updateLeadStatus()"><i class="fas fa-save"></i>Update</button>
    </div>
  </div>
</div>

<!-- Add Follow-up -->
<div class="modal-overlay" id="addFollowupModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-calendar-plus"></i>Schedule Follow-up</span>
            <button class="modal-close" onclick="closeModal('addFollowupModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Select Lead</label>
                    <select class="form-select" id="fuLeadSelect">
                        <option value="">Select lead…</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Follow-up Date & Time *</label>
                    <input class="form-input" type="datetime-local" id="fuDate">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="fuNotes" placeholder="What to discuss, reminder notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('addFollowupModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveFollowup()"><i class="fas fa-save"></i>Schedule</button>
        </div>
    </div>
</div>

<!-- Payout Request -->
<div class="modal-overlay" id="payoutModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-money-bill-wave"></i>Request Payout</span>
      <button class="modal-close" onclick="closeModal('payoutModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:var(--brand-light);border:1px solid var(--brand-muted);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;font-size:13px;">
        Available for payout: <strong id="modalAvail">—</strong>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Amount (₹) *</label><input class="form-input" id="payAmt" type="number" placeholder="Minimum ₹1,000" min="1000" step="100"></div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select class="form-select" id="payMethod">
            <option>Bank Transfer (NEFT/IMPS)</option>
            <option>UPI</option>
            <option>RTGS</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">UPI ID / Account Note</label><input class="form-input" id="payNote" placeholder="UPI ID or additional note"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('payoutModal')">Cancel</button>
      <button class="btn btn-primary" id="payoutSubmitBtn" onclick="requestPayout()"><i class="fas fa-paper-plane"></i>Submit Request</button>
    </div>
  </div>
</div>

<!-- Add Connector -->
<div class="modal-overlay" id="addConnectorModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-plug"></i>Add Connector</span>
      <button class="modal-close" onclick="closeModal('addConnectorModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Full Name *</label><input class="form-input" id="connName" placeholder="Name"></div>
        <div class="form-group"><label class="form-label">Phone *</label><input class="form-input" id="connPhone" placeholder="Phone" type="tel"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="connEmail" placeholder="Email" type="email"></div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select class="form-select" id="connType">
            <option value="bank">Bank Representative</option>
            <option value="ca">CA / Accountant</option>
            <option value="lawyer">Lawyer</option>
            <option value="property">Property Dealer</option>
            <option value="vehicle">Vehicle Showroom</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Company</label><input class="form-input" id="connCompany" placeholder="Organization name"></div>
        <div class="form-group"><label class="form-label">City</label><input class="form-input" id="connCity" placeholder="City"></div>
      </div>
      <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="connNotes" placeholder="Additional info…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addConnectorModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveConnector()"><i class="fas fa-save"></i>Save Connector</button>
    </div>
  </div>
</div>

<!-- Add Contact -->
<div class="modal-overlay" id="addContactModal">
  <div class="modal-box wide">
    <div class="modal-header">
      <span class="modal-title" id="contactModalTitle"><i class="fas fa-user-plus"></i>Add Contact</span>
      <button class="modal-close" onclick="closeModal('addContactModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editContactId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-select" id="cCat">
            <option value="bank">🏦 Bank</option>
            <option value="ca">📊 CA</option>
            <option value="lawyer">⚖️ Lawyer</option>
            <option value="property">🏠 Property</option>
            <option value="vehicle">🚗 Vehicle</option>
            <option value="others">📁 Others</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Full Name *</label><input class="form-input" id="cName" placeholder="Full name"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Role/Designation</label><input class="form-input" id="cRole" placeholder="e.g. Branch Manager"></div>
        <div class="form-group"><label class="form-label">Phone *</label><input class="form-input" id="cPhone" placeholder="Phone" type="tel"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="cEmail" placeholder="Email" type="email"></div>
        <div class="form-group"><label class="form-label">Company / Firm</label><input class="form-input" id="cCompany" placeholder="Organization"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">City</label><input class="form-input" id="cCity" placeholder="City"></div>
        <div class="form-group"><label class="form-label">State</label><input class="form-input" id="cState" placeholder="State"></div>
        <div class="form-group"><label class="form-label">Pincode</label><input class="form-input" id="cPin" placeholder="Pincode"></div>
      </div>
      <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="cNotes" placeholder="Specialization, notes…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addContactModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveContact()"><i class="fas fa-save"></i>Save Contact</button>
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
        <div class="form-group"><label class="form-label">Subject *</label><input class="form-input" id="tSubject" placeholder="Briefly describe your issue"></div>
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

<!-- Bulk Import -->
<div class="modal-overlay" id="bulkImportModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-file-upload"></i>Bulk Import Leads</span>
      <button class="modal-close" onclick="closeModal('bulkImportModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:var(--bg-sunken);border:2px dashed var(--border-strong);border-radius:var(--radius-lg);padding:24px;text-align:center;margin-bottom:16px;">
        <i class="fas fa-cloud-upload-alt" style="font-size:32px;color:var(--brand);margin-bottom:10px;display:block;"></i>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px;">Upload CSV file with columns: name, phone, email, service, source</p>
        <input type="file" id="bulkFile" accept=".csv,.xlsx" style="display:none;" onchange="handleBulkFile()">
        <button class="btn btn-primary" onclick="document.getElementById('bulkFile').click()"><i class="fas fa-folder-open"></i>Choose File</button>
        <p id="bulkFileName" style="font-size:12px;color:var(--text-muted);margin-top:8px;"></p>
      </div>
      <div id="bulkPreview" style="display:none;max-height:200px;overflow-y:auto;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('bulkImportModal')">Cancel</button>
      <button class="btn btn-primary" id="bulkImportBtn" onclick="processBulkImport()" disabled><i class="fas fa-upload"></i>Import Leads</button>
    </div>
  </div>
</div>

<!-- ================================================================
     TOAST CONTAINER
     ================================================================ -->
<div class="toast-container" id="toastContainer"></div>

<!-- ================================================================
     AI ASSISTANT
     ================================================================ -->
<div class="ai-panel" id="aiPanel">
  <div class="ai-header" id="aiHeader">
    <div class="ai-status-dot"></div>
    <div>
      <div class="ai-title">Partner AI Assistant</div>
      <div class="ai-subtitle">Powered by Claude</div>
    </div>
    <i class="fas fa-chevron-down ai-chevron" id="aiChevron"></i>
  </div>
  <div class="ai-body" id="aiBody">
    <div class="ai-messages" id="aiMessages">
      <div class="ai-msg bot">
        👋 Hi <?= $user_name ?>! I'm your AI partner assistant.<br><br>
        I can help with lead conversion tips, commission calculations, tier upgrades, scoring advice, and your business performance. What would you like to know?
      </div>
    </div>
    <div class="ai-chips">
      <span class="ai-chip" onclick="quickAsk('How to convert more leads?')">📈 Convert leads</span>
      <span class="ai-chip" onclick="quickAsk('Show my commission summary')">💰 Commission</span>
      <span class="ai-chip" onclick="quickAsk('Partner tier benefits explained')">🏆 Tier benefits</span>
      <span class="ai-chip" onclick="quickAsk('Lead scoring tips for credit repair')">🎯 Lead scoring</span>
      <span class="ai-chip" onclick="quickAsk('How to reach Platinum tier?')">💎 Reach Platinum</span>
    </div>
    <div class="ai-input-row">
      <input class="ai-input" id="aiInput" placeholder="Ask me anything…" onkeydown="if(event.key==='Enter') sendAI()">
      <button class="ai-send" onclick="sendAI()"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>
</div>

<!-- ================================================================ -->
<!-- JAVASCRIPT - ALL CODE INSIDE SCRIPT TAGS -->
<!-- ================================================================ -->
<script>
// ── CONFIG ──────────────────────────────────────────────────────────
const API   = 'api/partner/';
const SCORE = 'api/lead-scoring/';
const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content || '';
const PARTNER_ID = <?= $target_partner_id ?>;
const PARTNER_NAME = <?= json_encode($partner['name'] ?? 'Partner') ?>;
const TIER = {
  level:      <?= $current_tier ?>,
  name:       <?= json_encode($tier_info['name']) ?>,
  commission: <?= $tier_info['commission'] ?>,
  icon:       <?= json_encode($tier_info['icon']) ?>
};
const VIEWER_ROLE = <?= json_encode($viewer_role) ?>;
const IS_ADMIN_VIEW = <?= $is_admin_view ? 'true' : 'false' ?>;

// ── XSS ESCAPE ──────────────────────────────────────────────────────
function esc(s) {
  if (s == null) return '';
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#x27;'}[c]));
}

// ── CSRF FETCH ───────────────────────────────────────────────────────
async function apiFetch(url, opts = {}) {
    opts.headers = { 
        'Content-Type': 'application/json', 
        'X-CSRF-Token': CSRF, 
        ...(opts.headers || {}) 
    };
    opts.credentials = 'include';
    opts.mode = 'cors';
    
    try {
        const separator = url.includes('?') ? '&' : '?';
        const urlWithCache = url + separator + '_t=' + Date.now();
        
        const r = await fetch(urlWithCache, opts);
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return await r.json();
    } catch(e) {
        console.error('API error:', url, e);
        return { success: false, error: e.message };
    }
}

// ── THEME ────────────────────────────────────────────────────────────
function setTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('partnerTheme', t);
  const lightBtn = document.getElementById('lightBtn');
  const darkBtn = document.getElementById('darkBtn');
  if (lightBtn) lightBtn.classList.toggle('active', t === 'light');
  if (darkBtn) darkBtn.classList.toggle('active', t === 'dark');
  setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(function() {
  const saved = localStorage.getItem('partnerTheme') || 'light';
  setTheme(saved);
})();

// ── CLOCK ────────────────────────────────────────────────────────────
(function tick() {
  const el = document.getElementById('liveClock');
  if (el) el.textContent = new Date().toLocaleTimeString('en-IN', { hour12: false });
  setTimeout(tick, 1000);
})();

// ── SIDEBAR ──────────────────────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const toggleIcon = document.getElementById('sidebarToggleIcon');
let sidebarCollapsed = localStorage.getItem('partnerSidebarCollapsed') === 'true';

function applySidebar() {
  if (!sidebar) return;
  sidebar.classList.toggle('collapsed', sidebarCollapsed);
  if (toggleIcon) toggleIcon.className = sidebarCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
  const main = document.getElementById('main');
  if (main) main.style.marginLeft = sidebarCollapsed ? '64px' : 'var(--sidebar-w)';
}
applySidebar();

const sidebarToggle = document.getElementById('sidebarToggle');
if (sidebarToggle) {
  sidebarToggle.onclick = () => {
    sidebarCollapsed = !sidebarCollapsed;
    localStorage.setItem('partnerSidebarCollapsed', sidebarCollapsed);
    applySidebar();
  };
}

document.querySelectorAll('.nav-item[data-section]').forEach(item => {
  item.addEventListener('click', () => {
    showSection(item.dataset.section);
    if (window.innerWidth < 900 && sidebar) sidebar.classList.remove('mobile-open');
  });
});

// ── SECTIONS ─────────────────────────────────────────────────────────
const sectionTitles = {
  dashboard: 'Partner Dashboard', leads: 'My Leads', scoring: 'Lead Scoring',
  customers: 'My Customers', followups: 'Follow-up Manager',
  commission: 'Commission History', payouts: 'Payout Management',
  connectors: 'My Connectors', contacts: 'Contact Directory',
  referral: 'Referral Program', performance: 'Performance Analytics',
  tickets: 'Support Tickets', profile: 'My Profile'
};

function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  const el = document.getElementById(name + 'Section');
  if (el) el.classList.add('active');
  document.getElementById('pageTitle').textContent = sectionTitles[name] || name;
  const nav = document.querySelector(`.nav-item[data-section="${name}"]`);
  if (nav) nav.classList.add('active');

  console.log('🔄 Showing section:', name);
  
  switch(name) {
    case 'dashboard':
      loadDashboard();
      break;
    case 'leads':
      loadLeads();
      break;
    case 'scoring':
      loadScoredLeads();
      break;
    case 'customers':
      loadCustomers();
      break;
    case 'followups':
      loadFollowups();
      break;
    case 'commission':
      loadCommission();
      break;
    case 'payouts':
      loadPayouts();
      break;
    case 'connectors':
      loadConnectors();
      break;
    case 'contacts':
      loadContacts();
      break;
    case 'performance':
      loadPerformance();
      break;
    case 'tickets':
      loadTickets();
      break;
    case 'profile':
      loadProfile();
      break;
    case 'referral':
      loadReferralEarnings();
      loadReferralLinks();
      break;
    default:
      console.log('No load function for section:', name);
  }
}

// ── TOAST ─────────────────────────────────────────────────────────────
function toast(msg, type = 'info', duration = 3500) {
  const icons = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<div class="toast-icon"><i class="fas ${icons[type]}"></i></div><span class="toast-msg">${esc(msg)}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 200); }, duration);
}

// ── MODAL ─────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ── BADGES HELPER ────────────────────────────────────────────────────
function statusBadge(s) {
  const map = { new:'badge-blue', contacted:'badge-amber', converted:'badge-green', lost:'badge-red',
    active:'badge-green', inactive:'badge-red', pending:'badge-amber', paid:'badge-green',
    resolved:'badge-green', open:'badge-blue', closed:'badge-gray', completed:'badge-green',
    registered:'badge-blue', earned:'badge-green' };
  return `<span class="badge ${map[(s||'').toLowerCase()] || 'badge-gray'}">${esc(s||'—')}</span>`;
}

function priorityBadge(score) {
  if (score >= 70) return `<span class="badge" style="background:#fef2f2;color:#dc2626;">🔴 Urgent</span>`;
  if (score >= 50) return `<span class="badge" style="background:#fff7ed;color:#ea580c;">🟠 High</span>`;
  if (score >= 30) return `<span class="badge badge-amber">🟡 Medium</span>`;
  return `<span class="badge badge-gray">⚪ Low</span>`;
}

function scoreBar(score) {
  const color = score >= 70 ? '#dc2626' : score >= 50 ? '#ea580c' : score >= 30 ? '#d97706' : '#9ca3af';
  return `<div class="score-bar-wrap"><div class="score-bar"><div class="score-bar-fill" style="width:${score}%;background:${color};"></div></div><span class="score-num" style="color:${color};">${score}</span></div>`;
}

// ── CHARTS ────────────────────────────────────────────────────────────
const charts = {};
function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = () => isDark() ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
const textColor = () => isDark() ? '#94a3b8' : '#6b7280';

function initDashCharts(monthly = [0,0,0,0,0,0,0], leadCounts = [0,0,0,0]) {
  destroyChart('commChart'); destroyChart('leadStatusChart');

  const commChart = document.getElementById('commissionChart');
  if (commChart) {
    charts['commChart'] = new Chart(commChart, {
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
        datasets: [{ label: 'Commission (₹)', data: monthly,
          borderColor: '#0d9e78', backgroundColor: 'rgba(13,158,120,0.08)',
          fill: true, tension: 0.4, pointRadius: 4, pointHoverRadius: 6 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: gridColor() }, ticks: { color: textColor() } },
          y: { grid: { color: gridColor() }, ticks: { color: textColor(), callback: v => '₹' + v.toLocaleString('en-IN') } }
        }
      }
    });
  }

  const leadStatusChart = document.getElementById('leadStatusChart');
  if (leadStatusChart) {
    charts['leadStatusChart'] = new Chart(leadStatusChart, {
      type: 'doughnut',
      data: {
        labels: ['New', 'Contacted', 'Converted', 'Lost'],
        datasets: [{ data: leadCounts, backgroundColor: ['#2563eb','#d97706','#059669','#dc2626'], borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { labels: { color: textColor(), font: { size: 11 } } } } }
    });
  }
}

function switchDashChart(mode) {
  destroyChart('commChart');
  const labels = mode === 'weekly' ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] : ['Jan','Feb','Mar','Apr','May','Jun','Jul'];
  const data   = mode === 'weekly' ? [0,0,0,0,0,0,0] : [0,0,0,0,0,0,0];
  const commChart = document.getElementById('commissionChart');
  if (commChart) {
    charts['commChart'] = new Chart(commChart, {
      type: 'line',
      data: { labels, datasets: [{ label: 'Commission (₹)', data, borderColor: '#0d9e78', backgroundColor: 'rgba(13,158,120,0.08)', fill: true, tension: 0.4, pointRadius: 4 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
        scales: { x: { grid: { color: gridColor() }, ticks: { color: textColor() } }, y: { grid: { color: gridColor() }, ticks: { color: textColor(), callback: v => '₹' + v.toLocaleString('en-IN') } } } }
    });
  }
}

// ── DEMO DATA FOR ADMIN VIEW ──────────────────────────────────────────
function getDemoLeads(partnerId) {
    const demoLeads = [];
    const names = ['Rajesh Kumar', 'Priya Sharma', 'Amit Singh', 'Neha Patel', 'Vikram Reddy', 'Sneha Gupta', 'Rahul Jain', 'Pooja Desai', 'Sanjay Mehta', 'Anita Verma'];
    const services = ['Written Off Clearance', 'Settled Clearance', 'Credit Report Analysis', 'Profile Correction', 'Suit Filed Clearance'];
    const statuses = ['new', 'contacted', 'converted', 'lost'];
    const sources = ['direct', 'referral', 'connector'];
    const sourceNames = ['Direct', 'Referral Partner', 'Connector Name'];
    
    for (let i = 0; i < 10; i++) {
        const date = new Date();
        date.setDate(date.getDate() - i * 2);
        const status = statuses[i % statuses.length];
        const source = sources[i % sources.length];
        demoLeads.push({
            id: 1000 + i,
            customer_name: names[i % names.length],
            customer_phone: '987654' + String(1000 + i).padStart(4, '0'),
            customer_email: 'customer' + (i + 1) + '@example.com',
            service_type: services[i % services.length],
            status: status,
            source_type: source,
            source_name: source !== 'direct' ? sourceNames[i % 3] : null,
            created_at: date.toISOString(),
            tier_id: 1,
            estimated_amount: 5000 + (i * 2500)
        });
    }
    return demoLeads;
}

function getDemoCustomers(partnerId) {
    const names = ['Rajesh Kumar', 'Priya Sharma', 'Amit Singh', 'Neha Patel', 'Vikram Reddy'];
    const services = ['Written Off Clearance', 'Settled Clearance', 'Credit Report Analysis', 'Profile Correction'];
    
    return names.map((name, i) => ({
        id: 2000 + i,
        name: name,
        email: name.toLowerCase().replace(' ', '.') + '@example.com',
        phone: '987654' + String(2000 + i).padStart(4, '0'),
        service_type: services[i % services.length],
        status: 'active',
        joined: new Date(Date.now() - i * 86400000 * 5).toLocaleDateString('en-IN')
    }));
}

// ── DASHBOARD ─────────────────────────────────────────────────────────
async function loadDashboard() {
  try {
    // Try API first
    const data = await apiFetch(`${API}get_dashboard.php?partner_id=${PARTNER_ID}`);
    const d = data.success ? data : {};
    
    let total = d.total_leads || 0;
    let conv = d.converted_customers || 0;
    let comm = d.total_commission || 0;
    let pending = d.pending_payout || 0;
    let followupsDue = d.followups_due || 0;
    
    // If API failed, use demo data for admin view
    if (!data.success && IS_ADMIN_VIEW) {
        const demoLeads = getDemoLeads(PARTNER_ID);
        total = demoLeads.length;
        conv = demoLeads.filter(l => l.status === 'converted').length;
        comm = conv * 5000 * (TIER.commission / 100);
        pending = Math.round(comm * 0.3);
        followupsDue = 2;
        console.log('📊 Using demo data for admin view');
        toast('Showing demo data (Admin View)', 'info', 3000);
    }
    
    const rate = total > 0 ? Math.round((conv / total) * 100) : 0;

    const statTotalLeads = document.getElementById('statTotalLeads');
    const statConverted = document.getElementById('statConverted');
    const statCommission = document.getElementById('statCommission');
    const statPending = document.getElementById('statPending');
    const statConvRate = document.getElementById('statConvRate');
    const statFollowups = document.getElementById('statFollowups');
    const modalAvail = document.getElementById('modalAvail');
    const statLeadChange = document.getElementById('statLeadChange');
    const statConvChange = document.getElementById('statConvChange');
    const statCrChange = document.getElementById('statCrChange');
    const statLeadBar = document.getElementById('statLeadBar');
    const statConvBar = document.getElementById('statConvBar');
    const statCrBar = document.getElementById('statCrBar');
    const sideLeadBadge = document.getElementById('sideLeadBadge');

    if (statTotalLeads) statTotalLeads.textContent = total;
    if (statConverted) statConverted.textContent = conv;
    if (statCommission) statCommission.textContent = '₹' + comm.toLocaleString('en-IN');
    if (statPending) statPending.textContent = '₹' + pending.toLocaleString('en-IN');
    if (statConvRate) statConvRate.textContent = rate + '%';
    if (statFollowups) statFollowups.textContent = followupsDue;
    if (modalAvail) modalAvail.textContent = '₹' + pending.toLocaleString('en-IN');
    if (statLeadChange) statLeadChange.textContent = total > 0 ? `+${total} total` : '0';
    if (statConvChange) statConvChange.textContent = conv + ' converted';
    if (statCrChange) statCrChange.textContent = rate + '%';
    if (statLeadBar) statLeadBar.style.width = Math.min(total, 100) + '%';
    if (statConvBar) statConvBar.style.width = Math.min(conv * 4, 100) + '%';
    if (statCrBar) statCrBar.style.width = rate + '%';
    if (sideLeadBadge) sideLeadBadge.textContent = total;

    const monthly = (d.monthly_commission || []).map(m => m.total || 0).slice(0, 7);
    while (monthly.length < 7) monthly.push(0);
    
    // If using demo data, generate monthly data
    if (!data.success && IS_ADMIN_VIEW) {
        for (let i = 0; i < 7; i++) {
            monthly[i] = Math.round(comm / 7 * (0.5 + Math.random() * 0.5));
        }
    }
    
    const leadCounts = [d.new_leads||0, d.contacted_leads||0, conv, d.lost_leads||0];
    if (!data.success && IS_ADMIN_VIEW) {
        leadCounts[0] = Math.round(total * 0.4);
        leadCounts[1] = Math.round(total * 0.25);
        leadCounts[3] = Math.round(total * 0.15);
    }
    initDashCharts(monthly, leadCounts);

    const tbody = document.getElementById('recentActivityBody');
    if (tbody) {
      if (d.recent_activity && d.recent_activity.length) {
        tbody.innerHTML = d.recent_activity.map(a => `
          <tr>
            <td>${esc(a.activity)}</td>
            <td><strong>${esc(a.customer)}</strong></td>
            <td>${new Date(a.date).toLocaleDateString('en-IN')}</td>
            <td>${statusBadge(a.status)}</td>
            <td>${a.amount ? '₹' + Number(a.amount).toLocaleString('en-IN') : '—'}</td>
          </tr>`).join('');
      } else if (IS_ADMIN_VIEW) {
        // Show demo activity
        const activities = ['Lead Added', 'Lead Converted', 'Follow-up Scheduled', 'Commission Earned', 'Payout Requested'];
        tbody.innerHTML = activities.map((a, i) => `
          <tr>
            <td>${a}</td>
            <td><strong>Customer ${i + 1}</strong></td>
            <td>${new Date(Date.now() - i * 86400000).toLocaleDateString('en-IN')}</td>
            <td>${statusBadge(i % 3 === 0 ? 'converted' : 'new')}</td>
            <td>${i % 2 === 0 ? '₹' + ((i + 1) * 2500).toLocaleString('en-IN') : '—'}</td>
          </tr>`).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fas fa-history"></i><p>No recent activity</p></div></td></tr>`;
      }
    }
  } catch(e) {
    console.error('Dashboard error:', e);
    // Show dashboard with demo data on error
    if (IS_ADMIN_VIEW) {
        toast('Using demo data - API unavailable', 'info', 3000);
        // Try to show basic stats
        const demoLeads = getDemoLeads(PARTNER_ID);
        document.getElementById('statTotalLeads').textContent = demoLeads.length;
        document.getElementById('statConverted').textContent = demoLeads.filter(l => l.status === 'converted').length;
        document.getElementById('statCommission').textContent = '₹' + (demoLeads.length * 5000).toLocaleString('en-IN');
    }
  }
}

// ── LEADS ─────────────────────────────────────────────────────────────
let allLeads = [];

function getTierCommission(tierId) {
    const tiers = { 1: 20, 2: 25, 3: 30, 4: 35, 5: 40 };
    return tiers[tierId] || 20;
}

async function loadLeads() {
    const tbody = document.getElementById('leadsBody');
    if (!tbody) {
        console.error('leadsBody element not found!');
        return;
    }
    
    console.log('🔄 Loading leads...');
    tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><div class="spinner"></div> Loading leads...</div></td></tr>`;
    
    // If admin view, use demo data immediately to avoid hanging
    if (IS_ADMIN_VIEW) {
        console.log('📊 Admin view - using demo leads');
        allLeads = getDemoLeads(PARTNER_ID);
        setTimeout(() => {
            renderLeads(allLeads);
            const sideLeadBadge = document.getElementById('sideLeadBadge');
            if (sideLeadBadge) sideLeadBadge.textContent = allLeads.length;
            toast('Showing demo leads (Admin View)', 'info', 2000);
        }, 500);
        return;
    }
    
    try {
        const status = document.getElementById('leadStatusFilter')?.value || '';
        const search = document.getElementById('leadSearch')?.value || '';
        const source = document.getElementById('leadSourceFilter')?.value || '';
        
        let url = `api/partner/get_leads.php`;
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (search) params.append('search', search);
        if (source) params.append('source_type', source);
        params.append('partner_id', PARTNER_ID);
        if (params.toString()) url += '?' + params.toString();
        
        console.log('📡 Fetching leads from:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF || ''
            },
            credentials: 'include'
        });
        
        const text = await response.text();
        console.log('📥 Raw response length:', text.length);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error('❌ Failed to parse JSON:', e);
            tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error: Invalid server response</p></div></td></tr>`;
            return;
        }
        
        console.log('📊 Parsed data:', data);
        
        if (data.success) {
            allLeads = data.leads || [];
            console.log('✅ Loaded ' + allLeads.length + ' leads');
            renderLeads(allLeads);
            const sideLeadBadge = document.getElementById('sideLeadBadge');
            if (sideLeadBadge) sideLeadBadge.textContent = allLeads.length;
        } else {
            console.error('❌ API error:', data.error);
            tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${data.error || 'Failed to load leads'}</p></div></td></tr>`;
        }
    } catch(e) {
        console.error('❌ Error loading leads:', e);
        tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading leads: ${e.message}</p></div></td></tr>`;
    }
}

function renderLeads(leads) {
    const tbody = document.getElementById('leadsBody');
    if (!tbody) return;
    
    console.log('🔄 Rendering ' + (leads ? leads.length : 0) + ' leads');
    
    if (!leads || leads.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-inbox"></i><p>No leads found. Start by adding your first lead!</p></div></td></tr>`;
        return;
    }
    
    tbody.innerHTML = leads.map((l, i) => {
        const name = l.customer_name || l.name || '—';
        const phone = l.customer_phone || l.phone || '—';
        const service = l.service_type || l.service || '—';
        const status = l.status || 'new';
        const created = l.created_at || '—';
        
        let sourceDisplay = '<span class="badge badge-gray">📌 Direct</span>';
        if (l.source_type === 'referral' && l.source_name) {
            sourceDisplay = `<span class="badge badge-blue">🔗 ${esc(l.source_name)}</span>`;
        } else if (l.source_type === 'connector' && l.source_name) {
            sourceDisplay = `<span class="badge badge-purple">🔌 ${esc(l.source_name)}</span>`;
        } else if (l.source_type === 'direct') {
            sourceDisplay = '<span class="badge badge-gray">📌 Direct</span>';
        }
    
        const tierId = l.tier_id || 1;
        const tierCommission = getTierCommission(tierId);
        const serviceAmount = l.estimated_amount || l.service_amount || 5000;
        const commissionAmount = (serviceAmount * tierCommission) / 100;
    
        const commissionDisplay = status === 'converted' 
            ? `<strong style="color:var(--brand);">₹${commissionAmount.toLocaleString('en-IN')}</strong> <span style="font-size:10px;color:var(--text-muted);">(${tierCommission}%)</span>`
            : '<span style="color:var(--text-muted);font-size:12px;">—</span>';
    
        return `
        <tr>
            <td>${i + 1}</td>
            <td><strong>${esc(name)}</strong></td>
            <td>${esc(phone)}</td>
            <td><span class="badge badge-gray" style="font-size:10px;">${esc(service)}</span></td>
            <td>${sourceDisplay}</td>
            <td>${statusBadge(status)}</td>
            <td>${created ? new Date(created).toLocaleDateString('en-IN') : '—'}</td>
            <td>${commissionDisplay}</td>
            <td><span style="color:var(--text-muted);font-size:12px;">—</span></td>
            <td style="white-space:nowrap;">
                <button class="btn btn-ghost btn-xs" onclick="openUpdateLead(${l.id},'${esc(status)}')"><i class="fas fa-edit"></i></button>
                <button class="btn btn-primary btn-xs" onclick="scheduleFollowupFor(${l.id},'${esc(name)}')"><i class="fas fa-calendar-plus"></i></button>
                <button class="btn btn-ghost btn-xs" onclick="calcScore(${l.id})"><i class="fas fa-bullseye"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function filterLeads() {
    const s = document.getElementById('leadSearch')?.value?.toLowerCase() || '';
    const st = document.getElementById('leadStatusFilter')?.value || '';
    const svc = document.getElementById('leadServiceFilter')?.value || '';
    const src = document.getElementById('leadSourceFilter')?.value || '';
    
    const filtered = allLeads.filter(l => {
        const match = !s || (l.customer_name + l.customer_phone + (l.customer_email || '')).toLowerCase().includes(s);
        const stMatch = !st || l.status === st;
        const svcMatch = !svc || (l.service_type || l.service) === svc;
        const srcMatch = !src || l.source_type === src;
        return match && stMatch && svcMatch && srcMatch;
    });
    renderLeads(filtered);
}

// ── CUSTOMERS ─────────────────────────────────────────────────────────
let allCustomers = [];

async function loadCustomers() {
    const tbody = document.getElementById('customersBody');
    if (!tbody) {
        console.error('❌ customersBody not found');
        return;
    }
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8">
                <div class="empty-state">
                    <div class="spinner"></div>
                    <p>Loading customers...</p>
                </div>
            </td>
        </tr>
    `;
    
    // If admin view, use demo data
    if (IS_ADMIN_VIEW) {
        allCustomers = getDemoCustomers(PARTNER_ID);
        setTimeout(() => {
            renderCustomers(allCustomers);
            toast('Showing demo customers (Admin View)', 'info', 2000);
        }, 300);
        return;
    }
    
    try {
        const search = document.getElementById('custSearch')?.value || '';
        const url = `api/partner/get_customers.php?search=${encodeURIComponent(search)}`;
        
        console.log(`📡 Fetching customers from: ${url}`);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF || ''
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            console.warn('⚠️ Empty response from customers API');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                            <p>Empty response from server</p>
                            <button class="btn btn-sm btn-primary mt-2" onclick="loadCustomers()">
                                <i class="fas fa-sync"></i> Retry
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error('❌ Failed to parse JSON');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                            <p>Error: Invalid server response</p>
                            <button class="btn btn-sm btn-primary mt-2" onclick="loadCustomers()">
                                <i class="fas fa-sync"></i> Retry
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        if (!data.success) {
            console.warn('⚠️ API returned error:', data.error || 'Unknown error');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle" style="color: #ffc107;"></i>
                            <p>${data.error || 'Failed to load customers'}</p>
                            <button class="btn btn-sm btn-primary mt-2" onclick="loadCustomers()">
                                <i class="fas fa-sync"></i> Try Again
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        const customersData = data.customers || data.data || [];
        console.log(`✅ Loaded ${customersData.length} customers`);
        
        if (customersData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-users" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="mt-2">No customers yet</p>
                            <small class="text-muted">Convert leads to customers to see them here</small>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        allCustomers = customersData.map(c => ({
            id: c.id,
            name: c.name || c.full_name || '—',
            phone: c.phone || c.mobile || '—',
            email: c.email || '—',
            service_type: c.service_type || c.service || '—',
            status: c.status || 'active',
            joined: c.created_at || c.joined 
                ? new Date(c.created_at || c.joined).toLocaleDateString('en-IN', { 
                    day: '2-digit', 
                    month: 'short', 
                    year: 'numeric' 
                  })
                : '—',
            created_at: c.created_at || c.joined
        }));
        
        renderCustomers(allCustomers);
        
    } catch(e) {
        console.error('❌ Error loading customers:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        <p>Error: ${e.message}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadCustomers()">
                            <i class="fas fa-sync"></i> Retry
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
}

function renderCustomers(list) {
    const tbody = document.getElementById('customersBody');
    if (!tbody) return;
    
    if (!list || list.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No customers yet. Convert leads to customers!</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = list.map((c, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><strong>${esc(c.name)}</strong></td>
            <td>${esc(c.email || '—')}</td>
            <td>${esc(c.phone || '—')}</td>
            <td><span class="badge bg-primary">${esc(c.service_type || '—')}</span></td>
            <td>${statusBadge(c.status || 'active')}</td>
            <td>${c.joined || '—'}</td>
            <td>
                <button class="btn btn-ghost btn-xs" onclick="viewCustomer(${c.id})" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-ghost btn-xs" onclick="editCustomer(${c.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-ghost btn-xs" onclick="deleteCustomer(${c.id})" title="Delete">
                    <i class="fas fa-trash text-danger"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterCustomers() {
    const search = document.getElementById('custSearch')?.value?.toLowerCase() || '';
    const filtered = allCustomers.filter(c => 
        c.name.toLowerCase().includes(search) ||
        c.phone.includes(search) ||
        c.email.toLowerCase().includes(search) ||
        c.service_type.toLowerCase().includes(search)
    );
    renderCustomers(filtered);
}

function viewCustomer(id) {
    const customer = allCustomers.find(c => c.id === id);
    if (!customer) {
        toast('Customer not found', 'error');
        return;
    }
    toast(`Customer: ${customer.name}\nPhone: ${customer.phone}\nEmail: ${customer.email}\nService: ${customer.service_type}`, 'info', 5000);
}

function editCustomer(id) {
    toast('Edit customer functionality coming soon!', 'info');
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    allCustomers = allCustomers.filter(c => c.id !== id);
    renderCustomers(allCustomers);
    toast('Customer deleted successfully!', 'success');
}

function exportCustomersExcel() {
    if (!allCustomers.length) {
        toast('No customers to export', 'error');
        return;
    }
    try {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.json_to_sheet(allCustomers);
        XLSX.utils.book_append_sheet(wb, ws, 'Customers');
        XLSX.writeFile(wb, `customers_${new Date().toISOString().slice(0,10)}.xlsx`);
        toast('Customers exported successfully!', 'success');
    } catch(e) {
        console.error('Export error:', e);
        toast('Error exporting: ' + e.message, 'error');
    }
}

// ── OTHER FUNCTIONS (Placeholders for completeness) ──────────────────

async function loadScoredLeads() {
    const tbody = document.getElementById('scoredLeadsBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-bullseye"></i><p>Lead scoring available when API is connected</p></div></td></tr>`;
}

async function loadFollowups() {
    // Placeholder
    document.getElementById('fuPendingBody').innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-calendar"></i><p>Follow-ups available when API is connected</p></div></td></tr>`;
    document.getElementById('fuDoneBody').innerHTML = `<tr><td colspan="4"><div class="empty-state"><i class="fas fa-check-circle"></i><p>No completed follow-ups</p></div></td></tr>`;
    document.getElementById('fuAllBody').innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-calendar"></i><p>No follow-ups scheduled</p></div></td></tr>`;
}

async function loadCommission() {
    const tbody = document.getElementById('commissionBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-rupee-sign"></i><p>Commission data available when API is connected</p></div></td></tr>`;
}

async function loadPayouts() {
    const tbody = document.getElementById('payoutsBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-wallet"></i><p>Payout data available when API is connected</p></div></td></tr>`;
}

async function loadConnectors() {
    const tbody = document.getElementById('connectorsBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-plug"></i><p>Connectors available when API is connected</p></div></td></tr>`;
}

async function loadContacts() {
    const tbody = document.getElementById('contactsBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-address-book"></i><p>Contacts available when API is connected</p></div></td></tr>`;
}

async function loadPerformance() {
    // Placeholder
    document.getElementById('perfConvRate').textContent = '—';
    document.getElementById('perfAvgDeal').textContent = '—';
    document.getElementById('perfResponseTime').textContent = '—';
    document.getElementById('perfRank').textContent = '#—';
    document.getElementById('perfRating').textContent = '—★';
    document.getElementById('targetText').textContent = '0 / 20';
    document.getElementById('targetProgress').style.width = '0%';
    document.getElementById('targetMsg').textContent = 'Data available when API is connected';
    document.getElementById('leaderboardBody').innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-trophy"></i><p>Leaderboard available when API is connected</p></div></td></tr>`;
    document.getElementById('badgesGrid').innerHTML = `<div class="empty-state"><i class="fas fa-medal"></i><p>Achievements available when API is connected</p></div>`;
}

async function loadTickets() {
    const tbody = document.getElementById('ticketsBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-headset"></i><p>Support tickets available when API is connected</p></div></td></tr>`;
}

async function loadProfile() {
    // Already populated from PHP
}

async function loadReferralEarnings() {
    document.getElementById('refTotalSignups').textContent = '0';
    document.getElementById('refConversions').textContent = '0';
    document.getElementById('refEarnings').textContent = '₹0';
    document.getElementById('refRank').textContent = '—';
    document.getElementById('refListBody').innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-share-alt"></i><p>No referrals yet. Share your code!</p></div></td></tr>`;
}

async function loadReferralLinks() {
    // Already populated from PHP
}

// ── ADD LEAD (Placeholder) ──────────────────────────────────────────
async function addLead() {
    toast('Add lead functionality coming soon!', 'info');
}

function openUpdateLead(id, status) {
    toast('Update lead functionality coming soon!', 'info');
}

async function updateLeadStatus() {
    toast('Update lead status coming soon!', 'info');
}

function scheduleFollowupFor(leadId, name) {
    toast('Schedule follow-up coming soon!', 'info');
}

async function calcScore(leadId) {
    toast('Score calculation coming soon!', 'info');
}

function exportLeadsExcel() {
    toast('Export leads coming soon!', 'info');
}

// ── EXPOSE FUNCTIONS GLOBALLY ──────────────────────────────────────
window.loadCustomers = loadCustomers;
window.filterCustomers = filterCustomers;
window.viewCustomer = viewCustomer;
window.editCustomer = editCustomer;
window.deleteCustomer = deleteCustomer;
window.exportCustomersExcel = exportCustomersExcel;
window.loadFollowups = loadFollowups;
window.addLead = addLead;
window.openUpdateLead = openUpdateLead;
window.updateLeadStatus = updateLeadStatus;
window.scheduleFollowupFor = scheduleFollowupFor;
window.calcScore = calcScore;
window.exportLeadsExcel = exportLeadsExcel;

// ── NOTIFICATIONS ─────────────────────────────────────────────────────
const notifBtn = document.getElementById('notifBtn');
if (notifBtn) {
    notifBtn.addEventListener('click', e => {
        e.stopPropagation();
        const panel = document.getElementById('notifPanel');
        if (panel) panel.classList.toggle('open');
    });
}
document.addEventListener('click', () => {
    const panel = document.getElementById('notifPanel');
    if (panel) panel.classList.remove('open');
});

function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
  const badge = document.getElementById('notifBadge');
  if (badge) badge.style.display = 'none';
  toast('All notifications marked as read', 'success');
}

async function loadNotifications() {
  try {
    const data = await apiFetch(`${API}get_notifications.php?partner_id=${PARTNER_ID}`);
    if (!data.success) return;
    const badge = document.getElementById('notifBadge');
    const unread = data.unread_count || 0;
    if (badge) {
        if (unread > 0) { badge.style.display = 'inline-block'; badge.textContent = unread > 9 ? '9+' : unread; }
        else badge.style.display = 'none';
    }
  } catch(e) {}
}
setInterval(loadNotifications, 30000);

// ── TAB HELPER ────────────────────────────────────────────────────────
function switchTab(btn, targetId) {
  const bar = btn.closest('.tab-bar');
  bar.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  btn.closest('.card').querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.getElementById(targetId)?.classList.add('active');
}

// ── AI ASSISTANT ─────────────────────────────────────────────────────
let aiCollapsed = false;
const aiHeader = document.getElementById('aiHeader');
if (aiHeader) {
    aiHeader.addEventListener('click', () => {
        aiCollapsed = !aiCollapsed;
        const panel = document.getElementById('aiPanel');
        const chevron = document.getElementById('aiChevron');
        if (panel) panel.classList.toggle('mini', aiCollapsed);
        if (chevron) chevron.className = aiCollapsed ? 'fas fa-chevron-up ai-chevron' : 'fas fa-chevron-down ai-chevron';
    });
}

function quickAsk(q) { document.getElementById('aiInput').value = q; sendAI(); }

function aiAddMsg(text, role) {
  const el = document.createElement('div');
  el.className = `ai-msg ${role}`;
  el.innerHTML = role === 'bot' ? text.replace(/\n/g, '<br>') : esc(text);
  const messages = document.getElementById('aiMessages');
  if (messages) {
    messages.appendChild(el);
    messages.scrollTop = 99999;
  }
  return el;
}

async function sendAI() {
  const input = document.getElementById('aiInput');
  const msg = input.value.trim();
  if (!msg) return;
  aiAddMsg(msg, 'user');
  input.value = '';
  if (aiCollapsed) { aiCollapsed = false; const panel = document.getElementById('aiPanel'); if (panel) panel.classList.remove('mini'); }
  const thinking = aiAddMsg('Thinking…', 'bot');

  const totalLeads   = document.getElementById('statTotalLeads')?.textContent || '0';
  const converted    = document.getElementById('statConverted')?.textContent || '0';
  const commission   = document.getElementById('statCommission')?.textContent || '₹0';
  const convRate     = document.getElementById('statConvRate')?.textContent || '0%';

  const ctx = `You are a helpful AI assistant for a CIBIL Repair partner dashboard.
Partner context:
- Name: ${PARTNER_NAME}
- Tier: ${TIER.icon} ${TIER.name} (${TIER.commission}% commission)
- Total Leads: ${totalLeads}
- Converted: ${converted}
- Commission Earned: ${commission}
- Conversion Rate: ${convRate}

You help with: lead conversion tips, commission optimization, tier progression, lead scoring, partner best practices, business growth.
Be concise, practical, and encouraging. Format clearly. No markdown — plain text only.`;

  try {
    const resp = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 500,
        system: ctx,
        messages: [{ role: 'user', content: msg }]
      })
    });
    const data = await resp.json();
    if (thinking) thinking.remove();
    aiAddMsg(data.content?.[0]?.text || 'Could not get a response. Please try again.', 'bot');
  } catch(e) {
    if (thinking) thinking.remove();
    aiAddMsg('Connection error. Please check your network and try again.', 'bot');
  }
}

// ── LOGOUT ────────────────────────────────────────────────────────────
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to log out?')) window.location.href = 'logout.php';
    });
}

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.altKey && e.key === 'd') showSection('dashboard');
  if (e.altKey && e.key === 'l') showSection('leads');
  if (e.altKey && e.key === 'p') showSection('performance');
  if (e.altKey && e.key === 'c') showSection('contacts');
  if (e.altKey && e.key === 'r') showSection('referral');
});

// ── INITIALIZATION ───────────────────────────────────────────────────
(async function init() {
    try {
        console.log('🚀 Initializing Partner Dashboard...');
        console.log('📊 Admin View:', IS_ADMIN_VIEW);
        console.log('📊 Partner ID:', PARTNER_ID);
        console.log('🏆 Tier:', TIER.name, '(', TIER.commission + '%', ')');
        
        showSection('dashboard');
        await loadNotifications();
        await loadDashboard();
        
        // If admin view, show a toast
        if (IS_ADMIN_VIEW) {
            toast('🛡️ Admin View - Viewing partner: ' + PARTNER_NAME, 'info', 4000);
        }
        
        console.log('✅ Partner dashboard initialized successfully!');
    } catch(e) {
        console.error('❌ Initialization error:', e);
        toast('Error initializing dashboard: ' + e.message, 'error');
    }
})();

// ── ERROR HANDLING ──────────────────────────────────────────────────
window.addEventListener('error', function(e) {
    console.error('Uncaught error:', e.message, e.filename, e.lineno);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled rejection:', e.reason);
});

console.log('📦 Partner Dashboard JavaScript loaded successfully!');
</script>

</body>
</html>