<?php
// /home/u929623538/public_html/api/cron/followup_cron.php
// Automated follow-up reminder system - CRON JOB

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('Asia/Kolkata');

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    die("Database connection failed");
}

// Check if followups table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'followups'");
if (mysqli_num_rows($check_table) == 0) {
    echo "Followups table does not exist yet. No reminders to send.\n";
    exit;
}

// Get pending follow-ups needing reminders
$query = "SELECT f.*, l.customer_name, l.customer_phone, l.customer_email, l.service_type as service,
          TIMESTAMPDIFF(HOUR, NOW(), f.followup_date) as hours_until
          FROM followups f
          JOIN partner_leads l ON f.lead_id = l.id
          WHERE f.status = 'pending' 
          AND f.reminder_sent = 0
          AND f.followup_date > NOW()
          AND f.followup_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query error: " . mysqli_error($conn) . "\n";
    exit;
}

$followups = mysqli_fetch_all($result, MYSQLI_ASSOC);
$sent_count = 0;

foreach ($followups as $followup) {
    $hours = $followup['hours_until'];
    $time_text = $hours <= 1 ? "in less than an hour" : "in $hours hours";
    
    $priority_icons = [
        'urgent' => '🔴 URGENT',
        'high' => '🟠 HIGH PRIORITY',
        'medium' => '🟡 Reminder',
        'low' => '🔵 Gentle Reminder'
    ];
    $icon = $priority_icons[$followup['priority']] ?? '📌 Follow-up Reminder';
    
    $message = "$icon\n\n";
    $message .= "Lead: *{$followup['customer_name']}*\n";
    $message .= "Service: {$followup['service']}\n";
    $message .= "Follow-up scheduled: *{$time_text}*\n\n";
    
    if (!empty($followup['title'])) {
        $message .= "Topic: {$followup['title']}\n\n";
    }
    
    $message .= "Lead ID: #{$followup['lead_id']}\n";
    $message .= "_CIBIL Repair_";
    
    // Update reminder count
    $update = mysqli_prepare($conn, "UPDATE followups SET reminder_count = reminder_count + 1, reminder_sent = 1 WHERE id = ?");
    if ($update) {
        mysqli_stmt_bind_param($update, "i", $followup['id']);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    }
    
    $sent_count++;
}

// Create logs folder if not exists
$log_dir = __DIR__ . '/../../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log the execution
$log_entry = date('Y-m-d H:i:s') . " - Sent $sent_count reminders\n";
file_put_contents($log_dir . '/cron_followup.log', $log_entry, FILE_APPEND);

echo "Cron executed: " . date('Y-m-d H:i:s') . " - Sent $sent_count reminders\n";

mysqli_close($conn);
?>