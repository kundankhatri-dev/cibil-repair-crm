<?php
// chatbot-api.php - Chatbot API endpoint

require_once 'chatbot.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

$session_id = $_SESSION['chat_session_id'] ?? uniqid('chat_');
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = $session_id;
}

$response = getResponse($message, $session_id);

echo json_encode(['response' => $response]);
?>
