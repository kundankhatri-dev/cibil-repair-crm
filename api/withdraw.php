<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$method = isset($input['method']) ? trim($input['method']) : 'Bank Transfer';
$description = isset($input['description']) ? trim($input['description']) : 'Withdrawal';

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit;
}

// Get wallet balance
$wallet = mysqli_query($conn, "SELECT balance FROM wallet WHERE id = 1");
if (mysqli_num_rows($wallet) == 0) {
    mysqli_query($conn, "INSERT INTO wallet (id, balance) VALUES (1, 0)");
    $balance = 0;
} else {
    $row = mysqli_fetch_assoc($wallet);
    $balance = floatval($row['balance']);
}

if ($amount > $balance) {
    echo json_encode(['success' => false, 'error' => 'Insufficient balance. Available: ₹' . number_format($balance, 2)]);
    exit;
}

$newBalance = $balance - $amount;

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

if (mysqli_query($conn, "UPDATE wallet SET balance = $newBalance WHERE id = 1")) {
    mysqli_query($conn, "INSERT INTO transactions (date, description, amount, type, method, balance_after) 
                         VALUES (CURDATE(), '$description', $amount, 'debit', '$method', $newBalance)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal successful',
        'amount' => $amount,
        'method' => $method,
        'new_balance' => $newBalance,
        'formatted_balance' => '₹' . number_format($newBalance, 2)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>