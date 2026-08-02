<?php
// ============================================================
// TEST MAIL API - Check if PHP mail() is working
// Endpoint: /api/mail_test.php
// Method: GET
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ============================================================
// CONFIGURATION
// ============================================================

// Replace with your actual email
$to = "contact@cibilrepair.in";
$subject = "PHP Mail Test - CIBIL Repair CRM";
$message = "
<html>
<head>
    <title>PHP Mail Test</title>
</head>
<body>
    <h2>CIBIL Repair CRM - Mail Test</h2>
    <p>If you read this, PHP mail() is working on this server.</p>
    <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
    <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>IP:</strong> " . $_SERVER['SERVER_ADDR'] . "</p>
</body>
</html>
";

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: CIBIL Repair <contact@cibilrepair.in>\r\n";
$headers .= "Reply-To: contact@cibilrepair.in\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// ============================================================
// SEND EMAIL
// ============================================================

$result = mail($to, $subject, $message, $headers);

$response = [
    'success' => $result,
    'message' => $result ? 'Mail sent successfully!' : 'Mail failed to send.',
    'to' => $to,
    'subject' => $subject,
    'server' => $_SERVER['SERVER_NAME'],
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s')
];

if ($result) {
    $response['info'] = 'Check your email inbox (and spam folder) for the test email.';
} else {
    $response['error'] = 'Failed to send email. Check server mail configuration.';
    $response['suggestions'] = [
        'Check if sendmail is installed',
        'Verify SMTP configuration',
        'Check server mail logs',
        'Try using SMTP instead of mail()'
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>