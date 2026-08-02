<?php
// whatsapp-templates.php - Manage WhatsApp templates

require_once 'whatsapp-api.php';

$templates = [
    [
        'name' => 'welcome_template',
        'content' => "Welcome to CIBIL Repair, {{1}}! 🎉\n\nWe're here to help you improve your CIBIL score and achieve financial freedom.\n\n📌 Next steps:\n1. Share your CIBIL report\n2. Schedule a free consultation\n3. Get your personalized action plan\n\nNeed help? Reply to this message!"
    ],
    [
        'name' => 'case_update_template',
        'content' => "📊 Case Update for {{1}}\n\nCase #: {{2}}\nStatus: {{3}}\n\n📝 Next step: {{4}}\n📅 Expected date: {{5}}\n\nNeed help? Call us at +91 99054 82503"
    ],
    [
        'name' => 'payment_reminder_template',
        'content' => "💳 Payment Reminder\n\nDear {{1}},\n\nYour payment of ₹{{2}} is due on {{3}}.\n\n📌 Payment Methods:\n• UPI: cibilrepair@upi\n• Bank Transfer: {{bank_details}}\n\nLate fee of ₹{{4}} applies after due date."
    ],
    [
        'name' => 'appointment_reminder_template',
        'content' => "📅 Appointment Reminder\n\nHi {{1}},\n\nYour consultation is scheduled for:\n📆 Date: {{2}}\n⏰ Time: {{3}}\n📞 Call: +91 99054 82503\n\nPlease have your CIBIL report ready.\n\nClick here to join: {{meeting_link}}"
    ],
    [
        'name' => 'lead_followup_template',
        'content' => "👋 Hi {{1}},\n\nWe noticed you were interested in our CIBIL repair services. Our team is here to help!\n\n✅ Free consultation available\n✅ No commitment required\n✅ We'll call you in 15 minutes\n\nReply 'YES' to schedule a callback."
    ],
    [
        'name' => 'score_improved_template',
        'content' => "🎉 Congratulations {{1}}!\n\nYour CIBIL score has improved by {{2}} points!\n\n📊 New Score: {{3}}\n📈 Previous Score: {{4}}\n\nYou can now apply for:\n✅ Home Loans\n✅ Car Loans\n✅ Premium Credit Cards\n\nReady to apply? Reply to this message!"
    ]
];

$pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");

foreach ($templates as $template) {
    $stmt = $pdo->prepare("
        INSERT INTO whatsapp_templates (template_name, template_content) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
        template_content = VALUES(template_content), 
        is_active = 1
    ");
    $stmt->execute([$template['name'], $template['content']]);
    echo "✅ Template saved: " . $template['name'] . "\n";
}

echo "\n🎉 All templates loaded!\n";
?>
