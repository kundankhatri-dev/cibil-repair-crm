<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch($action) {
    case 'create':
        $clientName = isset($input['clientName']) ? $input['clientName'] : 'Test Client';
        $amount = isset($input['amount']) ? (float)$input['amount'] : 1000;
        $service = isset($input['service']) ? $input['service'] : 'CIBIL Repair';
        $status = isset($input['status']) ? $input['status'] : 'pending';
        $date = isset($input['date']) ? $input['date'] : date('Y-m-d');
        $transactionId = isset($input['transaction_id']) ? $input['transaction_id'] : 'TXN' . rand(100000, 999999);
        $paymentMode = isset($input['payment_mode']) ? $input['payment_mode'] : 'Cash';
        $package = isset($input['package']) ? $input['package'] : 'Basic';
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 1;
        
        $sql = "INSERT INTO payments (clientName, amount, service, status, date, transaction_id, payment_mode, package, user_id) 
                VALUES ('$clientName', $amount, '$service', '$status', '$date', '$transactionId', '$paymentMode', '$package', $userId)";
        
        if (mysqli_query($conn, $sql)) {
            $id = mysqli_insert_id($conn);
            echo json_encode(['success' => true, 'message' => 'Payment created', 'id' => $id, 'transaction_id' => $transactionId]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;
        
    case 'list':
        $result = mysqli_query($conn, "SELECT * FROM payments ORDER BY id DESC LIMIT 50");
        $payments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $payments[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $payments]);
        break;
        
    case 'get':
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            break;
        }
        $result = mysqli_query($conn, "SELECT * FROM payments WHERE id = $id");
        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
        }
        break;
        
    case 'update':
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $status = isset($input['status']) ? $input['status'] : '';
        if ($id <= 0 || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'ID and status required']);
            break;
        }
        mysqli_query($conn, "UPDATE payments SET status = '$status' WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Payment updated']);
        break;
        
    case 'delete':
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            break;
        }
        mysqli_query($conn, "DELETE FROM payments WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Payment deleted']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Use: create, list, get, update, delete']);
}

mysqli_close($conn);
exit;
?>