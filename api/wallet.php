<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// GET request - Get balance
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'balance' => floatval($row['balance'])
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'balance' => 0
        ]);
    }
    exit;
}

// POST request - Add or withdraw
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$action = isset($input['action']) ? trim($input['action']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$method = isset($input['method']) ? trim($input['method']) : 'Cash';
$description = isset($input['description']) ? trim($input['description']) : '';

if (empty($action) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Action and amount required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS wallet (
    id INT PRIMARY KEY DEFAULT 1,
    balance DECIMAL(12,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE,
    description TEXT,
    amount DECIMAL(12,2),
    type ENUM('credit', 'debit'),
    method VARCHAR(50),
    balance_after DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure wallet exists
$wallet = mysqli_query($conn, "SELECT * FROM wallet WHERE id = 1");
if (mysqli_num_rows($wallet) == 0) {
    mysqli_query($conn, "INSERT INTO wallet (id, balance) VALUES (1, 0)");
}

$walletData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1"));
$currentBalance = floatval($walletData['balance']);

if ($action == 'add') {
    $newBalance = $currentBalance + $amount;
    $type = 'credit';
    
    if (mysqli_query($conn, "UPDATE wallet SET balance = $newBalance WHERE id = 1")) {
        mysqli_query($conn, "INSERT INTO transactions (date, description, amount, type, method, balance_after) 
                             VALUES (CURDATE(), '$description', $amount, '$type', '$method', $newBalance)");
        echo json_encode([
            'success' => true,
            'message' => 'Money added',
            'amount' => $amount,
            'new_balance' => $newBalance
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} elseif ($action == 'withdraw') {
    if ($amount > $currentBalance) {
        echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
        exit;
    }
    $newBalance = $currentBalance - $amount;
    $type = 'debit';
    
    if (mysqli_query($conn, "UPDATE wallet SET balance = $newBalance WHERE id = 1")) {
        mysqli_query($conn, "INSERT INTO transactions (date, description, amount, type, method, balance_after) 
                             VALUES (CURDATE(), '$description', $amount, '$type', '$method', $newBalance)");
        echo json_encode([
            'success' => true,
            'message' => 'Money withdrawn',
            'amount' => $amount,
            'new_balance' => $newBalance
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

mysqli_close($conn);
exit;
?>