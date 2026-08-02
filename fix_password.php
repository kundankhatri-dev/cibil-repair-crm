<?php
// File: fix_password.php
$host = 'localhost';
$dbname = 'u929623538_cibil';
$username = 'u929623538_cibilrepair';
$password = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Fixing Passwords for All Users</h2>";
    
    // Set the password you want to use
    $plain_password = 'Admin@123';
    
    // Generate a NEW correct hash
    $new_hash = password_hash($plain_password, PASSWORD_DEFAULT);
    
    echo "Password to set: <strong>$plain_password</strong><br>";
    echo "Generated Hash: <code style='background:#f0f0f0;padding:5px;display:block;word-break:break-all;'>$new_hash</code><br><br>";
    
    // Update ALL users with the new hash
    $update = $pdo->prepare("UPDATE admin_users SET password = :hash");
    $update->execute([':hash' => $new_hash]);
    
    $count = $update->rowCount();
    echo "<p style='color:green'>✅ Updated $count users with new password hash.</p><br>";
    
    // Verify it works for each user
    echo "<h3>Verifying Updates:</h3>";
    $users = $pdo->query("SELECT id, email, full_name, role FROM admin_users");
    
    $all_working = true;
    while ($user = $users->fetch()) {
        // Get the stored hash for this user
        $checkStmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = :id");
        $checkStmt->execute([':id' => $user['id']]);
        $stored = $checkStmt->fetch();
        
        if (password_verify($plain_password, $stored['password'])) {
            echo "<p style='color:green'>✅ {$user['email']} - Password works!</p>";
        } else {
            echo "<p style='color:red'>❌ {$user['email']} - Password still not working!</p>";
            $all_working = false;
        }
    }
    
    if ($all_working) {
        echo "<hr>";
        echo "<h3 style='color:green'>🎉 SUCCESS! All passwords fixed!</h3>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> admin@cibilrepair.com</li>";
        echo "<li><strong>Password:</strong> Admin@123</li>";
        echo "</ul>";
        echo "<a href='login.html' style='display:inline-block;background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:10px;'>Go to Login Page</a>";
    } else {
        echo "<p style='color:red'>Still having issues. Please check the error messages above.</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>