<?php
// ============================================================
// CIBIL REPAIR CRM - Client Portal API (COMPLETE)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

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
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get client email from request
$client_email = $_GET['email'] ?? $_POST['email'] ?? null;
if (!$client_email) {
    $input = json_decode(file_get_contents('php://input'), true);
    $client_email = $input['email'] ?? null;
}

if (!$client_email) {
    echo json_encode(['error' => 'Client email is required']);
    exit;
}

// Verify client exists
$clientCheck = mysqli_prepare($conn, "SELECT id, name, email, status FROM users WHERE email = ? AND role = 'client'");
mysqli_stmt_bind_param($clientCheck, 's', $client_email);
mysqli_stmt_execute($clientCheck);
$clientResult = mysqli_stmt_get_result($clientCheck);
$client = mysqli_fetch_assoc($clientResult);
mysqli_stmt_close($clientCheck);

if (!$client) {
    echo json_encode(['error' => 'Client not found or unauthorized']);
    exit;
}

if ($client['status'] !== 'active' && $client['status'] !== 'approved') {
    echo json_encode(['error' => 'Your account is not active. Please contact support.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================================
// CREATE TABLES ACTION
// ============================================================

if ($action === 'createTables') {
    $results = [];
    
    // Client cases table
    if (mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_cases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_email VARCHAR(255) NOT NULL,
        case_no VARCHAR(50) NOT NULL,
        service VARCHAR(255),
        amount DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )")) {
        $results['client_cases'] = 'Created';
    } else {
        $results['client_cases'] = mysqli_error($conn);
    }
    
    // Client payments table
    if (mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_email VARCHAR(255) NOT NULL,
        case_id INT DEFAULT 0,
        amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'completed',
        transaction_id VARCHAR(100),
        payment_method VARCHAR(50) DEFAULT 'UPI',
        payment_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )")) {
        $results['client_payments'] = 'Created';
    } else {
        $results['client_payments'] = mysqli_error($conn);
    }
    
    // Client documents table
    if (mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_email VARCHAR(255) NOT NULL,
        doc_type VARCHAR(100) NOT NULL,
        file_name VARCHAR(255),
        file_data LONGTEXT,
        file_type VARCHAR(50),
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_doc (client_email, doc_type)
    )")) {
        $results['client_documents'] = 'Created';
    } else {
        $results['client_documents'] = mysqli_error($conn);
    }
    
    // Support tickets table
    if (mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255),
        message TEXT,
        status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )")) {
        $results['support_tickets'] = 'Created';
    } else {
        $results['support_tickets'] = mysqli_error($conn);
    }
    
    // Credit score history table
    if (mysqli_query($conn, "CREATE TABLE IF NOT EXISTS credit_score_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_email VARCHAR(255) NOT NULL,
        score INT,
        recorded_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )")) {
        $results['credit_score_history'] = 'Created';
    } else {
        $results['credit_score_history'] = mysqli_error($conn);
    }
    
    echo json_encode(['success' => true, 'message' => 'Tables created', 'results' => $results]);
    exit;
}

// ============================================================
// GET OPERATIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'getClientCases':
            $stmt = mysqli_prepare($conn, "SELECT * FROM client_cases WHERE client_email = ? ORDER BY id DESC");
            mysqli_stmt_bind_param($stmt, 's', $client_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $cases = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $cases[] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'data' => $cases]);
            break;
            
        case 'getClientPayments':
            $stmt = mysqli_prepare($conn, "SELECT * FROM client_payments WHERE client_email = ? ORDER BY id DESC");
            mysqli_stmt_bind_param($stmt, 's', $client_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $payments = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $payments[] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'data' => $payments]);
            break;
            
        case 'getCreditScoreHistory':
            $stmt = mysqli_prepare($conn, "SELECT * FROM credit_score_history WHERE client_email = ? ORDER BY recorded_date ASC");
            mysqli_stmt_bind_param($stmt, 's', $client_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $history = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'data' => $history]);
            break;
            
        case 'getDocuments':
            $stmt = mysqli_prepare($conn, "SELECT doc_type, file_name, file_type, uploaded_at FROM client_documents WHERE client_email = ?");
            mysqli_stmt_bind_param($stmt, 's', $client_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $docs = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $docs[$row['doc_type']] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'data' => $docs]);
            break;
            
        case 'getTickets':
            $stmt = mysqli_prepare($conn, "SELECT * FROM support_tickets WHERE client_email = ? ORDER BY id DESC");
            mysqli_stmt_bind_param($stmt, 's', $client_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $tickets = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $tickets[] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'data' => $tickets]);
            break;
            
        case 'checkTables':
            $tables = [];
            $result = mysqli_query($conn, "SHOW TABLES");
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
            echo json_encode(['success' => true, 'tables' => $tables]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action', 'available_actions' => [
                'createTables', 'getClientCases', 'getClientPayments', 'getCreditScoreHistory',
                'getDocuments', 'getTickets', 'checkTables'
            ]]);
    }
    exit;
}

// ============================================================
// POST OPERATIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'uploadDocument':
            $doc_type = isset($input['doc_type']) ? trim($input['doc_type']) : '';
            $file_data = $input['file_data'] ?? '';
            $file_name = $input['file_name'] ?? '';
            $file_type = $input['file_type'] ?? '';
            
            if (empty($doc_type) || empty($file_data)) {
                echo json_encode(['success' => false, 'error' => 'Document type and data are required']);
                break;
            }
            
            // Check if document exists
            $check = mysqli_prepare($conn, "SELECT id FROM client_documents WHERE client_email = ? AND doc_type = ?");
            mysqli_stmt_bind_param($check, 'ss', $client_email, $doc_type);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            $exists = mysqli_stmt_num_rows($check) > 0;
            mysqli_stmt_close($check);
            
            if ($exists) {
                $stmt = mysqli_prepare($conn, "UPDATE client_documents SET file_name = ?, file_data = ?, file_type = ?, uploaded_at = NOW() WHERE client_email = ? AND doc_type = ?");
                mysqli_stmt_bind_param($stmt, 'sssss', $file_name, $file_data, $file_type, $client_email, $doc_type);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO client_documents (client_email, doc_type, file_name, file_data, file_type) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'sssss', $client_email, $doc_type, $file_name, $file_data, $file_type);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to upload document: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'deleteDocument':
            $doc_type = isset($input['doc_type']) ? trim($input['doc_type']) : '';
            if (empty($doc_type)) {
                echo json_encode(['success' => false, 'error' => 'Document type is required']);
                break;
            }
            
            $stmt = mysqli_prepare($conn, "DELETE FROM client_documents WHERE client_email = ? AND doc_type = ?");
            mysqli_stmt_bind_param($stmt, 'ss', $client_email, $doc_type);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete document: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'addPayment':
            $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
            $case_id = isset($input['case_id']) ? intval($input['case_id']) : 0;
            $payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'UPI';
            $transaction_id = 'TXN' . time() . rand(100, 999);
            
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'error' => 'Valid amount is required']);
                break;
            }
            
            $stmt = mysqli_prepare($conn, "INSERT INTO client_payments (client_email, case_id, amount, status, transaction_id, payment_method, payment_date) VALUES (?, ?, ?, 'completed', ?, ?, CURDATE())");
            mysqli_stmt_bind_param($stmt, 'sids', $client_email, $case_id, $amount, $transaction_id, $payment_method);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Payment successful', 'transaction_id' => $transaction_id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to process payment: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'createTicket':
            $subject = isset($input['subject']) ? trim($input['subject']) : '';
            $message = isset($input['message']) ? trim($input['message']) : '';
            
            if (empty($subject) || empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
                break;
            }
            
            $stmt = mysqli_prepare($conn, "INSERT INTO support_tickets (client_email, subject, message, status) VALUES (?, ?, ?, 'open')");
            mysqli_stmt_bind_param($stmt, 'sss', $client_email, $subject, $message);
            
            if (mysqli_stmt_execute($stmt)) {
                $ticket_id = mysqli_insert_id($conn);
                echo json_encode(['success' => true, 'message' => 'Ticket created successfully', 'ticket_id' => $ticket_id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create ticket: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action', 'available_actions' => [
                'uploadDocument', 'deleteDocument', 'addPayment', 'createTicket'
            ]]);
    }
    exit;
}

mysqli_close($conn);
?>