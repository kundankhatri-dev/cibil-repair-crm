<?php
// ============================================================
// FINANCE DASHBOARD - FULLY INTEGRATED
// Access: finance_team, admin, manager, super_admin
// Purpose: Manage payments, invoices, GST, commissions, payouts
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

// ── AUTH: allow finance_team, admin, manager, super_admin ──────────
$allowed_roles = ['finance_team', 'admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Finance Officer';
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
            $period = $_GET['period'] ?? '6m';
            
            // Total revenue
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
            $total_revenue = $stmt->fetch()['total'] ?? 0;
            
            // Total invoices
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices");
            $total_invoices = $stmt->fetch()['count'] ?? 0;
            
            // Partner commission due
            $stmt = $pdo->query("SELECT SUM(commission_amount) as total FROM commissions WHERE status = 'pending'");
            $partner_commission_due = $stmt->fetch()['total'] ?? 0;
            
            // Pending payouts
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM payouts WHERE status = 'pending'");
            $pending_payouts = $stmt->fetch()['total'] ?? 0;
            
            // Revenue trend
            $months = $period === '12m' ? 12 : 6;
            $trend_labels = [];
            $trend_values = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $trend_labels[] = date('M', strtotime($date));
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $trend_values[] = (float)($stmt->fetch()['total'] ?? 0);
            }
            
            // Package revenue
            $stmt = $pdo->query("
                SELECT package_name, SUM(amount) as total 
                FROM payments 
                WHERE status = 'paid' 
                GROUP BY package_name
            ");
            $package_data = $stmt->fetchAll();
            $package_labels = [];
            $package_values = [];
            foreach ($package_data as $p) {
                if ($p['package_name']) {
                    $package_labels[] = $p['package_name'];
                    $package_values[] = (float)$p['total'];
                }
            }
            
            // Recent payments
            $stmt = $pdo->query("
                SELECT p.*, c.name as client_name 
                FROM payments p
                LEFT JOIN customers c ON p.client_id = c.id
                ORDER BY p.payment_date DESC 
                LIMIT 10
            ");
            $recent_payments = $stmt->fetchAll();
            
            // Revenue change
            $last_month = date('Y-m', strtotime('-1 month'));
            $this_month = date('Y-m');
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
            $stmt->execute([$last_month]);
            $last_month_total = $stmt->fetch()['total'] ?? 0;
            $stmt->execute([$this_month]);
            $this_month_total = $stmt->fetch()['total'] ?? 0;
            $revenue_change = $last_month_total > 0 ? round((($this_month_total - $last_month_total) / $last_month_total) * 100) : 0;
            
            // New invoices this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
            $stmt->execute([$this_month]);
            $new_invoices = $stmt->fetch()['count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'total_revenue' => (float)$total_revenue,
                'total_invoices' => (int)$total_invoices,
                'partner_commission_due' => (float)$partner_commission_due,
                'pending_payouts' => (float)$pending_payouts,
                'revenue_change' => (int)$revenue_change,
                'new_invoices' => (int)$new_invoices,
                'revenue_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
                'package_revenue' => ['labels' => $package_labels, 'values' => $package_values],
                'recent_payments' => $recent_payments
            ]);
            exit;
        }
        
        // ── GET PAYMENTS ─────────────────────────────────────────────
        if ($action === 'get_payments') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $from = $_GET['from'] ?? '';
            $to = $_GET['to'] ?? '';
            
            $sql = "SELECT p.*, c.name as client_name FROM payments p LEFT JOIN customers c ON p.client_id = c.id WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND c.name LIKE ?";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND p.status = ?";
                $params[] = $status;
            }
            if ($from) {
                $sql .= " AND p.payment_date >= ?";
                $params[] = $from;
            }
            if ($to) {
                $sql .= " AND p.payment_date <= ?";
                $params[] = $to;
            }
            
            $sql .= " ORDER BY p.payment_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'payments' => $payments]);
            exit;
        }
        
        // ── ADD PAYMENT ──────────────────────────────────────────────
        if ($action === 'add_payment') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $package_name = $input['package_name'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $payment_date = $input['payment_date'] ?? date('Y-m-d');
            $payment_mode = $input['payment_mode'] ?? 'UPI';
            $transaction_id = $input['transaction_id'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if (!$client_id || $amount <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO payments (client_id, package_name, amount, payment_date, payment_mode, transaction_id, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', NOW())
            ");
            $stmt->execute([$client_id, $package_name, $amount, $payment_date, $payment_mode, $transaction_id, $notes]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Payment Recorded', "Payment of ₹$amount from client ID $client_id"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET CLIENTS ──────────────────────────────────────────────
        if ($action === 'get_clients') {
            $stmt = $pdo->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name");
            $clients = $stmt->fetchAll();
            echo json_encode(['success' => true, 'clients' => $clients]);
            exit;
        }
        
        // ── GET EMPLOYEES ────────────────────────────────────────────
        if ($action === 'get_employees') {
            $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE status = 'active' ORDER BY first_name");
            $employees = $stmt->fetchAll();
            echo json_encode(['success' => true, 'employees' => $employees]);
            exit;
        }
        
        // ── GET PACKAGES ─────────────────────────────────────────────
        if ($action === 'get_packages') {
            $stmt = $pdo->query("
                SELECT 
                    package_name as name,
                    COUNT(*) as sales,
                    SUM(amount) as revenue,
                    COUNT(DISTINCT client_id) as active_clients,
                    AVG(amount) as avg_price
                FROM payments 
                WHERE status = 'paid' 
                GROUP BY package_name
            ");
            $packages = $stmt->fetchAll();
            
            // Get counts for each package
            $basic_count = 0;
            $premium_count = 0;
            $corporate_count = 0;
            $loan_count = 0;
            
            foreach ($packages as $p) {
                $name = strtolower($p['name'] ?? '');
                if (strpos($name, 'basic') !== false) $basic_count = $p['sales'] ?? 0;
                elseif (strpos($name, 'premium') !== false) $premium_count = $p['sales'] ?? 0;
                elseif (strpos($name, 'corporate') !== false) $corporate_count = $p['sales'] ?? 0;
                elseif (strpos($name, 'loan') !== false) $loan_count = $p['sales'] ?? 0;
            }
            
            echo json_encode([
                'success' => true,
                'packages' => $packages,
                'basic_count' => $basic_count,
                'premium_count' => $premium_count,
                'corporate_count' => $corporate_count,
                'loan_count' => $loan_count
            ]);
            exit;
        }
        
        // ── GET INVOICES ─────────────────────────────────────────────
        if ($action === 'get_invoices') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT i.*, c.name as client_name FROM invoices i LEFT JOIN customers c ON i.client_id = c.id WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (i.invoice_no LIKE ? OR c.name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status) {
                $sql .= " AND i.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY i.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $invoices = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'invoices' => $invoices]);
            exit;
        }
        
        // ── GENERATE INVOICE ─────────────────────────────────────────
        if ($action === 'generate_invoice') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $client_id = (int)($input['client_id'] ?? 0);
            $package_name = $input['package_name'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $gst = (float)($input['gst'] ?? 0);
            $total = (float)($input['total'] ?? 0);
            $due_date = $input['due_date'] ?? date('Y-m-d', strtotime('+15 days'));
            
            if (!$client_id || $amount <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            $invoice_no = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices (invoice_no, client_id, package_name, amount, gst, total, due_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$invoice_no, $client_id, $package_name, $amount, $gst, $total, $due_date]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Invoice Generated', "Invoice $invoice_no for ₹$total"]);
            
            echo json_encode(['success' => true, 'invoice_no' => $invoice_no]);
            exit;
        }
        
        // ── GET GST STATS ────────────────────────────────────────────
        if ($action === 'get_gst_stats') {
            // GST collected (output tax)
            $stmt = $pdo->query("SELECT SUM(gst) as total FROM invoices WHERE status = 'paid'");
            $gst_collected = $stmt->fetch()['total'] ?? 0;
            
            // GST paid (input tax)
            $stmt = $pdo->query("SELECT SUM(gst) as total FROM expenses WHERE gst_applicable = 1");
            $gst_paid = $stmt->fetch()['total'] ?? 0;
            
            // GST returns
            $stmt = $pdo->query("
                SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as period,
                    SUM(amount) as taxable_value,
                    SUM(gst * 0.5) as cgst,
                    SUM(gst * 0.5) as sgst,
                    0 as igst,
                    SUM(gst) as total_tax,
                    'filed' as status
                FROM invoices 
                WHERE status = 'paid' 
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY created_at DESC
                LIMIT 6
            ");
            $returns = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'gst_collected' => (float)$gst_collected,
                'gst_paid' => (float)$gst_paid,
                'gst_net' => (float)($gst_collected - $gst_paid),
                'returns' => $returns
            ]);
            exit;
        }
        
        // ── GET TDS ──────────────────────────────────────────────────
        if ($action === 'get_tds') {
            $stmt = $pdo->query("
                SELECT 
                    SUM(tds_amount) as total_deducted,
                    SUM(CASE WHEN status = 'deposited' THEN tds_amount ELSE 0 END) as total_deposited,
                    SUM(CASE WHEN status = 'pending' THEN tds_amount ELSE 0 END) as total_pending
                FROM tds_entries
            ");
            $totals = $stmt->fetch();
            
            $stmt = $pdo->query("SELECT * FROM tds_entries ORDER BY created_at DESC");
            $entries = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_deducted' => (float)($totals['total_deducted'] ?? 0),
                'total_deposited' => (float)($totals['total_deposited'] ?? 0),
                'total_pending' => (float)($totals['total_pending'] ?? 0),
                'entries' => $entries
            ]);
            exit;
        }
        
        // ── ADD TDS ──────────────────────────────────────────────────
        if ($action === 'add_tds') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $deductee_name = $input['deductee_name'] ?? '';
            $pan = strtoupper($input['pan'] ?? '');
            $section = $input['section'] ?? '194C';
            $tds_rate = (float)($input['tds_rate'] ?? 10);
            $amount_paid = (float)($input['amount_paid'] ?? 0);
            $tds_amount = (float)($input['tds_amount'] ?? 0);
            $month = $input['month'] ?? date('Y-m');
            
            if (!$deductee_name || !$pan || $amount_paid <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO tds_entries (deductee_name, pan, section, tds_rate, amount_paid, tds_amount, month, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$deductee_name, $pan, $section, $tds_rate, $amount_paid, $tds_amount, $month]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE TDS ──────────────────────────────────────────────
        if ($action === 'update_tds') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE tds_entries SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PARTNER COMMISSION ──────────────────────────────────
        if ($action === 'get_partner_commission') {
            $stmt = $pdo->query("
                SELECT 
                    SUM(commission_amount) as total,
                    SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending
                FROM commissions
            ");
            $totals = $stmt->fetch();
            
            $stmt = $pdo->query("
                SELECT c.*, p.name as partner_name, cl.name as client_name 
                FROM commissions c
                LEFT JOIN partners p ON c.partner_id = p.id
                LEFT JOIN customers cl ON c.client_id = cl.id
                ORDER BY c.created_at DESC
            ");
            $commissions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total' => (float)($totals['total'] ?? 0),
                'paid' => (float)($totals['paid'] ?? 0),
                'pending' => (float)($totals['pending'] ?? 0),
                'commissions' => $commissions
            ]);
            exit;
        }
        
        // ── UPDATE COMMISSION ────────────────────────────────────────
        if ($action === 'update_commission') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE commissions SET status = ?, paid_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET INCENTIVES ───────────────────────────────────────────
        if ($action === 'get_incentives') {
            $month = $_GET['month'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT i.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name 
                    FROM incentives i 
                    LEFT JOIN employees e ON i.employee_id = e.id 
                    WHERE 1=1";
            $params = [];
            
            if ($month) {
                $sql .= " AND i.month = ?";
                $params[] = $month;
            }
            if ($status) {
                $sql .= " AND i.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY i.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $incentives = $stmt->fetchAll();
            
            // Get totals
            $stmt = $pdo->query("
                SELECT 
                    SUM(amount) as total,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending
                FROM incentives
            ");
            $totals = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'total' => (float)($totals['total'] ?? 0),
                'paid' => (float)($totals['paid'] ?? 0),
                'pending' => (float)($totals['pending'] ?? 0),
                'incentives' => $incentives
            ]);
            exit;
        }
        
        // ── ADD INCENTIVE ────────────────────────────────────────────
        if ($action === 'add_incentive') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $employee_id = (int)($input['employee_id'] ?? 0);
            $type = $input['type'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $month = $input['month'] ?? date('Y-m');
            $remarks = $input['remarks'] ?? '';
            
            if (!$employee_id || $amount <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO incentives (employee_id, type, amount, month, remarks, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$employee_id, $type, $amount, $month, $remarks]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE INCENTIVE ─────────────────────────────────────────
        if ($action === 'update_incentive') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE incentives SET status = ?, paid_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PAYOUTS ──────────────────────────────────────────────
        if ($action === 'get_payouts') {
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT p.*, u.name as recipient_name FROM payouts p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND p.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY p.request_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $payouts = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'payouts' => $payouts]);
            exit;
        }
        
        // ── PROCESS PAYOUT ───────────────────────────────────────────
        if ($action === 'process_payout') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE payouts SET status = 'processed', processed_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET ANALYTICS ────────────────────────────────────────────
        if ($action === 'get_analytics') {
            // Financial summary - last 12 months
            $labels = [];
            $revenue = [];
            $expenses = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($date));
                
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $revenue[] = (float)($stmt->fetch()['total'] ?? 0);
                
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?");
                $stmt->execute([$date]);
                $expenses[] = (float)($stmt->fetch()['total'] ?? 0);
            }
            
            // Top partners
            $stmt = $pdo->query("
                SELECT p.name, SUM(c.commission_amount) as total_commission
                FROM commissions c
                LEFT JOIN partners p ON c.partner_id = p.id
                GROUP BY c.partner_id
                ORDER BY total_commission DESC
                LIMIT 5
            ");
            $top_partners = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'financial_summary' => [
                    'labels' => $labels,
                    'revenue' => $revenue,
                    'expenses' => $expenses
                ],
                'top_partners' => [
                    'labels' => array_column($top_partners, 'name'),
                    'values' => array_column($top_partners, 'total_commission')
                ]
            ]);
            exit;
        }
        
        // ── GET RECONCILIATION ───────────────────────────────────────
        if ($action === 'get_reconciliation') {
            $stmt = $pdo->query("
                SELECT * FROM reconciliation 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $transactions = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'transactions' => $transactions]);
            exit;
        }
        
        // ── RECONCILE TRANSACTION ────────────────────────────────────
        if ($action === 'reconcile_transaction') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE reconciliation SET status = 'matched', reconciled_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
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
<title>Finance Dashboard | CIBIL Repair</title>

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
.stat-change { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
.stat-change.up { color: var(--success); }
.stat-change.down { color: var(--danger); }

/* CHARTS */
.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
.chart-wrap { position: relative; height: 220px; }

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
.badge-purple { background: var(--purple-bg); color: var(--purple); }

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
        <div class="brand-icon">FD</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Finance Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>

        <div class="nav-section-label">Revenue</div>
        <div class="nav-item" data-section="payments">
            <i class="fas fa-credit-card"></i>
            <span class="nav-label">Client Payments</span>
        </div>
        <div class="nav-item" data-section="packages">
            <i class="fas fa-box"></i>
            <span class="nav-label">Packages</span>
        </div>
        <div class="nav-item" data-section="invoices">
            <i class="fas fa-file-invoice"></i>
            <span class="nav-label">Invoices</span>
        </div>

        <div class="nav-section-label">Tax</div>
        <div class="nav-item" data-section="gst">
            <i class="fas fa-file-invoice-dollar"></i>
            <span class="nav-label">GST Reports</span>
        </div>
        <div class="nav-item" data-section="tds">
            <i class="fas fa-percent"></i>
            <span class="nav-label">TDS Management</span>
        </div>

        <div class="nav-section-label">Commissions</div>
        <div class="nav-item" data-section="partnerCommission">
            <i class="fas fa-handshake"></i>
            <span class="nav-label">Partner Commission</span>
        </div>
        <div class="nav-item" data-section="employeeIncentives">
            <i class="fas fa-users"></i>
            <span class="nav-label">Employee Incentives</span>
        </div>
        <div class="nav-item" data-section="payouts">
            <i class="fas fa-wallet"></i>
            <span class="nav-label">Payouts</span>
        </div>

        <div class="nav-section-label">Reports</div>
        <div class="nav-item" data-section="analytics">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-label">Analytics</span>
        </div>
        <div class="nav-item" data-section="reconciliation">
            <i class="fas fa-balance-scale"></i>
            <span class="nav-label">Reconciliation</span>
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
            <span class="page-title" id="pageTitle">Finance Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="stat-value" id="totalRevenue">—</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change" id="revenueChange">Loading…</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-file-invoice"></i></span>
                    <div class="stat-value" id="totalInvoices">—</div>
                    <div class="stat-label">Total Invoices</div>
                    <div class="stat-change" id="invoiceChange">Loading…</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-handshake"></i></span>
                    <div class="stat-value" id="partnerCommissionStat">—</div>
                    <div class="stat-label">Partner Commission Due</div>
                    <div class="stat-change" id="commissionChange">Due this month</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-wallet"></i></span>
                    <div class="stat-value" id="pendingPayouts">—</div>
                    <div class="stat-label">Pending Payouts</div>
                    <div class="stat-change" id="payoutChange">Awaiting processing</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Revenue Trend</div>
                    <select id="revenuePeriod" class="form-select" style="width:120px;padding:8px 12px;" onchange="loadDashboard()">
                        <option value="6m">6 Months</option>
                        <option value="12m">12 Months</option>
                    </select>
                </div>
                <div class="card-body chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Revenue by Package</div>
                </div>
                <div class="card-body chart-wrap" style="max-width:400px;margin:0 auto;">
                    <canvas id="packageChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Transactions</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addPaymentModal')">
                        <i class="fas fa-plus"></i> Add Payment
                    </button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Client</th><th>Package</th><th>Amount</th><th>Mode</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="recentBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PAYMENTS ====== -->
        <div class="section" id="paymentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-credit-card"></i> Client Payments</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addPaymentModal')"><i class="fas fa-plus"></i> Add</button>
                        <button class="btn btn-success btn-sm" onclick="exportPayments()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="paymentSearch" placeholder="Search client…" oninput="debounce(loadPayments, 400)()">
                    </div>
                    <select class="form-select" id="paymentStatusFilter" onchange="loadPayments()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                    <input type="date" class="form-select" id="dateFrom" onchange="loadPayments()" style="width:150px;padding:8px 12px;">
                    <input type="date" class="form-select" id="dateTo" onchange="loadPayments()" style="width:150px;padding:8px 12px;">
                    <button class="btn btn-outline btn-sm" onclick="clearPaymentFilters()"><i class="fas fa-times"></i> Clear</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#ID</th><th>Date</th><th>Client</th><th>Package</th><th>Amount</th><th>Mode</th><th>Status</th><th>Txn ID</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="paymentsBody">
                            <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PACKAGES ====== -->
        <div class="section" id="packagesSection">
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card green"><div class="stat-value" id="packageBasic">—</div><div class="stat-label">Basic Package</div></div>
                <div class="stat-card amber"><div class="stat-value" id="packagePremium">—</div><div class="stat-label">Premium Package</div></div>
                <div class="stat-card blue"><div class="stat-value" id="packageCorporate">—</div><div class="stat-label">Corporate Package</div></div>
                <div class="stat-card purple"><div class="stat-value" id="packageLoan">—</div><div class="stat-label">Loan Assistance</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-box"></i> Package Performance</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Package</th><th>Price</th><th>Total Sales</th><th>Revenue</th><th>Active Clients</th></tr></thead>
                        <tbody id="packageBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== INVOICES ====== -->
        <div class="section" id="invoicesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-invoice"></i> Invoices</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal('generateInvoiceModal')"><i class="fas fa-plus"></i> Generate</button>
                        <button class="btn btn-success btn-sm" onclick="exportInvoices()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="invoiceSearch" placeholder="Search invoice…" oninput="debounce(loadInvoices, 400)()">
                    </div>
                    <select class="form-select" id="invoiceStatusFilter" onchange="loadInvoices()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Invoice No</th><th>Client</th><th>Date</th><th>Amount</th><th>GST</th><th>Total</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="invoicesBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== GST ====== -->
        <div class="section" id="gstSection">
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card green"><div class="stat-value" id="gstCollected">—</div><div class="stat-label">GST Collected</div></div>
                <div class="stat-card red"><div class="stat-value" id="gstPaid">—</div><div class="stat-label">GST Paid (Input)</div></div>
                <div class="stat-card purple"><div class="stat-value" id="gstNet">—</div><div class="stat-label">Net GST Liability</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-invoice-dollar"></i> GST Returns</div>
                    <button class="btn btn-primary btn-sm" onclick="downloadGST()"><i class="fas fa-download"></i> Download GSTR-1</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Period</th><th>Taxable Value</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total Tax</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="gstBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TDS ====== -->
        <div class="section" id="tdsSection">
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card green"><div class="stat-value" id="tdsDeducted">—</div><div class="stat-label">TDS Deducted (FY)</div></div>
                <div class="stat-card blue"><div class="stat-value" id="tdsDeposited">—</div><div class="stat-label">TDS Deposited</div></div>
                <div class="stat-card amber"><div class="stat-value" id="tdsPending">—</div><div class="stat-label">TDS Pending</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-percent"></i> TDS Management</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addTDSModal')"><i class="fas fa-plus"></i> Add Entry</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Deductee</th><th>PAN</th><th>Section</th><th>Amount Paid</th><th>TDS Rate</th><th>TDS Amount</th><th>Month</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="tdsBody">
                            <tr><td colspan="9"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PARTNER COMMISSION ====== -->
        <div class="section" id="partnerCommissionSection">
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card purple"><div class="stat-value" id="partnerTotal">—</div><div class="stat-label">Total Commission</div></div>
                <div class="stat-card green"><div class="stat-value" id="partnerPaid">—</div><div class="stat-label">Paid</div></div>
                <div class="stat-card amber"><div class="stat-value" id="partnerPending">—</div><div class="stat-label">Pending</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-handshake"></i> Partner Commission</div>
                    <button class="btn btn-success btn-sm" onclick="exportPartnerCommission()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Partner</th><th>Client</th><th>Service</th><th>Amount</th><th>Rate</th><th>Commission</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="partnerCommissionBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== EMPLOYEE INCENTIVES ====== -->
        <div class="section" id="employeeIncentivesSection">
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card purple"><div class="stat-value" id="employeeTotal">—</div><div class="stat-label">Total Incentives</div></div>
                <div class="stat-card green"><div class="stat-value" id="employeePaid">—</div><div class="stat-label">Paid</div></div>
                <div class="stat-card amber"><div class="stat-value" id="employeePending">—</div><div class="stat-label">Pending</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Employee Incentives</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addIncentiveModal')"><i class="fas fa-plus"></i> Add Incentive</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="incentiveMonthFilter" onchange="loadIncentives()" style="width:150px;padding:8px 12px;">
                        <option value="">All Months</option>
                    </select>
                    <select class="form-select" id="incentiveStatusFilter" onchange="loadIncentives()" style="width:140px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Employee</th><th>Type</th><th>Amount</th><th>Month</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="incentiveBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PAYOUTS ====== -->
        <div class="section" id="payoutsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-wallet"></i> Payout Requests</div>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="payoutStatusFilter" onchange="loadPayouts()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processed">Processed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Request ID</th><th>Recipient</th><th>Type</th><th>Amount</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="payoutBody">
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
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Revenue vs Expenses</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trophy"></i> Top Partners by Commission</div>
                </div>
                <div class="card-body chart-wrap" style="height:220px;">
                    <canvas id="topPartnersChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ====== RECONCILIATION ====== -->
        <div class="section" id="reconciliationSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-balance-scale"></i> Bank Reconciliation</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('uploadStatementModal')"><i class="fas fa-upload"></i> Upload Statement</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Description</th><th>Bank Amount</th><th>System Amount</th><th>Variance</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="reconcileBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Add Payment Modal -->
<div class="modal-overlay" id="addPaymentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-credit-card"></i> Record Payment</span>
            <button class="modal-close" onclick="closeModal('addPaymentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Client <span class="form-required">*</span></label>
                <select class="form-select" id="paymentClient">
                    <option value="">— Select Client —</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Package <span class="form-required">*</span></label>
                <select class="form-select" id="paymentPackage">
                    <option value="">— Select Package —</option>
                    <option value="Basic Package">Basic Package</option>
                    <option value="Premium Package">Premium Package</option>
                    <option value="Corporate Package">Corporate Package</option>
                    <option value="Loan Assistance">Loan Assistance</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Amount (₹) <span class="form-required">*</span></label>
                    <input class="form-input" id="paymentAmount" type="number" min="0" placeholder="0.00">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Payment Date <span class="form-required">*</span></label>
                    <input type="date" class="form-input" id="paymentDate">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Mode</label>
                <select class="form-select" id="paymentMode">
                    <option value="UPI">UPI</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="Net Banking">Net Banking</option>
                    <option value="NEFT/RTGS">NEFT/RTGS</option>
                    <option value="Cash">Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Transaction ID / Reference</label>
                <input class="form-input" id="transactionId" placeholder="Optional">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="paymentNotes" rows="2" placeholder="Optional notes…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addPaymentModal')">Cancel</button>
            <button class="btn btn-primary" id="addPaymentBtn" onclick="addPayment()"><i class="fas fa-save"></i> Record Payment</button>
        </div>
    </div>
</div>

<!-- Generate Invoice Modal -->
<div class="modal-overlay" id="generateInvoiceModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-file-invoice"></i> Generate Invoice</span>
            <button class="modal-close" onclick="closeModal('generateInvoiceModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Client <span class="form-required">*</span></label>
                <select class="form-select" id="invoiceClient">
                    <option value="">— Select Client —</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Package <span class="form-required">*</span></label>
                <select class="form-select" id="invoicePackage">
                    <option value="">— Select Package —</option>
                    <option value="Basic Package">Basic Package</option>
                    <option value="Premium Package">Premium Package</option>
                    <option value="Corporate Package">Corporate Package</option>
                    <option value="Loan Assistance">Loan Assistance</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Base Amount (₹) <span class="form-required">*</span></label>
                    <input class="form-input" id="invoiceAmount" type="number" min="0" placeholder="0.00" oninput="calcInvoiceGST()">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">GST (18%)</label>
                    <input class="form-input" id="invoiceGST" type="number" readonly style="background:var(--bg-sunken);">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Amount (₹)</label>
                <input class="form-input" id="invoiceTotal" type="number" readonly style="background:var(--bg-sunken);font-weight:700;">
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" class="form-input" id="invoiceDueDate">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('generateInvoiceModal')">Cancel</button>
            <button class="btn btn-primary" id="genInvoiceBtn" onclick="generateInvoice()"><i class="fas fa-file-invoice"></i> Generate</button>
        </div>
    </div>
</div>

<!-- Add TDS Modal -->
<div class="modal-overlay" id="addTDSModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-percent"></i> Add TDS Entry</span>
            <button class="modal-close" onclick="closeModal('addTDSModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Deductee Name <span class="form-required">*</span></label>
                    <input class="form-input" id="tdsDeducteeName" placeholder="Vendor / Employee">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">PAN <span class="form-required">*</span></label>
                    <input class="form-input" id="tdsDeducteePAN" placeholder="ABCDE1234F" maxlength="10">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Section</label>
                    <select class="form-select" id="tdsSection">
                        <option value="194C">194C – Contractor</option>
                        <option value="194J">194J – Professional Fees</option>
                        <option value="194I">194I – Rent</option>
                        <option value="194A">194A – Interest</option>
                        <option value="192">192 – Salary</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">TDS Rate (%)</label>
                    <input class="form-input" id="tdsSectionRate" type="number" value="10" min="0" max="30">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Amount Paid (₹) <span class="form-required">*</span></label>
                    <input class="form-input" id="tdsAmountPaid" type="number" min="0" oninput="calcTDS()">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">TDS Amount (₹)</label>
                    <input class="form-input" id="tdsTDSAmount" type="number" readonly style="background:var(--bg-sunken);">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Month <span class="form-required">*</span></label>
                <input type="month" class="form-input" id="tdsMonth">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addTDSModal')">Cancel</button>
            <button class="btn btn-primary" id="addTDSBtn" onclick="addTDS()"><i class="fas fa-save"></i> Save Entry</button>
        </div>
    </div>
</div>

<!-- Add Incentive Modal -->
<div class="modal-overlay" id="addIncentiveModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-users"></i> Add Employee Incentive</span>
            <button class="modal-close" onclick="closeModal('addIncentiveModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Employee <span class="form-required">*</span></label>
                <select class="form-select" id="incentiveEmployee">
                    <option value="">— Select Employee —</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Incentive Type</label>
                <select class="form-select" id="incentiveType">
                    <option value="Performance Bonus">Performance Bonus</option>
                    <option value="Referral Bonus">Referral Bonus</option>
                    <option value="Quarterly Bonus">Quarterly Bonus</option>
                    <option value="Diwali Bonus">Diwali Bonus</option>
                    <option value="Annual Bonus">Annual Bonus</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Amount (₹) <span class="form-required">*</span></label>
                    <input class="form-input" id="incentiveAmount" type="number" min="0" placeholder="0.00">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Month <span class="form-required">*</span></label>
                    <input type="month" class="form-input" id="incentiveMonth">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea class="form-textarea" id="incentiveRemarks" rows="2" placeholder="Optional…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addIncentiveModal')">Cancel</button>
            <button class="btn btn-primary" id="addIncentiveBtn" onclick="addIncentive()"><i class="fas fa-save"></i> Add Incentive</button>
        </div>
    </div>
</div>

<!-- Upload Statement Modal -->
<div class="modal-overlay" id="uploadStatementModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-upload"></i> Upload Bank Statement</span>
            <button class="modal-close" onclick="closeModal('uploadStatementModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info" style="background:var(--info-bg);color:var(--info-text);padding:12px 16px;border-radius:var(--radius-md);margin-bottom:16px;font-size:13px;">
                <i class="fas fa-info-circle"></i> Upload your bank statement in CSV or Excel format. The system will match entries against recorded payments automatically.
            </div>
            <div class="form-group">
                <label class="form-label">Bank Name <span class="form-required">*</span></label>
                <select class="form-select" id="bankName">
                    <option value="">— Select Bank —</option>
                    <option>HDFC Bank</option>
                    <option>ICICI Bank</option>
                    <option>SBI</option>
                    <option>Axis Bank</option>
                    <option>Kotak Bank</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Statement Period</label>
                <div class="form-row">
                    <div class="form-group flex-1"><input type="date" class="form-input" id="stmtFrom" placeholder="From"></div>
                    <div class="form-group flex-1"><input type="date" class="form-input" id="stmtTo" placeholder="To"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Statement File (CSV / XLSX) <span class="form-required">*</span></label>
                <input type="file" class="form-input" id="statementFile" accept=".csv,.xlsx,.xls">
                <div class="form-hint" style="font-size:11px;color:var(--text-muted);margin-top:4px;">Max file size: 5 MB</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('uploadStatementModal')">Cancel</button>
            <button class="btn btn-primary" onclick="uploadStatement()"><i class="fas fa-upload"></i> Upload & Match</button>
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
    localStorage.setItem('financeTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('financeTheme') || 'light'); })();

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
    dashboard: 'Finance Dashboard',
    payments: 'Client Payments',
    packages: 'Packages',
    invoices: 'Invoices',
    gst: 'GST Reports',
    tds: 'TDS Management',
    partnerCommission: 'Partner Commission',
    employeeIncentives: 'Employee Incentives',
    payouts: 'Payouts',
    analytics: 'Analytics',
    reconciliation: 'Reconciliation'
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
        payments: loadPayments,
        packages: loadPackages,
        invoices: loadInvoices,
        gst: loadGST,
        tds: loadTDS,
        partnerCommission: loadPartnerCommission,
        employeeIncentives: loadIncentives,
        payouts: loadPayouts,
        analytics: loadAnalytics,
        reconciliation: loadReconciliation
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
function todayStr() {
    return new Date().toISOString().split('T')[0];
}

function fmtINR(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN');
}

function getStatusBadge(status) {
    const map = {
        'paid': 'badge-success',
        'completed': 'badge-success',
        'active': 'badge-success',
        'matched': 'badge-success',
        'pending': 'badge-warning',
        'processing': 'badge-warning',
        'due': 'badge-warning',
        'failed': 'badge-danger',
        'rejected': 'badge-danger',
        'overdue': 'badge-danger',
        'refunded': 'badge-gray',
        'cancelled': 'badge-gray',
        'filed': 'badge-brand',
        'deposited': 'badge-info'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${escHtml(status)}</span>`;
}

function debounce(fn, delay) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

function setLoading(id, cols) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `<tr><td colspan="${cols}"><div class="empty-state"><div class="spinner"></div></div></td></tr>`;
}

function setEmpty(id, cols, msg = 'No records found') {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `<tr><td colspan="${cols}"><div class="empty-state"><i class="fas fa-inbox"></i><p>${msg}</p></div></td></tr>`;
}

function setBtnLoading(id, loading) {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span> Please wait…';
    } else {
        btn.innerHTML = btn.dataset.orig || btn.innerHTML;
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

// ── GST/TDS CALCULATIONS ────────────────────────────────────────────
function calcInvoiceGST() {
    const amt = parseFloat(document.getElementById('invoiceAmount').value) || 0;
    const gst = +(amt * 0.18).toFixed(2);
    const total = +(amt + gst).toFixed(2);
    document.getElementById('invoiceGST').value = gst;
    document.getElementById('invoiceTotal').value = total;
}

function calcTDS() {
    const amt = parseFloat(document.getElementById('tdsAmountPaid').value) || 0;
    const rate = parseFloat(document.getElementById('tdsSectionRate').value) || 10;
    document.getElementById('tdsTDSAmount').value = +(amt * rate / 100).toFixed(2);
}

function clearPaymentFilters() {
    document.getElementById('paymentSearch').value = '';
    document.getElementById('paymentStatusFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    loadPayments();
}

// ── DASHBOARD ─────────────────────────────────────────────────────────
async function loadDashboard() {
    const period = document.getElementById('revenuePeriod')?.value || '6m';
    const data = await apiCall(`get_dashboard_stats?period=${period}`);
    if (!data.success) { showToast(data.error || 'Dashboard load failed', 'error'); return; }

    document.getElementById('totalRevenue').textContent = fmtINR(data.total_revenue);
    document.getElementById('totalInvoices').textContent = data.total_invoices || 0;
    document.getElementById('partnerCommissionStat').textContent = fmtINR(data.partner_commission_due);
    document.getElementById('pendingPayouts').textContent = fmtINR(data.pending_payouts);

    const rc = document.getElementById('revenueChange');
    rc.textContent = `${data.revenue_change >= 0 ? '+' : ''}${data.revenue_change || 0}% from last month`;
    rc.className = 'stat-change ' + (data.revenue_change >= 0 ? 'up' : 'down');

    document.getElementById('invoiceChange').textContent = `+${data.new_invoices || 0} new this month`;

    // Revenue chart
    if (data.revenue_trend) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        if (charts.revenue) charts.revenue.destroy();
        charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.revenue_trend.labels || [],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: data.revenue_trend.values || [],
                    borderColor: '#0d9e78',
                    backgroundColor: 'rgba(13,158,120,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280' } },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280', callback: v => '₹' + v.toLocaleString() } }
                }
            }
        });
    }

    // Package chart
    if (data.package_revenue && data.package_revenue.labels && data.package_revenue.labels.length) {
        const ctx2 = document.getElementById('packageChart').getContext('2d');
        if (charts.package) charts.package.destroy();
        const colors = ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6'];
        charts.package = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: data.package_revenue.labels,
                datasets: [{
                    data: data.package_revenue.values,
                    backgroundColor: colors.slice(0, data.package_revenue.labels.length),
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

    // Recent payments
    const recentBody = document.getElementById('recentBody');
    if (data.recent_payments && data.recent_payments.length) {
        recentBody.innerHTML = data.recent_payments.map(p => `
            <tr>
                <td>${escHtml(p.payment_date || p.created_at)}</td>
                <td><strong>${escHtml(p.client_name || '—')}</strong></td>
                <td>${escHtml(p.package_name || '—')}</td>
                <td>${fmtINR(p.amount)}</td>
                <td>${escHtml(p.payment_mode || '—')}</td>
                <td>${getStatusBadge(p.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewPayment(${p.id})"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        recentBody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No recent transactions</p></div></td></tr>`;
    }
}

// ── PAYMENTS ──────────────────────────────────────────────────────────
async function loadPayments() {
    setLoading('paymentsBody', 9);
    const search = document.getElementById('paymentSearch')?.value || '';
    const status = document.getElementById('paymentStatusFilter')?.value || '';
    const from = document.getElementById('dateFrom')?.value || '';
    const to = document.getElementById('dateTo')?.value || '';
    const data = await apiCall(`get_payments?search=${encodeURIComponent(search)}&status=${status}&from=${from}&to=${to}`);
    const rows = data.payments || [];
    const body = document.getElementById('paymentsBody');
    if (rows.length) {
        body.innerHTML = rows.map(p => `
            <tr>
                <td>#${p.id}</td>
                <td>${escHtml(p.payment_date || p.created_at)}</td>
                <td><strong>${escHtml(p.client_name || '—')}</strong></td>
                <td>${escHtml(p.package_name || '—')}</td>
                <td>${fmtINR(p.amount)}</td>
                <td>${escHtml(p.payment_mode || '—')}</td>
                <td>${getStatusBadge(p.status)}</td>
                <td>${escHtml(p.transaction_id || '—')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewPayment(${p.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="downloadReceipt(${p.id})"><i class="fas fa-download"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('paymentsBody', 9, 'No payments found');
    }
}

async function addPayment() {
    const client_id = document.getElementById('paymentClient').value;
    const package_name = document.getElementById('paymentPackage').value;
    const amount = document.getElementById('paymentAmount').value;
    const payment_date = document.getElementById('paymentDate').value;
    const payment_mode = document.getElementById('paymentMode').value;
    const transaction_id = document.getElementById('transactionId').value;
    const notes = document.getElementById('paymentNotes').value;

    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!package_name) { showToast('Please select a package', 'warning'); return; }
    if (!amount || amount <= 0) { showToast('Enter a valid amount', 'warning'); return; }
    if (!payment_date) { showToast('Please select payment date', 'warning'); return; }

    setBtnLoading('addPaymentBtn', true);
    const result = await apiCall('add_payment', 'POST', { client_id, package_name, amount, payment_date, payment_mode, transaction_id, notes });
    setBtnLoading('addPaymentBtn', false);

    if (result.success) {
        showToast('Payment recorded successfully!', 'success');
        closeModal('addPaymentModal');
        document.getElementById('paymentClient').value = '';
        document.getElementById('paymentAmount').value = '';
        document.getElementById('transactionId').value = '';
        document.getElementById('paymentNotes').value = '';
        loadDashboard();
        loadPayments();
    } else {
        showToast(result.error || 'Failed to record payment', 'error');
    }
}

// ── PACKAGES ──────────────────────────────────────────────────────────
async function loadPackages() {
    setLoading('packageBody', 5);
    const data = await apiCall('get_packages');
    if (data.success) {
        document.getElementById('packageBasic').textContent = data.basic_count || 0;
        document.getElementById('packagePremium').textContent = data.premium_count || 0;
        document.getElementById('packageCorporate').textContent = data.corporate_count || 0;
        document.getElementById('packageLoan').textContent = data.loan_count || 0;
    }
    const rows = data.packages || [];
    const body = document.getElementById('packageBody');
    if (rows.length) {
        body.innerHTML = rows.map(p => `
            <tr>
                <td><strong>${escHtml(p.name)}</strong></td>
                <td>${fmtINR(p.avg_price || p.price || 0)}</td>
                <td>${p.sales || 0}</td>
                <td>${fmtINR(p.revenue || 0)}</td>
                <td>${p.active_clients || 0}</td>
            </tr>
        `).join('');
    } else {
        setEmpty('packageBody', 5, 'No package data');
    }
}

// ── INVOICES ──────────────────────────────────────────────────────────
async function loadInvoices() {
    setLoading('invoicesBody', 8);
    const search = document.getElementById('invoiceSearch')?.value || '';
    const status = document.getElementById('invoiceStatusFilter')?.value || '';
    const data = await apiCall(`get_invoices?search=${encodeURIComponent(search)}&status=${status}`);
    const rows = data.invoices || [];
    const body = document.getElementById('invoicesBody');
    if (rows.length) {
        body.innerHTML = rows.map(i => `
            <tr>
                <td><i class="fas fa-file-pdf" style="color:var(--danger)"></i> ${escHtml(i.invoice_no)}</td>
                <td>${escHtml(i.client_name || '—')}</td>
                <td>${escHtml(i.created_at)}</td>
                <td>${fmtINR(i.amount)}</td>
                <td>${fmtINR(i.gst || 0)}</td>
                <td><strong>${fmtINR(i.total || i.amount)}</strong></td>
                <td>${getStatusBadge(i.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="viewInvoice(${i.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="downloadInvoice(${i.id})"><i class="fas fa-download"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('invoicesBody', 8, 'No invoices found');
    }
}

async function generateInvoice() {
    const client_id = document.getElementById('invoiceClient').value;
    const package_name = document.getElementById('invoicePackage').value;
    const amount = parseFloat(document.getElementById('invoiceAmount').value) || 0;
    const due_date = document.getElementById('invoiceDueDate').value;

    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!package_name) { showToast('Please select a package', 'warning'); return; }
    if (amount <= 0) { showToast('Enter a valid amount', 'warning'); return; }

    const gst = +(amount * 0.18).toFixed(2);
    const total = +(amount + gst).toFixed(2);

    setBtnLoading('genInvoiceBtn', true);
    const result = await apiCall('generate_invoice', 'POST', { client_id, package_name, amount, gst, total, due_date });
    setBtnLoading('genInvoiceBtn', false);

    if (result.success) {
        showToast('Invoice generated successfully!', 'success');
        closeModal('generateInvoiceModal');
        document.getElementById('invoiceAmount').value = '';
        document.getElementById('invoiceGST').value = '';
        document.getElementById('invoiceTotal').value = '';
        loadInvoices();
    } else {
        showToast(result.error || 'Failed to generate invoice', 'error');
    }
}

// ── GST ──────────────────────────────────────────────────────────────
async function loadGST() {
    setLoading('gstBody', 8);
    const data = await apiCall('get_gst_stats');
    if (data.success) {
        document.getElementById('gstCollected').textContent = fmtINR(data.gst_collected);
        document.getElementById('gstPaid').textContent = fmtINR(data.gst_paid);
        document.getElementById('gstNet').textContent = fmtINR(data.gst_net);
    }
    const rows = data.returns || [];
    const body = document.getElementById('gstBody');
    if (rows.length) {
        body.innerHTML = rows.map(r => `
            <tr>
                <td><strong>${escHtml(r.period)}</strong></td>
                <td>${fmtINR(r.taxable_value)}</td>
                <td>${fmtINR(r.cgst)}</td>
                <td>${fmtINR(r.sgst)}</td>
                <td>${fmtINR(r.igst)}</td>
                <td><strong>${fmtINR(r.total_tax)}</strong></td>
                <td>${getStatusBadge(r.status)}</td>
                <td>
                    <button class="btn btn-primary btn-xs" onclick="downloadGSTR('${escHtml(r.period)}')"><i class="fas fa-download"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('gstBody', 8, 'No GST data');
    }
}

function downloadGST() { window.open('api/finance/download_gstr1.php', '_blank'); }
function downloadGSTR(period) { window.open(`api/finance/download_gstr1.php?period=${encodeURIComponent(period)}`, '_blank'); }

// ── TDS ──────────────────────────────────────────────────────────────
async function loadTDS() {
    setLoading('tdsBody', 9);
    const data = await apiCall('get_tds');
    if (data.success) {
        document.getElementById('tdsDeducted').textContent = fmtINR(data.total_deducted);
        document.getElementById('tdsDeposited').textContent = fmtINR(data.total_deposited);
        document.getElementById('tdsPending').textContent = fmtINR(data.total_pending);
    }
    const rows = data.entries || [];
    const body = document.getElementById('tdsBody');
    if (rows.length) {
        body.innerHTML = rows.map(t => `
            <tr>
                <td>${escHtml(t.deductee_name)}</td>
                <td><code>${escHtml(t.pan)}</code></td>
                <td>${escHtml(t.section)}</td>
                <td>${fmtINR(t.amount_paid)}</td>
                <td>${t.tds_rate}%</td>
                <td><strong>${fmtINR(t.tds_amount)}</strong></td>
                <td>${escHtml(t.month)}</td>
                <td>${getStatusBadge(t.status)}</td>
                <td>
                    ${t.status === 'pending' ? `<button class="btn btn-success btn-xs" onclick="markTDSDeposited(${t.id})"><i class="fas fa-check"></i></button>` : '—'}
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('tdsBody', 9, 'No TDS entries found');
    }
}

async function addTDS() {
    const deductee_name = document.getElementById('tdsDeducteeName').value.trim();
    const pan = document.getElementById('tdsDeducteePAN').value.trim().toUpperCase();
    const section = document.getElementById('tdsSection').value;
    const tds_rate = parseFloat(document.getElementById('tdsSectionRate').value) || 10;
    const amount_paid = parseFloat(document.getElementById('tdsAmountPaid').value) || 0;
    const tds_amount = parseFloat(document.getElementById('tdsTDSAmount').value) || 0;
    const month = document.getElementById('tdsMonth').value;

    if (!deductee_name) { showToast('Enter deductee name', 'warning'); return; }
    if (!pan || !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan)) { showToast('Enter valid PAN (e.g. ABCDE1234F)', 'warning'); return; }
    if (amount_paid <= 0) { showToast('Enter valid amount', 'warning'); return; }
    if (!month) { showToast('Select a month', 'warning'); return; }

    setBtnLoading('addTDSBtn', true);
    const result = await apiCall('add_tds', 'POST', { deductee_name, pan, section, tds_rate, amount_paid, tds_amount, month });
    setBtnLoading('addTDSBtn', false);

    if (result.success) {
        showToast('TDS entry saved!', 'success');
        closeModal('addTDSModal');
        document.getElementById('tdsDeducteeName').value = '';
        document.getElementById('tdsDeducteePAN').value = '';
        document.getElementById('tdsAmountPaid').value = '';
        document.getElementById('tdsTDSAmount').value = '';
        loadTDS();
    } else {
        showToast(result.error || 'Failed to save TDS entry', 'error');
    }
}

async function markTDSDeposited(id) {
    if (!confirm('Mark this TDS as deposited?')) return;
    const result = await apiCall('update_tds', 'POST', { id, status: 'deposited' });
    if (result.success) { showToast('TDS marked as deposited!', 'success'); loadTDS(); }
    else showToast(result.error || 'Update failed', 'error');
}

// ── PARTNER COMMISSION ──────────────────────────────────────────────
async function loadPartnerCommission() {
    setLoading('partnerCommissionBody', 8);
    const data = await apiCall('get_partner_commission');
    if (data.success) {
        document.getElementById('partnerTotal').textContent = fmtINR(data.total);
        document.getElementById('partnerPaid').textContent = fmtINR(data.paid);
        document.getElementById('partnerPending').textContent = fmtINR(data.pending);
    }
    const rows = data.commissions || [];
    const body = document.getElementById('partnerCommissionBody');
    if (rows.length) {
        body.innerHTML = rows.map(c => `
            <tr>
                <td><strong>${escHtml(c.partner_name || '—')}</strong></td>
                <td>${escHtml(c.client_name || '—')}</td>
                <td>${escHtml(c.service || '—')}</td>
                <td>${fmtINR(c.amount)}</td>
                <td>${c.commission_rate || 0}%</td>
                <td><strong>${fmtINR(c.commission_amount || 0)}</strong></td>
                <td>${getStatusBadge(c.status)}</td>
                <td>
                    ${c.status === 'pending' ? `<button class="btn btn-success btn-xs" onclick="markCommissionPaid(${c.id})"><i class="fas fa-check"></i> Pay</button>` : '—'}
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('partnerCommissionBody', 8, 'No commissions found');
    }
}

async function markCommissionPaid(id) {
    if (!confirm('Mark this commission as paid?')) return;
    const result = await apiCall('update_commission', 'POST', { id, status: 'paid' });
    if (result.success) { showToast('Commission marked as paid!', 'success'); loadPartnerCommission(); }
    else showToast(result.error || 'Update failed', 'error');
}

// ── EMPLOYEE INCENTIVES ─────────────────────────────────────────────
async function loadIncentives() {
    setLoading('incentiveBody', 6);
    const month = document.getElementById('incentiveMonthFilter')?.value || '';
    const status = document.getElementById('incentiveStatusFilter')?.value || '';
    const data = await apiCall(`get_incentives?month=${month}&status=${status}`);
    if (data.success) {
        document.getElementById('employeeTotal').textContent = fmtINR(data.total);
        document.getElementById('employeePaid').textContent = fmtINR(data.paid);
        document.getElementById('employeePending').textContent = fmtINR(data.pending);
    }
    const rows = data.incentives || [];
    const body = document.getElementById('incentiveBody');
    if (rows.length) {
        body.innerHTML = rows.map(i => `
            <tr>
                <td><strong>${escHtml(i.employee_name || '—')}</strong></td>
                <td>${escHtml(i.type)}</td>
                <td>${fmtINR(i.amount)}</td>
                <td>${escHtml(i.month)}</td>
                <td>${getStatusBadge(i.status)}</td>
                <td>
                    ${i.status === 'pending' ? `<button class="btn btn-success btn-xs" onclick="markIncentivePaid(${i.id})"><i class="fas fa-check"></i></button>` : '—'}
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('incentiveBody', 6, 'No incentives found');
    }
}

async function addIncentive() {
    const employee_id = document.getElementById('incentiveEmployee').value;
    const type = document.getElementById('incentiveType').value;
    const amount = document.getElementById('incentiveAmount').value;
    const month = document.getElementById('incentiveMonth').value;
    const remarks = document.getElementById('incentiveRemarks').value;

    if (!employee_id) { showToast('Please select an employee', 'warning'); return; }
    if (!amount || amount <= 0) { showToast('Enter a valid amount', 'warning'); return; }
    if (!month) { showToast('Please select a month', 'warning'); return; }

    setBtnLoading('addIncentiveBtn', true);
    const result = await apiCall('add_incentive', 'POST', { employee_id, type, amount, month, remarks });
    setBtnLoading('addIncentiveBtn', false);

    if (result.success) {
        showToast('Incentive added successfully!', 'success');
        closeModal('addIncentiveModal');
        document.getElementById('incentiveAmount').value = '';
        document.getElementById('incentiveRemarks').value = '';
        loadIncentives();
    } else {
        showToast(result.error || 'Failed to add incentive', 'error');
    }
}

async function markIncentivePaid(id) {
    if (!confirm('Mark this incentive as paid?')) return;
    const result = await apiCall('update_incentive', 'POST', { id, status: 'paid' });
    if (result.success) { showToast('Incentive marked as paid!', 'success'); loadIncentives(); }
    else showToast(result.error || 'Update failed', 'error');
}

// ── PAYOUTS ──────────────────────────────────────────────────────────
async function loadPayouts() {
    setLoading('payoutBody', 7);
    const status = document.getElementById('payoutStatusFilter')?.value || '';
    const data = await apiCall(`get_payouts?status=${status}`);
    const rows = data.payouts || [];
    const body = document.getElementById('payoutBody');
    if (rows.length) {
        body.innerHTML = rows.map(p => `
            <tr>
                <td>#${p.id}</td>
                <td><strong>${escHtml(p.recipient_name || '—')}</strong></td>
                <td>${escHtml(p.type || '—')}</td>
                <td>${fmtINR(p.amount)}</td>
                <td>${escHtml(p.request_date)}</td>
                <td>${getStatusBadge(p.status)}</td>
                <td>
                    ${p.status === 'pending' ? `<button class="btn btn-primary btn-xs" onclick="processPayout(${p.id})"><i class="fas fa-check"></i> Process</button>` : '—'}
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('payoutBody', 7, 'No payout requests');
    }
}

async function processPayout(id) {
    if (!confirm('Process this payout?')) return;
    const result = await apiCall('process_payout', 'POST', { id });
    if (result.success) { showToast('Payout processed!', 'success'); loadPayouts(); }
    else showToast(result.error || 'Failed to process payout', 'error');
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_analytics');
    if (data.success && data.financial_summary) {
        const ctx = document.getElementById('financialChart').getContext('2d');
        if (charts.financial) charts.financial.destroy();
        charts.financial = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.financial_summary.labels || [],
                datasets: [
                    { label: 'Revenue (₹)', data: data.financial_summary.revenue || [], backgroundColor: '#0d9e78' },
                    { label: 'Expenses (₹)', data: data.financial_summary.expenses || [], backgroundColor: '#dc2626' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: {
                    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280' } },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280', callback: v => '₹' + v.toLocaleString() } }
                }
            }
        });
    }

    // Top partners chart
    if (data.success && data.top_partners && data.top_partners.labels && data.top_partners.labels.length) {
        const ctx2 = document.getElementById('topPartnersChart').getContext('2d');
        if (charts.partners) charts.partners.destroy();
        charts.partners = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: data.top_partners.labels || [],
                datasets: [{
                    label: 'Commission (₹)',
                    data: data.top_partners.values || [],
                    backgroundColor: '#d97706'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280', callback: v => '₹' + v.toLocaleString() } },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }
}

// ── RECONCILIATION ────────────────────────────────────────────────────
async function loadReconciliation() {
    setLoading('reconcileBody', 7);
    const data = await apiCall('get_reconciliation');
    const rows = data.transactions || [];
    const body = document.getElementById('reconcileBody');
    if (rows.length) {
        body.innerHTML = rows.map(t => `
            <tr>
                <td>${escHtml(t.date)}</td>
                <td>${escHtml(t.description)}</td>
                <td>${fmtINR(t.bank_amount)}</td>
                <td>${fmtINR(t.system_amount)}</td>
                <td style="color:${(t.bank_amount - t.system_amount) !== 0 ? 'var(--danger)' : 'var(--success)'}">${fmtINR(t.bank_amount - t.system_amount)}</td>
                <td>${getStatusBadge(t.status)}</td>
                <td>
                    ${t.status !== 'matched' ? `<button class="btn btn-primary btn-xs" onclick="reconcile(${t.id})"><i class="fas fa-check"></i> Reconcile</button>` : '✅ Matched'}
                </td>
            </tr>
        `).join('');
    } else {
        setEmpty('reconcileBody', 7, 'No reconciliation data');
    }
}

async function reconcile(id) {
    const result = await apiCall('reconcile_transaction', 'POST', { id });
    if (result.success) { showToast('Transaction reconciled!', 'success'); loadReconciliation(); }
    else showToast(result.error || 'Reconciliation failed', 'error');
}

async function uploadStatement() {
    const bank = document.getElementById('bankName').value;
    const file = document.getElementById('statementFile').files[0];
    if (!bank) { showToast('Please select a bank', 'warning'); return; }
    if (!file) { showToast('Please select a file', 'warning'); return; }

    const formData = new FormData();
    formData.append('bank', bank);
    formData.append('from', document.getElementById('stmtFrom').value);
    formData.append('to', document.getElementById('stmtTo').value);
    formData.append('statement', file);
    formData.append('csrf', CSRF);

    showToast('Uploading and matching…', 'info');
    try {
        const res = await fetch('api/finance/upload_statement.php', { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();
        if (data.success) {
            showToast(`Matched ${data.matched || 0} of ${data.total || 0} transactions`, 'success');
            closeModal('uploadStatementModal');
            loadReconciliation();
        } else {
            showToast(data.error || 'Upload failed', 'error');
        }
    } catch (e) {
        showToast('Upload failed: ' + e.message, 'error');
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportPayments() { showToast('Preparing export…', 'info'); window.open('api/finance/export_payments.php', '_blank'); }
function exportInvoices() { showToast('Preparing export…', 'info'); window.open('api/finance/export_invoices.php', '_blank'); }
function exportPartnerCommission() { showToast('Preparing export…', 'info'); window.open('api/finance/export_commissions.php', '_blank'); }

function viewPayment(id) { window.open(`api/finance/view_payment.php?id=${id}`, '_blank'); }
function downloadReceipt(id) { window.open(`api/finance/download_receipt.php?id=${id}`, '_blank'); }
function viewInvoice(id) { window.open(`api/finance/view_invoice.php?id=${id}`, '_blank'); }
function downloadInvoice(id) { window.open(`api/finance/download_invoice.php?id=${id}`, '_blank'); }

// ── LOAD DROPDOWNS ───────────────────────────────────────────────────
async function loadClientsDropdown() {
    const data = await apiCall('get_clients');
    const clients = data.clients || [];
    const opts = '<option value="">— Select Client —</option>' + clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
    ['paymentClient', 'invoiceClient'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = opts;
    });
}

async function loadEmployeesDropdown() {
    const data = await apiCall('get_employees');
    const employees = data.employees || [];
    const el = document.getElementById('incentiveEmployee');
    if (!el) return;
    el.innerHTML = '<option value="">— Select Employee —</option>' + employees.map(e => `<option value="${e.id}">${escHtml(e.name)}</option>`).join('');
}

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'addPaymentModal' || modal.id === 'generateInvoiceModal') loadClientsDropdown();
            if (modal.id === 'addIncentiveModal') loadEmployeesDropdown();
            if (modal.id === 'addPaymentModal') {
                const dateEl = document.getElementById('paymentDate');
                if (dateEl && !dateEl.value) dateEl.value = todayStr();
            }
            if (modal.id === 'generateInvoiceModal') {
                document.getElementById('invoiceGST').value = '';
                document.getElementById('invoiceTotal').value = '';
                if (!document.getElementById('invoiceDueDate').value) {
                    const due = new Date();
                    due.setDate(due.getDate() + 15);
                    document.getElementById('invoiceDueDate').value = due.toISOString().split('T')[0];
                }
            }
            if (modal.id === 'addIncentiveModal') {
                const monthEl = document.getElementById('incentiveMonth');
                if (monthEl && !monthEl.value) monthEl.value = new Date().toISOString().slice(0, 7);
            }
            if (modal.id === 'addTDSModal') {
                const monthEl = document.getElementById('tdsMonth');
                if (monthEl && !monthEl.value) monthEl.value = new Date().toISOString().slice(0, 7);
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'p') showSection('payments');
    if (e.altKey && e.key === 'i') showSection('invoices');
    if (e.altKey && e.key === 'a') showSection('analytics');
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClientsDropdown();

console.log('✅ Finance Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>