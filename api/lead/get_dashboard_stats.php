<?php
// api/lead/get_dashboard_stats.php - Lead Dashboard Statistics
session_start();
header('Content-Type: application/json');

// Allow only sales team or admin
$allowed_roles = ['sales', 'bd', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

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

// Create leads table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    source ENUM('website', 'whatsapp', 'facebook', 'google', 'referral', 'other') DEFAULT 'website',
    stage ENUM('new', 'contacted', 'analysis', 'proposal', 'converted', 'lost') DEFAULT 'new',
    score INT DEFAULT 0,
    notes TEXT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stage (stage),
    INDEX idx_source (source),
    INDEX idx_created (created_at)
)";

mysqli_query($conn, $create_table);

// Create followups table
$create_followups = "CREATE TABLE IF NOT EXISTS lead_followups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    followup_date DATETIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    INDEX idx_lead (lead_id),
    INDEX idx_date (followup_date),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
)";

mysqli_query($conn, $create_followups);

// 1. Total leads
$total_query = "SELECT COUNT(*) as total FROM leads";
$total_result = mysqli_query($conn, $total_query);
$total_leads = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// 2. Converted leads
$converted_query = "SELECT COUNT(*) as converted FROM leads WHERE stage = 'converted'";
$converted_result = mysqli_query($conn, $converted_query);
$converted_leads = mysqli_fetch_assoc($converted_result)['converted'] ?? 0;

// 3. New leads this month
$month_query = "SELECT COUNT(*) as new_month FROM leads WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$month_result = mysqli_query($conn, $month_query);
$new_leads_month = mysqli_fetch_assoc($month_result)['new_month'] ?? 0;

// 4. Conversion rate
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 1) : 0;

// 5. Pending follow-ups today
$today = date('Y-m-d');
$followup_query = "SELECT COUNT(*) as pending FROM lead_followups WHERE DATE(followup_date) <= '$today' AND status = 'pending'";
$followup_result = mysqli_query($conn, $followup_query);
$pending_followups = mysqli_fetch_assoc($followup_result)['pending'] ?? 0;

// 6. Avg response time (simplified - time from lead creation to first contact)
$avg_response_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours 
                       FROM leads WHERE stage != 'new' AND updated_at IS NOT NULL";
$avg_result = mysqli_query($conn, $avg_response_query);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_response_time = round($avg_data['avg_hours'] ?? 24, 1);

// 7. Pipeline data by stage
$pipeline_query = "SELECT stage, COUNT(*) as count FROM leads GROUP BY stage";
$pipeline_result = mysqli_query($conn, $pipeline_query);
$pipeline_data = [];
while ($row = mysqli_fetch_assoc($pipeline_result)) {
    $pipeline_data[$row['stage']] = (int)$row['count'];
}

$stage_labels = ['new', 'contacted', 'analysis', 'proposal', 'converted', 'lost'];
$stage_names = [
    'new' => 'New Leads',
    'contacted' => 'Contacted',
    'analysis' => 'Credit Analysis',
    'proposal' => 'Proposal Sent',
    'converted' => 'Converted',
    'lost' => 'Lost'
];
$stage_values = [];
foreach ($stage_labels as $stage) {
    $stage_values[] = $pipeline_data[$stage] ?? 0;
}

// 8. Source data
$source_query = "SELECT source, COUNT(*) as count FROM leads GROUP BY source";
$source_result = mysqli_query($conn, $source_query);
$source_labels = [];
$source_values = [];
while ($row = mysqli_fetch_assoc($source_result)) {
    $source_labels[] = ucfirst($row['source']);
    $source_values[] = (int)$row['count'];
}

// 9. Recent leads (last 10)
$recent_query = "SELECT l.*, 
                    (SELECT COUNT(*) FROM lead_followups WHERE lead_id = l.id AND status = 'pending' AND followup_date >= NOW()) as has_followup,
                    (SELECT followup_date FROM lead_followups WHERE lead_id = l.id AND status = 'pending' ORDER BY followup_date ASC LIMIT 1) as next_followup
                 FROM leads l 
                 ORDER BY l.created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
$recent_leads = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    // Calculate simple score based on source and stage
    $score = 50;
    if ($row['source'] == 'referral') $score += 20;
    if ($row['source'] == 'website') $score += 10;
    if ($row['stage'] == 'converted') $score += 30;
    if ($row['stage'] == 'analysis') $score += 15;
    $row['score'] = min(100, $score);
    
    $recent_leads[] = $row;
}

echo json_encode([
    'success' => true,
    'total_leads' => $total_leads,
    'converted_leads' => $converted_leads,
    'new_leads_month' => $new_leads_month,
    'conversion_rate' => $conversion_rate,
    'pending_followups' => $pending_followups,
    'avg_response_time' => $avg_response_time,
    'pipeline_data' => [
        'labels' => array_values($stage_names),
        'values' => $stage_values
    ],
    'source_data' => [
        'labels' => $source_labels,
        'values' => $source_values
    ],
    'recent_leads' => $recent_leads
]);

mysqli_close($conn);
?>