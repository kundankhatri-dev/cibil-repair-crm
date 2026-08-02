<?php
// api/client/config.php - Client Portal Configuration
require_once __DIR__ . '/../config.php';

// Client portal configuration
define('CLIENT_PORTAL_URL', 'https://cibilrepair.in/client-dashboard.html');

// Document upload settings
define('CLIENT_MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('CLIENT_ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('CLIENT_ALLOWED_MIMES', [
    'application/pdf',
    'image/jpeg', 'image/jpg', 'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Case status stages
$CASE_STAGES = [
    'pending' => ['label' => 'Pending', 'icon' => '⏳', 'progress' => 0, 'color' => '#d97706'],
    'processing' => ['label' => 'Processing', 'icon' => '🔄', 'progress' => 20, 'color' => '#2563eb'],
    'document_verification' => ['label' => 'Document Verification', 'icon' => '📄', 'progress' => 40, 'color' => '#2563eb'],
    'dispute_filed' => ['label' => 'Dispute Filed', 'icon' => '⚖️', 'progress' => 60, 'color' => '#7c3aed'],
    'bank_response' => ['label' => 'Bank Response', 'icon' => '🏦', 'progress' => 80, 'color' => '#d97706'],
    'resolved' => ['label' => 'Resolved', 'icon' => '✅', 'progress' => 100, 'color' => '#059669'],
    'closed' => ['label' => 'Closed', 'icon' => '🔒', 'progress' => 100, 'color' => '#6b7280']
];

// Credit score ranges
$SCORE_RANGES = [
    'excellent' => ['min' => 750, 'max' => 900, 'label' => 'Excellent', 'color' => '#34d399', 'icon' => '🏆'],
    'good' => ['min' => 650, 'max' => 749, 'label' => 'Good', 'color' => '#60a5fa', 'icon' => '📈'],
    'average' => ['min' => 550, 'max' => 649, 'label' => 'Average', 'color' => '#fbbf24', 'icon' => '⚠️'],
    'poor' => ['min' => 300, 'max' => 549, 'label' => 'Poor', 'color' => '#f87171', 'icon' => '🔴']
];

// Function to get client data (reusable)
function getClientById($pdo, $client_id) {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$client_id]);
    return $stmt->fetch();
}

// Function to verify client access (for partner/admin viewing)
function verifyClientAccess($pdo, $viewer_id, $viewer_role, $client_id) {
    if ($viewer_role === 'admin') {
        return true;
    }
    if ($viewer_role === 'partner') {
        // Check if client is linked to this partner
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$viewer_id, $client_id]);
        return $stmt->fetchColumn() > 0;
    }
    return $viewer_id == $client_id;
}
?>