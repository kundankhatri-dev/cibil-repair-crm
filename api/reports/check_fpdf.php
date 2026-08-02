<?php
// api/reports/check_fpdf.php
$fpdf_path = __DIR__ . '/fpdf/fpdf.php';

echo "Checking FPDF file: " . $fpdf_path . "<br><br>";

if (file_exists($fpdf_path)) {
    echo "✅ File exists<br>";
    
    // Check file size (should be around 80-100 KB)
    $size = filesize($fpdf_path);
    echo "File size: " . $size . " bytes<br>";
    
    if ($size < 50000) {
        echo "⚠️ File seems too small. Might be corrupted.<br>";
    }
    
    // Read first few lines
    $content = file_get_contents($fpdf_path);
    if (strpos($content, 'class FPDF') !== false) {
        echo "✅ 'class FPDF' found in file<br>";
    } else {
        echo "❌ 'class FPDF' NOT found in file. Wrong file!<br>";
    }
    
    if (strpos($content, 'function AddPage') !== false) {
        echo "✅ 'function AddPage' found<br>";
    } else {
        echo "❌ 'function AddPage' NOT found<br>";
    }
    
} else {
    echo "❌ File does not exist!";
}
?>