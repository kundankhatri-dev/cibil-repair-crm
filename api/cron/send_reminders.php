#!/usr/bin/env php
<?php
require_once '../api/config.php';

// Send payment reminders
$reminder_query = "SELECT p.*, u.email, u.name 
                   FROM payments p 
                   JOIN users u ON p.user_id = u.id 
                   WHERE p.status = 'pending' 
                   AND p.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$result = mysqli_query($conn, $reminder_query);
while ($payment = mysqli_fetch_assoc($result)) {
    $subject = "Payment Reminder - CIBIL Repair";
    $message = "Dear {$payment['name']},\n\nThis is a reminder that your payment of ₹{$payment['amount']} is still pending.\n\nPlease complete your payment at: https://cibilrepair.in/payment.html\n\nThanks,\nCIBIL Repair Team";
    
    // mail($payment['email'], $subject, $message);
    
    // Log reminder
    $log = mysqli_prepare($conn, "INSERT INTO email_queue (recipient_email, recipient_name, subject, message) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($log, "ssss", $payment['email'], $payment['name'], $subject, $message);
    mysqli_stmt_execute($log);
}

echo "Reminders sent\n";
?>