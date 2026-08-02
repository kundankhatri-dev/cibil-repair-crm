<?php
// ============================================================
// SIMPLE LOGIN TEST - NO REDIRECTS, JUST DEBUG
// ============================================================
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'u929623538_cibil';
$DB_USER = 'u929623538_cibilrepair';
$DB_PASS = 'Kundanlaxmi@1995';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    echo "<h2>Login Attempt</h2>";
    echo "Email: $email<br>";
    echo "Password: " . str_repeat('*', strlen($pass)) . "<br><br>";
    
    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ User found!<br>";
            echo "Name: {$user['name']}<br>";
            echo "Role: {$user['role']}<br>";
            echo "Status: {$user['status']}<br>";
            echo "Password hash: {$user['password']}<br><br>";
            
            if (password_verify($pass, $user['password'])) {
                echo "✅✅✅ PASSWORD MATCHES! ✅✅✅<br><br>";
                
                // Set session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                echo "Session set successfully!<br>";
                echo "<a href='index.php'>Go to Partner Dashboard</a><br>";
                echo "<a href='simple_login_test.php?check_session'>Check Session</a><br>";
            } else {
                echo "❌❌❌ PASSWORD DOES NOT MATCH! ❌❌❌<br>";
                echo "Trying common passwords...<br>";
                $common = ['password123', 'admin123', 'partner123', 'Kundanlaxmi@1995', 'test123'];
                foreach ($common as $pwd) {
                    if (password_verify($pwd, $user['password'])) {
                        echo "✅ The password is: <strong>$pwd</strong><br>";
                    }
                }
            }
        } else {
            echo "❌ User NOT found with email: $email<br>";
        }
    } catch(PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}

// Check session
if (isset($_GET['check_session'])) {
    echo "<h2>Current Session:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; padding: 40px; max-width: 600px; margin: 0 auto; }
        input { padding: 10px; margin: 5px 0; width: 100%; }
        button { padding: 10px 20px; background: #22c55e; color: white; border: none; cursor: pointer; }
        .box { border: 1px solid #ddd; padding: 20px; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🔑 Simple Login Test</h1>
    
    <div class="box">
        <form method="POST">
            <input type="email" name="email" placeholder="Email" value="partner@cibilrepair.in" required>
            <input type="password" name="password" placeholder="Password" value="password123" required>
            <button type="submit">Test Login</button>
        </form>
    </div>
    
    <div class="box">
        <h3>Quick Links:</h3>
        <a href="simple_login_test.php?check_session">Check Session</a><br>
        <a href="index.php">Go to Partner Dashboard</a>
    </div>
</body>
</html>