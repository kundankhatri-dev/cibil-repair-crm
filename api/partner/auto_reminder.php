<?php
// api/partner/auto_reminder.php
// Automated follow-up reminders via cron job

session_start();
require_once '../config.php';

// This should be run via cron job every hour
// 0 * * * * php /path/to/auto_reminder.php

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get leads that need follow-up (contacted but no follow-up in 3 days)
$query = "SELECT l.id, l.customer_name, l.customer_phone, l.partner_id, u.name as partner_name, u.email as partner_email
          FROM $leadsTable l
          JOIN users u ON l.partner_id = u.id
          WHERE l.status = 'contacted' 
          AND l.updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
          AND NOT EXISTS (
              SELECT 1 FROM partner_lead_followups f WHERE f.lead_id = l.id AND f.followup_date > DATE_SUB(NOW(), INTERVAL 3 DAY)
          )";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Create auto-reminders
$remindersCreated = 0;
$followupsTable = 'partner_lead_followups';
$checkFollowups = mysqli_query($conn, "SHOW TABLES LIKE '$followupsTable'");
if (mysqli_num_rows($checkFollowups) > 0) {
    foreach ($leads as $lead) {
        $followup_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        $notes = "Auto-generated reminder: Follow up with " . $lead['customer_name'];
        
        $insert = mysqli_prepare($conn, "INSERT INTO $followupsTable (lead_id, followup_date, notes, status) VALUES (?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($insert, "iss", $lead['id'], $followup_date, $notes);
        
        if (mysqli_stmt_execute($insert)) {
            $remindersCreated++;
            
            // Send email notification to partner
            $subject = "Reminder: Follow up with " . $lead['customer_name'];
            $message = "Dear " . $lead['partner_name'] . ",\n\n";
            $message .= "This is a reminder to follow up with " . $lead['customer_name'] . " (" . $lead['customer_phone'] . ").\n";
            $message .= "The lead has been in 'contacted' status for over 3 days.\n\n";
            $message .= "Best regards,\nCIBIL Repair System";
            
            // mail($lead['partner_email'], $subject, $message, "From: reminders@cibilrepair.in");
        }
    }
}

echo json_encode([
    'success' => true,
    'reminders_created' => $remindersCreated,
    'pending_followups' => count($leads),
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>