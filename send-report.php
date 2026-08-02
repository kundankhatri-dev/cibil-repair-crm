<?php
// send-report.php - Send reports via email

require_once 'generate-report.php';

function sendReport($report_type, $recipient_email) {
    // Generate the report
    switch($report_type) {
        case 'daily':
            $data = generateDailyReport();
            $subject = "📊 Daily Report - " . date('Y-m-d');
            break;
        case 'weekly':
            $data = generateWeeklyReport();
            $subject = "📊 Weekly Report - " . date('Y-m-d');
            break;
        case 'monthly':
            $data = generateMonthlyReport();
            $subject = "📊 Monthly Report - " . date('Y-m-d');
            break;
        default:
            return false;
    }
    
    // Build email content
    $message = "<html><head><style>
        body { font-family: Arial; font-size: 14px; }
        h1 { color: #0d9e78; }
        .stat { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; }
        .stat strong { display: inline-block; width: 200px; }
        hr { border: 1px solid #ddd; }
    </style></head><body>";
    $message .= "<h1>📊 CIBIL Repair CRM - " . ucfirst($report_type) . " Report</h1>";
    $message .= "<p>Generated: " . date('Y-m-d H:i:s') . "</p><hr>";
    
    foreach($data as $key => $value) {
        $label = str_replace('_', ' ', ucfirst($key));
        if (is_array($value)) {
            $message .= "<div class='stat'><strong>$label:</strong> <pre>" . print_r($value, true) . "</pre></div>";
        } else {
            $message .= "<div class='stat'><strong>$label:</strong> $value</div>";
        }
    }
    
    $message .= "<hr><p style='color: #888; font-size: 12px;'>This is an automated report from your CRM system.</p>";
    $message .= "</body></html>";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@cibilrepair.in\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    
    // Send email
    return mail($recipient_email, $subject, $message, $headers);
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $report_type = $argv[1] ?? 'daily';
    $recipient = $argv[2] ?? 'admin@cibilrepair.in';
    
    if (sendReport($report_type, $recipient)) {
        echo "✅ " . ucfirst($report_type) . " report sent to $recipient\n";
    } else {
        echo "❌ Failed to send report\n";
    }
}
?>
