<?php
// document-processor.php - AI-Powered Document Processing

class DocumentProcessor {
    private $pdo;
    private $api_key;
    private $upload_dir = 'uploads/documents/';
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
        $this->api_key = getenv('DEEPSEEK_API_KEY') ?: 'sk-38c3e8df141b4434aa8dcd116dd26aee';
        
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function uploadDocument($client_id, $file, $document_type) {
        // Validate file
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            return ['error' => 'Invalid file type. Only PDF, JPG, PNG allowed.'];
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['error' => 'File size exceeds 10MB limit.'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = date('Ymd_His') . '_' . $client_id . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['error' => 'Failed to upload file.'];
        }
        
        // Save to database
        $stmt = $this->pdo->prepare("
            INSERT INTO processed_documents (client_id, document_name, document_type, file_path) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$client_id, $file['name'], $document_type, $filepath]);
        $document_id = $this->pdo->lastInsertId();
        
        // Start processing
        $this->processDocument($document_id);
        
        return [
            'success' => true,
            'document_id' => $document_id,
            'message' => 'Document uploaded and processing started'
        ];
    }
    
    public function processDocument($document_id) {
        // Get document details
        $stmt = $this->pdo->prepare("SELECT * FROM processed_documents WHERE id = ?");
        $stmt->execute([$document_id]);
        $document = $stmt->fetch();
        
        if (!$document) {
            return ['error' => 'Document not found'];
        }
        
        // Update status
        $this->updateStatus($document_id, 'processing');
        $this->logAction($document_id, 'processing_started', 'info', 'Started AI processing');
        
        // Extract text from document
        $text = $this->extractText($document['file_path']);
        
        if (!$text) {
            $this->updateStatus($document_id, 'failed');
            $this->logAction($document_id, 'extraction_failed', 'error', 'Failed to extract text from document');
            return ['error' => 'Failed to extract text'];
        }
        
        // Analyze with AI
        $analysis = $this->analyzeDocument($text, $document['document_type']);
        
        if (!$analysis) {
            $this->updateStatus($document_id, 'failed');
            $this->logAction($document_id, 'analysis_failed', 'error', 'AI analysis failed');
            return ['error' => 'AI analysis failed'];
        }
        
        // Save extracted data
        $stmt = $this->pdo->prepare("
            UPDATE processed_documents 
            SET extracted_data = ?, confidence_score = ?, processing_status = 'completed', processed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([json_encode($analysis['data']), $analysis['confidence'], $document_id]);
        
        $this->logAction($document_id, 'processing_completed', 'success', 'Document processed successfully');
        
        return [
            'success' => true,
            'data' => $analysis['data'],
            'confidence' => $analysis['confidence']
        ];
    }
    
    private function extractText($filepath) {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        // For PDF files
        if ($extension === 'pdf') {
            return $this->extractPDFText($filepath);
        }
        
        // For images - use Tesseract OCR (if available)
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return $this->extractImageText($filepath);
        }
        
        return false;
    }
    
    private function extractPDFText($filepath) {
        // Use pdftotext if available
        $output = shell_exec("pdftotext '$filepath' - 2>/dev/null");
        if ($output && strlen($output) > 10) {
            return $output;
        }
        
        // Alternative: Use simple text extraction
        // In production, use a proper PDF library like PDFParser
        return "PDF text extraction requires pdftotext or PDF parser library";
    }
    
    private function extractImageText($filepath) {
        // Use Tesseract OCR
        $output = shell_exec("tesseract '$filepath' stdout 2>/dev/null");
        return $output ?: "OCR failed - install Tesseract";
    }
    
    private function analyzeDocument($text, $document_type) {
        $prompt = $this->buildPrompt($text, $document_type);
        
        // Simulate AI analysis (in production, call DeepSeek API)
        $result = $this->simulateAnalysis($text, $document_type);
        
        return $result;
    }
    
    private function buildPrompt($text, $document_type) {
        $prompt = "Analyze the following document and extract key information:\n\n";
        $prompt .= "Document Type: $document_type\n\n";
        $prompt .= "Text Content:\n" . substr($text, 0, 3000) . "\n\n";
        $prompt .= "Extract:\n";
        
        switch ($document_type) {
            case 'cibil_report':
                $prompt .= "- CIBIL Score\n- Outstanding Amounts\n- Late Payments\n- Written Off Accounts\n- Settled Accounts";
                break;
            case 'pan_card':
                $prompt .= "- Full Name\n- PAN Number\n- Date of Birth";
                break;
            case 'aadhaar':
                $prompt .= "- Full Name\n- Aadhaar Number\n- Date of Birth\n- Gender\n- Address";
                break;
            case 'bank_statement':
                $prompt .= "- Account Number\n- Account Holder Name\n- Bank Name\n- Average Balance\n- Transactions";
                break;
            default:
                $prompt .= "- Document Type\n- Key Information\n- Any Numbers or Dates";
        }
        
        return $prompt;
    }
    
    private function simulateAnalysis($text, $document_type) {
        // Simulate AI extraction (for demo purposes)
        $data = [];
        $confidence = 0.85;
        
        // Extract score if present
        if (preg_match('/score[:\s]+(\d{3})/i', $text, $matches)) {
            $data['score'] = (int)$matches[1];
        }
        
        // Extract name
        if (preg_match('/name[:\s]+([A-Za-z\s]+)/i', $text, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Detect issues
        $issues = [];
        if (stripos($text, 'written off') !== false) {
            $issues[] = 'Written Off';
            $confidence -= 0.05;
        }
        if (stripos($text, 'settled') !== false) {
            $issues[] = 'Settled';
            $confidence -= 0.05;
        }
        if (stripos($text, 'late') !== false && stripos($text, 'payment') !== false) {
            $issues[] = 'Late Payment';
            $confidence -= 0.05;
        }
        $data['issues'] = $issues;
        
        // Extract amounts
        preg_match_all('/₹[\s]*([0-9,]+)/i', $text, $matches);
        if (!empty($matches[1])) {
            $data['amounts'] = array_map(function($v) {
                return (int)str_replace(',', '', $v);
            }, $matches[1]);
        }
        
        return [
            'data' => $data,
            'confidence' => max(0.5, $confidence)
        ];
    }
    
    private function updateStatus($document_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE processed_documents SET processing_status = ? WHERE id = ?");
        $stmt->execute([$status, $document_id]);
    }
    
    private function logAction($document_id, $action, $status, $message = '') {
        $stmt = $this->pdo->prepare("
            INSERT INTO processing_logs (document_id, action, status, message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$document_id, $action, $status, $message]);
    }
    
    public function getDocument($document_id) {
        $stmt = $this->pdo->prepare("
            SELECT d.*, c.name as client_name 
            FROM processed_documents d 
            LEFT JOIN customers c ON d.client_id = c.id 
            WHERE d.id = ?
        ");
        $stmt->execute([$document_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getClientDocuments($client_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM processed_documents 
            WHERE client_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProcessingStats() {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN processing_status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(confidence_score) as avg_confidence
            FROM processed_documents
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Web interface
if (isset($_GET['action'])) {
    $processor = new DocumentProcessor();
    
    switch ($_GET['action']) {
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $client_id = $_POST['client_id'] ?? 0;
                $document_type = $_POST['document_type'] ?? 'other';
                $result = $processor->uploadDocument($client_id, $_FILES['document'], $document_type);
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
            break;
            
        case 'get':
            $document_id = $_GET['id'] ?? 0;
            $result = $processor->getDocument($document_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'list':
            $client_id = $_GET['client_id'] ?? 0;
            $result = $processor->getClientDocuments($client_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'stats':
            $result = $processor->getProcessingStats();
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'process':
            $document_id = $_GET['id'] ?? 0;
            $result = $processor->processDocument($document_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
    }
}

// CLI usage
if (php_sapi_name() === 'cli' && $argc > 1) {
    $processor = new DocumentProcessor();
    $command = $argv[1];
    
    switch ($command) {
        case 'upload':
            if ($argc < 4) {
                echo "Usage: php document-processor.php upload [client_id] [file_path] [document_type]\n";
                exit;
            }
            $client_id = (int)$argv[2];
            $file_path = $argv[3];
            $document_type = $argv[4] ?? 'other';
            
            $file_info = [
                'name' => basename($file_path),
                'type' => mime_content_type($file_path),
                'size' => filesize($file_path),
                'tmp_name' => $file_path
            ];
            $result = $processor->uploadDocument($client_id, $file_info, $document_type);
            print_r($result);
            break;
            
        case 'process':
            $document_id = (int)($argv[2] ?? 0);
            $result = $processor->processDocument($document_id);
            print_r($result);
            break;
            
        case 'stats':
            $stats = $processor->getProcessingStats();
            print_r($stats);
            break;
            
        default:
            echo "Commands: upload, process, stats\n";
    }
    exit;
}
?>
