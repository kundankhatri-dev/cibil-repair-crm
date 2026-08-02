<?php
// ============================================================
// admin/generate_partner_credentials.php
// ============================================================
// Approve/Reject partner applications with secure registration
// ============================================================

session_start();

// ── Constants ────────────────────────────────────────────────────────
define('REGISTRATION_CODE_EXPIRY_HOURS', 72);
define('TEMP_PASSWORD_LENGTH', 10);
define('FROM_EMAIL', 'no-reply@cibilrepair.in');
define('FROM_NAME', 'CIBIL Repair');
define('REPLY_TO_EMAIL', 'contact@cibilrepair.in');

// ── Auth: Admin Only ─────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Admin access required']);
    exit;
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../api/config.php';

// ── CSRF Check ──────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$csrfToken = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
    exit;
}

// ── Input Validation ─────────────────────────────────────────────────
$applicationId = (int)($input['application_id'] ?? 0);
$action = $input['action'] ?? 'approve';
$adminNotes = trim($input['notes'] ?? '');
$rejectReason = trim($input['reject_reason'] ?? '');
$sendWhatsApp = isset($input['send_whatsapp']) ? filter_var($input['send_whatsapp'], FILTER_VALIDATE_BOOLEAN) : true;

if (!$applicationId) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Application ID is required']);
    exit;
}

// ── Load Application ─────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT * FROM partner_applications WHERE id = ? AND status = 'pending'");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Partner approval DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
}

if (!$app) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Application not found or already processed']);
    exit;
}

// ── Sanitize Input ──────────────────────────────────────────────────
$adminNotes = htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8');
$rejectReason = htmlspecialchars($rejectReason, ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars($app['name'] ?? 'Partner', ENT_QUOTES, 'UTF-8');
$appEmail = htmlspecialchars($app['email'] ?? '', ENT_QUOTES, 'UTF-8');

// ── Handle REJECTION ─────────────────────────────────────────────────
if ($action === 'reject') {
    try {
        $pdo->prepare("UPDATE partner_applications SET 
            status = 'rejected', 
            rejection_reason = ?, 
            notes = CONCAT(notes, ?, ' [Rejected by Admin #', ?, ']'), 
            approved_by = ?, 
            updated_at = NOW() 
            WHERE id = ?")
            ->execute([
                $rejectReason,
                $adminNotes ? "\n" . $adminNotes : '',
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $applicationId
            ]);

        // Send rejection email
        $emailBody = buildRejectionEmail($app, $rejectReason, $adminNotes);
        $emailSent = sendEmail($appEmail, "Update on Your Partner Application — CIBIL Repair", $emailBody);

        // Log activity
        logActivity($pdo, $_SESSION['user_id'], 'partner_rejected', 
            "Rejected partner application #{$applicationId} for {$appName} ({$appEmail})");

        echo json_encode([
            'success' => true,
            'message' => 'Application rejected and applicant notified.',
            'email_sent' => $emailSent
        ]);
        exit;
    } catch (PDOException $e) {
        error_log("Partner rejection error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to reject application']);
        exit;
    }
}

// ── Handle APPROVAL ──────────────────────────────────────────────────
try {
    // Check if user already exists
    $existingUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $existingUser->execute([$appEmail]);
    if ($existingUser->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'A user with this email already exists. Please check the users table.'
        ]);
        exit;
    }

    // Generate secure registration code
    $regCode = bin2hex(random_bytes(32)); // 64-char hex
    $tempPassword = generateSecurePassword();
    $expiresAt = date('Y-m-d H:i:s', strtotime("+" . REGISTRATION_CODE_EXPIRY_HOURS . " hours"));

    // Ensure registration_codes table exists
    ensureRegistrationCodesTable($pdo);

    // Hash temporary password
    $hashedTemp = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Insert registration code
    $pdo->prepare("
        INSERT INTO registration_codes
            (code, created_for_role, created_by, assigned_to_email, assigned_name, 
             application_id, temp_password, temp_password_plain, expires_at, created_at)
        VALUES 
            (?, 'partner', ?, ?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $regCode,
        $_SESSION['user_id'],
        $appEmail,
        $appName,
        $applicationId,
        $hashedTemp,
        $tempPassword,
        $expiresAt
    ]);

    // Update application status
    $pdo->prepare("UPDATE partner_applications SET 
        status = 'approved', 
        approved_at = NOW(), 
        approved_by = ?, 
        notes = CONCAT(notes, ?, ' [Approved by Admin #', ?, ']'),
        updated_at = NOW()
        WHERE id = ?")
        ->execute([
            $_SESSION['user_id'],
            $adminNotes ? "\n" . $adminNotes : '',
            $_SESSION['user_id'],
            $applicationId
        ]);

    // ── Build Registration URL ──────────────────────────────────────
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
               . '://' . ($_SERVER['HTTP_HOST'] ?? 'cibilrepair.in');
    $registerUrl = $baseUrl . "/register.php?code=" . urlencode($regCode) . "&email=" . urlencode($appEmail);

    // ── Send Approval Email ────────────────────────────────────────
    $emailBody = buildApprovalEmail($app, $tempPassword, $registerUrl, $appEmail);
    $emailSent = sendEmail($appEmail, "🎉 Congratulations! You're Approved — CIBIL Repair Partner Login", $emailBody);

    // ── Send WhatsApp Notification ──────────────────────────────────
    $whatsappSent = false;
    if ($sendWhatsApp && !empty($app['phone'])) {
        $whatsappSent = sendWhatsAppNotification($pdo, $app, $registerUrl, $tempPassword);
    }

    // ── Log Activity ────────────────────────────────────────────────
    logActivity($pdo, $_SESSION['user_id'], 'partner_approved',
        "Approved partner application #{$applicationId} for {$appName} ({$appEmail})");

    // ── Response ────────────────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'message' => "Partner approved. Login credentials sent to {$appEmail}.",
        'email_sent' => $emailSent,
        'whatsapp_sent' => $whatsappSent,
        'register_url' => $registerUrl,
        'expires_at' => $expiresAt,
        'temp_password' => $tempPassword, // Only for admin reference
        'ref_number' => $app['ref_number'] ?? 'N/A',
        'application_id' => $applicationId
    ]);

} catch (Exception $e) {
    error_log("Partner approval error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing the approval: ' . $e->getMessage()
    ]);
}
exit;


// ════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════════════

/**
 * Generate a secure temporary password
 */
function generateSecurePassword(): string {
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $special = '@#$!';
    
    // Guarantee at least one of each character class
    $pwd = '';
    $pwd .= $upper[random_int(0, strlen($upper) - 1)];
    $pwd .= $lower[random_int(0, strlen($lower) - 1)];
    $pwd .= $digits[random_int(0, strlen($digits) - 1)];
    $pwd .= $special[random_int(0, strlen($special) - 1)];
    
    $all = $upper . $lower . $digits . $special;
    for ($i = 0; $i < (TEMP_PASSWORD_LENGTH - 4); $i++) {
        $pwd .= $all[random_int(0, strlen($all) - 1)];
    }
    
    return str_shuffle($pwd);
}

/**
 * Ensure registration_codes table exists
 */
function ensureRegistrationCodesTable(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS registration_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(128) NOT NULL UNIQUE,
            created_for_role ENUM('partner','client') DEFAULT 'partner',
            created_by INT,
            assigned_to_email VARCHAR(200),
            assigned_name VARCHAR(200),
            application_id INT,
            temp_password VARCHAR(255),
            temp_password_plain VARCHAR(30),
            is_used TINYINT(1) DEFAULT 0,
            used_by_user_id INT,
            used_at DATETIME DEFAULT NULL,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_email (assigned_to_email),
            INDEX idx_used (is_used)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        error_log("Failed to create registration_codes table: " . $e->getMessage());
    }
}

/**
 * Send email with proper headers
 */
function sendEmail(string $to, string $subject, string $body): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . REPLY_TO_EMAIL,
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1'
    ];
    
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Send WhatsApp notification
 */
function sendWhatsAppNotification(PDO $pdo, array $app, string $registerUrl, string $tempPassword): bool {
    try {
        // Ensure whatsapp_queue table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending','sent','failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            INDEX idx_status (status),
            INDEX idx_phone (phone)
        )");
        
        $message = "🏦 *CIBIL Repair Partner Approved!*\n\n"
                 . "Hi {$app['name']}!\n\n"
                 . "🎉 Your partner application has been *APPROVED*!\n\n"
                 . "🔗 *Activation Link:*\n" . $registerUrl . "\n\n"
                 . "🔑 *Temporary Password:*\n`{$tempPassword}`\n\n"
                 . "⏳ *Link expires in " . REGISTRATION_CODE_EXPIRY_HOURS . " hours*\n\n"
                 . "📞 Questions? WhatsApp us: +91 99054 82503\n\n"
                 . "— CIBIL Repair Team";
        
        $stmt = $pdo->prepare("INSERT INTO whatsapp_queue (phone, message, status, created_at) VALUES (?, ?, 'pending', NOW())");
        return $stmt->execute([$app['phone'], $message]);
        
    } catch (PDOException $e) {
        error_log("WhatsApp notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log activity
 */
function logActivity(PDO $pdo, int $userId, string $type, string $description): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $type, $description]);
    } catch (PDOException $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

// ════════════════════════════════════════════════════════════════════
// EMAIL TEMPLATES
// ════════════════════════════════════════════════════════════════════

/**
 * Build approval email
 */
function buildApprovalEmail(array $app, string $tempPass, string $registerUrl, string $email): string {
    $name = htmlspecialchars($app['name'] ?? 'Partner', ENT_QUOTES, 'UTF-8');
    $partnerType = htmlspecialchars($app['partner_type'] ?? 'Business', ENT_QUOTES, 'UTF-8');
    $refNum = $app['ref_number'] ?? date('Y') . rand(1000, 9999);
    $expiry = date('d M Y, h:i A', strtotime("+" . REGISTRATION_CODE_EXPIRY_HOURS . " hours"));

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Partner Approval</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #111827; }
        .wrap { max-width: 620px; margin: 30px auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
        .hdr { background: linear-gradient(135deg, #0b2a23, #0d9e78); padding: 36px 32px; color: #fff; text-align: center; }
        .hdr .emoji { font-size: 48px; display: block; margin-bottom: 12px; }
        .hdr h1 { margin: 0; font-size: 26px; letter-spacing: .5px; }
        .hdr p { margin: 8px 0 0; opacity: .75; font-size: 14px; }
        .body { padding: 32px; }
        p { font-size: 14px; line-height: 1.75; color: #374151; }
        .cred-box { background: #0b2a23; border-radius: 12px; padding: 24px; margin: 24px 0; text-align: center; color: #fff; }
        .cred-box .label { font-size: 11px; opacity: .5; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 8px; }
        .cred-box .value { font-family: monospace; font-size: 20px; font-weight: 700; color: #34d399; letter-spacing: 2px; word-break: break-all; }
        .cred-row { display: flex; justify-content: space-between; gap: 16px; margin-top: 16px; }
        .cred-item { flex: 1; background: rgba(255,255,255,0.06); border-radius: 8px; padding: 14px; text-align: center; }
        .cred-item .ci-label { font-size: 10px; opacity: .5; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .cred-item .ci-value { font-size: 16px; font-weight: 700; color: #fbbf24; font-family: monospace; }
        .btn { display: block; text-align: center; background: linear-gradient(135deg, #0d9e78, #34d399); color: #fff; border-radius: 50px; padding: 16px 32px; text-decoration: none; font-weight: 700; font-size: 16px; margin: 24px 0; }
        .btn:hover { background: linear-gradient(135deg, #0a7d60, #22c55e); }
        .warn { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; margin: 16px 0; }
        .steps { background: #f9fafb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .steps h4 { margin: 0 0 14px; font-size: 14px; color: #0d9e78; }
        .step { display: flex; gap: 12px; margin-bottom: 10px; font-size: 13px; align-items: flex-start; color: #374151; }
        .step-num { width: 22px; height: 22px; border-radius: 50%; background: #0d9e78; color: #fff; font-weight: 700; font-size: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ftr { padding: 20px 32px; background: #f4f6f9; font-size: 12px; color: #9ca3af; text-align: center; }
        .ftr a { color: #0d9e78; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hdr">
        <span class="emoji">🎉</span>
        <h1>Welcome to the Team, {$name}!</h1>
        <p>Your {$partnerType} Partner Application is Approved</p>
    </div>
    <div class="body">
        <p>We're excited to have you on board! Your CIBIL Repair partner application has been <strong>reviewed and approved</strong>. Here are your login credentials:</p>

        <div class="cred-box">
            <div class="label">Your Partner Portal Login</div>
            <div style="margin-bottom:14px">
                <div class="label">Email / Username</div>
                <div class="value">{$email}</div>
            </div>
            <div class="cred-row">
                <div class="cred-item">
                    <div class="ci-label">Temporary Password</div>
                    <div class="ci-value">{$tempPass}</div>
                </div>
                <div class="cred-item">
                    <div class="ci-label">Reference No.</div>
                    <div class="ci-value" style="font-size:13px">{$refNum}</div>
                </div>
            </div>
        </div>

        <div class="warn">
            ⏳ <strong>This link expires on {$expiry}.</strong> 
            Please set your password before then. After clicking the link, you will be asked to create a new permanent password.
        </div>

        <a href="{$registerUrl}" class="btn">🔐 Activate My Partner Account</a>

        <div class="steps">
            <h4>📋 Getting Started in 3 Steps</h4>
            <div class="step"><div class="step-num">1</div><div>Click the button above and use the temporary password to activate your account</div></div>
            <div class="step"><div class="step-num">2</div><div>Set your own secure password</div></div>
            <div class="step"><div class="step-num">3</div><div>Login to your Partner Dashboard and start tracking your leads &amp; commissions</div></div>
        </div>

        <p style="font-size:13px;color:#6b7280">If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{$registerUrl}" style="color:#0d9e78;word-break:break-all">{$registerUrl}</a></p>

        <p>Have questions? WhatsApp us anytime: <a href="https://wa.me/919905482503" style="color:#0d9e78">+91 99054 82503</a></p>
    </div>
    <div class="ftr">
        © " . date('Y') . " CIBIL Repair · <a href="https://cibilrepair.in">cibilrepair.in</a><br>
        Delhi NCR, India · contact@cibilrepair.in<br><br>
        If you did not apply to become a partner, please ignore this email.
    </div>
</div>
</body>
</html>
HTML;
}

/**
 * Build rejection email
 */
function buildRejectionEmail(array $app, string $reason, string $adminNotes): string {
    $name = htmlspecialchars($app['name'] ?? 'Applicant', ENT_QUOTES, 'UTF-8');
    $partnerType = htmlspecialchars($app['partner_type'] ?? '', ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Partner Application Update</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .hdr { background: linear-gradient(135deg, #1f2937, #374151); padding: 32px; color: #fff; text-align: center; }
        .hdr h1 { margin: 0; font-size: 22px; }
        .body { padding: 32px; font-size: 14px; line-height: 1.75; color: #374151; }
        .reason-box { background: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px; padding: 14px 18px; margin: 16px 0; font-size: 13px; color: #991b1b; }
        .ftr { padding: 20px; background: #f4f6f9; font-size: 12px; color: #9ca3af; text-align: center; }
        .ftr a { color: #0d9e78; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hdr"><h1>Update on Your Partner Application</h1></div>
    <div class="body">
        <p>Dear {$name},</p>
        <p>Thank you for your interest in the CIBIL Repair Partner Program. After reviewing your application, we are unable to approve it at this time.</p>
        " . ($reason ? "<div class='reason-box'><strong>Reason:</strong> {$reason}</div>" : '') . "
        " . ($adminNotes ? "<p><strong>Additional Notes:</strong> {$adminNotes}</p>" : '') . "
        <p>You are welcome to re-apply after addressing the above, or contact us for more information:</p>
        <p>📱 WhatsApp: <a href='https://wa.me/919905482503' style='color:#0d9e78'>+91 99054 82503</a><br>
           📧 Email: <a href='mailto:contact@cibilrepair.in' style='color:#0d9e78'>contact@cibilrepair.in</a></p>
        <p>We appreciate your understanding.</p>
        <p>Warm regards,<br><strong>CIBIL Repair Partner Team</strong></p>
    </div>
    <div class="ftr">© " . date('Y') . " CIBIL Repair · <a href='https://cibilrepair.in'>cibilrepair.in</a></div>
</div>
</body>
</html>
HTML;
}
?>