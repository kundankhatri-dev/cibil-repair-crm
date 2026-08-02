<?php
// ============================================================
// MASTER DASHBOARD HEADER - Include at the top of EVERY dashboard
// ============================================================

session_start();

// Database configuration
require_once __DIR__ . '/../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /login.html');
    exit;
}

// Session timeout check (1 hour)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_destroy();
    header('Location: /login.html?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'guest';

// Check if user has access to this dashboard
$current_file = basename($_SERVER['PHP_SELF']);
$allowed_dashboards = [
    'super_admin' => [
        'admin-dashboard.php', 'ceo-dashboard.php', 'management-dashboard.php',
        'finance-dashboard.php', 'hr-dashboard.php', 'lead-management-dashboard.php',
        'credit-analyst-dashboard.php', 'dispute-processing-dashboard.php',
        'customer-support-dashboard.php', 'operations-dashboard.php',
        'legal-compliance-dashboard.php', 'partner-dashboard.php',
        'client-dashboard.php', 'employee-dashboard.php', 'training-dashboard.php',
        'document-dashboard.php', 'qa-dashboard.php', 'it-dashboard.php',
        'risk-dashboard.php', 'project-dashboard.php', 'marketing-dashboard.php'
    ],
    'admin' => [
        'admin-dashboard.php', 'finance-dashboard.php', 'hr-dashboard.php',
        'lead-management-dashboard.php', 'customer-support-dashboard.php',
        'operations-dashboard.php', 'legal-compliance-dashboard.php'
    ],
    'ceo' => ['ceo-dashboard.php', 'finance-dashboard.php', 'management-dashboard.php'],
    'hr_manager' => ['hr-dashboard.php', 'training-dashboard.php', 'employee-dashboard.php'],
    'finance_manager' => ['finance-dashboard.php'],
    'sales_manager' => ['lead-management-dashboard.php', 'marketing-dashboard.php'],
    'support_manager' => ['customer-support-dashboard.php', 'operations-dashboard.php'],
    'credit_analyst' => ['credit-analyst-dashboard.php', 'dispute-processing-dashboard.php'],
    'client' => ['client-dashboard.php'],
    'partner' => ['partner-dashboard.php'],
    'employee' => ['employee-dashboard.php']
];

$user_allowed = $allowed_dashboards[$user_role] ?? [];
if (!in_array($current_file, $user_allowed) && $user_role !== 'super_admin') {
    // Redirect to default dashboard for this role
    $default_dashboards = [
        'super_admin' => 'admin-dashboard.php',
        'admin' => 'admin-dashboard.php',
        'ceo' => 'ceo-dashboard.php',
        'hr_manager' => 'hr-dashboard.php',
        'finance_manager' => 'finance-dashboard.php',
        'sales_manager' => 'lead-management-dashboard.php',
        'support_manager' => 'customer-support-dashboard.php',
        'credit_analyst' => 'credit-analyst-dashboard.php',
        'client' => 'client-dashboard.php',
        'partner' => 'partner-dashboard.php',
        'employee' => 'employee-dashboard.php'
    ];
    $default = $default_dashboards[$user_role] ?? 'login.html';
    header("Location: $default");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Helper functions
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function hasPermission($permission) {
    // Implement permission check based on user role
    $permissions = [
        'super_admin' => ['*'],
        'admin' => ['view_dashboard', 'manage_users', 'view_reports'],
        'hr_manager' => ['view_hr', 'manage_employees'],
        'finance_manager' => ['view_finance', 'manage_invoices']
    ];
    $user_perms = $permissions[$_SESSION['user_role']] ?? [];
    return in_array('*', $user_perms) || in_array($permission, $user_perms);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <title><?= $title ?? 'Dashboard' ?> | CIBIL Repair CRM</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
            overflow-x: hidden;
        }
        
        /* Main content area - offset for fixed navbar */
        .dashboard-wrapper {
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        
        /* Page container */
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        
        /* Page header */
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }
        
        .page-description {
            color: #6b7280;
            font-size: 14px;
            margin-top: 4px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 13px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover td {
            background: #f9fafb;
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success { background: #ecfdf5; color: #059669; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-danger { background: #fef2f2; color: #dc2626; }
        .badge-info { background: #eff6ff; color: #2563eb; }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
        }
        
        .btn-primary {
            background: #0d9e78;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0a7d60;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .btn-outline:hover {
            background: #f9fafb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-container {
                padding: 16px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .stat-value {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #0d9e78;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 40px auto;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toast notifications */
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
            padding: 12px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<!-- Include the navigation bar -->
<?php include __DIR__ . '/navbar.php'; ?>

<!-- Main content wrapper -->
<div class="dashboard-wrapper">
    <div class="page-container">