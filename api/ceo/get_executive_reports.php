<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['ceo', 'founder', 'admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Sample executive reports
$reports = [
    ['id' => 'Q1_2025', 'name' => 'Q1 2025 Executive Summary', 'period' => 'Jan - Mar 2025', 'generated' => '2025-04-05'],
    ['id' => 'annual_2024', 'name' => 'Annual Report 2024', 'period' => 'Full Year 2024', 'generated' => '2025-01-15'],
    ['id' => 'partner_performance', 'name' => 'Partner Performance Review', 'period' => 'Q1 2025', 'generated' => '2025-04-02'],
    ['id' => 'client_satisfaction', 'name' => 'Client Satisfaction Survey', 'period' => 'March 2025', 'generated' => '2025-04-01'],
    ['id' => 'financial_forecast', 'name' => '2025 Financial Forecast', 'period' => 'Full Year 2025', 'generated' => '2025-01-20']
];

echo json_encode(['success' => true, 'reports' => $reports]);
?>