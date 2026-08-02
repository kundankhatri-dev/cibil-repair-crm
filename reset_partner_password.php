<?php
// ============================================================
// RESET PARTNER PASSWORD
// ============================================================
$DB_HOST = 'localhost';
$DB_NAME = 'u929623538_cibil';
$DB_USER = 'u929623538_cibilrepair';
$DB_PASS = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE email = 'partner@cibilrepair.in'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>✅ User Found</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Reset password to 'secret'
        $password = 'secret';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'partner@cibilrepair.in'");
        $update->execute([$hash]);
        
        echo "<h3 style='color:green;'>✅ Password reset successfully!</h3>";
        echo "<p><strong>Email:</strong> partner@cibilrepair.in</p>";
        echo "<p><strong>Password:</strong> secret</p>";
        echo "<p><strong>New Hash:</strong> $hash</p>";
        echo "<br><a href='login.php' style='background:#0d9e78;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
    } else {
        echo "<h2>❌ User Not Found</h2>";
        echo "<p>Creating user...</p>";
        
        // Create user
        $hash = password_hash('secret', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) 
                                 VALUES ('Partner User', 'partner@cibilrepair.in', ?, 'partner', 'active', NOW())");
        $insert->execute([$hash]);
        
        echo "<h3 style='color:green;'>✅ User created!</h3>";
        echo "<p><strong>Email:</strong> partner@cibilrepair.in</p>";
        echo "<p><strong>Password:</strong> secret</p>";
        echo "<br><a href='login.php' style='background:#0d9e78;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>