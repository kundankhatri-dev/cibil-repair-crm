<?php
// convert_pdf.php - PDF to TXT Converter Tool

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload error");
    }
    
    $tempFile = UPLOAD_DIR . 'temp_' . time() . '.pdf';
    move_uploaded_file($file['tmp_name'], $tempFile);
    
    // Try PDF.co API
    $pdfContent = base64_encode(file_get_contents($tempFile));
    
    $data = [
        'async' => false,
        'file' => $pdfContent,
        'pages' => '1-0'
    ];
    
    $ch = curl_init('https://api.pdf.co/v1/pdf/convert/to/text');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . PDF_CO_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    unlink($tempFile);
    
    if (isset($result['body'])) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="converted_text.txt"');
        echo $result['body'];
        exit();
    } else {
        echo "Conversion failed: " . ($result['error'] ?? 'Unknown error');
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF to TXT Converter</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; }
        input, button { padding: 10px; margin: 10px 0; width: 100%; }
        button { background: #1f8a72; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>PDF to TXT Converter</h2>
        <p>Upload a PDF file to convert it to plain text for analysis.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="pdf_file" accept=".pdf" required>
            <button type="submit">Convert to TXT</button>
        </form>
    </div>
</body>
</html>