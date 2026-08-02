<?php
// ============================================================
// ENHANCED EMAIL SENDER - Supports multiple methods
// ============================================================

function sendEmail($to, $subject, $message, $from = "noreply@cibilrepair.in") {
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CIBIL Repair <" . $from . ">\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Additional headers for better delivery
    $headers .= "X-Priority: 1\r\n";
    $headers .= "X-MSMail-Priority: High\r\n";
    
    // Try to send
    return mail($to, $subject, $message, $headers, "-f" . $from);
}

// Alternative method using SMTP (if mail() fails)
function sendEmailSMTP($to, $subject, $message) {
    // You can configure SMTP here if needed
    // This is a fallback using PHPMailer or similar
    return false;
}
?>