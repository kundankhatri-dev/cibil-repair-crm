<?php
// cron/send_scheduled_reports.php
// Run this every hour: 0 * * * * php /path/to/send_scheduled_reports.php

require_once '../api/reports/config.php';

$query = "SELECT sr.*, rt.report_type, rt.columns, rt.filters, rt.date_range, u.email as partner_email, u.name as partner_name
          FROM scheduled_reports sr
          JOIN report_templates rt ON sr.template_id = rt.id
          JOIN users u ON sr.partner_id = u.id
          WHERE sr.is_active = 1 AND sr.next_send_at <= NOW()";

$result = mysqli_query($conn, $query);
$schedules = mysqli_fetch_all($result, MYSQLI_ASSOC);

$sent_count = 0;

foreach ($schedules as $schedule) {
    // Generate report
    $report_data = generateReport($schedule);
    
    // Send email
    $subject = "Scheduled Report: " . $schedule['report_type'] . " Report";
    $message = "Hello " . $schedule['partner_name'] . ",\n\n";
    $message .= "Your scheduled " . $schedule['report_type'] . " report is attached.\n\n";
    $message .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $message .= "Period: Last " . $schedule['schedule_type'] . "\n\n";
    $message .= "Thanks,\nCIBIL Repair Team";
    
    $headers = "From: reports@cibilrepair.in\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Attach file
    // mail($schedule['recipient_email'], $subject, $message, $headers);
    
    // Update next_send_at
    $next_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    $update = mysqli_prepare($conn, "UPDATE scheduled_reports SET last_sent_at = NOW(), next_send_at = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "si", $next_date, $schedule['id']);
    mysqli_stmt_execute($update);
    
    $sent_count++;
}

echo date('Y-m-d H:i:s') . " - Sent $sent_count scheduled reports\n";

function generateReport($schedule) {
    // Implement report generation logic
    return "report.pdf";
}

mysqli_close($conn);
?>