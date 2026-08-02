<?php
// client-portal-dashboard.php - Client portal dashboard
session_start();

if (!isset($_SESSION['client_id'])) {
    header('Location: client-login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    
    // Get client stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_cases WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $cases = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_documents WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $documents = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE client_email = ?");
    $stmt->execute([$_SESSION['client_email']]);
    $tickets = $stmt->fetchColumn();
    
    // Get recent cases
    $stmt = $pdo->prepare("SELECT * FROM client_cases WHERE client_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$client_id]);
    $recent_cases = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Portal Dashboard</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f4f6f9; }
        .header { background: #0d9e78; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #0d9e78; }
        .stat-label { color: #666; margin-top: 5px; }
        .cases { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .case-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .status { padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-pending { background: #ffd700; color: #333; }
        .status-completed { background: #4caf50; color: white; }
        .status-in-progress { background: #2196f3; color: white; }
        .logout { float: right; background: #f44336; color: white; padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .logout:hover { background: #d32f2f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👤 Welcome, <?= htmlspecialchars($client_name) ?>!</h1>
        <a href="client-logout.php" class="logout">🚪 Logout</a>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $cases ?></div>
            <div class="stat-label">📋 Active Cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $documents ?></div>
            <div class="stat-label">📄 Documents</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $tickets ?></div>
            <div class="stat-label">🎫 Support Tickets</div>
        </div>
    </div>
    
    <div class="cases">
        <h2>📊 Recent Cases</h2>
        <?php if (isset($recent_cases) && count($recent_cases) > 0): ?>
            <?php foreach ($recent_cases as $case): ?>
                <div class="case-item">
                    <span><?= htmlspecialchars($case['case_no']) ?></span>
                    <span><?= htmlspecialchars($case['service']) ?></span>
                    <span class="status status-<?= str_replace(' ', '-', $case['status']) ?>">
                        <?= ucfirst($case['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No cases found.</p>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="client-view-cases.php" style="background: #0d9e78; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">View All Cases</a>
        <a href="client-upload-document.php" style="background: #2196f3; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">📤 Upload Document</a>
        <a href="client-support.php" style="background: #ff9800; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">🎫 Support</a>
    </div>
</body>
</html>
