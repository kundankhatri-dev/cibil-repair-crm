#!/usr/bin/env php
<?php
// Simple backup script that works

echo "💾 Backup\n\n";

$backupDir = 'backups/production/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Ymd_His');
$backupFile = $backupDir . "backup_{$timestamp}.sql";

echo "🔄 Saving... ";

// Try with full path
$command = "/usr/bin/mysqldump -u u929623538_cibilrepair -p'Kundanlaxmi@1995' u929623538_cibil 2>&1 > " . $backupFile;
exec($command, $output, $returnCode);

// Check if file was created and has content
if (file_exists($backupFile) && filesize($backupFile) > 1000) {
    $size = round(filesize($backupFile) / 1024, 2);
    echo "\033[32m✅ Done! ({$size} KB)\033[0m\n";
    echo "\nBackup saved to: " . $backupFile . "\n";
} else {
    echo "\033[31m❌ Failed\033[0m\n";
    if (file_exists($backupFile)) {
        echo "File created but size: " . filesize($backupFile) . " bytes (too small)\n";
    } else {
        echo "No file created\n";
    }
}
