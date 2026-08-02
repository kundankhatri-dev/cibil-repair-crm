<?php
// ============================================================
// CREDIT ANALYST DASHBOARD - FULLY INTEGRATED
// Access: credit_analyst, admin, manager, super_admin
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

// ── AUTH: allow credit_analyst, admin, manager, super_admin ──────────
$allowed_roles = ['credit_analyst', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Credit Analyst';
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
            // Total reports
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM credit_reports");
            $total_reports = (int)($stmt->fetch()['total'] ?? 0);
            
            // Pending reports
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM credit_reports WHERE status = 'pending'");
            $pending_reports = (int)($stmt->fetch()['total'] ?? 0);
            
            // Average CIBIL score
            $stmt = $pdo->query("SELECT AVG(cibil_score) as avg FROM credit_reports WHERE cibil_score IS NOT NULL");
            $avg_score = (int)($stmt->fetch()['avg'] ?? 0);
            
            // Total issues
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM credit_issues");
            $total_issues = (int)($stmt->fetch()['total'] ?? 0);
            
            // Issue distribution
            $stmt = $pdo->query("
                SELECT issue_type, COUNT(*) as count 
                FROM credit_issues 
                GROUP BY issue_type 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $issue_dist = $stmt->fetchAll();
            
            $issue_labels = [];
            $issue_values = [];
            foreach ($issue_dist as $d) {
                $issue_labels[] = ucwords(str_replace('_', ' ', $d['issue_type']));
                $issue_values[] = (int)$d['count'];
            }
            
            // Recent analyses
            $stmt = $pdo->query("
                SELECT 
                    cr.*, 
                    c.name as client_name,
                    u.name as analyst_name,
                    (SELECT COUNT(*) FROM credit_issues WHERE report_id = cr.id) as issues_found
                FROM credit_reports cr
                LEFT JOIN customers c ON cr.client_id = c.id
                LEFT JOIN users u ON cr.analyzed_by = u.id
                WHERE cr.status IN ('analyzed', 'completed')
                ORDER BY cr.analyzed_at DESC
                LIMIT 10
            ");
            $recent_analyses = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_reports' => $total_reports,
                'pending_reports' => $pending_reports,
                'avg_cibil_score' => $avg_score,
                'total_issues' => $total_issues,
                'issue_distribution' => [
                    'labels' => $issue_labels,
                    'values' => $issue_values
                ],
                'recent_analyses' => array_map(function($a) {
                    return [
                        'id' => $a['id'],
                        'client_name' => $a['client_name'] ?? 'Unknown',
                        'cibil_score' => $a['cibil_score'],
                        'experian_score' => $a['experian_score'],
                        'equifax_score' => $a['equifax_score'],
                        'issues_found' => $a['issues_found'] ?? 0,
                        'analyst_name' => $a['analyst_name'] ?? '-',
                        'created_at' => $a['analyzed_at'] ?? $a['uploaded_at']
                    ];
                }, $recent_analyses)
            ]);
            exit;
        }
        
        // ── GET PENDING REPORTS ──────────────────────────────────────
        if ($action === 'get_pending_reports') {
            $stmt = $pdo->query("
                SELECT cr.*, c.name as client_name 
                FROM credit_reports cr
                LEFT JOIN customers c ON cr.client_id = c.id
                WHERE cr.status = 'pending'
                ORDER BY cr.uploaded_at ASC
            ");
            $reports = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'reports' => $reports]);
            exit;
        }
        
        // ── GET ANALYZED REPORTS ─────────────────────────────────────
        if ($action === 'get_analyzed_reports') {
            $stmt = $pdo->query("
                SELECT 
                    cr.*, 
                    c.name as client_name,
                    u.name as analyst_name,
                    (SELECT COUNT(*) FROM credit_issues WHERE report_id = cr.id) as issues_found
                FROM credit_reports cr
                LEFT JOIN customers c ON cr.client_id = c.id
                LEFT JOIN users u ON cr.analyzed_by = u.id
                WHERE cr.status IN ('analyzed', 'reviewed', 'completed')
                ORDER BY cr.analyzed_at DESC
                LIMIT 50
            ");
            $reports = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'reports' => $reports]);
            exit;
        }
        
        // ── SUBMIT ANALYSIS ──────────────────────────────────────────
        if ($action === 'submit_analysis') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $report_id = (int)($input['report_id'] ?? 0);
            $score = (int)($input['score'] ?? 0);
            $issues = $input['issues'] ?? [];
            $notes = trim($input['notes'] ?? '');
            
            if (!$report_id || !$score) {
                echo json_encode(['success' => false, 'error' => 'Report ID and score are required']);
                exit;
            }
            
            // Update report
            $stmt = $pdo->prepare("
                UPDATE credit_reports 
                SET cibil_score = ?, status = 'analyzed', analyzed_at = NOW(), analyzed_by = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$score, $user_id, $notes, $report_id]);
            
            // Get client_id
            $stmt = $pdo->prepare("SELECT client_id FROM credit_reports WHERE id = ?");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch();
            $client_id = $report['client_id'] ?? 0;
            
            // Save issues
            $issue_labels = [
                'written_off' => 'Written Off Account',
                'settled' => 'Settled Account',
                'late_payment' => 'Late Payment',
                'incorrect_enquiry' => 'Incorrect Enquiry'
            ];
            
            foreach ($issues as $issue_type) {
                $label = $issue_labels[$issue_type] ?? $issue_type;
                $stmt = $pdo->prepare("
                    INSERT INTO credit_issues (report_id, client_id, issue_type, issue_label, status, created_at)
                    VALUES (?, ?, ?, ?, 'identified', NOW())
                ");
                $stmt->execute([$report_id, $client_id, $issue_type, $label]);
            }
            
            // Save score history
            if ($score > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO credit_score_history (client_id, bureau, score, recorded_at)
                    VALUES (?, 'cibil', ?, NOW())
                ");
                $stmt->execute([$client_id, $score]);
            }
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Credit Analysis', "Analyzed report #$report_id with score $score"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET CIBIL ANALYSIS ───────────────────────────────────────
        if ($action === 'get_cibil_analysis') {
            // Score distribution
            $stmt = $pdo->query("
                SELECT 
                    CASE 
                        WHEN cibil_score >= 750 THEN 'Excellent (750+)'
                        WHEN cibil_score >= 650 THEN 'Good (650-749)'
                        WHEN cibil_score >= 550 THEN 'Average (550-649)'
                        WHEN cibil_score >= 450 THEN 'Poor (450-549)'
                        ELSE 'Very Poor (<450)'
                    END as range,
                    COUNT(*) as count
                FROM credit_reports
                WHERE cibil_score IS NOT NULL
                GROUP BY range
                ORDER BY MIN(cibil_score) DESC
            ");
            $distribution = $stmt->fetchAll();
            
            $labels = [];
            $values = [];
            foreach ($distribution as $d) {
                $labels[] = $d['range'];
                $values[] = (int)$d['count'];
            }
            
            // Client scores
            $stmt = $pdo->query("
                SELECT 
                    c.name as client_name,
                    cr.cibil_score,
                    cr.experian_score,
                    cr.equifax_score,
                    cr.crif_score,
                    cr.analyzed_at as updated_at
                FROM credit_reports cr
                LEFT JOIN customers c ON cr.client_id = c.id
                WHERE cr.status IN ('analyzed', 'completed')
                ORDER BY cr.analyzed_at DESC
                LIMIT 50
            ");
            $scores = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'score_distribution' => ['labels' => $labels, 'values' => $values],
                'scores' => $scores
            ]);
            exit;
        }
        
        // ── GET BUREAU STATS ─────────────────────────────────────────
        if ($action === 'get_bureau_stats') {
            $stmt = $pdo->query("
                SELECT 
                    ROUND(AVG(cibil_score)) as cibil_avg,
                    ROUND(AVG(experian_score)) as experian_avg,
                    ROUND(AVG(equifax_score)) as equifax_avg,
                    ROUND(AVG(crif_score)) as crif_avg
                FROM credit_reports
                WHERE status IN ('analyzed', 'completed')
            ");
            $stats = $stmt->fetch();
            
            // Bureau comparison data
            $stmt = $pdo->query("
                SELECT 
                    'CIBIL' as bureau,
                    ROUND(AVG(cibil_score)) as avg_score
                FROM credit_reports
                WHERE cibil_score IS NOT NULL AND status IN ('analyzed', 'completed')
                UNION ALL
                SELECT 'Experian', ROUND(AVG(experian_score))
                FROM credit_reports
                WHERE experian_score IS NOT NULL AND status IN ('analyzed', 'completed')
                UNION ALL
                SELECT 'Equifax', ROUND(AVG(equifax_score))
                FROM credit_reports
                WHERE equifax_score IS NOT NULL AND status IN ('analyzed', 'completed')
                UNION ALL
                SELECT 'CRIF', ROUND(AVG(crif_score))
                FROM credit_reports
                WHERE crif_score IS NOT NULL AND status IN ('analyzed', 'completed')
            ");
            $bureau_data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'cibil_avg' => $stats['cibil_avg'] ?? '—',
                'experian_avg' => $stats['experian_avg'] ?? '—',
                'equifax_avg' => $stats['equifax_avg'] ?? '—',
                'crif_avg' => $stats['crif_avg'] ?? '—',
                'bureau_data' => [
                    'labels' => array_column($bureau_data, 'bureau'),
                    'values' => array_column($bureau_data, 'avg_score')
                ]
            ]);
            exit;
        }
        
        // ── GET ISSUES STATS ─────────────────────────────────────────
        if ($action === 'get_issues_stats') {
            $stmt = $pdo->query("
                SELECT 
                    issue_type,
                    COUNT(*) as count
                FROM credit_issues
                GROUP BY issue_type
            ");
            $stats = [];
            while ($row = $stmt->fetch()) {
                $stats[$row['issue_type']] = (int)$row['count'];
            }
            
            echo json_encode([
                'success' => true,
                'written_off' => $stats['written_off'] ?? 0,
                'settled' => $stats['settled'] ?? 0,
                'overdue' => $stats['late_payment'] ?? 0,
                'incorrect_enquiries' => $stats['incorrect_enquiry'] ?? 0,
                'duplicate_loans' => $stats['duplicate_loan'] ?? 0
            ]);
            exit;
        }
        
        // ── GET ISSUES LIST ──────────────────────────────────────────
        if ($action === 'get_issues_list') {
            $stmt = $pdo->query("
                SELECT 
                    ci.*,
                    c.name as client_name,
                    cr.bureau
                FROM credit_issues ci
                LEFT JOIN customers c ON ci.client_id = c.id
                LEFT JOIN credit_reports cr ON ci.report_id = cr.id
                ORDER BY ci.created_at DESC
                LIMIT 50
            ");
            $issues = $stmt->fetchAll();
            
            $issue_labels = [
                'written_off' => 'Written Off',
                'settled' => 'Settled',
                'late_payment' => 'Late Payment',
                'incorrect_enquiry' => 'Incorrect Enquiry',
                'duplicate_loan' => 'Duplicate Loan'
            ];
            
            foreach ($issues as &$i) {
                $i['type_label'] = $issue_labels[$i['issue_type']] ?? $i['issue_type'];
            }
            
            echo json_encode(['success' => true, 'issues' => $issues]);
            exit;
        }
        
        // ── GET STRATEGIES ────────────────────────────────────────────
        if ($action === 'get_strategies') {
            $stmt = $pdo->query("SELECT * FROM repair_strategies ORDER BY created_at DESC");
            $strategies = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'strategies' => $strategies]);
            exit;
        }
        
        // ── ADD STRATEGY ─────────────────────────────────────────────
        if ($action === 'add_strategy') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $issue_type = $input['issue_type'] ?? '';
            $strategy = trim($input['strategy'] ?? '');
            $estimated_days = (int)($input['estimated_days'] ?? 30);
            $success_rate = (int)($input['success_rate'] ?? 70);
            
            if (empty($strategy)) {
                echo json_encode(['success' => false, 'error' => 'Strategy is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO repair_strategies (issue_type, strategy, estimated_days, success_rate, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$issue_type, $strategy, $estimated_days, $success_rate]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE STRATEGY ──────────────────────────────────────────
        if ($action === 'delete_strategy') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM repair_strategies WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET ANALYTICS ─────────────────────────────────────────────
        if ($action === 'get_analytics') {
            // Analyst performance
            $stmt = $pdo->query("
                SELECT 
                    u.name as analyst_name,
                    COUNT(cr.id) as reports_analyzed
                FROM credit_reports cr
                LEFT JOIN users u ON cr.analyzed_by = u.id
                WHERE cr.status IN ('analyzed', 'completed')
                GROUP BY cr.analyzed_by
                ORDER BY reports_analyzed DESC
                LIMIT 10
            ");
            $analyst_perf = $stmt->fetchAll();
            
            // Monthly trends
            $monthly = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $label = date('M', strtotime($month));
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM credit_reports 
                    WHERE status IN ('analyzed', 'completed') 
                    AND DATE_FORMAT(analyzed_at, '%Y-%m') = ?
                ");
                $stmt->execute([$month]);
                $monthly[] = [
                    'month' => $label,
                    'count' => (int)$stmt->fetch()['count']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'analyst_performance' => [
                    'labels' => array_column($analyst_perf, 'analyst_name'),
                    'values' => array_column($analyst_perf, 'reports_analyzed')
                ],
                'monthly_trends' => [
                    'labels' => array_column($monthly, 'month'),
                    'values' => array_column($monthly, 'count')
                ]
            ]);
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
<title>Credit Analyst | CIBIL Repair</title>

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

/* SCORE BADGES */
.badge-excellent { background: #d1fae5; color: #065f46; }
.badge-good { background: #dbeafe; color: #1e40af; }
.badge-average { background: #fef3c7; color: #78350f; }
.badge-poor { background: #fee2e2; color: #991b1b; }

/* SCORE WIDGET */
.score-widget {
    background: linear-gradient(135deg, var(--sidebar-bg), #0e3d30);
    border-radius: var(--radius-lg);
    padding: 20px;
    margin-bottom: 20px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.score-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 5px solid rgba(255,255,255,0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.score-circle-val {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}
.score-circle-lbl {
    font-size: 10px;
    opacity: 0.7;
}
.score-circle.excellent { border-color: #34d399; box-shadow: 0 0 20px rgba(52,211,153,0.3); }
.score-circle.good { border-color: #60a5fa; box-shadow: 0 0 20px rgba(96,165,250,0.3); }
.score-circle.average { border-color: #fbbf24; box-shadow: 0 0 20px rgba(251,191,36,0.3); }
.score-circle.poor { border-color: #f87171; box-shadow: 0 0 20px rgba(248,113,113,0.3); }
.score-info h3 { font-size: 18px; font-weight: 800; margin-bottom: 4px; }
.score-info p { font-size: 12px; opacity: 0.7; }

/* ISSUE TAGS */
.issue-tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    margin: 2px;
}
.issue-written_off { background: #fee2e2; color: #dc2626; }
.issue-settled { background: #ffedd5; color: #d97706; }
.issue-late_payment { background: #fef3c7; color: #b45309; }
.issue-incorrect_enquiry { background: #e0f2fe; color: #0369a1; }

/* ISSUE GRID */
.issue-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 10px;
}
.issue-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--bg-sunken);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition);
}
.issue-checkbox:hover { background: var(--brand-light); }
.issue-checkbox input {
    width: 16px;
    height: 16px;
    accent-color: var(--brand);
    cursor: pointer;
}

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
    .issue-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">CA</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Credit Analyst</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="pending">
            <i class="fas fa-clock"></i>
            <span class="nav-label">Pending Analysis</span>
        </div>
        <div class="nav-item" data-section="analyzed">
            <i class="fas fa-check-circle"></i>
            <span class="nav-label">Analyzed Reports</span>
        </div>
        <div class="nav-section-label">Credit Bureaus</div>
        <div class="nav-item" data-section="cibil">
            <i class="fas fa-chart-line"></i>
            <span class="nav-label">CIBIL Analysis</span>
        </div>
        <div class="nav-item" data-section="bureaus">
            <i class="fas fa-building"></i>
            <span class="nav-label">All Bureaus</span>
        </div>
        <div class="nav-section-label">Issues</div>
        <div class="nav-item" data-section="issues">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="nav-label">Issue Library</span>
        </div>
        <div class="nav-item" data-section="strategies">
            <i class="fas fa-clipboard-list"></i>
            <span class="nav-label">Repair Strategies</span>
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
            <span class="page-title" id="pageTitle">Credit Analyst Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-file-alt"></i></span>
                    <div class="stat-value" id="totalReports">—</div>
                    <div class="stat-label">Total Reports Analyzed</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div class="stat-value" id="pendingReports">—</div>
                    <div class="stat-label">Pending Analysis</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="stat-value" id="avgScore">—</div>
                    <div class="stat-label">Average CIBIL Score</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="totalIssues">—</div>
                    <div class="stat-label">Issues Identified</div>
                </div>
            </div>

            <div class="score-widget">
                <div class="score-circle" id="scoreCircle">
                    <div class="score-circle-val" id="avgScoreVal">—</div>
                    <div class="score-circle-lbl">Avg CIBIL</div>
                </div>
                <div class="score-info">
                    <h3>Credit Health Overview</h3>
                    <p id="scoreDesc">Loading statistics...</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Issue Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="issueChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Analyses</div>
                    <button class="btn btn-success btn-sm" onclick="exportReports()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>CIBIL</th><th>Experian</th><th>Equifax</th><th>Issues</th><th>Analyst</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody id="recentBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PENDING ====== -->
        <div class="section" id="pendingSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clock"></i> Pending Credit Reports</div>
                    <button class="btn btn-primary btn-sm" onclick="uploadReport()"><i class="fas fa-upload"></i> Upload Report</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Uploaded</th><th>Bureau</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="pendingBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYZED ====== -->
        <div class="section" id="analyzedSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-check-circle"></i> Analyzed Reports</div>
                    <button class="btn btn-success btn-sm" onclick="exportAnalyzed()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>CIBIL</th><th>Issues</th><th>Analyst</th><th>Completed</th><th>Actions</th></tr></thead>
                        <tbody id="analyzedBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CIBIL ====== -->
        <div class="section" id="cibilSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> CIBIL Score Distribution</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-table"></i> Client Scores</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>CIBIL</th><th>Experian</th><th>Equifax</th><th>CRIF</th><th>Last Updated</th></tr></thead>
                        <tbody id="scoresBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== BUREAUS ====== -->
        <div class="section" id="bureausSection">
            <div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
                <div class="stat-card green"><div class="stat-value" id="bureauCIBIL">—</div><div class="stat-label">CIBIL Avg</div></div>
                <div class="stat-card blue"><div class="stat-value" id="bureauExperian">—</div><div class="stat-label">Experian Avg</div></div>
                <div class="stat-card amber"><div class="stat-value" id="bureauEquifax">—</div><div class="stat-label">Equifax Avg</div></div>
                <div class="stat-card purple"><div class="stat-value" id="bureauCRIF">—</div><div class="stat-label">CRIF Avg</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Bureau Comparison</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="bureauChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ====== ISSUES ====== -->
        <div class="section" id="issuesSection">
            <div class="stats-grid" style="grid-template-columns: repeat(5,1fr);">
                <div class="stat-card red"><div class="stat-value" id="issueWrittenOff">—</div><div class="stat-label">Written Off</div></div>
                <div class="stat-card amber"><div class="stat-value" id="issueSettled">—</div><div class="stat-label">Settled</div></div>
                <div class="stat-card warning"><div class="stat-value" id="issueOverdue">—</div><div class="stat-label">Overdue</div></div>
                <div class="stat-card info"><div class="stat-value" id="issueIncorrect">—</div><div class="stat-label">Incorrect Enquiries</div></div>
                <div class="stat-card purple"><div class="stat-value" id="issueDuplicate">—</div><div class="stat-label">Duplicate Loans</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Issue Details</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Client</th><th>Issue Type</th><th>Bank</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="issuesBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== STRATEGIES ====== -->
        <div class="section" id="strategiesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clipboard-list"></i> Repair Strategies</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addStrategyModal')"><i class="fas fa-plus"></i> Add Strategy</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Issue Type</th><th>Strategy</th><th>Time</th><th>Success Rate</th><th>Actions</th></tr></thead>
                        <tbody id="strategiesBody">
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
                    <div class="card-title"><i class="fas fa-chart-line"></i> Analyst Performance</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analystChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trend-up"></i> Monthly Trends</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Analyze Modal -->
<div class="modal-overlay" id="analyzeModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-search"></i> Analyze Credit Report</span>
            <button class="modal-close" onclick="closeModal('analyzeModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="analyzeReportId">
            <div class="form-group">
                <label class="form-label">Client Name</label>
                <input class="form-input" id="analyzeClientName" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">CIBIL Score <span class="form-required">*</span></label>
                <input class="form-input" id="analyzeScore" type="number" min="300" max="900" placeholder="Enter CIBIL score">
            </div>
            <div class="form-group">
                <label class="form-label">Issues Found</label>
                <div class="issue-grid">
                    <label class="issue-checkbox"><input type="checkbox" value="written_off"> Written Off Account</label>
                    <label class="issue-checkbox"><input type="checkbox" value="settled"> Settled Account</label>
                    <label class="issue-checkbox"><input type="checkbox" value="late_payment"> Late Payment</label>
                    <label class="issue-checkbox"><input type="checkbox" value="incorrect_enquiry"> Incorrect Enquiry</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Analyst Notes</label>
                <textarea class="form-textarea" id="analyzeNotes" rows="3" placeholder="Add analysis notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('analyzeModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitAnalysis()"><i class="fas fa-save"></i> Submit Analysis</button>
        </div>
    </div>
</div>

<!-- Add Strategy Modal -->
<div class="modal-overlay" id="addStrategyModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Repair Strategy</span>
            <button class="modal-close" onclick="closeModal('addStrategyModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Issue Type <span class="form-required">*</span></label>
                <select class="form-select" id="strategyIssue">
                    <option value="written_off">Written Off Account</option>
                    <option value="settled">Settled Account</option>
                    <option value="late_payment">Late Payment</option>
                    <option value="incorrect_enquiry">Incorrect Enquiry</option>
                    <option value="duplicate_loan">Duplicate Loan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Strategy <span class="form-required">*</span></label>
                <textarea class="form-textarea" id="strategyDesc" rows="3" placeholder="Describe the repair strategy..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Est. Days</label>
                    <input class="form-input" id="strategyTime" type="number" value="30" min="1" max="180">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Success Rate (%)</label>
                    <input class="form-input" id="strategyRate" type="number" value="70" min="0" max="100">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addStrategyModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addStrategy()"><i class="fas fa-save"></i> Add Strategy</button>
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
    localStorage.setItem('analystTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('analystTheme') || 'light'); })();

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
    dashboard: 'Credit Analyst Dashboard',
    pending: 'Pending Analysis',
    analyzed: 'Analyzed Reports',
    cibil: 'CIBIL Analysis',
    bureaus: 'All Bureaus',
    issues: 'Issue Library',
    strategies: 'Repair Strategies',
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
        pending: loadPending,
        analyzed: loadAnalyzed,
        cibil: loadCIBIL,
        bureaus: loadBureaus,
        issues: loadIssues,
        strategies: loadStrategies,
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
function getScoreBadge(score) {
    if (!score) return '<span class="badge badge-gray">—</span>';
    if (score >= 750) return `<span class="badge badge-excellent">${score}</span>`;
    if (score >= 650) return `<span class="badge badge-good">${score}</span>`;
    if (score >= 550) return `<span class="badge badge-average">${score}</span>`;
    return `<span class="badge badge-poor">${score}</span>`;
}

function getStatusBadge(status) {
    const map = {
        'pending': 'badge-warning',
        'analyzed': 'badge-success',
        'reviewed': 'badge-info',
        'completed': 'badge-success'
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

// ── DASHBOARD ─────────────────────────────────────────────────────────
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalReports').textContent = data.total_reports || 0;
    document.getElementById('pendingReports').textContent = data.pending_reports || 0;
    document.getElementById('avgScore').textContent = data.avg_cibil_score || '—';
    document.getElementById('totalIssues').textContent = data.total_issues || 0;
    document.getElementById('avgScoreVal').textContent = data.avg_cibil_score || '—';

    // Score circle
    const avg = data.avg_cibil_score || 0;
    let scoreClass = 'average';
    if (avg >= 750) scoreClass = 'excellent';
    else if (avg >= 650) scoreClass = 'good';
    else if (avg >= 550) scoreClass = 'average';
    else scoreClass = 'poor';
    document.getElementById('scoreCircle').className = `score-circle ${scoreClass}`;
    document.getElementById('scoreDesc').innerHTML = `Average score across ${data.total_reports || 0} analyzed reports`;

    // Issue chart
    if (data.issue_distribution) {
        destroyChart('issueChart');
        const ctx = document.getElementById('issueChart').getContext('2d');
        const colors = ['#dc2626', '#d97706', '#f97316', '#8b5cf6', '#3b82f6'];
        charts.issueChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.issue_distribution.labels || [],
                datasets: [{
                    data: data.issue_distribution.values || [],
                    backgroundColor: colors.slice(0, data.issue_distribution.labels?.length || 0),
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

    // Recent analyses
    const body = document.getElementById('recentBody');
    if (data.recent_analyses && data.recent_analyses.length) {
        body.innerHTML = data.recent_analyses.map(a => `
            <tr>
                <td><strong>${escHtml(a.client_name)}</strong></td>
                <td>${getScoreBadge(a.cibil_score)}</td>
                <td>${a.experian_score || '—'}</td>
                <td>${a.equifax_score || '—'}</td>
                <td><span class="badge badge-danger">${a.issues_found || 0}</span></td>
                <td>${escHtml(a.analyst_name)}</td>
                <td>${new Date(a.created_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewAnalysis(${a.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><p>No analyses found</p></div></td></tr>';
    }
}

function viewAnalysis(id) {
    showToast('Viewing analysis details...', 'info');
}

// ── PENDING ──────────────────────────────────────────────────────────
async function loadPending() {
    const data = await apiCall('get_pending_reports');
    const body = document.getElementById('pendingBody');
    
    if (data.success && data.reports && data.reports.length) {
        body.innerHTML = data.reports.map(r => `
            <tr>
                <td><strong>${escHtml(r.client_name)}</strong></td>
                <td>${new Date(r.uploaded_at).toLocaleDateString('en-IN')}</td>
                <td><span class="badge badge-info">${escHtml(r.bureau || 'CIBIL')}</span></td>
                <td>${getStatusBadge(r.status)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="openAnalyze(${r.id}, '${escHtml(r.client_name)}')"><i class="fas fa-search"></i> Analyze</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-clock"></i><p>No pending reports</p></div></td></tr>';
    }
}

function uploadReport() {
    showToast('Upload report feature coming soon!', 'info');
}

// ── ANALYZED ──────────────────────────────────────────────────────────
async function loadAnalyzed() {
    const data = await apiCall('get_analyzed_reports');
    const body = document.getElementById('analyzedBody');
    
    if (data.success && data.reports && data.reports.length) {
        body.innerHTML = data.reports.map(r => `
            <tr>
                <td><strong>${escHtml(r.client_name)}</strong></td>
                <td>${getScoreBadge(r.cibil_score)}</td>
                <td><span class="badge badge-danger">${r.issues_found || 0}</span></td>
                <td>${escHtml(r.analyst_name || '-')}</td>
                <td>${new Date(r.analyzed_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewAnalysis(${r.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-check-circle"></i><p>No analyzed reports found</p></div></td></tr>';
    }
}

function openAnalyze(id, name) {
    document.getElementById('analyzeReportId').value = id;
    document.getElementById('analyzeClientName').value = name;
    document.getElementById('analyzeScore').value = '';
    document.querySelectorAll('.issue-checkbox input').forEach(cb => cb.checked = false);
    document.getElementById('analyzeNotes').value = '';
    openModal('analyzeModal');
}

async function submitAnalysis() {
    const reportId = document.getElementById('analyzeReportId').value;
    const score = document.getElementById('analyzeScore').value;
    const issues = Array.from(document.querySelectorAll('.issue-checkbox input:checked')).map(cb => cb.value);
    const notes = document.getElementById('analyzeNotes').value.trim();
    
    if (!reportId) { showToast('Report not found', 'error'); return; }
    if (!score || score < 300 || score > 900) { showToast('Please enter a valid CIBIL score (300-900)', 'warning'); return; }
    
    const result = await apiCall('submit_analysis', 'POST', { report_id: reportId, score, issues, notes });
    if (result.success) {
        showToast('Analysis submitted successfully!', 'success');
        closeModal('analyzeModal');
        loadDashboard();
        loadPending();
        loadAnalyzed();
        loadCIBIL();
    } else {
        showToast(result.error || 'Failed to submit analysis', 'error');
    }
}

// ── CIBIL ─────────────────────────────────────────────────────────────
async function loadCIBIL() {
    const data = await apiCall('get_cibil_analysis');
    if (!data.success) { showToast('Failed to load CIBIL data', 'error'); return; }
    
    // Score distribution chart
    if (data.score_distribution) {
        destroyChart('scoreDistributionChart');
        const ctx = document.getElementById('scoreDistributionChart').getContext('2d');
        const colors = ['#059669', '#3b82f6', '#d97706', '#dc2626', '#9ca3af'];
        charts.scoreDistributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.score_distribution.labels || [],
                datasets: [{
                    label: 'Clients',
                    data: data.score_distribution.values || [],
                    backgroundColor: colors.slice(0, data.score_distribution.labels?.length || 0),
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
    
    // Scores table
    const body = document.getElementById('scoresBody');
    if (data.scores && data.scores.length) {
        body.innerHTML = data.scores.map(s => `
            <tr>
                <td><strong>${escHtml(s.client_name)}</strong></td>
                <td>${getScoreBadge(s.cibil_score)}</td>
                <td>${s.experian_score || '—'}</td>
                <td>${s.equifax_score || '—'}</td>
                <td>${s.crif_score || '—'}</td>
                <td>${new Date(s.updated_at).toLocaleDateString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-table"></i><p>No scores found</p></div></td></tr>';
    }
}

// ── BUREAUS ───────────────────────────────────────────────────────────
async function loadBureaus() {
    const data = await apiCall('get_bureau_stats');
    if (!data.success) { showToast('Failed to load bureau data', 'error'); return; }
    
    document.getElementById('bureauCIBIL').textContent = data.cibil_avg || '—';
    document.getElementById('bureauExperian').textContent = data.experian_avg || '—';
    document.getElementById('bureauEquifax').textContent = data.equifax_avg || '—';
    document.getElementById('bureauCRIF').textContent = data.crif_avg || '—';
    
    if (data.bureau_data) {
        destroyChart('bureauChart');
        const ctx = document.getElementById('bureauChart').getContext('2d');
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6'];
        charts.bureauChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.bureau_data.labels || [],
                datasets: [{
                    label: 'Average Score',
                    data: data.bureau_data.values || [],
                    backgroundColor: colors.slice(0, data.bureau_data.labels?.length || 0),
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

// ── ISSUES ────────────────────────────────────────────────────────────
async function loadIssues() {
    const stats = await apiCall('get_issues_stats');
    if (stats.success) {
        document.getElementById('issueWrittenOff').textContent = stats.written_off || 0;
        document.getElementById('issueSettled').textContent = stats.settled || 0;
        document.getElementById('issueOverdue').textContent = stats.overdue || 0;
        document.getElementById('issueIncorrect').textContent = stats.incorrect_enquiries || 0;
        document.getElementById('issueDuplicate').textContent = stats.duplicate_loans || 0;
    }
    
    const data = await apiCall('get_issues_list');
    const body = document.getElementById('issuesBody');
    
    if (data.success && data.issues && data.issues.length) {
        body.innerHTML = data.issues.map(i => `
            <tr>
                <td><strong>${escHtml(i.client_name)}</strong></td>
                <td><span class="issue-tag issue-${i.issue_type}">${escHtml(i.type_label || i.issue_type)}</span></td>
                <td>${escHtml(i.bank_name || '-')}</td>
                <td>${i.amount ? '₹' + Number(i.amount).toLocaleString('en-IN') : '-'}</td>
                <td>${getStatusBadge(i.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewIssue(${i.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>No issues found</p></div></td></tr>';
    }
}

function viewIssue(id) {
    showToast('Viewing issue details...', 'info');
}

// ── STRATEGIES ────────────────────────────────────────────────────────
async function loadStrategies() {
    const data = await apiCall('get_strategies');
    const body = document.getElementById('strategiesBody');
    
    if (data.success && data.strategies && data.strategies.length) {
        body.innerHTML = data.strategies.map(s => `
            <tr>
                <td><span class="badge badge-info">${escHtml(s.issue_type)}</span></td>
                <td>${escHtml(s.strategy)}</td>
                <td>${s.estimated_days || 30} days</td>
                <td><span class="badge badge-success">${s.success_rate || 70}%</span></td>
                <td>
                    <button class="btn btn-danger btn-xs" onclick="deleteStrategy(${s.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No strategies found</p></div></td></tr>';
    }
}

async function addStrategy() {
    const issue_type = document.getElementById('strategyIssue').value;
    const strategy = document.getElementById('strategyDesc').value.trim();
    const estimated_days = parseInt(document.getElementById('strategyTime').value) || 30;
    const success_rate = parseInt(document.getElementById('strategyRate').value) || 70;
    
    if (!strategy) { showToast('Strategy is required', 'warning'); return; }
    
    const result = await apiCall('add_strategy', 'POST', { issue_type, strategy, estimated_days, success_rate });
    if (result.success) {
        showToast('Strategy added!', 'success');
        closeModal('addStrategyModal');
        document.getElementById('strategyDesc').value = '';
        loadStrategies();
    } else {
        showToast(result.error || 'Failed to add strategy', 'error');
    }
}

async function deleteStrategy(id) {
    if (!confirm('Delete this strategy?')) return;
    const result = await apiCall('delete_strategy', 'POST', { id });
    if (result.success) {
        showToast('Strategy deleted', 'success');
        loadStrategies();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    // Analyst performance chart
    if (data.analyst_performance) {
        destroyChart('analystChart');
        const ctx = document.getElementById('analystChart').getContext('2d');
        charts.analystChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.analyst_performance.labels || [],
                datasets: [{
                    label: 'Reports Analyzed',
                    data: data.analyst_performance.values || [],
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
    
    // Monthly trends chart
    if (data.monthly_trends) {
        destroyChart('trendChart');
        const ctx = document.getElementById('trendChart').getContext('2d');
        charts.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.monthly_trends.labels || [],
                datasets: [{
                    label: 'Reports',
                    data: data.monthly_trends.values || [],
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

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportReports() { showToast('Exporting reports...', 'info'); }
function exportAnalyzed() { showToast('Exporting analyzed reports...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'p') showSection('pending');
    if (e.altKey && e.key === 'a') showSection('analyzed');
    if (e.altKey && e.key === 'i') showSection('issues');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadPending();
loadAnalyzed();
loadCIBIL();
loadBureaus();
loadIssues();
loadStrategies();
loadAnalytics();

console.log('✅ Credit Analyst Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>