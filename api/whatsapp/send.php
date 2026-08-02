<?php
// api/whatsapp/send.php
require_once 'config.php';

class WhatsAppService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    private function formatPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 10) $phone = '91' . $phone;
        if (strlen($phone) == 12 && substr($phone, 0, 2) == '91') return $phone;
        if (strlen($phone) == 13 && substr($phone, 0, 3) == '091') return substr($phone, 1);
        return '91' . substr($phone, -10);
    }
    
    private function logMessage($to, $message, $type, $success, $response, $template_name = null) {
        $query = "INSERT INTO whatsapp_logs (phone_number, message, type, template_name, status, response, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($this->conn, $query);
        $status = $success ? 'sent' : 'failed';
        $response_json = json_encode($response);
        mysqli_stmt_bind_param($stmt, "ssssss", $to, $message, $type, $template_name, $status, $response_json);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    public function sendMessage($to, $message, $type = 'text', $template_name = null, $template_params = []) {
        if (empty(WHATSAPP_TOKEN) || empty(WHATSAPP_PHONE_NUMBER_ID)) {
            // Fallback to direct WhatsApp link (no API)
            return ['success' => false, 'fallback' => true, 'message' => 'API not configured', 'link' => "https://wa.me/" . $this->formatPhoneNumber($to) . "?text=" . urlencode($message)];
        }
        
        $to = $this->formatPhoneNumber($to);
        
        if ($type === 'template' && $template_name) {
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template_name,
                    'language' => ['code' => 'en'],
                    'components' => []
                ]
            ];
            if (!empty($template_params)) {
                $data['template']['components'][] = [
                    'type' => 'body',
                    'parameters' => array_map(function($param) {
                        return ['type' => 'text', 'text' => $param];
                    }, $template_params)
                ];
            }
        } else {
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message]
            ];
        }
        
        $ch = curl_init(WHATSAPP_API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $success = ($http_code == 200);
        $response_data = json_decode($response, true);
        
        $this->logMessage($to, $message, $type, $success, $response_data, $template_name);
        
        return [
            'success' => $success,
            'http_code' => $http_code,
            'response' => $response_data,
            'error' => $curl_error ?: null
        ];
    }
    
    public function sendLeadAlert($phone, $customer_name, $service, $lead_id) {
        $message = "🔔 *New Lead Alert!*\n\n";
        $message .= "📋 *Customer:* $customer_name\n";
        $message .= "🛠️ *Service:* $service\n";
        $message .= "🔗 *Lead ID:* #$lead_id\n\n";
        $message .= "Please check your dashboard and follow up promptly.\n\n";
        $message .= "_CIBIL Repair_";
        
        return $this->sendMessage($phone, $message);
    }
    
    public function sendPayoutNotification($phone, $amount, $payout_id) {
        $message = "💰 *Payout Processed!*\n\n";
        $message .= "Amount: *₹" . number_format($amount, 2) . "*\n";
        $message .= "Payout ID: #$payout_id\n\n";
        $message .= "Amount will be credited within 2-3 business days.\n\n";
        $message .= "Thank you for your partnership! 🙏";
        
        return $this->sendMessage($phone, $message);
    }
    
    public function sendLeadConvertedNotification($phone, $customer_name, $commission) {
        $message = "🎉 *Lead Converted!*\n\n";
        $message .= "Customer: *$customer_name*\n";
        $message .= "Commission Earned: *₹" . number_format($commission, 2) . "*\n\n";
        $message .= "Great work! Keep it up! 🚀";
        
        return $this->sendMessage($phone, $message);
    }
    
    public function sendWelcomeMessage($phone, $partner_name, $referral_code) {
        $message = "🎉 *Welcome to CIBIL Repair Partner Program!* 🎉\n\n";
        $message .= "Dear *$partner_name*,\n\n";
        $message .= "Thank you for joining!\n\n";
        $message .= "🔑 *Referral Code:* `$referral_code`\n";
        $message .= "🔗 *Login:* " . (getenv('APP_URL') ?: 'https://cibilrepair.in') . "/login.html\n\n";
        $message .= "Start adding leads and earning commissions! 💰\n\n";
        $message .= "_CIBIL Repair Team_";
        
        return $this->sendMessage($phone, $message);
    }
}

// API endpoint handler (does NOT affect your existing pages)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config.php';
    $whatsapp = new WhatsAppService($conn);
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'send':
            echo json_encode($whatsapp->sendMessage($input['to'], $input['message']));
            break;
        case 'lead_alert':
            echo json_encode($whatsapp->sendLeadAlert($input['phone'], $input['customer_name'], $input['service'], $input['lead_id']));
            break;
        case 'payout':
            echo json_encode($whatsapp->sendPayoutNotification($input['phone'], $input['amount'], $input['payout_id']));
            break;
        case 'lead_converted':
            echo json_encode($whatsapp->sendLeadConvertedNotification($input['phone'], $input['customer_name'], $input['commission']));
            break;
        case 'welcome':
            echo json_encode($whatsapp->sendWelcomeMessage($input['phone'], $input['partner_name'], $input['referral_code']));
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}
?>