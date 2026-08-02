<?php
// api/partner/whatsapp_templates.php
// WhatsApp Business API templates

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? 'list';

$templates = [
    'lead_welcome' => [
        'name' => 'lead_welcome',
        'language' => 'en',
        'body' => 'Hello {{1}}, welcome to CIBIL Repair! 🎉\n\nWe specialize in credit repair services. Our expert will call you shortly.\n\nThank you!'
    ],
    'followup_reminder' => [
        'name' => 'followup_reminder',
        'language' => 'en',
        'body' => 'Hi {{1}}, this is a gentle reminder about your credit repair consultation.\n\nPlease reply YES to confirm or call us at +91 87094 55441.'
    ],
    'conversion_success' => [
        'name' => 'conversion_success',
        'language' => 'en',
        'body' => 'Congratulations {{1}}! 🎉 Your credit repair is complete.\n\nYour new credit score: {{2}}\n\nThank you for choosing CIBIL Repair!'
    ],
    'payout_notification' => [
        'name' => 'payout_notification',
        'language' => 'en',
        'body' => 'Dear {{1}}, your payout of ₹{{2}} has been credited to your bank account.\n\nTransaction ID: {{3}}\n\nThank you for being a valued partner!'
    ]
];

if ($action === 'list') {
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'total_templates' => count($templates)
    ]);
} elseif ($action === 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $template_name = $data['template_name'] ?? '';
    $recipient_phone = $data['phone'] ?? '';
    $variables = $data['variables'] ?? [];
    
    if (!isset($templates[$template_name])) {
        echo json_encode(['success' => false, 'error' => 'Template not found']);
        exit;
    }
    
    $template = $templates[$template_name];
    $message = $template['body'];
    
    // Replace variables
    foreach ($variables as $index => $value) {
        $message = str_replace("{{" . ($index + 1) . "}}", $value, $message);
    }
    
    // Simulate WhatsApp sending
    // In production, use WhatsApp Business API or Twilio
    $sent = !empty($recipient_phone);
    
    echo json_encode([
        'success' => $sent,
        'template' => $template_name,
        'recipient' => $recipient_phone,
        'message_preview' => $message,
        'status' => $sent ? 'sent' : 'failed'
    ]);
}

mysqli_close($conn);
?>