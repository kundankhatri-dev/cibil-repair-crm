<?php
// ============================================================
// FORGOT PASSWORD PAGE - Unified for ALL Roles
// ============================================================

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
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

$message = '';
$messageType = '';
$showForm = true;
$submittedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'all';
    $submittedEmail = $email;
    
    if (empty($email)) {
        $message = 'Please enter your email address';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $messageType = 'error';
    } else {
        // Call API
        $apiUrl = 'api/forgot-password.php';
        $data = json_encode(['email' => $email, 'role' => $role]);
        
        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
        } else {
            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'POST',
                    'content' => $data
                ]
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($apiUrl, false, $context);
            $result = json_decode($response, true);
        }
        
        if ($result && isset($result['success']) && $result['success']) {
            $message = $result['message'] ?? 'Reset link sent! Please check your email.';
            $messageType = 'success';
            $showForm = false;
        } else {
            $message = $result['error'] ?? 'An error occurred. Please try again.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CIBIL Repair</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #060e1e;
            padding: 20px;
        }
        .bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 20%, rgba(26,74,156,.25) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 80%, rgba(34,197,94,.12) 0%, transparent 60%);
        }
        .container {
            position: relative;
            z-index: 10;
            max-width: 450px;
            width: 100%;
            background: rgba(10,20,50,.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(76,140,255,.2);
            border-radius: 28px;
            padding: 40px 35px;
            box-shadow: 0 32px 80px rgba(0,0,0,.5);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-family: 'Montserrat', sans-serif;
            color: #22c55e;
            font-size: 28px;
            font-weight: 900;
        }
        .logo h1 b { color: #fff; font-style: italic; }
        .logo p {
            color: rgba(255,255,255,.4);
            font-size: 14px;
        }
        .title {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .subtitle {
            color: rgba(255,255,255,.5);
            font-size: 14px;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-family: 'Montserrat', sans-serif;
            font-size: 10.5px;
            font-weight: 700;
            color: rgba(255,255,255,.45);
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .form-group input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(0,0,0,.35);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 12px;
            font-size: .9rem;
            color: #fff;
            transition: all .25s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #22c55e;
            background: rgba(0,0,0,.5);
            box-shadow: 0 0 0 3px rgba(34,197,94,.12);
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,.2);
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 50px;
            font-family: 'Montserrat', sans-serif;
            font-size: .95rem;
            font-weight: 800;
            color: #fff;
            cursor: pointer;
            transition: all .3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            box-shadow: 0 6px 22px rgba(34,197,94,.35);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(34,197,94,.45);
        }
        .btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }
        .error {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .success {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.25);
            color: #86efac;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .success-icon {
            font-size: 48px;
            text-align: center;
            display: block;
            margin-bottom: 15px;
        }
        .back-link {
            text-align: center;
            margin-top: 18px;
        }
        .back-link a {
            color: #4c8cff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: .78rem;
            font-weight: 700;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        .resend-link a {
            color: rgba(255,255,255,.4);
            text-decoration: none;
            font-size: 13px;
        }
        .resend-link a:hover {
            color: #22c55e;
        }
        .role-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .role-option {
            flex: 1;
            min-width: 60px;
            padding: 10px;
            border: 2px solid rgba(255,255,255,.1);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,.5);
            background: transparent;
        }
        .role-option:hover {
            border-color: rgba(34,197,94,.3);
        }
        .role-option.active {
            border-color: rgba(34,197,94,.3);
            background: rgba(34,197,94,.1);
            color: #22c55e;
        }
        .role-option input {
            display: none;
        }
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn.loading .spinner {
            display: inline-block;
        }
        .btn.loading .btn-text {
            display: none;
        }
    </style>
</head>
<body>
    <div class="bg"></div>
    
    <div class="container">
        <div class="logo">
            <h1>CIBIL<b>Repair</b></h1>
            <p>Better Credit. Better Future.</p>
        </div>

        <h2 class="title">Forgot Password?</h2>
        <p class="subtitle">We'll send you a link to reset your password</p>

        <?php if ($message): ?>
            <div class="<?php echo $messageType; ?>">
                <?php if ($messageType === 'success'): ?>
                    <span class="success-icon">📧</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <form method="POST" action="" id="forgotForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($submittedEmail); ?>" required>
                </div>

                <div class="form-group">
                    <label>Account Type (Optional)</label>
                    <div class="role-selector">
                        <label class="role-option active">
                            <input type="radio" name="role" value="all" checked> All Roles
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="admin"> 👑 Admin
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="partner"> 🤝 Partner
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="client"> 🧑 Client
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="employee"> 👔 Employee
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="hr"> 📊 HR
                        </label>
                    </div>
                    <small style="color:rgba(255,255,255,.3);font-size:11px;display:block;margin-top:4px;">Select your role to speed up the process (optional)</small>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <span class="btn-text">📩 Send Reset Link</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php else: ?>
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
            <div class="resend-link">
                <a href="forgot-password.php">Resend Reset Link</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Role selector toggle
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Form submission loading state
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>