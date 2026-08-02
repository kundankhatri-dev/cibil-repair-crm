<?php
// delete_analysis.php - Delete a saved analysis

require_once 'config.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit();
}

$dbFile = DB_FILE;
$analyses = [];

if (file_exists($dbFile)) {
    $analyses = json_decode(file_get_contents($dbFile), true) ?? [];
}

$found = false;
foreach ($analyses as $key => $analysis) {
    if ($analysis['id'] === $id) {
        unset($analyses[$key]);
        $found = true;
        break;
    }
}

if ($found) {
    // Reindex array
    $analyses = array_values($analyses);
    file_put_contents($dbFile, json_encode($analyses, JSON_PRETTY_PRINT));
    
    // Delete exported TXT file if exists
    $txtFile = ANALYSIS_DIR . $id . '.txt';
    if (file_exists($txtFile)) {
        unlink($txtFile);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Analysis not found']);
}
?>