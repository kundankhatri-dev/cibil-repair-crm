<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager', 'credit_analyst'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB failed']); exit; }

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS loan_applications (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT NOT NULL, loan_type VARCHAR(50),
    amount DECIMAL(12,2), tenure INT, bank VARCHAR(100), status ENUM('pending','processing','approved','rejected') DEFAULT 'pending',
    sanctioned_amount DECIMAL(12,2), approved_date DATE, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS loan_commission (
    id INT PRIMARY KEY AUTO_INCREMENT, loan_id INT, commission DECIMAL(12,2), status ENUM('pending','paid') DEFAULT 'pending',
    paid_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loan_applications"))['c'] ?? 0;
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loan_applications WHERE status='approved'"))['c'] ?? 0;
$sanctioned = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(sanctioned_amount) as s FROM loan_applications WHERE status='approved'"))['s'] ?? 0;
$rate = $total > 0 ? round(($approved / $total) * 100) : 0;

$loan_types = ['Home Loan', 'Personal Loan', 'Business Loan', 'Loan Against Property', 'Credit Card'];
$type_data = [];
foreach ($loan_types as $type) {
    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loan_applications WHERE loan_type='$type'"))['c'] ?? 0;
    $type_data[] = $cnt;
}

$recent = mysqli_query($conn, "SELECT l.*, u.name as client_name FROM loan_applications l JOIN users u ON l.client_id = u.id ORDER BY l.created_at DESC LIMIT 10");
$recent_apps = [];
while($r = mysqli_fetch_assoc($recent)) $recent_apps[] = $r;

$eligibility = '<div class="eligibility-card"><span class="eligibility-icon">🏠</span><div class="eligibility-title">Home Loan</div><div class="eligibility-amount">Up to ₹1 Cr</div><div class="eligibility-status">Eligible for 750+ score</div></div>
<div class="eligibility-card"><span class="eligibility-icon">👤</span><div class="eligibility-title">Personal Loan</div><div class="eligibility-amount">Up to ₹50L</div><div class="eligibility-status">Eligible for 700+ score</div></div>
<div class="eligibility-card"><span class="eligibility-icon">💼</span><div class="eligibility-title">Business Loan</div><div class="eligibility-amount">Up to ₹2 Cr</div><div class="eligibility-status">Based on turnover</div></div>
<div class="eligibility-card"><span class="eligibility-icon">🏢</span><div class="eligibility-title">Loan Against Property</div><div class="eligibility-amount">Up to ₹5 Cr</div><div class="eligibility-status">60-70% LTV</div></div>
<div class="eligibility-card"><span class="eligibility-icon">💳</span><div class="eligibility-title">Credit Card</div><div class="eligibility-amount">Up to ₹10L Limit</div><div class="eligibility-status">Instant approval</div></div>';

echo json_encode(['success'=>true, 'total_applications'=>$total, 'approved_loans'=>$approved, 'total_sanctioned'=>$sanctioned, 'approval_rate'=>$rate, 'eligibility'=>$eligibility, 'loan_type_data'=>['labels'=>$loan_types,'values'=>$type_data], 'recent_applications'=>$recent_apps]);
mysqli_close($conn);
?>