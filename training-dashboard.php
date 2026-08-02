<?php
// ============================================================
// TRAINING DASHBOARD - FULLY INTEGRATED
// Access: training_team, admin, manager, super_admin, hr
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

// ── AUTH: allow training_team, admin, manager, super_admin, hr ──────────
$allowed_roles = ['training_team', 'admin', 'manager', 'super_admin', 'hr'];
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
$user_name = $_SESSION['user_name'] ?? 'Training Manager';
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
            // Total programs
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_programs");
            $total_programs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Active programs (published or in_progress)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_programs WHERE status IN ('published', 'in_progress')");
            $active_programs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Total enrollments
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_enrollments");
            $total_enrollments = (int)($stmt->fetch()['total'] ?? 0);
            
            // Completed trainings
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_enrollments WHERE status = 'completed'");
            $completed_trainings = (int)($stmt->fetch()['total'] ?? 0);
            
            // Certifications
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_certifications WHERE is_active = 1");
            $certifications = (int)($stmt->fetch()['total'] ?? 0);
            
            // Program distribution by category
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as count 
                FROM training_programs 
                GROUP BY category
            ");
            $cat_data = $stmt->fetchAll();
            $cat_labels = [];
            $cat_values = [];
            foreach ($cat_data as $c) {
                $cat_labels[] = ucwords(str_replace('_', ' ', $c['category']));
                $cat_values[] = (int)$c['count'];
            }
            
            // Recent programs
            $stmt = $pdo->query("
                SELECT p.*, u.name as created_by_name 
                FROM training_programs p
                LEFT JOIN users u ON p.created_by = u.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $recent_programs = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_programs' => $total_programs,
                'active_programs' => $active_programs,
                'total_enrollments' => $total_enrollments,
                'completed_trainings' => $completed_trainings,
                'certifications' => $certifications,
                'category_data' => ['labels' => $cat_labels, 'values' => $cat_values],
                'recent_programs' => $recent_programs
            ]);
            exit;
        }
        
        // ── GET PROGRAMS ──────────────────────────────────────────────
        if ($action === 'get_programs') {
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT p.*, u.name as created_by_name 
                    FROM training_programs p
                    LEFT JOIN users u ON p.created_by = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (p.program_name LIKE ? OR p.program_id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($category) {
                $sql .= " AND p.category = ?";
                $params[] = $category;
            }
            if ($status) {
                $sql .= " AND p.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $programs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'programs' => $programs]);
            exit;
        }
        
        // ── CREATE PROGRAM ────────────────────────────────────────────
        if ($action === 'create_program') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $program_name = trim($input['program_name'] ?? '');
            $description = trim($input['description'] ?? '');
            $category = $input['category'] ?? 'technical';
            $type = $input['type'] ?? 'online';
            $duration_hours = (int)($input['duration_hours'] ?? 0);
            $trainer = trim($input['trainer'] ?? '');
            $target_audience = trim($input['target_audience'] ?? '');
            $prerequisites = trim($input['prerequisites'] ?? '');
            $objectives = trim($input['objectives'] ?? '');
            $cost = (float)($input['cost'] ?? 0);
            $max_participants = (int)($input['max_participants'] ?? 0);
            
            if (empty($program_name)) {
                echo json_encode(['success' => false, 'error' => 'Program name is required']);
                exit;
            }
            
            $program_id = 'TRN-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO training_programs (
                    program_id, program_name, description, category, type, duration_hours,
                    trainer, target_audience, prerequisites, objectives, cost, max_participants,
                    status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())
            ");
            $stmt->execute([
                $program_id, $program_name, $description, $category, $type, $duration_hours,
                $trainer, $target_audience, $prerequisites, $objectives, $cost, $max_participants,
                $user_id
            ]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Training Program Created', "Program #$program_id: $program_name"]);
            
            echo json_encode(['success' => true, 'program_id' => $program_id]);
            exit;
        }
        
        // ── UPDATE PROGRAM ────────────────────────────────────────────
        if ($action === 'update_program') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE training_programs SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE PROGRAM ────────────────────────────────────────────
        if ($action === 'delete_program') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM training_programs WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET SESSIONS ──────────────────────────────────────────────
        if ($action === 'get_sessions') {
            $program_id = (int)($_GET['program_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT s.*, p.program_name 
                    FROM training_sessions s
                    LEFT JOIN training_programs p ON s.course_id = p.id
                    WHERE 1=1";
            $params = [];
            
            if ($program_id > 0) {
                $sql .= " AND s.course_id = ?";
                $params[] = $program_id;
            }
            if ($status) {
                $sql .= " AND s.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY s.start_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $sessions = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'sessions' => $sessions]);
            exit;
        }
        
        // ── CREATE SESSION ────────────────────────────────────────────
        if ($action === 'create_session') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $course_id = (int)($input['program_id'] ?? 0);
            $session_name = trim($input['session_name'] ?? '');
            $start_date = $input['start_date'] ?? null;
            $end_date = $input['end_date'] ?? null;
            $location = trim($input['venue'] ?? '');
            $trainer_name = trim($input['trainer'] ?? '');
            $max_capacity = (int)($input['capacity'] ?? 0);
            
            if (!$course_id || empty($session_name) || !$start_date || !$end_date) {
                echo json_encode(['success' => false, 'error' => 'Program, session name, start and end dates are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO training_sessions (
                    course_id, session_name, start_date, end_date, location, 
                    trainer_name, max_capacity, current_enrollment, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'scheduled', ?, NOW())
            ");
            $stmt->execute([$course_id, $session_name, $start_date, $end_date, $location, $trainer_name, $max_capacity, $user_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE SESSION ────────────────────────────────────────────
        if ($action === 'update_session') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE training_sessions SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET ENROLLMENTS ───────────────────────────────────────────
        if ($action === 'get_enrollments') {
            $session_id = (int)($_GET['session_id'] ?? 0);
            $employee_id = (int)($_GET['employee_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT e.*, u.name as employee_name, s.session_name, p.program_name 
                    FROM training_enrollments e
                    LEFT JOIN users u ON e.user_id = u.id
                    LEFT JOIN training_sessions s ON e.course_id = s.course_id
                    LEFT JOIN training_programs p ON s.course_id = p.id
                    WHERE 1=1";
            $params = [];
            
            if ($session_id > 0) {
                $sql .= " AND e.course_id = ?";
                $params[] = $session_id;
            }
            if ($employee_id > 0) {
                $sql .= " AND e.user_id = ?";
                $params[] = $employee_id;
            }
            if ($status) {
                $sql .= " AND e.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY e.enrollment_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $enrollments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'enrollments' => $enrollments]);
            exit;
        }
        
        // ── ENROLL EMPLOYEE ──────────────────────────────────────────
        if ($action === 'enroll_employee') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $course_id = (int)($input['session_id'] ?? 0);
            $user_id = (int)($input['employee_id'] ?? 0);
            
            if (!$course_id || !$user_id) {
                echo json_encode(['success' => false, 'error' => 'Session and employee are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO training_enrollments (user_id, course_id, enrollment_date, status, created_at)
                VALUES (?, ?, CURDATE(), 'in_progress', NOW())
                ON DUPLICATE KEY UPDATE status = 'in_progress'
            ");
            $stmt->execute([$user_id, $course_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE ENROLLMENT ─────────────────────────────────────────
        if ($action === 'update_enrollment') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $score = (float)($input['score'] ?? 0);
            $feedback = trim($input['feedback'] ?? '');
            $rating = (int)($input['rating'] ?? 0);
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE training_enrollments SET status = ?";
            $params = [$status];
            
            if ($status === 'completed') {
                $sql .= ", completion_date = CURDATE()";
            }
            if ($score > 0) {
                $sql .= ", score = ?";
                $params[] = $score;
            }
            if ($feedback) {
                $sql .= ", feedback = ?";
                $params[] = $feedback;
            }
            if ($rating > 0) {
                $sql .= ", rating = ?";
                $params[] = $rating;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET CERTIFICATIONS ────────────────────────────────────────
        if ($action === 'get_certifications') {
            $employee_id = (int)($_GET['employee_id'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT c.*, u.name as employee_name 
                    FROM training_certifications c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($employee_id > 0) {
                $sql .= " AND c.user_id = ?";
                $params[] = $employee_id;
            }
            if ($status) {
                $sql .= " AND c.is_active = ?";
                $params[] = ($status === 'active') ? 1 : 0;
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $certifications = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'certifications' => $certifications]);
            exit;
        }
        
        // ── CREATE CERTIFICATION ──────────────────────────────────────
        if ($action === 'create_certification') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $user_id = (int)($input['employee_id'] ?? 0);
            $certification_name = trim($input['certification_name'] ?? '');
            $issuing_body = trim($input['issuing_authority'] ?? '');
            $issue_date = $input['issue_date'] ?? null;
            $expiry_date = $input['expiry_date'] ?? null;
            $validity_years = (int)($input['validity_years'] ?? 1);
            
            if (!$user_id || empty($certification_name)) {
                echo json_encode(['success' => false, 'error' => 'Employee and certification name are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO training_certifications (
                    user_id, certification_name, issuing_body, issue_date, expiry_date, 
                    validity_years, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$user_id, $certification_name, $issuing_body, $issue_date, $expiry_date, $validity_years]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── GET USERS/EMPLOYEES ──────────────────────────────────────
        if ($action === 'get_employees') {
            $stmt = $pdo->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
            $employees = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'employees' => $employees]);
            exit;
        }
        
        // ── GET FEEDBACK STATS ────────────────────────────────────────
        if ($action === 'get_feedback_stats') {
            $stmt = $pdo->query("
                SELECT 
                    ROUND(AVG(rating)) as avg_rating,
                    ROUND(AVG(content_rating)) as avg_content,
                    ROUND(AVG(trainer_rating)) as avg_trainer,
                    ROUND(AVG(overall_rating)) as avg_overall
                FROM training_feedback
            ");
            $stats = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'avg_rating' => round($stats['avg_rating'] ?? 0, 1),
                'avg_content' => round($stats['avg_content'] ?? 0, 1),
                'avg_trainer' => round($stats['avg_trainer'] ?? 0, 1),
                'avg_overall' => round($stats['avg_overall'] ?? 0, 1)
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
<title>Training Dashboard | CIBIL Repair</title>

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
.badge-purple { background: var(--purple-bg); color: var(--purple); }

/* TRAINING STATUS BADGES */
.status-draft { background: #e5e7eb; color: #4b5563; }
.status-published { background: #dbeafe; color: #1e40af; }
.status-in_progress { background: #fef3c7; color: #78350f; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

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
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">TR</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Training Management</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Training Programs</div>
        <div class="nav-item" data-section="programs">
            <i class="fas fa-graduation-cap"></i>
            <span class="nav-label">All Programs</span>
        </div>
        <div class="nav-item" data-section="createProgram">
            <i class="fas fa-plus-circle"></i>
            <span class="nav-label">New Program</span>
        </div>
        <div class="nav-section-label">Sessions</div>
        <div class="nav-item" data-section="sessions">
            <i class="fas fa-calendar-alt"></i>
            <span class="nav-label">Sessions</span>
        </div>
        <div class="nav-section-label">Enrollments</div>
        <div class="nav-item" data-section="enrollments">
            <i class="fas fa-users"></i>
            <span class="nav-label">Enrollments</span>
        </div>
        <div class="nav-item" data-section="certifications">
            <i class="fas fa-certificate"></i>
            <span class="nav-label">Certifications</span>
        </div>
        <div class="nav-section-label">Feedback</div>
        <div class="nav-item" data-section="feedback">
            <i class="fas fa-star"></i>
            <span class="nav-label">Feedback</span>
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
            <span class="page-title" id="pageTitle">Training Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-graduation-cap"></i></span>
                    <div class="stat-value" id="totalPrograms">—</div>
                    <div class="stat-label">Total Programs</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-play-circle"></i></span>
                    <div class="stat-value" id="activePrograms">—</div>
                    <div class="stat-label">Active Programs</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                    <div class="stat-value" id="totalEnrollments">—</div>
                    <div class="stat-label">Total Enrollments</div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon"><i class="fas fa-certificate"></i></span>
                    <div class="stat-value" id="certifications">—</div>
                    <div class="stat-label">Certifications</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Program Categories</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-doughnut"></i> Program Status</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Programs</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('createProgram')"><i class="fas fa-plus"></i> New Program</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Program ID</th><th>Name</th><th>Category</th><th>Type</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="recentProgramsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== PROGRAMS ====== -->
        <div class="section" id="programsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-graduation-cap"></i> All Training Programs</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="showSection('createProgram')"><i class="fas fa-plus"></i> New Program</button>
                        <button class="btn btn-success btn-sm" onclick="exportPrograms()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="programSearch" placeholder="Search programs…" oninput="debounce(loadPrograms, 400)()">
                    </div>
                    <select class="form-select" id="programCategory" onchange="loadPrograms()" style="width:150px;padding:8px 12px;">
                        <option value="">All Categories</option>
                        <option value="technical">Technical</option>
                        <option value="soft_skills">Soft Skills</option>
                        <option value="compliance">Compliance</option>
                        <option value="leadership">Leadership</option>
                        <option value="sales">Sales</option>
                        <option value="product_knowledge">Product Knowledge</option>
                        <option value="onboarding">Onboarding</option>
                    </select>
                    <select class="form-select" id="programStatus" onchange="loadPrograms()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Program ID</th><th>Name</th><th>Category</th><th>Type</th><th>Duration</th><th>Trainer</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="programsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CREATE PROGRAM ====== -->
        <div class="section" id="createProgramSection">
            <div class="card" style="max-width:700px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-plus-circle"></i> Create Training Program</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('programs')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Program Name <span class="form-required">*</span></label>
                        <input class="form-input" id="programName" placeholder="Enter program name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="programDesc" rows="3" placeholder="Program description..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="programCategoryNew">
                                <option value="technical">Technical</option>
                                <option value="soft_skills">Soft Skills</option>
                                <option value="compliance">Compliance</option>
                                <option value="leadership">Leadership</option>
                                <option value="sales">Sales</option>
                                <option value="product_knowledge">Product Knowledge</option>
                                <option value="onboarding">Onboarding</option>
                            </select>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="programType">
                                <option value="online">Online</option>
                                <option value="in_person">In Person</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="self_paced">Self-Paced</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Duration (Hours)</label>
                            <input type="number" class="form-input" id="programDuration" placeholder="0" min="0">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Max Participants</label>
                            <input type="number" class="form-input" id="programMaxParticipants" placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label class="form-label">Trainer</label>
                            <input class="form-input" id="programTrainer" placeholder="Trainer name">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Cost (₹)</label>
                            <input type="number" class="form-input" id="programCost" placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Audience</label>
                        <input class="form-input" id="programAudience" placeholder="e.g., All employees, Managers">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prerequisites</label>
                        <textarea class="form-textarea" id="programPrerequisites" rows="2" placeholder="Prerequisites..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Objectives</label>
                        <textarea class="form-textarea" id="programObjectives" rows="2" placeholder="Learning objectives..."></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="createProgram()"><i class="fas fa-save"></i> Create Program</button>
                </div>
            </div>
        </div>

        <!-- ====== SESSIONS ====== -->
        <div class="section" id="sessionsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-calendar-alt"></i> Training Sessions</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('createSessionModal')"><i class="fas fa-plus"></i> New Session</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="sessionProgramFilter" onchange="loadSessions()" style="width:200px;padding:8px 12px;">
                        <option value="">All Programs</option>
                    </select>
                    <select class="form-select" id="sessionStatusFilter" onchange="loadSessions()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Session</th><th>Program</th><th>Start</th><th>End</th><th>Location</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="sessionsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ENROLLMENTS ====== -->
        <div class="section" id="enrollmentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Training Enrollments</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('enrollEmployeeModal')"><i class="fas fa-plus"></i> Enroll Employee</button>
                </div>
                <div class="filter-bar">
                    <select class="form-select" id="enrollmentStatusFilter" onchange="loadEnrollments()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="not_started">Not Started</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Employee</th><th>Program</th><th>Session</th><th>Status</th><th>Progress</th><th>Score</th><th>Actions</th></tr></thead>
                        <tbody id="enrollmentsBody">
                            <tr><td colspan="7"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CERTIFICATIONS ====== -->
        <div class="section" id="certificationsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-certificate"></i> Certifications</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('createCertificationModal')"><i class="fas fa-plus"></i> Add Certification</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Employee</th><th>Certification</th><th>Issuing Body</th><th>Validity</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="certificationsBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== FEEDBACK ====== -->
        <div class="section" id="feedbackSection">
            <div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
                <div class="stat-card green"><div class="stat-value" id="avgRating">—</div><div class="stat-label">Overall Rating</div></div>
                <div class="stat-card blue"><div class="stat-value" id="avgContent">—</div><div class="stat-label">Content Rating</div></div>
                <div class="stat-card amber"><div class="stat-value" id="avgTrainer">—</div><div class="stat-label">Trainer Rating</div></div>
                <div class="stat-card purple"><div class="stat-value" id="avgOverall">—</div><div class="stat-label">Overall Experience</div></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-star"></i> Training Feedback</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Employee</th><th>Program</th><th>Rating</th><th>Content</th><th>Trainer</th><th>Feedback</th><th>Date</th></tr></thead>
                        <tbody id="feedbackBody">
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
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Training Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Program Status Modal -->
<div class="modal-overlay" id="updateProgramModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Program</span>
            <button class="modal-close" onclick="closeModal('updateProgramModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateProgramId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateProgramStatus">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateProgramNotes" rows="3" placeholder="Update notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateProgramModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateProgram()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Create Session Modal -->
<div class="modal-overlay" id="createSessionModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Create Session</span>
            <button class="modal-close" onclick="closeModal('createSessionModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Program <span class="form-required">*</span></label>
                <select class="form-select" id="sessionProgram"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Session Name <span class="form-required">*</span></label>
                <input class="form-input" id="sessionName" placeholder="Enter session name">
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Start Date <span class="form-required">*</span></label>
                    <input type="datetime-local" class="form-input" id="sessionStart">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">End Date <span class="form-required">*</span></label>
                    <input type="datetime-local" class="form-input" id="sessionEnd">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Location</label>
                    <input class="form-input" id="sessionVenue" placeholder="Venue location">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-input" id="sessionCapacity" placeholder="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Trainer</label>
                <input class="form-input" id="sessionTrainer" placeholder="Trainer name">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createSessionModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createSession()"><i class="fas fa-save"></i> Create Session</button>
        </div>
    </div>
</div>

<!-- Update Session Modal -->
<div class="modal-overlay" id="updateSessionModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Session</span>
            <button class="modal-close" onclick="closeModal('updateSessionModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateSessionId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateSessionStatus">
                    <option value="scheduled">Scheduled</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateSessionModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateSession()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Enroll Employee Modal -->
<div class="modal-overlay" id="enrollEmployeeModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Enroll Employee</span>
            <button class="modal-close" onclick="closeModal('enrollEmployeeModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Session <span class="form-required">*</span></label>
                <select class="form-select" id="enrollSession"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Employee <span class="form-required">*</span></label>
                <select class="form-select" id="enrollEmployee"></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('enrollEmployeeModal')">Cancel</button>
            <button class="btn btn-primary" onclick="enrollEmployee()"><i class="fas fa-save"></i> Enroll</button>
        </div>
    </div>
</div>

<!-- Update Enrollment Modal -->
<div class="modal-overlay" id="updateEnrollmentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Enrollment</span>
            <button class="modal-close" onclick="closeModal('updateEnrollmentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateEnrollmentId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateEnrollmentStatus">
                    <option value="not_started">Not Started</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Score</label>
                <input type="number" class="form-input" id="updateEnrollmentScore" placeholder="0" min="0" max="100">
            </div>
            <div class="form-group">
                <label class="form-label">Rating</label>
                <select class="form-select" id="updateEnrollmentRating">
                    <option value="0">Not Rated</option>
                    <option value="1">⭐</option>
                    <option value="2">⭐⭐</option>
                    <option value="3">⭐⭐⭐</option>
                    <option value="4">⭐⭐⭐⭐</option>
                    <option value="5">⭐⭐⭐⭐⭐</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Feedback</label>
                <textarea class="form-textarea" id="updateEnrollmentFeedback" rows="3" placeholder="Feedback..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateEnrollmentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateEnrollment()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Create Certification Modal -->
<div class="modal-overlay" id="createCertificationModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Certification</span>
            <button class="modal-close" onclick="closeModal('createCertificationModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Employee <span class="form-required">*</span></label>
                <select class="form-select" id="certEmployee"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Certification Name <span class="form-required">*</span></label>
                <input class="form-input" id="certName" placeholder="Certification name">
            </div>
            <div class="form-group">
                <label class="form-label">Issuing Body</label>
                <input class="form-input" id="certAuthority" placeholder="Issuing authority">
            </div>
            <div class="form-row">
                <div class="form-group flex-1">
                    <label class="form-label">Issue Date</label>
                    <input type="date" class="form-input" id="certIssueDate">
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Validity (Years)</label>
                    <input type="number" class="form-input" id="certValidity" placeholder="1" min="1" max="10">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createCertificationModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createCertification()"><i class="fas fa-save"></i> Add Certification</button>
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
const USER_ID = <?= json_encode($user_id) ?>;

// ── THEME ─────────────────────────────────────────────────────────────
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('trainingTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('trainingTheme') || 'light'); })();

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
    dashboard: 'Training Dashboard',
    programs: 'Training Programs',
    createProgram: 'Create Program',
    sessions: 'Sessions',
    enrollments: 'Enrollments',
    certifications: 'Certifications',
    feedback: 'Feedback',
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
        programs: loadPrograms,
        sessions: loadSessions,
        enrollments: loadEnrollments,
        certifications: loadCertifications,
        feedback: loadFeedback,
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
function getProgramStatusBadge(status) {
    const map = {
        'draft': 'status-draft',
        'published': 'status-published',
        'in_progress': 'status-in_progress',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled'
    };
    const labels = {
        'draft': 'Draft',
        'published': 'Published',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    const cls = map[status?.toLowerCase()] || 'status-draft';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getEnrollmentStatusBadge(status) {
    const map = {
        'not_started': 'badge-gray',
        'in_progress': 'badge-info',
        'completed': 'badge-success',
        'failed': 'badge-danger',
        'expired': 'badge-warning'
    };
    const labels = {
        'not_started': 'Not Started',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'failed': 'Failed',
        'expired': 'Expired'
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
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast(data.error || 'Failed to load dashboard', 'error'); return; }

    document.getElementById('totalPrograms').textContent = data.total_programs || 0;
    document.getElementById('activePrograms').textContent = data.active_programs || 0;
    document.getElementById('totalEnrollments').textContent = data.total_enrollments || 0;
    document.getElementById('certifications').textContent = data.certifications || 0;

    // Category chart
    if (data.category_data) {
        destroyChart('categoryChart');
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const colors = ['#7c3aed', '#2563eb', '#0d9e78', '#d97706', '#dc2626', '#8b5cf6', '#059669'];
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

    // Status chart - use program status distribution
    const statusData = await apiCall('get_programs');
    if (statusData.success && statusData.programs) {
        const statusCounts = { draft: 0, published: 0, in_progress: 0, completed: 0, cancelled: 0 };
        statusData.programs.forEach(p => {
            if (statusCounts.hasOwnProperty(p.status)) statusCounts[p.status]++;
        });
        
        destroyChart('statusChart');
        const ctx = document.getElementById('statusChart').getContext('2d');
        const colors2 = ['#9ca3af', '#3b82f6', '#d97706', '#059669', '#dc2626'];
        charts.statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Published', 'In Progress', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [statusCounts.draft, statusCounts.published, statusCounts.in_progress, statusCounts.completed, statusCounts.cancelled],
                    backgroundColor: colors2,
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

    // Recent programs
    const body = document.getElementById('recentProgramsBody');
    if (data.recent_programs && data.recent_programs.length) {
        body.innerHTML = data.recent_programs.map(p => `
            <tr>
                <td><span class="font-mono">${escHtml(p.program_id)}</span></td>
                <td><strong>${escHtml(p.program_name)}</strong></td>
                <td><span class="badge badge-info">${escHtml(p.category)}</span></td>
                <td><span class="badge badge-brand">${escHtml(p.type)}</span></td>
                <td>${p.duration_hours || 0}h</td>
                <td>${getProgramStatusBadge(p.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateProgram(${p.id}, '${p.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteProgram(${p.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No programs found</p></div></td></tr>';
    }
}

// ── PROGRAMS ──────────────────────────────────────────────────────────
async function loadPrograms() {
    const search = document.getElementById('programSearch')?.value || '';
    const category = document.getElementById('programCategory')?.value || '';
    const status = document.getElementById('programStatus')?.value || '';
    
    const data = await apiCall(`get_programs?search=${encodeURIComponent(search)}&category=${category}&status=${status}`);
    const body = document.getElementById('programsBody');
    
    if (data.success && data.programs && data.programs.length) {
        body.innerHTML = data.programs.map(p => `
            <tr>
                <td><span class="font-mono">${escHtml(p.program_id)}</span></td>
                <td><strong>${escHtml(p.program_name)}</strong></td>
                <td><span class="badge badge-info">${escHtml(p.category)}</span></td>
                <td><span class="badge badge-brand">${escHtml(p.type)}</span></td>
                <td>${p.duration_hours || 0}h</td>
                <td>${escHtml(p.trainer || '—')}</td>
                <td>${getProgramStatusBadge(p.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateProgram(${p.id}, '${p.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteProgram(${p.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No programs found</p></div></td></tr>';
    }
}

function openUpdateProgram(id, status) {
    document.getElementById('updateProgramId').value = id;
    document.getElementById('updateProgramStatus').value = status || 'draft';
    document.getElementById('updateProgramNotes').value = '';
    openModal('updateProgramModal');
}

async function updateProgram() {
    const id = document.getElementById('updateProgramId').value;
    const status = document.getElementById('updateProgramStatus').value;
    const notes = document.getElementById('updateProgramNotes').value.trim();
    
    const result = await apiCall('update_program', 'POST', { id, status, notes });
    if (result.success) {
        showToast('Program updated!', 'success');
        closeModal('updateProgramModal');
        loadDashboard();
        loadPrograms();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

async function deleteProgram(id) {
    if (!confirm('Delete this program?')) return;
    const result = await apiCall('delete_program', 'POST', { id });
    if (result.success) {
        showToast('Program deleted', 'success');
        loadDashboard();
        loadPrograms();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

// ── CREATE PROGRAM ──────────────────────────────────────────────────
async function createProgram() {
    const program_name = document.getElementById('programName').value.trim();
    const description = document.getElementById('programDesc').value.trim();
    const category = document.getElementById('programCategoryNew').value;
    const type = document.getElementById('programType').value;
    const duration_hours = parseInt(document.getElementById('programDuration').value) || 0;
    const max_participants = parseInt(document.getElementById('programMaxParticipants').value) || 0;
    const trainer = document.getElementById('programTrainer').value.trim();
    const cost = parseFloat(document.getElementById('programCost').value) || 0;
    const target_audience = document.getElementById('programAudience').value.trim();
    const prerequisites = document.getElementById('programPrerequisites').value.trim();
    const objectives = document.getElementById('programObjectives').value.trim();
    
    if (!program_name) { showToast('Program name is required', 'warning'); return; }
    
    const result = await apiCall('create_program', 'POST', {
        program_name, description, category, type, duration_hours, max_participants,
        trainer, cost, target_audience, prerequisites, objectives
    });
    
    if (result.success) {
        showToast('Program created!', 'success');
        showSection('programs');
        document.getElementById('programName').value = '';
        document.getElementById('programDesc').value = '';
        document.getElementById('programDuration').value = '';
        document.getElementById('programMaxParticipants').value = '';
        document.getElementById('programTrainer').value = '';
        document.getElementById('programCost').value = '';
        document.getElementById('programAudience').value = '';
        document.getElementById('programPrerequisites').value = '';
        document.getElementById('programObjectives').value = '';
        loadDashboard();
        loadPrograms();
    } else {
        showToast(result.error || 'Failed to create program', 'error');
    }
}

// ── SESSIONS ──────────────────────────────────────────────────────────
async function loadSessions() {
    const program_id = document.getElementById('sessionProgramFilter')?.value || '';
    const status = document.getElementById('sessionStatusFilter')?.value || '';
    
    const data = await apiCall(`get_sessions?program_id=${program_id}&status=${status}`);
    const body = document.getElementById('sessionsBody');
    
    if (data.success && data.sessions && data.sessions.length) {
        body.innerHTML = data.sessions.map(s => `
            <tr>
                <td><strong>${escHtml(s.session_name)}</strong></td>
                <td>${escHtml(s.program_name || '—')}</td>
                <td>${new Date(s.start_date).toLocaleString('en-IN')}</td>
                <td>${new Date(s.end_date).toLocaleString('en-IN')}</td>
                <td>${escHtml(s.location || '—')}</td>
                <td>${s.current_enrollment || 0}/${s.max_capacity || 0}</td>
                <td>${getProgramStatusBadge(s.status)}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateSession(${s.id}, '${s.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-calendar-alt"></i><p>No sessions found</p></div></td></tr>';
    }
}

async function createSession() {
    const program_id = document.getElementById('sessionProgram').value;
    const session_name = document.getElementById('sessionName').value.trim();
    const start_date = document.getElementById('sessionStart').value;
    const end_date = document.getElementById('sessionEnd').value;
    const location = document.getElementById('sessionVenue').value.trim();
    const trainer = document.getElementById('sessionTrainer').value.trim();
    const capacity = parseInt(document.getElementById('sessionCapacity').value) || 0;
    
    if (!program_id) { showToast('Please select a program', 'warning'); return; }
    if (!session_name) { showToast('Session name is required', 'warning'); return; }
    if (!start_date || !end_date) { showToast('Start and end dates are required', 'warning'); return; }
    
    const result = await apiCall('create_session', 'POST', { program_id, session_name, start_date, end_date, location, trainer, capacity });
    if (result.success) {
        showToast('Session created!', 'success');
        closeModal('createSessionModal');
        document.getElementById('sessionName').value = '';
        document.getElementById('sessionVenue').value = '';
        document.getElementById('sessionTrainer').value = '';
        document.getElementById('sessionCapacity').value = '';
        loadSessions();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to create session', 'error');
    }
}

function openUpdateSession(id, status) {
    document.getElementById('updateSessionId').value = id;
    document.getElementById('updateSessionStatus').value = status || 'scheduled';
    openModal('updateSessionModal');
}

async function updateSession() {
    const id = document.getElementById('updateSessionId').value;
    const status = document.getElementById('updateSessionStatus').value;
    
    const result = await apiCall('update_session', 'POST', { id, status });
    if (result.success) {
        showToast('Session updated!', 'success');
        closeModal('updateSessionModal');
        loadSessions();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

// ── ENROLLMENTS ──────────────────────────────────────────────────────
async function loadEnrollments() {
    const status = document.getElementById('enrollmentStatusFilter')?.value || '';
    
    const data = await apiCall(`get_enrollments?status=${status}`);
    const body = document.getElementById('enrollmentsBody');
    
    if (data.success && data.enrollments && data.enrollments.length) {
        body.innerHTML = data.enrollments.map(e => `
            <tr>
                <td><strong>${escHtml(e.employee_name || '—')}</strong></td>
                <td>${escHtml(e.program_name || '—')}</td>
                <td>${escHtml(e.session_name || '—')}</td>
                <td>${getEnrollmentStatusBadge(e.status)}</td>
                <td>${e.progress_percentage || 0}%</td>
                <td>${e.score || 0}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateEnrollment(${e.id}, '${e.status}')"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><p>No enrollments found</p></div></td></tr>';
    }
}

async function enrollEmployee() {
    const session_id = document.getElementById('enrollSession').value;
    const employee_id = document.getElementById('enrollEmployee').value;
    
    if (!session_id) { showToast('Please select a session', 'warning'); return; }
    if (!employee_id) { showToast('Please select an employee', 'warning'); return; }
    
    const result = await apiCall('enroll_employee', 'POST', { session_id, employee_id });
    if (result.success) {
        showToast('Employee enrolled!', 'success');
        closeModal('enrollEmployeeModal');
        loadEnrollments();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to enroll', 'error');
    }
}

function openUpdateEnrollment(id, status) {
    document.getElementById('updateEnrollmentId').value = id;
    document.getElementById('updateEnrollmentStatus').value = status || 'not_started';
    document.getElementById('updateEnrollmentScore').value = '';
    document.getElementById('updateEnrollmentRating').value = 0;
    document.getElementById('updateEnrollmentFeedback').value = '';
    openModal('updateEnrollmentModal');
}

async function updateEnrollment() {
    const id = document.getElementById('updateEnrollmentId').value;
    const status = document.getElementById('updateEnrollmentStatus').value;
    const score = parseFloat(document.getElementById('updateEnrollmentScore').value) || 0;
    const rating = parseInt(document.getElementById('updateEnrollmentRating').value) || 0;
    const feedback = document.getElementById('updateEnrollmentFeedback').value.trim();
    
    const result = await apiCall('update_enrollment', 'POST', { id, status, score, rating, feedback });
    if (result.success) {
        showToast('Enrollment updated!', 'success');
        closeModal('updateEnrollmentModal');
        loadEnrollments();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

// ── CERTIFICATIONS ──────────────────────────────────────────────────
async function loadCertifications() {
    const data = await apiCall('get_certifications');
    const body = document.getElementById('certificationsBody');
    
    if (data.success && data.certifications && data.certifications.length) {
        body.innerHTML = data.certifications.map(c => `
            <tr>
                <td><strong>${escHtml(c.employee_name || '—')}</strong></td>
                <td><strong>${escHtml(c.certification_name)}</strong></td>
                <td>${escHtml(c.issuing_body || '—')}</td>
                <td>${c.validity_years || 1} year(s)</td>
                <td>${c.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-certificate"></i><p>No certifications found</p></div></td></tr>';
    }
}

async function createCertification() {
    const employee_id = document.getElementById('certEmployee').value;
    const certification_name = document.getElementById('certName').value.trim();
    const issuing_body = document.getElementById('certAuthority').value.trim();
    const issue_date = document.getElementById('certIssueDate').value;
    const validity_years = parseInt(document.getElementById('certValidity').value) || 1;
    
    if (!employee_id) { showToast('Please select an employee', 'warning'); return; }
    if (!certification_name) { showToast('Certification name is required', 'warning'); return; }
    
    const result = await apiCall('create_certification', 'POST', {
        employee_id, certification_name, issuing_body, issue_date, validity_years
    });
    if (result.success) {
        showToast('Certification added!', 'success');
        closeModal('createCertificationModal');
        document.getElementById('certName').value = '';
        document.getElementById('certAuthority').value = '';
        document.getElementById('certIssueDate').value = '';
        document.getElementById('certValidity').value = '';
        loadCertifications();
        loadDashboard();
    } else {
        showToast(result.error || 'Failed to add certification', 'error');
    }
}

// ── FEEDBACK ─────────────────────────────────────────────────────────
async function loadFeedback() {
    // Load feedback stats
    const statsData = await apiCall('get_feedback_stats');
    if (statsData.success) {
        document.getElementById('avgRating').textContent = statsData.avg_rating || '—';
        document.getElementById('avgContent').textContent = statsData.avg_content || '—';
        document.getElementById('avgTrainer').textContent = statsData.avg_trainer || '—';
        document.getElementById('avgOverall').textContent = statsData.avg_overall || '—';
    }
    
    // Load feedback list
    const data = await apiCall('get_enrollments?status=completed');
    const body = document.getElementById('feedbackBody');
    
    if (data.success && data.enrollments && data.enrollments.length) {
        const feedbackData = data.enrollments.filter(e => e.rating > 0 || e.feedback);
        if (feedbackData.length) {
            body.innerHTML = feedbackData.map(e => `
                <tr>
                    <td><strong>${escHtml(e.employee_name || '—')}</strong></td>
                    <td>${escHtml(e.program_name || '—')}</td>
                    <td>${e.rating ? '⭐'.repeat(e.rating) : '—'}</td>
                    <td>${e.rating || '—'}</td>
                    <td>${e.rating || '—'}</td>
                    <td>${escHtml(e.feedback || '—')}</td>
                    <td>${new Date(e.completion_date).toLocaleDateString('en-IN')}</td>
                </tr>
            `).join('');
        } else {
            body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-star"></i><p>No feedback found</p></div></td></tr>';
        }
    } else {
        body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fas fa-star"></i><p>No feedback found</p></div></td></tr>';
    }
}

// ── ANALYTICS ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    if (data.category_data) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const colors = ['#7c3aed', '#2563eb', '#0d9e78', '#d97706', '#dc2626', '#8b5cf6', '#059669'];
        charts.analyticsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.category_data.labels || [],
                datasets: [{
                    label: 'Programs by Category',
                    data: data.category_data.values || [],
                    backgroundColor: colors.slice(0, data.category_data.labels?.length || 0),
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

// ── LOAD DROPDOWNS ──────────────────────────────────────────────────
async function loadDropdowns() {
    // Load programs for session dropdown
    const programsData = await apiCall('get_programs');
    if (programsData.success && programsData.programs) {
        const selectIds = ['sessionProgram', 'sessionProgramFilter'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Program —</option>' +
                    programsData.programs.map(p => `<option value="${p.id}">${escHtml(p.program_name)}</option>`).join('');
            }
        });
    }
    
    // Load sessions for enrollment dropdown
    const sessionsData = await apiCall('get_sessions');
    if (sessionsData.success && sessionsData.sessions) {
        const select = document.getElementById('enrollSession');
        if (select) {
            select.innerHTML = '<option value="">— Select Session —</option>' +
                sessionsData.sessions.map(s => `<option value="${s.id}">${escHtml(s.session_name)} (${escHtml(s.program_name || '')})</option>`).join('');
        }
    }
    
    // Load employees
    const employeesData = await apiCall('get_employees');
    if (employeesData.success && employeesData.employees) {
        const selectIds = ['enrollEmployee', 'certEmployee'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Employee —</option>' +
                    employeesData.employees.map(e => `<option value="${e.id}">${escHtml(e.name)}</option>`).join('');
            }
        });
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportPrograms() { showToast('Exporting programs...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'p') showSection('programs');
    if (e.altKey && e.key === 's') showSection('sessions');
    if (e.altKey && e.key === 'e') showSection('enrollments');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (['createSessionModal', 'enrollEmployeeModal', 'createCertificationModal'].includes(modal.id)) {
                loadDropdowns();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadDropdowns();

console.log('✅ Training Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>