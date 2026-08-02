<?php
// api/client/get_case_details.php - Complete Case Details API
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id from session (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

$case_id = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($case_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Case ID required']);
    exit;
}

// ========== CHECK WHICH TABLES EXIST ==========
$has_client_cases = mysqli_query($conn, "SHOW TABLES LIKE 'client_cases'");
$use_client_cases = mysqli_num_rows($has_client_cases) > 0;

$has_timeline = mysqli_query($conn, "SHOW TABLES LIKE 'case_timeline'");
$use_timeline = mysqli_num_rows($has_timeline) > 0;

$has_documents = mysqli_query($conn, "SHOW TABLES LIKE 'client_documents'");
$use_documents = mysqli_num_rows($has_documents) > 0;

$has_messages = mysqli_query($conn, "SHOW TABLES LIKE 'client_messages'");
$use_messages = mysqli_num_rows($has_messages) > 0;

// ========== 1. GET CASE DETAILS ==========
$case = null;

if ($use_client_cases) {
    // Using client_cases table
    $query = "SELECT c.*, 
              l.customer_name, l.customer_phone, l.customer_email, l.service_type as service,
              p.name as partner_name, p.company_name
              FROM client_cases c
              LEFT JOIN leads l ON c.lead_id = l.id
              LEFT JOIN users p ON l.partner_id = p.id
              WHERE c.id = ? AND c.client_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $case_id, $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $case = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    // Fallback to leads table
    $query = "SELECT l.id as case_id, l.id as case_no, l.customer_name, l.customer_phone, 
              l.customer_email, l.service_type as service, l.status, l.commission_amount as amount,
              l.created_at, l.updated_at,
              p.name as partner_name, p.company_name
              FROM leads l
              LEFT JOIN users p ON l.partner_id = p.id
              WHERE l.id = ? AND l.customer_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $case_id, $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $case = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$case) {
    echo json_encode(['success' => false, 'error' => 'Case not found or access denied']);
    exit;
}

// Add progress percentage if not present
if (!isset($case['progress']) && isset($case['status'])) {
    $status_progress = [
        'new' => 5, 'pending' => 0, 'processing' => 20,
        'document_verification' => 40, 'dispute_filed' => 60,
        'bank_response' => 80, 'contacted' => 30,
        'converted' => 100, 'resolved' => 100, 'closed' => 100
    ];
    $case['progress'] = $status_progress[$case['status']] ?? 10;
}

// ========== 2. GET TIMELINE ==========
$timeline = [];
if ($use_timeline && $use_client_cases) {
    $timeline_query = "SELECT id, event_type as type, title, description, event_date as date, 
                       created_at, metadata
                       FROM case_timeline 
                       WHERE case_id = ? 
                       ORDER BY event_date ASC, created_at ASC";
    
    $stmt = mysqli_prepare($conn, $timeline_query);
    mysqli_stmt_bind_param($stmt, "i", $case_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $timeline = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    // Generate basic timeline from case data
    $timeline[] = [
        'type' => 'case_created',
        'title' => 'Case Created',
        'description' => 'Your case has been registered with CIBIL Repair',
        'date' => $case['created_at']
    ];
    
    if ($case['status'] === 'converted' || $case['status'] === 'resolved') {
        $timeline[] = [
            'type' => 'case_completed',
            'title' => 'Case Resolved',
            'description' => 'Your case has been successfully resolved',
            'date' => $case['updated_at'] ?? $case['created_at']
        ];
    }
}

// ========== 3. GET DOCUMENTS ==========
$documents = [];
if ($use_documents) {
    $docs_query = "SELECT id, document_name, document_type, file_path, file_size, status, 
                   uploaded_at, verified_at
                   FROM client_documents 
                   WHERE case_id = ? AND client_id = ?
                   ORDER BY uploaded_at DESC";
    
    $stmt = mysqli_prepare($conn, $docs_query);
    mysqli_stmt_bind_param($stmt, "ii", $case_id, $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $documents = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    // Format file sizes
    foreach ($documents as &$doc) {
        if (isset($doc['file_size'])) {
            $size = (int)$doc['file_size'];
            if ($size < 1024) {
                $doc['size_formatted'] = $size . ' B';
            } elseif ($size < 1048576) {
                $doc['size_formatted'] = round($size / 1024, 1) . ' KB';
            } else {
                $doc['size_formatted'] = round($size / 1048576, 1) . ' MB';
            }
        } else {
            $doc['size_formatted'] = '—';
        }
    }
}

// ========== 4. GET MESSAGES ==========
$messages = [];
if ($use_messages) {
    $messages_query = "SELECT id, user_id, user_type, message, is_read, 
                       created_at, attachment
                       FROM client_messages 
                       WHERE case_id = ? 
                       ORDER BY created_at ASC";
    
    $stmt = mysqli_prepare($conn, $messages_query);
    mysqli_stmt_bind_param($stmt, "i", $case_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    // Mark unread messages as read (when client views them)
    if ($viewer_role === 'client') {
        $update_stmt = mysqli_prepare($conn, "UPDATE client_messages SET is_read = 1 WHERE case_id = ? AND user_type != 'client' AND is_read = 0");
        mysqli_stmt_bind_param($update_stmt, "i", $case_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
}

// ========== 5. GET STATUS BADGE INFO ==========
$status_colors = [
    'pending' => 'warning', 'processing' => 'info',
    'document_verification' => 'info', 'dispute_filed' => 'primary',
    'bank_response' => 'warning', 'resolved' => 'success',
    'closed' => 'secondary', 'new' => 'info',
    'contacted' => 'warning', 'converted' => 'success'
];

$case['status_badge'] = $status_colors[$case['status'] ?? 'pending'] ?? 'secondary';
$case['status_label'] = ucfirst(str_replace('_', ' ', $case['status'] ?? 'Pending'));

// ========== 6. GET SIMILAR/RELATED CASES ==========
$similar_cases = [];
$similar_query = "SELECT id, case_no, service_type, status, created_at 
                  FROM client_cases 
                  WHERE client_id = ? AND id != ? 
                  ORDER BY created_at DESC LIMIT 3";
$stmt = mysqli_prepare($conn, $similar_query);
mysqli_stmt_bind_param($stmt, "ii", $client_id, $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$similar_cases = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'case' => $case,
    'timeline' => $timeline,
    'documents' => $documents,
    'messages' => $messages,
    'similar_cases' => $similar_cases,
    'stats' => [
        'total_documents' => count($documents),
        'total_messages' => count($messages),
        'unread_messages' => count(array_filter($messages, function($m) { 
            return $m['is_read'] == 0 && $m['user_type'] != 'client'; 
        }))
    ],
    'viewer_role' => $viewer_role
]);

mysqli_close($conn);
?>