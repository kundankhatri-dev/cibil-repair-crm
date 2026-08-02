<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB failed']); exit; }

// Create disputes table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS disputes (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT NOT NULL, dispute_no VARCHAR(50), entity VARCHAR(100),
    issue_type VARCHAR(100), description TEXT, status ENUM('draft','submitted','under_review','bank_response','resolved','closed') DEFAULT 'draft',
    submitted_date DATE, resolution_date DATE, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes"))['c'] ?? 0;
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes WHERE status NOT IN ('resolved','closed')"))['c'] ?? 0;
$resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes WHERE status IN ('resolved','closed')"))['c'] ?? 0;
$avg_days = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(DATEDIFF(resolution_date, submitted_date)) as avg FROM disputes WHERE resolution_date IS NOT NULL"))['avg'] ?? 0;

$status_dist = ['draft'=>0,'submitted'=>0,'under_review'=>0,'bank_response'=>0,'resolved'=>0];
$sd = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM disputes GROUP BY status");
while($r = mysqli_fetch_assoc($sd)) $status_dist[$r['status']] = $r['c'];

$recent = mysqli_query($conn, "SELECT d.*, u.name as client_name FROM disputes d JOIN users u ON d.client_id = u.id ORDER BY d.created_at DESC LIMIT 10");
$recent_disputes = [];
while($r = mysqli_fetch_assoc($recent)) $recent_disputes[] = $r;

$status_flow = '<div class="status-step completed"><div class="step-circle">1</div><div class="step-label">Draft</div></div>
<div class="status-step active"><div class="step-circle">2</div><div class="step-label">Submitted</div></div>
<div class="status-step"><div class="step-circle">3</div><div class="step-label">Under Review</div></div>
<div class="status-step"><div class="step-circle">4</div><div class="step-label">Bank Response</div></div>
<div class="status-step"><div class="step-circle">5</div><div class="step-label">Resolved</div></div>';

echo json_encode(['success'=>true, 'total_disputes'=>$total, 'pending_disputes'=>$pending, 'resolved_disputes'=>$resolved, 'avg_resolution_days'=>round($avg_days), 'status_flow'=>$status_flow, 'status_distribution'=>['labels'=>array_keys($status_dist),'values'=>array_values($status_dist)], 'recent_disputes'=>$recent_disputes]);
mysqli_close($conn);
?>