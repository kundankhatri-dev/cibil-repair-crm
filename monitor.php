<?php
// monitor.php - System Health Dashboard
header('Content-Type: application/json');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'checks' => []
];

// Database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    $status['checks']['database'] = '✅ Connected';
} catch(Exception $e) {
    $status['checks']['database'] = '❌ Failed';
}

// Last backup
$backups = glob('backups/production/*.sql');
if ($backups) {
    $latest = max($backups);
    $status['checks']['last_backup'] = basename($latest) . ' (' . round(filesize($latest)/1024, 2) . ' KB)';
} else {
    $status['checks']['last_backup'] = '❌ No backups';
}

// Git status
exec('cd /home/u929623538/domains/cibilrepair.in/public_html && git log --oneline -1', $git_output);
$status['checks']['latest_commit'] = $git_output[0] ?? 'Unknown';

echo json_encode($status, JSON_PRETTY_PRINT);
