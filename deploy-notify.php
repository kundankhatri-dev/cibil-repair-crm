<?php
// deploy-notify.php - Send email on deployment
$to = "your_email@example.com";
$subject = "Deployment Successful - " . date('Y-m-d H:i:s');
$message = "Your site was successfully deployed at " . date('Y-m-d H:i:s') . "\n\n";
$message .= "Latest commit: " . shell_exec('cd /home/u929623538/domains/cibilrepair.in/public_html && git log --oneline -1');

mail($to, $subject, $message);
