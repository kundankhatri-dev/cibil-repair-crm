<?php
// whatsapp-api.php - WhatsApp Business API Integration

class WhatsAppAPI {
    private $api_url = 'https://graph.facebook.com/v18.0/';
    private $phone_number_id;
    private $access_token;
    private $pdo;
    
    public function __construct() {
        $this->phone_number_id = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: 'YOUR_PHONE_NUMBER_ID';
        $this->access_token = getenv('WHATSAPP_ACCESS_TOKEN') ?: 'YOUR_ACCESS_TOKEN';
        
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            error_log("WhatsApp DB Error: " . $e->getMessage());
        }
    }
    
    // Send a text message
    public function sendTextMessage($to, $text) {
        $url = $this->api_url . $this->phone_number_id . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text]
        ];
        
        $response = $this->makeRequest($url, $data);
        
        // Log message
        $this->logMessage($to, $text, $response);
        
        return $response;
    }
    
    // Send a template message
    public function sendTemplateMessage($to, $template_name, $variables = []) {
        $url = $this->api_url . $this->phone_number_id . '/messages';
        
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
        
        if (!empty($variables)) {
            $data['template']['components'][] = [
                'type' => 'body',
                'parameters' => array_map(function($v) {
                    return ['type' => 'text', 'text' => $v];
                }, $variables)
            ];
        }
        
        $response = $this->makeRequest($url, $data);
        $this->logMessage($to, 'Template: ' . $template_name, $response);
        
        return $response;
    }
    
    // Send a notification
    public function sendNotification($to, $type, $data) {
        $templates = [
            'case_update' => 'case_update_template',
            'payment_reminder' => 'payment_reminder_template',
            'appointment_reminder' => 'appointment_reminder_template',
            'welcome' => 'welcome_template',
            'lead_followup' => 'lead_followup_template'
        ];
        
        $template = $templates[$type] ?? 'default_template';
        
        return $this->sendTemplateMessage($to, $template, $data);
    }
    
    // Send bulk messages
    public function sendBulkMessages($recipients, $message) {
        $results = [];
        foreach ($recipients as $to) {
            $results[] = $this->sendTextMessage($to, $message);
            usleep(500000); // 0.5 second delay to avoid rate limits
        }
        return $results;
    }
    
    // Make API request
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }
    
    // Log message to database
    private function logMessage($to, $message, $response) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO whatsapp_messages (recipient_phone, message, status) 
                VALUES (?, ?, ?)
            ");
            $status = ($response['http_code'] == 200 || $response['http_code'] == 201) ? 'sent' : 'failed';
            $stmt->execute([$to, $message, $status]);
        } catch (Exception $e) {
            error_log("WhatsApp log error: " . $e->getMessage());
        }
    }
    
    // Get message history
    public function getMessageHistory($phone = null, $limit = 50) {
        try {
            $query = "SELECT * FROM whatsapp_messages";
            if ($phone) {
                $query .= " WHERE recipient_phone = ?";
            }
            $query .= " ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $this->pdo->prepare($query);
            if ($phone) {
                $stmt->execute([$phone, $limit]);
            } else {
                $stmt->execute([$limit]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>
