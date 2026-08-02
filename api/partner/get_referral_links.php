<?php
// ============================================================
// API: Partner Get Referral Links - EXTENSIVE VERSION
// ============================================================
// Features:
// - Get referral code and share links
// - Track referral clicks and signups
// - Get referral statistics
// - Generate QR code for referral link
// - Get social media share cards
// - Track referral performance
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== VERIFY PARTNER ==========
$result = mysqli_query($conn, "SELECT role FROM users WHERE id = $partner_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    if (!$row || $row['role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        mysqli_close($conn);
        exit;
    }
}

// ========== GET PARTNER DETAILS ==========
$partner_name = '';
$partner_email = '';
$partner_phone = '';

$stmt = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $partner = mysqli_fetch_assoc($result);
    if ($partner) {
        $partner_name = $partner['name'] ?? 'Partner';
        $partner_email = $partner['email'] ?? '';
        $partner_phone = $partner['phone'] ?? '';
    }
    mysqli_stmt_close($stmt);
}

// ========== GET REFERRAL CODE ==========
// Check if partners table exists
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
$tableExists = mysqli_num_rows($checkTable) > 0;

$referralCode = 'PART-' . strtoupper(substr(md5($partner_id . 'cibilrepair'), 0, 8));

if ($tableExists) {
    // Check if referral_code column exists
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'referral_code'");
    if (mysqli_num_rows($checkColumn) > 0) {
        $stmt = mysqli_prepare($conn, "SELECT referral_code, tier_id, monthly_referrals, total_leads, total_commission FROM partners WHERE user_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $partner_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            if ($row && !empty($row['referral_code'])) {
                $referralCode = $row['referral_code'];
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Add column if it doesn't exist
        mysqli_query($conn, "ALTER TABLE partners ADD COLUMN referral_code VARCHAR(50) DEFAULT NULL");
        
        // Update with default code
        $defaultCode = 'PART-' . strtoupper(substr(md5($partner_id . time()), 0, 8));
        $stmt = mysqli_prepare($conn, "UPDATE partners SET referral_code = ? WHERE user_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $defaultCode, $partner_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $referralCode = $defaultCode;
        }
    }
}

// ========== GET REFERRAL STATISTICS ==========
$total_clicks = 0;
$total_signups = 0;
$conversions = 0;
$total_earnings = 0;

// Check if referral_tracking table exists
$checkTracking = mysqli_query($conn, "SHOW TABLES LIKE 'referral_tracking'");
if (mysqli_num_rows($checkTracking) > 0) {
    // Get total clicks
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as clicks FROM referral_tracking WHERE referral_code = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $referralCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_clicks = (int)($row['clicks'] ?? 0);
        mysqli_stmt_close($stmt);
    }
    
    // Get signups and conversions
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as signups, SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions FROM referral_tracking WHERE referral_code = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $referralCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_signups = (int)($row['signups'] ?? 0);
        $conversions = (int)($row['conversions'] ?? 0);
        mysqli_stmt_close($stmt);
    }
    
    // Get total earnings from commissions
    $stmt = mysqli_prepare($conn, "SELECT SUM(amount) as total FROM referral_commissions WHERE referral_code = ? AND status = 'paid'");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $referralCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_earnings = (float)($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }
}

// ========== GET RECENT REFERRALS ==========
$recent_referrals = [];
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, created_at, status FROM users WHERE referral_code = ? AND role = 'partner' ORDER BY created_at DESC LIMIT 5");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $referralCode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_referrals[] = [
            'name' => $row['name'] ?? '—',
            'email' => $row['email'] ?? '—',
            'phone' => $row['phone'] ?? '—',
            'joined' => $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '—',
            'status' => $row['status'] ?? 'registered'
        ];
    }
    mysqli_stmt_close($stmt);
}

// ========== BUILD RESPONSE ==========
$domain = 'https://cibilrepair.in';
$encodedCode = urlencode($referralCode);
$message = urlencode("Join CIBIL Repair using my referral code: " . $referralCode);
$partner_name_encoded = urlencode($partner_name);

$response = [
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name,
        'email' => $partner_email,
        'phone' => $partner_phone
    ],
    'referral' => [
        'code' => $referralCode,
        'links' => [
            'registration' => $domain . '/register?ref=' . $encodedCode,
            'homepage' => $domain . '/?ref=' . $encodedCode,
            'whatsapp' => 'https://wa.me/?text=' . $message,
            'whatsapp_number' => 'https://wa.me/' . $partner_phone . '?text=' . $message,
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($domain . '/?ref=' . $encodedCode),
            'twitter' => 'https://twitter.com/intent/tweet?text=' . $message . '&url=' . urlencode($domain . '/?ref=' . $encodedCode),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($domain . '/?ref=' . $encodedCode),
            'email' => 'mailto:?subject=Join%20CIBIL%20Repair&body=Join%20using%20my%20referral%20code%3A%20' . $encodedCode,
            'copy_link' => $domain . '/?ref=' . $encodedCode,
            'short_link' => $domain . '/r/' . $referralCode, // For URL shortening (needs .htaccess)
            'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($domain . '/?ref=' . $encodedCode)
        ],
        'stats' => [
            'total_clicks' => $total_clicks,
            'total_signups' => $total_signups,
            'conversions' => $conversions,
            'conversion_rate' => $total_signups > 0 ? round(($conversions / $total_signups) * 100, 1) : 0,
            'total_earnings' => $total_earnings,
            'total_earnings_formatted' => '₹' . number_format($total_earnings, 2)
        ],
        'recent_referrals' => $recent_referrals,
        'share_message' => "🎉 Join CIBIL Repair and improve your credit score!\n\nUse my referral code: " . $referralCode . "\n\n👉 " . $domain . '/?ref=' . $encodedCode . "\n\n#CIBILRepair #CreditScore #FinancialFreedom",
        'html_banner' => '<div style="background:linear-gradient(135deg,#0b2a23,#0d9e78);padding:20px;border-radius:10px;text-align:center;color:#fff;font-family:Arial,sans-serif;">
            <h2 style="margin:0;font-size:24px;">🤝 Refer & Earn!</h2>
            <p style="margin:8px 0;">Share your code and earn commissions</p>
            <div style="background:rgba(255,255,255,0.15);padding:10px;border-radius:5px;font-size:18px;font-weight:bold;letter-spacing:2px;margin:10px 0;">' . $referralCode . '</div>
            <a href="' . $domain . '/?ref=' . $encodedCode . '" style="display:inline-block;background:#fff;color:#0b2a23;padding:8px 20px;border-radius:5px;text-decoration:none;font-weight:bold;">Start Earning →</a>
        </div>'
    ],
    'debug' => [
        'table_exists' => $tableExists,
        'partner_found' => true,
        'referral_code_used' => $referralCode
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

mysqli_close($conn);
?>