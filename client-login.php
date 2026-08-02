<?php
// client-login.php - Client portal login
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
        
        $stmt = $pdo->prepare("
            SELECT cp.*, c.name, c.email, c.id as client_id 
            FROM client_portal cp 
            JOIN customers c ON cp.client_id = c.id 
            WHERE cp.username = ? AND cp.status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['client_id'] = $user['client_id'];
            $_SESSION['client_name'] = $user['name'];
            $_SESSION['client_email'] = $user['email'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE client_portal SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: client-portal-dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Login</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #0d9e78; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🔐 Client Login</h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    <p style="text-align:center;margin-top:20px;">
        <a href="client-register.php">New user? Register here</a>
    </p>
</body>
</html>
