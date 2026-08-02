<?php
// ============================================================
// CIBIL REPAIR CRM - Database Backup API (WORKING)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ============================================================
// CREATE BACKUP DIRECTORY
// ============================================================

$backupDir = __DIR__ . '/../backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// ============================================================
// GET TABLES
// ============================================================

$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

if (empty($tables)) {
    echo json_encode(['success' => false, 'error' => 'No tables found']);
    exit;
}

// ============================================================
// GENERATE BACKUP
// ============================================================

$timestamp = date('Y-m-d_H-i-s');
$filename = "backup_{$timestamp}.sql";
$filePath = $backupDir . $filename;

$sql = "-- ============================================================\n";
$sql .= "-- CIBIL REPAIR CRM Database Backup\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Tables: " . count($tables) . "\n";
$sql .= "-- ============================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Backup each table
foreach ($tables as $table) {
    // Get table structure
    $createResult = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    if ($createResult) {
        $row = mysqli_fetch_row($createResult);
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Table: `$table`\n";
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";
        mysqli_free_result($createResult);
    }
    
    // Get table data (limit to 1000 rows per table to keep file manageable)
    $dataResult = mysqli_query($conn, "SELECT * FROM `$table` LIMIT 1000");
    if ($dataResult && mysqli_num_rows($dataResult) > 0) {
        $fields = mysqli_num_fields($dataResult);
        $rows = [];
        while ($row = mysqli_fetch_row($dataResult)) {
            $values = [];
            for ($i = 0; $i < $fields; $i++) {
                if ($row[$i] === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . mysqli_real_escape_string($conn, $row[$i]) . "'";
                }
            }
            $rows[] = "(" . implode(',', $values) . ")";
        }
        if (!empty($rows)) {
            $sql .= "INSERT INTO `$table` VALUES \n" . implode(",\n", $rows) . ";\n\n";
        }
        mysqli_free_result($dataResult);
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Write to file
file_put_contents($filePath, $sql);
$fileSize = filesize($filePath);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Backup created successfully',
    'data' => [
        'filename' => $filename,
        'size' => $fileSize,
        'size_formatted' => formatFileSize($fileSize),
        'tables_count' => count($tables),
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
exit;

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>