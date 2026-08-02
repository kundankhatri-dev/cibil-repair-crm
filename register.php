<?php
// ============================================================
// REGISTRATION PAGE - With Referral Support
// ============================================================

session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'u929623538_cibil';
$DB_USER = 'u929623538_cibilrepair';
$DB_PASS = 'Kundanlaxmi@1995';

$referral_code = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$error = '';
$success = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $ref_code = trim($_POST['referral_code'] ?? '');
        
        // Validate
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status, referral_code, created_at) 
                                      VALUES (?, ?, ?, ?, 'partner', 'active', ?, NOW())");
                $stmt->execute([$name, $email, $phone, $hashed_password, $ref_code]);
                $user_id = $pdo->lastInsertId();
                
                // Create partner record
                $stmt = $pdo->prepare("INSERT INTO partners (user_id, company_name, phone, status, created_at) 
                                      VALUES (?, ?, ?, 'active', NOW())");
                $stmt->execute([$user_id, $name . "'s Company", $phone]);
                
                $success = 'Registration successful! You can now login.';
                header("refresh:2;url=login.php");
            }
        }
    }
} catch(PDOException $e) {
    $error = 'Registration failed. Please try again.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - CIBIL Repair Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: #060e1e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: rgba(10,20,50,.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(76,140,255,.2);
            border-radius: 28px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 32px 80px rgba(0,0,0,.5);
        }
        .register-card h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            text-align: center;
            margin-bottom: 8px;
        }
        .register-card p.sub {
            color: rgba(255,255,255,.5);
            text-align: center;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .ref-code-display {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.2);
            border-radius: 10px;
            padding: 12px 16px;
            text-align: center;
            margin-bottom: 20px;
        }
        .ref-code-display span {
            font-family: monospace;
            font-size: 18px;
            font-weight: 700;
            color: #22c55e;
            letter-spacing: 2px;
        }
        .ref-code-display label {
            display: block;
            font-size: 11px;
            color: rgba(255,255,255,.4);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,.5);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0,0,0,.35);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: border-color .3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #22c55e;
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,.2);
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 50px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 6px 22px rgba(34,197,94,.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(34,197,94,.45);
        }
        .error {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .success {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.25);
            color: #86efac;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: rgba(255,255,255,.4);
        }
        .login-link a {
            color: #22c55e;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-card">
        <h2>🚀 Partner Registration</h2>
        <p class="sub">Join CIBIL Repair as a Partner</p>
        
        <?php if ($referral_code): ?>
        <div class="ref-code-display">
            <label>Referral Code</label>
            <span><?= htmlspecialchars($referral_code) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="referral_code" value="<?= htmlspecialchars($referral_code) ?>">
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Your full name" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="9876543210">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" placeholder="Min 8 characters" required>
            </div>
            
            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>