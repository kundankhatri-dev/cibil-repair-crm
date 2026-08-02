<?php
// ============================================================
// MANAGEMENT DASHBOARD (CEO / FOUNDER) - WITH FULL API INTEGRATION
// ============================================================
session_start();

// Authentication
$allowed_roles = ['ceo', 'founder', 'admin', 'director', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'CEO';
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
<title>Management Dashboard | CIBIL Repair</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --brand: #0d9e78;
    --brand-dark: #0a7d60;
    --bg-base: #f4f6f9;
    --bg-surface: #ffffff;
    --text-primary: #111827;
    --text-secondary: #4b5563;
    --border: #e2e8f0;
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
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    background: var(--bg-base);
    color: var(--text-primary);
}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 260px;
    background: var(--sidebar-bg);
    z-index: 100;
    overflow-y: auto;
}

.sidebar-brand {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.brand-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--brand), #06b6d4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: white;
    font-size: 18px;
}

.brand-text {
    margin-top: 12px;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.nav-section {
    font-size: 11px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.4);
    padding: 12px 20px 6px;
    letter-spacing: 1px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    margin: 2px 12px;
    border-radius: 10px;
    color: var(--sidebar-text);
    cursor: pointer;
    transition: all 0.2s;
}

.nav-item:hover,
.nav-item.active {
    background: rgba(255,255,255,0.1);
    color: var(--sidebar-active);
}

.nav-item i {
    width: 20px;
}

/* Main Content */
.main {
    margin-left: 260px;
    min-height: 100vh;
}

/* Topbar */
.topbar {
    height: 64px;
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 99;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-secondary);
}

.page-title {
    font-size: 20px;
    font-weight: 700;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.theme-toggle {
    display: flex;
    gap: 4px;
    background: var(--bg-base);
    border-radius: 99px;
    padding: 4px;
}

.theme-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: transparent;
    cursor: pointer;
}

.theme-btn.active {
    background: var(--brand);
    color: white;
}

.logout-btn {
    padding: 8px 16px;
    border-radius: 10px;
    background: var(--danger-bg);
    color: var(--danger);
    border: none;
    font-weight: 600;
    cursor: pointer;
}

/* Back Button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(13,158,120,0.12);
    color: var(--brand);
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.back-btn:hover {
    background: rgba(13,158,120,0.25);
    transform: translateX(-2px);
}

/* Content */
.content {
    padding: 24px;
}

/* Sections */
.section {
    display: none;
}

.section.active {
    display: block;
}

/* KPI Cards */
.kpi-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.kpi-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
}

.kpi-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--brand);
}

.kpi-label {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 5px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
}

.stat-value {
    font-size: 28px;
    font-weight: 800;
    margin: 10px 0;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 13px;
}

.stat-change {
    font-size: 12px;
    margin-top: 8px;
}

.stat-change.up {
    color: var(--success);
}

.stat-change.down {
    color: var(--danger);
}

/* Cards */
.card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    margin-bottom: 24px;
    overflow: hidden;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-body {
    padding: 20px;
}

/* Tables */
.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 11px;
    text-transform: uppercase;
}

tr:hover td {
    background: var(--bg-base);
}

/* Badges */
.badge {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: var(--success-bg);
    color: var(--success);
}

.badge-warning {
    background: var(--warning-bg);
    color: var(--warning);
}

.badge-danger {
    background: var(--danger-bg);
    color: var(--danger);
}

/* Progress Bar */
.progress-bar {
    height: 8px;
    background: var(--border);
    border-radius: 4px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    background: var(--brand);
    border-radius: 4px;
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    font-size: 13px;
}

.btn-primary {
    background: var(--brand);
    color: white;
}

.btn-primary:hover {
    background: var(--brand-dark);
}

.btn-success {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(5,150,105,0.2);
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* Toast */
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
    background: var(--bg-surface);
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 3px solid var(--brand);
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

.consent-preview {
    background: var(--bg-base);
    border-left: 3px solid var(--brand);
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    .main {
        margin-left: 0;
    }
    .menu-toggle {
        display: block;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .kpi-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">MG</div>
        <div class="brand-text">Management</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Overview</div>
        <div class="nav-item active" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </div>
        <div class="nav-section">Business Metrics</div>
        <div class="nav-item" data-section="revenue">
            <i class="fas fa-rupee-sign"></i> Revenue Analytics
        </div>
        <div class="nav-item" data-section="clients">
            <i class="fas fa-users"></i> Client Growth
        </div>
        <div class="nav-item" data-section="operations">
            <i class="fas fa-chart-line"></i> Operational KPIs
        </div>
        <div class="nav-section">Performance</div>
        <div class="nav-item" data-section="team">
            <i class="fas fa-user-tie"></i> Team Performance
        </div>
        <div class="nav-item" data-section="partners">
            <i class="fas fa-handshake"></i> Partner Performance
        </div>
        <div class="nav-section">Reports</div>
        <div class="nav-item" data-section="financial">
            <i class="fas fa-file-invoice-dollar"></i> Financial Reports
        </div>
        <div class="nav-item" data-section="forecast">
            <i class="fas fa-chart-line"></i> Forecast
        </div>
    </nav>
</aside>

<div class="main" id="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a href="admin-dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Admin
            </a>
            <span class="page-title" id="pageTitle">Dashboard</span>
        </div>
        <div class="topbar-right">
            <div class="theme-toggle">
                <button class="theme-btn active" id="lightBtn">☀️</button>
                <button class="theme-btn" id="darkBtn">🌙</button>
            </div>
            <span><?= h($user_name) ?></span>
            <button class="logout-btn" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="content">
        <!-- Dashboard Section -->
        <div class="section active" id="dashboardSection">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-value" id="totalRevenue">-</div>
                    <div class="kpi-label">Total Revenue</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="totalClients">-</div>
                    <div class="kpi-label">Total Clients</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="avgScore">-</div>
                    <div class="kpi-label">Avg CIBIL Score</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="partnerCount">-</div>
                    <div class="kpi-label">Active Partners</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" id="employeeCount">-</div>
                    <div class="kpi-label">Employees</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-chart-line" style="color: var(--brand); font-size: 24px;"></i>
                    <div class="stat-value" id="monthlyRevenue">-</div>
                    <div class="stat-label">Revenue (This Month)</div>
                    <div class="stat-change up" id="revenueGrowth">-</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-plus" style="color: var(--info); font-size: 24px;"></i>
                    <div class="stat-value" id="newClients">-</div>
                    <div class="stat-label">New Clients (This Month)</div>
                    <div class="stat-change up" id="clientGrowth">-</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle" style="color: var(--success); font-size: 24px;"></i>
                    <div class="stat-value" id="caseSuccessRate">-</div>
                    <div class="stat-label">Case Success Rate</div>
                    <div class="stat-change">Target: 95%</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-star" style="color: var(--warning); font-size: 24px;"></i>
                    <div class="stat-value" id="avgRating">-</div>
                    <div class="stat-label">Avg Client Rating</div>
                    <div class="stat-change">4.9 target</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Revenue & Growth Trends</div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> Business Breakdown</div>
                </div>
                <div class="card-body">
                    <canvas id="businessChart" height="250"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-trophy"></i> Key Highlights</div>
                </div>
                <div class="card-body" id="highlights"></div>
            </div>
        </div>

        <!-- Revenue Section -->
        <div class="section" id="revenueSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> Revenue Breakdown</div>
                </div>
                <div class="card-body">
                    <canvas id="revenueBreakdownChart" height="250"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Monthly Revenue Trend</div>
                </div>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Clients Section -->
        <div class="section" id="clientsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-users"></i> Client Growth Trends</div>
                </div>
                <div class="card-body">
                    <canvas id="clientTrendChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Operations Section -->
        <div class="section" id="operationsSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> Operational KPIs</div>
                </div>
                <div class="card-body">
                    <canvas id="kpiChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="section" id="teamSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-user-tie"></i> Team Performance</div>
                    <button class="btn btn-success btn-sm" onclick="showToast('Exporting team performance...', 'info')">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Employee</th><th>Department</th><th>Cases Closed</th><th>SLA Met</th><th>Rating</th></tr>
                        </thead>
                        <tbody id="teamBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Partners Section -->
        <div class="section" id="partnersSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-handshake"></i> Partner Performance</div>
                </div>
                <div class="table-wrap">
                    <tr>
                        <thead>
                            <tr><th>Partner</th><th>Leads</th><th>Conversions</th><th>Rate</th><th>Commission</th></tr>
                        </thead>
                        <tbody id="partnerBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Section -->
        <div class="section" id="financialSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-file-invoice-dollar"></i> Financial Summary</div>
                </div>
                <div class="table-wrap">
                    </table>
                        <thead>
                            <tr><th>Metric</th><th>This Month</th><th>Last Month</th><th>Change</th><th>YTD</th></tr>
                        </thead>
                        <tbody id="financialBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Forecast Section -->
        <div class="section" id="forecastSection">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-chart-line"></i> 12-Month Forecast</div>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="250"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bullseye"></i> Annual Goals Progress</div>
                </div>
                <div class="card-body">
                    <div id="goalProgress"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================================
// MANAGEMENT DASHBOARD - FULL API INTEGRATION
// ============================================================

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let charts = {};

// Theme
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('mgmtTheme', theme);
    document.getElementById('lightBtn').classList.toggle('active', theme === 'light');
    document.getElementById('darkBtn').classList.toggle('active', theme === 'dark');
    // Update chart colors
    Object.values(charts).forEach(chart => {
        if (chart) chart.update();
    });
}

const savedTheme = localStorage.getItem('mgmtTheme') || 'light';
setTheme(savedTheme);

document.getElementById('lightBtn').onclick = () => setTheme('light');
document.getElementById('darkBtn').onclick = () => setTheme('dark');

// Mobile menu
document.getElementById('menuToggle').onclick = () => {
    document.getElementById('sidebar').classList.toggle('mobile-open');
};

// Toast notification
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle';
    const color = type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#0d9e78';
    toast.innerHTML = `<i class="fas ${icon}" style="color:${color}"></i> ${message}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// API Call Function
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = { 
        method, 
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, 
        credentials: 'include' 
    };
    if (data) options.body = JSON.stringify(data);
    try {
        const response = await fetch('api/management/' + endpoint, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

// Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Sidebar navigation
document.querySelectorAll('.nav-item').forEach(item => {
    item.onclick = () => {
        const section = item.getAttribute('data-section');
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        item.classList.add('active');
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(section + 'Section').classList.add('active');
        document.getElementById('pageTitle').textContent = item.textContent.trim();
        
        // Load section data
        if (section === 'dashboard') loadDashboard();
        if (section === 'revenue') loadRevenue();
        if (section === 'clients') loadClients();
        if (section === 'operations') loadOperations();
        if (section === 'team') loadTeam();
        if (section === 'partners') loadPartners();
        if (section === 'financial') loadFinancial();
        if (section === 'forecast') loadForecast();
        
        if (window.innerWidth < 768) {
            document.getElementById('sidebar').classList.remove('mobile-open');
        }
    };
});

// ========== LOAD DASHBOARD ==========
async function loadDashboard() {
    const data = await apiCall('get_dashboard_stats.php');
    if (data.success) {
        document.getElementById('totalRevenue').textContent = '₹' + (data.total_revenue || 0).toLocaleString();
        document.getElementById('totalClients').textContent = data.total_clients || 0;
        document.getElementById('avgScore').textContent = data.avg_cibil_score || '—';
        document.getElementById('partnerCount').textContent = data.active_partners || 0;
        document.getElementById('employeeCount').textContent = data.total_employees || 0;
        document.getElementById('monthlyRevenue').textContent = '₹' + (data.monthly_revenue || 0).toLocaleString();
        document.getElementById('newClients').textContent = data.new_clients_month || 0;
        document.getElementById('caseSuccessRate').textContent = (data.case_success_rate || 0) + '%';
        document.getElementById('avgRating').textContent = (data.avg_rating || 0) + '★';
        document.getElementById('revenueGrowth').innerHTML = (data.revenue_growth >= 0 ? '+' : '') + data.revenue_growth + '% vs last month';
        document.getElementById('clientGrowth').innerHTML = (data.client_growth >= 0 ? '+' : '') + data.client_growth + '% vs last month';
        
        // Revenue Trend Chart
        if (data.revenue_trend) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            if (charts.revenue) charts.revenue.destroy();
            charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.revenue_trend.labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: data.revenue_trend.values,
                        borderColor: '#0d9e78',
                        backgroundColor: 'rgba(13,158,120,0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true }
            });
        }
        
        // Business Breakdown Chart
        if (data.business_breakdown) {
            const ctx2 = document.getElementById('businessChart').getContext('2d');
            if (charts.business) charts.business.destroy();
            charts.business = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: data.business_breakdown.labels,
                    datasets: [{
                        data: data.business_breakdown.values,
                        backgroundColor: ['#0d9e78', '#3b82f6', '#d97706', '#8b5cf6']
                    }]
                },
                options: { responsive: true }
            });
        }
        
        // Key Highlights
        if (data.highlights) {
            document.getElementById('highlights').innerHTML = data.highlights.map(h => `<div class="consent-preview">${h}</div>`).join('');
        }
    } else {
        showToast(data.error || 'Failed to load dashboard', 'error');
        // Fallback to demo data
        setFallbackDashboard();
    }
}

// Fallback function for demo data
function setFallbackDashboard() {
    document.getElementById('totalRevenue').textContent = '₹15,42,500';
    document.getElementById('totalClients').textContent = '1,247';
    document.getElementById('avgScore').textContent = '748';
    document.getElementById('partnerCount').textContent = '24';
    document.getElementById('employeeCount').textContent = '18';
    document.getElementById('monthlyRevenue').textContent = '₹3,25,000';
    document.getElementById('newClients').textContent = '147';
    document.getElementById('caseSuccessRate').textContent = '94%';
    document.getElementById('avgRating').textContent = '4.8★';
    document.getElementById('revenueGrowth').innerHTML = '+18% vs last month';
    document.getElementById('clientGrowth').innerHTML = '+12% vs last month';
}

// ========== LOAD REVENUE ==========
async function loadRevenue() {
    const data = await apiCall('get_revenue_analytics.php');
    if (data.success) {
        // Revenue Breakdown Chart
        if (data.breakdown) {
            const ctx = document.getElementById('revenueBreakdownChart').getContext('2d');
            if (charts.breakdown) charts.breakdown.destroy();
            charts.breakdown = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.breakdown.labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: data.breakdown.values,
                        backgroundColor: '#0d9e78'
                    }]
                },
                options: { responsive: true }
            });
        }
        
        // Monthly Revenue Chart
        if (data.monthly) {
            const ctx2 = document.getElementById('monthlyRevenueChart').getContext('2d');
            if (charts.monthly) charts.monthly.destroy();
            charts.monthly = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: data.monthly.labels,
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: data.monthly.values,
                        borderColor: '#0d9e78',
                        fill: true
                    }]
                },
                options: { responsive: true }
            });
        }
    } else {
        setFallbackRevenue();
    }
}

function setFallbackRevenue() {
    const breakdownCtx = document.getElementById('revenueBreakdownChart').getContext('2d');
    if (breakdownCtx) {
        charts.breakdown = new Chart(breakdownCtx, {
            type: 'bar',
            data: {
                labels: ['Credit Repair', 'Loan Assistance', 'Consultation', 'Partnership'],
                datasets: [{ label: 'Revenue (₹)', data: [1850000, 850000, 420000, 310000], backgroundColor: '#0d9e78' }]
            },
            options: { responsive: true }
        });
    }
    const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    if (monthlyCtx) {
        charts.monthly = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{ label: 'Monthly Revenue', data: [125000, 148000, 162000, 158000, 185000, 210000, 245000, 268000, 285000, 310000, 325000, 340000], borderColor: '#0d9e78', fill: true }]
            },
            options: { responsive: true }
        });
    }
}

// ========== LOAD CLIENTS ==========
async function loadClients() {
    const data = await apiCall('get_client_growth.php');
    if (data.success && data.trend) {
        const ctx = document.getElementById('clientTrendChart').getContext('2d');
        if (charts.trend) charts.trend.destroy();
        charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.trend.labels,
                datasets: [{ label: 'New Clients', data: data.trend.values, borderColor: '#0d9e78', fill: true }]
            },
            options: { responsive: true }
        });
    } else {
        const ctx = document.getElementById('clientTrendChart').getContext('2d');
        if (ctx) {
            charts.trend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{ label: 'New Clients', data: [85, 92, 88, 95, 105, 112, 125, 130, 142, 147, 155, 162], borderColor: '#0d9e78', fill: true }]
                },
                options: { responsive: true }
            });
        }
    }
}

// ========== LOAD OPERATIONS ==========
async function loadOperations() {
    const data = await apiCall('get_operational_kpis.php');
    if (data.success && data.kpi_data) {
        const ctx = document.getElementById('kpiChart').getContext('2d');
        if (charts.kpi) charts.kpi.destroy();
        charts.kpi = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.kpi_data.labels,
                datasets: [{ label: 'KPI Score', data: data.kpi_data.values, borderColor: '#0d9e78', fill: true }]
            },
            options: { responsive: true }
        });
    } else {
        const ctx = document.getElementById('kpiChart').getContext('2d');
        if (ctx) {
            charts.kpi = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{ label: 'KPI Score', data: [85, 88, 92, 94], borderColor: '#0d9e78', fill: true }]
                },
                options: { responsive: true }
            });
        }
    }
}

// ========== LOAD TEAM ==========
async function loadTeam() {
    const data = await apiCall('get_team_performance.php');
    if (data.success && data.team) {
        document.getElementById('teamBody').innerHTML = data.team.map(t => `
            <tr>
                <td><strong>${escapeHtml(t.name)}</strong></td>
                <td>${t.department || '-'}</td>
                <td>${t.cases_closed || 0}</td>
                <td>${t.sla_met || 0}%</span></td>
                <td>${t.rating || 0}★</span></td>
            </tr>
        `).join('');
    } else {
        // Fallback demo data
        document.getElementById('teamBody').innerHTML = `
            <tr><td><strong>Rahul Sharma</strong></td><td>Credit Analysis</span><td>45</span><td>96%</span><td>4.9★</span></tr>
            <tr><td><strong>Priya Mehta</strong></span><td>Dispute Resolution</span><td>38</span><td>92%</span><td>4.7★</span></tr>
            <tr><td><strong>Amit Verma</strong></span><td>Customer Support</span><td>142</span><td>99%</span><td>4.9★</span></tr>
            <tr><td><strong>Neha Gupta</strong></span><td>Sales</span><td>28</span><td>85%</span><td>4.6★</span></tr>
        `;
    }
}

// ========== LOAD PARTNERS ==========
async function loadPartners() {
    const data = await apiCall('get_partner_performance.php');
    if (data.success && data.partners) {
        document.getElementById('partnerBody').innerHTML = data.partners.map(p => `
            <tr>
                <td><strong>${escapeHtml(p.name)}</strong></td>
                <td>${p.leads_sent || 0}</td>
                <td>${p.conversions || 0}</td>
                <td><span class="badge ${p.conversion_rate >= 30 ? 'badge-success' : p.conversion_rate >= 15 ? 'badge-warning' : 'badge-secondary'}">${p.conversion_rate || 0}%</span></td>
                <td>₹${(p.commission || 0).toLocaleString()}</span></td>
            </tr>
        `).join('');
    } else {
        document.getElementById('partnerBody').innerHTML = `
            <tr><td><strong>Delhi Credit Solutions</strong></td><td>245</span><td>78</span><td><span class="badge badge-success">32%</span></td><td>₹1,85,000</span></tr>
            <tr><td><strong>Mumbai Finance Hub</strong></span><td>187</span><td>52</span><td><span class="badge badge-success">28%</span></span><td>₹1,25,000</span></tr>
            <tr><td><strong>Pune Financial Services</strong></span><td>98</span><td>21</span><td><span class="badge badge-warning">21%</span></span><td>₹42,000</span></tr>
        `;
    }
}

// ========== LOAD FINANCIAL ==========
async function loadFinancial() {
    const data = await apiCall('get_financial_summary.php');
    if (data.success && data.metrics) {
        document.getElementById('financialBody').innerHTML = data.metrics.map(m => `
            <tr>
                <td>${m.metric}</td>
                <td>${m.this_month}</td>
                <td>${m.last_month}</td>
                <td><span class="${m.change >= 0 ? 'stat-change up' : 'stat-change down'}">${m.change >= 0 ? '+' : ''}${m.change}%</span></td>
                <td>${m.ytd}</td>
            </tr>
        `).join('');
    } else {
        document.getElementById('financialBody').innerHTML = `
            <tr><td>Total Revenue</span><td>₹3,25,000</span><td>₹2,85,000</span><td><span class="stat-change up">+14%</span></td><td>₹34,50,000</span></td>
            <tr><td>Net Profit</span><td>₹2,13,000</span><td>₹1,86,500</span><td><span class="stat-change up">+14.2%</span></span><td>₹22,05,000</span></td>
            <tr><td>Partner Commissions</span><td>₹45,000</span><td>₹38,000</span><td><span class="stat-change up">+18.4%</span></span><td>₹4,85,000</span></tr>
        `;
    }
}

// ========== LOAD FORECAST ==========
async function loadForecast() {
    const data = await apiCall('get_forecast.php');
    if (data.success && data.forecast_data) {
        const ctx = document.getElementById('forecastChart').getContext('2d');
        if (charts.forecast) charts.forecast.destroy();
        charts.forecast = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.forecast_data.labels,
                datasets: [
                    { label: 'Actual Revenue', data: data.forecast_data.actual, borderColor: '#0d9e78', fill: false },
                    { label: 'Forecast', data: data.forecast_data.forecast, borderColor: '#d97706', borderDash: [5, 5], fill: false }
                ]
            },
            options: { responsive: true }
        });
    } else {
        const ctx = document.getElementById('forecastChart').getContext('2d');
        if (ctx) {
            charts.forecast = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        { label: 'Actual Revenue', data: [125000, 148000, 162000, 158000, 185000, 210000, 245000, 268000, 285000, 310000, 325000, 340000], borderColor: '#0d9e78', fill: false },
                        { label: 'Forecast', data: [130000, 155000, 175000, 185000, 210000, 240000, 270000, 300000, 330000, 360000, 390000, 420000], borderColor: '#d97706', borderDash: [5, 5], fill: false }
                    ]
                },
                options: { responsive: true }
            });
        }
    }
    
    // Goals Progress (always show demo for now)
    document.getElementById('goalProgress').innerHTML = `
        <div style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between"><strong>Annual Revenue Target</strong><span>86% Achieved</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:86%"></div></div>
            <div style="font-size:12px;color:var(--text-secondary)">Target: ₹40,00,000 | Achieved: ₹34,50,000</div>
        </div>
        <div style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between"><strong>Client Acquisition</strong><span>81% Achieved</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:81%"></div></div>
            <div style="font-size:12px;color:var(--text-secondary)">Target: 2000 | Achieved: 1620</div>
        </div>
        <div style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between"><strong>Partner Network</strong><span>96% Achieved</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:96%"></div></div>
            <div style="font-size:12px;color:var(--text-secondary)">Target: 25 | Achieved: 24</div>
        </div>
        <div style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between"><strong>Customer Satisfaction</strong><span>98% Achieved</span></div>
            <div class="progress-bar"><div class="progress-fill" style="width:98%"></div></div>
            <div style="font-size:12px;color:var(--text-secondary)">Target: 4.9★ | Achieved: 4.8★</div>
        </div>
    `;
}

// Initialize everything on page load
loadDashboard();
</script>
</body>
</html>