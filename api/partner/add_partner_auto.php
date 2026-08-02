<?php
// ============================================================
// AUTO-GENERATE PARTNER ACCOUNT WITH EMAIL
// File: /api/partner/add_partner_auto.php
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================================
// IMPROVED WELCOME EMAIL FUNCTION
// ============================================================

function sendWelcomeEmail($email, $name, $password, $partner_id) {
    // Email subject
    $subject = "🎉 Welcome to CIBIL Repair - Your Partner Account";
    
    // Plain text version (for better delivery)
    $plain_text = "
    Welcome to CIBIL Repair!
    
    Hello " . $name . ",
    
    Your partner account has been created successfully.
    
    Login Credentials:
    Email: " . $email . "
    Password: " . $password . "
    Partner ID: " . $partner_id . "
    
    Login here: https://cibilrepair.in/login.php
    
    Please change your password after first login.
    
    Security Tips:
    - Never share your password with anyone
    - Use a strong, unique password
    - Contact support if you need help
    
    Best regards,
    CIBIL Repair Team
    ";
    
    // HTML version
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Welcome to CIBIL Repair</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #0d9e78, #06b6d4); padding: 30px 20px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 28px; }
            .header p { color: rgba(255,255,255,0.9); margin: 5px 0 0; }
            .content { padding: 30px; }
            .content h2 { color: #0d9e78; margin-top: 0; }
            .credentials { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0d9e78; margin: 20px 0; }
            .credentials p { margin: 8px 0; font-size: 15px; }
            .credentials strong { color: #0d9e78; }
            .password-box { background: #ffffff; padding: 12px 16px; border-radius: 6px; border: 2px dashed #0d9e78; display: inline-block; font-size: 18px; font-weight: bold; color: #0d9e78; letter-spacing: 1px; }
            .btn { display: inline-block; background: #0d9e78; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 10px 0; }
            .btn:hover { background: #0a7d60; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
            .security-tips { background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0; }
            .security-tips ul { margin: 5px 0; padding-left: 20px; }
            @media (max-width: 480px) { .content { padding: 20px; } }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏢 CIBIL Repair</h1>
                <p>Partner Account Created Successfully</p>
            </div>
            <div class='content'>
                <h2>Welcome " . $name . "! 🎉</h2>
                <p style='font-size: 16px;'>We're excited to have you on board as a CIBIL Repair Partner.</p>
                <div class='credentials'>
                    <h4 style='margin: 0 0 12px; color: #0d9e78;'>🔑 Your Login Credentials</h4>
                    <p><strong>Email:</strong> " . $email . "</p>
                    <p><strong>Password:</strong> <span class='password-box'>" . $password . "</span></p>
                    <p style='margin-top: 10px; font-size: 13px; color: #666;'><strong>Partner ID:</strong> " . $partner_id . "</p>
                </div>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='https://cibilrepair.in/login.php' class='btn'>🚀 Login to Your Dashboard</a>
                </div>
                <div class='security-tips'>
                    <h4>🔒 Security Tips</h4>
                    <ul>
                        <li>Change your password immediately after first login</li>
                        <li>Never share your password with anyone</li>
                        <li>Contact support if you need help</li>
                    </ul>
                </div>
                <div style='background: #e6f7f2; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>📞 Need Help?</strong> <a href='mailto:support@cibilrepair.in' style='color: #0d9e78;'>support@cibilrepair.in</a></p>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; 2025 CIBIL Repair. All rights reserved.</p>
                <p style='font-size: 11px; color: #bbb;'>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CIBIL Repair <noreply@cibilrepair.in>\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    $headers .= "X-MSMail-Priority: High\r\n";
    
    // Try sending HTML email
    $result = mail($email, $subject, $html_message, $headers);
    
    // If HTML fails, try plain text
    if (!$result) {
        $plain_headers = "From: CIBIL Repair <noreply@cibilrepair.in>\r\n";
        $plain_headers .= "Reply-To: support@cibilrepair.in\r\n";
        $result = mail($email, $subject, $plain_text, $plain_headers);
    }
    
    // Log the result
    $log_message = date('Y-m-d H:i:s') . " - Email to $email: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    file_put_contents('email_log.txt', $log_message, FILE_APPEND);
    
    return $result;
}

// ============================================================
// GENERATE PASSWORD
// ============================================================

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// ============================================================
// PROCESS FORM
// ============================================================

$message = '';
$error = '';
$success = false;
$generated_password = '';
$generated_email = '';
$generated_name = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $owner = trim($_POST['owner'] ?? '');
        $commission_rate = floatval($_POST['commission_rate'] ?? 25);
        $tier = intval($_POST['tier'] ?? 2);
        $status = $_POST['status'] ?? 'active';
        
        // Validate
        if (empty($name)) throw new Exception('Business name is required');
        if (empty($email)) throw new Exception('Email is required');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format');
        if (empty($phone)) throw new Exception('Phone number is required');
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) throw new Exception('Invalid phone number');
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) throw new Exception('Email already registered');
        
        // Generate password
        $password = generateRandomPassword(12);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into users
        $stmt = $pdo->prepare("
            INSERT INTO users (
                name, email, phone, password, role, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'partner', ?, NOW(), NOW())
        ");
        $stmt->execute([$name, $email, $phone, $hashed_password, $status]);
        $user_id = $pdo->lastInsertId();
        
        // Tier mapping
        $tier_levels = ['bronze', 'silver', 'gold', 'platinum', 'diamond'];
        $tier_level = $tier_levels[$tier - 1] ?? 'bronze';
        
        // Insert into partners
        $stmt = $pdo->prepare("
            INSERT INTO partners (
                user_id, name, email, phone, status, commission_rate, tier, tier_level,
                base_commission_rate, current_commission_rate, allow_payouts, allow_referrals,
                location, owner, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id, $name, $email, $phone, $status, $commission_rate, $tier, $tier_level,
            $commission_rate, $commission_rate, $location, $owner
        ]);
        
        $partner_id = $pdo->lastInsertId();
        
        // Commit
        $pdo->commit();
        
        // Send email - IMPORTANT: This now returns true/false
        $email_sent = sendWelcomeEmail($email, $name, $password, $partner_id);
        
        // Debug: Log email result
        error_log("Email sent to $email: " . ($email_sent ? "YES" : "NO"));
        
        $success = true;
        $generated_password = $password;
        $generated_email = $email;
        $generated_name = $name;
        $message = '✅ Partner created successfully!';
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
        error_log("Partner creation error: " . $error);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Partner - Auto Generate</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 40px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #0d9e78; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #0d9e78; }
        .btn { background: #0d9e78; color: #fff; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .btn:hover { background: #0a7d60; }
        .info { background: #e6f7f2; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0d9e78; }
        .success { background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; color: #155724; }
        .error { background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545; color: #721c24; }
        .credential-box { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #ddd; }
        .credential { font-family: monospace; background: #fff; padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd; display: inline-block; font-size: 16px; }
        .email-status { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 13px; font-weight: 600; }
        .email-sent { background: #d4edda; color: #155724; }
        .email-failed { background: #f8d7da; color: #721c24; }
        .back-link { display: inline-block; margin-top: 20px; color: #0d9e78; text-decoration: none; }
        .row { display: flex; gap: 15px; }
        .row .form-group { flex: 1; }
        @media (max-width: 600px) { .row { flex-direction: column; } }
        .required { color: #dc3545; }
        .debug-info { background: #f0f0f0; padding: 10px; border-radius: 4px; font-size: 12px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🤝 Add Partner (Auto-Generate)</h1>
    <p class="subtitle">Secure credentials generated and sent via email</p>
    
    <?php if ($success): ?>
    <div class="success">
        <strong><?php echo $message; ?></strong>
        <div class="credential-box">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($generated_name); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($generated_email); ?></p>
            <p><strong>Password:</strong> <span class="credential"><?php echo htmlspecialchars($generated_password); ?></span></p>
            <p>
                <strong>Email Status:</strong> 
                <span class="email-status <?php echo $email_sent ? 'email-sent' : 'email-failed'; ?>">
                    <?php echo $email_sent ? '✅ Sent Successfully' : '❌ Failed to Send'; ?>
                </span>
            </p>
        </div>
        <p><a href="/admin-dashboard.php?section=partnerList" class="btn" style="display:inline-block; background: #28a745;">Go to Partner List</a></p>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="error">
        <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if (!$success): ?>
    <div class="info">
        <strong>📧 Email Notification:</strong> A welcome email with login credentials will be sent to the partner's email address.
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label>Business Name <span class="required">*</span></label>
            <input type="text" name="name" required placeholder="e.g., ABC Credit Solutions" id="name">
        </div>
        
        <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" required placeholder="partner@company.com" id="email">
            <small style="color:#666;font-size:12px;">📧 Welcome email with credentials will be sent here</small>
        </div>
        
        <div class="form-group">
            <label>Phone <span class="required">*</span></label>
            <input type="tel" name="phone" required placeholder="9876543210" maxlength="10" id="phone">
        </div>
        
        <div class="row">
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g., Delhi NCR">
            </div>
            <div class="form-group">
                <label>Owner Name</label>
                <input type="text" name="owner" placeholder="e.g., Mr. ABC Singh">
            </div>
        </div>
        
        <div class="row">
            <div class="form-group">
                <label>Tier</label>
                <select name="tier" id="tier" onchange="updateCommission()">
                    <option value="1">🥉 Bronze (20%)</option>
                    <option value="2" selected>🥈 Silver (25%)</option>
                    <option value="3">🥇 Gold (30%)</option>
                    <option value="4">💎 Platinum (35%)</option>
                    <option value="5">👑 Diamond (40%)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Commission Rate (%)</label>
                <input type="number" name="commission_rate" id="commission_rate" value="25" min="0" max="100">
            </div>
        </div>
        
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        
        <button type="submit" class="btn">🚀 Create Partner & Send Email</button>
    </form>
    <?php endif; ?>
    
    <a href="/admin-dashboard.php" class="back-link">← Back to Admin Dashboard</a>
</div>

<script>
function updateCommission() {
    const tier = document.getElementById('tier').value;
    const rates = {'1': 20, '2': 25, '3': 30, '4': 35, '5': 40};
    document.getElementById('commission_rate').value = rates[tier] || 25;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('name').focus();
});
</script>
</body>
</html>