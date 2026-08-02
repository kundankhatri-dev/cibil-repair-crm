<?php
// api/get_activity_logs.php
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    global $conn;
    
    if (!isset($conn) || !$conn instanceof mysqli) {
        if (defined('DB_HOST')) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
        } else {
            throw new Exception('Database connection not available');
        }
    }
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_logs'");
    if ($tableCheck->num_rows === 0) {
        // Try alternative table name
        $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if ($tableCheck->num_rows === 0) {
            echo json_encode([
                'success' => true,
                'logs' => [],
                'data' => [],
                'total' => 0,
                'message' => 'Table activity_logs not found'
            ]);
            exit;
        }
        $tableName = 'activity_log';
    } else {
        $tableName = 'activity_logs';
    }
    
    // Get columns to find the right column names
    $columns = [];
    $colResult = $conn->query("SHOW COLUMNS FROM $tableName");
    while ($row = $colResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Find the date column
    $dateColumn = 'created_at';
    if (!in_array('created_at', $columns)) {
        $possibleDates = ['timestamp', 'date', 'log_date', 'entry_date', 'time'];
        foreach ($possibleDates as $col) {
            if (in_array($col, $columns)) {
                $dateColumn = $col;
                break;
            }
        }
    }
    
    // Find the user column
    $userColumn = 'user_id';
    if (!in_array('user_id', $columns)) {
        $possibleUsers = ['user', 'admin_id', 'user_name', 'username'];
        foreach ($possibleUsers as $col) {
            if (in_array($col, $columns)) {
                $userColumn = $col;
                break;
            }
        }
    }
    
    $sql = "SELECT * FROM $tableName ORDER BY $dateColumn DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('SQL Error: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $countResult = $conn->query("SELECT COUNT(*) as total FROM $tableName");
    $total = $countResult ? $countResult->fetch_assoc()['total'] : 0;
    
    echo json_encode([
        'success' => true,
        'logs' => $data,
        'data' => $data,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'table' => $tableName
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
if (isset($conn) && $conn instanceof mysqli) $conn->close();
exit;
?>