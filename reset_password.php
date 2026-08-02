<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$hash, 'admin@cibilrepair.in']);

echo "<h2>✅ Admin password reset successfully</h2>";
echo "<p>Email: admin@cibilrepair.in</p>";
echo "<p>Password: Admin@123</p>";
echo "<p>Generated Hash:</p>";
echo "<textarea rows='3' cols='80'>$hash</textarea>";