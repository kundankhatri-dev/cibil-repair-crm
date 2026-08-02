<?php
// api/partner/gdpr_export.php
// Export all user data for GDPR compliance

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$partner_id = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'json';

// Collect all user data
$user_data = [];

// 1. User profile
$query = "SELECT id, name, email, phone, city, state, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['profile'] = mysqli_fetch_assoc($stmt);

// 2. Partner details
$query = "SELECT * FROM partners WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['partner_details'] = mysqli_fetch_assoc($stmt);

// 3. Leads
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) > 0) {
    $query = "SELECT * FROM $leadsTable WHERE partner_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data['leads'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// 4. Commissions
$query = "SELECT * FROM partner_commissions WHERE partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['commissions'] = mysqli_fetch_all($result, MYSQLI_ASSOC);

// 5. Payouts
$query = "SELECT * FROM partner_payouts WHERE partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['payouts'] = mysqli_fetch_all($result, MYSQLI_ASSOC);

// 6. Tickets
$query = "SELECT * FROM partner_tickets WHERE partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['tickets'] = mysqli_fetch_all($result, MYSQLI_ASSOC);

// 7. Activities
$query = "SELECT * FROM activities WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data['activities'] = mysqli_fetch_all($result, MYSQLI_ASSOC);

$user_data['export_date'] = date('Y-m-d H:i:s');
$user_data['data_controller'] = 'CIBIL Repair';
$user_data['data_protection_officer'] = 'dpo@cibilrepair.in';

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="personal_data_export.json"');
    echo json_encode($user_data, JSON_PRETTY_PRINT);
} elseif ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="personal_data_export.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Section', 'Data']);
    
    foreach ($user_data as $section => $data) {
        if (is_array($data)) {
            fputcsv($output, [$section, json_encode($data)]);
        } else {
            fputcsv($output, [$section, $data]);
        }
    }
    fclose($output);
}

mysqli_close($conn);
?>