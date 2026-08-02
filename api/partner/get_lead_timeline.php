<?php
// api/partner/get_lead_timeline.php
// Complete lead timeline view with all interactions

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Lead ID required']);
    exit;
}

// Determine leads table
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Verify lead ownership
$check_stmt = mysqli_prepare($conn, "SELECT id, customer_name FROM $leadsTable WHERE id = ? AND partner_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $lead_id, $partner_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$lead = mysqli_fetch_assoc($check_result);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    exit;
}

$timeline = [];

// 1. Lead created event
$timeline[] = [
    'type' => 'lead_created',
    'icon' => 'fa-plus-circle',
    'color' => 'success',
    'title' => 'Lead Created',
    'description' => "Lead for {$lead['customer_name']} was created",
    'timestamp' => date('Y-m-d H:i:s'),
    'formatted_date' => date('d M Y, h:i A')
];

// 2. Status changes (from lead history if table exists)
$historyTable = 'lead_status_history';
$checkHistoryTable = mysqli_query($conn, "SHOW TABLES LIKE '$historyTable'");
if (mysqli_num_rows($checkHistoryTable) > 0) {
    $history_query = "SELECT old_status, new_status, changed_by, notes, created_at 
                      FROM $historyTable WHERE lead_id = ? ORDER BY created_at ASC";
    $history_stmt = mysqli_prepare($conn, $history_query);
    mysqli_stmt_bind_param($history_stmt, "i", $lead_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    $history_items = mysqli_fetch_all($history_result, MYSQLI_ASSOC);
    
    foreach ($history_items as $item) {
        $timeline[] = [
            'type' => 'status_change',
            'icon' => 'fa-exchange-alt',
            'color' => 'info',
            'title' => 'Status Changed',
            'description' => "Status changed from '{$item['old_status']}' to '{$item['new_status']}'",
            'notes' => $item['notes'],
            'timestamp' => $item['created_at'],
            'formatted_date' => date('d M Y, h:i A', strtotime($item['created_at']))
        ];
    }
}

// 3. Follow-ups
$followupsTable = 'partner_lead_followups';
$checkFollowupsTable = mysqli_query($conn, "SHOW TABLES LIKE '$followupsTable'");
if (mysqli_num_rows($checkFollowupsTable) > 0) {
    $followup_query = "SELECT followup_date, notes, status, created_at 
                       FROM $followupsTable WHERE lead_id = ? ORDER BY followup_date DESC";
    $followup_stmt = mysqli_prepare($conn, $followup_query);
    mysqli_stmt_bind_param($followup_stmt, "i", $lead_id);
    mysqli_stmt_execute($followup_stmt);
    $followup_result = mysqli_stmt_get_result($followup_stmt);
    $followups = mysqli_fetch_all($followup_result, MYSQLI_ASSOC);
    
    foreach ($followups as $followup) {
        $timeline[] = [
            'type' => 'followup',
            'icon' => 'fa-calendar-alt',
            'color' => 'warning',
            'title' => 'Follow-up Scheduled',
            'description' => "Follow-up on " . date('d M Y', strtotime($followup['followup_date'])),
            'notes' => $followup['notes'],
            'status' => $followup['status'],
            'timestamp' => $followup['created_at'],
            'formatted_date' => date('d M Y, h:i A', strtotime($followup['created_at']))
        ];
    }
}

// 4. Notes
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable");
while ($col = mysqli_fetch_assoc($colResult)) {
    $columns[] = $col['Field'];
}

if (in_array('notes', $columns)) {
    $note_query = "SELECT notes, updated_at FROM $leadsTable WHERE id = ? AND notes IS NOT NULL AND notes != ''";
    $note_stmt = mysqli_prepare($conn, $note_query);
    mysqli_stmt_bind_param($note_stmt, "i", $lead_id);
    mysqli_stmt_execute($note_stmt);
    $note_result = mysqli_stmt_get_result($note_stmt);
    $note_data = mysqli_fetch_assoc($note_result);
    
    if ($note_data && !empty($note_data['notes'])) {
        // Parse individual notes (assuming timestamped format)
        $notes_lines = explode("\n", $note_data['notes']);
        foreach ($notes_lines as $line) {
            if (preg_match('/\[(.*?)\]\s*(.*)/', $line, $matches)) {
                $timeline[] = [
                    'type' => 'note',
                    'icon' => 'fa-sticky-note',
                    'color' => 'secondary',
                    'title' => 'Note Added',
                    'description' => $matches[2],
                    'timestamp' => $matches[1],
                    'formatted_date' => date('d M Y, h:i A', strtotime($matches[1]))
                ];
            }
        }
    }
}

// 5. Documents uploaded
$documentsTable = 'partner_documents';
$checkDocsTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkDocsTable) > 0) {
    $docs_query = "SELECT document_name, document_type, uploaded_at FROM $documentsTable 
                   WHERE lead_id = ? AND partner_id = ? AND status = 'active'";
    $docs_stmt = mysqli_prepare($conn, $docs_query);
    mysqli_stmt_bind_param($docs_stmt, "ii", $lead_id, $partner_id);
    mysqli_stmt_execute($docs_stmt);
    $docs_result = mysqli_stmt_get_result($docs_stmt);
    $documents = mysqli_fetch_all($docs_result, MYSQLI_ASSOC);
    
    foreach ($documents as $doc) {
        $timeline[] = [
            'type' => 'document',
            'icon' => 'fa-file-upload',
            'color' => 'primary',
            'title' => 'Document Uploaded',
            'description' => "Uploaded: {$doc['document_name']} ({$doc['document_type']})",
            'timestamp' => $doc['uploaded_at'],
            'formatted_date' => date('d M Y, h:i A', strtotime($doc['uploaded_at']))
        ];
    }
}

// 6. Commission earned (if converted)
$commissionTable = 'partner_commissions';
$checkCommTable = mysqli_query($conn, "SHOW TABLES LIKE '$commissionTable'");
if (mysqli_num_rows($checkCommTable) > 0) {
    $comm_query = "SELECT commission_amount, created_at FROM $commissionTable WHERE lead_id = ?";
    $comm_stmt = mysqli_prepare($conn, $comm_query);
    mysqli_stmt_bind_param($comm_stmt, "i", $lead_id);
    mysqli_stmt_execute($comm_stmt);
    $comm_result = mysqli_stmt_get_result($comm_stmt);
    $commission = mysqli_fetch_assoc($comm_result);
    
    if ($commission) {
        $timeline[] = [
            'type' => 'commission',
            'icon' => 'fa-rupee-sign',
            'color' => 'success',
            'title' => 'Commission Earned',
            'description' => "Commission of ₹" . number_format($commission['commission_amount'], 2) . " earned",
            'timestamp' => $commission['created_at'],
            'formatted_date' => date('d M Y, h:i A', strtotime($commission['created_at']))
        ];
    }
}

// Sort timeline by timestamp (newest first)
usort($timeline, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

echo json_encode([
    'success' => true,
    'lead' => [
        'id' => $lead_id,
        'name' => $lead['customer_name']
    ],
    'timeline' => $timeline,
    'total_events' => count($timeline)
]);

mysqli_close($conn);
?>