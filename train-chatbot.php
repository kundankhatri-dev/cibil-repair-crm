<?php
// train-chatbot.php - Train the chatbot with intents

$pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");

$intents = [
    [
        'intent_name' => 'greeting',
        'patterns' => ['hello', 'hi', 'hey', 'good morning', 'good evening', 'greetings'],
        'responses' => ['Hello! Welcome to CIBIL Repair. How can I assist you today?', 'Hi there! How can I help with your credit repair needs?', 'Greetings! I\'m here to help with your CIBIL questions.']
    ],
    [
        'intent_name' => 'services',
        'patterns' => ['what services', 'what do you do', 'services', 'offer', 'help with', 'can you'],
        'responses' => ['We offer CIBIL score repair, written-off clearance, settled account resolution, profile correction, and suit filed removal. Which service interests you?', 'Our services include CIBIL Repair, Written Off Clearance, Settled Account Resolution, Profile Correction, and Suit Filed Removal. Tell me what you need!']
    ],
    [
        'intent_name' => 'pricing',
        'patterns' => ['price', 'cost', 'how much', 'fee', 'charges', 'afford', 'payment'],
        'responses' => ['Our pricing starts from ₹999. The exact cost depends on your specific case. Would you like a free consultation?', 'We offer competitive pricing starting at ₹999. Each case is different, so we provide customized quotes. Want to schedule a free consultation?']
    ],
    [
        'intent_name' => 'timeline',
        'patterns' => ['how long', 'time', 'duration', 'when', 'days', 'weeks', 'months', 'process time'],
        'responses' => ['Simple cases take 15-30 days. Complex cases like written-off or settled accounts may take 30-45 days. We\'ll keep you updated throughout the process!', 'Timeline depends on your case complexity. Typically 15-45 days. We\'ll give you a specific timeline after reviewing your CIBIL report.']
    ],
    [
        'intent_name' => 'cibil_score',
        'patterns' => ['what is cibil', 'cibil score', 'score', 'credit score', 'how to improve', 'good score'],
        'responses' => ['A CIBIL score ranges from 300-900. 750+ is considered excellent for loans and credit cards. We can help improve your score!', 'Your CIBIL score is a 3-digit number between 300-900. A score above 750 is good. We specialize in improving scores through legal dispute processes.']
    ],
    [
        'intent_name' => 'contact',
        'patterns' => ['contact', 'phone', 'email', 'reach', 'call', 'message', 'support'],
        'responses' => ['You can reach us at +91 99054 82503 or email at contact@cibilrepair.in. Our team is available 9 AM to 6 PM, Monday to Saturday.', 'Contact us via email at contact@cibilrepair.in or call +91 99054 82503. We respond within 24 hours!']
    ],
    [
        'intent_name' => 'consultation',
        'patterns' => ['consultation', 'free consultation', 'advice', 'talk to expert', 'meet', 'schedule'],
        'responses' => ['Yes, we offer free consultations! Would you like to schedule one? Please share your name and phone number, and we\'ll call you back.', 'Free consultation available! Please provide your name and phone number and we\'ll get back to you within 2 hours.']
    ],
    [
        'intent_name' => 'thank_you',
        'patterns' => ['thank', 'thanks', 'appreciate', 'helpful', 'good info'],
        'responses' => ['You\'re welcome! Is there anything else I can help with?', 'Glad to help! Let me know if you have more questions.', 'Happy to assist! Feel free to ask anything else.']
    ],
    [
        'intent_name' => 'lead_collection',
        'patterns' => ['lead', 'interested', 'need help', 'sign up', 'enroll', 'join', 'start'],
        'responses' => ['Great! Let me get some details to help you better. What\'s your name?', 'I\'d love to help. Could you share your name and phone number?', 'Let\'s get started! Please share your name and preferred contact number.']
    ],
    [
        'intent_name' => 'goodbye',
        'patterns' => ['bye', 'goodbye', 'see you', 'later', 'exit', 'close', 'quit'],
        'responses' => ['Goodbye! Feel free to come back anytime you need help.', 'Take care! We\'re here whenever you need credit repair assistance.', 'Bye for now! Don\'t hesitate to reach out if you have more questions.']
    ]
];

foreach ($intents as $intent) {
    $stmt = $pdo->prepare("
        INSERT INTO chatbot_intents (intent_name, intent_patterns, intent_responses) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        intent_patterns = VALUES(intent_patterns), 
        intent_responses = VALUES(intent_responses)
    ");
    $stmt->execute([
        $intent['intent_name'],
        json_encode($intent['patterns']),
        json_encode($intent['responses'])
    ]);
    echo "✅ Trained: " . $intent['intent_name'] . "\n";
}

echo "\n🎉 Chatbot training complete!\n";
?>
