<?php
// api/partner/get_api_info.php
// API Information - Returns version info and available endpoints

// No authentication required - public endpoint

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$version = "2.0.0";
$release_date = "2025-03-15";

// Check database connection and get version
if ($conn) {
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'system_version'");
    if (mysqli_num_rows($table_check) > 0) {
        $version_query = mysqli_query($conn, "SELECT version, release_date FROM system_version ORDER BY id DESC LIMIT 1");
        $version_data = mysqli_fetch_assoc($version_query);
        if ($version_data) {
            $version = $version_data['version'];
            $release_date = $version_data['release_date'];
        }
    }
}

// List of actual endpoints that exist
$endpoints = [
    'auth' => [
        'login' => '/api/partner/login.php',
        'logout' => '/api/partner/logout.php',
        'forgot_password' => '/api/partner/forgot_password.php'
    ],
    'dashboard' => [
        'get_stats' => '/api/partner/get_dashboard_stats.php',
        'get_widgets' => '/api/partner/get_widgets.php',
        'get_analytics' => '/api/partner/get_analytics.php',
        'get_performance' => '/api/partner/get_performance_analytics.php'
    ],
    'leads' => [
        'get_leads' => '/api/partner/get_leads.php',
        'add_lead' => '/api/partner/add_lead.php',
        'update_lead' => '/api/partner/update_lead.php',
        'delete_lead' => '/api/partner/delete_lead.php',
        'get_lead_details' => '/api/partner/get_lead_details.php',
        'export_leads' => '/api/partner/export_leads.php',
        'add_followup' => '/api/partner/add_followup.php',
        'add_note' => '/api/partner/add_note.php'
    ],
    'customers' => [
        'get_customers' => '/api/partner/get_customers.php'
    ],
    'commission' => [
        'get_commission' => '/api/partner/get_commission.php',
        'get_commission_details' => '/api/partner/get_commission_details.php'
    ],
    'payouts' => [
        'get_payouts' => '/api/partner/get_payouts.php',
        'request_payout' => '/api/partner/request_payout.php',
        'get_payment_history' => '/api/partner/get_payment_history.php',
        'get_payout_summary' => '/api/partner/get_payout_summary.php'
    ],
    'profile' => [
        'get_profile' => '/api/partner/get_profile.php',
        'update_profile' => '/api/partner/update_profile.php',
        'change_password' => '/api/partner/change_password.php',
        'save_bank' => '/api/partner/save_bank.php'
    ],
    'tickets' => [
        'get_tickets' => '/api/partner/get_tickets.php',
        'create_ticket' => '/api/partner/create_ticket.php',
        'get_ticket_details' => '/api/partner/get_ticket_details.php'
    ],
    'documents' => [
        'get_documents' => '/api/partner/get_documents.php',
        'delete_document' => '/api/partner/delete_document.php'
    ],
    'referrals' => [
        'get_link' => '/api/partner/get_referral_links.php',
        'get_earnings' => '/api/partner/get_referrals.php'
    ],
    'notifications' => [
        'get' => '/api/partner/get_notifications.php',
        'get_settings' => '/api/partner/get_notification_settings.php'
    ],
    'reports' => [
        'generate' => '/api/partner/generate_report.php'
    ],
    'support' => [
        'get_faqs' => '/api/partner/get_faqs.php'
    ],
    'leaderboard' => [
        'get' => '/api/partner/get_leaderboard.php'
    ],
    'settings' => [
        'get' => '/api/partner/get_settings.php'
    ]
];

echo json_encode([
    'success' => true,
    'api_name' => 'CIBIL Repair Partner API',
    'api_version' => $version,
    'release_date' => $release_date,
    'base_url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'cibilrepair.com'),
    'endpoints' => $endpoints,
    'total_endpoints' => count($endpoints, COUNT_RECURSIVE) - count($endpoints),
    'last_updated' => date('Y-m-d H:i:s')
]);

if ($conn) {
    mysqli_close($conn);
}
?>