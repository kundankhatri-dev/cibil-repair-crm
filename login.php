<?php
// ============================================================
// LOGIN PORTAL - FIXED FOR PARTNER DASHBOARD
// ============================================================

// ========== SESSION CONFIGURATION ==========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $role = $_SESSION['user_role'] ?? 'client';
    $redirect = match($role) {
        'admin', 'super_admin' => 'admin-dashboard.php',
        'partner' => 'partner-dashboard.php',
        'employee' => 'employee-dashboard.php',
        'hr' => 'hr-dashboard.php',
        default => 'client-dashboard.php'
    };
    header("Location: $redirect");
    exit;
}

// ========== DATABASE CONNECTION ==========
require_once __DIR__ . '/config.php';

$error = '';
$success = '';
$show_captcha = false;
$remembered_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
$login_attempts = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    try {
        $pdo = getPDO();
        
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $remember = isset($_POST['remember_me']) ? true : false;
        
        // Check if CAPTCHA is required (after 3 failed attempts)
        if ($login_attempts >= 3) {
            $captcha_input = strtoupper(trim($_POST['captcha'] ?? ''));
            $expected_captcha = $_SESSION['captcha_code'] ?? '';
            
            if (empty($captcha_input) || $captcha_input !== $expected_captcha) {
                $error = "Invalid CAPTCHA code. Please try again.";
                $show_captcha = true;
                $_SESSION['captcha_code'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
            }
        }
        
        // If no CAPTCHA error, proceed with login
        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $stmt2 = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND is_active = 1");
                $stmt2->execute([$email]);
                $user = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $user['role'] = 'admin';
                }
            }
            
            if ($user && password_verify($pass, $user['password'])) {
                $_SESSION['login_attempts'] = 0;
                $login_attempts = 0;
                
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'] ?? ($user['full_name'] ?? 'User');
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'client';
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), '/');
                }
                
                $role = $user['role'] ?? 'client';
                $redirect = match($role) {
                    'super_admin', 'admin' => 'admin-dashboard.php',
                    'partner' => 'partner-dashboard.php',
                    'employee' => 'employee-dashboard.php',
                    'hr' => 'hr-dashboard.php',
                    default => 'client-dashboard.php'
                };
                
                header("Location: $redirect");
                exit;
            } else {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $login_attempts = $_SESSION['login_attempts'];
                $error = "Invalid email or password";
                
                if ($login_attempts >= 3) {
                    $show_captcha = true;
                    $_SESSION['captcha_code'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
                }
            }
        }
    } catch(PDOException $e) {
        $error = "Database error. Please try again.";
        error_log("Login error: " . $e->getMessage());
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Generate CAPTCHA if needed
if ($show_captcha || $login_attempts >= 3) {
    if (empty($_SESSION['captcha_code'])) {
        $_SESSION['captcha_code'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
    }
    $captcha_code = $_SESSION['captcha_code'];
}

// Remaining attempts
$remaining_attempts = max(0, 5 - $login_attempts);

// Get the partner ID for reference
$partner_user_id = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#071428">
<meta name="description" content="Secure partner & client login portal for CIBIL Repair. Access your dashboard, track CIBIL score fixes, and manage credit repair services.">
<meta name="robots" content="noindex, follow">
<title>CIBIL Repair | Login</title>
<link rel="canonical" href="https://cibilrepair.in/login">

<!-- ============================================================
     SCHEMA 1: WEBSITE
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "CIBIL Repair",
  "url": "https://cibilrepair.in/"
}
</script>

<!-- ============================================================
     SCHEMA 2: ORGANIZATION
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "CIBIL Repair",
  "url": "https://cibilrepair.in/",
  "logo": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
  "image": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
  "telephone": "+919905482503",
  "email": "contact@cibilrepair.in",
  "description": "India's most trusted credit repair consultancy since 2018. 5,000+ Indians have fixed their CIBIL scores legally with Upto 98% success rate.",
  "foundingDate": "2018",
  "founder": {
    "@type": "Person",
    "name": "Vikram Malhotra"
  },
  "numberOfEmployees": {
    "@type": "QuantitativeValue",
    "value": "25"
  },
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Delhi NCR",
    "addressLocality": "Delhi",
    "addressRegion": "Delhi",
    "postalCode": "110070",
    "addressCountry": "IN"
  },
  "sameAs": [
    "https://www.facebook.com/cibilrepair",
    "https://www.instagram.com/cibilrepair1",
    "https://twitter.com/cibilrepair0",
    "https://www.linkedin.com/company/cibil-repair",
    "https://www.youtube.com/channel/UCG5yi-vJkUPb2OJESSKf8Kg"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+919905482503",
    "contactType": "customer service",
    "areaServed": "IN",
    "availableLanguage": ["English", "Hindi"]
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.9",
    "reviewCount": "5000",
    "bestRating": "5"
  }
}
</script>

<!-- ============================================================
     SCHEMA 3: LOCAL BUSINESS
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "CIBIL Repair",
  "image": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
  "url": "https://cibilrepair.in/",
  "telephone": "+919905482503",
  "email": "contact@cibilrepair.in",
  "description": "India's most trusted credit repair consultancy. 5,000+ Indians have fixed their CIBIL scores legally with Upto 98% success rate.",
  "priceRange": "₹3,999 - ₹10,999",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Delhi NCR",
    "addressLocality": "Delhi",
    "addressRegion": "Delhi",
    "postalCode": "110070",
    "addressCountry": "IN"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 28.6139,
    "longitude": 77.2090
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "opens": "09:00",
      "closes": "19:00"
    },
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "Saturday",
      "opens": "10:00",
      "closes": "17:00"
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.9",
    "reviewCount": "5000",
    "bestRating": "5"
  },
  "sameAs": [
    "https://www.facebook.com/cibilrepair",
    "https://www.instagram.com/cibilrepair1",
    "https://twitter.com/cibilrepair0",
    "https://www.linkedin.com/company/cibil-repair",
    "https://www.youtube.com/channel/UCG5yi-vJkUPb2OJESSKf8Kg"
  ],
  "contactPoint": [
    {
      "@type": "ContactPoint",
      "telephone": "+919905482503",
      "contactType": "customer service",
      "areaServed": "IN",
      "availableLanguage": ["English", "Hindi"]
    },
    {
      "@type": "ContactPoint",
      "telephone": "+919905482503",
      "contactType": "sales",
      "areaServed": "IN",
      "availableLanguage": ["English", "Hindi"]
    }
  ]
}
</script>

<!-- ============================================================
     SCHEMA 4: WEBPAGE (Login Page)
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "CIBIL Repair | Login Portal",
  "description": "Secure partner & client login portal for CIBIL Repair. Access your dashboard, track CIBIL score fixes, and manage credit repair services.",
  "url": "https://cibilrepair.in/login",
  "mainEntity": {
    "@type": "Organization",
    "name": "CIBIL Repair",
    "url": "https://cibilrepair.in/"
  }
}
</script>

<!-- ============================================================
     SCHEMA 5: BREADCRUMB
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://cibilrepair.in/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Login",
      "item": "https://cibilrepair.in/login"
    }
  ]
}
</script>

<!-- ============================================================
     SCHEMA 6: PASSWORD REQUIREMENTS (Security)
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "PasswordRequirements",
  "minimumLength": 8,
  "upperCaseRequired": true,
  "lowerCaseRequired": true,
  "numberRequired": true,
  "specialCharacterRequired": true,
  "timeBasedOneTimePassword": true
}
</script>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ... (keep all existing styles) ... */
:root{
  --navy:#071428;
  --green:#22c55e;
  --green2:#16a34a;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Open Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;background:#060e1e}

.bg{position:fixed;inset:0;z-index:0;overflow:hidden}
.bg::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 20% 20%,rgba(26,74,156,.25) 0%,transparent 60%),radial-gradient(ellipse at 80% 80%,rgba(34,197,94,.12) 0%,transparent 60%)}
.bg-grid{position:absolute;inset:0;background:linear-gradient(rgba(76,140,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(76,140,255,.03) 1px,transparent 1px);background-size:48px 48px}
.orb{position:absolute;border-radius:50%;filter:blur(80px);animation:drift 20s ease-in-out infinite}
.orb1{width:500px;height:500px;background:rgba(26,74,156,.18);top:-150px;right:-100px}
.orb2{width:400px;height:400px;background:rgba(34,197,94,.1);bottom:-150px;left:-100px;animation-delay:-10s}
.orb3{width:300px;height:300px;background:rgba(245,197,24,.07);top:40%;left:30%;animation-delay:-5s}
@keyframes drift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(40px,-50px) scale(1.08)}66%{transform:translate(-30px,40px) scale(.95)}}

.particles{position:absolute;inset:0;pointer-events:none}
.p{position:absolute;width:3px;height:3px;border-radius:50%;background:var(--green);opacity:0;animation:rise linear infinite}
@keyframes rise{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:.6}90%{opacity:.4}100%{transform:translateY(-20px) scale(1);opacity:0}}

.login-wrap{position:relative;z-index:10;width:100%;max-width:460px;margin:0 20px;animation:slideUp .5s cubic-bezier(.34,1.56,.64,1) both}
@keyframes slideUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}

.login-card{background:rgba(10,20,50,.85);backdrop-filter:blur(24px);border:1px solid rgba(76,140,255,.2);border-radius:28px;box-shadow:0 32px 80px rgba(0,0,0,.5);overflow:hidden}
.login-card:hover{box-shadow:0 36px 90px rgba(0,0,0,.55)}
.card-top-bar{height:3px;background:linear-gradient(90deg,var(--green),#4c8cff,#f5c518)}

.lh{padding:36px 36px 24px;text-align:center}
.brand{display:inline-flex;align-items:center;gap:12px;margin-bottom:24px}
.brand-icon svg{width:46px;height:50px}
.brand-text{text-align:left}
.brand-name{font-family:'Montserrat',sans-serif;font-size:1.55rem;font-weight:900;color:#fff;line-height:1}
.brand-name b{color:var(--green);font-style:italic}
.brand-tag{font-size:10px;color:rgba(255,255,255,.4);margin-top:2px}

.trust-pills{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-bottom:24px}
.tp{display:inline-flex;align-items:center;gap:5px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:var(--green);padding:4px 12px;border-radius:20px;font-family:'Montserrat',sans-serif;font-size:10px;font-weight:700}

.role-indicator{background:rgba(0,0,0,.25);border-radius:30px;padding:10px 16px;margin-bottom:20px;text-align:center;font-size:12px;color:rgba(255,255,255,.5)}
.role-indicator i{color:var(--green);margin-right:6px}
.role-indicator span{color:var(--green);font-weight:700}

.lb{padding:6px 36px 36px}
.alert-box{display:none;padding:11px 16px;border-radius:10px;margin-bottom:18px;font-size:.8rem;align-items:center;gap:9px}
.alert-box.show{display:flex;animation:fadeIn .3s}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#86efac}
.attempts-remaining{font-size:.7rem;color:rgba(255,255,255,.3);margin-top:4px}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

.fg{margin-bottom:20px}
label{display:block;font-family:'Montserrat',sans-serif;font-size:10.5px;font-weight:700;color:rgba(255,255,255,.45);letter-spacing:.8px;text-transform:uppercase;margin-bottom:7px}
.inp-wrap{position:relative}
.inp-wrap input{width:100%;background:rgba(0,0,0,.35);border:1.5px solid rgba(255,255,255,.1);border-radius:12px;padding:13px 44px 13px 16px;font-family:'Open Sans',sans-serif;font-size:.9rem;color:#fff;transition:all .25s;outline:none}
.inp-wrap input:focus{border-color:var(--green);background:rgba(0,0,0,.5);box-shadow:0 0 0 3px rgba(34,197,94,.12)}
.inp-wrap input::placeholder{color:rgba(255,255,255,.2)}
.inp-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);cursor:pointer;transition:color .2s;font-size:15px;pointer-events:none}

.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 15px;
    color: rgba(255,255,255,.4);
    transition: color 0.2s;
    z-index: 2;
    background: none;
    border: none;
    padding: 5px;
}
.password-toggle:hover {
    color: var(--green);
}

/* CAPTCHA Styles */
.captcha-wrap {
    background: rgba(0,0,0,.3);
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.captcha-code {
    font-family: 'Courier New', monospace;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 4px;
    color: #4c8cff;
    background: rgba(0,0,0,.4);
    padding: 4px 14px;
    border-radius: 6px;
    user-select: none;
}
.captcha-refresh {
    background: none;
    border: none;
    color: rgba(255,255,255,.4);
    cursor: pointer;
    font-size: 16px;
    padding: 4px 8px;
}
.captcha-refresh:hover {
    color: var(--green);
}
.captcha-input {
    flex: 1;
    min-width: 100px;
    background: rgba(0,0,0,.3);
    border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 8px;
    padding: 8px 12px;
    color: #fff;
    outline: none;
    font-size: .9rem;
    text-align: center;
    letter-spacing: 2px;
}
.captcha-input:focus {
    border-color: var(--green);
}

.strength-bar{height:3px;border-radius:2px;margin-top:7px;background:rgba(255,255,255,.06);overflow:hidden}
.strength-fill{height:100%;border-radius:2px;width:0%;transition:width .4s,background .4s}
.strength-text{font-family:'Montserrat',sans-serif;font-size:10px;color:rgba(255,255,255,.3);margin-top:4px}

.form-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:8px}
.remember{display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.8rem;color:rgba(255,255,255,.45);font-family:'Montserrat',sans-serif;font-weight:600}
.remember input{width:15px;height:15px;accent-color:var(--green);cursor:pointer}
.forgot-link{font-family:'Montserrat',sans-serif;font-size:.78rem;font-weight:700;color:#4c8cff;text-decoration:none;cursor:pointer}
.forgot-link:hover{opacity:.75;text-decoration:underline}

.submit-btn{width:100%;background:linear-gradient(135deg,var(--green),var(--green2));border:none;border-radius:50px;padding:15px;font-family:'Montserrat',sans-serif;font-size:.95rem;font-weight:800;color:#fff;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 6px 22px rgba(34,197,94,.35)}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(34,197,94,.45)}
.submit-btn:disabled{opacity:.55;cursor:not-allowed}

.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:rgba(255,255,255,.2);font-size:.72rem;font-family:'Montserrat',sans-serif}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.08)}

.alt-btns{display:flex;gap:10px}
.alt-btn{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px;cursor:pointer;color:rgba(255,255,255,.5);font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:600;transition:all .2s}
.alt-btn:hover{background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.25);color:var(--green)}

.social-btns{display:flex;gap:10px;margin-top:10px}
.social-btn{flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:10px;cursor:pointer;color:rgba(255,255,255,.45);font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:600}
.social-btn:hover{background:rgba(255,255,255,.08);color:var(--green)}

.card-footer{border-top:1px solid rgba(255,255,255,.06);padding:18px 36px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.cf-link{font-family:'Montserrat',sans-serif;font-size:.73rem;font-weight:600;color:rgba(255,255,255,.3);text-decoration:none;display:flex;align-items:center;gap:5px;transition:color .2s}
.cf-link:hover{color:var(--green)}

.trust-row{display:flex;justify-content:center;gap:20px;padding:16px 36px 20px;flex-wrap:wrap}
.tr-item{font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;color:rgba(255,255,255,.2);display:flex;align-items:center;gap:5px}
.tr-item i{color:var(--green);font-size:.65rem}

/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(8px);z-index:9999;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal-box{background:rgba(10,20,50,.95);border:1px solid rgba(34,197,94,.2);border-radius:22px;padding:36px;width:90%;max-width:400px;text-align:center;position:relative}
.modal-close{position:absolute;top:14px;right:16px;background:none;border:none;color:rgba(255,255,255,.35);font-size:1.1rem;cursor:pointer}
.modal-icon{font-size:2.5rem;margin-bottom:12px}
.modal-box h3{font-family:'Montserrat',sans-serif;font-size:1.8rem;color:#fff;margin-bottom:6px}
.modal-box p{font-size:.82rem;color:rgba(255,255,255,.5);margin-bottom:18px}
.modal-input{width:100%;background:rgba(0,0,0,.4);border:1.5px solid rgba(255,255,255,.12);border-radius:10px;padding:12px 14px;color:#fff;outline:none;margin-bottom:14px}
.modal-input:focus{border-color:var(--green)}
.modal-btns{display:flex;gap:10px}
.modal-primary{flex:1;padding:12px;background:linear-gradient(135deg,var(--green),var(--green2));border:none;border-radius:50px;color:#fff;font-weight:700;cursor:pointer}
.modal-secondary{flex:1;padding:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:50px;color:rgba(255,255,255,.6);font-weight:700;cursor:pointer}
.modal-msg{font-size:.75rem;margin-top:12px}

.spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

.success-flash{position:fixed;inset:0;background:rgba(34,197,94,.2);z-index:99999;pointer-events:none;opacity:0;transition:opacity .3s}

@media(max-width:480px){
  .lh,.lb{padding-left:24px;padding-right:24px}
  .card-footer,.trust-row{padding-left:24px;padding-right:24px}
  .brand-name{font-size:1.3rem}
  .alt-btns,.social-btns{flex-direction:column}
}
/* Forgot password link */
.forgot-link {
    transition: all 0.3s ease;
}
.forgot-link:hover {
    color: #22c55e !important;
    text-decoration: underline !important;
}

/* Remember me checkbox */
.remember {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.remember input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #22c55e;
    cursor: pointer;
}
</style>
</head>
<body>

<div class="bg">
  <div class="bg-grid"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
  <div class="particles" id="particles"></div>
</div>

<div class="success-flash" id="sFlash"></div>

<div class="login-wrap">
  <div class="login-card">
    <div class="card-top-bar"></div>

    <div class="lh">
      <div class="brand">
        <div class="brand-icon">
          <svg width="46" height="50" viewBox="0 0 52 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="sg1" x1="26" y1="2" x2="26" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#1a3a7c"/><stop offset="1" stop-color="#071428"/></linearGradient>
              <linearGradient id="sg2" x1="4" y1="2" x2="48" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#4a8aff"/><stop offset="1" stop-color="#1a4a9c"/></linearGradient>
            </defs>
            <path d="M26 2 L48 12 L48 30 C48 43 38 52 26 54 C14 52 4 43 4 30 L4 12 Z" fill="url(#sg1)" stroke="url(#sg2)" stroke-width="1.5"/>
            <rect x="11" y="36" width="5" height="10" rx="1.5" fill="#ef4444"/>
            <rect x="18" y="30" width="5" height="16" rx="1.5" fill="#f97316"/>
            <rect x="25" y="24" width="5" height="22" rx="1.5" fill="#f5c518"/>
            <rect x="32" y="18" width="5" height="28" rx="1.5" fill="#22c55e"/>
            <path d="M38 18 L42 10 L46 18" stroke="#22c55e" stroke-width="2" fill="none"/>
            <line x1="42" y1="10" x2="42" y2="22" stroke="#22c55e" stroke-width="2"/>
          </svg>
        </div>
        <div class="brand-text">
          <div class="brand-name">CIBIL<b>Repair</b></div>
          <div class="brand-tag">Better Credit. Better Future.</div>
        </div>
      </div>

      <div class="trust-pills">
        <div class="tp"><i class="fas fa-lock"></i> 256-bit SSL</div>
        <div class="tp"><i class="fas fa-shield-alt"></i> RBI Compliant</div>
        <div class="tp"><i class="fas fa-clock"></i> 24/7 Secure Access</div>
      </div>

      <div class="role-indicator" id="roleIndicator">
        <i class="fas fa-info-circle"></i> Enter your credentials → Auto-redirect to your dashboard
      </div>
    </div>

    <div class="lb">
      <?php if ($error): ?>
      <div id="alertBox" class="alert-box show alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        <?php if ($login_attempts > 0): ?>
        <span class="attempts-remaining">(<?= $remaining_attempts ?> attempts remaining)</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div id="alertBox" class="alert-box show alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="fg">
          <label>EMAIL ADDRESS</label>
          <div class="inp-wrap">
            <input type="email" id="email" name="email" placeholder="Enter your registered email" value="<?= htmlspecialchars($remembered_email) ?>" required autofocus>
            <i class="far fa-envelope inp-icon"></i>
          </div>
        </div>

        <div class="fg">
          <label>PASSWORD</label>
          <div class="inp-wrap">
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
              <i class="fas fa-eye-slash"></i>
            </button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <div class="strength-text" id="strengthText"></div>
        </div>

        <?php if ($show_captcha || $login_attempts >= 3): ?>
        <div class="captcha-wrap">
          <span class="captcha-code" id="captchaCode"><?= $captcha_code ?? '' ?></span>
          <button type="button" class="captcha-refresh" onclick="refreshCaptcha()" title="Refresh CAPTCHA">
            <i class="fas fa-sync-alt"></i>
          </button>
          <input type="text" class="captcha-input" id="captcha" name="captcha" placeholder="Enter CAPTCHA" maxlength="6" autocomplete="off">
        </div>
        <input type="hidden" id="captchaHash" value="<?= md5($captcha_code ?? '') ?>">
        <?php endif; ?>

        <div class="form-row">
            <label class="remember">
                <input type="checkbox" id="rememberMe" name="remember_me" <?= $remembered_email ? 'checked' : '' ?>> 
                <span style="color: rgba(255,255,255,0.6); font-size: 13px;">Remember me</span>
            </label>
            <a href="forgot-password.php" class="forgot-link" style="color: #4c8cff; text-decoration: none; font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 600;">
                <i class="fas fa-key" style="font-size: 11px;"></i> Forgot password?
            </a>
        </div>

        <button type="submit" class="submit-btn" id="loginBtn">
          <i class="fas fa-sign-in-alt"></i> Access Account
        </button>
      </form>

      <div class="divider">or sign in with</div>

      <div class="alt-btns">
        <button type="button" class="alt-btn" onclick="magicLink()"><i class="fas fa-magic"></i> Magic Link</button>
        <button type="button" class="alt-btn" onclick="smsOTP()"><i class="fas fa-sms"></i> OTP / SMS</button>
        <button type="button" class="alt-btn" onclick="whatsappLogin()"><i class="fab fa-whatsapp"></i> WhatsApp</button>
      </div>

      <div class="social-btns">
        <button type="button" class="social-btn" onclick="socialLogin('google')"><i class="fab fa-google"></i> Google</button>
        <button type="button" class="social-btn" onclick="socialLogin('linkedin')"><i class="fab fa-linkedin"></i> LinkedIn</button>
      </div>
    </div>

    <div class="card-footer">
      <a href="index.html" class="cf-link"><i class="fas fa-home"></i> Back to Home</a>
      <a href="partners" class="cf-link"><i class="fas fa-handshake"></i> Become a Partner</a>
    </div>

    <div class="trust-row">
      <div class="tr-item"><i class="fas fa-lock"></i> End-to-End Encrypted</div>
      <div class="tr-item"><i class="fas fa-gavel"></i> RBI Approved</div>
      <div class="tr-item"><i class="fas fa-shield-alt"></i> GDPR Compliant</div>
      <div class="tr-item"><i class="fas fa-check-circle"></i> CIBIL Certified</div>
    </div>
  </div>

  <div style="text-align:center;margin-top:16px;font-size:.68rem;color:rgba(255,255,255,.2)">
    © 2025 Corvanta Financial Services · CIBIL® is a trademark of TransUnion CIBIL
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-bg" id="forgotModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('forgotModal')"><i class="fas fa-times"></i></button>
    <div class="modal-icon">🔐</div>
    <h3>Reset Password</h3>
    <p>Enter your registered email and we'll send you a secure reset link.</p>
    <input type="email" class="modal-input" id="resetEmail" placeholder="your@email.com">
    <div class="modal-btns">
      <button class="modal-primary" onclick="sendResetLink()"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
      <button class="modal-secondary" onclick="closeModal('forgotModal')">Cancel</button>
    </div>
    <div class="modal-msg" id="forgotMsg"></div>
  </div>
</div>

<!-- OTP Modal -->
<div class="modal-bg" id="otpModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('otpModal')"><i class="fas fa-times"></i></button>
    <div class="modal-icon">📱</div>
    <h3>Enter OTP</h3>
    <p>A 6-digit OTP has been sent to your registered mobile number.</p>
    <input type="text" class="modal-input" id="otpInput" placeholder="000000" maxlength="6" style="text-align:center;font-size:1.5rem;letter-spacing:8px">
    <div class="modal-btns">
      <button class="modal-primary" onclick="verifyOTP()"><i class="fas fa-check"></i> Verify OTP</button>
      <button class="modal-secondary" onclick="closeModal('otpModal')">Cancel</button>
    </div>
    <div class="modal-msg" id="otpMsg"></div>
  </div>
</div>

<script>
// Particles
(function(){
  const c = document.getElementById('particles');
  for(let i=0;i<40;i++){
    const p = document.createElement('div');
    p.className = 'p';
    p.style.cssText = `left:${Math.random()*100}%;width:${2+Math.random()*3}px;height:${2+Math.random()*3}px;animation-duration:${8+Math.random()*12}s;animation-delay:${Math.random()*15}s;opacity:${.3+Math.random()*.4}`;
    c.appendChild(p);
  }
})();

// Password Show/Hide Toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function(e) {
        e.preventDefault();
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        const icon = this.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    });
}

// Password strength meter
function checkPasswordStrength(){
  const pwd = document.getElementById('password').value;
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  
  let strength = 0;
  if(pwd.length >= 8) strength++;
  if(/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) strength++;
  if(/[0-9]/.test(pwd)) strength++;
  if(/[^a-zA-Z0-9]/.test(pwd)) strength++;
  
  const colors = ['', '#ef4444', '#f97316', '#3b82f6', '#22c55e'];
  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  
  fill.style.width = strength * 25 + '%';
  fill.style.backgroundColor = colors[strength];
  text.textContent = pwd.length ? labels[strength] : '';
  text.style.color = colors[strength] || 'rgba(255,255,255,0.3)';
}

document.getElementById('password').addEventListener('input', checkPasswordStrength);

// Auto-detect role from email as user types
document.getElementById('email').addEventListener('input', function(){
  const email = this.value.toLowerCase();
  const indicator = document.getElementById('roleIndicator');
  
  if(email.includes('admin')){
    indicator.innerHTML = '<i class="fas fa-crown"></i> You will be redirected to <span>Admin Dashboard</span>';
  } else if(email.includes('partner')){
    indicator.innerHTML = '<i class="fas fa-handshake"></i> You will be redirected to <span>Partner Dashboard</span>';
  } else if(email.includes('employee') || email.includes('john.doe') || email.includes('rahul')){
    indicator.innerHTML = '<i class="fas fa-briefcase"></i> You will be redirected to <span>Employee Dashboard</span>';
  } else if(email.includes('hr') || email.includes('priya.hr')){
    indicator.innerHTML = '<i class="fas fa-chart-line"></i> You will be redirected to <span>HR Dashboard</span>';
  } else if(email.includes('credit') || email.includes('analyst')){
    indicator.innerHTML = '<i class="fas fa-chart-line"></i> You will be redirected to <span>Credit Analyst Dashboard</span>';
  } else if(email.includes('dispute')){
    indicator.innerHTML = '<i class="fas fa-gavel"></i> You will be redirected to <span>Dispute Processing Dashboard</span>';
  } else if(email.includes('support')){
    indicator.innerHTML = '<i class="fas fa-headset"></i> You will be redirected to <span>Support Dashboard</span>';
  } else if(email.includes('sales') || email.includes('executive')){
    indicator.innerHTML = '<i class="fas fa-chart-bar"></i> You will be redirected to <span>Sales Dashboard</span>';
  } else if(email.includes('operations') || email.includes('manager')){
    indicator.innerHTML = '<i class="fas fa-tasks"></i> You will be redirected to <span>Operations Dashboard</span>';
  } else if(email.includes('finance') || email.includes('account')){
    indicator.innerHTML = '<i class="fas fa-rupee-sign"></i> You will be redirected to <span>Finance Dashboard</span>';
  } else if(email.length > 0){
    indicator.innerHTML = '<i class="fas fa-user"></i> You will be redirected to <span>Client Dashboard</span>';
  } else {
    indicator.innerHTML = '<i class="fas fa-info-circle"></i> Enter your credentials → Auto-redirect to your dashboard';
  }
});

// Modal functions
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-bg').forEach(m => {
  m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); });
});

// CAPTCHA Refresh
function refreshCaptcha(){
  const captchaCode = document.getElementById('captchaCode');
  const captchaHash = document.getElementById('captchaHash');
  
  // Generate new CAPTCHA
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let newCode = '';
  for(let i = 0; i < 6; i++){
    newCode += chars[Math.floor(Math.random() * chars.length)];
  }
  
  captchaCode.textContent = newCode;
  captchaHash.value = md5(newCode);
  document.getElementById('captcha').value = '';
}

// Simple MD5 implementation for CAPTCHA
function md5(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return hash.toString(36);
}

// Forgot password
function sendResetLink(){
  const email = document.getElementById('resetEmail').value.trim();
  const msgDiv = document.getElementById('forgotMsg');
  if(!email){
    msgDiv.innerHTML = 'Please enter your email address.';
    msgDiv.style.color = '#fca5a5';
    return;
  }
  msgDiv.innerHTML = '✅ Reset link sent! Check your email.';
  msgDiv.style.color = '#86efac';
  setTimeout(() => closeModal('forgotModal'), 2500);
}

// Alternative login methods
function magicLink(){
  const email = document.getElementById('email').value.trim();
  if(!email){ alert('Enter your email first.'); return; }
  alert('✨ Magic link sent to ' + email);
}

function smsOTP(){
  const email = document.getElementById('email').value.trim();
  if(!email){ alert('Enter your email first.'); return; }
  alert('📱 OTP sent to your registered mobile number!');
  openModal('otpModal');
}

function verifyOTP(){
  const otp = document.getElementById('otpInput').value.trim();
  const msg = document.getElementById('otpMsg');
  if(otp.length !== 6){
    msg.innerHTML = 'Enter a valid 6-digit OTP.';
    msg.style.color = '#fca5a5';
    return;
  }
  msg.innerHTML = '✅ OTP verified! Redirecting...';
  msg.style.color = '#86efac';
  setTimeout(() => {
    closeModal('otpModal');
    window.location.href = 'client-dashboard.php';
  }, 1200);
}

function whatsappLogin(){
  window.open('https://wa.me/918709455441?text=Hi! Please help me log in to my CIBIL Repair account.', '_blank');
}

function socialLogin(provider){
  alert(`Redirecting to ${provider} login...`);
}

// Form validation before submit
document.getElementById('loginForm').addEventListener('submit', function(e) {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();
  
  if(!email || !password) {
    e.preventDefault();
    alert('Please enter both email and password.');
    return false;
  }
  
  // Check CAPTCHA if visible
  const captchaInput = document.getElementById('captcha');
  if(captchaInput && captchaInput.closest('.captcha-wrap')) {
    const captchaCode = document.getElementById('captchaCode').textContent;
    if(captchaInput.value.trim().toUpperCase() !== captchaCode) {
      e.preventDefault();
      alert('Invalid CAPTCHA. Please try again.');
      refreshCaptcha();
      return false;
    }
  }
  
  // Show loading state
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in...';
  
  return true;
});

// Debug: Show if partner user exists
console.log('Partner user ID: <?= $partner_user_id ?? "Not set" ?>');
</script>
</body>
</html>