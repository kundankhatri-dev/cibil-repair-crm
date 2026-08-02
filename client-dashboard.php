<?php
// client-dashboard.php - Client portal dashboard
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: client-login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");

// Get client data
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// Get case count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM client_cases WHERE client_id = ?");
$stmt->execute([$client_id]);
$cases = $stmt->fetchColumn();

// Get documents
$stmt = $pdo->prepare("SELECT COUNT(*) FROM client_documents WHERE client_id = ?");
$stmt->execute([$client_id]);
$documents = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head><title>Client Dashboard</title></head>
<body>
<h1>👤 Welcome, <?= htmlspecialchars($client['name']) ?></h1>
<p><strong>📧 Email:</strong> <?= htmlspecialchars($client['email']) ?></p>
<p><strong>📊 Cases:</strong> <?= $cases ?></p>
<p><strong>📄 Documents:</strong> <?= $documents ?></p>
<p><a href="logout.php">Logout</a></p>
</body>
</html>
