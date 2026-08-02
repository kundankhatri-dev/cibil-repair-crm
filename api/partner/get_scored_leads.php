<?php
// ============================================================
// API: Get Scored Leads
// File: api/partner/get_scored_leads.php
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];
$priority = isset($_GET['priority']) ? $_GET['priority'] : 'all';

// ========== DIRECT DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== GET LEADS WITH SCORES ==========
$query = "SELECT id, name, phone, email, service_type, status, 
                 score, priority, created_at 
          FROM leads 
          WHERE partner_id = $partner_id";

if ($priority !== 'all') {
    $query .= " AND priority = '$priority'";
}

$query .= " ORDER BY score DESC, id DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

// ========== BUILD LEADS ARRAY ==========
$leads = [];
while ($row = mysqli_fetch_assoc($result)) {
    $score = (int)($row['score'] ?? 0);
    
    // Priority label based on score
    if ($score >= 70) $priority_label = 'Urgent';
    elseif ($score >= 50) $priority_label = 'High';
    elseif ($score >= 30) $priority_label = 'Medium';
    else $priority_label = 'Low';
    
    $leads[] = [
        'id' => (int)$row['id'],
        'customer_name' => $row['name'] ?? '—',
        'customer_phone' => $row['phone'] ?? '—',
        'customer_email' => $row['email'] ?? '—',
        'service_type' => $row['service_type'] ?? '—',
        'status' => $row['status'] ?? 'new',
        'score' => $score,
        'priority' => $row['priority'] ?? 'low',
        'priority_label' => $priority_label,
        'created_at' => $row['created_at'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'leads' => $leads,
    'total' => count($leads)
]);

mysqli_close($conn);
?>