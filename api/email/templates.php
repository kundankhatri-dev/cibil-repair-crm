<?php
// /api/email/templates.php
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../config/email_config.php';
    
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
        case 'get':
            getTemplates($conn);
            break;
        case 'get_one':
            $key = isset($_GET['key']) ? $_GET['key'] : '';
            getTemplate($conn, $key);
            break;
        case 'create':
            createTemplate($conn, $input);
            break;
        case 'update':
            updateTemplate($conn, $input);
            break;
        case 'delete':
            deleteTemplate($conn, $input);
            break;
        case 'preview':
            previewTemplate($conn, $input);
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

function getTemplates($conn) {
    $result = $conn->query("SELECT * FROM email_templates ORDER BY template_key");
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    echo json_encode([
        'success' => true,
        'data' => $templates,
        'total' => count($templates)
    ]);
}

function getTemplate($conn, $key) {
    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Template key required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM email_templates WHERE template_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Template not found']);
    }
    $stmt->close();
}

function createTemplate($conn, $data) {
    $key = $data['template_key'] ?? '';
    $subject = $data['subject'] ?? '';
    $body = $data['body'] ?? '';
    
    if (empty($key) || empty($subject) || empty($body)) {
        echo json_encode(['success' => false, 'error' => 'All fields required']);
        return;
    }
    
    // Check if exists
    $check = $conn->query("SELECT id FROM email_templates WHERE template_key = '$key'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Template key already exists']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO email_templates (template_key, subject, body) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $key, $subject, $body);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Template created',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function updateTemplate($conn, $data) {
    $id = $data['id'] ?? 0;
    $subject = $data['subject'] ?? '';
    $body = $data['body'] ?? '';
    
    if (empty($id) || empty($subject) || empty($body)) {
        echo json_encode(['success' => false, 'error' => 'All fields required']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE email_templates SET subject = ?, body = ? WHERE id = ?");
    $stmt->bind_param("ssi", $subject, $body, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Template updated']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function deleteTemplate($conn, $data) {
    $id = $data['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Template ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM email_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Template deleted']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

function previewTemplate($conn, $data) {
    $template_key = $data['template_key'] ?? '';
    $variables = $data['variables'] ?? [];
    
    if (empty($template_key)) {
        echo json_encode(['success' => false, 'error' => 'Template key required']);
        return;
    }
    
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
    $body = $template['body'];
    $subject = $template['subject'];
    
    foreach ($variables as $key => $value) {
        $body = str_replace("{{{$key}}}", htmlspecialchars($value), $body);
        $subject = str_replace("{{{$key}}}", htmlspecialchars($value), $subject);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'subject' => $subject,
            'body' => $body
        ]
    ]);
}
?>