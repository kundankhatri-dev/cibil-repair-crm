<?php
// document-upload.php - Document upload interface
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Upload</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 0 auto; padding: 20px; background: #f4f6f9; }
        .container { background: white; padding: 30px; border-radius: 10px; }
        .form-group { margin: 15px 0; }
        label { font-weight: 600; display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 12px 30px; background: #0d9e78; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .status { margin: 15px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .result { margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 AI Document Processor</h1>
        
        <div id="status"></div>
        
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Client ID</label>
                <input type="number" name="client_id" placeholder="Enter client ID" required>
            </div>
            
            <div class="form-group">
                <label>Document Type</label>
                <select name="document_type">
                    <option value="cibil_report">CIBIL Report</option>
                    <option value="pan_card">PAN Card</option>
                    <option value="aadhaar">Aadhaar Card</option>
                    <option value="bank_statement">Bank Statement</option>
                    <option value="other">Other Document</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Document</label>
                <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                <small>Supported: PDF, JPG, PNG (Max 10MB)</small>
            </div>
            
            <button type="submit">📤 Upload & Process</button>
        </form>
        
        <div id="result"></div>
    </div>
    
    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const statusDiv = document.getElementById('status');
            const resultDiv = document.getElementById('result');
            const formData = new FormData(this);
            
            statusDiv.innerHTML = '<div class="status" style="background:#e3f2fd;color:#0d47a1;">⏳ Uploading and processing document...</div>';
            resultDiv.innerHTML = '';
            
            try {
                const response = await fetch('document-processor.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.error) {
                    statusDiv.innerHTML = `<div class="status error">❌ ${data.error}</div>`;
                    return;
                }
                
                if (data.success) {
                    statusDiv.innerHTML = `<div class="status success">✅ ${data.message}</div>`;
                    
                    // Show extracted data
                    resultDiv.innerHTML = `<h3>📊 Extracted Data</h3><div class="result">${JSON.stringify(data, null, 2)}</div>`;
                    
                    // Show processing details
                    if (data.document_id) {
                        setTimeout(async () => {
                            const docResponse = await fetch(`document-processor.php?action=get&id=${data.document_id}`);
                            const docData = await docResponse.json();
                            if (docData.extracted_data) {
                                const extracted = JSON.parse(docData.extracted_data);
                                resultDiv.innerHTML += `<h3>🔍 AI Analysis Results</h3><div class="result">${JSON.stringify(extracted, null, 2)}</div>`;
                            }
                        }, 2000);
                    }
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="status error">❌ Error: ${error.message}</div>`;
            }
        });
    </script>
</body>
</html>
