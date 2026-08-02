<?php
// Simple backup that definitely works
$backupFile = 'backups/production/backup_' . date('Ymd_His') . '.txt';

mkdir('backups/production', 0755, true);

echo "💾 Backup\n\n";
echo "🔄 Saving... ";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=u929623538_cibil', 'u929623538_cibilrepair', 'Kundanlaxmi@1995');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $content = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Get table structure
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $content .= "DROP TABLE IF EXISTS `$table`;\n";
        $content .= $create[1] . ";\n\n";
        
        // Get data
        $rows = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_keys($row);
            $vals = array_map(function($v) {
                return $v === null ? 'NULL' : "'" . addslashes($v) . "'";
            }, $row);
            $content .= "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
        }
        $content .= "\n";
    }
    
    file_put_contents($backupFile, $content);
    $size = round(filesize($backupFile) / 1024, 2);
    echo "\033[32m✅ Done! ({$size} KB)\033[0m\n";
    
} catch (Exception $e) {
    echo "\033[31m❌ Failed: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}
