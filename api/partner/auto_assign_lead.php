<?php
// api/partner/auto_assign_lead.php
// Automatically assign leads to agents based on rules

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'assign';

// Create automation rules table
$rulesTable = 'auto_assign_rules';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$rulesTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $rulesTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        rule_name VARCHAR(100),
        criteria_field VARCHAR(50),
        criteria_value VARCHAR(100),
        agent_id INT,
        priority INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner (partner_id)
    )";
    mysqli_query($conn, $createTable);
}

// Create lead queue table
$queueTable = 'lead_assignment_queue';
$checkQueue = mysqli_query($conn, "SHOW TABLES LIKE '$queueTable'");
if (mysqli_num_rows($checkQueue) == 0) {
    $createQueue = "CREATE TABLE $queueTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        assigned_to INT,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'assigned', 'failed') DEFAULT 'pending',
        retry_count INT DEFAULT 0,
        INDEX idx_lead (lead_id)
    )";
    mysqli_query($conn, $createQueue);
}

if ($action === 'assign') {
    // Get unassigned leads
    $leadsTable = 'partner_leads';
    $checkLeads = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkLeads) == 0) {
        $leadsTable = 'leads';
    }
    
    $query = "SELECT l.id, l.customer_name, l.customer_phone, l.service_type, l.source 
              FROM $leadsTable l
              LEFT JOIN $queueTable q ON l.id = q.lead_id
              WHERE l.partner_id = ? AND l.status = 'new' AND (q.id IS NULL OR q.status = 'pending')
              LIMIT 10";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $leads = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Get agents
    $agentsTable = 'partner_agents';
    $checkAgents = mysqli_query($conn, "SHOW TABLES LIKE '$agentsTable'");
    if (mysqli_num_rows($checkAgents) > 0) {
        $agent_query = "SELECT id, agent_name, total_leads FROM $agentsTable WHERE parent_partner_id = ? AND status = 'active' ORDER BY total_leads ASC";
        $agent_stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($agent_stmt, "i", $partner_id);
        mysqli_stmt_execute($agent_stmt);
        $agent_result = mysqli_stmt_get_result($agent_stmt);
        $agents = mysqli_fetch_all($agent_result, MYSQLI_ASSOC);
    } else {
        $agents = [];
    }
    
    $assigned = 0;
    foreach ($leads as $index => $lead) {
        // Round-robin assignment
        $agent_index = $index % max(1, count($agents));
        $assigned_to = !empty($agents) ? $agents[$agent_index]['id'] : $partner_id;
        
        $queue_insert = mysqli_prepare($conn, "INSERT INTO $queueTable (lead_id, assigned_to, status) VALUES (?, ?, 'assigned')");
        mysqli_stmt_bind_param($queue_insert, "ii", $lead['id'], $assigned_to);
        mysqli_stmt_execute($queue_insert);
        $assigned++;
    }
    
    echo json_encode([
        'success' => true,
        'leads_assigned' => $assigned,
        'pending_leads' => count($leads),
        'available_agents' => count($agents),
        'assignment_mode' => 'round_robin'
    ]);
    
} elseif ($action === 'create_rule') {
    $data = json_decode(file_get_contents('php://input'), true);
    $rule_name = $data['rule_name'] ?? '';
    $criteria_field = $data['criteria_field'] ?? 'service_type';
    $criteria_value = $data['criteria_value'] ?? '';
    $agent_id = $data['agent_id'] ?? 0;
    
    $insert = mysqli_prepare($conn, "INSERT INTO $rulesTable (partner_id, rule_name, criteria_field, criteria_value, agent_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "isssi", $partner_id, $rule_name, $criteria_field, $criteria_value, $agent_id);
    
    if (mysqli_stmt_execute($insert)) {
        echo json_encode(['success' => true, 'message' => 'Assignment rule created']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create rule']);
    }
}

mysqli_close($conn);
?>