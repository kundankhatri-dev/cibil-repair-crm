<?php
// api/partner/smart_response.php
// AI-powered response suggestions for leads

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$lead_question = $data['question'] ?? '';
$lead_service = $data['service'] ?? '';

// Predefined response templates
$response_templates = [
    'pricing' => [
        'keywords' => ['price', 'cost', 'fees', 'charges', 'how much'],
        'response' => "Thank you for your interest! The pricing for {$lead_service} depends on the complexity. Could you share your credit report for a free assessment? We'll provide an accurate quote afterward."
    ],
    'timeline' => [
        'keywords' => ['time', 'duration', 'how long', 'days', 'weeks'],
        'response' => "The timeline for {$lead_service} typically ranges from 15-45 days, depending on the complexity of your case and bank response times. We'll keep you updated throughout the process!"
    ],
    'guarantee' => [
        'keywords' => ['guarantee', 'sure', 'confirm', 'promise'],
        'response' => "We have a 95% success rate with {$lead_service}. While we can't guarantee results (as banks have final say), we use proven legal methods and have successfully helped 5000+ clients. You only pay after results!"
    ],
    'process' => [
        'keywords' => ['process', 'how it works', 'steps', 'procedure'],
        'response' => "The process for {$lead_service} is simple:\n1️⃣ Share your credit report\n2️⃣ Free analysis & consultation\n3️⃣ Legal notice drafting (if needed)\n4️⃣ Bank follow-ups\n5️⃣ Success confirmation\n\nShall I explain any step in detail?"
    ],
    'documents' => [
        'keywords' => ['document', 'paper', 'need', 'require'],
        'response' => "For {$lead_service}, you'll need:\n• Latest credit report\n• Bank statements\n• Any existing correspondence with bank\n• ID proof\n\nDo you have these ready?"
    ]
];

$suggested_response = null;
foreach ($response_templates as $template) {
    foreach ($template['keywords'] as $keyword) {
        if (stripos($lead_question, $keyword) !== false) {
            $suggested_response = $template['response'];
            break 2;
        }
    }
}

if (!$suggested_response) {
    $suggested_response = "Thank you for reaching out! For {$lead_service}, I'd recommend scheduling a free consultation call. Would you prefer a call today or tomorrow? Our experts will answer all your questions.";
}

echo json_encode([
    'success' => true,
    'suggested_response' => $suggested_response,
    'confidence' => 85
]);

mysqli_close($conn);
?>