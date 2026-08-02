<?php
// api/partner/sub_partners.php
// Manage sub-partners and team members

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

// Create sub-partners table
$subTable = 'partner_agents';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$subTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $subTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_partner_id INT NOT NULL,
        agent_name VARCHAR(100),
        agent_email VARCHAR(100) UNIQUE,
        agent_phone VARCHAR(15),
        commission_share DECIMAL(5,2) DEFAULT 5.00,
        total_leads INT DEFAULT 0,
        total_converted INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_parent (parent_partner_id)
    )";
    mysqli_query($conn, $createTable);
}

if ($action === 'list') {
    $query = "SELECT * FROM $subTable WHERE parent_partner_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $agents = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'agents' => $agents,
        'total_agents' => count($agents)
    ]);
    
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $commission_share = $data['commission_share'] ?? 5;
    
    $insert = mysqli_prepare($conn, "INSERT INTO $subTable (parent_partner_id, agent_name, agent_email, agent_phone, commission_share) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "isssd", $partner_id, $name, $email, $phone, $commission_share);
    
    if (mysqli_stmt_execute($insert)) {
        echo json_encode([
            'success' => true,
            'message' => 'Agent added successfully',
            'agent_id' => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add agent']);
    }
    
} elseif ($action === 'delete') {
    $agent_id = $_GET['id'] ?? 0;
    $delete = mysqli_prepare($conn, "DELETE FROM $subTable WHERE id = ? AND parent_partner_id = ?");
    mysqli_stmt_bind_param($delete, "ii", $agent_id, $partner_id);
    
    if (mysqli_stmt_execute($delete)) {
        echo json_encode(['success' => true, 'message' => 'Agent removed']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove agent']);
    }
}

mysqli_close($conn);
?>