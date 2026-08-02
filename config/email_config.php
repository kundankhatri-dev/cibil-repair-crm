<?php
// /config/email_config.php

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');          // or smtp.sendgrid.net, smtp.mailgun.org
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');                   // tls or ssl
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@cibilrepair.in');
define('SMTP_FROM_NAME', 'CIBIL Repair');

// Rate limiting
define('EMAIL_RATE_LIMIT', 50);                 // emails per hour
define('EMAIL_RETRY_ATTEMPTS', 3);
define('EMAIL_RETRY_DELAY', 300);               // seconds between retries

// Queue settings
define('QUEUE_BATCH_SIZE', 100);
define('QUEUE_PROCESS_INTERVAL', 60);           // seconds
?>