<?php
// ============================================================
// DOCUMENT DASHBOARD - FULLY INTEGRATED
// Access: admin, manager, super_admin
// Purpose: Central document management system
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

// ── AUTH: allow admin, manager, super_admin ──────────────
$allowed_roles = ['admin', 'manager', 'super_admin'];
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
$user_name = $_SESSION['user_name'] ?? 'Document Manager';
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
            // Total documents
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents");
            $total_docs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Pending verification
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents WHERE status = 'pending'");
            $pending_docs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Verified documents
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents WHERE status = 'verified'");
            $verified_docs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Expired documents
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents WHERE status = 'expired' OR expiry_date < CURDATE()");
            $expired_docs = (int)($stmt->fetch()['total'] ?? 0);
            
            // Total size
            $stmt = $pdo->query("SELECT SUM(file_size) as total FROM documents");
            $total_size = (int)($stmt->fetch()['total'] ?? 0);
            
            // Category distribution
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as count 
                FROM documents 
                GROUP BY category
            ");
            $cat_data = $stmt->fetchAll();
            $cat_labels = [];
            $cat_values = [];
            foreach ($cat_data as $c) {
                $cat_labels[] = ucwords(str_replace('_', ' ', $c['category']));
                $cat_values[] = (int)$c['count'];
            }
            
            // Recent documents
            $stmt = $pdo->query("
                SELECT d.*, c.name as client_name, u.name as uploaded_by_name 
                FROM documents d
                LEFT JOIN customers c ON d.client_id = c.id
                LEFT JOIN users u ON d.uploaded_by = u.id
                ORDER BY d.uploaded_at DESC
                LIMIT 10
            ");
            $recent_docs = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'total_docs' => $total_docs,
                'pending_docs' => $pending_docs,
                'verified_docs' => $verified_docs,
                'expired_docs' => $expired_docs,
                'total_size' => $total_size,
                'category_data' => ['labels' => $cat_labels, 'values' => $cat_values],
                'recent_docs' => $recent_docs
            ]);
            exit;
        }
        
        // ── GET DOCUMENTS ────────────────────────────────────────────
        if ($action === 'get_documents') {
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $status = $_GET['status'] ?? '';
            $client_id = (int)($_GET['client_id'] ?? 0);
            
            $sql = "SELECT d.*, c.name as client_name, u.name as uploaded_by_name 
                    FROM documents d
                    LEFT JOIN customers c ON d.client_id = c.id
                    LEFT JOIN users u ON d.uploaded_by = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (d.document_name LIKE ? OR d.document_id LIKE ? OR c.name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($category) {
                $sql .= " AND d.category = ?";
                $params[] = $category;
            }
            if ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
            if ($client_id > 0) {
                $sql .= " AND d.client_id = ?";
                $params[] = $client_id;
            }
            
            $sql .= " ORDER BY d.uploaded_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $documents = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'documents' => $documents]);
            exit;
        }
        
        // ── GET CATEGORIES ───────────────────────────────────────────
        if ($action === 'get_categories') {
            $stmt = $pdo->query("SELECT * FROM document_categories ORDER BY category_name");
            $categories = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'categories' => $categories]);
            exit;
        }
        
        // ── UPLOAD DOCUMENT ──────────────────────────────────────────
        if ($action === 'upload_document') {
            // This is handled via POST with file upload
            $client_id = (int)($_POST['client_id'] ?? 0);
            $document_name = trim($_POST['document_name'] ?? '');
            $document_type = trim($_POST['document_type'] ?? '');
            $category = $_POST['category'] ?? 'general';
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$client_id || empty($document_name) || empty($document_type)) {
                echo json_encode(['success' => false, 'error' => 'Client, document name and type are required']);
                exit;
            }
            
            // Handle file upload
            if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'Please select a file to upload']);
                exit;
            }
            
            $file = $_FILES['document_file'];
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $upload_dir = 'uploads/documents/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
                exit;
            }
            
            $document_id = 'DOC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO documents (
                    document_id, client_id, document_name, document_type, category,
                    file_name, file_path, file_size, file_type, status, uploaded_by, notes, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $stmt->execute([
                $document_id, $client_id, $document_name, $document_type, $category,
                $file['name'], $file_path, $file['size'], $file['type'], $user_id, $notes
            ]);
            
            $doc_id = $pdo->lastInsertId();
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Document Uploaded', "Document #$document_id uploaded for client ID $client_id"]);
            
            echo json_encode(['success' => true, 'document_id' => $document_id, 'id' => $doc_id]);
            exit;
        }
        
        // ── UPDATE DOCUMENT ──────────────────────────────────────────
        if ($action === 'update_document') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $document_name = trim($input['document_name'] ?? '');
            $document_type = trim($input['document_type'] ?? '');
            $category = $input['category'] ?? 'general';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || empty($document_name)) {
                echo json_encode(['success' => false, 'error' => 'Document ID and name are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE documents 
                SET document_name = ?, document_type = ?, category = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$document_name, $document_type, $category, $notes, $id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── UPDATE DOCUMENT STATUS ──────────────────────────────────
        if ($action === 'update_document_status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = trim($input['notes'] ?? '');
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'error' => 'Document ID and status are required']);
                exit;
            }
            
            $sql = "UPDATE documents SET status = ?";
            $params = [$status];
            
            if ($status === 'verified') {
                $sql .= ", verified_by = ?, verified_at = NOW()";
                $params[] = $user_id;
            }
            if ($notes) {
                $sql .= ", notes = CONCAT(notes, ?)";
                $params[] = "\n[" . date('Y-m-d H:i') . "] " . $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, 'Document Status Updated', "Document ID $id status changed to $status"]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── DELETE DOCUMENT ──────────────────────────────────────────
        if ($action === 'delete_document') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            // Get file path
            $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();
            
            if ($doc && file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            
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
        
        // ── GET DOCUMENT ACCESS LOGS ─────────────────────────────────
        if ($action === 'get_access_logs') {
            $doc_id = (int)($_GET['document_id'] ?? 0);
            
            $sql = "SELECT l.*, u.name as user_name 
                    FROM document_access_logs l
                    LEFT JOIN users u ON l.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($doc_id > 0) {
                $sql .= " AND l.document_id = ?";
                $params[] = $doc_id;
            }
            
            $sql .= " ORDER BY l.accessed_at DESC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }
        
        // ── GET SHARED DOCUMENTS ─────────────────────────────────────
        if ($action === 'get_shared_documents') {
            $user_id = (int)($_GET['user_id'] ?? $user_id);
            
            $stmt = $pdo->prepare("
                SELECT s.*, d.document_name, d.document_id, c.name as client_name,
                       u.name as shared_by_name
                FROM document_sharing s
                LEFT JOIN documents d ON s.document_id = d.id
                LEFT JOIN customers c ON d.client_id = c.id
                LEFT JOIN users u ON s.shared_by = u.id
                WHERE s.shared_with = ? OR s.shared_by = ?
                ORDER BY s.shared_at DESC
            ");
            $stmt->execute([$user_id, $user_id]);
            $shared = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'shared' => $shared]);
            exit;
        }
        
        // ── SHARE DOCUMENT ───────────────────────────────────────────
        if ($action === 'share_document') {
            $input = json_decode(file_get_contents('php://input'), true);
            $document_id = (int)($input['document_id'] ?? 0);
            $shared_with = (int)($input['shared_with'] ?? 0);
            $permission = $input['permission'] ?? 'view';
            $expires_at = $input['expires_at'] ?? null;
            
            if (!$document_id || !$shared_with) {
                echo json_encode(['success' => false, 'error' => 'Document ID and user are required']);
                exit;
            }
            
            $access_code = bin2hex(random_bytes(16));
            
            $stmt = $pdo->prepare("
                INSERT INTO document_sharing (document_id, shared_with, shared_by, permission, expires_at, access_code, shared_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$document_id, $shared_with, $user_id, $permission, $expires_at, $access_code]);
            
            echo json_encode(['success' => true, 'access_code' => $access_code]);
            exit;
        }
        
        // ── GET DOCUMENT TEMPLATES ───────────────────────────────────
        if ($action === 'get_templates') {
            $stmt = $pdo->query("SELECT * FROM document_templates ORDER BY template_name");
            $templates = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'templates' => $templates]);
            exit;
        }
        
        // ── ADD DOCUMENT CATEGORY ────────────────────────────────────
        if ($action === 'add_category') {
            $input = json_decode(file_get_contents('php://input'), true);
            $category_name = trim($input['category_name'] ?? '');
            $category_code = trim($input['category_code'] ?? '');
            $description = trim($input['description'] ?? '');
            $is_mandatory = isset($input['is_mandatory']) ? 1 : 0;
            
            if (empty($category_name) || empty($category_code)) {
                echo json_encode(['success' => false, 'error' => 'Category name and code are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO document_categories (category_name, category_code, description, is_mandatory, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$category_name, $category_code, $description, $is_mandatory]);
            
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
<title>Document Management | CIBIL Repair</title>

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
.stat-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

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
    max-width: 650px;
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
        <div class="brand-icon">DM</div>
        <div class="brand-text">
            <div class="brand-name">CIBIL Repair</div>
            <div class="brand-sub">Document Management</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-section-label">Documents</div>
        <div class="nav-item" data-section="allDocuments">
            <i class="fas fa-folder-open"></i>
            <span class="nav-label">All Documents</span>
        </div>
        <div class="nav-item" data-section="upload">
            <i class="fas fa-upload"></i>
            <span class="nav-label">Upload Document</span>
        </div>
        <div class="nav-item" data-section="pending">
            <i class="fas fa-clock"></i>
            <span class="nav-label">Pending Verification</span>
        </div>
        <div class="nav-section-label">Categories</div>
        <div class="nav-item" data-section="categories">
            <i class="fas fa-tags"></i>
            <span class="nav-label">Categories</span>
        </div>
        <div class="nav-section-label">Sharing</div>
        <div class="nav-item" data-section="shared">
            <i class="fas fa-share-alt"></i>
            <span class="nav-label">Shared Documents</span>
        </div>
        <div class="nav-section-label">Templates</div>
        <div class="nav-item" data-section="templates">
            <i class="fas fa-file-alt"></i>
            <span class="nav-label">Templates</span>
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
            <span class="page-title" id="pageTitle">Document Dashboard</span>
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
                    <span class="stat-icon"><i class="fas fa-file"></i></span>
                    <div class="stat-value" id="totalDocs">—</div>
                    <div class="stat-label">Total Documents</div>
                    <div class="stat-sub" id="totalSize">0 MB</div>
                </div>
                <div class="stat-card amber">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-value" id="pendingDocs">—</div>
                    <div class="stat-label">Pending Verification</div>
                </div>
                <div class="stat-card blue">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-value" id="verifiedDocs">—</div>
                    <div class="stat-label">Verified</div>
                </div>
                <div class="stat-card red">
                    <span class="stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="stat-value" id="expiredDocs">—</div>
                    <div class="stat-label">Expired</div>
                </div>
            </div>

            <div class="charts-row">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Category Distribution</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-line"></i> Document Status</div>
                    </div>
                    <div class="card-body chart-wrap">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Recent Documents</div>
                    <button class="btn btn-primary btn-sm" onclick="showSection('upload')"><i class="fas fa-upload"></i> Upload</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Client</th><th>Document</th><th>Type</th><th>Category</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead>
                        <tbody id="recentBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ALL DOCUMENTS ====== -->
        <div class="section" id="allDocumentsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-folder-open"></i> All Documents</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" onclick="showSection('upload')"><i class="fas fa-upload"></i> Upload</button>
                        <button class="btn btn-success btn-sm" onclick="exportDocuments()"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input class="search-input" id="docSearch" placeholder="Search documents…" oninput="debounce(loadDocuments, 400)()">
                    </div>
                    <select class="form-select" id="docCategory" onchange="loadDocuments()" style="width:150px;padding:8px 12px;">
                        <option value="">All Categories</option>
                        <option value="kyc">KYC</option>
                        <option value="legal">Legal</option>
                        <option value="credit_report">Credit Report</option>
                        <option value="dispute">Dispute</option>
                        <option value="invoice">Invoice</option>
                        <option value="agreement">Agreement</option>
                        <option value="general">General</option>
                    </select>
                    <select class="form-select" id="docStatus" onchange="loadDocuments()" style="width:150px;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </select>
                    <select class="form-select" id="docClient" onchange="loadDocuments()" style="width:180px;padding:8px 12px;">
                        <option value="">All Clients</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Client</th><th>Document</th><th>Type</th><th>Category</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead>
                        <tbody id="documentsBody">
                            <tr><td colspan="8"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== UPLOAD ====== -->
        <div class="section" id="uploadSection">
            <div class="card" style="max-width:650px;margin:0 auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-upload"></i> Upload Document</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('allDocuments')"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Client <span class="form-required">*</span></label>
                            <select class="form-select" id="uploadClient" required></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Document Name <span class="form-required">*</span></label>
                            <input class="form-input" id="uploadName" placeholder="Enter document name" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label class="form-label">Document Type <span class="form-required">*</span></label>
                                <input class="form-input" id="uploadType" placeholder="e.g., PAN Card, Aadhaar">
                            </div>
                            <div class="form-group flex-1">
                                <label class="form-label">Category <span class="form-required">*</span></label>
                                <select class="form-select" id="uploadCategory">
                                    <option value="kyc">KYC</option>
                                    <option value="legal">Legal</option>
                                    <option value="credit_report">Credit Report</option>
                                    <option value="dispute">Dispute</option>
                                    <option value="invoice">Invoice</option>
                                    <option value="agreement">Agreement</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File <span class="form-required">*</span></label>
                            <input type="file" class="form-input" id="uploadFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <small style="color:var(--text-muted);font-size:11px;">Allowed: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea class="form-textarea" id="uploadNotes" rows="3" placeholder="Additional notes…"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="uploadDocument()"><i class="fas fa-upload"></i> Upload Document</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ====== PENDING ====== -->
        <div class="section" id="pendingSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-clock"></i> Pending Verification</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Client</th><th>Document</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead>
                        <tbody id="pendingBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== CATEGORIES ====== -->
        <div class="section" id="categoriesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-tags"></i> Document Categories</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addCategoryModal')"><i class="fas fa-plus"></i> Add Category</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Category</th><th>Code</th><th>Description</th><th>Mandatory</th><th>Actions</th></tr></thead>
                        <tbody id="categoriesBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== SHARED ====== -->
        <div class="section" id="sharedSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-share-alt"></i> Shared Documents</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('shareDocumentModal')"><i class="fas fa-plus"></i> Share Document</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Document</th><th>Client</th><th>Shared With</th><th>Permission</th><th>Expires</th><th>Actions</th></tr></thead>
                        <tbody id="sharedBody">
                            <tr><td colspan="6"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== TEMPLATES ====== -->
        <div class="section" id="templatesSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-alt"></i> Document Templates</div>
                    <button class="btn btn-primary btn-sm" onclick="showToast('Upload template feature coming soon', 'info')"><i class="fas fa-upload"></i> Upload Template</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Template Name</th><th>Type</th><th>Description</th><th>Actions</th></tr></thead>
                        <tbody id="templatesBody">
                            <tr><td colspan="4"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====== ANALYTICS ====== -->
        <div class="section" id="analyticsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Document Analytics</div>
                </div>
                <div class="card-body chart-wrap" style="height:280px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list"></i> Access Logs</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Document</th><th>User</th><th>Action</th><th>IP Address</th><th>Time</th></tr></thead>
                        <tbody id="accessLogsBody">
                            <tr><td colspan="5"><div class="empty-state"><div class="spinner"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODALS ====== -->

<!-- Update Document Status Modal -->
<div class="modal-overlay" id="updateStatusModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-edit"></i> Update Document Status</span>
            <button class="modal-close" onclick="closeModal('updateStatusModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateStatusDocId">
            <div class="form-group">
                <label class="form-label">Status <span class="form-required">*</span></label>
                <select class="form-select" id="updateStatusValue">
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" id="updateStatusNotes" rows="3" placeholder="Add notes..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateDocumentStatus()"><i class="fas fa-save"></i> Update</button>
        </div>
    </div>
</div>

<!-- Share Document Modal -->
<div class="modal-overlay" id="shareDocumentModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-share-alt"></i> Share Document</span>
            <button class="modal-close" onclick="closeModal('shareDocumentModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Document <span class="form-required">*</span></label>
                <select class="form-select" id="shareDocument"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Share With <span class="form-required">*</span></label>
                <select class="form-select" id="shareWithUser"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Permission</label>
                <select class="form-select" id="sharePermission">
                    <option value="view">View Only</option>
                    <option value="download">View & Download</option>
                    <option value="edit">Edit</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Expires At</label>
                <input type="datetime-local" class="form-input" id="shareExpires">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('shareDocumentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="shareDocument()"><i class="fas fa-share-alt"></i> Share</button>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal-overlay" id="addCategoryModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-plus-circle"></i> Add Category</span>
            <button class="modal-close" onclick="closeModal('addCategoryModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Category Name <span class="form-required">*</span></label>
                <input class="form-input" id="categoryName" placeholder="e.g., Medical Documents">
            </div>
            <div class="form-group">
                <label class="form-label">Category Code <span class="form-required">*</span></label>
                <input class="form-input" id="categoryCode" placeholder="e.g., MEDICAL">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" id="categoryDesc" rows="2" placeholder="Category description..."></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="categoryMandatory"> Mandatory Document
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('addCategoryModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addCategory()"><i class="fas fa-save"></i> Add Category</button>
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
    localStorage.setItem('docTheme', t);
    document.getElementById('lightBtn').classList.toggle('active', t === 'light');
    document.getElementById('darkBtn').classList.toggle('active', t === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(() => { setTheme(localStorage.getItem('docTheme') || 'light'); })();

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
    dashboard: 'Document Dashboard',
    allDocuments: 'All Documents',
    upload: 'Upload Document',
    pending: 'Pending Verification',
    categories: 'Categories',
    shared: 'Shared Documents',
    templates: 'Templates',
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
        allDocuments: loadDocuments,
        pending: loadPending,
        categories: loadCategories,
        shared: loadShared,
        templates: loadTemplates,
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
        'pending': 'badge-warning',
        'verified': 'badge-success',
        'rejected': 'badge-danger',
        'expired': 'badge-gray'
    };
    const labels = {
        'pending': 'Pending',
        'verified': 'Verified',
        'rejected': 'Rejected',
        'expired': 'Expired'
    };
    const cls = map[status?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getCategoryBadge(category) {
    const map = {
        'kyc': 'badge-purple',
        'legal': 'badge-info',
        'credit_report': 'badge-brand',
        'dispute': 'badge-danger',
        'invoice': 'badge-success',
        'agreement': 'badge-blue',
        'general': 'badge-gray'
    };
    const labels = {
        'kyc': 'KYC',
        'legal': 'Legal',
        'credit_report': 'Credit Report',
        'dispute': 'Dispute',
        'invoice': 'Invoice',
        'agreement': 'Agreement',
        'general': 'General'
    };
    const cls = map[category?.toLowerCase()] || 'badge-gray';
    return `<span class="badge ${cls}">${labels[category] || category}</span>`;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

    document.getElementById('totalDocs').textContent = data.total_docs || 0;
    document.getElementById('pendingDocs').textContent = data.pending_docs || 0;
    document.getElementById('verifiedDocs').textContent = data.verified_docs || 0;
    document.getElementById('expiredDocs').textContent = data.expired_docs || 0;
    document.getElementById('totalSize').textContent = formatFileSize(data.total_size || 0);

    // Category chart
    if (data.category_data) {
        destroyChart('categoryChart');
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const colors = ['#7c3aed', '#2563eb', '#0d9e78', '#dc2626', '#059669', '#3b82f6', '#9ca3af'];
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

    // Status chart - using the same data
    const statusData = {
        labels: ['Pending', 'Verified', 'Rejected', 'Expired'],
        values: [data.pending_docs || 0, data.verified_docs || 0, 0, data.expired_docs || 0]
    };
    // We'll use a bar chart for status
    destroyChart('statusChart');
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    const colors2 = ['#d97706', '#059669', '#dc2626', '#9ca3af'];
    charts.statusChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: statusData.labels,
            datasets: [{
                label: 'Documents',
                data: statusData.values,
                backgroundColor: colors2,
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

    // Recent documents
    const body = document.getElementById('recentBody');
    if (data.recent_docs && data.recent_docs.length) {
        body.innerHTML = data.recent_docs.map(d => `
            <tr>
                <td><span class="font-mono">${escHtml(d.document_id)}</span></td>
                <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                <td>${escHtml(d.document_name)}</td>
                <td>${escHtml(d.document_type)}</td>
                <td>${getCategoryBadge(d.category)}</td>
                <td>${getStatusBadge(d.status)}</td>
                <td>${new Date(d.uploaded_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateStatus(${d.id}, '${d.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="downloadDocument(${d.id})"><i class="fas fa-download"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteDocument(${d.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><p>No documents found</p></div></td></tr>';
    }
}

function downloadDocument(id) {
    showToast(`Downloading document #${id}...`, 'info');
}

// ── DOCUMENTS ─────────────────────────────────────────────────────────
async function loadDocuments() {
    const search = document.getElementById('docSearch')?.value || '';
    const category = document.getElementById('docCategory')?.value || '';
    const status = document.getElementById('docStatus')?.value || '';
    const client_id = document.getElementById('docClient')?.value || '';
    
    const data = await apiCall(`get_documents?search=${encodeURIComponent(search)}&category=${category}&status=${status}&client_id=${client_id}`);
    const body = document.getElementById('documentsBody');
    
    if (data.success && data.documents && data.documents.length) {
        body.innerHTML = data.documents.map(d => `
            <tr>
                <td><span class="font-mono">${escHtml(d.document_id)}</span></td>
                <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                <td>${escHtml(d.document_name)}</td>
                <td>${escHtml(d.document_type)}</td>
                <td>${getCategoryBadge(d.category)}</td>
                <td>${getStatusBadge(d.status)}</td>
                <td>${new Date(d.uploaded_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="openUpdateStatus(${d.id}, '${d.status}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-primary btn-xs" onclick="downloadDocument(${d.id})"><i class="fas fa-download"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteDocument(${d.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No documents found</p></div></td></tr>';
    }
}

// ── UPLOAD ────────────────────────────────────────────────────────────
async function uploadDocument() {
    const client_id = document.getElementById('uploadClient').value;
    const document_name = document.getElementById('uploadName').value.trim();
    const document_type = document.getElementById('uploadType').value.trim();
    const category = document.getElementById('uploadCategory').value;
    const notes = document.getElementById('uploadNotes').value.trim();
    const fileInput = document.getElementById('uploadFile');
    
    if (!client_id) { showToast('Please select a client', 'warning'); return; }
    if (!document_name) { showToast('Document name is required', 'warning'); return; }
    if (!document_type) { showToast('Document type is required', 'warning'); return; }
    if (!fileInput.files || fileInput.files.length === 0) { showToast('Please select a file', 'warning'); return; }
    
    const formData = new FormData();
    formData.append('client_id', client_id);
    formData.append('document_name', document_name);
    formData.append('document_type', document_type);
    formData.append('category', category);
    formData.append('notes', notes);
    formData.append('document_file', fileInput.files[0]);
    formData.append('csrf_token', CSRF);
    
    try {
        const response = await fetch(API + 'upload_document', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Document uploaded successfully!', 'success');
            document.getElementById('uploadName').value = '';
            document.getElementById('uploadType').value = '';
            document.getElementById('uploadNotes').value = '';
            document.getElementById('uploadFile').value = '';
            showSection('allDocuments');
            loadDocuments();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to upload', 'error');
        }
    } catch (e) {
        showToast('Upload failed', 'error');
    }
}

// ── UPDATE STATUS ────────────────────────────────────────────────────
function openUpdateStatus(id, status) {
    document.getElementById('updateStatusDocId').value = id;
    document.getElementById('updateStatusValue').value = status || 'pending';
    document.getElementById('updateStatusNotes').value = '';
    openModal('updateStatusModal');
}

async function updateDocumentStatus() {
    const id = document.getElementById('updateStatusDocId').value;
    const status = document.getElementById('updateStatusValue').value;
    const notes = document.getElementById('updateStatusNotes').value.trim();
    
    const result = await apiCall('update_document_status', 'POST', { id, status, notes });
    if (result.success) {
        showToast('Status updated!', 'success');
        closeModal('updateStatusModal');
        loadDashboard();
        loadDocuments();
        loadPending();
    } else {
        showToast(result.error || 'Failed to update', 'error');
    }
}

// ── DELETE DOCUMENT ──────────────────────────────────────────────────
async function deleteDocument(id) {
    if (!confirm('Delete this document?')) return;
    const result = await apiCall('delete_document', 'POST', { id });
    if (result.success) {
        showToast('Document deleted', 'success');
        loadDashboard();
        loadDocuments();
        loadPending();
    } else {
        showToast(result.error || 'Failed to delete', 'error');
    }
}

// ── PENDING ──────────────────────────────────────────────────────────
async function loadPending() {
    const data = await apiCall('get_documents?status=pending');
    const body = document.getElementById('pendingBody');
    
    if (data.success && data.documents && data.documents.length) {
        body.innerHTML = data.documents.map(d => `
            <tr>
                <td><span class="font-mono">${escHtml(d.document_id)}</span></td>
                <td><strong>${escHtml(d.client_name || '—')}</strong></td>
                <td>${escHtml(d.document_name)}</td>
                <td>${escHtml(d.document_type)}</td>
                <td>${new Date(d.uploaded_at).toLocaleDateString('en-IN')}</td>
                <td>
                    <button class="btn btn-success btn-xs" onclick="openUpdateStatus(${d.id}, 'pending')"><i class="fas fa-check"></i> Verify</button>
                    <button class="btn btn-danger btn-xs" onclick="openUpdateStatus(${d.id}, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-clock"></i><p>No pending documents</p></div></td></tr>';
    }
}

// ── CATEGORIES ────────────────────────────────────────────────────────
async function loadCategories() {
    const data = await apiCall('get_categories');
    const body = document.getElementById('categoriesBody');
    
    if (data.success && data.categories && data.categories.length) {
        body.innerHTML = data.categories.map(c => `
            <tr>
                <td><strong>${escHtml(c.category_name)}</strong></td>
                <td><span class="badge badge-brand">${escHtml(c.category_code)}</span></td>
                <td>${escHtml(c.description || '—')}</td>
                <td>${c.is_mandatory ? '✅ Yes' : '—'}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-tags"></i><p>No categories found</p></div></td></tr>';
    }
}

async function addCategory() {
    const category_name = document.getElementById('categoryName').value.trim();
    const category_code = document.getElementById('categoryCode').value.trim().toUpperCase();
    const description = document.getElementById('categoryDesc').value.trim();
    const is_mandatory = document.getElementById('categoryMandatory').checked ? 1 : 0;
    
    if (!category_name) { showToast('Category name is required', 'warning'); return; }
    if (!category_code) { showToast('Category code is required', 'warning'); return; }
    
    const result = await apiCall('add_category', 'POST', { category_name, category_code, description, is_mandatory });
    if (result.success) {
        showToast('Category added!', 'success');
        closeModal('addCategoryModal');
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryCode').value = '';
        document.getElementById('categoryDesc').value = '';
        document.getElementById('categoryMandatory').checked = false;
        loadCategories();
    } else {
        showToast(result.error || 'Failed to add category', 'error');
    }
}

// ── SHARED ────────────────────────────────────────────────────────────
async function loadShared() {
    const data = await apiCall('get_shared_documents');
    const body = document.getElementById('sharedBody');
    
    if (data.success && data.shared && data.shared.length) {
        body.innerHTML = data.shared.map(s => `
            <tr>
                <td>${escHtml(s.document_name || '—')}</td>
                <td>${escHtml(s.client_name || '—')}</td>
                <td>${escHtml(s.shared_by_name || '—')}</td>
                <td><span class="badge badge-info">${escHtml(s.permission)}</span></td>
                <td>${s.expires_at ? new Date(s.expires_at).toLocaleDateString('en-IN') : 'Never'}</td>
                <td>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fas fa-share-alt"></i><p>No shared documents</p></div></td></tr>';
    }
}

async function shareDocument() {
    const document_id = document.getElementById('shareDocument').value;
    const shared_with = document.getElementById('shareWithUser').value;
    const permission = document.getElementById('sharePermission').value;
    const expires_at = document.getElementById('shareExpires').value;
    
    if (!document_id) { showToast('Please select a document', 'warning'); return; }
    if (!shared_with) { showToast('Please select a user', 'warning'); return; }
    
    const result = await apiCall('share_document', 'POST', { document_id, shared_with, permission, expires_at });
    if (result.success) {
        showToast('Document shared! Access code: ' + result.access_code, 'success');
        closeModal('shareDocumentModal');
        loadShared();
    } else {
        showToast(result.error || 'Failed to share', 'error');
    }
}

// ── TEMPLATES ────────────────────────────────────────────────────────
async function loadTemplates() {
    const data = await apiCall('get_templates');
    const body = document.getElementById('templatesBody');
    
    if (data.success && data.templates && data.templates.length) {
        body.innerHTML = data.templates.map(t => `
            <tr>
                <td><strong>${escHtml(t.template_name)}</strong></td>
                <td>${escHtml(t.template_type)}</td>
                <td>${escHtml(t.description || '—')}</td>
                <td>
                    <button class="btn btn-primary btn-xs"><i class="fas fa-download"></i> Download</button>
                    <button class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `).join('');
    } else {
        body.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-file-alt"></i><p>No templates found</p></div></td></tr>';
    }
}

// ── ANALYTICS ────────────────────────────────────────────────────────
async function loadAnalytics() {
    const data = await apiCall('get_dashboard_stats');
    if (!data.success) { showToast('Failed to load analytics', 'error'); return; }
    
    // Analytics chart - category distribution
    if (data.category_data) {
        destroyChart('analyticsChart');
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const colors = ['#7c3aed', '#2563eb', '#0d9e78', '#dc2626', '#059669', '#3b82f6', '#9ca3af'];
        charts.analyticsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.category_data.labels || [],
                datasets: [{
                    label: 'Documents by Category',
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
    
    // Access logs
    const logsData = await apiCall('get_access_logs');
    const logsBody = document.getElementById('accessLogsBody');
    
    if (logsData.success && logsData.logs && logsData.logs.length) {
        logsBody.innerHTML = logsData.logs.map(l => `
            <tr>
                <td>Document #${l.document_id}</td>
                <td>${escHtml(l.user_name || '—')}</td>
                <td><span class="badge badge-info">${escHtml(l.action)}</span></td>
                <td>${escHtml(l.ip_address || '—')}</td>
                <td>${new Date(l.accessed_at).toLocaleString('en-IN')}</td>
            </tr>
        `).join('');
    } else {
        logsBody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-history"></i><p>No access logs found</p></div></td></tr>';
    }
}

// ── LOAD DROPDOWNS ──────────────────────────────────────────────────
async function loadClients() {
    const data = await apiCall('get_clients');
    if (data.success && data.clients) {
        const selectIds = ['uploadClient', 'docClient'];
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.innerHTML = '<option value="">— Select Client —</option>' +
                    data.clients.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
            }
        });
    }
}

async function loadUsersForShare() {
    const data = await apiCall('get_users');
    if (data.success && data.users) {
        const select = document.getElementById('shareWithUser');
        if (select) {
            select.innerHTML = '<option value="">— Select User —</option>' +
                data.users.map(u => `<option value="${u.id}">${escHtml(u.name)} (${escHtml(u.role)})</option>`).join('');
        }
    }
}

async function loadDocumentsForShare() {
    const data = await apiCall('get_documents');
    if (data.success && data.documents) {
        const select = document.getElementById('shareDocument');
        if (select) {
            select.innerHTML = '<option value="">— Select Document —</option>' +
                data.documents.map(d => `<option value="${d.id}">${escHtml(d.document_name)} (${escHtml(d.document_id)})</option>`).join('');
        }
    }
}

// ── EXPORT FUNCTIONS ─────────────────────────────────────────────────
function exportDocuments() { showToast('Exporting documents...', 'info'); }

// ── LOGOUT ────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').onclick = () => {
    if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php';
};

// ── KEYBOARD SHORTCUTS ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'd') showSection('dashboard');
    if (e.altKey && e.key === 'a') showSection('allDocuments');
    if (e.altKey && e.key === 'u') showSection('upload');
    if (e.altKey && e.key === 'p') showSection('pending');
});

// ── MODAL POPULATE ON OPEN ──────────────────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(modal => {
    const observer = new MutationObserver(() => {
        if (modal.classList.contains('open')) {
            if (modal.id === 'shareDocumentModal') {
                loadDocumentsForShare();
                loadUsersForShare();
            }
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});

// ── INIT ──────────────────────────────────────────────────────────────
loadDashboard();
loadClients();
loadUsersForShare();

console.log('✅ Document Dashboard initialized');
console.log('👤 User ID:', <?= json_encode($user_id) ?>);
console.log('👔 Role:', <?= json_encode($user_role) ?>);
</script>
</body>
</html>