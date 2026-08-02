<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB failed']); exit; }

// Create support_tickets table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT NOT NULL, ticket_no VARCHAR(50),
    subject VARCHAR(255), category VARCHAR(50), priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    message TEXT, status ENUM('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    assigned_to INT, resolved_at DATETIME, rating INT, sla_due DATETIME, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create ticket_replies table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT, ticket_id INT NOT NULL, user_id INT NOT NULL,
    message TEXT, is_admin TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create whatsapp_chats table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS whatsapp_chats (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT NOT NULL, message TEXT,
    is_incoming TINYINT DEFAULT 1, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create email_logs table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT, from_email VARCHAR(100), to_email VARCHAR(100),
    subject VARCHAR(255), message TEXT, status VARCHAR(20), received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create call_logs table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS call_logs (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT, call_type ENUM('incoming','outgoing'),
    duration INT, agent_id INT, notes TEXT, call_time DATETIME
)");

// Create ticket_escalations table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ticket_escalations (
    id INT PRIMARY KEY AUTO_INCREMENT, ticket_id INT, reason TEXT,
    escalated_to INT, status ENUM('pending','resolved') DEFAULT 'pending', escalated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create faqs table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS faqs (
    id INT PRIMARY KEY AUTO_INCREMENT, question VARCHAR(255), answer TEXT,
    category VARCHAR(50), updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create reply_templates table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reply_templates (
    id INT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(100), category VARCHAR(50), template TEXT
)");

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets"))['c'] ?? 0;
$open = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE status IN ('open','in_progress','waiting')"))['c'] ?? 0;
$avg_resp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg FROM support_tickets WHERE status='resolved'"))['avg'] ?? 0;
$csat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM support_tickets WHERE rating IS NOT NULL"))['avg'] ?? 0;

// Trend data (last 6 months)
$labels = []; $values = [];
for ($i = 5; $i >= 0; $i--) {
    $labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $values[] = $cnt;
}

// Category distribution
$cats = ['Score Query', 'Dispute Status', 'Loan Status', 'Refund Query', 'Document Request', 'Other'];
$cat_vals = [];
foreach ($cats as $cat) {
    $cat_vals[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE category='$cat'"))['c'] ?? 0;
}

// Recent tickets
$recent = mysqli_query($conn, "SELECT t.*, u.name as client_name FROM support_tickets t JOIN users u ON t.client_id = u.id ORDER BY t.created_at DESC LIMIT 10");
$recent_tickets = [];
while ($row = mysqli_fetch_assoc($recent)) {
    $sla_status = '';
    $sla_class = '';
    if ($row['sla_due']) {
        $now = new DateTime();
        $due = new DateTime($row['sla_due']);
        if ($now > $due && $row['status'] != 'resolved') { $sla_status = 'Breached'; $sla_class = 'sla-critical'; }
        elseif ($now->diff($due)->days <= 1) { $sla_status = 'At Risk'; $sla_class = 'sla-warning'; }
        else { $sla_status = 'On Track'; $sla_class = 'sla-good'; }
    } else { $sla_status = 'Not Set'; $sla_class = ''; }
    $row['sla_status'] = $sla_status;
    $row['sla_class'] = $sla_class;
    $recent_tickets[] = $row;
}

echo json_encode([
    'success' => true, 'total_tickets' => $total, 'open_tickets' => $open,
    'avg_response_time' => round($avg_resp), 'csat' => round($csat * 20),
    'trend_data' => ['labels' => $labels, 'values' => $values],
    'category_data' => ['labels' => $cats, 'values' => $cat_vals],
    'recent_tickets' => $recent_tickets
]);
mysqli_close($conn);
?>