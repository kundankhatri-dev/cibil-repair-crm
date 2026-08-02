<?php
// /api/integrations.php
header('Content-Type: application/json');

class IntegrationHub {
    private $config;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->config = $this->loadConfig();
    }
    
    private function loadConfig() {
        $result = $this->conn->query("SELECT * FROM integrations_config");
        $config = [];
        while ($row = $result->fetch_assoc()) {
            $config[$row['name']] = $row['value'];
        }
        return $config;
    }
    
    // ── PAYMENT GATEWAY ──
    public function processPayment($data) {
        $gateway = isset($data['gateway']) ? $data['gateway'] : 'razorpay';
        
        switch ($gateway) {
            case 'razorpay':
                return $this->razorpayPayment($data);
            case 'stripe':
                return $this->stripePayment($data);
            case 'paypal':
                return $this->paypalPayment($data);
            default:
                return ['success' => false, 'error' => 'Unsupported gateway'];
        }
    }
    
    private function razorpayPayment($data) {
        // Razorpay integration
        $key_id = $this->config['razorpay_key_id'] ?? '';
        $key_secret = $this->config['razorpay_key_secret'] ?? '';
        
        $amount = $data['amount'] * 100; // Convert to paise
        $order = [
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'order_' . uniqid()
        ];
        
        // Call Razorpay API
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function stripePayment($data) {
        // Stripe integration
        require_once '../vendor/stripe/stripe-php/init.php';
        
        \Stripe\Stripe::setApiKey($this->config['stripe_secret_key'] ?? '');
        
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $data['amount'] * 100,
                'currency' => 'inr',
                'description' => $data['description'] ?? 'Payment'
            ]);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function paypalPayment($data) {
        // PayPal integration
        $client_id = $this->config['paypal_client_id'] ?? '';
        $client_secret = $this->config['paypal_client_secret'] ?? '';
        
        // Get access token
        $ch = curl_init('https://api.sandbox.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $auth = json_decode($response, true);
        
        if (!isset($auth['access_token'])) {
            return ['success' => false, 'error' => 'Failed to authenticate with PayPal'];
        }
        
        $access_token = $auth['access_token'];
        
        // Create order
        $order = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $data['amount']
                    ],
                    'description' => $data['description'] ?? 'Payment'
                ]
            ]
        ];
        
        $ch = curl_init('https://api.sandbox.paypal.com/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    // ── SMS GATEWAY ──
    public function sendSMS($data) {
        $provider = isset($data['provider']) ? $data['provider'] : 'twilio';
        
        switch ($provider) {
            case 'twilio':
                return $this->twilioSMS($data);
            case 'msg91':
                return $this->msg91SMS($data);
            default:
                return ['success' => false, 'error' => 'Unsupported SMS provider'];
        }
    }
    
    private function twilioSMS($data) {
        $account_sid = $this->config['twilio_account_sid'] ?? '';
        $auth_token = $this->config['twilio_auth_token'] ?? '';
        $from = $this->config['twilio_phone_number'] ?? '';
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
        
        $post = [
            'To' => $data['to'],
            'From' => $from,
            'Body' => $data['message']
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function msg91SMS($data) {
        $auth_key = $this->config['msg91_auth_key'] ?? '';
        $sender_id = $this->config['msg91_sender_id'] ?? '';
        $route = $this->config['msg91_route'] ?? '4';
        
        $url = "https://api.msg91.com/api/v5/flow/";
        
        $post = [
            'sender' => $sender_id,
            'mobiles' => $data['to'],
            'message' => $data['message'],
            'route' => $route
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'authkey: ' . $auth_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    // ── WHATSAPP GATEWAY ──
    public function sendWhatsApp($data) {
        $provider = isset($data['provider']) ? $data['provider'] : 'whatsapp_business';
        
        switch ($provider) {
            case 'whatsapp_business':
                return $this->whatsappBusiness($data);
            default:
                return ['success' => false, 'error' => 'Unsupported WhatsApp provider'];
        }
    }
    
    private function whatsappBusiness($data) {
        $phone_number_id = $this->config['whatsapp_phone_number_id'] ?? '';
        $access_token = $this->config['whatsapp_access_token'] ?? '';
        
        $url = "https://graph.facebook.com/v18.0/$phone_number_id/messages";
        
        $post = [
            'messaging_product' => 'whatsapp',
            'to' => $data['to'],
            'type' => 'text',
            'text' => [
                'body' => $data['message']
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// ── API HANDLER ──
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
    
    $hub = new IntegrationHub($conn);
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'payment':
            $result = $hub->processPayment($input);
            break;
        case 'sms':
            $result = $hub->sendSMS($input);
            break;
        case 'whatsapp':
            $result = $hub->sendWhatsApp($input);
            break;
        default:
            $result = ['success' => false, 'error' => 'Invalid action'];
            break;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?>