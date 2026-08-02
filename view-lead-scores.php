<?php
// view-lead-scores.php - View lead scores
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    $stmt = $pdo->query("
        SELECT id, name, phone, email, service, status, lead_score, source, created_at 
        FROM leads 
        WHERE status != 'converted' 
        ORDER BY lead_score DESC, created_at DESC 
        LIMIT 50
    ");
    $leads = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lead Scores</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f4f6f9; }
        h1 { color: #0b2a23; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th { background: #0d9e78; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f5f5f5; }
        .score-high { color: #4caf50; font-weight: bold; }
        .score-medium { color: #ff9800; font-weight: bold; }
        .score-low { color: #f44336; font-weight: bold; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-new { background: #2196f3; color: white; }
        .status-contacted { background: #ff9800; color: white; }
        .status-converted { background: #4caf50; color: white; }
        .status-lost { background: #f44336; color: white; }
    </style>
</head>
<body>
    <h1>🎯 Lead Scores</h1>
    <p>Total: <?= count($leads) ?> active leads</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Service</th>
                <th>Source</th>
                <th>Status</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $i => $lead): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($lead['name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($lead['phone'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($lead['service'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($lead['source'] ?? 'N/A') ?></td>
                    <td>
                        <span class="status-badge status-<?= $lead['status'] ?? 'new' ?>">
                            <?= ucfirst($lead['status'] ?? 'New') ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $score = (int)($lead['lead_score'] ?? 0);
                        $class = $score >= 70 ? 'score-high' : ($score >= 40 ? 'score-medium' : 'score-low');
                        ?>
                        <span class="<?= $class ?>"><?= $score ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
