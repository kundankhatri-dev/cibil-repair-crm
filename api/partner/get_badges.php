<?php
// api/partner/get_badges.php
// Achievement badges system

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Get partner stats
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

$stats_query = "SELECT 
    COUNT(*) as total_leads,
    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as total_converted,
    SUM(CASE WHEN status = 'converted' AND MONTH(created_at) = MONTH(CURRENT_DATE()) THEN 1 ELSE 0 END) as monthly_converted,
    SUM(commission_amount) as total_commission
    FROM $leadsTable WHERE partner_id = ?";

$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);

$badges = [];

// Badge definitions
$badge_definitions = [
    [
        'id' => 'first_lead',
        'name' => 'First Step',
        'description' => 'Added your first lead',
        'icon' => 'fa-star',
        'color' => '#ffc107',
        'condition' => $stats['total_leads'] >= 1,
        'required' => 1
    ],
    [
        'id' => 'lead_master_10',
        'name' => 'Lead Master',
        'description' => 'Added 10 leads',
        'icon' => 'fa-trophy',
        'color' => '#c0c0c0',
        'condition' => $stats['total_leads'] >= 10,
        'required' => 10
    ],
    [
        'id' => 'lead_master_50',
        'name' => 'Lead Champion',
        'description' => 'Added 50 leads',
        'icon' => 'fa-trophy',
        'color' => '#cd7f32',
        'condition' => $stats['total_leads'] >= 50,
        'required' => 50
    ],
    [
        'id' => 'lead_master_100',
        'name' => 'Lead Legend',
        'description' => 'Added 100 leads',
        'icon' => 'fa-trophy',
        'color' => '#ffd700',
        'condition' => $stats['total_leads'] >= 100,
        'required' => 100
    ],
    [
        'id' => 'first_conversion',
        'name' => 'First Success',
        'description' => 'Converted your first lead',
        'icon' => 'fa-check-circle',
        'color' => '#28a745',
        'condition' => $stats['total_converted'] >= 1,
        'required' => 1
    ],
    [
        'id' => 'conversion_streak_5',
        'name' => 'On Fire!',
        'description' => 'Converted 5 leads in a month',
        'icon' => 'fa-fire',
        'color' => '#dc3545',
        'condition' => $stats['monthly_converted'] >= 5,
        'required' => 5
    ],
    [
        'id' => 'conversion_streak_10',
        'name' => 'Inferno',
        'description' => 'Converted 10 leads in a month',
        'icon' => 'fa-fire',
        'color' => '#dc3545',
        'condition' => $stats['monthly_converted'] >= 10,
        'required' => 10
    ],
    [
        'id' => 'earnings_10k',
        'name' => '₹10K Club',
        'description' => 'Earned ₹10,000 in commission',
        'icon' => 'fa-rupee-sign',
        'color' => '#1f8a72',
        'condition' => $stats['total_commission'] >= 10000,
        'required' => 10000
    ],
    [
        'id' => 'earnings_50k',
        'name' => '₹50K Club',
        'description' => 'Earned ₹50,000 in commission',
        'icon' => 'fa-rupee-sign',
        'color' => '#1f8a72',
        'condition' => $stats['total_commission'] >= 50000,
        'required' => 50000
    ],
    [
        'id' => 'earnings_1lakh',
        'name' => '₹1L Club',
        'description' => 'Earned ₹1,00,000 in commission',
        'icon' => 'fa-crown',
        'color' => '#ffd700',
        'condition' => $stats['total_commission'] >= 100000,
        'required' => 100000
    ]
];

foreach ($badge_definitions as $badge) {
    $earned = $badge['condition'];
    $progress = 0;
    
    if ($badge['id'] === 'first_lead') {
        $progress = min(100, ($stats['total_leads'] / $badge['required']) * 100);
    } elseif (strpos($badge['id'], 'lead_master') !== false) {
        $progress = min(100, ($stats['total_leads'] / $badge['required']) * 100);
    } elseif (strpos($badge['id'], 'conversion') !== false) {
        $progress = min(100, ($stats['monthly_converted'] / $badge['required']) * 100);
    } elseif (strpos($badge['id'], 'earnings') !== false) {
        $progress = min(100, ($stats['total_commission'] / $badge['required']) * 100);
    } else {
        $progress = $earned ? 100 : 0;
    }
    
    $badges[] = [
        'id' => $badge['id'],
        'name' => $badge['name'],
        'description' => $badge['description'],
        'icon' => $badge['icon'],
        'color' => $badge['color'],
        'earned' => $earned,
        'progress' => round($progress, 1),
        'required' => $badge['required']
    ];
}

$next_badge = null;
foreach ($badges as $badge) {
    if (!$badge['earned'] && $badge['progress'] > 0) {
        $next_badge = $badge;
        break;
    }
}

echo json_encode([
    'success' => true,
    'badges' => $badges,
    'earned_count' => count(array_filter($badges, fn($b) => $b['earned'])),
    'total_badges' => count($badges),
    'next_badge' => $next_badge,
    'partner_stats' => [
        'total_leads' => (int)$stats['total_leads'],
        'total_converted' => (int)$stats['total_converted'],
        'total_commission' => round((float)$stats['total_commission'], 2)
    ]
]);

mysqli_close($conn);
?>