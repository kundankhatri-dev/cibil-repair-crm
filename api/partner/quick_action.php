<?php
// api/partner/quick_action.php
// One-click actions for mobile app

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

if ($action === 'quick_add_lead') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = $data['phone'] ?? '';
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'Phone number required']);
        exit;
    }
    
    // Fetch lead details from quick add (uses caller ID)
    $leadsTable = 'partner_leads';
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkTable) == 0) {
        $leadsTable = 'leads';
    }
    
    // Check if lead already exists
    $check = mysqli_prepare($conn, "SELECT id FROM $leadsTable WHERE customer_phone = ? AND partner_id = ?");
    mysqli_stmt_bind_param($check, "si", $phone, $partner_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    
    if (mysqli_stmt_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Lead already exists']);
        exit;
    }
    
    // Add lead with minimal info
    $insert = mysqli_prepare($conn, "INSERT INTO $leadsTable (partner_id, customer_phone, customer_name, source, status) VALUES (?, ?, 'Quick Lead', 'Mobile App', 'new')");
    mysqli_stmt_bind_param($insert, "is", $partner_id, $phone);
    
    if (mysqli_stmt_execute($insert)) {
        $response = [
            'success' => true,
            'message' => 'Lead added successfully',
            'lead_id' => mysqli_insert_id($conn)
        ];
    }
    
} elseif ($action === 'quick_followup') {
    $data = json_decode(file_get_contents('php://input'), true);
    $lead_id = $data['lead_id'] ?? 0;
    $followup_date = date('Y-m-d H:i:s', strtotime('+1 day'));
    
    $followupsTable = 'partner_lead_followups';
    $checkFollowupsTable = mysqli_query($conn, "SHOW TABLES LIKE '$followupsTable'");
    if (mysqli_num_rows($checkFollowupsTable) > 0) {
        $insert = mysqli_prepare($conn, "INSERT INTO $followupsTable (lead_id, followup_date, status) VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($insert, "is", $lead_id, $followup_date);
        
        if (mysqli_stmt_execute($insert)) {
            $response = [
                'success' => true,
                'message' => 'Follow-up scheduled for tomorrow'
            ];
        }
    }
    
} elseif ($action === 'quick_status_update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $lead_id = $data['lead_id'] ?? 0;
    $new_status = $data['status'] ?? 'contacted';
    
    $leadsTable = 'partner_leads';
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkTable) == 0) {
        $leadsTable = 'leads';
    }
    
    $update = mysqli_prepare($conn, "UPDATE $leadsTable SET status = ? WHERE id = ? AND partner_id = ?");
    mysqli_stmt_bind_param($update, "sii", $new_status, $lead_id, $partner_id);
    
    if (mysqli_stmt_execute($update)) {
        $response = [
            'success' => true,
            'message' => 'Status updated to ' . ucfirst($new_status)
        ];
    }
    
} elseif ($action === 'dashboard_summary') {
    // Quick dashboard summary for mobile
    $leadsTable = 'partner_leads';
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkTable) == 0) {
        $leadsTable = 'leads';
    }
    
    $query = "SELECT 
        COUNT(*) as total_leads,
        SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
        SUM(commission_amount) as total_commission
        FROM $leadsTable WHERE partner_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($result);
    
    $response = [
        'success' => true,
        'total_leads' => (int)$stats['total_leads'],
        'converted' => (int)$stats['converted'],
        'total_commission' => round($stats['total_commission'], 2),
        'conversion_rate' => $stats['total_leads'] > 0 ? round(($stats['converted'] / $stats['total_leads']) * 100, 1) : 0,
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

echo json_encode($response);

mysqli_close($conn);
?>