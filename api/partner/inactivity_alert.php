<?php
// api/partner/inactivity_alert.php
// Alert partners about inactive leads

session_start();
require_once '../config.php';

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get leads with no activity for 7+ days
$query = "SELECT l.id, l.customer_name, l.customer_phone, l.partner_id, 
          u.name as partner_name, u.email as partner_email,
          DATEDIFF(NOW(), l.updated_at) as inactive_days
          FROM $leadsTable l
          JOIN users u ON l.partner_id = u.id
          WHERE l.status IN ('new', 'contacted') 
          AND l.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND DATEDIFF(NOW(), l.updated_at) > 7";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$inactive_leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

$alerts_sent = 0;
foreach ($inactive_leads as $lead) {
    // Create notification
    $notifTable = 'partner_notifications';
    $checkNotif = mysqli_query($conn, "SHOW TABLES LIKE '$notifTable'");
    if (mysqli_num_rows($checkNotif) > 0) {
        $title = "⚠️ Lead Inactive Alert";
        $message = "Lead '{$lead['customer_name']}' has been inactive for {$lead['inactive_days']} days. Follow up now!";
        
        $insert = mysqli_prepare($conn, "INSERT INTO $notifTable (partner_id, title, message, type, is_read) VALUES (?, ?, ?, 'warning', 0)");
        mysqli_stmt_bind_param($insert, "iss", $lead['partner_id'], $title, $message);
        mysqli_stmt_execute($insert);
        $alerts_sent++;
    }
    
    // Update lead priority to urgent
    $update = mysqli_prepare($conn, "UPDATE $leadsTable SET priority = 'urgent' WHERE id = ?");
    mysqli_stmt_bind_param($update, "i", $lead['id']);
    mysqli_stmt_execute($update);
}

echo json_encode([
    'success' => true,
    'alerts_sent' => $alerts_sent,
    'inactive_leads_count' => count($inactive_leads),
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>