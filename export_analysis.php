<?php
// export_analysis.php - Export analysis as TXT file

require_once 'config.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    die('No analysis ID provided');
}

$txtFile = ANALYSIS_DIR . $id . '.txt';

if (file_exists($txtFile)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="analysis_' . $id . '.txt"');
    header('Content-Length: ' . filesize($txtFile));
    readfile($txtFile);
    exit();
}

$dbFile = DB_FILE;
if (file_exists($dbFile)) {
    $analyses = json_decode(file_get_contents($dbFile), true) ?? [];
    foreach ($analyses as $analysis) {
        if ($analysis['id'] === $id) {
            $content = "Analysis ID: {$analysis['id']}\n";
            $content .= "Customer: {$analysis['customer_name']}\n";
            $content .= "File: {$analysis['file_name']}\n";
            $content .= "Date: {$analysis['created_at']}\n";
            $content .= "Type: {$analysis['analysis_type']}\n";
            $content .= str_repeat('-', 50) . "\n\n";
            $content .= $analysis['analysis_text'];
            
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="analysis_' . $id . '.txt"');
            echo $content;
            exit();
        }
    }
}

die('Analysis not found');
?>