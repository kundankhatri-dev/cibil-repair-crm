<?php
// api/client/get_dashboard.php - Complete Client Dashboard API
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

// Get client_id from session or GET parameter (for partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner') {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $_SESSION['user_id'], $client_id);
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

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get client info
$client_query = "SELECT id, name, email, phone FROM users WHERE id = ? AND role = 'client'";
$stmt = mysqli_prepare($conn, $client_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$client_result = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($stmt);

if (!$client) {
    echo json_encode(['success' => false, 'error' => 'Client not found']);
    exit;
}

// ========== 1. CASES STATISTICS ==========
// Check if client_cases table exists, if not, get from leads
$cases_table = 'client_cases';
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'client_cases'");
if (mysqli_num_rows($check_table) > 0) {
    $cases_query = "SELECT 
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status IN ('pending', 'processing', 'document_verification', 'dispute_filed', 'bank_response') THEN 1 ELSE 0 END) as active_cases,
                    SUM(CASE WHEN status = 'resolved' OR status = 'closed' THEN 1 ELSE 0 END) as completed_cases,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cases
                    FROM client_cases WHERE client_id = ?";
    $stmt = mysqli_prepare($conn, $cases_query);
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $cases_result = mysqli_stmt_get_result($stmt);
    $cases = mysqli_fetch_assoc($cases_result);
    mysqli_stmt_close($stmt);
} else {
    // Fallback to leads table
    $cases_query = "SELECT 
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as completed_cases,
                    SUM(CASE WHEN status IN ('new', 'contacted') THEN 1 ELSE 0 END) as active_cases
                    FROM leads WHERE customer_id = ?";
    $stmt = mysqli_prepare($conn, $cases_query);
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $cases_result = mysqli_stmt_get_result($stmt);
    $cases = mysqli_fetch_assoc($cases_result);
    mysqli_stmt_close($stmt);
    $cases['pending_cases'] = ($cases['total_cases'] ?? 0) - ($cases['completed_cases'] ?? 0);
}

// ========== 2. CURRENT CIBIL SCORE ==========
$current_score = null;
$score_date = null;
$previous_score = null;

$score_query = "SELECT score, recorded_date FROM credit_scores WHERE client_id = ? ORDER BY recorded_date DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $score_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$score_result = mysqli_stmt_get_result($stmt);
$score_data = mysqli_fetch_assoc($score_result);
mysqli_stmt_close($stmt);

if ($score_data) {
    $current_score = (int)$score_data['score'];
    $score_date = $score_data['recorded_date'];
    
    // Get previous score for comparison
    $prev_stmt = mysqli_prepare($conn, "SELECT score FROM credit_scores WHERE client_id = ? AND id != ? ORDER BY recorded_date DESC LIMIT 1");
    mysqli_stmt_bind_param($prev_stmt, "ii", $client_id, $score_data['id']);
    mysqli_stmt_execute($prev_stmt);
    $prev_result = mysqli_stmt_get_result($prev_stmt);
    $prev_data = mysqli_fetch_assoc($prev_result);
    $previous_score = $prev_data ? (int)$prev_data['score'] : $current_score;
    mysqli_stmt_close($prev_stmt);
}

// ========== 3. PAYMENT SUMMARY ==========
$payment_query = "SELECT 
                  SUM(CASE WHEN status = 'paid' OR status = 'completed' THEN amount ELSE 0 END) as total_paid,
                  SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                  FROM payments WHERE client_id = ?";
$stmt = mysqli_prepare($conn, $payment_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$payment_result = mysqli_stmt_get_result($stmt);
$payments = mysqli_fetch_assoc($payment_result);
mysqli_stmt_close($stmt);

// ========== 4. DOCUMENT COUNT ==========
$doc_query = "SELECT COUNT(*) as doc_count FROM client_documents WHERE client_id = ?";
$stmt = mysqli_prepare($conn, $doc_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$doc_result = mysqli_stmt_get_result($stmt);
$documents = mysqli_fetch_assoc($doc_result);
mysqli_stmt_close($stmt);

// ========== 5. ACTIVE DISPUTES COUNT ==========
$dispute_query = "SELECT COUNT(*) as dispute_count FROM disputes WHERE client_id = ? AND status NOT IN ('resolved', 'closed')";
$stmt = mysqli_prepare($conn, $dispute_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$dispute_result = mysqli_stmt_get_result($stmt);
$disputes = mysqli_fetch_assoc($dispute_result);
mysqli_stmt_close($stmt);

// ========== 6. RECENT CASES ==========
if (mysqli_num_rows($check_table) > 0) {
    $recent_query = "SELECT c.id, c.case_no, c.service_type, c.status, c.amount, c.created_at,
                    CASE 
                        WHEN c.status = 'pending' THEN 0
                        WHEN c.status = 'processing' THEN 20
                        WHEN c.status = 'document_verification' THEN 40
                        WHEN c.status = 'dispute_filed' THEN 60
                        WHEN c.status = 'bank_response' THEN 80
                        WHEN c.status IN ('resolved', 'closed') THEN 100
                        ELSE 0
                    END as progress
                    FROM client_cases c
                    WHERE c.client_id = ?
                    ORDER BY c.created_at DESC LIMIT 5";
} else {
    $recent_query = "SELECT l.id, l.id as case_no, l.service_type, l.status, l.commission_amount as amount, l.created_at,
                    CASE 
                        WHEN l.status = 'converted' THEN 100
                        WHEN l.status = 'contacted' THEN 50
                        WHEN l.status = 'new' THEN 10
                        ELSE 0
                    END as progress
                    FROM leads l
                    WHERE l.customer_id = ?
                    ORDER BY l.created_at DESC LIMIT 5";
}

$stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$recent_result = mysqli_stmt_get_result($stmt);
$recent_cases = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== 7. SCORE HISTORY FOR CHART ==========
$history_query = "SELECT score, recorded_date FROM credit_scores WHERE client_id = ? ORDER BY recorded_date ASC LIMIT 12";
$stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);
$score_history = mysqli_fetch_all($history_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Format score history for chart
$formatted_history = [];
foreach ($score_history as $h) {
    $formatted_history[] = [
        'month' => date('M Y', strtotime($h['recorded_date'])),
        'score' => (int)$h['score']
    ];
}

// ========== 8. OPEN CASES FOR PAYMENT DROPDOWN ==========
if (mysqli_num_rows($check_table) > 0) {
    $open_cases_query = "SELECT case_no, service_type FROM client_cases WHERE client_id = ? AND status NOT IN ('resolved', 'closed')";
    $stmt = mysqli_prepare($conn, $open_cases_query);
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $open_result = mysqli_stmt_get_result($stmt);
    $open_cases = mysqli_fetch_all($open_result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $open_cases = [];
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'client' => [
        'id' => $client['id'],
        'name' => $client['name'],
        'email' => $client['email'],
        'phone' => $client['phone']
    ],
    'current_score' => $current_score,
    'previous_score' => $previous_score,
    'score_date' => $score_date,
    'score_history' => $formatted_history,
    'total_cases' => (int)($cases['total_cases'] ?? 0),
    'active_cases' => (int)($cases['active_cases'] ?? 0),
    'completed_cases' => (int)($cases['completed_cases'] ?? 0),
    'pending_cases' => (int)($cases['pending_cases'] ?? 0),
    'total_paid' => (float)($payments['total_paid'] ?? 0),
    'pending_amount' => (float)($payments['pending_amount'] ?? 0),
    'document_count' => (int)($documents['doc_count'] ?? 0),
    'active_disputes' => (int)($disputes['dispute_count'] ?? 0),
    'recent_cases' => $recent_cases,
    'open_cases' => $open_cases,
    'viewer_role' => $viewer_role
]);

mysqli_close($conn);
?>