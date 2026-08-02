<?php
// maintenance.php - Enable/disable maintenance mode
$maintenance_file = __DIR__ . '/.maintenance';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'enable') {
        file_put_contents($maintenance_file, date('Y-m-d H:i:s') . ' - Maintenance enabled');
        echo "✅ Maintenance mode ENABLED";
    } elseif ($action === 'disable') {
        unlink($maintenance_file);
        echo "✅ Maintenance mode DISABLED";
    }
    exit;
}

if (file_exists($maintenance_file)) {
    http_response_code(503);
    header('Retry-After: 3600');
    die('<h1>🔧 Under Maintenance</h1><p>We are performing scheduled maintenance. Please check back soon.</p>');
}
?>
