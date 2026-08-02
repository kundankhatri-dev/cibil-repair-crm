<?php
// api/partner/get_lead_details.php
// Partner Get Lead Details API - View complete lead information with history

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$partner_name = $role_data['name'];

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// ========== GET TABLE COLUMN NAMES ==========
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $columns[] = $col['Field'];
    }
}

$nameCol = in_array('customer_name', $columns) ? 'customer_name' : (in_array('name', $columns) ? 'name' : 'customer_name');
$phoneCol = in_array('customer_phone', $columns) ? 'customer_phone' : (in_array('phone', $columns) ? 'phone' : 'customer_phone');
$emailCol = in_array('customer_email', $columns) ? 'customer_email' : (in_array('email', $columns) ? 'email' : 'customer_email');
$serviceCol = in_array('service_type', $columns) ? 'service_type' : (in_array('service', $columns) ? 'service' : 'service_type');
$sourceCol = in_array('source', $columns) ? 'source' : 'source';
$notesCol = in_array('notes', $columns) ? 'notes' : null;
$commissionCol = in_array('commission_amount', $columns) ? 'commission_amount' : 'commission_amount';
$statusCol = in_array('status', $columns) ? 'status' : 'status';

// ========== GET INPUT ==========
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Lead ID is required']);
    exit;
}

// ========== GET LEAD DETAILS ==========
$query = "SELECT 
            l.id,
            l.$nameCol as customer_name,
            l.$phoneCol as customer_phone,
            COALESCE(l.$emailCol, '-') as customer_email,
            COALESCE(l.$serviceCol, '-') as service,
            COALESCE(l.$sourceCol, '-') as source,
            l.$statusCol as status,
            COALESCE(l.$commissionCol, 0) as commission_amount,
            l.$notesCol as notes,
            DATE_FORMAT(l.created_at, '%d-%m-%Y %h:%i %p') as created_at,
            DATE_FORMAT(l.created_at, '%Y-%m-%d') as created_date,
            DATE_FORMAT(l.updated_at, '%d-%m-%Y %h:%i %p') as updated_at
          FROM $leadsTable l
          WHERE l.id = ? AND l.partner_id = ?";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $lead_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lead = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
    exit;
}

// Format values
$lead['commission_amount'] = (float)$lead['commission_amount'];
$lead['conversion_potential'] = calculateConversionPotential($lead['status'], $lead['created_date']);
$lead['days_old'] = (int)ceil((time() - strtotime($lead['created_date'])) / (60 * 60 * 24));

// Format notes with line breaks
if (!empty($lead['notes'])) {
    $lead['notes_html'] = nl2br(htmlspecialchars($lead['notes']));
    $lead['notes_preview'] = strlen($lead['notes']) > 200 ? substr($lead['notes'], 0, 200) . '...' : $lead['notes'];
} else {
    $lead['notes_html'] = '';
    $lead['notes_preview'] = '';
}

// Status badge mapping
$status_badges = [
    'new' => 'danger',
    'contacted' => 'warning',
    'converted' => 'success',
    'lost' => 'secondary'
];
$lead['status_badge'] = $status_badges[$lead['status']] ?? 'secondary';
$lead['status_label'] = ucfirst($lead['status']);

// ========== GET FOLLOW-UP HISTORY ==========
$followups = [];
$followupsTable = 'partner_lead_followups';
$checkFollowupTable = mysqli_query($conn, "SHOW TABLES LIKE '$followupsTable'");
if (mysqli_num_rows($checkFollowupTable) > 0) {
    $followup_query = "SELECT 
                        id,
                        DATE_FORMAT(followup_date, '%d-%m-%Y %h:%i %p') as followup_date,
                        followup_date as followup_raw,
                        notes,
                        status,
                        DATE_FORMAT(created_at, '%d-%m-%Y %h:%i %p') as created_at
                      FROM $followupsTable 
                      WHERE lead_id = ? 
                      ORDER BY followup_date DESC";
    
    $followup_stmt = mysqli_prepare($conn, $followup_query);
    mysqli_stmt_bind_param($followup_stmt, "i", $lead_id);
    mysqli_stmt_execute($followup_stmt);
    $followup_result = mysqli_stmt_get_result($followup_stmt);
    $followups = mysqli_fetch_all($followup_result, MYSQLI_ASSOC);
    mysqli_stmt_close($followup_stmt);
    
    // Status badge for followups
    foreach ($followups as &$followup) {
        $follow_badges = ['pending' => 'warning', 'completed' => 'success', 'missed' => 'danger', 'rescheduled' => 'info'];
        $followup['status_badge'] = $follow_badges[$followup['status']] ?? 'secondary';
    }
}

// ========== GET DOCUMENTS COUNT ==========
$documentsCount = 0;
$documentsTable = 'partner_documents';
$checkDocsTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkDocsTable) > 0) {
    $docs_query = "SELECT COUNT(*) as count FROM $documentsTable WHERE lead_id = ? AND partner_id = ? AND status = 'active'";
    $docs_stmt = mysqli_prepare($conn, $docs_query);
    mysqli_stmt_bind_param($docs_stmt, "ii", $lead_id, $partner_id);
    mysqli_stmt_execute($docs_stmt);
    $docs_result = mysqli_stmt_get_result($docs_stmt);
    $docs_data = mysqli_fetch_assoc($docs_result);
    $documentsCount = $docs_data['count'] ?? 0;
    mysqli_stmt_close($docs_stmt);
}

// ========== GET ACTIVITY LOG FOR THIS LEAD ==========
$activities = [];
$checkActivitiesTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
if (mysqli_num_rows($checkActivitiesTable) > 0) {
    // Safer query using lead_id directly
    $activity_query = "SELECT 
                        activity_type,
                        description,
                        DATE_FORMAT(created_at, '%d-%m-%Y %h:%i %p') as created_at,
                        DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
                      FROM activities 
                      WHERE (user_id = ? AND description LIKE ?)
                      OR description LIKE ?
                      ORDER BY created_at DESC 
                      LIMIT 15";
    
    $search_pattern1 = "%lead #$lead_id%";
    $search_pattern2 = "%customer $lead_id%";
    $search_pattern3 = "%Lead ID $lead_id%";
    
    $activity_stmt = mysqli_prepare($conn, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, "isss", $partner_id, $search_pattern1, $search_pattern2, $search_pattern3);
    mysqli_stmt_execute($activity_stmt);
    $activity_result = mysqli_stmt_get_result($activity_stmt);
    $activities = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);
    mysqli_stmt_close($activity_stmt);
}

// If no activities found, create default
if (empty($activities)) {
    $activities = [
        [
            'activity_type' => 'lead_created',
            'description' => 'Lead was created',
            'created_at' => $lead['created_at'],
            'created_date' => $lead['created_date']
        ]
    ];
}

// ========== GET RECOMMENDATIONS ==========
$recommendations = [];
if ($lead['status'] === 'new') {
    $recommendations[] = [
        'type' => 'action',
        'title' => 'Call Customer',
        'description' => 'Contact this lead within 24 hours for better conversion chances',
        'priority' => 'high',
        'action' => 'call'
    ];
    $recommendations[] = [
        'type' => 'action',
        'title' => 'Send Service Details',
        'description' => 'Share relevant service information via email or WhatsApp',
        'priority' => 'medium',
        'action' => 'message'
    ];
} elseif ($lead['status'] === 'contacted') {
    $recommendations[] = [
        'type' => 'action',
        'title' => 'Schedule Follow-up',
        'description' => 'Set a follow-up reminder to check customer interest',
        'priority' => 'high',
        'action' => 'followup'
    ];
} elseif ($lead['status'] === 'converted') {
    $recommendations[] = [
        'type' => 'congrats',
        'title' => '🎉 Lead Converted!',
        'description' => 'Commission of ₹' . number_format($lead['commission_amount'], 2) . ' will be credited soon',
        'priority' => 'success',
        'action' => 'view_commission'
    ];
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'lead' => $lead,
    'followups' => $followups,
    'followup_count' => count($followups),
    'documents_count' => $documentsCount,
    'activities' => $activities,
    'recommendations' => $recommendations,
    'stats' => [
        'total_followups' => count($followups),
        'has_notes' => !empty($lead['notes']),
        'age_days' => $lead['days_old'],
        'conversion_potential' => $lead['conversion_potential']
    ]
]);

// ========== HELPER FUNCTIONS ==========
function calculateConversionPotential($status, $created_date) {
    if ($status === 'converted') return 100;
    if ($status === 'lost') return 0;
    
    $days_old = (time() - strtotime($created_date)) / (60 * 60 * 24);
    
    if ($days_old <= 2) return 85;
    if ($days_old <= 5) return 65;
    if ($days_old <= 10) return 45;
    if ($days_old <= 20) return 25;
    return 10;
}

mysqli_close($conn);
?>