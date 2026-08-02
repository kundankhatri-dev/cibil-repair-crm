<?php
// verify_login.php - Verify passwords work

$host = 'localhost';
$dbname = 'u929623538_cibil';
$username = 'u929623538_cibilrepair';
$password = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $email = 'admin@cibilrepair.in';
    $testPassword = 'Admin@123';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== ✅ VERIFICATION ===\n";
    echo "Email: $email\n";
    echo "Password: $testPassword\n\n";
    
    if ($user && password_verify($testPassword, $user['password'])) {
        echo "✅✅✅ SUCCESS! Password works!\n";
        echo "You can now login with: admin@cibilrepair.in / Admin@123\n";
        echo "\nTry your login page now!\n";
    } else {
        echo "❌ Still not working. Please contact support.\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>