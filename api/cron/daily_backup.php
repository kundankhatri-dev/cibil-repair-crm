#!/usr/bin/env php
<?php
require_once '../api/config.php';

$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$filename = "backup_" . date('Y-m-d') . ".sql";
$filepath = $backup_dir . $filename;

// Get all tables
$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$backup_sql = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    $create = mysqli_query($conn, "SHOW CREATE TABLE $table");
    $create_row = mysqli_fetch_row($create);
    $backup_sql .= "DROP TABLE IF EXISTS $table;\n";
    $backup_sql .= $create_row[1] . ";\n\n";
    
    $data = mysqli_query($conn, "SELECT * FROM $table");
    while ($row = mysqli_fetch_assoc($data)) {
        $backup_sql .= "INSERT INTO $table VALUES(";
        foreach ($row as $value) {
            $backup_sql .= '"' . addslashes($value) . '",';
        }
        $backup_sql = rtrim($backup_sql, ',') . ");\n";
    }
    $backup_sql .= "\n";
}

file_put_contents($filepath, $backup_sql);

// Keep only last 30 days backups
$files = glob($backup_dir . "backup_*.sql");
if (count($files) > 30) {
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    $to_delete = array_slice($files, 0, count($files) - 30);
    foreach ($to_delete as $file) {
        unlink($file);
    }
}

echo "Backup completed: " . $filename . "\n";
?>