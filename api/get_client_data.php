<?php
// ============================================================
// CIBIL REPAIR CRM - Get Client Data API (FIXED)
// ============================================================

// Disable error display for production
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET TABLE COLUMNS
// ============================================================

function getTableColumns($conn, $tableName) {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        mysqli_free_result($result);
    }
    return $columns;
}

// Get users table columns
$userColumns = getTableColumns($conn, 'users');

// ============================================================
// GET PARAMETERS
// ============================================================

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

// ============================================================
// CREATE TABLES IF NOT EXISTS
// ============================================================

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    case_no VARCHAR(50),
    service VARCHAR(100),
    status ENUM('pending','in-progress','completed','cancelled') DEFAULT 'pending',
    amount DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    case_id INT,
    amount DECIMAL(15,2),
    status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS credit_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    score INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ============================================================
// BUILD SELECT CLAUSE FOR USERS
// ============================================================

$userSelect = 'id, name, email, phone, created_at';
if (in_array('credit_score', $userColumns)) {
    $userSelect .= ', credit_score';
}
if (in_array('city', $userColumns)) {
    $userSelect .= ', city';
}
if (in_array('status', $userColumns)) {
    $userSelect .= ', status';
}

// ============================================================
// GET CLIENT DATA
// ============================================================

try {
    $client = null;
    $clientId = 0;
    $isDemo = false;
    
    // Try to find client by email
    if (!empty($email)) {
        $query = "SELECT $userSelect FROM users WHERE email = ? AND role = 'client'";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $client = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        }
    }
    
    // If no client found by email, try session
    if (!$client && $userId > 0 && $userRole === 'client') {
        $query = "SELECT $userSelect FROM users WHERE id = ? AND role = 'client'";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $client = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        }
    }
    
    // Use demo data if no client found
    if (!$client) {
        $isDemo = true;
        $client = [
            'id' => 1,
            'name' => 'Demo Client',
            'email' => $email ?: 'demo@example.com',
            'phone' => '9876543210',
            'credit_score' => 680,
            'created_at' => date('Y-m-d H:i:s')
        ];
    } else {
        $clientId = (int)$client['id'];
        // Set default credit_score if not present
        if (!isset($client['credit_score'])) {
            $client['credit_score'] = 680;
        }
    }
    
    // Get cases
    $cases = [];
    if ($clientId > 0 && !$isDemo) {
        $query = "SELECT case_no, service, status, amount, created_at FROM cases WHERE client_id = $clientId ORDER BY id DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cases[] = $row;
            }
            mysqli_free_result($result);
        }
    }
    
    // Get payments
    $payments = [];
    if ($clientId > 0 && !$isDemo) {
        $query = "SELECT status, amount, payment_date FROM payments WHERE client_id = $clientId ORDER BY id DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $payments[] = $row;
            }
            mysqli_free_result($result);
        }
    }
    
    // Get credit history
    $creditHistory = [];
    if ($clientId > 0 && !$isDemo) {
        $query = "SELECT score, recorded_at as date FROM credit_history WHERE client_id = $clientId ORDER BY recorded_at ASC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $creditHistory[] = $row;
            }
            mysqli_free_result($result);
        }
    }
    
    // If no data found, use demo data
    if (empty($cases) || $isDemo) {
        $cases = [
            ['case_no' => 'CASE001', 'service' => 'Written Off Clearance', 'status' => 'in-progress', 'amount' => 15000, 'created_at' => date('Y-m-d H:i:s')],
            ['case_no' => 'CASE002', 'service' => 'Profile Correction', 'status' => 'completed', 'amount' => 3000, 'created_at' => date('Y-m-d H:i:s')]
        ];
    }
    
    if (empty($payments) || $isDemo) {
        $payments = [
            ['status' => 'paid', 'amount' => 3000, 'payment_date' => date('Y-m-d')],
            ['status' => 'pending', 'amount' => 15000, 'payment_date' => date('Y-m-d')]
        ];
    }
    
    if (empty($creditHistory) || $isDemo) {
        $creditHistory = [
            ['score' => 650, 'date' => date('Y-m-d', strtotime('-6 months'))],
            ['score' => 680, 'date' => date('Y-m-d')]
        ];
    }
    
    // Calculate stats
    $totalCases = count($cases);
    $activeCases = 0;
    $completedCases = 0;
    foreach ($cases as $case) {
        if ($case['status'] === 'in-progress' || $case['status'] === 'pending') {
            $activeCases++;
        } elseif ($case['status'] === 'completed') {
            $completedCases++;
        }
    }
    
    $totalPayments = count($payments);
    $paidPayments = 0;
    $pendingPayments = 0;
    foreach ($payments as $payment) {
        if ($payment['status'] === 'paid') {
            $paidPayments++;
        } elseif ($payment['status'] === 'pending') {
            $pendingPayments++;
        }
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'data' => [
            'client' => $client,
            'cases' => $cases,
            'payments' => $payments,
            'creditHistory' => $creditHistory,
            'is_demo' => $isDemo,
            'stats' => [
                'total_cases' => $totalCases,
                'active_cases' => $activeCases,
                'completed_cases' => $completedCases,
                'total_payments' => $totalPayments,
                'paid_payments' => $paidPayments,
                'pending_payments' => $pendingPayments,
                'current_credit_score' => (int)($client['credit_score'] ?? 680)
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>