<?php
// client-register.php - Client portal registration
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    
    if (empty($email) || empty($password) || empty($name)) {
        $error = "All fields are required";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
            
            // Check if client exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $client = $stmt->fetch();
            
            if ($client) {
                // Create portal account
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO client_portal (client_id, username, password, status) 
                    VALUES (?, ?, ?, 'active')
                ");
                $stmt->execute([$client['id'], $email, $hashed]);
                
                $success = "✅ Account created! Please login.";
            } else {
                // Create customer and portal account
                $stmt = $pdo->prepare("
                    INSERT INTO customers (name, email, status, created_at) 
                    VALUES (?, ?, 'active', NOW())
                ");
                $stmt->execute([$name, $email]);
                $client_id = $pdo->lastInsertId();
                
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO client_portal (client_id, username, password, status) 
                    VALUES (?, ?, ?, 'active')
                ");
                $stmt->execute([$client_id, $email, $hashed]);
                
                $success = "✅ Account created! Please login.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Registration</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #0d9e78; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>🔐 Client Registration</h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <form method="POST">
        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit">Register</button>
    </form>
    <p style="text-align:center;margin-top:20px;">
        <a href="client-login.php">Already have an account? Login</a>
    </p>
</body>
</html>
