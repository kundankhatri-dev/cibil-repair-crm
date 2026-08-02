<?php
// api/partner/enrich_leads.php
// Automatically enrich lead data from external APIs

session_start();
require_once '../config.php';

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get leads missing email or other data
$query = "SELECT id, customer_name, customer_phone FROM $leadsTable 
          WHERE (customer_email IS NULL OR customer_email = '') 
          AND status = 'new'
          LIMIT 50";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

$enriched = 0;
foreach ($leads as $lead) {
    // Simulate API call to enrichment service (TrueCaller, Clearbit, etc.)
    // In production, integrate with real APIs
    
    // Mock enrichment data
    $enriched_data = [
        'email' => strtolower(str_replace(' ', '.', $lead['customer_name'])) . '@example.com',
        'city' => 'Mumbai',
        'pin_code' => '400001'
    ];
    
    // Update lead with enriched data
    $update = mysqli_prepare($conn, "UPDATE $leadsTable SET customer_email = ?, city = ?, pincode = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "sssi", $enriched_data['email'], $enriched_data['city'], $enriched_data['pin_code'], $lead['id']);
    mysqli_stmt_execute($update);
    $enriched++;
    
    // Rate limiting
    usleep(100000);
}

echo json_encode([
    'success' => true,
    'leads_enriched' => $enriched,
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>