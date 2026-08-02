<?php
// ============================================================
// CIBIL REPAIR CRM - Clear Activity Logs API (COMPLETE)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'clear_activity_logs.php') {
//     http_response_code(403);
//     exit('Direct access forbidden.');
// }

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

// Check authentication
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Check admin role
if (!in_array($role, ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
# VALIDATE INPUT PARAMETERS
// ============================================================

$mode = isset($input['mode']) ? trim($input['mode']) : 'all';
$days = isset($input['days']) ? intval($input['days']) : 30;
$startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
$endDate = isset($input['end_date']) ? trim($input['end_date']) : '';
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$confirm = isset($input['confirm']) ? filter_var($input['confirm'], FILTER_VALIDATE_BOOLEAN) : false;
$archiveBeforeDelete = isset($input['archive_before_delete']) ? filter_var($input['archive_before_delete'], FILTER_VALIDATE_BOOLEAN) : true;
$dryRun = isset($input['dry_run']) ? filter_var($input['dry_run'], FILTER_VALIDATE_BOOLEAN) : false;
$limit = isset($input['limit']) ? intval($input['limit']) : 10000;

// ============================================================
# VALIDATE CONFIRMATION
// ============================================================

if (!$confirm && !$dryRun) {
    echo json_encode(['success' => false, 'error' => 'Confirmation required. Set confirm=true to proceed.']);
    exit;
}

// ============================================================
# BUILD CONDITIONS
// ============================================================

$whereConditions = [];
$params = [];
$types = '';
$whereClause = '';

if ($mode === 'all') {
    $whereConditions[] = "1=1";
} elseif ($mode === 'older_than') {
    if ($days < 1) {
        echo json_encode(['success' => false, 'error' => 'Days must be at least 1']);
        exit;
    }
    $whereConditions[] = "created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $days;
    $types .= 'i';
} elseif ($mode === 'date_range') {
    if (empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'error' => 'Start date and end date are required']);
        exit;
    }
    if (!strtotime($startDate) || !strtotime($endDate)) {
        echo json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }
    if ($startDate > $endDate) {
        echo json_encode(['success' => false, 'error' => 'Start date must be before end date']);
        exit;
    }
    $whereConditions[] = "DATE(created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
} elseif ($mode === 'user') {
    if ($userId < 1) {
        echo json_encode(['success' => false, 'error' => 'Valid user_id is required']);
        exit;
    }
    $whereConditions[] = "user_id = ?";
    $params[] = $userId;
    $types .= 'i';
} elseif ($mode === 'action') {
    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => 'Action is required']);
        exit;
    }
    $whereConditions[] = "action LIKE ?";
    $params[] = '%' . $action . '%';
    $types .= 's';
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid mode. Allowed: all, older_than, date_range, user, action']);
    exit;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// ============================================================
# GET COUNT BEFORE DELETE
// ============================================================

$countSql = "SELECT COUNT(*) as total FROM activity_logs $whereClause";
$countStmt = mysqli_prepare($conn, $countSql);
if (!empty($params)) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalToDelete = mysqli_fetch_assoc($countResult)['total'] ?? 0;
mysqli_stmt_close($countStmt);

// ============================================================
# CHECK IF ANY LOGS TO DELETE
// ============================================================

if ($totalToDelete === 0) {
    echo json_encode([
        'success' => true,
        'message' => 'No logs found matching the criteria',
        'data' => [
            'mode' => $mode,
            'logs_found' => 0,
            'deleted' => 0,
            'archived' => 0,
            'dry_run' => $dryRun
        ]
    ]);
    exit;
}

// ============================================================
# DRY RUN
// ============================================================

if ($dryRun) {
    $sampleSql = "SELECT id, user_name, action, details, ip_address, created_at 
                  FROM activity_logs $whereClause 
                  ORDER BY created_at DESC 
                  LIMIT 10";
    $sampleStmt = mysqli_prepare($conn, $sampleSql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($sampleStmt, $types, ...$params);
    }
    mysqli_stmt_execute($sampleStmt);
    $sampleResult = mysqli_stmt_get_result($sampleStmt);
    $sampleLogs = [];
    while ($row = mysqli_fetch_assoc($sampleResult)) {
        $sampleLogs[] = $row;
    }
    mysqli_stmt_close($sampleStmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Dry run completed',
        'data' => [
            'mode' => $mode,
            'logs_found' => $totalToDelete,
            'sample_logs' => $sampleLogs,
            'dry_run' => true,
            'message' => 'This was a dry run. No logs were deleted. Set dry_run=false to proceed.'
        ]
    ]);
    exit;
}

// ============================================================
# START TRANSACTION
// ============================================================

mysqli_begin_transaction($conn);

try {
    $deletedCount = 0;
    $archivedCount = 0;
    
    // ============================================================
    # ARCHIVE LOGS BEFORE DELETION (if enabled)
    // ============================================================
    
    if ($archiveBeforeDelete) {
        // Create archive table if it doesn't exist
        $createArchiveSql = "
            CREATE TABLE IF NOT EXISTS activity_logs_archive (
                id INT PRIMARY KEY AUTO_INCREMENT,
                original_id INT,
                user_id INT NULL,
                user_name VARCHAR(100) NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                archived_by INT,
                archive_reason VARCHAR(255),
                INDEX idx_original_id (original_id),
                INDEX idx_archived_at (archived_at),
                INDEX idx_created_at (created_at)
            )";
        mysqli_query($conn, $createArchiveSql);
        
        // Archive logs
        $archiveReason = "Cleared via API - Mode: $mode";
        if ($mode === 'older_than') {
            $archiveReason .= " - Older than $days days";
        } elseif ($mode === 'date_range') {
            $archiveReason .= " - Date range: $startDate to $endDate";
        } elseif ($mode === 'user') {
            $archiveReason .= " - User ID: $userId";
        } elseif ($mode === 'action') {
            $archiveReason .= " - Action: $action";
        }
        
        // Insert into archive table
        $archiveSql = "
            INSERT INTO activity_logs_archive 
            (original_id, user_id, user_name, action, details, ip_address, user_agent, created_at, archived_by, archive_reason)
            SELECT 
                id, user_id, user_name, action, details, ip_address, user_agent, created_at, ?, ?
            FROM activity_logs
            $whereClause
        ";
        $archiveStmt = mysqli_prepare($conn, $archiveSql);
        $archiveParams = array_merge([$user_id, $archiveReason], $params);
        if (!empty($archiveParams)) {
            $archiveTypes = 'ii' . $types;
            mysqli_stmt_bind_param($archiveStmt, $archiveTypes, ...$archiveParams);
        } else {
            mysqli_stmt_bind_param($archiveStmt, 'ii', $user_id, $archiveReason);
        }
        mysqli_stmt_execute($archiveStmt);
        $archivedCount = mysqli_stmt_affected_rows($archiveStmt);
        mysqli_stmt_close($archiveStmt);
    }
    
    // ============================================================
    # DELETE LOGS
    // ============================================================
    
    $deleteSql = "DELETE FROM activity_logs $whereClause";
    $deleteStmt = mysqli_prepare($conn, $deleteSql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($deleteStmt, $types, ...$params);
    }
    mysqli_stmt_execute($deleteStmt);
    $deletedCount = mysqli_stmt_affected_rows($deleteStmt);
    mysqli_stmt_close($deleteStmt);
    
    // ============================================================
    # COMMIT TRANSACTION
    // ============================================================
    
    mysqli_commit($conn);
    
    // ============================================================
    # LOG ACTIVITY
    // ============================================================
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Cleared activity logs - Mode: $mode, Deleted: $deletedCount logs";
    if ($archiveBeforeDelete) {
        $logDetails .= ", Archived: $archivedCount logs";
    }
    
    // Create activity_logs table if not exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                         VALUES ($user_id, '$user_name', 'Activity logs cleared', '$logDetails', '$ip', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Activity logs cleared successfully',
        'data' => [
            'mode' => $mode,
            'logs_found' => $totalToDelete,
            'deleted' => $deletedCount,
            'archived' => $archivedCount,
            'archived_before_delete' => $archiveBeforeDelete,
            'dry_run' => false,
            'message' => $deletedCount > 0 
                ? "Successfully deleted $deletedCount activity logs" 
                : "No logs were deleted"
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to clear activity logs: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>