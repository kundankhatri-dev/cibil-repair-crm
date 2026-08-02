<?php
// /api/email.php
require_once 'config.php';
header('Content-Type: application/json');

// ── SEND EMAIL ──
function sendEmail($to, $subject, $message, $from_name = 'CIBIL Repair', $from_email = 'noreply@cibilrepair.in') {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1f8a72, #0e5e4c); color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .footer { background: #f0f2f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; background: #1f8a72; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🏦 CIBIL Repair</h2>
            </div>
            <div class="content">
                ' . $message . '
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' CIBIL Repair. All rights reserved.</p>
                <p>Need help? Contact us at support@cibilrepair.in</p>
            </div>
        </div>
    </body>
    </html>';
    
    return mail($to, $subject, $html, $headers);
}

// ── GET TEMPLATE ──
function getEmailTemplate($template_key, $variables = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM email_templates WHERE template_key = ?");
    mysqli_stmt_bind_param($stmt, "s", $template_key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $template = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$template) {
        return null;
    }
    
    // Replace variables
    $subject = $template['subject'];
    $body = $template['body'];
    
    foreach ($variables as $key => $value) {
        $body = str_replace("{{$key}}", htmlspecialchars($value), $body);
        $subject = str_replace("{{$key}}", htmlspecialchars($value), $subject);
    }
    
    return [
        'subject' => $subject,
        'body' => $body
    ];
}

// ── SEND TEMPLATE EMAIL ──
function sendTemplateEmail($to, $template_key, $variables = [], $name = '') {
    $template = getEmailTemplate($template_key, $variables);
    
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    $from_name = 'CIBIL Repair';
    $from_email = 'noreply@cibilrepair.in';
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1f8a72, #0e5e4c); color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .footer { background: #f0f2f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; background: #1f8a72; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🏦 CIBIL Repair</h2>
            </div>
            <div class="content">
                ' . $template['body'] . '
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' CIBIL Repair. All rights reserved.</p>
                <p>Need help? Contact us at support@cibilrepair.in</p>
            </div>
        </div>
    </body>
    </html>';
    
    $success = mail($to, $template['subject'], $html, $headers);
    
    // Log to history
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO email_history (recipient_email, subject, template_key, status) VALUES (?, ?, ?, ?)");
    $status = $success ? 'sent' : 'failed';
    mysqli_stmt_bind_param($stmt, "ssss", $to, $template['subject'], $template_key, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return [
        'success' => $success,
        'subject' => $template['subject']
    ];
}

// ── QUEUE EMAIL ──
function queueEmail($to, $subject, $message, $name = '', $template_key = '', $priority = 1, $scheduled_at = null) {
    global $conn;
    
    $status = 'pending';
    $scheduled = $scheduled_at ?: date('Y-m-d H:i:s');
    
    $stmt = mysqli_prepare($conn, "INSERT INTO email_queue (recipient_email, recipient_name, subject, message, template_key, priority, status, scheduled_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssiss", $to, $name, $subject, $message, $template_key, $priority, $status, $scheduled);
    
    return mysqli_stmt_execute($stmt);
}

// ── QUEUE TEMPLATE EMAIL ──
function queueTemplateEmail($to, $template_key, $variables = [], $name = '', $priority = 1, $scheduled_at = null) {
    $template = getEmailTemplate($template_key, $variables);
    
    if (!$template) {
        return ['success' => false, 'error' => 'Template not found'];
    }
    
    return queueEmail($to, $template['subject'], $template['body'], $name, $template_key, $priority, $scheduled_at);
}

// ── PROCESS QUEUE ──
function processEmailQueue() {
    global $conn;
    $processed = 0;
    $sent = 0;
    $failed = 0;
    
    $result = mysqli_query($conn, "SELECT * FROM email_queue WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW()) ORDER BY priority DESC, created_at ASC LIMIT 50");
    
    while ($row = mysqli_fetch_assoc($result)) {
        $processed++;
        $success = sendEmail($row['recipient_email'], $row['subject'], $row['message']);
        $status = $success ? 'sent' : 'failed';
        $sent_at = $success ? 'NOW()' : 'NULL';
        
        $update = mysqli_prepare($conn, "UPDATE email_queue SET status = ?, sent_at = NOW(), attempts = attempts + 1 WHERE id = ?");
        mysqli_stmt_bind_param($update, "si", $status, $row['id']);
        mysqli_stmt_execute($update);
        
        if ($success) {
            $sent++;
        } else {
            $failed++;
        }
    }
    
    return [
        'processed' => $processed,
        'sent' => $sent,
        'failed' => $failed
    ];
}

// ── GET QUEUE STATUS ──
function getQueueStatus() {
    global $conn;
    $statuses = ['pending', 'sent', 'failed'];
    $stats = [];
    
    foreach ($statuses as $status) {
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM email_queue WHERE status = '$status'");
        $row = mysqli_fetch_assoc($result);
        $stats[$status] = $row['count'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM email_queue");
    $row = mysqli_fetch_assoc($result);
    $stats['total'] = $row['total'];
    
    return $stats;
}

// ── GET EMAIL HISTORY ──
function getEmailHistory($limit = 50, $offset = 0) {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM email_history ORDER BY sent_at DESC LIMIT $limit OFFSET $offset");
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    return $history;
}

// ── API HANDLER ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'send':
            $result = sendEmail($input['to'], $input['subject'], $input['message']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'send_template':
            $result = sendTemplateEmail(
                $input['to'],
                $input['template_key'],
                $input['variables'] ?? [],
                $input['name'] ?? ''
            );
            echo json_encode($result);
            break;
            
        case 'queue':
            $result = queueEmail(
                $input['to'],
                $input['subject'],
                $input['message'],
                $input['name'] ?? '',
                $input['template_key'] ?? '',
                $input['priority'] ?? 1,
                $input['scheduled_at'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'queue_template':
            $result = queueTemplateEmail(
                $input['to'],
                $input['template_key'],
                $input['variables'] ?? [],
                $input['name'] ?? '',
                $input['priority'] ?? 1,
                $input['scheduled_at'] ?? null
            );
            echo json_encode($result);
            break;
            
        case 'process':
            $result = processEmailQueue();
            echo json_encode([
                'success' => true,
                'processed' => $result['processed'],
                'sent' => $result['sent'],
                'failed' => $result['failed']
            ]);
            break;
            
        case 'status':
            $result = getQueueStatus();
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'history':
            $result = getEmailHistory(
                $input['limit'] ?? 50,
                $input['offset'] ?? 0
            );
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'status') {
        $result = getQueueStatus();
        echo json_encode(['success' => true, 'data' => $result]);
    } elseif ($action === 'history') {
        $result = getEmailHistory(
            $_GET['limit'] ?? 50,
            $_GET['offset'] ?? 0
        );
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}
?>