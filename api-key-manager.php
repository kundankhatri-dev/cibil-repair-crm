<?php
// api-key-manager.php - API Key Management

$pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");

function generateApiKey() {
    return bin2hex(random_bytes(16));
}

function generateApiSecret() {
    return bin2hex(random_bytes(24));
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'generate':
            $data = json_decode(file_get_contents('php://input'), true);
            $name = $data['name'] ?? 'API Key';
            
            $api_key = generateApiKey();
            $secret_key = generateApiSecret();
            $hashed_secret = password_hash($secret_key, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO api_keys (api_key, secret_key, name, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
            $stmt->execute([$api_key, $hashed_secret, $name]);
            
            echo json_encode([
                'success' => true,
                'api_key' => $api_key,
                'secret_key' => $secret_key,
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'list':
            $stmt = $pdo->query("SELECT id, api_key, name, status, requests_count, last_used_at, created_at FROM api_keys ORDER BY created_at DESC");
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'revoke':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE api_keys SET status = 'revoked' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>
