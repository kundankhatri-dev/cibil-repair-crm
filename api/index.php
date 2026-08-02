<?php
// ============================================================
// CIBIL REPAIR CRM - API Router
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get request path
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '?') !== false) {
    $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
}
$path = str_replace('/api/', '', $request_uri);
$path = trim($path, '/');

// Root endpoint
if (empty($path) || $path === 'index.php') {
    echo json_encode([
        'status' => 'API Router Active',
        'version' => '1.0.0',
        'server' => $_SERVER['SERVER_NAME'],
        'timestamp' => date('Y-m-d H:i:s'),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
    exit();
}

// ============================================================
// API MAPPING - COMPLETE
// ============================================================

$api_map = [
    // ── ADMIN ROUTES ──
    'admin/test' => 'test.php',
    'admin/get_applications' => 'get_partner_applications.php',
    'admin/get_reviews' => 'fetch_reviews.php',
    'admin/get_services' => 'fetch_services.php',
    'admin/get_users' => 'get_all_data.php',
    'admin/get_customers' => 'get_all_data.php',
    'admin/get_leads' => 'get_all_data.php',
    'admin/get_followups' => 'get_followups.php',
    'admin/get_commission' => 'get_commission.php',
    'admin/get_payouts' => 'get_payouts.php',
    
    // ── ADD THIS LINE ──
    'admin/get_partners' => 'get_all_data.php',  // ← ADD THIS
    
    // ── PARTNERS (Admin) ──
    'admin/get_partners' => 'get_all_data.php',
    'admin/get_partner' => 'get_partner.php',
    'admin/add_partner' => 'add_partner.php',
    'admin/update_partner' => 'update_partner.php',
    'admin/delete_partner' => 'delete_partner.php',
    
    // ── EMAIL ──
    'email' => 'email.php',
    'email/send' => 'email.php',
    'email/queue' => 'email.php',
    'email/history' => 'email/history.php',
    'email/templates' => 'email/templates.php',
    
    // ── CORE DATA ──
    'get_all_data' => 'get_all_data.php',
    'get_partner_applications' => 'get_partner_applications.php',
    'fetch_reviews' => 'fetch_reviews.php',
    'fetch_services' => 'fetch_services.php',
    'get_posters' => 'get_posters.php',
    'get_transactions' => 'get_transactions.php',
    'get_settings' => 'get_settings.php',
    'get_stats' => 'get_stats.php',
    
    // ── AUTHENTICATION ──
    'login' => 'login.php',
    'logout' => 'logout.php',
    'check_session' => 'check_session.php',
    'reset_password' => 'reset_password.php',
    
    // ── CUSTOMERS ──
    'get_customers' => 'get_customers.php',
    'get_customer' => 'get_customer.php',
    'save_customer' => 'save_customer.php',
    'add_customer' => 'add_customer.php',
    'delete_customer' => 'delete_customer.php',
    'export_customers' => 'export_customers.php',
    
    // ── PARTNERS ──
    'get_partners' => 'get_partners.php',
    'get_partner' => 'get_partner.php',
    'add_partner' => 'add_partner.php',
    'save_partner' => 'save_partner.php',
    'update_partner' => 'update_partner.php',
    'delete_partner' => 'delete_partner.php',
    'export_partners' => 'export_partners.php',
    'get_partner_performance' => 'get_partner_performance.php',
    'get_partner_details' => 'get_partner_details.php',
    'update_partner_controls' => 'update_partner_controls.php',
    
    // ── BANKS ──
    'get_banks' => 'get_banks.php',
    'save_bank' => 'save_bank.php',
    'add_bank' => 'add_bank.php',
    'update_bank' => 'update_bank.php',
    'delete_bank' => 'delete_bank.php',
    'export_banks' => 'export_banks.php',
    
    // ── LEADS ──
    'get_leads' => 'get_leads.php',
    'save_lead' => 'save_lead.php',
    'add_lead' => 'add_lead.php',
    'delete_lead' => 'delete_lead.php',
    'convert_lead' => 'convert_lead.php',
    'export_leads' => 'export_leads.php',
    
    // ── SALES ──
    'get_sales' => 'get_sales.php',
    'get_sale' => 'get_sale.php',
    'save_sale' => 'save_sale.php',
    'add_sale' => 'add_sale.php',
    'delete_sale' => 'delete_sale.php',
    'get_sales_report' => 'get_sales_report.php',
    'export_sales' => 'export_sales.php',
    
    // ── QUOTATIONS ──
    'get_quotations' => 'get_quotations.php',
    'get_quotation' => 'get_quotation.php',
    'save_quotation' => 'save_quotation.php',
    'add_quotation' => 'add_quotation.php',
    'update_quotation' => 'update_quotation.php',
    'convert_quotation' => 'convert_quotation.php',
    'delete_quotation' => 'delete_quotation.php',
    'export_quotations' => 'export_quotations.php',
    
    // ── EXPENSES ──
    'get_expenses' => 'get_expenses.php',
    'save_expense' => 'save_expense.php',
    'add_expense' => 'add_expense.php',
    'delete_expense' => 'delete_expense.php',
    'get_expense_report' => 'get_expense_report.php',
    
    // ── REQUESTS ──
    'get_requests' => 'get_requests.php',
    'get_request' => 'get_request.php',
    'save_request' => 'save_request.php',
    'update_request' => 'update_request.php',
    'delete_request' => 'delete_request.php',
    
    // ── USERS ──
    'get_users' => 'get_users.php',
    'get_user' => 'get_user.php',
    'update_user' => 'update_user.php',
    'delete_user' => 'delete_user.php',
    
    // ── WALLET & TRANSACTIONS ──
    'wallet' => 'wallet.php',
    'get_transactions' => 'get_transactions.php',
    'get_wallet_balance' => 'get_wallet_balance.php',
    'add_transaction' => 'add_transaction.php',
    'export_transactions' => 'export_transactions.php',
    
    // ── PAYMENTS ──
    'get_payments' => 'get_payments.php',
    'get_payment' => 'get_payment.php',
    'add_payment' => 'add_payment.php',
    'get_payment_history' => 'get_payment_history.php',
    
    // ── REGISTRATION CODES ──
    'create_registration_code' => 'create_registration_code.php',
    'get_registration_codes' => 'get_registration_codes.php',
    'get_registration_code' => 'get_registration_code.php',
    'delete_registration_code' => 'delete_registration_code.php',
    
    // ── SERVICES ──
    'get_services' => 'get_services.php',
    'get_service' => 'get_service.php',
    'add_service' => 'add_service.php',
    'update_service' => 'update_service.php',
    'delete_service' => 'delete_service.php',
    'export_services' => 'export_services.php',
    
    // ── REVIEWS ──
    'get_reviews' => 'get_reviews.php',
    'add_review' => 'add_review.php',
    'save_review' => 'save_review.php',
    'delete_review' => 'delete_review.php',
    
    // ── POSTERS ──
    'get_posters' => 'get_posters.php',
    'upload_poster' => 'upload_poster.php',
    'delete_poster' => 'delete_poster.php',
    'download_poster' => 'download_poster.php',
    
    // ── ACTIVITY ──
    'log_activity' => 'log_activity.php',
    'get_activity_logs' => 'get_activity_logs.php',
    'get_recent_activity' => 'get_recent_activity.php',
    
    // ── BACKUP ──
    'backup_database' => 'backup_database.php',
    'get_backups' => 'get_backups.php',
    
    // ── SETTINGS ──
    'save_settings' => 'save_settings.php',
    'get_system_settings' => 'get_system_settings.php',
    
    // ── ANALYTICS ──
    'save_analysis' => 'save_analysis.php',
    'get_analyses' => 'get_analyses.php',
    'delete_analysis' => 'delete_analysis.php',
    
    // ── REPORTS ──
    'revenue_report' => 'revenue_report.php',
    'quarterly_report' => 'quarterly_report.php',
    
    // Follow-ups
    'get_followups' => 'get_followups.php',
    'add_followup' => 'add_followup.php',
    'update_followup' => 'update_followup.php',
    'delete_followup' => 'delete_followup.php',

    // Commission & Payouts
    'get_commission' => 'get_commission.php',
    'get_payouts' => 'get_payouts.php',
];

// ============================================================
// ROUTE THE REQUEST
// ============================================================

if (isset($api_map[$path])) {
    $file = __DIR__ . '/' . $api_map[$path];
    
    if (file_exists($file)) {
        $_SERVER['PHP_SELF'] = '/api/index.php';
        require_once $file;
        exit();
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'API file not found',
            'requested_endpoint' => $path,
            'file' => $file
        ]);
        exit();
    }
}

// Endpoint not found
http_response_code(404);
echo json_encode([
    'success' => false,
    'error' => 'API endpoint not found',
    'requested_endpoint' => '/' . $path
]);
?>