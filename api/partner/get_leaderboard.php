<?php
// api/partner/get_leaderboard.php
// Partner Leaderboard API - Get top performing partners

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
// Get current partner ID if logged in (for showing their rank)
$current_partner_id = $_SESSION['user_id'] ?? 0;

// Verify if user is a partner
$is_partner = false;
$partner_name = '';
if ($current_partner_id) {
    $role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
    if ($role_check) {
        mysqli_stmt_bind_param($role_check, "i", $current_partner_id);
        mysqli_stmt_execute($role_check);
        $result_role = mysqli_stmt_get_result($role_check);
        $role_data = mysqli_fetch_assoc($result_role);
        $is_partner = ($role_data && $role_data['role'] === 'partner');
        $partner_name = $role_data['name'] ?? '';
        mysqli_stmt_close($role_check);
    }
}

// ========== GET FILTER PARAMETERS ==========
$period = isset($_GET['period']) ? $_GET['period'] : 'all'; // all, month, year
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate limit
if ($limit < 1 || $limit > 50) {
    $limit = 10;
}

// ========== CHECK COLUMNS IN PARTNERS TABLE ==========
$partnersTable = 'partners';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$partnersTable'");
if (mysqli_num_rows($checkTable) == 0) {
    echo json_encode(['success' => false, 'error' => 'Partners table not found']);
    exit;
}

// Get existing columns to avoid SQL errors
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $partnersTable");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $columns[] = $col['Field'];
    }
}

// Determine which columns exist
$has_total_converted = in_array('total_converted', $columns);
$has_total_leads = in_array('total_leads', $columns);
$has_total_commission = in_array('total_commission', $columns);
$has_pending_payout = in_array('pending_payout', $columns);
$has_commission_rate = in_array('commission_rate', $columns);
$has_created_at = in_array('created_at', $columns);

// ========== GET LEADERBOARD DATA ==========
// Build select fields based on existing columns
$select_fields = ["p.id as partner_id", "u.name as name"];

if ($has_total_leads) {
    $select_fields[] = "COALESCE(p.total_leads, 0) as total_leads";
} else {
    $select_fields[] = "0 as total_leads";
}

if ($has_total_converted) {
    $select_fields[] = "COALESCE(p.total_converted, 0) as total_converted";
} else {
    // Calculate from leads table if exists
    $leads_check = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
    if (mysqli_num_rows($leads_check)) {
        $select_fields[] = "(SELECT COUNT(*) FROM partner_leads WHERE partner_id = p.user_id AND status = 'converted') as total_converted";
    } else {
        $select_fields[] = "0 as total_converted";
    }
}

if ($has_total_commission) {
    $select_fields[] = "COALESCE(p.total_commission, 0) as total_commission";
} else {
    $select_fields[] = "0 as total_commission";
}

if ($has_pending_payout) {
    $select_fields[] = "COALESCE(p.pending_payout, 0) as pending_payout";
} else {
    $select_fields[] = "0 as pending_payout";
}

if ($has_commission_rate) {
    $select_fields[] = "COALESCE(p.commission_rate, 10) as commission_rate";
} else {
    $select_fields[] = "10 as commission_rate";
}

$select_clause = implode(", ", $select_fields);

// Build query with time filter
$query = "SELECT $select_clause 
          FROM $partnersTable p
          INNER JOIN users u ON p.user_id = u.id
          WHERE u.status = 'active' AND u.role = 'partner'";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND u.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// Add period filter (if created_at column exists)
if ($period != 'all' && $has_created_at) {
    if ($period == 'month') {
        $query .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } elseif ($period == 'year') {
        $query .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
}

$query .= " ORDER BY total_commission DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

// Execute query
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leaderboard = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== CALCULATE TOP 3 TOTALS FOR METRICS ==========
$top1_commission = 0;
$top2_commission = 0;
$top3_commission = 0;

foreach ($leaderboard as $index => &$partner) {
    $partner['rank'] = $index + 1;
    $partner['rank_icon'] = getRankIcon($index + 1);
    $partner['total_commission'] = (float)$partner['total_commission'];
    $partner['pending_payout'] = (float)$partner['pending_payout'];
    $partner['commission_rate'] = (float)$partner['commission_rate'];
    
    // Calculate conversion rate if total_leads > 0
    $partner['total_leads'] = (int)$partner['total_leads'];
    $partner['total_converted'] = (int)$partner['total_converted'];
    if ($partner['total_leads'] > 0) {
        $partner['conversion_rate'] = round(($partner['total_converted'] / $partner['total_leads']) * 100, 1);
    } else {
        $partner['conversion_rate'] = 0;
    }
    
    // Track top 3 commissions
    if ($index == 0) $top1_commission = $partner['total_commission'];
    if ($index == 1) $top2_commission = $partner['total_commission'];
    if ($index == 2) $top3_commission = $partner['total_commission'];
}

// ========== GET CURRENT PARTNER'S RANK AND STATS ==========
$current_rank = null;
$my_stats = null;
$my_rank_position = null;

if ($is_partner && $current_partner_id) {
    // Get partner's own stats first
    $my_stats_query = "SELECT 
                         u.name,
                         COALESCE(p.total_leads, 0) as total_leads,
                         COALESCE(p.total_converted, 0) as total_converted,
                         COALESCE(p.total_commission, 0) as total_commission,
                         COALESCE(p.commission_rate, 10) as commission_rate,
                         COALESCE(p.pending_payout, 0) as pending_payout
                       FROM $partnersTable p
                       INNER JOIN users u ON p.user_id = u.id
                       WHERE p.user_id = ?";
    
    $stats_stmt = mysqli_prepare($conn, $my_stats_query);
    if ($stats_stmt) {
        mysqli_stmt_bind_param($stats_stmt, "i", $current_partner_id);
        mysqli_stmt_execute($stats_stmt);
        $stats_result = mysqli_stmt_get_result($stats_stmt);
        $my_stats = mysqli_fetch_assoc($stats_result);
        mysqli_stmt_close($stats_stmt);
        
        if ($my_stats) {
            $my_stats['total_commission'] = (float)$my_stats['total_commission'];
            $my_stats['pending_payout'] = (float)$my_stats['pending_payout'];
            $my_stats['total_leads'] = (int)$my_stats['total_leads'];
            $my_stats['total_converted'] = (int)$my_stats['total_converted'];
            
            // Calculate conversion rate
            if ($my_stats['total_leads'] > 0) {
                $my_stats['conversion_rate'] = round(($my_stats['total_converted'] / $my_stats['total_leads']) * 100, 1);
            } else {
                $my_stats['conversion_rate'] = 0;
            }
        }
    }
    
    // Get partner's rank using a more accurate method
    $rank_query = "SELECT COUNT(*) + 1 as rank
                   FROM $partnersTable p
                   INNER JOIN users u ON p.user_id = u.id
                   WHERE u.status = 'active' 
                     AND u.role = 'partner'
                     AND COALESCE(p.total_commission, 0) > (SELECT COALESCE(total_commission, 0) FROM $partnersTable WHERE user_id = ?)";
    
    $rank_stmt = mysqli_prepare($conn, $rank_query);
    if ($rank_stmt) {
        mysqli_stmt_bind_param($rank_stmt, "i", $current_partner_id);
        mysqli_stmt_execute($rank_stmt);
        $rank_result = mysqli_stmt_get_result($rank_stmt);
        $rank_data = mysqli_fetch_assoc($rank_result);
        $current_rank = $rank_data['rank'] ?? count($leaderboard) + 1;
        mysqli_stmt_close($rank_stmt);
    }
    
    // Find if partner already in leaderboard and their position
    foreach ($leaderboard as $index => $partner) {
        if ($partner['partner_id'] == $current_partner_id) {
            $my_rank_position = $index + 1;
            break;
        }
    }
}

// ========== GET TOTAL PARTNER COUNT ==========
$totalPartnersQuery = "SELECT COUNT(*) as total FROM $partnersTable p 
                       INNER JOIN users u ON p.user_id = u.id 
                       WHERE u.status = 'active' AND u.role = 'partner'";
$totalResult = mysqli_query($conn, $totalPartnersQuery);
$totalPartners = mysqli_fetch_assoc($totalResult)['total'] ?? 0;

// ========== HELPER FUNCTIONS ==========
function getRankIcon($rank) {
    switch($rank) {
        case 1: return '🥇';
        case 2: return '🥈';
        case 3: return '🥉';
        default: return '#';
    }
}

// ========== RETURN JSON RESPONSE ==========
$response = [
    'success' => true,
    'data' => $leaderboard,
    'total' => count($leaderboard),
    'total_partners' => (int)$totalPartners,
    'period' => $period,
    'limit' => $limit,
    'summary' => [
        'top1_commission' => $top1_commission,
        'top2_commission' => $top2_commission,
        'top3_commission' => $top3_commission,
        'avg_commission' => count($leaderboard) > 0 ? round(array_sum(array_column($leaderboard, 'total_commission')) / count($leaderboard), 2) : 0
    ]
];

// Add current partner info if logged in as partner
if ($is_partner) {
    $response['current_partner'] = [
        'id' => $current_partner_id,
        'name' => $partner_name,
        'rank' => $current_rank,
        'in_top_list' => $my_rank_position !== null,
        'position' => $my_rank_position,
        'stats' => $my_stats
    ];
}

// Add search info if searched
if (!empty($search)) {
    $response['search'] = $search;
}

echo json_encode($response);

mysqli_close($conn);
?>