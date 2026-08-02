<?php
// api/partner/export_tier_report.php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="tier_report_' . date('Y-m-d') . '.csv"');

$host = 'localhost';
$dbname = 'u929623538_cibil';
$db