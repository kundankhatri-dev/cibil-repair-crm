<?php
// ============================================
// SIMPLE WORKING WEBHOOK
// ============================================

// Log file
$log_file = __DIR__ . '/storage/logs/deploy.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

// Log the trigger
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Webhook triggered\n", FILE_APPEND);

// Change directory and run deploy
$command = 'cd /home/u929623538/domains/cibilrepair.in/public_html && php bin/console deploy 2>&1';
exec($command, $output, $return_code);

// Log the result
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Deploy completed (code: $return_code)\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Output: " . implode("\n", $output) . "\n", FILE_APPEND);

// Response
echo "✅ Webhook processed at " . date('Y-m-d H:i:s') . "\n";
if ($return_code === 0) {
    echo "✅ Deployed successfully!\n";
} else {
    echo "❌ Deploy failed with code: $return_code\n";
}
