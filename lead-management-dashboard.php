<?php
// ============================================================
// LEAD MANAGEMENT DASHBOARD - FULLY INTEGRATED
// Access: sales, bd, admin, manager, super_admin, partner
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

// ── AUTH: allow sales, bd, admin, manager, super_admin, partner ──────
$allowed_roles = ['sales', 'bd', 'admin', 'manager', 'super_admin', 'partner'];
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
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$is_partner = ($user_role === 'partner');
$csrf = $_SESSION['csrf_token'];

// ── Get partner ID if partner ────────────────────────────────────────
$partner_id = 0;
if ($is_partner) {
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $partner = $stmt->fetch();
    $partner_id = $partner['id'] ?? 0;
}

// ── Get employee ID for filtering ────────────────────────────────────
$employee_id = 0;
if (!$is_admin && !$is_partner) {
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();
    $employee_id = $emp['id'] ?? 0;
}

// ── Determine back URL based on role ─────────────────────────────────
$back_url = $is_partner ? 'partner-dashboard.php' : 'admin-dashboard.php';

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
            $sql_where = "1=1";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql_where = "partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql_where = "assigned_to = ?";
                $params[] = $employee_id;
            }
            
            // Total leads
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE $sql_where");
            $stmt->execute($params);
            $total_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Converted leads
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE $sql_where AND stage = 'converted'");
            $stmt->execute($params);
            $converted_leads = (int)($stmt->fetch()['total'] ?? 0);
            
            // Pending followups
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM followups f 
                JOIN leads l ON f.lead_id = l.id 
                WHERE $sql_where AND f.status = 'pending'
            ");
            $stmt->execute($params);
            $pending_followups = (int)($stmt->fetch()['total'] ?? 0);
            
            // New leads this month
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM leads 
                WHERE $sql_where 
                AND MONTH(created_at) = ? AND YEAR(created_at) = ?
            ");
            $stmt->execute(array_merge($params, [date('m'), date('Y')]));
            $new_leads_month = (int)($stmt->fetch()['total'] ?? 0);
            
            // Conversion rate
            $conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100) : 0;
            
            // Avg response time (in hours - placeholder)
            $avg_response_time = 2.5;
            
            // Pipeline data
            $stages = ['new', 'contacted', 'analysis', 'proposal', 'converted', 'lost'];
            $stage_labels = ['New', 'Contacted', 'Analysis', 'Proposal', 'Converted', 'Lost'];
            $stage_values = [];
            foreach ($stages as $stage) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE $sql_where AND stage = ?");
                $stmt->execute(array_merge($params, [$stage]));
                $stage_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Source data
            $stmt = $pdo->prepare("SELECT source, COUNT(*) as count FROM leads WHERE $sql_where GROUP BY source");
            $stmt->execute($params);
            $source_data = $stmt->fetchAll();
            
            // Recent leads
            $stmt = $pdo->prepare("
                SELECT l.*, c.name as customer_name 
                FROM leads l
                LEFT JOIN customers c ON l.customer_id = c.id
                WHERE $sql_where 
                ORDER BY l.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute($params);
            $recent_leads = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_leads' => $total_leads,
                'converted_leads' => $converted_leads,
                'pending_followups' => $pending_followups,
                'new_leads_month' => $new_leads_month,
                'conversion_rate' => $conversion_rate,
                'avg_response_time' => $avg_response_time,
                'pipeline_data' => ['labels' => $stage_labels, 'values' => $stage_values],
                'source_data' => ['labels' => array_column($source_data, 'source'), 'values' => array_column($source_data, 'count')],
                'recent_leads' => $recent_leads
            ]);
            exit;
        }
        
        // ── GET LEADS ────────────────────────────────────────────────
        if ($action === 'get_leads') {
            $search = $_GET['search'] ?? '';
            $stage = $_GET['stage'] ?? '';
            $source = $_GET['source'] ?? '';
            
            $sql = "SELECT l.*, c.name as customer_name 
                    FROM leads l
                    LEFT JOIN customers c ON l.customer_id = c.id
                    WHERE 1=1";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql .= " AND l.partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql .= " AND l.assigned_to = ?";
                $params[] = $employee_id;
            }
            
            if ($search) {
                $sql .= " AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($stage) {
                $sql .= " AND l.stage = ?";
                $params[] = $stage;
            }
            if ($source) {
                $sql .= " AND l.source = ?";
                $params[] = $source;
            }
            
            $sql .= " ORDER BY l.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'leads' => $leads]);
            exit;
        }
        
        // ── ADD LEAD ─────────────────────────────────────────────────
        if ($action === 'add_lead') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = trim($input['name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $email = trim($input['email'] ?? '');
            $source = $input['source'] ?? 'website';
            $notes = trim($input['notes'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $expected_amount = (float)($input['expected_amount'] ?? 0);
            
            if (empty($name) || empty($phone)) {
                echo json_encode(['success' => false, 'error' => 'Name and phone are required']);
                exit;
            }
            
            // Check if customer exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? OR email = ?");
            $stmt->execute([$phone, $email]);
            $customer = $stmt->fetch();
            
            if ($customer) {
                $customer_id = $customer['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                $stmt->execute([$name, $phone, $email]);
                $customer_id = $pdo->lastInsertId();
            }
            
            $partner_id_val = $is_partner && $partner_id > 0 ? $partner_id : 0;
            $assigned_to = (!$is_admin && !$is_partner && $employee_id > 0) ? $employee_id : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO leads (customer_id, name, phone, email, source, notes, priority, expected_amount, stage, partner_id, assigned_to, score, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, 0, NOW())
            ");
            $stmt->execute([
                $customer_id, $name, $phone, $email, $source, $notes, 
                $priority, $expected_amount, $partner_id_val, $assigned_to
            ]);
            
            $lead_id = $pdo->lastInsertId();
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Lead Added', "New lead: $name from $source"]);
            
            echo json_encode(['success' => true, 'lead_id' => $lead_id]);
            exit;
        }
        
        // ── UPDATE LEAD STAGE ────────────────────────────────────────
        if ($action === 'update_lead_stage') {
            $input = json_decode(file_get_contents('php://input'), true);
            $lead_id = (int)($input['lead_id'] ?? 0);
            $stage = $input['stage'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE leads SET stage = ?, notes = CONCAT(notes, ?), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$stage, "\n[" . date('Y-m-d H:i') . "] Stage update: " . $notes, $lead_id]);
            
            // If converted, update customer
            if ($stage === 'converted') {
                $stmt = $pdo->prepare("SELECT customer_id FROM leads WHERE id = ?");
                $stmt->execute([$lead_id]);
                $lead = $stmt->fetch();
                if ($lead && $lead['customer_id']) {
                    $stmt = $pdo->prepare("UPDATE customers SET status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$lead['customer_id']]);
                }
            }
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Lead Stage Updated', "Lead ID $lead_id moved to $stage"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET KANBAN ───────────────────────────────────────────────
        if ($action === 'get_kanban') {
            $stages = ['new', 'contacted', 'analysis', 'proposal', 'converted'];
            $result = [];
            
            foreach ($stages as $stage) {
                $sql = "SELECT l.* FROM leads l WHERE l.stage = ?";
                $params = [$stage];
                
                if ($is_partner && $partner_id > 0) {
                    $sql .= " AND l.partner_id = ?";
                    $params[] = $partner_id;
                } elseif (!$is_admin && $employee_id > 0) {
                    $sql .= " AND l.assigned_to = ?";
                    $params[] = $employee_id;
                }
                
                $sql .= " ORDER BY l.created_at DESC LIMIT 20";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $result[$stage] = $stmt->fetchAll();
            }
            
            echo json_encode($result);
            exit;
        }
        
        // ── GET SOURCE STATS ─────────────────────────────────────────
        if ($action === 'get_source_stats') {
            $sql = "SELECT source, COUNT(*) as count FROM leads";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql .= " WHERE partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql .= " WHERE assigned_to = ?";
                $params[] = $employee_id;
            }
            
            $sql .= " GROUP BY source";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $source_stats = $stmt->fetchAll();
            
            $result = ['website' => 0, 'whatsapp' => 0, 'facebook' => 0, 'google' => 0, 'referral' => 0, 'other' => 0];
            foreach ($source_stats as $s) {
                $key = strtolower($s['source'] ?? 'other');
                if (isset($result[$key])) {
                    $result[$key] = (int)$s['count'];
                } else {
                    $result['other'] += (int)$s['count'];
                }
            }
            
            // Source performance
            $source_perf = ['labels' => array_keys($result), 'values' => array_values($result)];
            
            echo json_encode([
                'success' => true,
                'website' => $result['website'],
                'whatsapp' => $result['whatsapp'],
                'facebook' => $result['facebook'],
                'google' => $result['google'],
                'referral' => $result['referral'],
                'other' => $result['other'],
                'source_performance' => $source_perf
            ]);
            exit;
        }
        
        // ── GET FOLLOWUPS ────────────────────────────────────────────
        if ($action === 'get_followups') {
            $sql = "SELECT f.*, l.name as lead_name FROM followups f LEFT JOIN leads l ON f.lead_id = l.id WHERE f.status = 'pending'";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql .= " AND l.partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql .= " AND l.assigned_to = ?";
                $params[] = $employee_id;
            }
            
            $sql .= " ORDER BY f.followup_date ASC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $followups = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'followups' => $followups]);
            exit;
        }
        
        // ── COMPLETE FOLLOWUP ────────────────────────────────────────
        if ($action === 'complete_followup') {
            $input = json_decode(file_contents('php://input'), true);
            $followup_id = (int)($input['followup_id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$followup_id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Follow-up Completed', "Follow-up ID $followup_id completed"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PERFORMANCE ──────────────────────────────────────────
        if ($action === 'get_performance') {
            $sql_where = "1=1";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql_where = "partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql_where = "assigned_to = ?";
                $params[] = $employee_id;
            }
            
            // Lead to client conversion
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE $sql_where");
            $stmt->execute($params);
            $total = (int)($stmt->fetch()['total'] ?? 0);
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE $sql_where AND stage = 'converted'");
            $stmt->execute($params);
            $converted = (int)($stmt->fetch()['total'] ?? 0);
            $lead_to_client = $total > 0 ? round(($converted / $total) * 100) . '%' : '0%';
            
            // Average conversion time
            $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM leads WHERE $sql_where AND stage = 'converted'");
            $stmt->execute($params);
            $avg_days = (int)($stmt->fetch()['avg_days'] ?? 0);
            $avg_conversion_days = $avg_days . ' days';
            
            // Win rate
            $win_rate = $total > 0 ? round(($converted / $total) * 100) . '%' : '0%';
            
            // Leak rate (lost leads)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE $sql_where AND stage = 'lost'");
            $stmt->execute($params);
            $lost = (int)($stmt->fetch()['total'] ?? 0);
            $leak_rate = $total > 0 ? round(($lost / $total) * 100) . '%' : '0%';
            
            // Funnel data
            $stages = ['new', 'contacted', 'analysis', 'proposal', 'converted'];
            $funnel_labels = ['New', 'Contacted', 'Analysis', 'Proposal', 'Converted'];
            $funnel_values = [];
            foreach ($stages as $stage) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leads WHERE $sql_where AND stage = ?");
                $stmt->execute(array_merge($params, [$stage]));
                $funnel_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'lead_to_client' => $lead_to_client,
                'avg_conversion_days' => $avg_conversion_days,
                'win_rate' => $win_rate,
                'leak_rate' => $leak_rate,
                'funnel_data' => ['labels' => $funnel_labels, 'values' => $funnel_values]
            ]);
            exit;
        }
        
        // ── GET CONVERSION BY SOURCE ────────────────────────────────
        if ($action === 'get_conversion_by_source') {
            $sql_where = "1=1";
            $params = [];
            
            if ($is_partner && $partner_id > 0) {
                $sql_where = "partner_id = ?";
                $params[] = $partner_id;
            } elseif (!$is_admin && $employee_id > 0) {
                $sql_where = "assigned_to = ?";
                $params[] = $employee_id;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    source,
                    COUNT(*) as total,
                    SUM(CASE WHEN stage = 'converted' THEN 1 ELSE 0 END) as converted,
                    ROUND(SUM(CASE WHEN stage = 'converted' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as percentage
                FROM leads
                WHERE $sql_where
                GROUP BY source
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $sources = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'sources' => $sources]);
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
<title>Lead Management | CIBIL Repair</title>

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

/* STAGE BADGES */
.stage-new { background: #e0f2fe; color: #0369a1; }
.stage-contacted { background: #fef3c7; color: #b45309; }
.stage-analysis { background: #f3e8ff; color: #6b21a5; }
.stage-proposal { background: #fee2e2; color: #991b1b; }
.stage-converted { background: #d1fae5; color: #065f46; }
.stage-lost { background: #fecaca; color: #991b1b; }

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

/* KANBAN */
.kanban-board {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    overflow-x: auto;
}
.kanban-column {
    background: var(--bg-sunken);
    border-radius: var(--radius-lg);
    min-width: 220px;
}
.kanban-header {
    padding: 14px 16px;
    font-weight: 700;
    border-bottom: 2px solid var(--brand);
    font-size: 14px;
}
.kanban-cards { padding: 12px; min-height: 300px; }
.kanban-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 12px 14px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all var(--transition);
}
.kanban-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.kanban-card .lead-name { font-weight: 600; margin-bottom: 4px; }
.kanban-card .lead-meta { font-size: 11px; color: var(--text-muted); }

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .charts-row { grid-template-columns: 1fr; }
    .kanban-board { grid-template-columns: repeat(5, 240px); }
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
        <div class="brand-icon">LM</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Lead Management</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Leads</div>
        <div class="nav-item" data-section="leads">
            <i class="fas fa-filter"></i>
            <span class="nav-label">All Leads</span>
        </div>
        <div class="nav-item" data-section="kanban">
            <i class="fas fa-columns"></i>
            <span class="nav-label">Kanban Board</span>
        </div>
        <div class="nav-item" data-section="sources">
            <i class="fas fa-chart-pie"></i>
            <span class="nav-label">Lead Sources</span>
        </div>
        <div class="nav-item" data-section="followups">
            <i class="fas fa-calendar-check"></i>
            <span class="nav-label">Follow-ups</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="performance">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">Performance</span>
        </div>
        <div class="nav-item" data-section="conversion">
            <i class="fas fa-percent"></i>
            <span class="nav-label">Conversion Rate</span>
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
            <a href="<?= $back_url ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to <?= $is_partner ? 'Partner Portal' : 'Admin Dashboard' ?>
            </a>
            <span class="page-title" id="pageTitle">Lead Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                    <div class="stat-value" id="totalLeads">—</div>
                    <div class="stat-label">Total Leads</div>
                    <div class="stat-change" id="newLeads">New this month: —</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-user-check"></i></span>
                    <div class="stat-value" id="convertedLeads">—</div>
                    <div class="stat-label">Converted Clients</div>
                    <div class="stat-change" id="conversionRate">Conversion: —%</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="pendingFollowups">—</div>
                    <div class="stat-label">Follow-ups Due</div>
                    <div class="stat-change">⚠️ Pending</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="stat-value" id="avgResponseTime">—</div>
                    <div class="stat-label">Avg Response Time</div>
                    <div class="stat-change">Hours</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Lead Pipeline</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addLeadModal')"><i class="fas fa-plus"></i> Add Lead</button>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="pipelineChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-fire"></i> Recent Leads</div>
                    <button class="btn btn-success btn-sm" onclick="exportLeads()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Contact</th><th>Source</th><th>Stage</th><th>Score</th><th>Actions</th></tr></thead>
                        <tbody id="recentLeadsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ALL LEADS ====== -->
        <div class="section" id="leadsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-filter"></i> All Leads</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addLeadModal')"><i class="fas fa-plus"></i> Add Lead</button>
                        <button class="btn btn-success btn-sm" onclick="exportLeads()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="leadSearch" placeholder="Search leads…" oninput="debounce(filterLeads, 400)()">
                    </div>
                    <select class="form-select" id="stageFilter" onchange="filterLeads()" style="width:140px;padding:8px 12px;">
                        <option value="">All Stages</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="analysis">Analysis</option>
                        <option value="proposal">Proposal</option>
                        <option value="converted">Converted</option>
                        <option value="lost">Lost</option>
                    </select>
                    <select class="form-select" id="sourceFilter" onchange="filterLeads()" style="width:140px;padding:8px 12px;">
                        <option value="">All Sources</option>
                        <option value="website">Website</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="facebook">Facebook</option>
                        <option value="google">Google</option>
                        <option value="referral">Referral</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Source</th><th>Stage</th><th>Score</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody id="allLeadsBody">
                            <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== KANBAN ====== -->
        <div class="section" id="kanbanSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-columns"></i> Kanban Board</div>
                </div>
                <div class="card-body">
                    <div class="kanban-board" id="kanbanBoard">
                        <div class="empty-state"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== SOURCES ====== -->
        <div class="section" id="sourcesSection">
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-value" id="sourceWebsite">—</div><div class="stat-label">Website</div></div>
                <div class="stat-card blue"><div class="stat-value" id="sourceWhatsApp">—</div><div class="stat-label">WhatsApp</div></div>
                <div class="stat-card amber"><div class="stat-value" id="sourceFacebook">—</div><div class="stat-label">Facebook</div></div>
                <div class="stat-card purple"><div class="stat-value" id="sourceGoogle">—</div><div class="stat-label">Google</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Source Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="sourcePerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ====== FOLLOWUPS ====== -->
        <div class="section" id="followupsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-calendar-check"></i> Follow-up Schedule</div>
                    <button class="btn btn-primary btn-sm" onclick="scheduleFollowup()"><i class="fas fa-plus"></i> Schedule</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Lead</th><th>Follow-up Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="followupsBody">
                            <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PERFORMANCE ====== -->
        <div class="section" id="performanceSection">
            <div class="stats-grid">
                <div class="stat-card green"><div class="stat-value" id="perfLeadToClient">—</div><div class="stat-label">Lead → Client</div></div>
                <div class="stat-card blue"><div class="stat-value" id="perfAvgTime">—</div><div class="stat-label">Avg Conversion Time</div></div>
                <div class="stat-card amber"><div class="stat-value" id="perfWinRate">—</div><div class="stat-label">Win Rate</div></div>
                <div class="stat-card red"><div class="stat-value" id="perfLeakRate">—</div><div class="stat-label">Leak Rate</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-area"></i> Funnel Analysis</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="funnelChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ====== CONVERSION ====== -->
        <div class="section" id="conversionSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-percent"></i> Conversion by Source</div>
                    <button class="btn btn-success btn-sm" onclick="exportConversion()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Source</th><th>Total Leads</th><th>Converted</th><th>Conversion %</th></tr></thead>
                        <tbody id="conversionBody">
                            <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Lead Modal -->
<div class="modal-overlay" id="addLeadModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Add New Lead</span>
            <button class="modal-close" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Full Name <span class="form-required">*</span></label>
                    <input class="form-input" id="leadName" placeholder="Enter full name">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Phone <span class="form-required">*</span></label>
                    <input class="form-input" id="leadPhone" placeholder="10-digit mobile" maxlength="10">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Email</label>
                    <input class="form-input" id="leadEmail" type="email" placeholder="Email address">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Source</label>
                    <select class="form-select" id="leadSource">
                        <option value="website">Website</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="facebook">Facebook</option>
                        <option value="google">Google</option>
                        <option value="referral">Referral</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="leadNotes" rows="3" placeholder="Additional notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addLeadModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addLead()"><i class="fas fa-save"></i> Add Lead</button>
        </div>
    </div>
</div>

<!-- Update Stage Modal -->
<div class="modal-overlay" id="updateStageModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Lead Stage</span>
            <button class="modal-close" onclick="closeModal('updateStageModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateLeadId">
            <div class="form-group">
                <label class="form-label">Stage</label>
                <select class="form-select" id="updateStage">
                    <option value="new">New Lead</option>
                    <option value="contacted">Contacted</option>
                    <option value="analysis">Credit Report Analysis</option>
                    <option value="proposal">Proposal Sent</option>
                    <option value="converted">Client Onboarded</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateNotes" rows="3" placeholder="Update notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateStageModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateLeadStage()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Schedule Follow-up Modal -->
<div class="modal-overlay" id="scheduleFollowupModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-calendar-plus"></i> Schedule Follow-up</span>
            <button class="modal-close" onclick="closeModal('scheduleFollowupModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Lead</label>
                <select class="form-select" id="followupLeadSelect">
                    <option value="">Select lead…</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Follow-up Date & Time</label>
                <input type="datetime-local" class="form-input" id="followupDateTime">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="followupNotes" rows="3" placeholder="What to discuss…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('scheduleFollowupModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveFollowup()"><i class="fas fa-save"></i> Schedule</button>
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

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('leadTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('leadTheme') || 'light'); })();

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
    dashboard: 'Lead Dashboard',
    leads: 'All Leads',
    kanban: 'Kanban Board',
    sources: 'Lead Sources',
    followups: 'Follow-ups',
    performance: 'Performance',
    conversion: 'Conversion Rate'
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
        leads: loadAllLeads,
        kanban: loadKanban,
        sources: loadSources,
        followups: loadFollowups,
        performance: loadPerformance,
        conversion: loadConversion
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
function getStageBadge(stage) {
    const map = {
        'new': 'stage-new',
        'contacted': 'stage-contacted',
        'analysis': 'stage-analysis',
        'proposal': 'stage-proposal',
        'converted': 'stage-converted',
        'lost': 'stage-lost'
    };
    const labels = {
        'new': 'New',
        'contacted': 'Contacted',
        'analysis': 'Analysis',
        'proposal': 'Proposal',
        'converted': 'Converted',
        'lost': 'Lost'
    };
    const cls = map[stage?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[stage] || stage}</span>`;
}

function getScoreBadge(score) {
    if (score >= 70) return `<span class="badge badge-success">${score}</span>`;
    if (score >= 50) return `<span class="badge badge-warning">${score}</span>`;
    return `<span class="badge badge-gray">${score || 0}</span>`;
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

    document.getElementById('totalLeads').textContent = data.total_leads || 0;
    document.getElementById('convertedLeads').textContent = data.converted_leads || 0;
    document.getElementById('pendingFollowups').textContent = data.pending_followups || 0;
    document.getElementById('avgResponseTime').textContent = data.avg_response_time || 0;
    document.getElementById('newLeads').innerHTML = `New this month: ${data.new_leads_month || 0}`;
    document.getElementById('conversionRate').innerHTML = `Conversion: ${data.conversion_rate || 0}%`;

    // Pipeline chart
    if (data.pipeline_data) {
        destroyChart('pipelineChart');
        const ctx = document.getElementById('pipelineChart').getContext('2d');
        const colors = ['#3b82f6', '#d97706', '#7c3aed', '#dc2626', '#059669', '#9ca3af'];
        charts.pipelineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.pipeline_data.labels || [],
                datasets: [{
                    label: 'Leads',
                    data: data.pipeline_data.values || [],
                    backgroundColor: colors.slice(0, data.pipeline_data.labels?.length || 0),
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

    // Recent leads
    const body = document.getElementById('recentLeadsBody');
    if (data.recent_leads && data.recent_leads.length) {
        body.innerHTML = data.recent_leads.map(l => `
            <tr>
                <td><strong>${escHtml(l.name || l.customer_name || '—')}</strong></td>
                <td>${escHtml(l.phone || '—')}<br><small>${escHtml(l.email || '')}</small></td>
                <td><span class="badge badge-info">${escHtml(l.source || '—')}</span></td>
                <td>${getStageBadge(l.stage)}</td>
                <td>${getScoreBadge(l.score || 0)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateStage(${l.id}, '${l.stage}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="scheduleFollowupFor(${l.id}, '${escHtml(l.name || l.customer_name || '')}')"><i class="fas fa-calendar-plus"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent leads</p></div></td></tr>';
    }
}

// ── ALL LEADS ─────────────────────────────────────────────────────────
let allLeads = [];

async function loadAllLeads() {
    const data = await apiCall('get_leads');
    if (data.success) {
        allLeads = data.leads || [];
        renderAllLeads(allLeads);
    }
}

function renderAllLeads(leads) {
    const body = document.getElementById('allLeadsBody');
    if (!leads || leads.length === 0) {
        body.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-filter"></i><p>No leads found</p></div></td></tr>';
        return;
    }
    
    body.innerHTML = leads.map((l, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><strong>${escHtml(l.name || l.customer_name || '—')}</strong></td>
            <td>${escHtml(l.phone || '—')}</td>
            <td>${escHtml(l.email || '—')}</td>
            <td><span class="badge badge-info">${escHtml(l.source || '—')}</span></td>
            <td>${getStageBadge(l.stage)}</td>
            <td>${getScoreBadge(l.score || 0)}</td>
            <td>${l.created_at ? new Date(l.created_at).toLocaleDateString('en-IN') : '—'}</td>
            <td>
                <button class="btn btn-outline btn-xs" onclick="openUpdateStage(${l.id}, '${l.stage}')"><i class="fas fa-edit"></i></button>
                <button class="btn btn-primary btn-xs" onclick="scheduleFollowupFor(${l.id}, '${escHtml(l.name || l.customer_name || '')}')"><i class="fas fa-calendar-plus"></i></button>
            </td>
        </tr>
    `).join('');
}

function filterLeads() {
    const search = document.getElementById('leadSearch')?.value?.toLowerCase() || '';
    const stage = document.getElementById('stageFilter')?.value || '';
    const source = document.getElementById('sourceFilter')?.value || '';
    
    const filtered = allLeads.filter(l => {
        const matchSearch = !search || 
            (l.name || '').toLowerCase().includes(search) || 
            (l.phone || '').toLowerCase().includes(search) || 
            (l.email || '').toLowerCase().includes(search);
        const matchStage = !stage || l.stage === stage;
        const matchSource = !source || l.source === source;
        return matchSearch && matchStage && matchSource;
    });
    renderAllLeads(filtered);
}

function openUpdateStage(id, stage) {
    document.getElementById('updateLeadId').value = id;
    document.getElementById('updateStage').value = stage || 'new';
    document.getElementById('updateNotes').value = '';
    openModal('updateStageModal');
}

async function updateLeadStage() {
    const id = document.getElementById('updateLeadId').value;
    const stage = document.getElementById('updateStage').value;
    const notes = document.getElementById('updateNotes').value.trim();
    
    const result = await apiCall('update_lead_stage', 'POST', { lead_id: id, stage, notes });
    if (result.success) {
        showToast('Lead stage updated!', 'success');
        closeModal('updateStageModal');
        loadDashboard();
        loadAllLeads();
        loadKanban();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function addLead() {
    const name = document.getElementById('leadName').value.trim();
    const phone = document.getElementById('leadPhone').value.trim();
    const email = document.getElementById('leadEmail').value.trim();
    const source = document.getElementById('leadSource').value;
    const notes = document.getElementById('leadNotes').value.trim();
    
    if (!name) { showToast('Name is required', 'warning'); return; }
    if (!phone) { showToast('Phone is required', 'warning'); return; }
    
    const result = await apiCall('add_lead', 'POST', { name, phone, email, source, notes });
    if (result.success) {
        showToast('Lead added successfully!', 'success');
        closeModal('addLeadModal');
        document.getElementById('leadName').value = '';
        document.getElementById('leadPhone').value = '';
        document.getElementById('leadEmail').value = '';
        document.getElementById('leadNotes').value = '';
        loadDashboard();
        loadAllLeads();
        loadKanban();
    } else {
        showToast(result.error || 'Failed to add lead', 'error');
    }
}

// ── KANBAN ─────────────────────────────────────────────────────────────
async function loadKanban() {
    const data = await apiCall('get_kanban');
    const stages = ['new', 'contacted', 'analysis', 'proposal', 'converted'];
    const stageNames = {
        'new': 'New Lead',
        'contacted': 'Contacted',
        'analysis': 'Credit Analysis',
        'proposal': 'Proposal Sent',
        'converted': 'Converted ✓'
    };
    
    let html = '';
    for (const stage of stages) {
        const leads = data[stage] || [];
        html += `
            <div class="kanban-column">
                <div class="kanban-header">${stageNames[stage] || stage} (${leads.length})</div>
                <div class="kanban-cards">
                    ${leads.map(l => `
                        <div class="kanban-card" onclick="openUpdateStage(${l.id}, '${stage}')">
                            <div class="lead-name">${escHtml(l.name || l.customer_name || '—')}</div>
                            <div class="lead-meta">${escHtml(l.phone || '')}</div>
                            <div style="margin-top:6px;">
                                <span class="badge badge-info">${escHtml(l.source || '—')}</span>
                                ${getScoreBadge(l.score || 0)}
                            </div>
                        </div>
                    `).join('')}
                    ${leads.length === 0 ? '<div style="padding:12px;text-align:center;color:var(--text-muted);">No leads</div>' : ''}
                </div>
            </div>
        `;
    }
    document.getElementById('kanbanBoard').innerHTML = html;
}

// ── SOURCES ────────────────────────────────────────────────────────────
async function loadSources() {
    const data = await apiCall('get_source_stats');
    if (!data.success) { showToast('Failed to load sources', 'error'); return; }

    document.getElementById('sourceWebsite').textContent = data.website || 0;
    document.getElementById('sourceWhatsApp').textContent = data.whatsapp || 0;
    document.getElementById('sourceFacebook').textContent = data.facebook || 0;
    document.getElementById('sourceGoogle').textContent = data.google || 0;

    if (data.source_performance) {
        destroyChart('sourcePerformanceChart');
        const ctx = document.getElementById('sourcePerformanceChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#ec489a', '#14b8a6'];
        charts.sourcePerformanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.source_performance.labels || [],
                datasets: [{
                    label: 'Leads',
                    data: data.source_performance.values || [],
                    backgroundColor: colors.slice(0, data.source_performance.labels?.length || 0),
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

// ── FOLLOWUPS ─────────────────────────────────────────────────────────
async function loadFollowups() {
    const data = await apiCall('get_followups');
    const body = document.getElementById('followupsBody');
    
    if (data.success && data.followups && data.followups.length) {
        body.innerHTML = data.followups.map(f => `
            <tr>
                <td><strong>${escHtml(f.lead_name || '—')}</strong></td>
                <td>${new Date(f.followup_date).toLocaleString('en-IN')}</td>
                <td><span class="badge badge-warning">Pending</span></td>
                <td>
                    <button class="btn btn-success btn-xs" onclick="completeFollowup(${f.id})"><i class="fas fa-check"></i> Complete</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-calendar"></i><p>No follow-ups scheduled</p></div></td></tr>';
    }
}

async function completeFollowup(id) {
    const result = await apiCall('complete_followup', 'POST', { followup_id: id });
    if (result.success) {
        showToast('Follow-up completed!', 'success');
        loadFollowups();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to complete', 'error');
    }
}

function scheduleFollowup() {
    // Populate lead dropdown
    const select = document.getElementById('followupLeadSelect');
    if (select) {
        select.innerHTML = '<option value="">Select lead…</option>' +
            allLeads.map(l => `<option value="${l.id}">${escHtml(l.name || l.customer_name || '—')}</option>`).join('');
    }
    const dt = document.getElementById('followupDateTime');
    if (dt) {
        const now = new Date();
        now.setDate(now.getDate() + 1);
        now.setHours(10, 0, 0, 0);
        dt.value = now.toISOString().slice(0, 16);
    }
    document.getElementById('followupNotes').value = '';
    openModal('scheduleFollowupModal');
}

function scheduleFollowupFor(leadId, leadName) {
    const select = document.getElementById('followupLeadSelect');
    if (select) {
        select.value = leadId;
        // Also populate all leads
        select.innerHTML = '<option value="">Select lead…</option>' +
            allLeads.map(l => `<option value="${l.id}" ${l.id === leadId ? 'selected' : ''}>${escHtml(l.name || l.customer_name || '—')}</option>`).join('');
    }
    const dt = document.getElementById('followupDateTime');
    if (dt) {
        const now = new Date();
        now.setDate(now.getDate() + 1);
        now.setHours(10, 0, 0, 0);
        dt.value = now.toISOString().slice(0, 16);
    }
    document.getElementById('followupNotes').value = `Follow-up with ${leadName}`;
    openModal('scheduleFollowupModal');
}

async function saveFollowup() {
    const lead_id = document.getElementById('followupLeadSelect').value;
    const followup_date = document.getElementById('followupDateTime').value;
    const notes = document.getElementById('followupNotes').value.trim();
    
    if (!lead_id) { showToast('Please select a lead', 'warning'); return; }
    if (!followup_date) { showToast('Please select date & time', 'warning'); return; }
    
    const result = await apiCall('add_followup', 'POST', { lead_id, followup_date, notes });
    if (result.success) {
        showToast('Follow-up scheduled!', 'success');
        closeModal('scheduleFollowupModal');
        loadFollowups();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to schedule', 'error');
    }
}

// ── PERFORMANCE ──────────────────────────────────────────────────────
async function loadPerformance() {
    const data = await apiCall('get_performance');
    if (!data.success) { showToast('Failed to load performance', 'error'); return; }

    document.getElementById('perfLeadToClient').textContent = data.lead_to_client || '0%';
    document.getElementById('perfAvgTime').textContent = data.avg_conversion_days || '0 days';
    document.getElementById('perfWinRate').textContent = data.win_rate || '0%';
    document.getElementById('perfLeakRate').textContent = data.leak_rate || '0%';

    if (data.funnel_data) {
        destroyChart('funnelChart');
        const ctx = document.getElementById('funnelChart').getContext('2d');
        charts.funnelChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.funnel_data.labels || [],
                datasets: [{
                    label: 'Leads',
                    data: data.funnel_data.values || [],
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
}

// ── CONVERSION ────────────────────────────────────────────────────────
async function loadConversion() {
    const data = await apiCall('get_conversion_by_source');
    const body = document.getElementById('conversionBody');
    
    if (data.success && data.sources && data.sources.length) {
        body.innerHTML = data.sources.map(s => `
            <tr>
                <td><strong>${escHtml(s.source || '—')}</strong></td>
                <td>${s.total || 0}</td>
                <td>${s.converted || 0}</td>
                <td>
                    <span class="badge ${s.percentage >= 30 ? 'badge-success' : s.percentage >= 20 ? 'badge-warning' : 'badge-gray'}">
                        ${s.percentage || 0}%
                    </span>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-percent"></i><p>No conversion data found</p></div></td></tr>';
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportLeads() { showToast('Exporting leads...', 'info'); }
function exportConversion() { showToast('Exporting conversion data...', 'info'); }

// ── ADD FOLLOWUP API ─────────────────────────────────────────────────
// Add this to the PHP API section or create separate endpoint
async function addFollowupAPI(lead_id, followup_date, notes) {
    return await apiCall('add_followup', 'POST', { lead_id, followup_date, notes });
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'l') showSection('leads');
    if (e.altKey && e.key === 'k') showSection('kanban');
    if (e.altKey && e.key === 'f') showSection('followups');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadAllLeads();
loadKanban();
loadSources();
loadFollowups();
loadPerformance();
loadConversion();

console.log('✅ Lead Management Dashboard initialized');
console.log('👤 User Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>