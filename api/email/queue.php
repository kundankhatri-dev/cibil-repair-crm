<?php
// /api/email/queue.php
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../config/email_config.php';
    require_once 'send.php';
    
    global $conn;
    
    if (!isset($conn) || !$conn instanceof mysqli) {
        if (defined('DB_HOST')) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
        } else {
            throw new Exception('Database connection not available');
        }
    }
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'add':
            addToQueue($conn, $input);
            break;
        case 'process':
            processQueue($conn);
            break;
        case 'status':
            getQueueStatus($conn);
            break;
        case 'retry':
            retryFailed($conn, $input);
            break;
        case 'clear':
            clearQueue($conn, $input);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;

function addToQueue($conn, $data) {
    $to = $data['to'] ?? '';
    $template_key = $data['template_key'] ?? '';
    $variables = $data['variables'] ?? [];
    $priority = isset($data['priority']) ? intval($data['priority']) : 1;
    $scheduled_at = isset($data['scheduled_at']) ? $data['scheduled_at'] : null;
    
    if (empty($to) || empty($template_key)) {
        echo json_encode(['success' => false, 'error' => 'Recipient and template required']);
        return;
    }
    
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        return;
    }
    
    // Get template
    $stmt = $conn->prepare("SELECT * FROM email_templates WHERE template_key = ?");
    $stmt->bind_param("s", $template_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        echo json_encode(['success' => false, 'error' => 'Template not found']);
        return;
    }
    
    // Replace variables
    $subject = $template['subject'];
    $body = $template['body'];
    
    foreach ($variables as $key => $value) {
        $body = str_replace("{{{$key}}}", htmlspecialchars($value), $body);
        $subject = str_replace("{{{$key}}}", htmlspecialchars($value), $subject);
    }
    
    // Add to queue
    $status = 'pending';
    $scheduled = $scheduled_at ?: date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("INSERT INTO email_queue 
        (recipient_email, subject, body, template_key, variables, priority, status, scheduled_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $variables_json = json_encode($variables);
    $stmt->bind_param("sssssiss", $to, $subject, $body, $template_key, $variables_json, $priority, $status, $scheduled);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Email added to queue',
            'queue_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function processQueue($conn) {
    // Get pending emails
    $limit = QUEUE_BATCH_SIZE ?? 50;
    $stmt = $conn->prepare("SELECT * FROM email_queue 
        WHERE status = 'pending' AND scheduled_at <= NOW() 
        ORDER BY priority DESC, created_at ASC 
        LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    $processed = 0;
    $failed = 0;
    $sent = 0;
    $errors = [];
    
    while ($email = $result->fetch_assoc()) {
        $processed++;
        
        // Check rate limit
        $rateCheck = $conn->query("SELECT COUNT(*) as count FROM email_queue 
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND status = 'sent'");
        $rateCount = $rateCheck->fetch_assoc()['count'];
        
        if ($rateCount >= EMAIL_RATE_LIMIT) {
            $errors[] = "Rate limit reached. Try again later.";
            break;
        }
        
        // Send email
        $sendResult = sendEmail($email['recipient_email'], $email['subject'], $email['body']);
        
        if ($sendResult['success']) {
            // Update as sent
            $update = $conn->prepare("UPDATE email_queue 
                SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 
                WHERE id = ?");
            $update->bind_param("i", $email['id']);
            $update->execute();
            $update->close();
            $sent++;
        } else {
            // Update attempts
            $attempts = $email['attempts'] + 1;
            $status = $attempts >= EMAIL_RETRY_ATTEMPTS ? 'failed' : 'pending';
            $retry_after = $email['failed_at'] ? date('Y-m-d H:i:s', strtotime('+5 minutes')) : date('Y-m-d H:i:s');
            
            $update = $conn->prepare("UPDATE email_queue 
                SET status = ?, attempts = ?, error_message = ?, retry_after = ? 
                WHERE id = ?");
            $update->bind_param("sissi", $status, $attempts, $sendResult['error'], $retry_after, $email['id']);
            $update->execute();
            $update->close();
            
            $failed++;
            $errors[] = "Email {$email['id']}: " . $sendResult['error'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'processed' => $processed,
        'sent' => $sent,
        'failed' => $failed,
        'errors' => $errors
    ]);
}

function getQueueStatus($conn) {
    $statuses = ['pending', 'processing', 'sent', 'failed'];
    $stats = [];
    
    foreach ($statuses as $status) {
        $result = $conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = '$status'");
        $stats[$status] = $result->fetch_assoc()['count'];
    }
    
    // Get total
    $result = $conn->query("SELECT COUNT(*) as total FROM email_queue");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function retryFailed($conn, $data) {
    $id = $data['id'] ?? 0;
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE id = ? AND status = 'failed'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Email reset for retry']);
    } else {
        // Retry all failed
        $conn->query("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE status = 'failed' AND attempts < " . EMAIL_RETRY_ATTEMPTS);
        echo json_encode(['success' => true, 'message' => 'All failed emails reset for retry']);
    }
}

function clearQueue($conn, $data) {
    $status = $data['status'] ?? 'sent';
    $days = isset($data['days']) ? intval($data['days']) : 30;
    
    $stmt = $conn->prepare("DELETE FROM email_queue WHERE status = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("si", $status, $days);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => "Deleted $deleted emails",
        'deleted' => $deleted
    ]);
}
?>