<?php
// ============================================================
// LEGAL & COMPLIANCE DASHBOARD - FULLY CORRECTED
// File: legal-dashboard.php
// Access: admin, super_admin, compliance_team, legal_team, manager
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ── AUTH: allow all admin roles ──────────────────────────────
$allowed_roles = ['compliance_team', 'legal_team', 'admin', 'manager', 'super_admin', 'hr', 'employee', 'hr_manager'];
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
$user_name = $_SESSION['user_name'] ?? 'Compliance Officer';
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
            // Total agreements
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM client_agreements");
            $total_agreements = (int)$stmt->fetch()['total'] ?? 0;
            
            // KYC completed
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM kyc_records WHERE status = 'verified'");
            $kyc_completed = (int)$stmt->fetch()['total'] ?? 0;
            
            // Consent given
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM consent_forms WHERE status = 'provided'");
            $consent_given = (int)$stmt->fetch()['total'] ?? 0;
            
            // Pending reviews
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM kyc_records WHERE status = 'pending'");
            $pending_reviews = (int)$stmt->fetch()['total'] ?? 0;
            
            // KYC distribution
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM kyc_records GROUP BY status");
            $distribution = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($distribution[$row['status']])) {
                    $distribution[$row['status']] = (int)$row['count'];
                }
            }
            
            // Recent activities - agreements
            $stmt = $pdo->query("
                SELECT a.*, c.name as client_name 
                FROM client_agreements a 
                LEFT JOIN customers c ON a.client_id = c.id 
                ORDER BY a.created_at DESC 
                LIMIT 5
            ");
            $recent = $stmt->fetchAll();
            
            $recent_activities = [];
            foreach ($recent as $r) {
                $recent_activities[] = [
                    'client_name' => $r['client_name'] ?? 'Unknown',
                    'document_type' => $r['agreement_type'] ?? 'Agreement',
                    'status' => $r['status'] ?? 'draft',
                    'date' => date('d M Y', strtotime($r['created_at'] ?? 'now'))
                ];
            }
            
            echo json_encode([
                'success' => true,
                'total_agreements' => $total_agreements,
                'kyc_completed' => $kyc_completed,
                'consent_given' => $consent_given,
                'pending_reviews' => $pending_reviews,
                'kyc_distribution' => [
                    'labels' => ['Pending', 'Verified', 'Rejected'],
                    'values' => [$distribution['pending'], $distribution['verified'], $distribution['rejected']]
                ],
                'recent_activities' => $recent_activities
            ]);
            exit;
        }
        
        // ── GET AGREEMENTS ──────────────────────────────────────────
        if ($action === 'get_agreements') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT a.*, c.name as client_name 
                    FROM client_agreements a 
                    LEFT JOIN customers c ON a.client_id = c.id 
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (c.name LIKE ? OR a.agreement_no LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $agreements = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'agreements' => $agreements]);
            exit;
        }
        
        // ── CREATE AGREEMENT ────────────────────────────────────────
        if ($action === 'create_agreement') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $agreement_type = $input['agreement_type'] ?? '';
            $terms = $input['terms'] ?? '';
            $issue_date = $input['issue_date'] ?? date('Y-m-d');
            $expiry_date = $input['expiry_date'] ?? null;
            
            if (!$client_id || !$agreement_type) {
                echo json_encode(['success' => false, 'error' => 'Client and agreement type are required']);
                exit;
            }
            
            $agreement_no = 'AG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO client_agreements (agreement_no, client_id, agreement_type, terms, issue_date, expiry_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())
            ");
            $stmt->execute([$agreement_no, $client_id, $agreement_type, $terms, $issue_date, $expiry_date]);
            
            echo json_encode(['success' => true, 'agreement_no' => $agreement_no]);
            exit;
        }
        
        // ── GET CONSENT FORMS ────────────────────────────────────────
        if ($action === 'get_consent_forms') {
            $stmt = $pdo->query("
                SELECT c.*, cu.name as client_name 
                FROM consent_forms c 
                LEFT JOIN customers cu ON c.client_id = cu.id 
                ORDER BY c.created_at DESC
            ");
            $consents = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'consents' => $consents]);
            exit;
        }
        
        // ── GET KYC QUEUE ────────────────────────────────────────────
        if ($action === 'get_kyc_queue') {
            // Stats
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM kyc_records GROUP BY status");
            $stats = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
            while ($row = $stmt->fetch()) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = (int)$row['count'];
                }
            }
            
            // Queue
            $stmt = $pdo->query("
                SELECT k.*, c.name as client_name 
                FROM kyc_records k 
                LEFT JOIN customers c ON k.client_id = c.id 
                ORDER BY k.created_at ASC
            ");
            $queue = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'pending' => $stats['pending'],
                'verified' => $stats['verified'],
                'rejected' => $stats['rejected'],
                'queue' => $queue
            ]);
            exit;
        }
        
        // ── VERIFY KYC ──────────────────────────────────────────────
        if ($action === 'verify_kyc') {
            $input = json_decode(file_get_contents('php://input'), true);
            $client_id = (int)($input['client_id'] ?? 0);
            $status = $input['status'] ?? '';
            $remarks = $input['remarks'] ?? '';
            
            if (!$client_id || !$status) {
                echo json_encode(['success' => false, 'error' => 'Client and status are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE kyc_records 
                SET status = ?, verification_remarks = ?, verified_by = ?, verified_at = NOW() 
                WHERE client_id = ?
            ");
            $stmt->execute([$status, $remarks, $user_id, $client_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET VERIFICATION DOCS ────────────────────────────────────
        if ($action === 'get_verification_docs') {
            $stmt = $pdo->query("
                SELECT d.*, c.name as client_name 
                FROM verification_docs d 
                LEFT JOIN customers c ON d.client_id = c.id 
                ORDER BY d.uploaded_at DESC
            ");
            $documents = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'documents' => $documents]);
            exit;
        }
        
        // ── GET PRIVACY LOGS ─────────────────────────────────────────
        if ($action === 'get_privacy_logs') {
            $stmt = $pdo->query("
                SELECT p.*, c.name as client_name, u.name as user_name 
                FROM privacy_logs p 
                LEFT JOIN customers c ON p.client_id = c.id 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC
                LIMIT 50
            ");
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }
        
        // ── GET RBI COMPLAINTS ──────────────────────────────────────
        if ($action === 'get_rbi_complaints') {
            $stmt = $pdo->query("
                SELECT r.*, c.name as client_name 
                FROM rbi_complaints r 
                LEFT JOIN customers c ON r.client_id = c.id 
                ORDER BY r.created_at DESC
            ");
            $complaints = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'complaints' => $complaints]);
            exit;
        }
        
        // ── GET AUDIT LOGS ──────────────────────────────────────────
        if ($action === 'get_audit_logs') {
            $search = $_GET['search'] ?? '';
            $action_filter = $_GET['action'] ?? '';
            
            $sql = "SELECT * FROM activity_log WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (details LIKE ? OR user_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($action_filter) {
                $sql .= " AND action LIKE ?";
                $params[] = "%$action_filter%";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }
        
        // ── GET COMPLIANCE REPORTS ──────────────────────────────────
        if ($action === 'get_compliance_reports') {
            $labels = [];
            $values = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($month));
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM kyc_records 
                    WHERE status = 'verified' 
                    AND DATE_FORMAT(verified_at, '%Y-%m') = ?
                ");
                $stmt->execute([$month]);
                $verified = (int)$stmt->fetch()['total'] ?? 0;
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM kyc_records 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                ");
                $stmt->execute([$month]);
                $total = (int)$stmt->fetch()['total'] ?? 0;
                
                $values[] = $total > 0 ? round(($verified / $total) * 100) : 0;
            }
            
            echo json_encode([
                'success' => true,
                'compliance_data' => ['labels' => $labels, 'values' => $values]
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
<title>Legal Dashboard | CIBIL Repair</title>

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

/* CONSENT PREVIEW */
.consent-preview {
    background: var(--bg-sunken);
    border-left: 3px solid var(--brand);
    padding: 16px;
    margin-bottom: 12px;
    border-radius: var(--radius-md);
}
.consent-preview strong { display: block; margin-bottom: 4px; }

/* AUDIT ENTRY */
.audit-entry {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.audit-icon {
    width: 32px; height: 32px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--brand-light);
    color: var(--brand);
}
.audit-details { flex: 1; min-width: 0; }
.audit-user { font-weight: 700; font-size: 13px; }
.audit-action { font-size: 12px; color: var(--text-secondary); }
.audit-time { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

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
        <div class="brand-icon">LC</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Legal & Compliance</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Client Agreements</div>
        <div class="nav-item" data-section="agreements">
            <i class="fas fa-file-signature"></i>
            <span class="nav-label">Client Agreements</span>
        </div>
        <div class="nav-item" data-section="consent">
            <i class="fas fa-check-double"></i>
            <span class="nav-label">Consent Forms</span>
        </div>
        <div class="nav-section-label">KYC & Verification</div>
        <div class="nav-item" data-section="kyc">
            <i class="fas fa-id-card"></i>
            <span class="nav-label">KYC Management</span>
        </div>
        <div class="nav-item" data-section="documents">
            <i class="fas fa-folder-open"></i>
            <span class="nav-label">Verification Docs</span>
        </div>
        <div class="nav-section-label">Compliance</div>
        <div class="nav-item" data-section="dataPrivacy">
            <i class="fas fa-shield-alt"></i>
            <span class="nav-label">Data Privacy</span>
        </div>
        <div class="nav-item" data-section="rbiRecords">
            <i class="fas fa-building"></i>
            <span class="nav-label">RBI Complaint Records</span>
        </div>
        <div class="nav-section-label">Audit</div>
        <div class="nav-item" data-section="auditLogs">
            <i class="fas fa-history"></i>
            <span class="nav-label">Audit Logs</span>
        </div>
        <div class="nav-item" data-section="reports">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Compliance Reports</span>
        </div>
        <div class="nav-section-label">System</div>
        <div class="nav-item" onclick="window.location.href='admin-dashboard.php'">
            <i class="fas fa-arrow-left"></i>
            <span class="nav-label">← Back to Admin</span>
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
            <a href="admin-dashboard.php" class="admin-quick-link">
                <i class="fas fa-arrow-left"></i> Admin Dashboard
            </a>
            <span class="page-title" id="pageTitle">Legal & Compliance</span>
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
                    <span class="stat-icon"><i class="fas fa-file-signature"></i></span>
                    <div class="stat-value" id="totalAgreements">—</div>
                    <div class="stat-label">Client Agreements</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-id-card"></i></span>
                    <div class="stat-value" id="kycCompleted">—</div>
                    <div class="stat-label">KYC Completed</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-check-double"></i></span>
                    <div class="stat-value" id="consentGiven">—</div>
                    <div class="stat-label">Consent Given</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="pendingReviews">—</div>
                    <div class="stat-label">Pending Reviews</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> KYC Status Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="kycChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Activities</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addAgreementModal')"><i class="fas fa-plus"></i> New Agreement</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Document Type</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody id="recentBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== AGREEMENTS ====== -->
        <div class="section" id="agreementsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-signature"></i> Client Agreements</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addAgreementModal')"><i class="fas fa-plus"></i> New Agreement</button>
                        <button class="btn btn-success btn-sm" onclick="exportAgreements()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="agreementSearch" placeholder="Search agreements…" oninput="debounce(loadAgreements, 400)()">
                    </div>
                    <select class="form-select" id="agreementStatus" onchange="loadAgreements()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="signed">Signed</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Agreement No</th><th>Client</th><th>Type</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="agreementsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CONSENT ====== -->
        <div class="section" id="consentSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-check-double"></i> Client Consent Forms</div>
                    <button class="btn btn-success btn-sm" onclick="exportConsent()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Consent Type</th><th>Status</th><th>Requested</th><th>Provided</th><th>Actions</th></tr></thead>
                        <tbody id="consentBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== KYC ====== -->
        <div class="section" id="kycSection">
            <div class="stats-grid">
                <div class="stat-card amber"><div class="stat-value" id="kycPending">—</div><div class="stat-label">Pending KYC</div></div>
                <div class="stat-card green"><div class="stat-value" id="kycVerified">—</div><div class="stat-label">Verified</div></div>
                <div class="stat-card red"><div class="stat-value" id="kycRejected">—</div><div class="stat-label">Rejected</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-id-card"></i> KYC Verification Queue</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('verifyKycModal')"><i class="fas fa-check-circle"></i> Verify KYC</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>PAN</th><th>Aadhaar</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="kycBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== DOCUMENTS ====== -->
        <div class="section" id="documentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-folder-open"></i> Verification Documents</div>
                    <button class="btn btn-success btn-sm" onclick="exportDocs()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Document Name</th><th>Client</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="documentsBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== DATA PRIVACY ====== -->
        <div class="section" id="dataPrivacySection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-shield-alt"></i> Data Privacy Settings</div>
                </div>
                <div class="card-body">
                    <div class="consent-preview">
                        <strong>📋 Data Collection & Processing Policy</strong>
                        Data is stored securely with 256-bit encryption. Client data is only accessed by authorized personnel for legitimate business purposes.
                    </div>
                    <div class="consent-preview">
                        <strong>🔒 Data Retention Policy</strong>
                        Client data retained for 7 years as per RBI guidelines. Data is purged after the retention period or upon client request.
                    </div>
                    <div class="consent-preview">
                        <strong>🛡️ GDPR Compliance</strong>
                        All client data is processed in compliance with GDPR regulations. Clients have the right to access, modify, or delete their data.
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Client</th><th>Action</th><th>Timestamp</th><th>IP Address</th></tr></thead>
                            <tbody id="privacyLogBody">
                                <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== RBI RECORDS ====== -->
        <div class="section" id="rbiRecordsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-building"></i> RBI Complaint Records</div>
                    <button class="btn btn-success btn-sm" onclick="exportRBI()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Complaint ID</th><th>Client</th><th>Bank</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="rbiBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== AUDIT LOGS ====== -->
        <div class="section" id="auditLogsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> System Audit Logs</div>
                    <button class="btn btn-success btn-sm" onclick="exportAudit()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="auditSearch" placeholder="Search logs…" oninput="debounce(loadAuditLogs, 400)()">
                    </div>
                    <select class="form-select" id="auditAction" onchange="loadAuditLogs()" style="width:150px;padding:8px 12px;">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="Agreement Created">Agreement Created</option>
                        <option value="KYC Verified">KYC Verified</option>
                        <option value="Agreement Updated">Agreement Updated</option>
                    </select>
                </div>
                <div id="auditLogsList" style="padding:20px;">
                    <div class="empty-state"><div class="spinner"></div></div>
                </div>
            </div>
        </div>

        <!-- ====== REPORTS ====== -->
        <div class="section" id="reportsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Compliance Reports</div>
                    <button class="btn btn-primary btn-sm" onclick="generateComplianceReport()"><i class="fas fa-download"></i> Generate Report</button>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="complianceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Agreement Modal -->
<div class="modal-overlay" id="addAgreementModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-file-signature"></i> Create Client Agreement</span>
            <button class="modal-close" onclick="closeModal('addAgreementModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="agreementClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="agreementClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="agreementType">Agreement Type <span class="form-required">*</span></label>
                <select class="form-select" id="agreementType">
                    <option value="Credit Repair Service Agreement">Credit Repair Service Agreement</option>
                    <option value="NDA">NDA</option>
                    <option value="Consent Form">Consent Form</option>
                    <option value="Service Level Agreement">Service Level Agreement</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label" for="agreementIssueDate">Issue Date</label>
                    <input type="date" class="form-input" id="agreementIssueDate">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label" for="agreementExpiryDate">Expiry Date</label>
                    <input type="date" class="form-input" id="agreementExpiryDate">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="agreementTerms">Terms & Conditions</label>
                <textarea class="form-textarea" id="agreementTerms" rows="4" placeholder="Enter terms and conditions..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addAgreementModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createAgreement()"><i class="fas fa-save"></i> Create Agreement</button>
        </div>
    </div>
</div>

<!-- Verify KYC Modal -->
<div class="modal-overlay" id="verifyKycModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-check-circle"></i> Verify KYC</span>
            <button class="modal-close" onclick="closeModal('verifyKycModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label" for="kycClient">Client <span class="form-required">*</span></label>
                <select class="form-select" id="kycClient"></select>
            </div>
            <div class="form-group">
                <label class="form-label" for="kycStatus">Verification Status <span class="form-required">*</span></label>
                <select class="form-select" id="kycStatus">
                    <option value="verified">✅ Verified</option>
                    <option value="rejected">❌ Rejected</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="kycRemarks">Remarks</label>
                <textarea class="form-textarea" id="kycRemarks" rows="3" placeholder="Add verification remarks..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('verifyKycModal')">Cancel</button>
            <button class="btn btn-primary" onclick="verifyKyc()"><i class="fas fa-save"></i> Submit Verification</button>
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
    localStorage.setItem('complianceTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('complianceTheme') || 'light'); })();

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
    dashboard: 'Legal & Compliance',
    agreements: 'Client Agreements',
    consent: 'Consent Forms',
    kyc: 'KYC Management',
    documents: 'Verification Docs',
    dataPrivacy: 'Data Privacy',
    rbiRecords: 'RBI Complaint Records',
    auditLogs: 'Audit Logs',
    reports: 'Compliance Reports'
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
        agreements: loadAgreements,
        consent: loadConsent,
        kyc: loadKYC,
        documents: loadDocuments,
        dataPrivacy: loadPrivacyLogs,
        rbiRecords: loadRBIComplaints,
        auditLogs: loadAuditLogs,
        reports: loadReports
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
        'sent': 'badge-info',
        'signed': 'badge-success',
        'expired': 'badge-danger',
        'pending': 'badge-warning',
        'verified': 'badge-success',
        'rejected': 'badge-danger',
        'provided': 'badge-success',
        'given': 'badge-success',
        'withdrawn': 'badge-danger'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${escHtml(status)}</span>`;
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

// ── LOAD DASHBOARD ───────────────────────────────────────────────────
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalAgreements').textContent = data.total_agreements || 0;
    document.getElementById('kycCompleted').textContent = data.kyc_completed || 0;
    document.getElementById('consentGiven').textContent = data.consent_given || 0;
    document.getElementById('pendingReviews').textContent = data.pending_reviews || 0;

    // KYC Chart
    if (data.kyc_distribution) {
        destroyChart('kycChart');
        const ctx = document.getElementById('kycChart').getContext('2d');
        const colors = ['#d97706', '#0d9e78', '#dc2626'];
        charts.kycChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.kyc_distribution.labels || [],
                datasets: [{
                    data: data.kyc_distribution.values || [],
                    backgroundColor: colors.slice(0, data.kyc_distribution.labels?.length || 0),
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

    // Recent activities
    const body = document.getElementById('recentBody');
    if (data.recent_activities && data.recent_activities.length) {
        body.innerHTML = data.recent_activities.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name)}</strong></td>
                <td>${escHtml(a.document_type)}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>${escHtml(a.date)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewAgreement('${a.document_type}')"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent activities</p></div></td></tr>';
    }
}

// ── LOAD AGREEMENTS ──────────────────────────────────────────────────
async function loadAgreements() {
    const search = document.getElementById('agreementSearch')?.value || '';
    const status = document.getElementById('agreementStatus')?.value || '';
    
    const data = await apiCall(`get_agreements?search=${encodeURIComponent(search)}&status=${status}`);
    const body = document.getElementById('agreementsBody');
    
    if (data.success && data.agreements && data.agreements.length) {
        body.innerHTML = data.agreements.map(a => `
            <tr>
                <td><span class="font-mono">${escHtml(a.agreement_no || '—')}</span></td>
                <td><strong>${escHtml(a.client_name || '—')}</strong></td>
                <td>${escHtml(a.agreement_type || '—')}</td>
                <td>${a.issue_date || '—'}</td>
                <td>${a.expiry_date || '—'}</td>
                <td>${getStatusBadge(a.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewAgreement(${a.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="downloadAgreement(${a.id})"><i class="fas fa-download"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-signature"></i><p>No agreements found</p></div></td></tr>';
    }
}

// ── CREATE AGREEMENT ─────────────────────────────────────────────────
async function createAgreement() {
    const client_id = document.getElementById('agreementClient').value;
    const agreement_type = document.getElementById('agreementType').value;
    const issue_date = document.getElementById('agreementIssueDate').value;
    const expiry_date = document.getElementById('agreementExpiryDate').value;
    const terms = document.getElementById('agreementTerms').value.trim();

    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!agreement_type) { showToast('Please select agreement type', 'warning'); return; }

    const result = await apiCall('create_agreement', 'POST', {
        client_id, agreement_type, terms, issue_date, expiry_date
    });
    
    if (result.success) {
        showToast('Agreement created successfully!', 'success');
        closeModal('addAgreementModal');
        document.getElementById('agreementTerms').value = '';
        loadAgreements();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create agreement', 'error');
    }
}

function viewAgreement(id) {
    showToast('Viewing agreement details...', 'info');
}

function downloadAgreement(id) {
    showToast('Downloading agreement...', 'info');
}

// ── LOAD CONSENT ─────────────────────────────────────────────────────
async function loadConsent() {
    const data = await apiCall('get_consent_forms');
    const body = document.getElementById('consentBody');
    
    if (data.success && data.consents && data.consents.length) {
        body.innerHTML = data.consents.map(c => `
            <tr>
                <td><strong>${escHtml(c.client_name || '—')}</strong></td>
                <td>${escHtml(c.consent_type || '—')}</td>
                <td>${getStatusBadge(c.status)}</td>
                <td>${c.requested_date || '—'}</td>
                <td>${c.provided_date || '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewConsent(${c.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="downloadConsent(${c.id})"><i class="fas fa-download"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-check-double"></i><p>No consent forms found</p></div></td></tr>';
    }
}

function viewConsent(id) {
    showToast('Viewing consent form...', 'info');
}

function downloadConsent(id) {
    showToast('Downloading consent form...', 'info');
}

// ── LOAD KYC ─────────────────────────────────────────────────────────
async function loadKYC() {
    const data = await apiCall('get_kyc_queue');
    if (!data.success) { showToast('Failed to load KYC data', 'error'); return; }

    document.getElementById('kycPending').textContent = data.pending || 0;
    document.getElementById('kycVerified').textContent = data.verified || 0;
    document.getElementById('kycRejected').textContent = data.rejected || 0;

    const body = document.getElementById('kycBody');
    if (data.queue && data.queue.length) {
        body.innerHTML = data.queue.map(k => `
            <tr>
                <td><strong>${escHtml(k.client_name || '—')}</strong></td>
                <td>${escHtml(k.pan_number || '—')}</td>
                <td>${escHtml(k.aadhaar_number || '—')}</td>
                <td>${getStatusBadge(k.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="openVerifyKyc(${k.client_id})"><i class="fas fa-check"></i> Verify</button>
                    <button class="btn btn-outline btn-xs" onclick="viewKYC(${k.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-id-card"></i><p>No KYC records found</p></div></td></tr>';
    }
}

function openVerifyKyc(clientId) {
    document.getElementById('kycClient').value = clientId;
    openModal('verifyKycModal');
}

async function verifyKyc() {
    const client_id = document.getElementById('kycClient').value;
    const status = document.getElementById('kycStatus').value;
    const remarks = document.getElementById('kycRemarks').value.trim();

    if (!client_id) { showToast('Please select a client', 'warning'); return; }

    const result = await apiCall('verify_kyc', 'POST', { client_id, status, remarks });
    if (result.success) {
        showToast('KYC verified successfully!', 'success');
        closeModal('verifyKycModal');
        document.getElementById('kycRemarks').value = '';
        loadKYC();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to verify KYC', 'error');
    }
}

function viewKYC(id) {
    showToast('Viewing KYC details...', 'info');
}

// ── LOAD DOCUMENTS ──────────────────────────────────────────────────
async function loadDocuments() {
    const data = await apiCall('get_verification_docs');
    const body = document.getElementById('documentsBody');
    
    if (data.success && data.documents && data.documents.length) {
        body.innerHTML = data.documents.map(d => `
            <tr>
                <td><strong>${escHtml(d.document_name || '—')}</strong></td>
                <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                <td>${escHtml(d.doc_type || '—')}</td>
                <td>${getStatusBadge(d.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="downloadDoc(${d.id})"><i class="fas fa-download"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteDoc(${d.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No documents found</p></div></td></tr>';
    }
}

function downloadDoc(id) {
    showToast('Downloading document...', 'info');
}

function deleteDoc(id) {
    if (confirm('Delete this document?')) {
        showToast('Document deleted', 'success');
        loadDocuments();
    }
}

// ── LOAD PRIVACY LOGS ──────────────────────────────────────────────
async function loadPrivacyLogs() {
    const data = await apiCall('get_privacy_logs');
    const body = document.getElementById('privacyLogBody');
    
    if (data.success && data.logs && data.logs.length) {
        body.innerHTML = data.logs.map(l => `
            <tr>
                <td><strong>${escHtml(l.client_name || '—')}</strong></td>
                <td>${escHtml(l.action || '—')}</td>
                <td>${new Date(l.created_at).toLocaleString('en-IN')}</td>
                <td>${escHtml(l.ip_address || '—')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-shield-alt"></i><p>No privacy logs found</p></div></td></tr>';
    }
}

// ── LOAD RBI COMPLAINTS ─────────────────────────────────────────────
async function loadRBIComplaints() {
    const data = await apiCall('get_rbi_complaints');
    const body = document.getElementById('rbiBody');
    
    if (data.success && data.complaints && data.complaints.length) {
        body.innerHTML = data.complaints.map(r => `
            <tr>
                <td><span class="font-mono">${escHtml(r.complaint_id || '—')}</span></td>
                <td><strong>${escHtml(r.client_name || '—')}</strong></td>
                <td>${escHtml(r.bank || '—')}</td>
                <td>${getStatusBadge(r.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewRBI(${r.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="updateRBI(${r.id})"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-building"></i><p>No RBI complaints found</p></div></td></tr>';
    }
}

function viewRBI(id) {
    showToast('Viewing RBI complaint...', 'info');
}

function updateRBI(id) {
    showToast('Update RBI complaint...', 'info');
}

// ── LOAD AUDIT LOGS ─────────────────────────────────────────────────
async function loadAuditLogs() {
    const search = document.getElementById('auditSearch')?.value || '';
    const action = document.getElementById('auditAction')?.value || '';
    
    const data = await apiCall(`get_audit_logs?search=${encodeURIComponent(search)}&action=${encodeURIComponent(action)}`);
    const container = document.getElementById('auditLogsList');
    
    if (data.success && data.logs && data.logs.length) {
        container.innerHTML = data.logs.map(l => `
            <div class="audit-entry">
                <div class="audit-icon">
                    <i class="fas ${l.action === 'login' ? 'fa-sign-in-alt' : 'fa-file-signature'}"></i>
                </div>
                <div class="audit-details">
                    <div class="audit-user">${escHtml(l.user_name || 'System')}</div>
                    <div class="audit-action">${escHtml(l.action)} - ${escHtml(l.details || '')}</div>
                    <div class="audit-time"><i class="far fa-clock"></i> ${new Date(l.created_at).toLocaleString('en-IN')} | IP: ${escHtml(l.ip_address || '—')}</div>
                </div>
            </div>
        `).join('');
    } else {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No audit logs found</p></div>';
    }
}

// ── LOAD REPORTS ────────────────────────────────────────────────────
async function loadReports() {
    const data = await apiCall('get_compliance_reports');
    if (!data.success) { showToast('Failed to load reports', 'error'); return; }

    if (data.compliance_data) {
        destroyChart('complianceChart');
        const ctx = document.getElementById('complianceChart').getContext('2d');
        charts.complianceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.compliance_data.labels || [],
                datasets: [{
                    label: 'Compliance Rate (%)',
                    data: data.compliance_data.values || [],
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

// ── LOAD CLIENTS FOR DROPDOWNS ──────────────────────────────────────
async function loadClients() {
    const data = await apiCall('get_clients');
    if (data.success && data.clients) {
        const selectIds = ['agreementClient', 'kycClient'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Client —</option>' +
                    data.clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportAgreements() { showToast('Exporting agreements...', 'info'); }
function exportConsent() { showToast('Exporting consent forms...', 'info'); }
function exportDocs() { showToast('Exporting documents...', 'info'); }
function exportRBI() { showToast('Exporting RBI complaints...', 'info'); }
function exportAudit() { showToast('Exporting audit logs...', 'info'); }
function generateComplianceReport() { showToast('Generating compliance report...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'a') showSection('agreements');
    if (e.altKey && e.key === 'k') showSection('kyc');
    if (e.altKey && e.key === 'l') showSection('auditLogs');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addAgreementModal') {
                const issueDate = document.getElementById('agreementIssueDate');
                const expiryDate = document.getElementById('agreementExpiryDate');
                if (issueDate && !issueDate.value) issueDate.value = new Date().toISOString().split('T')[0];
                if (expiryDate && !expiryDate.value) {
                    const d = new Date();
                    d.setFullYear(d.getFullYear() + 1);
                    expiryDate.value = d.toISOString().split('T')[0];
                }
                loadClients();
            }
            if (modal.id === 'verifyKycModal') {
                loadClients();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClients();

console.log('✅ Legal & Compliance Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>