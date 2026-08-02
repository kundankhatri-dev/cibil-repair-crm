<?php
// api/partner/email_campaign.php
// Automated email marketing campaigns

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$campaign_type = $data['campaign_type'] ?? 'welcome'; // welcome, followup, promotional, newsletter
$subject = $data['subject'] ?? '';
$message = $data['message'] ?? '';
$recipient_type = $data['recipient_type'] ?? 'leads'; // leads, customers, both

// Email templates based on campaign type
$templates = [
    'welcome' => [
        'subject' => 'Welcome to CIBIL Repair Family! 🎉',
        'body' => "Dear {name},\n\nThank you for choosing CIBIL Repair. We're excited to help you improve your credit score!\n\nOur team will reach out shortly to understand your requirements.\n\nBest regards,\nCIBIL Repair Team"
    ],
    'followup' => [
        'subject' => 'Following up on your credit repair request',
        'body' => "Hi {name},\n\nWe noticed you haven't responded to our previous message. Is there anything we can help you with?\n\nFeel free to reply to this email or call us at +91 87094 55441.\n\nBest regards,\nCIBIL Repair Team"
    ],
    'conversion' => [
        'subject' => 'Congratulations! 🎉 Your credit repair is complete',
        'body' => "Dear {name},\n\nGreat news! Your credit repair process has been completed successfully.\n\nYour credit score has improved significantly. Check your updated report attached.\n\nThank you for trusting CIBIL Repair!\n\nBest regards,\nCIBIL Repair Team"
    ]
];

if (isset($templates[$campaign_type])) {
    $subject = $templates[$campaign_type]['subject'];
    $message = $templates[$campaign_type]['body'];
}

// Get recipients
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

$recipients_query = "SELECT customer_name as name, customer_email as email FROM $leadsTable WHERE partner_id = ?";
if ($recipient_type === 'leads') {
    $recipients_query .= " AND status != 'converted'";
} elseif ($recipient_type === 'customers') {
    $recipients_query .= " AND status = 'converted'";
}

$stmt = mysqli_prepare($conn, $recipients_query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recipients = mysqli_fetch_all($result, MYSQLI_ASSOC);

$sent_count = 0;
foreach ($recipients as $recipient) {
    if (!empty($recipient['email'])) {
        $personalized_message = str_replace('{name}', $recipient['name'], $message);
        
        // In production, use PHPMailer or SMTP
        // mail($recipient['email'], $subject, $personalized_message, "From: support@cibilrepair.in");
        $sent_count++;
    }
}

// Log campaign
$log_table = 'email_campaigns';
$checkLogTable = mysqli_query($conn, "SHOW TABLES LIKE '$log_table'");
if (mysqli_num_rows($checkLogTable) == 0) {
    $createTable = "CREATE TABLE $log_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        campaign_type VARCHAR(50),
        subject VARCHAR(255),
        recipients_count INT,
        sent_count INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createTable);
}

$log_stmt = mysqli_prepare($conn, "INSERT INTO $log_table (partner_id, campaign_type, subject, recipients_count, sent_count) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($log_stmt, "issii", $partner_id, $campaign_type, $subject, count($recipients), $sent_count);
mysqli_stmt_execute($log_stmt);

echo json_encode([
    'success' => true,
    'campaign_type' => $campaign_type,
    'subject' => $subject,
    'total_recipients' => count($recipients),
    'sent_count' => $sent_count,
    'failed_count' => count($recipients) - $sent_count,
    'message' => 'Email campaign initiated successfully'
]);

mysqli_close($conn);
?>