<?php
include '../config/database.php';  // ← This goes UP one level, then INTO config folder

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($input['action'] ?? '');
$partner_email = $_GET['email'] ?? ($input['partner_email'] ?? '');

if (!$partner_email && $action !== 'getLeaderboard' && $action !== 'refreshLeaderboardPoints') {
    die(json_encode(['success' => false, 'error' => 'Partner email required']));
}

// ==================== CONNECTORS ====================
if ($action === 'getConnectors') {
    $stmt = $conn->prepare("SELECT * FROM partner_connectors WHERE partner_email = ? ORDER BY id DESC");
    $stmt->bind_param("s", $partner_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $connectors = [];
    while ($row = $result->fetch_assoc()) {
        $connectors[] = $row;
    }
    echo json_encode($connectors);
    exit;
}

if ($action === 'addConnector') {
    $name = $conn->real_escape_string($input['name']);
    $contact = $conn->real_escape_string($input['contact'] ?? '');
    $rate = (float)$input['rate'];
    $stmt = $conn->prepare("INSERT INTO partner_connectors (partner_email, name, contact, rate) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $partner_email, $name, $contact, $rate);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'updateConnector') {
    $id = (int)$input['id'];
    $name = $conn->real_escape_string($input['name']);
    $contact = $conn->real_escape_string($input['contact'] ?? '');
    $rate = (float)$input['rate'];
    $stmt = $conn->prepare("UPDATE partner_connectors SET name=?, contact=?, rate=? WHERE id=? AND partner_email=?");
    $stmt->bind_param("ssdis", $name, $contact, $rate, $id, $partner_email);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'deleteConnector') {
    $id = (int)$input['id'];
    $stmt = $conn->prepare("DELETE FROM partner_connectors WHERE id=? AND partner_email=?");
    $stmt->bind_param("is", $id, $partner_email);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// ==================== LEADS ====================
if ($action === 'getLeads') {
    $stmt = $conn->prepare("SELECT * FROM partner_leads WHERE partner_email = ? ORDER BY id DESC");
    $stmt->bind_param("s", $partner_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
    echo json_encode($leads);
    exit;
}

if ($action === 'addLead') {
    $client_name = $conn->real_escape_string($input['client_name']);
    $service = $conn->real_escape_string($input['service']);
    $phone = $conn->real_escape_string($input['phone'] ?? '');
    $connector_id = !empty($input['connector_id']) ? (int)$input['connector_id'] : null;
    $stmt = $conn->prepare("INSERT INTO partner_leads (partner_email, client_name, service, phone, connector_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $partner_email, $client_name, $service, $phone, $connector_id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'updateLeadStatus') {
    $lead_id = (int)$input['lead_id'];
    $new_status = $conn->real_escape_string($input['status']);
    $lead = $conn->query("SELECT * FROM partner_leads WHERE id=$lead_id AND partner_email='$partner_email'")->fetch_assoc();
    if (!$lead) die(json_encode(['success' => false, 'error' => 'Lead not found']));
    $old_status = $lead['status'];
    $stmt = $conn->prepare("UPDATE partner_leads SET status=? WHERE id=? AND partner_email=?");
    $stmt->bind_param("sis", $new_status, $lead_id, $partner_email);
    $success = $stmt->execute();
    if ($success && $new_status === 'converted' && $old_status !== 'converted') {
        $servicePrice = 12000;
        $commission = 0;
        if ($lead['connector_id']) {
            $connector = $conn->query("SELECT rate FROM partner_connectors WHERE id={$lead['connector_id']}")->fetch_assoc();
            if ($connector) $commission = $servicePrice * ($connector['rate'] / 100);
        } else {
            $commission = $servicePrice * 0.05;
        }
        $commission = round($commission);
        $conn->query("UPDATE partner_leads SET commission_amount=$commission WHERE id=$lead_id");
        $conn->query("INSERT INTO partner_commissions (partner_email, lead_id, connector_id, client_name, amount) VALUES ('$partner_email', $lead_id, {$lead['connector_id']}, '{$lead['client_name']}', $commission)");
    }
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'deleteLead') {
    $lead_id = (int)$input['lead_id'];
    $stmt = $conn->prepare("DELETE FROM partner_leads WHERE id=? AND partner_email=?");
    $stmt->bind_param("is", $lead_id, $partner_email);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// ==================== COMMISSIONS ====================
if ($action === 'getCommissions') {
    $stmt = $conn->prepare("SELECT * FROM partner_commissions WHERE partner_email = ? ORDER BY id DESC");
    $stmt->bind_param("s", $partner_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $commissions = [];
    while ($row = $result->fetch_assoc()) {
        $commissions[] = $row;
    }
    echo json_encode($commissions);
    exit;
}

// ==================== LEADERBOARD ====================
if ($action === 'getLeaderboard') {
    $result = $conn->query("SELECT partner_name, points, status FROM partner_leaderboard ORDER BY points DESC");
    $board = [];
    while ($row = $result->fetch_assoc()) {
        $board[] = $row;
    }
    echo json_encode($board);
    exit;
}

if ($action === 'refreshLeaderboardPoints') {
    $conn->query("UPDATE partner_leaderboard SET points = points + FLOOR(5 + RAND()*45) WHERE status='active'");
    echo json_encode(['success' => true]);
    exit;
}

// ==================== PROFILE UPDATE ====================
if ($action === 'updateProfile') {
    $name = $conn->real_escape_string($input['name']);
    $phone = $conn->real_escape_string($input['phone']);
    $company = $conn->real_escape_string($input['company']);
    $email = $conn->real_escape_string($input['email']);
    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, company=? WHERE email=? AND role='partner'");
    $stmt->bind_param("ssss", $name, $phone, $company, $email);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// ==================== PASSWORD UPDATE ====================
if ($action === 'updatePassword') {
    $email = $conn->real_escape_string($input['email']);
    $current = $input['current_password'];
    $new = password_hash($input['new_password'], PASSWORD_DEFAULT);
    // Verify current password
    $res = $conn->query("SELECT password FROM users WHERE email='$email' AND role='partner'");
    if ($res->num_rows === 0) die(json_encode(['success' => false, 'error' => 'User not found']));
    $row = $res->fetch_assoc();
    if (!password_verify($current, $row['password'])) {
        die(json_encode(['success' => false, 'error' => 'Current password is incorrect']));
    }
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=? AND role='partner'");
    $stmt->bind_param("ss", $new, $email);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// ==================== DEFAULT ====================
echo json_encode(['success' => false, 'error' => 'Invalid action']);
$conn->close();
?>