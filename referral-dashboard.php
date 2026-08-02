<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    header('Location: login.html');
    exit;
}
$user_name = $_SESSION['user_name'] ?? 'Partner';
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Dashboard | CIBIL Repair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: #0d1b2a; }
        .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header {
            background: white; border-radius: 16px; padding: 20px 24px;
            margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
        }
        .header h1 { font-size: 24px; font-weight: 700; color: #0b2a23; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; background: #0d9e78; color: white;
            text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: all 0.2s;
        }
        .back-btn:hover { background: #0a7d60; transform: translateY(-2px); }
        .referral-card {
            background: linear-gradient(135deg, #0b2a23, #0d9e78);
            border-radius: 16px; padding: 30px; text-align: center; color: white;
            margin-bottom: 24px;
        }
        .ref-code {
            font-size: 28px; font-weight: 800; letter-spacing: 4px;
            background: rgba(255,255,255,0.2); display: inline-block;
            padding: 12px 24px; border-radius: 12px; margin: 20px 0;
            font-family: monospace;
        }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 24px;
        }
        .stat-card {
            background: white; border-radius: 16px; padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value { font-size: 32px; font-weight: 800; color: #0d9e78; margin-top: 8px; }
        .stat-label { font-size: 13px; color: #4a5568; margin-top: 4px; }
        .card {
            background: white; border-radius: 16px; padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card-title {
            font-size: 18px; font-weight: 700; margin-bottom: 20px;
            padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; }
        .btn {
            padding: 8px 16px; border-radius: 8px; font-weight: 600;
            border: none; cursor: pointer; transition: all 0.2s;
        }
        .btn-primary { background: #0d9e78; color: white; }
        .btn-primary:hover { background: #0a7d60; }
        .empty-state { text-align: center; padding: 40px; color: #9ca3af; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 16px; text-align: center; }
            .ref-code { font-size: 18px; letter-spacing: 2px; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="header">
        <h1><i class="fas fa-share-alt"></i> Referral Dashboard</h1>
        <a href="partner-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Partner Portal</a>
    </div>

    <div class="referral-card">
        <i class="fas fa-gift" style="font-size: 32px;"></i>
        <h2 style="margin-top: 12px;">Refer & Earn More!</h2>
        <p style="opacity: 0.9; margin-top: 8px;">Share your code with other partners and earn commission on their conversions</p>
        <div class="ref-code" id="refCode">PART-<?= strtoupper(substr(md5($user_id), 0, 8)) ?></div>
        <button class="btn btn-primary" onclick="copyRefCode()"><i class="fas fa-copy"></i> Copy Code</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-user-plus" style="font-size: 24px; color: #0d9e78;"></i>
            <div class="stat-value" id="totalReferrals">0</div>
            <div class="stat-label">Total Referrals</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-line" style="font-size: 24px; color: #0d9e78;"></i>
            <div class="stat-value" id="refConversions">0</div>
            <div class="stat-label">Conversions</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-rupee-sign" style="font-size: 24px; color: #0d9e78;"></i>
            <div class="stat-value" id="refEarnings">₹0</div>
            <div class="stat-label">Referral Earnings</div>
        </div>
    </div>

    <div class="card">
        <div class="card-title"><i class="fas fa-list"></i> Referred Partners</div>
        <div class="table-wrap" style="overflow-x: auto;">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Joined</th><th>Status</th><th>Your Commission</th></tr></thead>
                <tbody id="referralsBody"><tr><td colspan="5" class="empty-state">No referrals yet. Share your code!</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyRefCode() {
    const code = document.getElementById('refCode').textContent;
    navigator.clipboard.writeText(code);
    alert('Referral code copied!');
}
</script>
</body>
</html>