<?php
// ============================================================
// CIBIL REPAIR CRM - Restore Backup API
// Endpoint: /api/restore_backup.php
// Method: POST
// ============================================================

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'restore_backup.php') {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// ============================================================
// CONFIGURATION
// ============================================================

require_once __DIR__ . '/config.php';

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// AUTHENTICATION & AUTHORIZATION
// ============================================================

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

// Check if user is authenticated
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required. Please login.'
    ]);
    exit;
}

// Check if user has admin or super_admin role (restore is critical operation)
if (!in_array($role, ['admin', 'super_admin'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Admin or Super Admin access required.'
    ]);
    exit;
}

// ============================================================
// CSRF VALIDATION
// ============================================================

$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode && (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken))) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token. Please refresh and try again.'
    ]);
    exit;
}

// ============================================================
// GET INPUT PARAMETERS
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$filename = isset($input['filename']) ? trim($input['filename']) : '';
$confirm = isset($input['confirm']) ? filter_var($input['confirm'], FILTER_VALIDATE_BOOLEAN) : false;
$testMode = isset($input['test_mode']) ? filter_var($input['test_mode'], FILTER_VALIDATE_BOOLEAN) : false;
$dropTables = isset($input['drop_tables']) ? filter_var($input['drop_tables'], FILTER_VALIDATE_BOOLEAN) : true;
$createTables = isset($input['create_tables']) ? filter_var($input['create_tables'], FILTER_VALIDATE_BOOLEAN) : true;
$insertData = isset($input['insert_data']) ? filter_var($input['insert_data'], FILTER_VALIDATE_BOOLEAN) : true;
$ignoreErrors = isset($input['ignore_errors']) ? filter_var($input['ignore_errors'], FILTER_VALIDATE_BOOLEAN) : false;
$backupBeforeRestore = isset($input['backup_before_restore']) ? filter_var($input['backup_before_restore'], FILTER_VALIDATE_BOOLEAN) : true;
$maxExecutionTime = isset($input['max_execution_time']) ? intval($input['max_execution_time']) : 3600;

// ============================================================
# VALIDATE INPUT
// ============================================================

if (empty($filename)) {
    echo json_encode([
        'success' => false,
        'error' => 'Backup filename is required'
    ]);
    exit;
}

// Prevent path traversal
$filename = basename($filename);
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid filename'
    ]);
    exit;
}

// ============================================================
# CHECK CONFIRMATION
// ============================================================

if (!$confirm && !$testMode) {
    echo json_encode([
        'success' => false,
        'error' => 'Confirmation required. Set confirm=true to proceed.',
        'warning' => 'Restoring a backup will overwrite your current database. Please ensure you have a recent backup before proceeding.'
    ]);
    exit;
}

// ============================================================
# CHECK BACKUP FILE
// ============================================================

$backupDir = __DIR__ . '/../backups/';
$filepath = $backupDir . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Backup file not found: ' . $filename
    ]);
    exit;
}

// Check if file is readable
if (!is_readable($filepath)) {
    echo json_encode([
        'success' => false,
        'error' => 'Backup file is not readable'
    ]);
    exit;
}

// Get file extension
$extension = pathinfo($filename, PATHINFO_EXTENSION);

// ============================================================
# CREATE PRE-RESTORE BACKUP
// ============================================================

if ($backupBeforeRestore && !$testMode) {
    $preRestoreBackup = createPreRestoreBackup($conn, $backupDir);
    if (!$preRestoreBackup['success']) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create pre-restore backup: ' . $preRestoreBackup['error']
        ]);
        exit;
    }
    $preRestoreFilename = $preRestoreBackup['filename'];
}

// ============================================================
# TEST MODE
// ============================================================

if ($testMode) {
    // Analyze backup file
    $analysis = analyzeBackupFile($filepath, $extension);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test mode completed. Backup file analyzed successfully.',
        'data' => [
            'test_mode' => true,
            'filename' => $filename,
            'file_size' => filesize($filepath),
            'file_size_formatted' => formatFileSize(filesize($filepath)),
            'extension' => $extension,
            'analysis' => $analysis,
            'pre_restore_backup' => isset($preRestoreFilename) ? $preRestoreFilename : null,
            'sql_statements' => [
                'drop_tables' => $dropTables,
                'create_tables' => $createTables,
                'insert_data' => $insertData
            ],
            'warning' => 'This was a test run. No changes were made to the database.'
        ]
    ]);
    exit;
}

// ============================================================
# SET MAX EXECUTION TIME
// ============================================================

set_time_limit($maxExecutionTime);
ini_set('memory_limit', '512M');

// ============================================================
# START RESTORE PROCESS
// ============================================================

try {
    // Disable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
    mysqli_query($conn, "SET AUTOCOMMIT=0");
    mysqli_query($conn, "START TRANSACTION");
    
    $restoreResults = [
        'total_statements' => 0,
        'successful_statements' => 0,
        'failed_statements' => 0,
        'errors' => [],
        'tables_restored' => [],
        'tables_failed' => []
    ];
    
    // Read backup file content
    $sqlContent = readBackupFile($filepath, $extension);
    if ($sqlContent === false) {
        throw new Exception('Failed to read backup file');
    }
    
    // Split SQL statements
    $statements = splitSqlStatements($sqlContent);
    $restoreResults['total_statements'] = count($statements);
    
    // Execute statements
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        // Skip certain statements if configured
        if (strpos(strtoupper($statement), 'DROP TABLE') !== false && !$dropTables) {
            $restoreResults['successful_statements']++;
            continue;
        }
        
        if (strpos(strtoupper($statement), 'CREATE TABLE') !== false && !$createTables) {
            $restoreResults['successful_statements']++;
            continue;
        }
        
        if (strpos(strtoupper($statement), 'INSERT INTO') !== false && !$insertData) {
            $restoreResults['successful_statements']++;
            continue;
        }
        
        // Detect table name for logging
        $tableName = detectTableName($statement);
        
        // Execute statement
        try {
            $result = mysqli_multi_query($conn, $statement);
            
            if ($result === false) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Process multi-query results
            do {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_next_result($conn));
            
            $restoreResults['successful_statements']++;
            if ($tableName) {
                $restoreResults['tables_restored'][] = $tableName;
            }
            
        } catch (Exception $e) {
            $errorMsg = "Statement #" . ($index + 1) . " failed: " . $e->getMessage();
            $restoreResults['failed_statements']++;
            $restoreResults['errors'][] = $errorMsg;
            
            if ($tableName) {
                $restoreResults['tables_failed'][] = $tableName;
            }
            
            if (!$ignoreErrors) {
                throw new Exception($errorMsg . " - Restore aborted. Set ignore_errors=true to continue.");
            }
            
            // Log error but continue
            error_log("Restore error: " . $errorMsg);
        }
    }
    
    // Commit transaction
    mysqli_query($conn, "COMMIT");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
    
    // ============================================================
    # LOG SUCCESSFUL RESTORE
    // ============================================================
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logSql = "INSERT INTO backup_logs (backup_file, file_size, file_type, backup_type, status, user_id, user_name, ip_address, created_at) 
               VALUES (?, ?, ?, 'restore', 'restored', ?, ?, ?, NOW())";
    $logStmt = mysqli_prepare($conn, $logSql);
    $fileSize = filesize($filepath);
    $fileType = $extension === 'gz' ? 'sql.gz' : 'sql';
    mysqli_stmt_bind_param($logStmt, 'sissss', $filename, $fileSize, $fileType, $user_id, $user_name, $ip);
    mysqli_stmt_execute($logStmt);
    mysqli_stmt_close($logStmt);
    
    // Log activity
    $activitySql = "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                    VALUES (?, ?, 'Database restore', ?, ?, NOW())";
    $details = "Restored database from backup: $filename. Statements: {$restoreResults['successful_statements']}/{$restoreResults['total_statements']}";
    if ($backupBeforeRestore && isset($preRestoreFilename)) {
        $details .= ". Pre-restore backup: $preRestoreFilename";
    }
    $activityStmt = mysqli_prepare($conn, $activitySql);
    mysqli_stmt_bind_param($activityStmt, 'isss', $user_id, $user_name, $details, $ip);
    mysqli_stmt_execute($activityStmt);
    mysqli_stmt_close($activityStmt);
    
    // ============================================================
    # SUCCESS RESPONSE
    // ============================================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Database restored successfully',
        'data' => [
            'filename' => $filename,
            'pre_restore_backup' => isset($preRestoreFilename) ? $preRestoreFilename : null,
            'total_statements' => $restoreResults['total_statements'],
            'successful_statements' => $restoreResults['successful_statements'],
            'failed_statements' => $restoreResults['failed_statements'],
            'tables_restored' => array_unique($restoreResults['tables_restored']),
            'tables_failed' => array_unique($restoreResults['tables_failed']),
            'errors' => $restoreResults['errors'],
            'completed_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_query($conn, "ROLLBACK");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
    
    // Log error
    error_log("Restore error: " . $e->getMessage());
    
    // Log failed restore attempt
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logSql = "INSERT INTO backup_logs (backup_file, file_size, file_type, backup_type, status, user_id, user_name, ip_address, created_at) 
               VALUES (?, ?, ?, 'restore', 'failed', ?, ?, ?, NOW())";
    $logStmt = mysqli_prepare($conn, $logSql);
    $fileSize = file_exists($filepath) ? filesize($filepath) : 0;
    $fileType = $extension === 'gz' ? 'sql.gz' : 'sql';
    mysqli_stmt_bind_param($logStmt, 'sissss', $filename, $fileSize, $fileType, $user_id, $user_name, $ip);
    mysqli_stmt_execute($logStmt);
    mysqli_stmt_close($logStmt);
    
    echo json_encode([
        'success' => false,
        'error' => 'Restore failed: ' . $e->getMessage(),
        'data' => [
            'filename' => $filename,
            'pre_restore_backup' => isset($preRestoreFilename) ? $preRestoreFilename : null,
            'failed_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

// ============================================================
# HELPER FUNCTIONS
// ============================================================

/**
 * Create a pre-restore backup
 */
function createPreRestoreBackup($conn, $backupDir) {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "pre_restore_backup_{$timestamp}.sql";
        $filepath = $backupDir . $filename;
        
        // Get all tables
        $tables = [];
        $result = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        mysqli_free_result($result);
        
        $backupSql = "-- Pre-Restore Backup: " . date('Y-m-d H:i:s') . "\n";
        $backupSql .= "-- Created before restoring: " . $filename . "\n\n";
        $backupSql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Get create table
            $createResult = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $createRow = mysqli_fetch_row($createResult);
            mysqli_free_result($createResult);
            
            $backupSql .= "DROP TABLE IF EXISTS `$table`;\n";
            $backupSql .= $createRow[1] . ";\n\n";
            
            // Get data
            $dataResult = mysqli_query($conn, "SELECT * FROM `$table`");
            $numFields = mysqli_num_fields($dataResult);
            $rowCount = 0;
            $insertValues = [];
            
            while ($row = mysqli_fetch_row($dataResult)) {
                $rowCount++;
                $values = [];
                for ($j = 0; $j < $numFields; $j++) {
                    $value = isset($row[$j]) ? mysqli_real_escape_string($conn, $row[$j]) : 'NULL';
                    $values[] = "'" . $value . "'";
                }
                $insertValues[] = "(" . implode(',', $values) . ")";
            }
            mysqli_free_result($dataResult);
            
            if ($rowCount > 0) {
                $backupSql .= "INSERT INTO `$table` VALUES \n";
                $backupSql .= implode(",\n", $insertValues) . ";\n\n";
            }
        }
        
        $backupSql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        if (file_put_contents($filepath, $backupSql) === false) {
            throw new Exception('Failed to write pre-restore backup');
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Read backup file (supports compressed files)
 */
function readBackupFile($filepath, $extension) {
    if ($extension === 'gz') {
        $content = '';
        $gz = gzopen($filepath, 'rb');
        if (!$gz) {
            return false;
        }
        while (!gzeof($gz)) {
            $content .= gzread($gz, 8192);
        }
        gzclose($gz);
        return $content;
    } elseif ($extension === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return false;
        }
        $content = $zip->getFromIndex(0);
        $zip->close();
        return $content;
    } else {
        return file_get_contents($filepath);
    }
}

/**
 * Split SQL statements
 */
function splitSqlStatements($sql) {
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    $inComment = false;
    $commentType = '';
    
    $sql = preg_replace('/^--.*$/m', '', $sql); // Remove comments
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        $nextChar = isset($sql[$i + 1]) ? $sql[$i + 1] : '';
        
        // Handle string literals
        if (!$inComment && ($char === "'" || $char === '"')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
                $currentStatement .= $char;
                continue;
            } elseif ($char === $stringChar && $nextChar !== $char) {
                $inString = false;
                $currentStatement .= $char;
                continue;
            }
        }
        
        // Handle comments
        if (!$inString && !$inComment) {
            if ($char === '-' && $nextChar === '-') {
                $inComment = true;
                $commentType = 'line';
                $i++;
                continue;
            } elseif ($char === '/' && $nextChar === '*') {
                $inComment = true;
                $commentType = 'block';
                $i++;
                continue;
            }
        }
        
        // End comment
        if ($inComment) {
            if ($commentType === 'line' && $char === "\n") {
                $inComment = false;
                $commentType = '';
                continue;
            } elseif ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                $inComment = false;
                $commentType = '';
                $i++;
                continue;
            }
            continue;
        }
        
        // Split on semicolon
        if (!$inString && $char === ';') {
            $trimmed = trim($currentStatement);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
            $currentStatement = '';
            continue;
        }
        
        $currentStatement .= $char;
    }
    
    // Add last statement if not empty
    $trimmed = trim($currentStatement);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }
    
    return $statements;
}

/**
 * Detect table name from SQL statement
 */
function detectTableName($statement) {
    $patterns = [
        '/CREATE\s+TABLE\s+`?([^`\s]+)`?/i',
        '/DROP\s+TABLE\s+`?([^`\s]+)`?/i',
        '/INSERT\s+INTO\s+`?([^`\s]+)`?/i',
        '/UPDATE\s+`?([^`\s]+)`?/i',
        '/DELETE\s+FROM\s+`?([^`\s]+)`?/i',
        '/ALTER\s+TABLE\s+`?([^`\s]+)`?/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $statement, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Analyze backup file
 */
function analyzeBackupFile($filepath, $extension) {
    $content = readBackupFile($filepath, $extension);
    if ($content === false) {
        return ['error' => 'Failed to read backup file'];
    }
    
    $statements = splitSqlStatements($content);
    $analysis = [
        'total_statements' => count($statements),
        'create_table_count' => 0,
        'drop_table_count' => 0,
        'insert_count' => 0,
        'update_count' => 0,
        'delete_count' => 0,
        'alter_count' => 0,
        'tables_found' => [],
        'estimated_size' => strlen($content)
    ];
    
    foreach ($statements as $statement) {
        if (preg_match('/CREATE\s+TABLE/i', $statement)) {
            $analysis['create_table_count']++;
            $table = detectTableName($statement);
            if ($table) {
                $analysis['tables_found'][] = $table;
            }
        } elseif (preg_match('/DROP\s+TABLE/i', $statement)) {
            $analysis['drop_table_count']++;
        } elseif (preg_match('/INSERT\s+INTO/i', $statement)) {
            $analysis['insert_count']++;
        } elseif (preg_match('/UPDATE\s+/i', $statement)) {
            $analysis['update_count']++;
        } elseif (preg_match('/DELETE\s+FROM/i', $statement)) {
            $analysis['delete_count']++;
        } elseif (preg_match('/ALTER\s+TABLE/i', $statement)) {
            $analysis['alter_count']++;
        }
    }
    
    $analysis['tables_found'] = array_unique($analysis['tables_found']);
    $analysis['estimated_size_formatted'] = formatFileSize($analysis['estimated_size']);
    
    return $analysis;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// ============================================================
# CLOSE CONNECTION
// ============================================================

if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}
?>