<?php
// ============================================================
// RESET PASSWORD PAGE - Unified for ALL Roles
// ============================================================

session_start();

require_once 'api/config.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = isset($_GET['email']) ? trim(urldecode($_GET['email'])) : '';

if (empty($token) || empty($email)) {
    die("Invalid reset link. Please request a new one.");
}

// Verify token
$stmt = mysqli_prepare($conn, "SELECT id, name, email, role FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
mysqli_stmt_bind_param($stmt, "ss", $email, $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    die("Invalid or expired reset link. Please request a new password reset.");
}

$roleDisplay = ucfirst($user['role'] ?? 'User');

// Process password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update users table
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $user['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Also update source table based on role
        $role = $user['role'] ?? 'client';
        
        if ($role === 'admin') {
            $stmt = mysqli_prepare($conn, "UPDATE admin_users SET password = ? WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($role === 'partner') {
            $stmt = mysqli_prepare($conn, "UPDATE partners SET password = ? WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Log activity
        logActivity('Password Reset Success', 'User: ' . $user['email'] . ' (Role: ' . $role . ')');
        
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | CIBIL Repair</title>
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
           