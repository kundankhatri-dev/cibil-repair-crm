<?php
// ============================================================
// DASHBOARD GENERATOR - Creates all CRM dashboards
// Run this file once to generate all dashboards
// ============================================================

$dashboards = [
    'admin' => 'Admin Dashboard',
    'ceo' => 'CEO Dashboard', 
    'hr' => 'HR Dashboard',
    'finance' => 'Finance Dashboard',
    'sales' => 'Sales Dashboard',
    'marketing' => 'Marketing Dashboard',
    'support' => 'Support Dashboard',
    'customer_support' => 'Customer Support Dashboard',
    'operations' => 'Operations Dashboard',
    'credit_analyst' => 'Credit Analyst Dashboard',
    'dispute_processing' => 'Dispute Processing Dashboard',
    'risk' => 'Risk Management Dashboard',
    'legal' => 'Legal & Compliance Dashboard',
    'it' => 'IT Dashboard',
    'project' => 'Project Management Dashboard',
    'training' => 'Training Dashboard',
    'document' => 'Document Management Dashboard',
    'qa' => 'Quality Assurance Dashboard',
    'client' => 'Client Dashboard',
    'partner' => 'Partner Dashboard',
    'employee' => 'Employee Dashboard'
];

$role_map = [
    'admin' => 'admin,super_admin',
    'ceo' => 'ceo,admin,super_admin',
    'hr' => 'hr_manager,admin,super_admin',
    'finance' => 'finance_manager,admin,super_admin',
    'sales' => 'sales_manager,admin,super_admin',
    'marketing' => 'marketing_manager,admin,super_admin',
    'support' => 'support_agent,support_manager,admin,super_admin',
    'customer_support' => 'support_agent,support_manager,admin,super_admin',
    'operations' => 'operations_manager,admin,super_admin',
    'credit_analyst' => 'credit_analyst,admin,super_admin',
    'dispute_processing' => 'credit_analyst,operations_manager,admin,super_admin',
    'risk' => 'risk_manager,admin,super_admin',
    'legal' => 'compliance_officer,legal_team,admin,super_admin',
    'it' => 'it_admin,admin,super_admin',
    'project' => 'project_manager,admin,super_admin',
    'training' => 'trainer,hr_manager,admin,super_admin',
    'document' => 'manager,compliance_officer,admin,super_admin',
    'qa' => 'qa_manager,admin,super_admin',
    'client' => 'client',
    'partner' => 'partner',
    'employee' => 'employee'
];

$template_start = '<?php
session_start();

$allowed_roles = ["';
$template_middle = '"];
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["user_role"], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION["user_name"] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>';
$template_title = '</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Inter", sans-serif;
            background: #f4f6f9;
            color: #111827;
        }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #0b2a23;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
        }
        .sidebar h2 { color: white; margin-bottom: 30px; }
        .sidebar nav a {
            display: block;
            padding: 10px 0;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            margin: 8px 0;
        }
        .sidebar nav a:hover { color: white; }
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 24px;
        }
        .header {
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { font-size: 24px; font-weight: 600; }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value { font-size: 28px; font-weight: 700; color: #0d9e78; margin: 10px 0; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <h2>📊 CRM</h2>
        <nav>
            <a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="header">
            <h1>';
$template_middle2 = '</h1>
            <div>
                <span style="margin-right: 15px;">👋 ' . $user_name . '</span>
                <button class="logout-btn" onclick="window.location.href=\'logout.php\'">Logout</button>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-chart-line" style="font-size: 24px;"></i>
                <div class="stat-value">—</div>
                <div>Coming Soon</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users" style="font-size: 24px;"></i>
                <div class="stat-value">—</div>
                <div>Coming Soon</div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">Welcome to ';
$template_end = '</div>
            <p>This dashboard is ready for you. Content will be added based on your requirements.</p>
            <p style="margin-top: 12px; color: #6b7280;">Role: ' . $_SESSION["user_role"] . '</p>
        </div>
    </main>
</div>
</body>
</html>';

$created = [];
foreach ($dashboards as $key => $title) {
    $filename = $key . '_dashboard.php';
    $content = $template_start . $role_map[$key] . $template_middle . $title . $template_middle2 . $title . $template_end;
    
    if (file_put_contents($filename, $content)) {
        $created[] = $filename;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Generator</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0a0e27; color: #fff; }
        .success { color: #10b981; }
        .summary { background: #1a1a2e; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        a { color: #0d9e78; text-decoration: none; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>📁 Dashboard Generator</h1>
    
    <div class="summary">
        <h3>Results:</h3>
        <p class="success">✅ Successfully created <?= count($created) ?> dashboards</p>
    </div>
    
    <div class="summary">
        <h3>Access Your Dashboards:</h3>
        <div class="grid">
            <?php foreach ($created as $file): ?>
                <div><a href="<?= $file ?>" target="_blank">📊 <?= $file ?></a></div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="summary">
        <h3>Quick Test:</h3>
        <button onclick="testDashboards()" style="padding: 10px 20px; background: #0d9e78; border: none; border-radius: 8px; color: white; cursor: pointer;">Test All Dashboards</button>
        <div id="testResults" style="margin-top: 15px;"></div>
    </div>
</body>
<script>
async function testDashboards() {
    const dashboards = <?= json_encode($created) ?>;
    const results = document.getElementById('testResults');
    results.innerHTML = 'Testing...<br>';
    
    for (const dashboard of dashboards) {
        try {
            const response = await fetch(dashboard, { method: 'HEAD' });
            results.innerHTML += `${response.ok ? '✅' : '❌'} ${dashboard}<br>`;
        } catch(e) {
            results.innerHTML += `❌ ${dashboard}<br>`;
        }
    }
}
</script>
</html>