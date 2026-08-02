<?php
// api/partner/update_lead.php
// Partner Update Lead API - Update lead status and calculate commission

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Determine which leads table to use
$leadsTable = 'leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
if (mysqli_num_rows($checkTable) > 0) {
    $leadsTable = 'partner_leads';
}
$checkTable2 = mysqli_query($conn, "SHOW TABLES LIKE 'leads'");
if (mysqli_num_rows($checkTable2) > 0 && $leadsTable == 'leads') {
    $leadsTable = 'leads';
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$new_status = trim($data['status'] ?? '');

if (!$lead_id || !$new_status) {
    echo json_encode(['success' => false, 'error' => 'Lead ID and status are required']);
    exit;
}

// Validate status
$valid_statuses = ['new', 'contacted', 'converted', 'lost'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value. Allowed: new, contacted, converted, lost']);
    exit;
}

// ========== GET LEAD INFO WITH VERIFICATION ==========
$stmt = mysqli_prepare($conn, "SELECT id, partner_id, customer_name, customer_phone, service_type, status, commission_amount, estimated_amount, source_type, source_id, source_name, source_commission_rate FROM $leadsTable WHERE id = ? AND partner_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $lead_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
    exit;
}

$old_status = $lead['status'];
$commission = (float)($lead['commission_amount'] ?? 0);

// ========== PREVENT INVALID STATUS CHANGES ==========
if (in_array($old_status, ['converted', 'lost']) && $old_status !== $new_status) {
    echo json_encode(['success' => false, 'error' => "Cannot change status from '$old_status' to '$new_status'. Converted/Lost leads are final."]);
    exit;
}

if ($old_status === $new_status) {
    echo json_encode([
        'success' => true,
        'message' => 'Lead status is already ' . $new_status,
        'commission' => $commission,
        'old_status' => $old_status,
        'new_status' => $new_status,
        'no_change' => true
    ]);
    exit;
}

// ========== GET PARTNER TIER ==========
function getPartnerTier($conn, $partner_id) {
    $tier_id = 1; // Default: Bronze
    $tier_name = 'Bronze';
    $commission_rate = 20;
    
    // Check if partners table exists
    $checkPartners = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
    if (mysqli_num_rows($checkPartners) > 0) {
        $stmt = mysqli_prepare($conn, "SELECT tier_id FROM partners WHERE user_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $partner_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            if ($row && isset($row['tier_id'])) {
                $tier_id = (int)$row['tier_id'];
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Define tiers (matches your PDF)
    $tiers = [
        1 => ['name' => 'Bronze',   'rate' => 20],
        2 => ['name' => 'Silver',   'rate' => 25],
        3 => ['name' => 'Gold',     'rate' => 30],
        4 => ['name' => 'Platinum', 'rate' => 35],
        5 => ['name' => 'Diamond',  'rate' => 40]
    ];
    
    if (isset($tiers[$tier_id])) {
        $tier_name = $tiers[$tier_id]['name'];
        $commission_rate = $tiers[$tier_id]['rate'];
    }
    
    return [
        'tier_id' => $tier_id,
        'tier_name' => $tier_name,
        'commission_rate' => $commission_rate
    ];
}

// ========== CALCULATE COMMISSION IF NEWLY CONVERTED ==========
$commission_calculated = false;
$tier_info = null;
$partner_commission = 0;
$source_commission = 0;
$price = 0;

if ($new_status === 'converted' && $old_status !== 'converted') {
    // Get partner's current tier
    $tier_info = getPartnerTier($conn, $partner_id);
    $commission_rate = $tier_info['commission_rate'];
    
    // Get lead source info
    $source_type = $lead['source_type'] ?? 'direct';
    $source_id = $lead['source_id'] ?? null;
    $source_name = $lead['source_name'] ?? '';
    $source_commission_rate = (float)($lead['source_commission_rate'] ?? 0);
    
    // Service prices for commission calculation
    $servicePrices = [
        'Written Off Clearance' => 15000,
        'Settled Clearance' => 12000,
        'Suit Filed Clearance' => 20000,
        'Credit Report Analysis' => 5000,
        'Profile Correction' => 3000,
        'Wrong Entry Clearance' => 8000
    ];
    
    $service_type = $lead['service_type'];
    $price = isset($servicePrices[$service_type]) ? $servicePrices[$service_type] : 10000;
    
    // Calculate partner commission
    $partner_commission = ($price * $commission_rate) / 100;
    $commission = $partner_commission;
    
    // Calculate source commission (if applicable)
    $source_commission = 0;
    if ($source_type !== 'direct' && !empty($source_id) && $source_commission_rate > 0) {
        $source_commission = ($price * $source_commission_rate) / 100;
        
        // Update source's earnings
        if ($source_type === 'referral') {
            $check_ref = mysqli_query($conn, "SHOW TABLES LIKE 'referrals'");
            if ($check_ref && mysqli_num_rows($check_ref) > 0) {
                $update_source = mysqli_prepare($conn, "UPDATE referrals 
                    SET earnings = earnings + ?, 
                        leads_referred = leads_referred + 1 
                    WHERE id = ?");
                if ($update_source) {
                    mysqli_stmt_bind_param($update_source, "di", $source_commission, $source_id);
                    mysqli_stmt_execute($update_source);
                    mysqli_stmt_close($update_source);
                }
            }
        } elseif ($source_type === 'connector') {
            $check_conn = mysqli_query($conn, "SHOW TABLES LIKE 'connectors'");
            if ($check_conn && mysqli_num_rows($check_conn) > 0) {
                $update_source = mysqli_prepare($conn, "UPDATE connectors 
                    SET commission_earned = commission_earned + ?, 
                        leads_referred = leads_referred + 1 
                    WHERE id = ?");
                if ($update_source) {
                    mysqli_stmt_bind_param($update_source, "di", $source_commission, $source_id);
                    mysqli_stmt_execute($update_source);
                    mysqli_stmt_close($update_source);
                }
            }
        }
    }
    
    $commission_calculated = true;
    
    // Determine which commission table to use
    $commissionTable = 'partner_commissions';
    $checkCommTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_commissions'");
    if (mysqli_num_rows($checkCommTable) == 0) {
        $commissionTable = 'partner_commission';
        $checkCommTable2 = mysqli_query($conn, "SHOW TABLES LIKE 'partner_commission'");
        if (mysqli_num_rows($checkCommTable2) == 0) {
            // Create commission table if it doesn't exist
            $createTable = "CREATE TABLE IF NOT EXISTS partner_commissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                partner_id INT NOT NULL,
                lead_id INT NOT NULL,
                customer_name VARCHAR(255),
                service_type VARCHAR(100),
                service_amount DECIMAL(10,2),
                tier_id INT DEFAULT 1,
                tier_name VARCHAR(50) DEFAULT 'Bronze',
                commission_rate DECIMAL(5,2) DEFAULT 20.00,
                commission_amount DECIMAL(10,2),
                source_type VARCHAR(20) DEFAULT 'direct',
                source_id INT NULL,
                source_name VARCHAR(255) DEFAULT NULL,
                source_commission_rate DECIMAL(5,2) DEFAULT 0,
                source_commission_amount DECIMAL(10,2) DEFAULT 0,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            mysqli_query($conn, $createTable);
            $commissionTable = 'partner_commissions';
        }
    }
    
    // Insert into commission table with tier and source info
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO $commissionTable 
        (partner_id, lead_id, customer_name, service_type, service_amount, 
         tier_id, tier_name, commission_rate, commission_amount,
         source_type, source_id, source_name, source_commission_rate, source_commission_amount, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    if ($insert_stmt) {
        mysqli_stmt_bind_param($insert_stmt, "iisssissdsssdd", 
            $partner_id, 
            $lead_id, 
            $lead['customer_name'], 
            $service_type, 
            $price, 
            $tier_info['tier_id'], 
            $tier_info['tier_name'], 
            $commission_rate, 
            $partner_commission,
            $source_type,
            $source_id,
            $source_name,
            $source_commission_rate,
            $source_commission
        );
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    
    // Update partner's total commission in partners table
    $checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
    if (mysqli_num_rows($checkPartnersTable) > 0) {
        // Check which column exists
        $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'total_commission'");
        if (mysqli_num_rows($colCheck) > 0) {
            // Try with user_id
            $update_partner = mysqli_prepare($conn, "UPDATE partners SET total_commission = total_commission + ?, pending_payout = pending_payout + ? WHERE user_id = ?");
            if ($update_partner) {
                mysqli_stmt_bind_param($update_partner, "ddi", $partner_commission, $partner_commission, $partner_id);
                mysqli_stmt_execute($update_partner);
                mysqli_stmt_close($update_partner);
            } else {
                // Fallback with id
                $update_partner2 = mysqli_prepare($conn, "UPDATE partners SET total_commission = total_commission + ?, pending_payout = pending_payout + ? WHERE id = ?");
                if ($update_partner2) {
                    mysqli_stmt_bind_param($update_partner2, "ddi", $partner_commission, $partner_commission, $partner_id);
                    mysqli_stmt_execute($update_partner2);
                    mysqli_stmt_close($update_partner2);
                }
            }
        }
    }
}

// ========== UPDATE LEAD STATUS ==========
$update_stmt = mysqli_prepare($conn, "UPDATE $leadsTable SET status = ?, commission_amount = ?, estimated_amount = ?, updated_at = NOW() WHERE id = ? AND partner_id = ?");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

$price = isset($price) ? $price : 0;
$commission = isset($commission) ? $commission : 0;
mysqli_stmt_bind_param($update_stmt, "sddii", $new_status, $commission, $price, $lead_id, $partner_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'update_lead', ?, NOW())");
        if ($log_stmt) {
            $description = "Updated lead #$lead_id status from '$old_status' to '$new_status'";
            if ($commission_calculated) {
                $description .= " | Commission earned: ₹" . number_format($partner_commission, 2) . " at " . $commission_rate . "% (" . $tier_info['tier_name'] . " tier)";
                if ($source_commission > 0) {
                    $description .= " | Source commission: ₹" . number_format($source_commission, 2) . " (" . $source_type . ": " . $source_name . ")";
                }
            }
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    // Return success with commission info
    $response = [
        'success' => true,
        'message' => 'Lead status updated successfully',
        'commission' => $commission,
        'old_status' => $old_status,
        'new_status' => $new_status
    ];
    
    if ($commission_calculated) {
        $response['commission_earned'] = $partner_commission;
        $response['commission_rate'] = $commission_rate . '%';
        $response['tier'] = $tier_info['tier_name'];
        $response['source'] = [
            'type' => $source_type ?? 'direct',
            'name' => $source_name ?? '',
            'commission' => $source_commission,
            'rate' => $source_commission_rate ?? 0
        ];
        $response['message'] = 'Lead converted! Commission earned: ₹' . number_format($partner_commission, 2) . ' at ' . $commission_rate . '% (' . $tier_info['tier_name'] . ' tier)';
        
        if ($source_commission > 0) {
            $response['message'] .= ' | Source commission: ₹' . number_format($source_commission, 2);
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

// Clean up
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($update_stmt)) mysqli_stmt_close($update_stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>