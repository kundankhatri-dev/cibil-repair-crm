<?php
// api/credit-analyst/submit_analysis.php - Submit credit analysis
session_start();
header('Content-Type: application/json');

$allowed_roles = ['credit_analyst', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$report_id = isset($input['report_id']) ? (int)$input['report_id'] : 0;
$score = isset($input['score']) ? (int)$input['score'] : 0;
$bureau = isset($input['bureau']) ? $input['bureau'] : 'CIBIL';
$issues = isset($input['issues']) ? implode(',', $input['issues']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$analyst_id = $_SESSION['user_id'];

if ($report_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id from the analysis record
$client_query = "SELECT client_id FROM credit_analysis WHERE id = $report_id";
$client_result = mysqli_query($conn, $client_query);
$client_data = mysqli_fetch_assoc($client_result);
$client_id = $client_data['client_id'] ?? 0;

// Update credit_analysis
$update_query = "UPDATE credit_analysis 
                 SET cibil_score = $score, issues = '$issues', analyst_notes = '$notes', 
                     analyst_id = $analyst_id, status = 'analyzed', analyzed_at = NOW() 
                 WHERE id = $report_id";
mysqli_query($conn, $update_query);

// Save individual issues
if (!empty($input['issues']) && is_array($input['issues'])) {
    foreach ($input['issues'] as $issue) {
        $issue_type = str_replace('_', ' ', $issue);
        $insert_issue = "INSERT INTO credit_issues (client_id, analysis_id, issue_type, status) 
                         VALUES ($client_id, $report_id, '$issue_type', 'pending')";
        mysqli_query($conn, $insert_issue);
    }
}

echo json_encode(['success' => true, 'message' => 'Analysis submitted successfully']);

mysqli_close($conn);
?>