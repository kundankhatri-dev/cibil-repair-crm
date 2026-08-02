<?php
// ============================================================
// LOAN ASSISTANCE DASHBOARD
// Access: loan_team, admin
// Purpose: Help clients check loan eligibility and facilitate loan applications
// ============================================================
session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');

session_regenerate_id(true);

// Authentication
$allowed_roles = ['loan_team', 'admin', 'manager', 'credit_analyst'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: login.php');
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Loan Officer';
$user_role = $_SESSION['user_role'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrf ?>">
<title>Loan Assistance | CIBIL Repair</title>

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
    --text-primary: #111827;
    --text-secondary: #4b5563;
    --text-muted: #9ca3af;
    --border: rgba(0,0,0,0.08);
    --sidebar-bg: #0b2a23;
    --sidebar-text: rgba(255,255,255,0.75);
    --sidebar-active: #ffffff;
    --success: #059669;
    --success-bg: #ecfdf5;
    --warning: #d97706;
    --warning-bg: #fffbeb;
    --danger: #dc2626;
    --danger-bg: #fef2f2;
    --info: #2563eb;
    --info-bg: #eff6ff;
    --radius-lg: 16px;
    --radius-md: 10px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --transition: 0.2s ease;
    --sidebar-w: 260px;
    --topbar-h: 64px;
}

[data-theme="dark"] {
    --bg-base: #0f1117;
    --bg-surface: #1a1d27;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --border: rgba(255,255,255,0.07);
    --sidebar-bg: #080e0b;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    background: var(--bg-base);
    color: var(--text-primary);
    transition: background var(--transition);
}

/* Sidebar */
.sidebar {
    position: fixed; left: 0; top: 0; bottom: 0;
    width: var(--sidebar-w); background: var(--sidebar-bg);
    display: flex; flex-direction: column; z-index: 100;
    transition: transform var(--transition);
}
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar-brand { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.brand-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-weight: 800; color: white; font-size: 18px;
}
.brand-text { margin-top: 12px; color: white; font-weight: 700; font-size: 18px; }
.sidebar-nav { flex: 1; padding: 20px 0; overflow-y: auto; }
.nav-section {
    font-size: 11px; text-transform: uppercase;
    color: rgba(255,255,255,0.4); padding: 12px 20px 6px; letter-spacing: 1px;
}
.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 20px; margin: 2px 12px;
    border-radius: var(--radius-md); color: var(--sidebar-text);
    cursor: pointer; transition: all var(--transition);
}
.nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: var(--sidebar-active); }
.nav-item i { width: 20px; font-size: 16px; }

/* Main Content */
.main { margin-left: var(--sidebar-w); transition: margin var(--transition); min-height: 100vh; }
.main.full-width { margin-left: 0; }

/* Topbar */
.topbar {
    height: var(--topbar-h); background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; position: sticky; top: 0; z-index: 99;
}
.menu-toggle { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-secondary); display: none; }
.page-title { font-size: 20px; font-weight: 700; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.theme-toggle { display: flex; gap: 4px; background: var(--bg-base); border-radius: 99px; padding: 4px; }
.theme-btn {
    width: 32px; height: 32px; border-radius: 50%; border: none;
    background: transparent; cursor: pointer; color: var(--text-secondary);
}
.theme-btn.active { background: var(--brand); color: white; }
.logout-btn {
    padding: 8px 16px; border-radius: var(--radius-md);
    background: var(--danger-bg); color: var(--danger);
    border: none; font-weight: 600; cursor: pointer;
}

/* Content */
.content { padding: 24px; }

/* Stats Grid */
.stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px; margin-bottom: 24px;
}
.stat-card {
    background: var(--bg-surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 20px;
    transition: transform var(--transition);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-value { font-size: 32px; font-weight: 800; margin: 10px 0; }
.stat-label { color: var(--text-secondary); font-size: 13px; }

/* Cards */
.card {
    background: var(--bg-surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); margin-bottom: 24px; overflow: hidden;
}
.card-header {
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
}
.card-title { font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px; }
.card-body { padding: 20px; }

/* Tables */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
th { font-weight: 600; color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
tr:hover td { background: var(--bg-base); }

/* Badges */
.badge {
    display: inline-flex; align-items: center; padding: 4px 10px;
    border-radius: 99px; font-size: 11px; font-weight: 600;
}
.badge-success { background: var(--success-bg); color: var(--success); }
.badge-warning { background: var(--warning-bg); color: var(--warning); }
.badge-danger { background: var(--danger-bg); color: var(--danger); }
.badge-info { background: var(--info-bg); color: var(--info); }
.badge-secondary { background: var(--border); color: var(--text-secondary); }
.badge-brand { background: var(--brand-light); color: var(--brand-dark); }

/* Eligibility Cards */
.eligibility-card {
    background: var(--bg-surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 20px;
    text-align: center; transition: all var(--transition);
    cursor: pointer;
}
.eligibility-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--brand); }
.eligibility-icon { font-size: 32px; margin-bottom: 12px; display: block; }
.eligibility-title { font-weight: 700; margin-bottom: 8px; }
.eligibility-amount { font-size: 18px; font-weight: 800; color: var(--brand); margin-bottom: 4px; }
.eligibility-status { font-size: 11px; }
.eligibility-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }

/* Buttons */
.btn {
    padding: 8px 16px; border-radius: var(--radius-md);
    font-weight: 600; border: none; cursor: pointer;
    transition: all var(--transition); font-size: 13px;
}
.btn-primary { background: var(--brand); color: white; }
.btn-primary:hover { background: var(--brand-dark); }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-primary); }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.btn-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(5,150,105,0.2); }
.btn-success:hover { background: var(--success); color: white; }

/* Modal */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); display: none;
    align-items: center; justify-content: center; z-index: 1000;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--bg-surface); border-radius: var(--radius-lg);
    width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;
}
.modal-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 20px; }
.modal-footer { padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px; }

/* Form */
.form-group { margin-bottom: 16px; }
.form-label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; }
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 10px 12px;
    border: 1px solid var(--border); border-radius: var(--radius-md);
    background: var(--bg-surface); color: var(--text-primary); font-size: 13px;
}
.form-row { display: flex; gap: 12px; margin-bottom: 16px; }
.form-row .form-group { flex: 1; margin-bottom: 0; }

/* Filter Bar */
.filter-bar {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
}
.search-input {
    padding: 8px 12px; border: 1px solid var(--border);
    border-radius: var(--radius-md); min-width: 200px;
}

/* Calculator */
.calc-row { margin-bottom: 20px; }
.calc-label { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; }
input[type=range] { width: 100%; height: 4px; -webkit-appearance: none; background: var(--border); border-radius: 2px; }
input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 16px; height: 16px; background: var(--brand); border-radius: 50%; cursor: pointer; }

/* Toast */
.toast-container {
    position: fixed; bottom: 20px; right: 20px; z-index: 1100;
    display: flex; flex-direction: column; gap: 10px;
}
.toast {
    padding: 12px 16px; background: var(--bg-surface); border-radius: var(--radius-md);
    box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 12px;
    border-left: 3px solid var(--brand);
}

/* Spinner */
.spinner {
    width: 20px; height: 20px; border: 2px solid var(--border);
    border-top-color: var(--brand); border-radius: 50%;
    animation: spin 0.6s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }

/* Responsive */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.mobile-open { transform: translateX(0); }
    .main { margin-left: 0; }
    .menu-toggle { display: block; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .form-row { flex-direction: column; }
    .eligibility-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">LA</div>
        <div class="brand-text">Loan Assistance</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Overview</div>
        <div class="nav-item active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</div>
        <div class="nav-item" data-section="clients"><i class="fas fa-users"></i> Client Applications</div>
        <div class="nav-section">Loan Types</div>
        <div class="nav-item" data-section="homeloan"><i class="fas fa-home"></i> Home Loan</div>
        <div class="nav-item" data-section="personalloan"><i class="fas fa-user"></i> Personal Loan</div>
        <div class="nav-item" data-section="businessloan"><i class="fas fa-briefcase"></i> Business Loan</div>
        <div class="nav-item" data-section="loanagainstproperty"><i class="fas fa-building"></i> Loan Against Property</div>
        <div class="nav-item" data-section="creditcard"><i class="fas fa-credit-card"></i> Credit Card</div>
        <div class="nav-section">Tracking</div>
        <div class="nav-item" data-section="applications"><i class="fas fa-file-alt"></i> Applications</div>
        <div class="nav-item" data-section="approved"><i class="fas fa-check-circle"></i> Approved Loans</div>
        <div class="nav-section">Reports</div>
        <div class="nav-item" data-section="commission"><i class="fas fa-rupee-sign"></i> Commission</div>
        <div class="nav-item" data-section="analytics"><i class="fas fa-chart-bar"></i> Analytics</div>
    </nav>
</aside>

<div class="main" id="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="page-title" id="pageTitle">Loan Assistance</span>
        </div>
        <div class="topbar-right">
            <div class="theme-toggle">
                <button class="theme-btn active" id="lightBtn">☀️</button>
                <button class="theme-btn" id="darkBtn">🌙</button>
            </div>
            <span><?= h($user_name) ?></span>
            <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <div class="content">
        <!-- Dashboard Section -->
        <div class="section active" id="dashboardSection">
            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-file-alt" style="color: var(--brand); font-size: 24px;"></i><div class="stat-value" id="totalApplications">-</div><div class="stat-label">Total Applications</div></div>
                <div class="stat-card"><i class="fas fa-check-circle" style="color: var(--success); font-size: 24px;"></i><div class="stat-value" id="approvedLoans">-</div><div class="stat-label">Approved</div></div>
                <div class="stat-card"><i class="fas fa-rupee-sign" style="color: var(--info); font-size: 24px;"></i><div class="stat-value" id="totalSanctioned">-</div><div class="stat-label">Total Sanctioned (₹)</div></div>
                <div class="stat-card"><i class="fas fa-percent" style="color: var(--warning); font-size: 24px;"></i><div class="stat-value" id="approvalRate">-</div><div class="stat-label">Approval Rate</div></div>
            </div>

            <div class="eligibility-grid" id="eligibilityGrid"></div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Loan Applications by Type</div></div>
                <div class="card-body"><canvas id="loanTypeChart" height="200"></canvas></div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Recent Applications</div><button class="btn btn-primary btn-sm" onclick="openModal('addApplicationModal')"><i class="fas fa-plus"></i> New Application</button></div>
                <div class="table-wrap"> <table id="recentTable"><thead><tr><th>Client</th><th>Loan Type</th><th>Amount</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead><tbody id="recentBody"><tr><td colspan="6"><div class="spinner"></div> Loading...</td></tr></tbody></table> </div>
            </div>
        </div>

        <!-- Client Applications Section -->
        <div class="section" id="clientsSection">
            <div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Client Loan Applications</div><button class="btn btn-success btn-sm" onclick="exportApplications()"><i class="fas fa-file-excel"></i> Export</button></div>
            <div class="table-wrap"><table id="clientsTable"><thead><tr><th>Client</th><th>Loan Type</th><th>Amount</th><th>CIBIL</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead><tbody id="clientsBody"></tbody></table></div></div>
        </div>

        <!-- Home Loan Section -->
        <div class="section" id="homeloanSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-home"></i> Home Loan Applications</div><button class="btn btn-primary btn-sm" onclick="openModal('addHomeLoanModal')"><i class="fas fa-plus"></i> New Home Loan</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Property Value</th><th>Loan Amount</th><th>Tenure</th><th>Status</th><th>Actions</th></tr></thead><tbody id="homeLoanBody"></tbody></table></div></div></div>

        <!-- Personal Loan Section -->
        <div class="section" id="personalloanSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Personal Loan Applications</div><button class="btn btn-primary btn-sm" onclick="openModal('addPersonalLoanModal')"><i class="fas fa-plus"></i> New Personal Loan</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Income</th><th>Loan Amount</th><th>Tenure</th><th>Status</th><th>Actions</th></tr></thead><tbody id="personalLoanBody"></tbody></table></div></div></div>

        <!-- Business Loan Section -->
        <div class="section" id="businessloanSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-briefcase"></i> Business Loan Applications</div><button class="btn btn-primary btn-sm" onclick="openModal('addBusinessLoanModal')"><i class="fas fa-plus"></i> New Business Loan</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Business Vintage</th><th>Turnover</th><th>Loan Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody id="businessLoanBody"></tbody></table></div></div></div>

        <!-- Loan Against Property Section -->
        <div class="section" id="loanagainstpropertySection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-building"></i> Loan Against Property</div><button class="btn btn-primary btn-sm" onclick="openModal('addLAPModal')"><i class="fas fa-plus"></i> New LAP Application</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Property Value</th><th>Loan Amount</th><th>LTV</th><th>Status</th><th>Actions</th></tr></thead><tbody id="lapBody"></tbody></table></div></div></div>

        <!-- Credit Card Section -->
        <div class="section" id="creditcardSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-credit-card"></i> Credit Card Applications</div><button class="btn btn-primary btn-sm" onclick="openModal('addCardModal')"><i class="fas fa-plus"></i> New Card Application</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Card Type</th><th>Limit</th><th>Bank</th><th>Status</th><th>Actions</th></tr></thead><tbody id="cardBody"></tbody></table></div></div></div>

        <!-- Applications Section -->
        <div class="section" id="applicationsSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-file-alt"></i> All Applications</div><div class="filter-bar"><input type="text" class="search-input" id="appSearch" placeholder="Search..."><select id="appStatusFilter" class="form-select" style="width:130px;"><option value="">All Status</option><option>pending</option><option>processing</option><option>approved</option><option>rejected</option></select></div></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Client</th><th>Loan Type</th><th>Amount</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead><tbody id="applicationsBody"></tbody></table></div></div></div>

        <!-- Approved Loans Section -->
        <div class="section" id="approvedSection"><div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-check-circle"></i> Approved Loans</div><button class="btn btn-success btn-sm" onclick="exportApproved()"><i class="fas fa-file-excel"></i> Export</button></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Loan Type</th><th>Sanctioned Amount</th><th>Bank</th><th>Approved Date</th><th>Commission</th></tr></thead><tbody id="approvedBody"></tbody></table></div></div></div>

        <!-- Commission Section -->
        <div class="section" id="commissionSection"><div class="stats-grid"><div class="stat-card"><div class="stat-value" id="totalCommission">-</div><div class="stat-label">Total Commission Earned</div></div><div class="stat-card"><div class="stat-value" id="pendingCommission">-</div><div class="stat-label">Pending</div></div><div class="stat-card"><div class="stat-value" id="paidCommission">-</div><div class="stat-label">Paid Out</div></div></div>
        <div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Commission History</div></div><div class="table-wrap"><table><thead><tr><th>Loan ID</th><th>Client</th><th>Loan Amount</th><th>Commission</th><th>Status</th><th>Paid Date</th></tr></thead><tbody id="commissionBody"></tbody></table></div></div></div>

        <!-- Analytics Section -->
        <div class="section" id="analyticsSection">
            <div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Monthly Applications</div></div><div class="card-body"><canvas id="monthlyChart" height="250"></canvas></div></div>
            <div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Loan Type Distribution</div></div><div class="card-body"><canvas id="distributionChart" height="250"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Add Application Modal -->
<div class="modal-overlay" id="addApplicationModal"><div class="modal"><div class="modal-header"><span>New Loan Application</span><button class="modal-close" onclick="closeModal('addApplicationModal')">✕</button></div>
<div class="modal-body"><div class="form-group"><label class="form-label">Client</label><select class="form-select" id="appClient"></select></div>
<div class="form-group"><label class="form-label">Loan Type</label><select class="form-select" id="appType"><option>Home Loan</option><option>Personal Loan</option><option>Business Loan</option><option>Loan Against Property</option><option>Credit Card</option></select></div>
<div class="form-row"><div class="form-group"><label class="form-label">Amount (₹)</label><input class="form-input" id="appAmount" type="number"></div><div class="form-group"><label class="form-label">Tenure (Years)</label><input class="form-input" id="appTenure" type="number"></div></div>
<div class="form-group"><label class="form-label">Bank</label><input class="form-input" id="appBank" placeholder="Bank name"></div></div>
<div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('addApplicationModal')">Cancel</button><button class="btn btn-primary" onclick="addApplication()">Submit Application</button></div></div></div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="updateStatusModal"><div class="modal"><div class="modal-header"><span>Update Application Status</span><button class="modal-close" onclick="closeModal('updateStatusModal')">✕</button></div>
<div class="modal-body"><input type="hidden" id="updateAppId"><div class="form-group"><label class="form-label">Status</label><select class="form-select" id="updateStatus"><option>pending</option><option>processing</option><option>approved</option><option>rejected</option></select></div>
<div class="form-group"><label class="form-label">Sanctioned Amount (₹)</label><input class="form-input" id="sanctionedAmount" type="number"></div>
<div class="form-group"><label class="form-label">Bank / Partner</label><input class="form-input" id="updateBank"></div>
<div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="updateNotes" rows="2"></textarea></div></div>
<div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button><button class="btn btn-primary" onclick="updateApplicationStatus()">Update</button></div></div></div>

<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================================
// LOAN ASSISTANCE DASHBOARD
// ============================================================

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let charts = {};

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('loanTheme', theme);
    document.getElementById('lightBtn').classList.toggle('active', theme === 'light');
    document.getElementById('darkBtn').classList.toggle('active', theme === 'dark');
    setTimeout(() => { Object.values(charts).forEach(c => { if (c) c.update(); }); }, 100);
}
(function() { setTheme(localStorage.getItem('loanTheme') || 'light'); })();
document.getElementById('lightBtn').onclick = () => setTheme('light');
document.getElementById('darkBtn').onclick = () => setTheme('dark');

document.getElementById('menuToggle').onclick = () => document.getElementById('sidebar').classList.toggle('mobile-open');

document.querySelectorAll('.nav-item').forEach(item => {
    item.onclick = () => {
        const section = item.dataset.section;
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        item.classList.add('active');
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(section + 'Section').classList.add('active');
        document.getElementById('pageTitle').textContent = item.querySelector('span').textContent;
        if (section === 'dashboard') loadDashboard();
        if (section === 'clients') loadClients();
        if (section === 'homeloan') loadHomeLoans();
        if (section === 'personalloan') loadPersonalLoans();
        if (section === 'businessloan') loadBusinessLoans();
        if (section === 'loanagainstproperty') loadLAP();
        if (section === 'creditcard') loadCards();
        if (section === 'applications') loadApplications();
        if (section === 'approved') loadApproved();
        if (section === 'commission') loadCommission();
        if (section === 'analytics') loadAnalytics();
        if (window.innerWidth < 768) document.getElementById('sidebar').classList.remove('mobile-open');
    };
});

function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'}" style="color:${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#0d9e78'}"></i> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => { m.onclick = (e) => { if (e.target === m) m.classList.remove('open'); }; });

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, credentials: 'include' };
    if (data) options.body = JSON.stringify(data);
    const response = await fetch('api/loan/' + endpoint, options);
    return response.json();
}

// Load Dashboard
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats.php');
    if (data.success) {
        document.getElementById('totalApplications').textContent = data.total_applications || 0;
        document.getElementById('approvedLoans').textContent = data.approved_loans || 0;
        document.getElementById('totalSanctioned').textContent = '₹' + (data.total_sanctioned || 0).toLocaleString();
        document.getElementById('approvalRate').textContent = data.approval_rate + '%';
        if (data.eligibility) document.getElementById('eligibilityGrid').innerHTML = data.eligibility;
        if (data.loan_type_data) {
            const ctx = document.getElementById('loanTypeChart').getContext('2d');
            if (charts.loanType) charts.loanType.destroy();
            charts.loanType = new Chart(ctx, { type: 'bar', data: { labels: data.loan_type_data.labels, datasets: [{ label: 'Applications', data: data.loan_type_data.values, backgroundColor: '#0d9e78' }] }, options: { responsive: true } });
        }
        if (data.recent_applications) document.getElementById('recentBody').innerHTML = data.recent_applications.map(a => `<tr><td><strong>${escapeHtml(a.client_name)}</strong></td><td>${a.loan_type}</td><td>₹${Number(a.amount).toLocaleString()}</td><td>${getStatusBadge(a.status)}</span></td><td>${new Date(a.created_at).toLocaleDateString()}</td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${a.id}, '${a.status}')"><i class="fas fa-edit"></i></button></td></tr>`).join('');
    }
}

function getStatusBadge(status) {
    const map = { pending: 'badge-warning', processing: 'badge-info', approved: 'badge-success', rejected: 'badge-danger' };
    return `<span class="badge ${map[status] || 'badge-secondary'}">${status}</span>`;
}

async function loadClients() { const data = await apiCall('get_applications.php'); if (data.applications) document.getElementById('clientsBody').innerHTML = data.applications.map(a => `<tr><td><strong>${escapeHtml(a.client_name)}</strong></td><td>${a.loan_type}</td><td>₹${Number(a.amount).toLocaleString()}</td><td>${a.cibil_score || '-'}</td><td>${getStatusBadge(a.status)}</span></td><td>${new Date(a.created_at).toLocaleDateString()}</td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${a.id})"><i class="fas fa-edit"></i></button></td></table>`).join(''); }
async function loadHomeLoans() { const data = await apiCall('get_home_loans.php'); if (data.loans) document.getElementById('homeLoanBody').innerHTML = data.loans.map(l => `<tr><td><strong>${escapeHtml(l.client_name)}</strong></td><td>₹${Number(l.property_value).toLocaleString()}</td><td>₹${Number(l.amount).toLocaleString()}</td><td>${l.tenure} yrs</span></td><td>${getStatusBadge(l.status)}</span></td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${l.id})"><i class="fas fa-edit"></i></button></td></tr>`).join(''); }
async function loadPersonalLoans() { const data = await apiCall('get_personal_loans.php'); if (data.loans) document.getElementById('personalLoanBody').innerHTML = data.loans.map(l => `<tr><td><strong>${escapeHtml(l.client_name)}</strong></td><td>₹${Number(l.income).toLocaleString()}</td><td>₹${Number(l.amount).toLocaleString()}</td><td>${l.tenure} yrs</span></td><td>${getStatusBadge(l.status)}</span></td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${l.id})"><i class="fas fa-edit"></i></button></td></tr>`).join(''); }
async function loadBusinessLoans() { const data = await apiCall('get_business_loans.php'); if (data.loans) document.getElementById('businessLoanBody').innerHTML = data.loans.map(l => `<td><td><strong>${escapeHtml(l.client_name)}</strong></td><td>${l.business_vintage} yrs</span></td><td>₹${Number(l.turnover).toLocaleString()}</td><td>₹${Number(l.amount).toLocaleString()}</td><td>${getStatusBadge(l.status)}</span></td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${l.id})"><i class="fas fa-edit"></i></button></td></tr>`).join(''); }
async function loadLAP() { const data = await apiCall('get_lap_loans.php'); if (data.loans) document.getElementById('lapBody').innerHTML = data.loans.map(l => `<tr><td><strong>${escapeHtml(l.client_name)}</strong></td><td>₹${Number(l.property_value).toLocaleString()}</td><td>₹${Number(l.amount).toLocaleString()}</td><td>${l.ltv}%</span></td><td>${getStatusBadge(l.status)}</span></td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${l.id})"><i class="fas fa-edit"></i></button></tr></tr>`).join(''); }
async function loadCards() { const data = await apiCall('get_credit_cards.php'); if (data.cards) document.getElementById('cardBody').innerHTML = data.cards.map(c => `<tr><td><strong>${escapeHtml(c.client_name)}</strong></td><td>${c.card_type}</td><td>₹${Number(c.limit).toLocaleString()}</td><td>${c.bank}</td><td>${getStatusBadge(c.status)}</span></td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${c.id})"><i class="fas fa-edit"></i></button></td></tr>`).join(''); }
async function loadApplications() { const data = await apiCall('get_all_applications.php'); if (data.applications) document.getElementById('applicationsBody').innerHTML = data.applications.map(a => `<td><td>#L${a.id}</td><td><strong>${escapeHtml(a.client_name)}</strong></td><td>${a.loan_type}</td><td>₹${Number(a.amount).toLocaleString()}</td><td>${getStatusBadge(a.status)}</span></td><td>${new Date(a.created_at).toLocaleDateString()}</td><td><button class="btn btn-outline btn-sm" onclick="openUpdateModal(${a.id})"><i class="fas fa-edit"></i></button></td></tr>`).join(''); }
async function loadApproved() { const data = await apiCall('get_approved_loans.php'); if (data.loans) document.getElementById('approvedBody').innerHTML = data.loans.map(l => `<td><td><strong>${escapeHtml(l.client_name)}</strong></td><td>${l.loan_type}</td><td>₹${Number(l.sanctioned_amount).toLocaleString()}</td><td>${l.bank}</td><td>${new Date(l.approved_date).toLocaleDateString()}</td><td>₹${Number(l.commission).toLocaleString()}</td></tr>`).join(''); }
async function loadCommission() { const data = await apiCall('get_commission.php'); if (data.success) { document.getElementById('totalCommission').textContent = '₹' + (data.total || 0).toLocaleString(); document.getElementById('pendingCommission').textContent = '₹' + (data.pending || 0).toLocaleString(); document.getElementById('paidCommission').textContent = '₹' + (data.paid || 0).toLocaleString(); } if (data.commissions) document.getElementById('commissionBody').innerHTML = data.commissions.map(c => `<tr><td>#L${c.loan_id}</td><td>${escapeHtml(c.client_name)}</td><td>₹${Number(c.loan_amount).toLocaleString()}</td><td>₹${Number(c.commission).toLocaleString()}</td><td>${getStatusBadge(c.status)}</span></td><td>${c.paid_date || '-'}</td></tr>`).join(''); }
async function loadAnalytics() { const data = await apiCall('get_analytics.php'); if (data.monthly_data) { const ctx = document.getElementById('monthlyChart').getContext('2d'); if (charts.monthly) charts.monthly.destroy(); charts.monthly = new Chart(ctx, { type: 'line', data: { labels: data.monthly_data.labels, datasets: [{ label: 'Applications', data: data.monthly_data.values, borderColor: '#0d9e78', fill: true }] }, options: { responsive: true } }); } if (data.distribution) { const ctx2 = document.getElementById('distributionChart').getContext('2d'); if (charts.dist) charts.dist.destroy(); charts.dist = new Chart(ctx2, { type: 'doughnut', data: { labels: data.distribution.labels, datasets: [{ data: data.distribution.values, backgroundColor: ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6', '#ec489a'] }] }, options: { responsive: true } }); } }

async function addApplication() {
    const client_id = document.getElementById('appClient').value;
    const loan_type = document.getElementById('appType').value;
    const amount = document.getElementById('appAmount').value;
    const tenure = document.getElementById('appTenure').value;
    const bank = document.getElementById('appBank').value;
    if (!client_id || !amount) { showToast('Client and amount required', 'error'); return; }
    const result = await apiCall('add_application.php', 'POST', { client_id, loan_type, amount, tenure, bank });
    if (result.success) { showToast('Application submitted!', 'success'); closeModal('addApplicationModal'); loadDashboard(); loadClients(); }
    else showToast(result.error || 'Failed', 'error');
}

function openUpdateModal(id, status) { document.getElementById('updateAppId').value = id; document.getElementById('updateStatus').value = status; openModal('updateStatusModal'); }
async function updateApplicationStatus() {
    const id = document.getElementById('updateAppId').value;
    const status = document.getElementById('updateStatus').value;
    const sanctioned_amount = document.getElementById('sanctionedAmount').value;
    const bank = document.getElementById('updateBank').value;
    const notes = document.getElementById('updateNotes').value;
    const result = await apiCall('update_application_status.php', 'POST', { id, status, sanctioned_amount, bank, notes });
    if (result.success) { showToast('Status updated!', 'success'); closeModal('updateStatusModal'); loadDashboard(); loadClients(); loadApproved(); }
    else showToast(result.error || 'Failed', 'error');
}

function exportApplications() { showToast('Exporting applications...', 'info'); }
function exportApproved() { showToast('Exporting approved loans...', 'info'); }
function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m])); }

document.getElementById('logoutBtn').onclick = () => { if (confirm('Logout?')) window.location.href = 'logout.php'; };
loadDashboard();
</script>
</body>
</html>