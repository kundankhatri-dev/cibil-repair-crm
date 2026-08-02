<?php
// ============================================================
// register.php  (FINAL CLEAN VERSION — no partial includes)
// Partner activates account using the one-time code from email.
// ============================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'partner';
    header('Location: ' . ($role === 'admin' ? 'admin/dashboard.php' : 'partner_dashboard.php'));
    exit;
}

require_once __DIR__ . '/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Read URL params ───────────────────────────────────────────────────
$urlCode  = preg_replace('/[^a-f0-9]/i', '', $_GET['code']  ?? '');
$urlEmail = filter_var(trim($_GET['email'] ?? ''), FILTER_SANITIZE_EMAIL);

$codeData  = null;
$codeError = '';
$codeValid = false;

if ($urlCode && $urlEmail && strlen($urlCode) === 64 && filter_var($urlEmail, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare("
        SELECT rc.*, pa.name AS app_name, pa.partner_type, pa.city, pa.phone AS app_phone,
               pa.pan, pa.company, pa.bank_name, pa.bank_acc_no, pa.bank_ifsc, pa.bank_upi
        FROM registration_codes rc
        LEFT JOIN partner_applications pa ON rc.application_id = pa.id
        WHERE rc.code = ?
          AND rc.assigned_to_email = ?
          AND rc.is_used = 0
          AND rc.expires_at > NOW()
          AND rc.created_for_role = 'partner'
        LIMIT 1
    ");
    $stmt->execute([$urlCode, $urlEmail]);
    $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($codeData) {
        $codeValid = true;
    } else {
        $chk = $pdo->prepare("SELECT is_used, expires_at FROM registration_codes WHERE code = ? LIMIT 1");
        $chk->execute([$urlCode]);
        $chkRow = $chk->fetch(PDO::FETCH_ASSOC);
        if ($chkRow) {
            $codeError = $chkRow['is_used']
                ? 'This activation link has already been used. Your account is active — please log in.'
                : 'This activation link has expired. Please contact support for a new link.';
        } else {
            $codeError = 'Invalid activation link. Please use the exact link from your approval email.';
        }
    }
} elseif ($urlCode || $urlEmail) {
    $codeError = 'Incomplete activation link. Please use the full link from your approval email.';
}

// ── Handle POST ───────────────────────────────────────────────────────
$postError   = '';
$postSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $postError = 'Security token mismatch. Please refresh and try again.';
    } else {
        $postCode    = preg_replace('/[^a-f0-9]/i', '', $_POST['reg_code'] ?? '');
        $postEmail   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $tempPass    = $_POST['temp_password']    ?? '';
        $newPass     = $_POST['new_password']     ?? '';
        $conPass     = $_POST['confirm_password'] ?? '';
        $postName    = htmlspecialchars(trim($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (strlen($postCode) !== 64) {
            $postError = 'Invalid activation code.';
        } elseif (!filter_var($postEmail, FILTER_VALIDATE_EMAIL)) {
            $postError = 'Invalid email address.';
        } elseif (empty($postName) || strlen($postName) < 2) {
            $postError = 'Full name is required.';
        } elseif (strlen($newPass) < 8) {
            $postError = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPass)) {
            $postError = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $newPass)) {
            $postError = 'New password must contain at least one number.';
        } elseif ($newPass !== $conPass) {
            $postError = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM registration_codes
                WHERE code = ?
                  AND assigned_to_email = ?
                  AND is_used = 0
                  AND expires_at > NOW()
                  AND created_for_role = 'partner'
                LIMIT 1
            ");
            $stmt->execute([$postCode, $postEmail]);
            $codeRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$codeRow) {
                $postError = 'Activation link is invalid, expired, or already used.';
            } elseif (!password_verify($tempPass, $codeRow['temp_password'])) {
                $postError = 'Incorrect temporary password. Please check your approval email carefully.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Load application
                    $appRow = null;
                    if ($codeRow['application_id']) {
                        $a = $pdo->prepare("SELECT * FROM partner_applications WHERE id = ?");
                        $a->execute([$codeRow['application_id']]);
                        $appRow = $a->fetch(PDO::FETCH_ASSOC);
                    }

                    // Check email uniqueness
                    $dup = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $dup->execute([$postEmail]);
                    if ($dup->fetch()) {
                        $pdo->rollBack();
                        $postError = 'An account with this email already exists. Please log in.';
                    } else {
                        $hashedPwd  = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                        $uniqueCode = 'PART-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                        // Create user
                        $pdo->prepare("
                            INSERT INTO users
                                (name, email, phone, password, role, city, status, unique_code, created_by, is_verified_by_code, created_at)
                            VALUES (?, ?, ?, ?, 'partner', ?, 'active', ?, ?, 1, NOW())
                        ")->execute([
                            $postName, $postEmail,
                            $appRow['phone'] ?? '',
                            $hashedPwd,
                            $appRow['city']  ?? '',
                            $uniqueCode,
                            $codeRow['created_by'] ?? 0
                        ]);
                        $userId = (int)$pdo->lastInsertId();

                        // Create partner record
                        try {
                            $pdo->prepare("
                                INSERT INTO partners
                                    (user_id, bank_name, bank_account, ifsc_code, pan_number, commission_rate, created_at)
                                VALUES (?, ?, ?, ?, ?, 30.00, NOW())
                            ")->execute([
                                $userId,
                                $appRow['bank_name']   ?? '',
                                $appRow['bank_acc_no'] ?? '',
                                $appRow['bank_ifsc']   ?? '',
                                $appRow['pan']         ?? '',
                            ]);
                        } catch (PDOException $e) {
                            $pdo->exec("CREATE TABLE IF NOT EXISTS partners (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT NOT NULL UNIQUE,
                                bank_name VARCHAR(255), bank_account VARCHAR(100),
                                ifsc_code VARCHAR(20), pan_number VARCHAR(20),
                                commission_rate DECIMAL(5,2) DEFAULT 30.00,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )");
                            $pdo->prepare("INSERT IGNORE INTO partners (user_id, commission_rate) VALUES (?, 30.00)")
                                ->execute([$userId]);
                        }

                        // Consume code
                        $pdo->prepare("
                            UPDATE registration_codes
                            SET is_used = 1, used_by_user_id = ?, temp_password_plain = NULL
                            WHERE code = ?
                        ")->execute([$userId, $postCode]);

                        // Link application
                        if ($codeRow['application_id']) {
                            $pdo->prepare("UPDATE partner_applications SET user_id = ? WHERE id = ?")
                                ->execute([$userId, $codeRow['application_id']]);
                        }

                        // Activity log
                        try {
                            $pdo->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'partner_registered', ?)")
                                ->execute([$userId, 'Partner account activated via registration link']);
                        } catch (PDOException $e) { /* non-fatal */ }

                        $pdo->commit();

                        // Welcome email
                        $dashUrl  = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'cibilrepair.in') . '/partner_dashboard.php';
                        $loginUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'cibilrepair.in') . '/login.html';
                        $wBody = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f4f6f9;padding:20px'>
<div style='max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)'>
<div style='background:linear-gradient(135deg,#0b2a23,#0d9e78);padding:28px;color:#fff;text-align:center'>
  <h2 style='margin:0'>🚀 Welcome Aboard, " . htmlspecialchars($postName) . "!</h2>
  <p style='opacity:.7;margin:8px 0 0'>CIBIL Repair Partner Account Active</p>
</div>
<div style='padding:28px;font-size:14px;color:#374151;line-height:1.7'>
  <p>Your partner account is now live. Log in anytime at:<br>
  <a href='{$loginUrl}' style='color:#0d9e78'>{$loginUrl}</a></p>
  <p style='text-align:center;margin:22px 0'>
    <a href='{$dashUrl}' style='background:#0d9e78;color:#fff;padding:13px 28px;border-radius:50px;text-decoration:none;font-weight:700;display:inline-block'>Go to Dashboard</a>
  </p>
  <p>Questions? WhatsApp: <a href='https://wa.me/919905482503' style='color:#0d9e78'>+91 99054 82503</a></p>
</div>
</div></body></html>";
                        $mh  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
                        $mh .= "From: CIBIL Repair <no-reply@cibilrepair.in>\r\n";
                        @mail($postEmail, '🚀 Welcome to CIBIL Repair Partner Program!', $wBody, $mh);

                        $postSuccess = true;
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $postError = 'Account creation failed. Please try again or contact support.';
                    error_log('register.php error: ' . $e->getMessage());
                }
            }
        }
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Activate Partner Account | CIBIL Repair</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(135deg,#0b2a23,#071428 50%,#0d2550);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:20px;width:100%;max-width:480px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35)}
.card-hdr{background:linear-gradient(135deg,#0b2a23,#0d9e78);padding:32px;color:#fff;text-align:center}
.card-hdr .logo{font-size:12px;font-weight:700;opacity:.55;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.card-hdr h1{font-size:22px;font-weight:800;margin:0}
.card-hdr p{font-size:13px;opacity:.65;margin:6px 0 0}
.card-body{padding:32px}
.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;line-height:1.55;font-weight:500}
.alert-err {background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.alert i{margin-top:2px;flex-shrink:0}
.user-chip{display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:22px}
.user-chip .av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#0d9e78,#34d399);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;flex-shrink:0}
.user-chip .info strong{display:block;font-size:13px;font-weight:700;color:#065f46}
.user-chip .info span{font-size:12px;color:#059669}
.fgrp{margin-bottom:16px}
.fgrp label{display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px}
.req{color:#dc2626}
.fi-wrap{position:relative}
.fi{width:100%;padding:11px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s;background:#f9fafb}
.fi:focus{border-color:#0d9e78;background:#fff}
.fi-wrap .fi{padding-right:44px}
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:15px;padding:4px}
.eye-btn:hover{color:#0d9e78}
.divider{height:1px;background:#f3f4f6;margin:20px 0}
.pwd-bar{height:4px;border-radius:99px;background:#e5e7eb;margin-top:6px;overflow:hidden}
.pwd-fill{height:100%;border-radius:99px;transition:width .3s,background .3s;width:0}
.pwd-hint{font-size:11px;color:#9ca3af;margin-top:4px}
.req-hint{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;font-size:12px;color:#065f46;margin-bottom:20px}
.req-hint ul{margin-left:16px;margin-top:4px}
.req-hint li{margin-bottom:3px}
.submit-btn{width:100%;padding:14px;background:linear-gradient(135deg,#0d9e78,#34d399);color:#fff;border:none;border-radius:50px;font-size:15px;font-weight:800;font-family:inherit;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px}
.submit-btn:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 8px 24px rgba(13,158,120,.4)}
.submit-btn:disabled{opacity:.6;cursor:not-allowed}
.help-link{display:block;text-align:center;margin-top:14px;font-size:12px;color:#9ca3af}
.help-link a{color:#0d9e78;font-weight:600;text-decoration:none}
/* States */
.state-wrap{padding:48px 32px;text-align:center}
.state-icon{font-size:56px;display:block;margin-bottom:16px}
.state-title{font-size:22px;font-weight:800;margin-bottom:8px}
.state-body{font-size:14px;color:#6b7280;line-height:1.7;max-width:340px;margin:0 auto 24px}
.big-btn{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;border-radius:50px;font-weight:800;font-size:15px;text-decoration:none;transition:all .2s}
.big-btn-green{background:linear-gradient(135deg,#0d9e78,#34d399);color:#fff}
.big-btn-green:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(13,158,120,.4)}
.big-btn-wa{background:#25D366;color:#fff}
.big-btn-wa:hover{transform:translateY(-1px)}
.sub-link{margin-top:14px;font-size:13px;color:#9ca3af}
.sub-link a{color:#0d9e78;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">

  <div class="card-hdr">
    <div class="logo">CIBIL Repair · Partner Program</div>
    <?php if ($postSuccess): ?>
      <h1>Account Activated! 🎉</h1><p>Your partner account is ready to use</p>
    <?php elseif ($codeError): ?>
      <h1>Activation Issue ⚠️</h1><p>We could not process your link</p>
    <?php else: ?>
      <h1>Activate Your Account 🔐</h1><p>Set your permanent password to get started</p>
    <?php endif; ?>
  </div>

  <?php if ($postSuccess): ?>
  <!-- ══ SUCCESS ══ -->
  <div class="state-wrap">
    <span class="state-icon">🚀</span>
    <div class="state-title">You're All Set!</div>
    <p class="state-body">Your CIBIL Repair Partner account is now active. Log in to access your dashboard and start earning commissions.</p>
    <a href="login.html" class="big-btn big-btn-green"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
    <p class="sub-link" style="margin-top:16px;font-size:12px;color:#9ca3af">A welcome email has been sent to your inbox.</p>
  </div>

  <?php elseif ($codeError): ?>
  <!-- ══ LINK ERROR ══ -->
  <div class="state-wrap">
    <span class="state-icon">⚠️</span>
    <div class="state-title" style="color:#dc2626">Link Problem</div>
    <p class="state-body"><?= h($codeError) ?></p>
    <a href="https://wa.me/919905482503?text=<?= urlencode('Hi! I need help activating my partner account. Email: ' . $urlEmail) ?>"
       class="big-btn big-btn-wa" target="_blank">
      <i class="fab fa-whatsapp"></i> Get Help on WhatsApp
    </a>
    <div class="sub-link"><a href="login.html">← Back to Login</a></div>
  </div>

  <?php else: ?>
  <!-- ══ ACTIVATION FORM ══ -->
  <div class="card-body">
    <?php if ($postError): ?>
    <div class="alert alert-err"><i class="fas fa-times-circle"></i><span><?= h($postError) ?></span></div>
    <?php endif; ?>

    <?php if ($codeData): ?>
    <div class="user-chip">
      <div class="av"><?= strtoupper(substr($codeData['app_name'] ?? 'P', 0, 2)) ?></div>
      <div class="info">
        <strong><?= h($codeData['app_name'] ?? '') ?></strong>
        <span><?= h($codeData['partner_type'] ?? 'Partner') ?> · <?= h($urlEmail) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <div class="req-hint">
      <strong>Password requirements:</strong>
      <ul>
        <li>At least 8 characters</li>
        <li>At least one uppercase letter (A–Z)</li>
        <li>At least one number (0–9)</li>
      </ul>
    </div>

    <form method="POST" id="regForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
      <input type="hidden" name="reg_code"   value="<?= h($urlCode) ?>">
      <input type="hidden" name="email"      value="<?= h($urlEmail) ?>">

      <div class="fgrp">
        <label>Full Name <span class="req">*</span></label>
        <input class="fi" type="text" name="full_name" required autocomplete="name"
               placeholder="Your full name as per PAN"
               value="<?= h($_POST['full_name'] ?? ($codeData['app_name'] ?? '')) ?>">
      </div>

      <div class="fgrp">
        <label>Temporary Password <span class="req">*</span></label>
        <div class="fi-wrap">
          <input class="fi" type="password" name="temp_password" id="tmpPwd"
                 required placeholder="From your approval email" autocomplete="current-password">
          <button type="button" class="eye-btn" onclick="toggleEye('tmpPwd',this)" tabindex="-1"><i class="fas fa-eye"></i></button>
        </div>
      </div>

      <div class="divider"></div>

      <div class="fgrp">
        <label>New Password <span class="req">*</span></label>
        <div class="fi-wrap">
          <input class="fi" type="password" name="new_password" id="newPwd"
                 required placeholder="Min 8 chars, 1 uppercase, 1 number"
                 autocomplete="new-password" oninput="checkPwd(this)">
          <button type="button" class="eye-btn" onclick="toggleEye('newPwd',this)" tabindex="-1"><i class="fas fa-eye"></i></button>
        </div>
        <div class="pwd-bar"><div class="pwd-fill" id="pwdBar"></div></div>
        <div class="pwd-hint" id="pwdHint">Enter a strong password</div>
      </div>

      <div class="fgrp">
        <label>Confirm Password <span class="req">*</span></label>
        <div class="fi-wrap">
          <input class="fi" type="password" name="confirm_password" id="conPwd"
                 required placeholder="Re-enter new password" autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="toggleEye('conPwd',this)" tabindex="-1"><i class="fas fa-eye"></i></button>
        </div>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">
        <i class="fas fa-lock-open"></i> Activate My Partner Account
      </button>
    </form>

    <div class="help-link">
      Need help? <a href="https://wa.me/919905482503?text=<?= urlencode('Hi! I need help activating my partner account. Email: ' . $urlEmail) ?>" target="_blank">WhatsApp Support</a>
      &nbsp;·&nbsp; <a href="login.html">Login instead</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleEye(id, btn) {
    const el = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (el.type === 'password') { el.type = 'text';     ic.className = 'fas fa-eye-slash'; }
    else                        { el.type = 'password'; ic.className = 'fas fa-eye'; }
}
function checkPwd(el) {
    const v   = el.value;
    const bar  = document.getElementById('pwdBar');
    const hint = document.getElementById('pwdHint');
    let score  = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    if (v.length >= 12)          score++;
    const c = ['','#dc2626','#f97316','#eab308','#22c55e','#16a34a'];
    const l = ['','Very Weak','Weak','Fair','Strong','Very Strong'];
    bar.style.width      = (score * 20) + '%';
    bar.style.background = c[score] || '#e5e7eb';
    hint.textContent     = l[score] || 'Enter a strong password';
    hint.style.color     = c[score] || '#9ca3af';
}
const form = document.getElementById('regForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const np = document.getElementById('newPwd')?.value || '';
        const cp = document.getElementById('conPwd')?.value || '';
        if (np && cp && np !== cp) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
        const btn = document.getElementById('submitBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Activating…'; }
    });
}
</script>
</body>
</html>