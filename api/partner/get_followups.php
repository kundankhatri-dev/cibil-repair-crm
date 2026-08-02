<?php
// ============================================================
// API: Partner Get Follow-ups - WORKING VERSION
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== CHECK IF TABLE EXISTS ==========
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'followups'");
$tableExists = mysqli_num_rows($tableCheck) > 0;

// ========== GET FOLLOW-UPS ==========
if ($tableExists) {
    $query = "SELECT f.*, 
              COALESCE(l.name, 'Deleted Lead') as customer_name, 
              COALESCE(l.phone, '—') as customer_phone 
              FROM followups f 
              LEFT JOIN leads l ON f.lead_id = l.id 
              WHERE f.partner_id = $partner_id 
              ORDER BY f.follow_up_date ASC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode([
            'success' => false, 
            'error' => 'Query failed: ' . mysqli_error($conn)
        ]);
        mysqli_close($conn);
        exit;
    }
    
    $followups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $followups[] = [
            'id' => (int)$row['id'],
            'lead_id' => (int)$row['lead_id'],
            'customer_name' => $row['customer_name'] ?? '—',
            'customer_phone' => $row['customer_phone'] ?? '—',
            'follow_up_date' => $row['follow_up_date'] ?? '',
            'notes' => $row['notes'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'created_at' => $row['created_at'] ?? '',
            'completed_at' => $row['completed_at'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'followups' => $followups,
        'total' => count($followups)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Followups table does not exist'
    ]);
}

mysqli_close($conn);
?>