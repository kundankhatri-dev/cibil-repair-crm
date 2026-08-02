<?php
// api-gateway.php - API Gateway with Authentication & Rate Limiting

class APIGateway {
    private $pdo;
    private $api_key;
    private $api_secret;
    private $rate_limit = 100;
    private $window = 3600; // 1 hour
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function authenticate($api_key, $api_secret) {
        $stmt = $this->pdo->prepare("SELECT * FROM api_keys WHERE api_key = ? AND status = 'active'");
        $stmt->execute([$api_key]);
        $key = $stmt->fetch();
        
        if (!$key) {
            return ['success' => false, 'error' => 'Invalid API key'];
        }
        
        if (password_verify($api_secret, $key['secret_key'])) {
            $this->api_key = $key;
            $this->rate_limit = $key['rate_limit'] ?? 100;
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Invalid secret'];
    }
    
    public function checkRateLimit($endpoint) {
        $stmt = $this->pdo->prepare("
            SELECT SUM(request_count) as total 
            FROM api_rate_limits 
            WHERE api_key_id = ? 
            AND endpoint = ? 
            AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$this->api_key['id'], $endpoint]);
        $result = $stmt->fetch();
        $count = $result['total'] ?? 0;
        
        if ($count >= $this->rate_limit) {
            return ['success' => false, 'error' => 'Rate limit exceeded', 'limit' => $this->rate_limit, 'current' => $count];
        }
        
        // Log request
        $stmt = $this->pdo->prepare("
            INSERT INTO api_rate_limits (api_key_id, endpoint, window_start, request_count) 
            VALUES (?, ?, NOW(), 1) 
            ON DUPLICATE KEY UPDATE request_count = request_count + 1
        ");
        $stmt->execute([$this->api_key['id'], $endpoint]);
        
        return ['success' => true];
    }
    
    public function logRequest($endpoint, $method, $response_code, $response_time) {
        $stmt = $this->pdo->prepare("
            INSERT INTO api_requests (api_key_id, endpoint, method, response_code, response_time, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->api_key['id'],
            $endpoint,
            $method,
            $response_code,
            $response_time,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Update last used
        $stmt = $this->pdo->prepare("UPDATE api_keys SET last_used_at = NOW(), requests_count = requests_count + 1 WHERE id = ?");
        $stmt->execute([$this->api_key['id']]);
    }
    
    public function route($path, $method) {
        $start_time = microtime(true);
        $response_code = 200;
        $response = [];
        
        // Parse path
        $parts = explode('/', trim($path, '/'));
        $resource = $parts[0] ?? '';
        $id = $parts[1] ?? null;
        
        // Check rate limit
        $rate_check = $this->checkRateLimit($path);
        if (!$rate_check['success']) {
            $response_code = 429;
            $response = $rate_check;
        } else {
            // Route to handlers
            try {
                switch ($resource) {
                    case 'leads':
                        $response = $this->handleLeads($method, $id);
                        break;
                    case 'customers':
                        $response = $this->handleCustomers($method, $id);
                        break;
                    case 'sales':
                        $response = $this->handleSales($method, $id);
                        break;
                    case 'partners':
                        $response = $this->handlePartners($method, $id);
                        break;
                    case 'credit-analysis':
                        $response = $this->handleCreditAnalysis($method, $id);
                        break;
                    case 'disputes':
                        $response = $this->handleDisputes($method, $id);
                        break;
                    case 'reports':
                        $response = $this->handleReports($method, $id);
                        break;
                    case 'health':
                        $response = ['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')];
                        break;
                    default:
                        $response_code = 404;
                        $response = ['error' => 'Endpoint not found'];
                }
            } catch (Exception $e) {
                $response_code = 500;
                $response = ['error' => 'Server error: ' . $e->getMessage()];
            }
        }
        
        // Log request
        $response_time = round((microtime(true) - $start_time) * 1000);
        $this->logRequest($path, $method, $response_code, $response_time);
        
        // Return response
        http_response_code($response_code);
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    // ── Handlers ──
    
    private function handleLeads($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM leads WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Lead not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $this->pdo->prepare("INSERT INTO leads (name, phone, email, service, source, status, created_at) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
                $stmt->execute([$data['name'], $data['phone'], $data['email'] ?? '', $data['service'] ?? '', $data['source'] ?? 'api']);
                return ['success' => true, 'id' => $this->pdo->lastInsertId()];
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handleCustomers($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Customer not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM customers ORDER BY created_at DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $this->pdo->prepare("INSERT INTO customers (name, email, phone, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? '']);
                return ['success' => true, 'id' => $this->pdo->lastInsertId()];
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handleSales($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM sales WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Sale not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM sales ORDER BY sale_date DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $this->pdo->prepare("INSERT INTO sales (customer_name, service, amount, sale_date, status) VALUES (?, ?, ?, ?, 'completed')");
                $stmt->execute([$data['customer'], $data['service'], $data['amount'], date('Y-m-d')]);
                return ['success' => true, 'id' => $this->pdo->lastInsertId()];
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handlePartners($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM partners WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Partner not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM partners ORDER BY total_conversions DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handleCreditAnalysis($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM credit_analysis_reports WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Analysis not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM credit_analysis_reports ORDER BY created_at DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handleDisputes($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM dispute_documents WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Dispute not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM dispute_documents ORDER BY created_at DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            default:
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function handleReports($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $stmt = $this->pdo->prepare("SELECT * FROM generated_reports WHERE id = ?");
                    $stmt->execute([$id]);
                    return $stmt->fetch() ?: ['error' => 'Report not found'];
                } else {
                    $stmt = $this->pdo->query("SELECT * FROM generated_reports ORDER BY generated_at DESC LIMIT 100");
                    return $stmt->fetchAll();
                }
            default:
                return ['error' => 'Method not allowed'];
        }
    }
}

// ── Router ──

$gateway = new APIGateway();

// Authentication
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
$api_secret = $_SERVER['HTTP_X_API_SECRET'] ?? '';

if (!$api_key || !$api_secret) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API key and secret required']);
    exit;
}

$auth = $gateway->authenticate($api_key, $api_secret);
if (!$auth['success']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode($auth);
    exit;
}

// Route request
$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$gateway->route($path, $method);
?>
