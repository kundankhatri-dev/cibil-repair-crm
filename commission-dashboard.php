<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    header('Location: login.html');
    exit;
}
$user_name = $_SESSION['user_name'] ?? 'Partner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Dashboard | CIBIL Repair</title>
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
        .badge {
            padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 600;
            display: inline-block;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fed7aa; color: #9a3412; }
        .empty-state { text-align: center; padding: 40px; color: #9ca3af; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 16px; text-align: center; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="header">
        <h1><i class="fas fa-rupee-sign"></i> Commission Dashboard</h1>
        <a href="partner-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Partner Portal</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-chart-line" style="font-size: 24px; color: #0d9e78;"></i>
            <div class="stat-value" id="totalCommission">₹0</div>
            <div class="stat-label">Total Commission Earned</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle" style="font-size: 24px; color: #0d9e78;"></i>
            <div class="stat-value" id="paidCommission">₹0</div>
            <div class="stat-label">Paid Out</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock" style="font-size: 24px; color: #d97706;"></i>
            <div class="stat-value" id="pendingCommission">₹0</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>

    <div class="card">
        <div class="card-title"><i class="fas fa-history"></i> Commission History</div>
        <div class="table-wrap" style="overflow-x: auto;">
            <table>
                <thead><tr><th>#</th><th>Customer</th><th>Service</th><th>Sale Amount</th><th>Commission</th><th>Date</th><th>Status</th></tr></thead>
                <tbody id="commissionBody"><tr><td colspan="7" class="empty-state">Loading commission data...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const PARTNER_ID = <?= json_encode($_SESSION['user_id']) ?>;

async function loadCommission() {
    try {
        const response = await fetch(`/api/partner/get_commission.php?partner_id=${PARTNER_ID}`);
        const data = await response.json();
        if (data.success && data.commissions) {
            const total = data.commissions.reduce((s, c) => s + (Number(c.commission_amount) || 0), 0);
            document.getElementById('totalCommission').textContent = '₹' + total.toLocaleString('en-IN');
            document.getElementById('pendingCommission').textContent = '₹' + total.toLocaleString('en-IN');
            
            const tbody = document.getElementById('commissionBody');
            if (data.commissions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No commission records found</td></tr>';
            } else {
                tbody.innerHTML = data.commissions.map((c, i) => `
                    <tr>
                        <td>${i+1}</td>
                        <td><strong>${escapeHtml(c.customer_name)}</strong></td>
                        <td>${escapeHtml(c.service_type || 'Credit Repair')}</td>
                        <td>₹${Number(c.sale_amount || 15000).toLocaleString('en-IN')}</td>
                        <td><strong>₹${Number(c.commission_amount || 0).toLocaleString('en-IN')}</strong></td>
                        <td>${c.created_at ? new Date(c.created_at).toLocaleDateString() : '-'}</td>
                        <td><span class="badge badge-success">Earned</span></td>
                    </tr>
                `).join('');
            }
        } else {
            document.getElementById('commissionBody').innerHTML = '<tr><td colspan="7" class="empty-state">No commission data available</td></tr>';
        }
    } catch(e) {
        console.error('Error loading commission:', e);
        document.getElementById('commissionBody').innerHTML = '<tr><td colspan="7" class="empty-state">Error loading commission data</td></tr>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}

loadCommission();
</script>
</body>
</html>