<?php
// whatsapp-automation.php - Automated WhatsApp notifications

require_once 'whatsapp-api.php';

$whatsapp = new WhatsAppAPI();
$pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");

function sendCaseUpdateNotifications($whatsapp, $pdo) {
    // Get cases that need notifications
    $stmt = $pdo->query("
        SELECT cc.*, c.name, c.phone 
        FROM client_cases cc 
        JOIN customers c ON cc.client_id = c.id 
        WHERE cc.status = 'in-progress' 
        AND cc.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        LIMIT 10
    ");
    $cases = $stmt->fetchAll();
    
    foreach ($cases as $case) {
        $message = "📊 Case Update for {$case['name']}\n\n";
        $message .= "Case #: {$case['case_no']}\n";
        $message .= "Status: {$case['status']}\n";
        $message .= "Service: {$case['service']}\n\n";
        $message .= "We're working on your case. Updates will be shared regularly.\n";
        $message .= "📞 Call us: +91 99054 82503";
        
        $whatsapp->sendTextMessage($case['phone'], $message);
        echo "✅ Notification sent to {$case['name']}\n";
    }
}

function sendPaymentReminders($whatsapp, $pdo) {
    $stmt = $pdo->query("
        SELECT p.*, c.name, c.phone 
        FROM payments p 
        JOIN customers c ON p.client_id = c.id 
        WHERE p.status = 'pending' 
        AND p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        LIMIT 10
    ");
    $payments = $stmt->fetchAll();
    
    foreach ($payments as $payment) {
        $message = "💳 Payment Reminder\n\n";
        $message .= "Dear {$payment['name']},\n\n";
        $message .= "Your payment of ₹{$payment['amount']} is pending.\n";
        $message .= "Please complete your payment to continue with your case.\n\n";
        $message .= "📞 Need help? Call +91 99054 82503";
        
        $whatsapp->sendTextMessage($payment['phone'], $message);
        echo "✅ Reminder sent to {$payment['name']}\n";
    }
}

// Run automation
echo "📱 WhatsApp Automation Started\n";
echo "═══════════════════════════════════════\n\n";

echo "📊 Sending case updates...\n";
sendCaseUpdateNotifications($whatsapp, $pdo);

echo "\n💳 Sending payment reminders...\n";
sendPaymentReminders($whatsapp, $pdo);

echo "\n✅ Automation completed!\n";
?>
