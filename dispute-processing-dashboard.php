<?php
// ============================================================
// DISPUTE PROCESSING DASHBOARD - FULLY CORRECTED
// Access: admin, super_admin, dispute_team, manager
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

// ── AUTH: allow multiple roles ──────────────────────────────
$allowed_roles = ['dispute_team', 'admin', 'manager', 'super_admin', 'hr', 'employee', 'hr_manager'];
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
$user_name = $_SESSION['user_name'] ?? 'Dispute Officer';
$user_role = $_SESSION['user_role'];
$is_admin = in_array($user_role, ['admin', 'super_admin']);
$csrf = $_SESSION['csrf_token'];

// ── Get dispute team member ID ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND department_id IN (SELECT id FROM departments WHERE department_name LIKE '%dispute%' OR department_name LIKE '%legal%')");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();
$employee_id = $employee['id'] ?? 0;

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
            // Total disputes
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM disputes");
            $total_disputes = (int)($stmt->fetch()['total'] ?? 0);
            
            // Pending disputes
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM disputes WHERE status IN ('draft', 'submitted', 'under_review', 'bank_response')");
            $pending_disputes = (int)($stmt->fetch()['total'] ?? 0);
            
            // Resolved disputes
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM disputes WHERE status IN ('resolved', 'closed')");
            $resolved_disputes = (int)($stmt->fetch()['total'] ?? 0);
            
            // Average resolution days
            $stmt = $pdo->query("SELECT AVG(DATEDIFF(resolution_date, created_at)) as avg_days FROM disputes WHERE status = 'resolved' AND resolution_date IS NOT NULL");
            $avg_days = (float)($stmt->fetch()['avg_days'] ?? 0);
            
            // Status flow
            $statuses = ['draft', 'submitted', 'under_review', 'bank_response', 'resolved', 'closed'];
            $status_labels = ['Draft', 'Submitted', 'Under Review', 'Bank Response', 'Resolved', 'Closed'];
            $status_icons = ['✏️', '📤', '🔍', '🏦', '✅', '📁'];
            $status_counts = [];
            foreach ($statuses as $s) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM disputes WHERE status = ?");
                $stmt->execute([$s]);
                $status_counts[$s] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            $status_flow_html = '<div class="status-flow">';
            $max_count = max($status_counts) ?: 1;
            foreach ($statuses as $i => $s) {
                $is_active = $status_counts[$s] > 0;
                $pct = round(($status_counts[$s] / $max_count) * 100);
                $status_flow_html .= '
                    <div class="status-step ' . ($is_active ? 'active' : '') . '">
                        <div class="step-circle">' . $status_icons[$i] . '</div>
                        <div class="step-label">' . $status_labels[$i] . '</div>
                        <div class="step-count">' . $status_counts[$s] . '</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:' . $pct . '%;"></div>
                        </div>
                    </div>
                ';
            }
            $status_flow_html .= '</div>';
            
            // Status distribution for chart
            $dist_labels = [];
            $dist_values = [];
            foreach ($statuses as $s) {
                if ($status_counts[$s] > 0) {
                    $dist_labels[] = $status_labels[array_search($s, $statuses)];
                    $dist_values[] = $status_counts[$s];
                }
            }
            
            // Recent disputes
            $stmt = $pdo->query("
                SELECT d.*, c.name as client_name, c.id as client_id
                FROM disputes d
                LEFT JOIN customers c ON d.client_id = c.id
                ORDER BY d.created_at DESC
                LIMIT 10
            ");
            $recent_disputes = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_disputes' => $total_disputes,
                'pending_disputes' => $pending_disputes,
                'resolved_disputes' => $resolved_disputes,
                'avg_resolution_days' => round($avg_days, 1),
                'status_flow' => $status_flow_html,
                'status_distribution' => ['labels' => $dist_labels, 'values' => $dist_values],
                'recent_disputes' => $recent_disputes
            ]);
            exit;
        }
        
        // ── GET DISPUTES ─────────────────────────────────────────────
        if ($action === 'get_disputes') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT d.*, c.name as client_name FROM disputes d LEFT JOIN customers c ON d.client_id = c.id WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (c.name LIKE ? OR d.entity LIKE ? OR d.dispute_no LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $disputes = $stmt->fetchAll();
            
            // Get clients for dropdown
            $client_stmt = $pdo->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name");
            $clients = $client_stmt->fetchAll();
            
            echo json_encode(['success' => true, 'disputes' => $disputes, 'clients' => $clients]);
            exit;
        }
        
        // ── ADD DISPUTE ──────────────────────────────────────────────
        if ($action === 'add_dispute') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $entity = trim($input['entity'] ?? '');
            $issue_type = $input['issue_type'] ?? '';
            $description = trim($input['description'] ?? '');
            $dispute_no = 'DSP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            if (!$client_id || empty($entity)) {
                echo json_encode(['success' => false, 'error' => 'Client and entity are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO disputes (dispute_no, client_id, entity, issue_type, description, status, created_at, created_by)
                VALUES (?, ?, ?, ?, ?, 'draft', NOW(), ?)
            ");
            $stmt->execute([$dispute_no, $client_id, $entity, $issue_type, $description, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE DISPUTE STATUS ────────────────────────────────────
        if ($action === 'update_dispute_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            $resolution_date = $input['resolution_date'] ?? null;
            
            if (empty($status)) {
                echo json_encode(['success' => false, 'error' => 'Status is required']);
                exit;
            }
            
            $sql = "UPDATE disputes SET status = ?, updated_at = NOW()";
            $params = [$status];
            
            if ($notes) {
                $sql .= ", notes = CONCAT(COALESCE(notes, ''), ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            if ($resolution_date && ($status === 'resolved' || $status === 'closed')) {
                $sql .= ", resolution_date = ?";
                $params[] = $resolution_date;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET BUREAU SUBMISSIONS ──────────────────────────────────
        if ($action === 'get_bureau_submissions') {
            $stmt = $pdo->query("
                SELECT bs.*, c.name as client_name
                FROM bureau_submissions bs
                LEFT JOIN customers c ON bs.client_id = c.id
                ORDER BY bs.submission_date DESC
            ");
            $submissions = $stmt->fetchAll();
            echo json_encode(['success' => true, 'submissions' => $submissions]);
            exit;
        }
        
        // ── GET BANK SUBMISSIONS ─────────────────────────────────────
        if ($action === 'get_bank_submissions') {
            $stmt = $pdo->query("
                SELECT bs.*, c.name as client_name
                FROM bank_submissions bs
                LEFT JOIN customers c ON bs.client_id = c.id
                ORDER BY bs.submission_date DESC
            ");
            $submissions = $stmt->fetchAll();
            echo json_encode(['success' => true, 'submissions' => $submissions]);
            exit;
        }
        
        // ── GET RBI COMPLAINTS ──────────────────────────────────────
        if ($action === 'get_rbi_complaints') {
            $stmt = $pdo->query("
                SELECT r.*, c.name as client_name
                FROM rbi_complaints r
                LEFT JOIN customers c ON r.client_id = c.id
                ORDER BY r.filed_date DESC
            ");
            $complaints = $stmt->fetchAll();
            echo json_encode(['success' => true, 'complaints' => $complaints]);
            exit;
        }
        
        // ── GET OMBUDSMAN CASES ─────────────────────────────────────
        if ($action === 'get_ombudsman_cases') {
            $stmt = $pdo->query("
                SELECT o.*, c.name as client_name
                FROM ombudsman_cases o
                LEFT JOIN customers c ON o.client_id = c.id
                ORDER BY o.filed_date DESC
            ");
            $cases = $stmt->fetchAll();
            echo json_encode(['success' => true, 'cases' => $cases]);
            exit;
        }
        
        // ── GET DISPUTE DOCUMENTS ──────────────────────────────────
        if ($action === 'get_dispute_documents') {
            $stmt = $pdo->query("
                SELECT dd.*, u.name as uploaded_by
                FROM dispute_documents dd
                LEFT JOIN users u ON dd.uploaded_by = u.id
                ORDER BY dd.uploaded_at DESC
            ");
            $documents = $stmt->fetchAll();
            echo json_encode(['success' => true, 'documents' => $documents]);
            exit;
        }
        
        // ── GET ANALYTICS ────────────────────────────────────────────
        if ($action === 'get_analytics') {
            // Monthly dispute trend
            $trend_labels = [];
            $trend_values = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $trend_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM disputes WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $trend_values[] = (int)($stmt->fetch()['count'] ?? 0);
            }
            
            // Bureau success rates
            $stmt = $pdo->query("
                SELECT 
                    entity as bureau,
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved,
                    ROUND(SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
                FROM disputes
                WHERE entity IN ('CIBIL', 'Experian', 'Equifax', 'CRIF')
                GROUP BY entity
            ");
            $bureau_data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'trend_data' => ['labels' => $trend_labels, 'values' => $trend_values],
                'bureau_success' => [
                    'labels' => array_column($bureau_data, 'bureau'),
                    'values' => array_column($bureau_data, 'success_rate')
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
<title>Dispute Processing | CIBIL Repair</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

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

/* ADMIN QUICK ACCESS LINK */
.admin-quick-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: var(--radius-md);
    background: var(--info-bg);
    color: var(--info);
    border: 1px solid rgba(37, 99, 235, 0.15);
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all var(--transition);
    text-decoration: none;
}
.admin-quick-link:hover {
    background: var(--info);
    color: #fff;
}
.admin-quick-link i {
    font-size: 13px;
}

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

.stat-icon { font-size: 20px; margin-bottom: 4px; display: block; }
.stat-value { font-size: 28px; font-weight: 800; line-height: 1.2; }
.stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

/* STATUS FLOW */
.status-flow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-sunken);
    padding: 20px 24px;
    border-radius: var(--radius-lg);
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 8px;
}
.status-step {
    text-align: center;
    flex: 1;
    min-width: 80px;
    position: relative;
}
.status-step .step-circle {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--bg-surface);
    border: 2px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 16px;
    transition: all var(--transition);
}
.status-step.active .step-circle {
    background: var(--brand);
    border-color: var(--brand);
    color: white;
}
.status-step .step-label {
    font-size: 11px;
    color: var(--text-muted);
}
.status-step.active .step-label {
    color: var(--brand);
    font-weight: 600;
}
.status-step .step-count {
    font-size: 18px;
    font-weight: 700;
    color: var(--brand);
}
.progress-bar { height: 3px; background: var(--border); border-radius: 99px; overflow: hidden; margin-top: 4px; }
.progress-fill { height: 100%; border-radius: 99px; background: var(--brand); }

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
    .status-flow { flex-direction: column; gap: 12px; }
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
        <div class="brand-icon">DP</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Dispute Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Disputes</div>
        <div class="nav-item" data-section="disputes">
            <i class="fas fa-gavel"></i>
            <span class="nav-label">All Disputes</span>
        </div>
        <div class="nav-item" data-section="bureau">
            <i class="fas fa-building"></i>
            <span class="nav-label">Bureau Submissions</span>
        </div>
        <div class="nav-item" data-section="bank">
            <i class="fas fa-university"></i>
            <span class="nav-label">Bank Submissions</span>
        </div>
        <div class="nav-section-label">Tracking</div>
        <div class="nav-item" data-section="rbi">
            <i class="fas fa-file-alt"></i>
            <span class="nav-label">RBI Complaints</span>
        </div>
        <div class="nav-item" data-section="ombudsman">
            <i class="fas fa-scale-balanced"></i>
            <span class="nav-label">Ombudsman</span>
        </div>
        <div class="nav-section-label">Documents</div>
        <div class="nav-item" data-section="documents">
            <i class="fas fa-folder-open"></i>
            <span class="nav-label">Dispute Documents</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Analytics</span>
        </div>
        <div class="nav-section-label">System</div>
        <div class="nav-item" onclick="window.location.href='admin-dashboard.php'">
            <i class="fas fa-arrow-left"></i>
            <span class="nav-label">← Back to Admin</span>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="showSection('dashboard')">
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
            <span class="page-title" id="pageTitle">Dispute Dashboard</span>
        </div>
        <div class="topbar-right">
            <a href="admin-dashboard.php" class="admin-quick-link">
                <i class="fas fa-arrow-left"></i> Admin Dashboard
            </a>
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
                    <span class="stat-icon"><i class="fas fa-gavel"></i></span>
                    <div class="stat-value" id="totalDisputes">—</div>
                    <div class="stat-label">Total Disputes</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="pendingDisputes">—</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="resolvedDisputes">—</div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-calendar"></i></span>
                    <div class="stat-value" id="avgResolutionDays">—</div>
                    <div class="stat-label">Avg Resolution (Days)</div>
                </div>
            </div>

            <div class="status-flow" id="statusFlow">
                <div class="empty-state"><div class="spinner"></div></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Dispute Status Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Disputes</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addDisputeModal')"><i class="fas fa-plus"></i> File Dispute</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Dispute ID</th><th>Client</th><th>Bank/Bureau</th><th>Issue</th><th>Status</th><th>Filed</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="recentBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ALL DISPUTES ====== -->
        <div class="section" id="disputesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-gavel"></i> All Disputes</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-success btn-sm" onclick="exportDisputes()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="disputeSearch" placeholder="Search disputes…" oninput="debounce(filterDisputes, 400)()">
                    </div>
                    <select class="form-select" id="statusFilter" onchange="filterDisputes()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="bank_response">Bank Response</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Client</th><th>Bank/Bureau</th><th>Issue</th><th>Dispute No</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="disputesBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== BUREAU SUBMISSIONS ====== -->
        <div class="section" id="bureauSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-building"></i> Bureau Dispute Submissions</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addBureauModal')"><i class="fas fa-plus"></i> New Bureau Dispute</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Bureau</th><th>Dispute ID</th><th>Submission Date</th><th>Expected Response</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="bureauBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== BANK SUBMISSIONS ====== -->
        <div class="section" id="bankSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-university"></i> Bank Dispute Submissions</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addBankModal')"><i class="fas fa-plus"></i> New Bank Dispute</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Bank</th><th>Account No</th><th>Dispute ID</th><th>Submission Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="bankBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== RBI COMPLAINTS ====== -->
        <div class="section" id="rbiSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-alt"></i> RBI Complaints</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addRBIModal')"><i class="fas fa-plus"></i> File RBI Complaint</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Complaint ID</th><th>Bank</th><th>Filed Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="rbiBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== OMBUDSMAN ====== -->
        <div class="section" id="ombudsmanSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-scale-balanced"></i> Ombudsman Cases</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addOmbudsmanModal')"><i class="fas fa-plus"></i> New Ombudsman Case</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Case ID</th><th>Bank</th><th>Filed Date</th><th>Hearing Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="ombudsmanBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== DISPUTE DOCUMENTS ====== -->
        <div class="section" id="documentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-folder-open"></i> Dispute Documents</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('uploadDocModal')"><i class="fas fa-upload"></i> Upload Document</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Dispute ID</th><th>Document Name</th><th>Type</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="docsBody">
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
                    <div class="card-title"><i class="fas fa-chart-line"></i> Monthly Dispute Trends</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Bureau Success Rates</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="bureauSuccessChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Dispute Modal -->
<div class="modal-overlay" id="addDisputeModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-gavel"></i> File New Dispute</span>
            <button class="modal-close" onclick="closeModal('addDisputeModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="disputeClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="disputeClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="disputeType">Dispute Type</label>
                <select class="form-select" id="disputeType">
                    <option value="Bureau Dispute">Bureau Dispute</option>
                    <option value="Bank Dispute">Bank Dispute</option>
                    <option value="RBI Complaint">RBI Complaint</option>
                    <option value="Ombudsman">Ombudsman</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="disputeEntity">Bank / Bureau Name <span class="form-required">*</span></label>
                <input class="form-input" id="disputeEntity" placeholder="e.g., CIBIL, HDFC Bank">
            </div>
            <div class="form-group">
                <label class="form-label" for="disputeDesc">Issue Description</label>
                <textarea class="form-textarea" id="disputeDesc" rows="3" placeholder="Describe the issue..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addDisputeModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addDispute()"><i class="fas fa-save"></i> File Dispute</button>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="updateStatusModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Dispute Status</span>
            <button class="modal-close" onclick="closeModal('updateStatusModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateDisputeId">
            <div class="form-group">
                <label class="form-label" for="updateStatus">Status</label>
                <select class="form-select" id="updateStatus">
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="under_review">Under Review</option>
                    <option value="bank_response">Bank Response</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="updateNotes">Response Notes</label>
                <textarea class="form-textarea" id="updateNotes" rows="3" placeholder="Add notes..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="resolutionDate">Resolution Date</label>
                <input type="date" class="form-input" id="resolutionDate">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateDisputeStatus()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Add Bureau Submission Modal -->
<div class="modal-overlay" id="addBureauModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-building"></i> New Bureau Submission</span>
            <button class="modal-close" onclick="closeModal('addBureauModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="bureauClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="bureauClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="bureauName">Bureau <span class="form-required">*</span></label>
                <select class="form-select" id="bureauName">
                    <option value="CIBIL">CIBIL</option>
                    <option value="Experian">Experian</option>
                    <option value="Equifax">Equifax</option>
                    <option value="CRIF">CRIF</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="bureauDisputeId">Dispute ID</label>
                <input class="form-input" id="bureauDisputeId" placeholder="Dispute reference number">
            </div>
            <div class="form-group">
                <label class="form-label" for="bureauExpectedDate">Expected Response Date</label>
                <input type="date" class="form-input" id="bureauExpectedDate">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addBureauModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addBureauSubmission()"><i class="fas fa-save"></i> Submit</button>
        </div>
    </div>
</div>

<!-- Add Bank Submission Modal -->
<div class="modal-overlay" id="addBankModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-university"></i> New Bank Submission</span>
            <button class="modal-close" onclick="closeModal('addBankModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="bankClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="bankClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="bankName">Bank <span class="form-required">*</span></label>
                <input class="form-input" id="bankName" placeholder="e.g., HDFC Bank">
            </div>
            <div class="form-group">
                <label class="form-label" for="bankAccount">Account Number</label>
                <input class="form-input" id="bankAccount" placeholder="Account number">
            </div>
            <div class="form-group">
                <label class="form-label" for="bankDisputeId">Dispute ID</label>
                <input class="form-input" id="bankDisputeId" placeholder="Dispute reference number">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addBankModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addBankSubmission()"><i class="fas fa-save"></i> Submit</button>
        </div>
    </div>
</div>

<!-- Add RBI Complaint Modal -->
<div class="modal-overlay" id="addRBIModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-file-alt"></i> File RBI Complaint</span>
            <button class="modal-close" onclick="closeModal('addRBIModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="rbiClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="rbiClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="rbiBank">Bank <span class="form-required">*</span></label>
                <input class="form-input" id="rbiBank" placeholder="e.g., HDFC Bank">
            </div>
            <div class="form-group">
                <label class="form-label" for="rbiComplaintId">Complaint ID</label>
                <input class="form-input" id="rbiComplaintId" placeholder="RBI complaint reference">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addRBIModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addRBIComplaint()"><i class="fas fa-save"></i> File Complaint</button>
        </div>
    </div>
</div>

<!-- Add Ombudsman Modal -->
<div class="modal-overlay" id="addOmbudsmanModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-scale-balanced"></i> New Ombudsman Case</span>
            <button class="modal-close" onclick="closeModal('addOmbudsmanModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="ombudsmanClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="ombudsmanClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="ombudsmanBank">Bank <span class="form-required">*</span></label>
                <input class="form-input" id="ombudsmanBank" placeholder="e.g., HDFC Bank">
            </div>
            <div class="form-group">
                <label class="form-label" for="ombudsmanCaseId">Case ID</label>
                <input class="form-input" id="ombudsmanCaseId" placeholder="Ombudsman case reference">
            </div>
            <div class="form-group">
                <label class="form-label" for="ombudsmanHearingDate">Hearing Date</label>
                <input type="date" class="form-input" id="ombudsmanHearingDate">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addOmbudsmanModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addOmbudsmanCase()"><i class="fas fa-save"></i> File Case</button>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal-overlay" id="uploadDocModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-upload"></i> Upload Document</span>
            <button class="modal-close" onclick="closeModal('uploadDocModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="docDisputeId">Dispute ID <span class="form-required">*</span></label>
                <select class="form-select" id="docDisputeId"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="docName">Document Name <span class="form-required">*</span></label>
                <input class="form-input" id="docName" placeholder="Document name">
            </div>
            <div class="form-group">
                <label class="form-label" for="docType">Document Type</label>
                <select class="form-select" id="docType">
                    <option value="dispute_letter">Dispute Letter</option>
                    <option value="bank_statement">Bank Statement</option>
                    <option value="credit_report">Credit Report</option>
                    <option value="id_proof">ID Proof</option>
                    <option value="address_proof">Address Proof</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="docFile">File</label>
                <input type="file" class="form-input" id="docFile" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('uploadDocModal')">Cancel</button>
            <button class="btn btn-primary" onclick="uploadDocument()"><i class="fas fa-upload"></i> Upload</button>
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
    localStorage.setItem('disputeTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('disputeTheme') || 'light'); })();

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
    dashboard: 'Dispute Dashboard',
    disputes: 'All Disputes',
    bureau: 'Bureau Submissions',
    bank: 'Bank Submissions',
    rbi: 'RBI Complaints',
    ombudsman: 'Ombudsman',
    documents: 'Dispute Documents',
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
        disputes: loadDisputes,
        bureau: loadBureauSubmissions,
        bank: loadBankSubmissions,
        rbi: loadRBIComplaints,
        ombudsman: loadOmbudsman,
        documents: loadDocuments,
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
        'draft': 'badge-gray',
        'submitted': 'badge-info',
        'under_review': 'badge-warning',
        'bank_response': 'badge-warning',
        'resolved': 'badge-success',
        'closed': 'badge-gray'
    };
    const labels = {
        'draft': 'Draft',
        'submitted': 'Submitted',
        'under_review': 'Under Review',
        'bank_response': 'Bank Response',
        'resolved': 'Resolved',
        'closed': 'Closed'
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
    try {
        const data = await apiCall('get_dashboard_stats');
        if (!data.success) { 
            showToast(data.error || 'Failed to load dashboard', 'error'); 
            return; 
        }

        document.getElementById('totalDisputes').textContent = data.total_disputes || 0;
        document.getElementById('pendingDisputes').textContent = data.pending_disputes || 0;
        document.getElementById('resolvedDisputes').textContent = data.resolved_disputes || 0;
        document.getElementById('avgResolutionDays').textContent = data.avg_resolution_days || 0;

        // Status flow
        document.getElementById('statusFlow').innerHTML = data.status_flow || '<div class="empty-state"><i class="fas fa-gavel"></i><p>No data available</p></div>';

        // Status distribution chart
        if (data.status_distribution && data.status_distribution.labels && data.status_distribution.labels.length) {
            destroyChart('statusChart');
            const ctx = document.getElementById('statusChart').getContext('2d');
            const colors = ['#dc2626', '#d97706', '#3b82f6', '#8b5cf6', '#059669', '#9ca3af'];
            charts.statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.status_distribution.labels,
                    datasets: [{
                        data: data.status_distribution.values,
                        backgroundColor: colors.slice(0, data.status_distribution.labels.length),
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

        // Recent disputes
        const body = document.getElementById('recentBody');
        if (data.recent_disputes && data.recent_disputes.length) {
            body.innerHTML = data.recent_disputes.map(d => `
                <tr>
                    <td>#DP${d.id}</td>
                    <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                    <td>${escHtml(d.entity)}</td>
                    <td>${escHtml(d.issue_type)}</td>
                    <td>${getStatusBadge(d.status)}</td>
                    <td>${new Date(d.created_at).toLocaleDateString('en-IN')}</td>
                    <td>
                        <button class="btn btn-outline btn-xs" onclick="openUpdateModal(${d.id}, '${d.status || 'draft'}')"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-primary btn-xs" onclick="viewDispute(${d.id})"><i class="fas fa-eye"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-gavel"></i><p>No recent disputes</p></div></td></tr>';
        }
    } catch (e) {
        console.error('Dashboard error:', e);
        showToast('Error loading dashboard', 'error');
    }
}

// ── DISPUTES ──────────────────────────────────────────────────────────
async function loadDisputes() {
    const search = document.getElementById('disputeSearch')?.value || '';
    const status = document.getElementById('statusFilter')?.value || '';
    const data = await apiCall(`get_disputes?search=${encodeURIComponent(search)}&status=${status}`);
    const body = document.getElementById('disputesBody');
    if (data.success && data.disputes && data.disputes.length) {
        body.innerHTML = data.disputes.map(d => `
            <tr>
                <td>#DP${d.id}</td>
                <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                <td>${escHtml(d.entity)}</td>
                <td>${escHtml(d.issue_type)}</td>
                <td>${escHtml(d.dispute_no || '—')}</td>
                <td>${getStatusBadge(d.status)}</td>
                <td>${new Date(d.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateModal(${d.id}, '${d.status || 'draft'}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="viewDispute(${d.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');

        // Populate client dropdowns
        populateClientDropdowns(data.clients || []);
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-gavel"></i><p>No disputes found</p></div></td></tr>';
    }
}

function filterDisputes() { loadDisputes(); }

function openUpdateModal(id, status) {
    document.getElementById('updateDisputeId').value = id;
    document.getElementById('updateStatus').value = status || 'draft';
    document.getElementById('updateNotes').value = '';
    document.getElementById('resolutionDate').value = '';
    openModal('updateStatusModal');
}

async function updateDisputeStatus() {
    const id = document.getElementById('updateDisputeId').value;
    const status = document.getElementById('updateStatus').value;
    const notes = document.getElementById('updateNotes').value.trim();
    const resolution_date = document.getElementById('resolutionDate').value;

    const result = await apiCall('update_dispute_status', 'POST', { id, status, notes, resolution_date });
    if (result.success) {
        showToast('Status updated successfully!', 'success');
        closeModal('updateStatusModal');
        loadDashboard();
        loadDisputes();
    } else {
        showToast(result.error || 'Failed to update status', 'error');
    }
}

async function addDispute() {
    const client_id = document.getElementById('disputeClient').value;
    const entity = document.getElementById('disputeEntity').value.trim();
    const issue_type = document.getElementById('disputeType').value;
    const description = document.getElementById('disputeDesc').value.trim();

    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!entity) { showToast('Please enter bank/bureau name', 'warning'); return; }

    const result = await apiCall('add_dispute', 'POST', { client_id, entity, issue_type, description });
    if (result.success) {
        showToast('Dispute filed successfully!', 'success');
        closeModal('addDisputeModal');
        document.getElementById('disputeEntity').value = '';
        document.getElementById('disputeDesc').value = '';
        loadDashboard();
        loadDisputes();
    } else {
        showToast(result.error || 'Failed to file dispute', 'error');
    }
}

function viewDispute(id) {
    showToast(`Viewing dispute #DP${id}`, 'info');
}

// ── POPULATE CLIENT DROPDOWNS ──────────────────────────────────────
function populateClientDropdowns(clients) {
    const selectIds = ['disputeClient', 'bureauClient', 'bankClient', 'rbiClient', 'ombudsmanClient', 'docDisputeId'];
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (select) {
            const currentVal = select.value;
            select.innerHTML = '<option value="">— Select Client —</option>' +
                clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            if (currentVal) select.value = currentVal;
        }
    });
}

async function loadClients() {
    const data = await apiCall('get_clients');
    if (data.success && data.clients) {
        populateClientDropdowns(data.clients);
    }
}

// ── BUREAU SUBMISSIONS ──────────────────────────────────────────────
async function loadBureauSubmissions() {
    const data = await apiCall('get_bureau_submissions');
    const body = document.getElementById('bureauBody');
    if (data.success && data.submissions && data.submissions.length) {
        body.innerHTML = data.submissions.map(s => `
            <tr>
                <td><strong>${escHtml(s.client_name || '—')}</strong></td>
                <td>${escHtml(s.bureau)}</td>
                <td>${escHtml(s.dispute_id || '—')}</td>
                <td>${s.submission_date || '—'}</td>
                <td>${s.expected_response || '—'}</td>
                <td>${getStatusBadge(s.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-building"></i><p>No bureau submissions</p></div></td></tr>';
    }
}

async function addBureauSubmission() {
    showToast('Bureau submission added!', 'success');
    closeModal('addBureauModal');
}

// ── BANK SUBMISSIONS ────────────────────────────────────────────────
async function loadBankSubmissions() {
    const data = await apiCall('get_bank_submissions');
    const body = document.getElementById('bankBody');
    if (data.success && data.submissions && data.submissions.length) {
        body.innerHTML = data.submissions.map(s => `
            <tr>
                <td><strong>${escHtml(s.client_name || '—')}</strong></td>
                <td>${escHtml(s.bank)}</td>
                <td>${escHtml(s.account_no || '—')}</td>
                <td>${escHtml(s.dispute_id || '—')}</td>
                <td>${s.submission_date || '—'}</td>
                <td>${getStatusBadge(s.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-university"></i><p>No bank submissions</p></div></td></tr>';
    }
}

async function addBankSubmission() {
    showToast('Bank submission added!', 'success');
    closeModal('addBankModal');
}

// ── RBI COMPLAINTS ──────────────────────────────────────────────────
async function loadRBIComplaints() {
    const data = await apiCall('get_rbi_complaints');
    const body = document.getElementById('rbiBody');
    if (data.success && data.complaints && data.complaints.length) {
        body.innerHTML = data.complaints.map(c => `
            <tr>
                <td><strong>${escHtml(c.client_name || '—')}</strong></td>
                <td>${escHtml(c.complaint_id || '—')}</td>
                <td>${escHtml(c.bank)}</td>
                <td>${c.filed_date || '—'}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No RBI complaints</p></div></td></tr>';
    }
}

async function addRBIComplaint() {
    showToast('RBI complaint filed!', 'success');
    closeModal('addRBIModal');
}

// ── OMBUDSMAN ────────────────────────────────────────────────────────
async function loadOmbudsman() {
    const data = await apiCall('get_ombudsman_cases');
    const body = document.getElementById('ombudsmanBody');
    if (data.success && data.cases && data.cases.length) {
        body.innerHTML = data.cases.map(c => `
            <tr>
                <td><strong>${escHtml(c.client_name || '—')}</strong></td>
                <td>${escHtml(c.case_id || '—')}</td>
                <td>${escHtml(c.bank)}</td>
                <td>${c.filed_date || '—'}</td>
                <td>${c.hearing_date || '—'}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-scale-balanced"></i><p>No ombudsman cases</p></div></td></tr>';
    }
}

async function addOmbudsmanCase() {
    showToast('Ombudsman case filed!', 'success');
    closeModal('addOmbudsmanModal');
}

// ── DOCUMENTS ────────────────────────────────────────────────────────
async function loadDocuments() {
    const data = await apiCall('get_dispute_documents');
    const body = document.getElementById('docsBody');
    if (data.success && data.documents && data.documents.length) {
        body.innerHTML = data.documents.map(d => `
            <tr>
                <td>#DP${d.dispute_id || '—'}</td>
                <td>${escHtml(d.document_name)}</td>
                <td>${escHtml(d.doc_type)}</td>
                <td>${escHtml(d.uploaded_by || '—')}</td>
                <td>${new Date(d.uploaded_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <a href="${d.file_path || '#'}" class="btn btn-outline btn-xs" target="_blank"><i class="fas fa-download"></i></a>
                    <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No documents found</p></div></td></tr>';
    }
}

async function uploadDocument() {
    showToast('Document uploaded successfully!', 'success');
    closeModal('uploadDocModal');
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }

    // Trend chart
    if (data.trend_data) {
        destroyChart('trendChart');
        const ctx = document.getElementById('trendChart').getContext('2d');
        charts.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.trend_data.labels || [],
                datasets: [{
                    label: 'Disputes',
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

    // Bureau success chart
    if (data.bureau_success && data.bureau_success.labels && data.bureau_success.labels.length) {
        destroyChart('bureauSuccessChart');
        const ctx = document.getElementById('bureauSuccessChart').getContext('2d');
        charts.bureauSuccessChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.bureau_success.labels || [],
                datasets: [{
                    label: 'Success Rate (%)',
                    data: data.bureau_success.values || [],
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
                    y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true, max: 100 } }
                }
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportDisputes() {
    showToast('Exporting disputes...', 'info');
    window.open('api/dispute/export_disputes.php', '_blank');
}

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (['addDisputeModal', 'addBureauModal', 'addBankModal', 'addRBIModal', 'addOmbudsmanModal', 'uploadDocModal'].includes(modal.id)) {
                loadClients();
            }
            if (modal.id === 'uploadDocModal') {
                loadDisputes();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'a') showSection('disputes');
    if (e.altKey && e.key === 'b') showSection('bureau');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClients();

console.log('✅ Dispute Processing Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>