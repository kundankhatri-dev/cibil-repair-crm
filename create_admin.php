<?php
// ============================================================
// CREATE ADMIN USER - RUN ONCE THEN DELETE
// ============================================================

$host = 'localhost';
$dbname = 'u929623538_cibil';
$username = 'u929623538_cibilrepair';
$password = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'client',
            status VARCHAR(20) DEFAULT 'active',
            last_login DATETIME,
            last_login_ip VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✅ Users table created.<br>";
    }
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@cibilrepair.in']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "⚠️ Admin user already exists.<br>";
        echo "Email: admin@cibilrepair.in<br>";
        echo "Password: Admin@123<br>";
        echo "<a href='login.php'>Go to Login</a>";
        exit;
    }
    
    // Create admin user
    // Password: Admin@123 (hashed)
    $hashed_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        'Admin',
        'admin@cibilrepair.in',
        '9999999999',
        $hashed_password,
        'admin',
        'active'
    ]);
    
    echo "✅ Admin user created successfully!<br>";
    echo "Email: <strong>admin@cibilrepair.in</strong><br>";
    echo "Password: <strong>Admin@123</strong><br>";
    echo "Role: <strong>Admin</strong><br><br>";
    echo "<a href='login.php' style='display:inline-block;padding:10px 24px;background:#22c55e;color:#fff;text-decoration:none;border-radius:8px;'>Go to Login</a>";
    echo "<br><br><strong style='color:red;'>⚠️ DELETE THIS FILE AFTER USE!</strong>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>