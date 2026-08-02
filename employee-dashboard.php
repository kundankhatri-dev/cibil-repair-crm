<?php
// ============================================================
// EMPLOYEE DASHBOARD - FULLY INTEGRATED
// File: employee-dashboard.php
// Access: employee only
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

// ── AUTH: employees and admins can access ──────────────────────────
$allowed_roles = ['employee', 'admin', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Employee';
$user_role = $_SESSION['user_role'] ?? 'employee';
$is_admin = in_array($user_role, ['admin', 'super_admin']);

// ── Get Employee Details ──────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT e.*, 
           u.email as user_email,
           d.department_name,
           ds.designation_name
    FROM employees e
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations ds ON e.designation_id = ds.id
    WHERE e.user_id = ?
");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();

if (!$employee) {
    // Create employee record if it doesn't exist
    $stmt = $pdo->prepare("
        INSERT INTO employees (user_id, first_name, last_name, work_email, employee_code, status, joining_date)
        SELECT id, name, '', email, CONCAT('EMP-', id), 'active', CURDATE()
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    
    // Reload employee data
    $stmt = $pdo->prepare("
        SELECT e.*, 
               u.email as user_email,
               d.department_name,
               ds.designation_name
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN designations ds ON e.designation_id = ds.id
        WHERE e.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch();
}

$employee_id = $employee['id'] ?? 0;
$csrf = $_SESSION['csrf_token'];

// Employee name display
$display_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
if (empty($display_name)) $display_name = $user_name;
$initials = strtoupper(substr($display_name, 0, 2));

// Get employee code
$emp_code = $employee['employee_code'] ?? 'EMP-' . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
$department = $employee['department_name'] ?? 'Not Assigned';
$designation = $employee['designation_name'] ?? 'Not Assigned';
$joining_date = $employee['joining_date'] ?? date('Y-m-d');
$work_email = $employee['work_email'] ?? ($employee['user_email'] ?? '');
$phone = $employee['personal_phone'] ?? '';
$status = $employee['status'] ?? 'active';

// ── Get Leave Balances ────────────────────────────────────────────────
$leave_balances = ['CL' => 12, 'SL' => 12, 'EL' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT leave_type_id, balance 
        FROM leave_balances 
        WHERE employee_id = ? AND balance_year = YEAR(CURDATE())
    ");
    $stmt->execute([$employee_id]);
    $balances = $stmt->fetchAll();
    foreach ($balances as $b) {
        if ($b['leave_type_id'] == 1) $leave_balances['CL'] = $b['balance'];
        elseif ($b['leave_type_id'] == 2) $leave_balances['SL'] = $b['balance'];
        elseif ($b['leave_type_id'] == 3) $leave_balances['EL'] = $b['balance'];
    }
} catch(PDOException $e) {
    // Table might not exist, use defaults
}

// ── Get Salary Info ──────────────────────────────────────────────────
$salary_data = ['basic' => 0, 'hra' => 0, 'allowances' => 0, 'deductions' => 0, 'net' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT basic_salary, hra, allowances, deductions, net_salary 
        FROM payroll 
        WHERE employee_id = ? 
        ORDER BY payroll_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$employee_id]);
    $salary = $stmt->fetch();
    if ($salary) {
        $salary_data['basic'] = $salary['basic_salary'] ?? 0;
        $salary_data['hra'] = $salary['hra'] ?? 0;
        $salary_data['allowances'] = $salary['allowances'] ?? 0;
        $salary_data['deductions'] = $salary['deductions'] ?? 0;
        $salary_data['net'] = $salary['net_salary'] ?? ($salary_data['basic'] + $salary_data['hra'] + $salary_data['allowances'] - $salary_data['deductions']);
    }
} catch(PDOException $e) {
    // Table might not exist
}

// ── Handle API Requests ──────────────────────────────────────────────
if (isset($_GET['api_action'])) {
    header('Content-Type: application/json');
    $action = $_GET['api_action'];
    
    try {
        // ── GET DASHBOARD DATA ──────────────────────────────────────
        if ($action === 'get_dashboard') {
            $today = date('Y-m-d');
            
            // Get monthly stats
            $month = date('m');
            $year = date('Y');
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN status IN ('present', 'late') THEN 1 END) as present,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                    COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_day
                FROM attendance 
                WHERE employee_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?
            ");
            $stmt->execute([$employee_id, $month, $year]);
            $stats = $stmt->fetch();
            
            // Get leaves taken
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM leave_requests 
                WHERE employee_id = ? AND status = 'approved' 
                AND MONTH(from_date) = ? AND YEAR(from_date) = ?
            ");
            $stmt->execute([$employee_id, $month, $year]);
            $leaves_taken = $stmt->fetch()['total'] ?? 0;
            
            // Get today's attendance
            $stmt = $pdo->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? AND attendance_date = ?
            ");
            $stmt->execute([$employee_id, $today]);
            $today_attendance = $stmt->fetch();
            
            // Get recent attendance (last 10 days)
            $stmt = $pdo->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? 
                ORDER BY attendance_date DESC 
                LIMIT 10
            ");
            $stmt->execute([$employee_id]);
            $recent = $stmt->fetchAll();
            
            // Chart data
            $chart_labels = ['Present', 'Absent', 'Half Day'];
            $chart_values = [
                (int)($stats['present'] ?? 0),
                (int)($stats['absent'] ?? 0),
                (int)($stats['half_day'] ?? 0)
            ];
            
            echo json_encode([
                'success' => true,
                'days_present' => (int)($stats['present'] ?? 0),
                'days_absent' => (int)($stats['absent'] ?? 0),
                'leaves_taken' => (int)$leaves_taken,
                'salary' => $salary_data['net'],
                'today_attendance' => $today_attendance ? [
                    'status' => $today_attendance['status'],
                    'check_in_time' => $today_attendance['check_in_time'],
                    'check_out_time' => $today_attendance['check_out_time'],
                    'working_hours' => $today_attendance['working_hours'] ?? null
                ] : null,
                'recent_attendance' => $recent,
                'chart_data' => ['labels' => $chart_labels, 'values' => $chart_values]
            ]);
            exit;
        }
        
        // ── GET ATTENDANCE ──────────────────────────────────────────
        if ($action === 'get_attendance') {
            $stmt = $pdo->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? 
                ORDER BY attendance_date DESC 
                LIMIT 100
            ");
            $stmt->execute([$employee_id]);
            $attendance = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'attendance' => $attendance]);
            exit;
        }
        
        // ── MARK ATTENDANCE ──────────────────────────────────────────
        if ($action === 'mark_attendance') {
            $input = json_decode(file_get_contents('php://input'), true);
            $today = date('Y-m-d');
            $check_in = $input['check_in_time'] ?? null;
            $check_out = $input['check_out_time'] ?? null;
            $status = $input['status'] ?? 'present';
            
            // Calculate working hours if both times provided
            $working_hours = null;
            if ($check_in && $check_out) {
                $in = strtotime($check_in);
                $out = strtotime($check_out);
                $diff = ($out - $in) / 3600;
                $working_hours = round($diff, 2);
            }
            
            // Check if attendance exists for today
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
            $stmt->execute([$employee_id, $today]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing
                $stmt = $pdo->prepare("
                    UPDATE attendance 
                    SET check_in_time = COALESCE(?, check_in_time),
                        check_out_time = COALESCE(?, check_out_time),
                        status = ?,
                        working_hours = ?
                    WHERE employee_id = ? AND attendance_date = ?
                ");
                $stmt->execute([$check_in, $check_out, $status, $working_hours, $employee_id, $today]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (employee_id, attendance_date, check_in_time, check_out_time, status, working_hours)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$employee_id, $today, $check_in, $check_out, $status, $working_hours]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET LEAVE BALANCE ──────────────────────────────────────
        if ($action === 'get_leave_balance') {
            echo json_encode([
                'success' => true,
                'balances' => $leave_balances
            ]);
            exit;
        }
        
        // ── GET LEAVE REQUESTS ──────────────────────────────────────
        if ($action === 'get_leave_requests') {
            $stmt = $pdo->prepare("
                SELECT l.*, lt.leave_name 
                FROM leave_requests l
                LEFT JOIN leave_types lt ON l.leave_type_id = lt.id
                WHERE l.employee_id = ?
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$employee_id]);
            $requests = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $requests]);
            exit;
        }
        
        // ── APPLY LEAVE ──────────────────────────────────────────────
        if ($action === 'apply_leave') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $leave_type_id = (int)($input['leave_type_id'] ?? 0);
            $from_date = $input['from_date'] ?? null;
            $to_date = $input['to_date'] ?? null;
            $reason = trim($input['reason'] ?? '');
            
            if (!$from_date || !$to_date) {
                echo json_encode(['success' => false, 'error' => 'Please select dates']);
                exit;
            }
            
            if (strtotime($from_date) > strtotime($to_date)) {
                echo json_encode(['success' => false, 'error' => 'From date must be before to date']);
                exit;
            }
            
            $days = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24) + 1;
            
            $stmt = $pdo->prepare("
                INSERT INTO leave_requests (employee_id, leave_type_id, from_date, to_date, total_days, reason, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$employee_id, $leave_type_id, $from_date, $to_date, $days, $reason]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET PAYROLL ──────────────────────────────────────────────
        if ($action === 'get_payroll') {
            $stmt = $pdo->prepare("
                SELECT * FROM payroll 
                WHERE employee_id = ? 
                ORDER BY payroll_date DESC 
                LIMIT 12
            ");
            $stmt->execute([$employee_id]);
            $history = $stmt->fetchAll();
            
            $current = null;
            if ($history && count($history) > 0) {
                $current = $history[0];
            }
            
            echo json_encode([
                'success' => true,
                'current' => $current,
                'history' => $history
            ]);
            exit;
        }
        
        // ── UPDATE PROFILE ──────────────────────────────────────────
        if ($action === 'update_profile') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE employees 
                SET first_name = ?, last_name = ?, personal_phone = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($input['first_name'] ?? ''),
                trim($input['last_name'] ?? ''),
                trim($input['phone'] ?? ''),
                $employee_id
            ]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── CHANGE PASSWORD ──────────────────────────────────────────
        if ($action === 'change_password') {
            $input = json_decode(file_get_contents('php://input'), true);
            $current = $input['current_password'] ?? '';
            $new = $input['new_password'] ?? '';
            
            if (strlen($new) < 6) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($current, $user['password'])) {
                echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                exit;
            }
            
            // Update password
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET EMPLOYEE LIST (Admin only) ──────────────────────────
        if ($action === 'get_employee_list' && $is_admin) {
            $stmt = $pdo->prepare("
                SELECT e.*, 
                       u.email as user_email,
                       d.department_name,
                       ds.designation_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations ds ON e.designation_id = ds.id
                ORDER BY e.first_name ASC
            ");
            $stmt->execute();
            $employees = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'employees' => $employees]);
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
<meta name="csrf-token" content="<?= $csrf ?>">
<title>Employee Dashboard | CIBIL Repair</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
:root {
    --brand: #0d9e78;
    --brand-dark: #0a7d60;
    --brand-light: #e6f7f2;
    --bg-base: #f4f6f9;
    --bg-surface: #ffffff;
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
button { font-family: inherit; cursor: pointer; border: none; background: none; }
input, select, textarea { font-family: inherit; }
:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 99px; }

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    z-index: 200;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s var(--transition);
    background-image: radial-gradient(circle at 20% 80%, rgba(13,158,120,0.08) 0%, transparent 50%);
}
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); box-shadow: var(--shadow-lg); }
}

.sidebar-brand {
    padding: 18px 20px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.brand-icon {
    width: 38px; height: 38px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    box-shadow: 0 4px 12px rgba(13,158,120,0.4);
}
.brand-text { min-width: 0; }
.brand-name { font-size: 14px; font-weight: 800; color: #fff; line-height: 1.2; }
.brand-sub { font-size: 10px; color: rgba(255,255,255,0.38); letter-spacing: 0.5px; }

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0 16px;
}
.sidebar-nav::-webkit-scrollbar { width: 0; }
.nav-section-label {
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.28);
    padding: 14px 20px 4px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    margin: 1px 10px;
    border-radius: var(--radius-md);
    color: var(--sidebar-text);
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
    min-height: 40px;
}
.nav-item:hover { background: var(--sidebar-hover); color: rgba(255,255,255,0.9); }
.nav-item.active {
    background: rgba(13,158,120,0.22);
    color: var(--sidebar-active);
    font-weight: 600;
}
.nav-item.active::before {
    content: '';
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: var(--brand);
    border-radius: 0 3px 3px 0;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
.nav-label { font-size: 13px; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.sidebar-footer {
    padding: 12px 14px;
    border-top: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 6px;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background var(--transition);
}
.sidebar-user:hover { background: rgba(255,255,255,0.07); }
.user-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.user-details { min-width: 0; flex: 1; }
.user-name { font-size: 12px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 10px; color: rgba(255,255,255,0.4); }

/* ===== MAIN ===== */
.main {
    margin-left: var(--sidebar-w);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
@media (max-width: 768px) {
    .main { margin-left: 0; }
}

/* ===== TOPBAR ===== */
.topbar {
    height: var(--topbar-h);
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 16px;
    gap: 10px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-sm);
    flex-shrink: 0;
}
.topbar-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}
.menu-toggle {
    display: none;
    width: 38px; height: 38px;
    border-radius: var(--radius-md);
    background: var(--bg-sunken);
    color: var(--text-secondary);
    font-size: 16px;
    transition: all var(--transition);
}
@media (max-width: 768px) { .menu-toggle { display: flex; align-items: center; justify-content: center; } }
.menu-toggle:hover { background: var(--brand-light); color: var(--brand); }

.page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.clock-badge {
    padding: 4px 12px;
    border-radius: var(--radius-sm);
    background: var(--bg-sunken);
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    font-variant-numeric: tabular-nums;
}
.theme-toggle {
    display: flex;
    background: var(--bg-sunken);
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    padding: 3px;
    gap: 2px;
}
.theme-btn {
    width: 30px; height: 30px;
    border-radius: var(--radius-sm);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--text-muted);
    transition: all var(--transition);
}
.theme-btn.active {
    background: var(--brand);
    color: #fff;
    box-shadow: 0 2px 6px rgba(13,158,120,0.35);
}
.logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--radius-md);
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid rgba(220,38,38,0.15);
    font-size: 13px;
    font-weight: 600;
    transition: all var(--transition);
}
.logout-btn:hover { background: var(--danger); color: #fff; }
@media (max-width: 500px) { .logout-btn .btn-text { display: none; } }

/* ===== CONTENT ===== */
.content {
    flex: 1;
    padding: 20px 24px;
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
}
@media (max-width: 768px) { .content { padding: 14px; } }
@media (max-width: 480px) { .content { padding: 10px; } }

/* ===== SECTIONS ===== */
.section { display: none; animation: sectionIn 250ms var(--transition) both; }
.section.active { display: block; }
@keyframes sectionIn { from { opacity: 0; transform: translateY(8px); } }

/* ===== CARDS ===== */
.card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 18px;
    transition: box-shadow var(--transition);
}
.card:hover { box-shadow: var(--shadow-md); }
.card-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.card-title {
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-title i { color: var(--brand); font-size: 14px; }
.card-body { padding: 18px; }

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
@media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; } }
@media (max-width: 380px) { .stats-grid { grid-template-columns: 1fr; } }

.stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px;
    position: relative;
    transition: transform var(--transition), box-shadow var(--transition);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-card .stat-icon {
    width: 42px; height: 42px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    margin-bottom: 12px;
}
.stat-card.green .stat-icon { background: var(--success-bg); color: var(--success); }
.stat-card.red .stat-icon { background: var(--danger-bg); color: var(--danger); }
.stat-card.blue .stat-icon { background: var(--info-bg); color: var(--info); }
.stat-card.purple .stat-icon { background: var(--purple-bg); color: var(--purple); }
.stat-card.amber .stat-icon { background: var(--warning-bg); color: var(--warning); }

.stat-value {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1.2;
    margin-bottom: 4px;
}
.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 500;
}

/* ===== TABLES ===== */
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; white-space: nowrap; }
thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--text-muted);
    background: var(--bg-sunken);
    border-bottom: 1px solid var(--border);
}
tbody td {
    padding: 11px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    color: var(--text-primary);
}
tbody tr:last-child td { border-bottom: none; }
tbody tr { transition: background var(--transition); }
tbody tr:hover td { background: var(--bg-sunken); }

.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
    gap: 10px;
}

/* ===== BADGES ===== */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-success { background: var(--success-bg); color: var(--success); }
.badge-warning { background: var(--warning-bg); color: var(--warning); }
.badge-danger { background: var(--danger-bg); color: var(--danger); }
.badge-blue { background: var(--info-bg); color: var(--info); }
.badge-gray { background: var(--bg-sunken); color: var(--text-secondary); }
.badge-brand { background: var(--brand-light); color: var(--brand-dark); }
.badge-purple { background: var(--purple-bg); color: var(--purple); }

/* ===== BUTTONS ===== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    transition: all var(--transition) var(--transition);
    white-space: nowrap;
    min-height: 36px;
}
.btn:disabled { opacity: 0.55; cursor: not-allowed; pointer-events: none; }
.btn-primary { background: var(--brand); color: #fff; box-shadow: 0 2px 8px rgba(13,158,120,0.25); }
.btn-primary:hover { background: var(--brand-dark); box-shadow: 0 4px 14px rgba(13,158,120,0.35); transform: translateY(-1px); }
.btn-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(220,38,38,0.15); }
.btn-danger:hover { background: var(--danger); color: #fff; }
.btn-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(5,150,105,0.15); }
.btn-success:hover { background: var(--success); color: #fff; }
.btn-outline { background: transparent; color: var(--text-secondary); border: 1px solid var(--border-strong); }
.btn-outline:hover { background: var(--bg-sunken); }
.btn-ghost { background: var(--bg-sunken); color: var(--text-secondary); border: 1px solid var(--border); }
.btn-ghost:hover { background: var(--border); }
.btn-sm { padding: 5px 12px; font-size: 12px; min-height: 30px; }
.btn-xs { padding: 3px 8px; font-size: 11px; min-height: 26px; }

/* ===== FORMS ===== */
.form-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
    min-width: 120px;
}
.flex-1 { flex: 1; }
.form-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
}
.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 9px 13px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-md);
    font-size: 13px;
    color: var(--text-primary);
    transition: border-color var(--transition), box-shadow var(--transition);
    outline: none;
    min-height: 38px;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(13,158,120,0.18);
}
.form-textarea { resize: vertical; min-height: 80px; }
.form-input[readonly] { opacity: 0.7; cursor: default; }

.filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
}
.search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
}
.search-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 12px;
}
.search-input {
    width: 100%;
    padding: 8px 12px 8px 32px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-md);
    font-size: 13px;
    color: var(--text-primary);
    outline: none;
    min-height: 36px;
    transition: border-color var(--transition);
}
.search-input:focus { border-color: var(--brand); }

/* ===== MODALS ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 300;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 520px;
    max-height: 92dvh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    animation: modalIn 220ms cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(16px); } }
@media (max-width: 480px) {
    .modal-overlay { align-items: flex-end; }
    .modal { border-radius: var(--radius-xl) var(--radius-xl) 0 0; }
}

.modal-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.modal-title {
    font-size: 16px;
    font-weight: 700;
}
.modal-close {
    width: 32px; height: 32px;
    border-radius: var(--radius-md);
    background: var(--bg-sunken);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 15px;
    transition: all var(--transition);
}
.modal-close:hover { background: var(--danger-bg); color: var(--danger); }
.modal-body { padding: 18px 20px; }
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    padding: 32px 16px;
    text-align: center;
    color: var(--text-muted);
}
.empty-state i { font-size: 32px; margin-bottom: 12px; display: block; color: var(--text-muted); }
.empty-state p { font-size: 14px; }
.spinner {
    width: 18px; height: 18px;
    border: 2px solid var(--border-strong);
    border-top-color: var(--brand);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ===== CHART ===== */
.chart-wrap {
    position: relative;
    height: 220px;
}
@media (max-width: 600px) { .chart-wrap { height: 180px; } }

/* ===== TOASTS ===== */
.toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 2000;
    pointer-events: none;
    max-width: 400px;
}
@media (max-width: 480px) { .toast-container { left: 10px; right: 10px; bottom: 10px; } }
.toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-radius: var(--radius-lg);
    background: var(--bg-surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
    width: 100%;
    animation: toastIn 300ms cubic-bezier(0.34,1.56,0.64,1);
    pointer-events: all;
}
.toast.leaving { animation: toastOut 300ms var(--transition) forwards; }
@keyframes toastIn { from { transform: translateX(110%); opacity: 0; } }
@keyframes toastOut { to { transform: translateX(110%); opacity: 0; } }
.toast-icon { width: 30px; height: 30px; border-radius: var(--radius-md); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; }
.toast-success .toast-icon { background: var(--success-bg); color: var(--success); }
.toast-error .toast-icon { background: var(--danger-bg); color: var(--danger); }
.toast-info .toast-icon { background: var(--info-bg); color: var(--info); }
.toast-warning .toast-icon { background: var(--warning-bg); color: var(--warning); }
.toast-msg { font-size: 13px; font-weight: 500; flex: 1; }
.toast-close { background: none; border: none; color: var(--text-muted); font-size: 15px; padding: 0 4px; }
.toast-close:hover { color: var(--text-primary); }

/* ===== RESPONSIVE ===== */
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .topbar-right .clock-badge { display: none; }
}
@media (max-width: 400px) {
    .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">CR</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Employee Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-item" data-section="attendance">
            <i class="fas fa-calendar-check"></i>
            <span class="nav-label">Attendance</span>
        </div>
        <div class="nav-item" data-section="leave">
            <i class="fas fa-umbrella-beach"></i>
            <span class="nav-label">Leave Requests</span>
        </div>
        <div class="nav-item" data-section="payroll">
            <i class="fas fa-wallet"></i>
            <span class="nav-label">Salary & Payroll</span>
        </div>
        <div class="nav-item" data-section="profile">
            <i class="fas fa-user-circle"></i>
            <span class="nav-label">My Profile</span>
        </div>
        <div class="nav-section-label">System</div>
        <?php if ($is_admin): ?>
        <div class="nav-item" data-section="employees">
            <i class="fas fa-users"></i>
            <span class="nav-label">All Employees</span>
        </div>
        <?php endif; ?>
        <div class="nav-item" onclick="window.location.href='admin-dashboard.php'">
            <i class="fas fa-arrow-left"></i>
            <span class="nav-label">← Back to Admin</span>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="showSection('profile')">
            <div class="user-avatar"><?= $initials ?></div>
            <div class="user-details">
                <div class="user-name"><?= h($display_name) ?></div>
                <div class="user-role"><?= ucfirst($user_role) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<div class="main" id="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="page-title" id="pageTitle">Dashboard</span>
        </div>
        <div class="topbar-right">
            <?php if ($is_admin): ?>
            <a href="admin-dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;background:rgba(13,158,120,0.12);color:#0d9e78;font-size:13px;font-weight:500;text-decoration:none;transition:all 0.2s;">
                <i class="fas fa-arrow-left"></i> Admin
            </a>
            <?php endif; ?>
            <div class="clock-badge" id="liveClock">--:--:--</div>
            <div class="theme-toggle">
                <button class="theme-btn active" id="lightBtn"><i class="fas fa-sun"></i></button>
                <button class="theme-btn" id="darkBtn"><i class="fas fa-moon"></i></button>
            </div>
            <span style="font-size:13px;color:var(--text-secondary);"><?= h($display_name) ?></span>
            <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> <span class="btn-text">Logout</span></button>
        </div>
    </div>

    <div class="content">
        <!-- ====== DASHBOARD ====== -->
        <div class="section active" id="dashboardSection">
            <div class="stats-grid">
                <div class="stat-card green"><span class="stat-icon"><i class="fas fa-calendar-check"></i></span><div class="stat-value" id="daysPresent">-</div><div class="stat-label">Days Present (This Month)</div></div>
                <div class="stat-card red"><span class="stat-icon"><i class="fas fa-calendar-times"></i></span><div class="stat-value" id="daysAbsent">-</div><div class="stat-label">Days Absent</div></div>
                <div class="stat-card blue"><span class="stat-icon"><i class="fas fa-umbrella-beach"></i></span><div class="stat-value" id="leavesTaken">-</div><div class="stat-label">Leaves Taken</div></div>
                <div class="stat-card purple"><span class="stat-icon"><i class="fas fa-wallet"></i></span><div class="stat-value" id="salary">-</div><div class="stat-label">Current Salary</div></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Attendance Overview</div></div>
                <div class="card-body"><div class="chart-wrap"><canvas id="attendanceChart"></canvas></div></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Today's Status</div><button class="btn btn-primary" id="markAttendanceBtn"><i class="fas fa-fingerprint"></i> Mark Attendance</button></div>
                <div class="card-body" id="todayStatus"><div class="empty-state"><div class="spinner"></div> Loading...</div></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Recent Attendance</div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Hours</th></tr></thead>
                        <tbody id="recentAttendanceBody"><tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ATTENDANCE ====== -->
        <div class="section" id="attendanceSection">
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-calendar-alt"></i> Attendance Calendar</div><button class="btn btn-primary" id="markAttendanceBtn2"><i class="fas fa-fingerprint"></i> Mark Today's Attendance</button></div>
                <div class="filter-bar">
                    <div class="search-wrap"><i class="fas fa-search"></i><input class="search-input" id="attendanceSearch" placeholder="Search by date..." oninput="filterAttendance()"></div>
                    <select class="form-select" id="attendanceStatusFilter" onchange="filterAttendance()" style="width:140px;">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="half_day">Half Day</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Hours</th><th>Late By</th></tr></thead>
                        <tbody id="attendanceBody"><tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr></tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <span style="font-size:12px;color:var(--text-muted);">Total: <strong id="attendanceTotal">0</strong> records</span>
                    <button class="btn btn-success btn-sm" onclick="exportAttendance()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
            </div>
        </div>

        <!-- ====== LEAVE ====== -->
        <div class="section" id="leaveSection">
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card green"><div class="stat-value" id="clBalance">-</div><div class="stat-label">Casual Leave</div></div>
                <div class="stat-card blue"><div class="stat-value" id="slBalance">-</div><div class="stat-label">Sick Leave</div></div>
                <div class="stat-card purple"><div class="stat-value" id="elBalance">-</div><div class="stat-label">Earned Leave</div></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-paper-plane"></i> My Leave Requests</div><button class="btn btn-primary" id="applyLeaveBtn"><i class="fas fa-plus"></i> Apply for Leave</button></div>
                <div class="filter-bar">
                    <select class="form-select" id="leaveStatusFilter" onchange="filterLeaveRequests()" style="width:150px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>From</th><th>To</th><th>Days</th><th>Type</th><th>Reason</th><th>Status</th></tr></thead>
                        <tbody id="leaveRequestsBody"><tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PAYROLL ====== -->
        <div class="section" id="payrollSection">
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                <div class="stat-card green"><div class="stat-value" id="basicSalary">-</div><div class="stat-label">Basic Salary</div></div>
                <div class="stat-card blue"><div class="stat-value" id="hraAmount">-</div><div class="stat-label">HRA</div></div>
                <div class="stat-card amber"><div class="stat-value" id="totalEarnings">-</div><div class="stat-label">Total Earnings</div></div>
                <div class="stat-card purple"><div class="stat-value" id="netSalary">-</div><div class="stat-label">Net Salary</div></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-receipt"></i> Salary History</div><button class="btn btn-success btn-sm" onclick="exportPayroll()"><i class="fas fa-file-excel"></i> Export</button></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Month</th><th>Year</th><th>Basic</th><th>HRA</th><th>Allowances</th><th>Deductions</th><th>Net Salary</th><th>Status</th></tr></thead>
                        <tbody id="payrollHistoryBody"><tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PROFILE ====== -->
        <div class="section" id="profileSection">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="card">
                    <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Personal Information</div></div>
                    <div class="card-body">
                        <div class="form-group"><label class="form-label">Employee Code</label><input class="form-input" id="empCode" readonly value="<?= h($emp_code) ?>"></div>
                        <div class="form-row">
                            <div class="form-group flex-1"><label class="form-label">First Name</label><input class="form-input" id="firstName" value="<?= h($employee['first_name'] ?? '') ?>"></div>
                            <div class="form-group flex-1"><label class="form-label">Last Name</label><input class="form-input" id="lastName" value="<?= h($employee['last_name'] ?? '') ?>"></div>
                        </div>
                        <div class="form-group"><label class="form-label">Work Email</label><input class="form-input" id="workEmail" readonly value="<?= h($work_email) ?>"></div>
                        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" id="phone" value="<?= h($phone) ?>"></div>
                        <div class="form-group"><label class="form-label">Department</label><input class="form-input" id="departmentField" readonly value="<?= h($department) ?>"></div>
                        <div class="form-group"><label class="form-label">Designation</label><input class="form-input" id="designationField" readonly value="<?= h($designation) ?>"></div>
                        <div class="form-group"><label class="form-label">Joining Date</label><input class="form-input" id="joiningDate" readonly value="<?= h($joining_date) ?>"></div>
                        <div class="form-group"><label class="form-label">Status</label><input class="form-input" id="statusField" readonly value="<?= h(ucfirst($status)) ?>"></div>
                        <button class="btn btn-primary" id="updateProfileBtn"><i class="fas fa-save"></i> Update Profile</button>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <div class="card-header"><div class="card-title"><i class="fas fa-lock"></i> Change Password</div></div>
                        <div class="card-body">
                            <div class="form-group"><label class="form-label">Current Password</label><input type="password" class="form-input" id="curPassword"></div>
                            <div class="form-group"><label class="form-label">New Password</label><input type="password" class="form-input" id="newPassword"></div>
                            <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" class="form-input" id="confirmPassword"></div>
                            <button class="btn btn-primary" id="changePasswordBtn"><i class="fas fa-key"></i> Change Password</button>
                        </div>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="card" style="margin-top:20px;">
                        <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Employee Management</div></div>
                        <div class="card-body">
                            <button class="btn btn-primary" onclick="window.location.href='admin-dashboard.php?section=hr'"><i class="fas fa-arrow-right"></i> Go to HR Dashboard</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <!-- ====== EMPLOYEES LIST (Admin only) ====== -->
        <div class="section" id="employeesSection">
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> All Employees</div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Status</th></tr></thead>
                        <tbody id="employeesBody"><tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Mark Attendance Modal -->
<div class="modal-overlay" id="attendanceModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title"><i class="fas fa-fingerprint"></i> Mark Attendance</span><button class="modal-close" onclick="closeModal('attendanceModal')"><i class="fas fa-times"></i></button></div>
        <div class="modal-body">
            <p style="margin-bottom:12px;">Current Date: <strong id="currentDate"></strong></p>
            <div class="form-row">
                <div class="form-group flex-1"><label class="form-label">Check In Time</label><input type="time" class="form-input" id="checkInTime"></div>
                <div class="form-group flex-1"><label class="form-label">Check Out Time</label><input type="time" class="form-input" id="checkOutTime"></div>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select class="form-select" id="attendanceStatus">
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="half_day">Half Day</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('attendanceModal')">Cancel</button>
            <button class="btn btn-primary" id="submitAttendanceBtn"><i class="fas fa-save"></i> Submit</button>
        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal-overlay" id="leaveModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title"><i class="fas fa-umbrella-beach"></i> Apply for Leave</span><button class="modal-close" onclick="closeModal('leaveModal')"><i class="fas fa-times"></i></button></div>
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Leave Type</label>
                <select class="form-select" id="leaveType">
                    <option value="1">Casual Leave</option>
                    <option value="2">Sick Leave</option>
                    <option value="3">Earned Leave</option>
                    <option value="4">Loss of Pay</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group flex-1"><label class="form-label">From Date</label><input type="date" class="form-input" id="fromDate"></div>
                <div class="form-group flex-1"><label class="form-label">To Date</label><input type="date" class="form-input" id="toDate"></div>
            </div>
            <div class="form-group"><label class="form-label">Reason</label><textarea class="form-textarea" id="leaveReason" rows="3" placeholder="Brief description of the reason..."></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('leaveModal')">Cancel</button>
            <button class="btn btn-primary" id="submitLeaveBtn"><i class="fas fa-paper-plane"></i> Submit</button>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<!-- ================================================================ -->
<!-- JAVASCRIPT -->
<!-- ================================================================ -->
<script>
// ── CONFIG ───────────────────────────────────────────────────────────
const API = window.location.pathname + '?api_action=';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const EMPLOYEE_ID = <?= json_encode($employee_id) ?>;
const USER_ID = <?= json_encode($user_id) ?>;
const IS_ADMIN = <?= json_encode($is_admin) ?>;

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('employeeTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('employeeTheme') || 'light'); })();

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

// ── NAVIGATION ───────────────────────────────────────────────────────
const sectionTitles = {
    dashboard: 'Dashboard',
    attendance: 'Attendance',
    leave: 'Leave Requests',
    payroll: 'Salary & Payroll',
    profile: 'My Profile',
    employees: 'All Employees'
};

function showSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const el = document.getElementById(name + 'Section');
    if (el) el.classList.add('active');
    document.getElementById('pageTitle').textContent = sectionTitles[name] || name;
    const nav = document.querySelector(`.nav-item[data-section="${name}"]`);
    if (nav) nav.classList.add('active');

    if (name === 'dashboard') loadDashboard();
    if (name === 'attendance') loadAttendance();
    if (name === 'leave') loadLeaveRequests();
    if (name === 'payroll') loadPayroll();
    if (name === 'employees' && IS_ADMIN) loadEmployees();

    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('mobile-open');
    }
}

document.querySelectorAll('.nav-item[data-section]').forEach(item => {
    item.onclick = () => showSection(item.dataset.section);
});

// ── TOAST ─────────────────────────────────────────────────────────────
function toast(msg, type = 'info', duration = 3500) {
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    const container = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.innerHTML = `<span class="toast-icon"><i class="fas ${icons[type] || icons.info}"></i></span><span class="toast-msg">${esc(msg)}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
    container.appendChild(t);
    setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 300); }, duration);
}

function esc(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' }[c]));
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

// ── API HELPER ────────────────────────────────────────────────────────
async function apiCall(action, method = 'GET', data = null) {
    const url = API + action;
    const options = { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, credentials: 'include' };
    if (data) options.body = JSON.stringify(data);
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return await response.json();
    } catch (e) {
        console.error('API error:', action, e);
        return { success: false, error: e.message };
    }
}

// ── BADGE HELPER ─────────────────────────────────────────────────────
function statusBadge(s) {
    const map = { 
        'present': 'badge-success', 
        'late': 'badge-warning', 
        'half_day': 'badge-warning', 
        'absent': 'badge-danger', 
        'pending': 'badge-warning', 
        'approved': 'badge-success', 
        'rejected': 'badge-danger', 
        'processed': 'badge-success',
        'active': 'badge-success',
        'inactive': 'badge-danger'
    };
    return `<span class="badge ${map[(s||'').toLowerCase()] || 'badge-gray'}">${esc(s || '—')}</span>`;
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
        const data = await apiCall('get_dashboard');
        if (data.success) {
            document.getElementById('daysPresent').textContent = data.days_present || 0;
            document.getElementById('daysAbsent').textContent = data.days_absent || 0;
            document.getElementById('leavesTaken').textContent = data.leaves_taken || 0;
            document.getElementById('salary').textContent = '₹' + (data.salary || 0).toLocaleString();

            const todayEl = document.getElementById('todayStatus');
            if (data.today_attendance) {
                const ta = data.today_attendance;
                todayEl.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;"><div><span style="font-size:16px;font-weight:600;">${statusBadge(ta.status)}</span><span style="margin-left:12px;font-size:13px;color:var(--text-secondary);">Check In: ${ta.check_in_time || 'Not marked'} &nbsp;|&nbsp; Check Out: ${ta.check_out_time || 'Not marked'}</span></div>${ta.status !== 'present' ? `<button class="btn btn-primary btn-sm" onclick="openModal('attendanceModal')"><i class="fas fa-edit"></i> Update</button>` : ''}</div>`;
            } else {
                todayEl.innerHTML = `<div style="text-align:center;padding:12px 0;"><p style="color:var(--text-muted);margin-bottom:12px;">No attendance marked for today</p><button class="btn btn-primary" onclick="openModal('attendanceModal')"><i class="fas fa-fingerprint"></i> Mark Now</button></div>`;
            }

            const recentBody = document.getElementById('recentAttendanceBody');
            if (data.recent_attendance && data.recent_attendance.length) {
                recentBody.innerHTML = data.recent_attendance.map(a => `<tr><td>${esc(a.attendance_date)}</td><td>${esc(a.check_in_time || '-')}</td><td>${esc(a.check_out_time || '-')}</td><td>${statusBadge(a.status)}</td><td>${a.working_hours || '-'} hrs</td></tr>`).join('');
            } else {
                recentBody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fas fa-calendar"></i><p>No recent attendance records</p></div></td></tr>`;
            }

            if (data.chart_data) {
                destroyChart('attendanceChart');
                const ctx = document.getElementById('attendanceChart').getContext('2d');
                charts['attendanceChart'] = new Chart(ctx, {
                    type: 'bar',
                    data: { labels: data.chart_data.labels || ['Present', 'Absent', 'Half Day'], datasets: [{ label: 'Days', data: data.chart_data.values || [0, 0, 0], backgroundColor: ['#059669', '#dc2626', '#fbbf24'], borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: gridColor() }, ticks: { color: textColor() } }, y: { grid: { color: gridColor() }, ticks: { color: textColor(), beginAtZero: true } } } }
                });
            }
        }
    } catch (e) { console.error('Dashboard error:', e); toast('Error loading dashboard', 'error'); }
}

// ── ATTENDANCE ────────────────────────────────────────────────────────
let allAttendance = [];

async function loadAttendance() {
    const body = document.getElementById('attendanceBody');
    body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>`;
    try {
        const data = await apiCall('get_attendance');
        if (data.success) {
            allAttendance = data.attendance || [];
            renderAttendance(allAttendance);
            document.getElementById('attendanceTotal').textContent = allAttendance.length;
        } else {
            body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${data.error || 'Failed to load attendance'}</p></div></td></tr>`;
        }
    } catch (e) {
        body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading attendance</p></div></td></tr>`;
    }
}

function renderAttendance(list) {
    const body = document.getElementById('attendanceBody');
    if (!list || !list.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-calendar"></i><p>No attendance records found</p></div></td></tr>`;
        return;
    }
    body.innerHTML = list.map(a => `<tr><td>${esc(a.attendance_date)}</td><td>${esc(a.check_in_time || '-')}</td><td>${esc(a.check_out_time || '-')}</td><td>${statusBadge(a.status)}</td><td>${a.working_hours || '-'} hrs</td><td>${a.late_minutes ? a.late_minutes + ' min' : '-'}</td></tr>`).join('');
}

function filterAttendance() {
    const search = document.getElementById('attendanceSearch').value.toLowerCase();
    const status = document.getElementById('attendanceStatusFilter').value;
    let filtered = allAttendance;
    if (search) filtered = filtered.filter(a => a.attendance_date.includes(search));
    if (status) filtered = filtered.filter(a => a.status === status);
    renderAttendance(filtered);
    document.getElementById('attendanceTotal').textContent = filtered.length;
}

function exportAttendance() {
    if (!allAttendance.length) { toast('No attendance data to export', 'warning'); return; }
    try {
        if (typeof XLSX !== 'undefined') {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(allAttendance);
            XLSX.utils.book_append_sheet(wb, ws, 'Attendance');
            XLSX.writeFile(wb, `attendance_${new Date().toISOString().slice(0,10)}.xlsx`);
            toast('Attendance exported!', 'success');
        } else {
            // Fallback to CSV
            let csv = 'Date,Check In,Check Out,Status,Hours\n';
            allAttendance.forEach(a => {
                csv += `${a.attendance_date},${a.check_in_time||'-'},${a.check_out_time||'-'},${a.status},${a.working_hours||'-'}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `attendance_${new Date().toISOString().slice(0,10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            toast('Attendance exported as CSV!', 'success');
        }
    } catch(e) {
        toast('Error exporting: ' + e.message, 'error');
    }
}

// ── MARK ATTENDANCE ──────────────────────────────────────────────────
document.getElementById('markAttendanceBtn').onclick = () => openModal('attendanceModal');
document.getElementById('markAttendanceBtn2').onclick = () => openModal('attendanceModal');
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-IN');

document.getElementById('submitAttendanceBtn').onclick = async () => {
    const data = { 
        check_in_time: document.getElementById('checkInTime').value, 
        check_out_time: document.getElementById('checkOutTime').value, 
        status: document.getElementById('attendanceStatus').value 
    };
    if (!data.check_in_time && !data.check_out_time) { toast('Please enter at least check-in or check-out time', 'error'); return; }
    const btn = document.getElementById('submitAttendanceBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving...';
    const result = await apiCall('mark_attendance', 'POST', data);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Submit';
    if (result.success) {
        toast('Attendance marked successfully!', 'success');
        closeModal('attendanceModal');
        loadDashboard();
        loadAttendance();
        document.getElementById('checkInTime').value = '';
        document.getElementById('checkOutTime').value = '';
    } else {
        toast(result.error || 'Failed to mark attendance', 'error');
    }
};

// ── LEAVE ─────────────────────────────────────────────────────────────
async function loadLeaveRequests() {
    try {
        const balanceData = await apiCall('get_leave_balance');
        if (balanceData.success) {
            document.getElementById('clBalance').textContent = balanceData.balances?.CL || 0;
            document.getElementById('slBalance').textContent = balanceData.balances?.SL || 0;
            document.getElementById('elBalance').textContent = balanceData.balances?.EL || 0;
        }
        const requestsData = await apiCall('get_leave_requests');
        if (requestsData.success) {
            allLeaveRequests = requestsData.data || [];
            renderLeaveRequests(allLeaveRequests);
        } else {
            document.getElementById('leaveRequestsBody').innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${requestsData.error || 'Failed to load leave requests'}</p></div></td></tr>`;
        }
    } catch (e) {
        document.getElementById('leaveRequestsBody').innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading leave requests</p></div></td></tr>`;
    }
}

let allLeaveRequests = [];

function renderLeaveRequests(list) {
    const body = document.getElementById('leaveRequestsBody');
    if (!list || !list.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-paper-plane"></i><p>No leave requests yet</p></div></td></tr>`;
        return;
    }
    body.innerHTML = list.map(l => `<tr><td>${esc(l.from_date)}</td><td>${esc(l.to_date)}</td><td>${l.total_days}</td><td>${esc(l.leave_name || '—')}</td><td>${esc(l.reason || '—')}</td><td>${statusBadge(l.status)}</td></tr>`).join('');
}

function filterLeaveRequests() {
    const status = document.getElementById('leaveStatusFilter').value;
    if (!status) { renderLeaveRequests(allLeaveRequests); return; }
    const filtered = allLeaveRequests.filter(l => l.status === status);
    renderLeaveRequests(filtered);
}

document.getElementById('applyLeaveBtn').onclick = () => openModal('leaveModal');

document.getElementById('submitLeaveBtn').onclick = async () => {
    const data = { 
        leave_type_id: document.getElementById('leaveType').value, 
        from_date: document.getElementById('fromDate').value, 
        to_date: document.getElementById('toDate').value, 
        reason: document.getElementById('leaveReason').value 
    };
    if (!data.from_date || !data.to_date) { toast('Please select both from and to dates', 'error'); return; }
    if (data.from_date > data.to_date) { toast('From date must be before to date', 'error'); return; }
    if (!data.reason) { toast('Please provide a reason for leave', 'error'); return; }
    const btn = document.getElementById('submitLeaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Submitting...';
    const result = await apiCall('apply_leave', 'POST', data);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
    if (result.success) {
        toast('Leave request submitted successfully!', 'success');
        closeModal('leaveModal');
        loadLeaveRequests();
        loadDashboard();
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        document.getElementById('leaveReason').value = '';
    } else {
        toast(result.error || 'Failed to submit leave request', 'error');
    }
};

// ── PAYROLL ───────────────────────────────────────────────────────────
async function loadPayroll() {
    try {
        const data = await apiCall('get_payroll');
        if (data.success) {
            const current = data.current || {};
            document.getElementById('basicSalary').textContent = '₹' + (current.basic_salary || 0).toLocaleString();
            document.getElementById('hraAmount').textContent = '₹' + (current.hra || 0).toLocaleString();
            const totalEarnings = (current.basic_salary || 0) + (current.hra || 0) + (current.allowances || 0);
            document.getElementById('totalEarnings').textContent = '₹' + totalEarnings.toLocaleString();
            document.getElementById('netSalary').textContent = '₹' + (current.net_salary || 0).toLocaleString();

            const historyBody = document.getElementById('payrollHistoryBody');
            if (data.history && data.history.length) {
                historyBody.innerHTML = data.history.map(p => {
                    const monthName = new Date(p.payroll_date + '-01').toLocaleString('en', { month: 'short' });
                    return `<tr><td>${monthName}</td><td>${new Date(p.payroll_date).getFullYear()}</td><td>₹${(p.basic_salary || 0).toLocaleString()}</td><td>₹${(p.hra || 0).toLocaleString()}</td><td>₹${(p.allowances || 0).toLocaleString()}</td><td>₹${(p.deductions || 0).toLocaleString()}</td><td><strong>₹${(p.net_salary || 0).toLocaleString()}</strong></td><td>${statusBadge(p.status)}</td></tr>`;
                }).join('');
            } else {
                historyBody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-receipt"></i><p>No salary history available</p></div></td></tr>`;
            }
        }
    } catch (e) { console.error('Payroll error:', e); }
}

function exportPayroll() { toast('Payroll export — coming soon', 'info'); }

// ── EMPLOYEES LIST (Admin) ──────────────────────────────────────────
async function loadEmployees() {
    const body = document.getElementById('employeesBody');
    body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>`;
    try {
        const data = await apiCall('get_employee_list');
        if (data.success && data.employees) {
            body.innerHTML = data.employees.map(e => `
                <tr>
                    <td>${esc(e.employee_code || e.user_id)}</td>
                    <td><strong>${esc(e.first_name)} ${esc(e.last_name)}</strong></td>
                    <td>${esc(e.user_email)}</td>
                    <td>${esc(e.department_name || '—')}</td>
                    <td>${esc(e.designation_name || '—')}</td>
                    <td>${statusBadge(e.status)}</td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>No employees found</p></div></td></tr>`;
        }
    } catch(e) {
        body.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading employees</p></div></td></tr>`;
    }
}

// ── PROFILE ──────────────────────────────────────────────────────────
document.getElementById('updateProfileBtn').onclick = async () => {
    const data = { 
        first_name: document.getElementById('firstName').value, 
        last_name: document.getElementById('lastName').value, 
        phone: document.getElementById('phone').value 
    };
    const btn = document.getElementById('updateProfileBtn');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving...';
    const result = await apiCall('update_profile', 'POST', data);
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Update Profile';
    if (result.success) { toast('Profile updated successfully!', 'success'); } else { toast(result.error || 'Update failed', 'error'); }
};

document.getElementById('changePasswordBtn').onclick = async () => {
    const cur = document.getElementById('curPassword').value, nw = document.getElementById('newPassword').value, con = document.getElementById('confirmPassword').value;
    if (!cur || !nw || !con) { toast('Please fill all fields', 'error'); return; }
    if (nw !== con) { toast('Passwords do not match', 'error'); return; }
    if (nw.length < 6) { toast('Password must be at least 6 characters', 'error'); return; }
    const btn = document.getElementById('changePasswordBtn');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Changing...';
    const result = await apiCall('change_password', 'POST', { current_password: cur, new_password: nw });
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Change Password';
    if (result.success) { toast('Password changed! Please login again.', 'success'); setTimeout(() => { window.location.href = 'logout.php'; }, 2000); } 
    else { toast(result.error || 'Password change failed', 'error'); }
};

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => { if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php'; };

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => { 
    if (e.altKey && e.key === 'd') showSection('dashboard'); 
    if (e.altKey && e.key === 'a') showSection('attendance'); 
    if (e.altKey && e.key === 'l') showSection('leave'); 
    if (e.altKey && e.key === 'p') showSection('payroll'); 
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadAttendance();
loadLeaveRequests();
loadPayroll();
if (IS_ADMIN) loadEmployees();

console.log('✅ Employee Dashboard initialized');
console.log('👤 Employee ID:', EMPLOYEE_ID);
console.log('👑 Admin Mode:', IS_ADMIN);
</script>
</body>
</html>