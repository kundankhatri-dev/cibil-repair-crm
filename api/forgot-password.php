<?php
// ============================================================
// FORGOT PASSWORD API - SIMPLIFIED VERSION
// ============================================================

header('Content-Type: application/json');

// Database configuration
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

try {
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $email = isset($data['email']) ? trim($data['email']) : '';
    $role = isset($data['role']) ? trim($data['role']) : '';
    
    // Validate email
    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Connect to database
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    mysqli_set_charset($conn, 'utf8mb4');
    
    // Check if user exists in users table ONLY
    $query = "SELECT id, name, email, role FROM users WHERE email = ? AND status = 'active'";
    $params = [$email];
    
    if (!empty($role) && $role !== 'all') {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($role) && $role !== 'all') {
        mysqli_stmt_bind_param($stmt, "ss", $params[0], $params[1]);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $params[0]);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // If user not found, return success (for security)
    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'If your email is registered, you will receive a reset link.']);
        mysqli_close($conn);
        exit;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with token
    $stmt = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $token, $expiry, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    mysqli_close($conn);
    
    // Create reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $resetLink = $protocol . $host . "/reset-password.php?token=" . $token . "&email=" . urlencode($email);
    
    $userName = $user['name'] ?? 'User';
    $roleDisplay = ucfirst($user['role'] ?? 'Client');
    
    // Email subject
    $subject = "Reset Your CIBIL Repair Password";
    
    // Plain text email (more reliable)
    $message = "Hello " . $userName . ",\n\n";
    $message .= "We received a request to reset your password for your " . $roleDisplay . " account.\n\n";
    $message .= "Click the link below to reset your password:\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link expires in 1 hour.\n\n";
    $message .= "If you didn't request this, please ignore this email.\n\n";
    $message .= "CIBIL Repair Team\n";
    $message .= "https://cibilrepair.in";
    
    // Email headers
    $headers = "From: CIBIL Repair <noreply@" . $host . ">" . "\r\n";
    $headers .= "Reply-To: support@cibilrepair.in" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Send email
    $mailSent = mail($email, $subject, $message, $headers);
    
    if ($mailSent) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset link sent to your email! Please check your inbox (and spam folder).'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Reset link generated. If you don\'t receive it within 5 minutes, please contact support.',
            'debug' => ['link' => $resetLink]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>