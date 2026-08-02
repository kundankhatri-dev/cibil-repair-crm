<?php
// ============================================================
// analyze_document.php - SECURED PRODUCTION VERSION
// AI Document Analysis with Groq API + PDF.co Support
// ============================================================

require_once 'config.php';
require_once 'config/database.php';

// ============================================================
// SECURITY HEADERS
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    sendJsonResponse(false, null, 'Unauthorized access. Please login as admin.');
    exit;
}

// ============================================================
// CSRF PROTECTION
// ============================================================
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    sendJsonResponse(false, null, 'Invalid security token. Please refresh the page.');
    exit;
}

// ============================================================
// ERROR HANDLING
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(180);

// ============================================================
// RATE LIMITING (Database-based)
// ============================================================
$conn = Database::getInstance()->getConnection();
$ip = $_SERVER['REMOTE_ADDR'];
$userId = $_SESSION['user_id'];

// Clean old rate limit records
$stmt = $conn->prepare("DELETE FROM rate_limits WHERE first_request < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute();

// Check rate limit per user
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE (ip_address = ? OR user_id = ?) AND action = 'analyze' AND first_request > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$ip, $userId]);
$result = $stmt->fetch();

if ($result['count'] >= 15) {
    sendJsonResponse(false, null, 'Rate limit exceeded. Maximum 15 analyses per hour. Please try again later.');
    exit;
}

// Record this request
$stmt = $conn->prepare("INSERT INTO rate_limits (ip_address, user_id, action) VALUES (?, ?, 'analyze')");
$stmt->execute([$ip, $userId]);

// ============================================================
// CORS HANDLING (if needed)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Send JSON response with proper headers
 */
function sendJsonResponse($success, $data = null, $error = null) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Save analysis to database (MySQL) instead of JSON file
 */
function saveAnalysis($customerName, $fileName, $analysisText, $analysisType, $fileType, $fileSize) {
    global $conn;
    
    $id = uniqid();
    $created_at = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Admin';
    
    try {
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO document_analyses 
            (id, user_id, customer_name, file_name, file_type, file_size, analysis_type, analysis_text, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id, $userId, $customerName, $fileName, $fileType, $fileSize, $analysisType, $analysisText, $created_at
        ]);
        
        // Create backup TXT file
        $exportDir = __DIR__ . '/data/analyses/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $exportFile = $exportDir . $id . '.txt';
        $content = "=" . str_repeat("=", 60) . "\n";
        $content .= "CIBIL Repair - Document Analysis Report\n";
        $content .= "=" . str_repeat("=", 60) . "\n\n";
        $content .= "Analysis ID: $id\n";
        $content .= "Customer: $customerName\n";
        $content .= "File: $fileName\n";
        $content .= "File Size: " . round($fileSize / 1024, 2) . " KB\n";
        $content .= "Analysis Type: $analysisType\n";
        $content .= "Analyzed By: $userName\n";
        $content .= "Date: $created_at\n";
        $content .= str_repeat("-", 60) . "\n\n";
        $content .= $analysisText;
        $content .= "\n\n" . str_repeat("-", 60) . "\n";
        $content .= "Generated by CIBIL Repair AI Assistant\n";
        
        file_put_contents($exportFile, $content);
        
        // Log activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Document Analyzed', ?, ?)");
        $stmt->execute([$userId, "Analyzed: $fileName for customer $customerName", $_SERVER['REMOTE_ADDR']]);
        
        return $id;
    } catch (PDOException $e) {
        error_log("Save analysis failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Extract text from PDF using PDF.co API
 */
function extractPDFTextWithAPI($filepath) {
    if (empty(PDF_CO_API_KEY) || PDF_CO_API_KEY === 'YOUR_PDF_CO_API_KEY_HERE') {
        return null;
    }
    
    $pdfContent = base64_encode(file_get_contents($filepath));
    
    $data = [
        'async' => false,
        'encrypt' => false,
        'name' => basename($filepath),
        'file' => $pdfContent,
        'pages' => '1-0',
        'printBackground' => true,
        'mediaType' => 'screen',
        'margins' => '0px 0px 0px 0px',
        'orientation' => 'portrait'
    ];
    
    $ch = curl_init('https://api.pdf.co/v1/pdf/convert/to/text');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . PDF_CO_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['body']) && strlen(trim($result['body'])) > 100) {
        return $result['body'];
    }
    
    return null;
}

/**
 * Simple PDF text extraction (fallback)
 */
function extractPDFTextSimple($filepath) {
    $content = file_get_contents($filepath);
    if (!$content) return '';
    
    preg_match_all('/BT(.*?)ET/s', $content, $matches);
    $text = '';
    foreach ($matches[1] as $match) {
        preg_match_all('/\(([^\)]*)\)/', $match, $textMatches);
        $text .= implode(' ', $textMatches[1]) . ' ';
    }
    $text = str_replace(['\\', '(', ')'], '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}

/**
 * Extract text from DOCX file
 */
function extractDocxText($filepath) {
    $zip = new ZipArchive;
    if ($zip->open($filepath) === true) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml) {
            $text = strip_tags($xml);
            $text = html_entity_decode($text);
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }
    }
    return '';
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// ============================================================
// MAIN PROCESSING
// ============================================================

try {
    // Validate file upload
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = '';
        if (isset($_FILES['document']['error'])) {
            switch ($_FILES['document']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded';
                    break;
                default:
                    $errorMessage = 'Unknown upload error';
            }
        } else {
            $errorMessage = 'No file uploaded';
        }
        throw new Exception($errorMessage);
    }
    
    $file = $_FILES['document'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $customerName = isset($_POST['customer_name']) ? sanitizeInput($_POST['customer_name']) : '';
    
    if (empty($customerName)) {
        throw new Exception('Customer name is required');
    }
    
    // Validate file type
    $allowed = ['txt', 'pdf', 'doc', 'docx'];
    if (!in_array($extension, $allowed)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed));
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }
    
    // Extract text based on file type
    $text = '';
    
    if ($extension === 'txt') {
        $text = file_get_contents($file['tmp_name']);
        if ($text === false) {
            throw new Exception('Could not read TXT file');
        }
    } 
    elseif ($extension === 'pdf') {
        // Try PDF.co API first
        $text = extractPDFTextWithAPI($file['tmp_name']);
        
        // If API fails, try simple extraction
        if (empty($text) || strlen(trim($text)) < 100) {
            $text = extractPDFTextSimple($file['tmp_name']);
        }
        
        // If still no text, provide helpful error
        if (empty($text) || strlen(trim($text)) < 50) {
            throw new Exception('Could not extract text from PDF. Please ensure the PDF contains selectable text or convert to TXT format.');
        }
    }
    elseif ($extension === 'docx') {
        $text = extractDocxText($file['tmp_name']);
        if (empty($text)) {
            throw new Exception('Could not extract text from DOCX file. Please ensure the file is not corrupted.');
        }
    }
    
    if (empty(trim($text))) {
        throw new Exception('No text content found in file.');
    }
    
    // Limit text length for API
    $originalLength = strlen($text);
    if ($originalLength > 15000) {
        $text = substr($text, 0, 15000) . "\n\n[Note: Document truncated from " . number_format($originalLength) . " characters to 15,000 for processing]";
    }
    
    // Get analysis parameters
    $analysisType = isset($_POST['analysis_type']) ? sanitizeInput($_POST['analysis_type']) : 'general';
    $customPrompt = isset($_POST['custom_prompt']) ? sanitizeInput($_POST['custom_prompt']) : '';
    
    // Validate analysis type
    $validTypes = ['general', 'business', 'legal', 'credit_report', 'summary'];
    if (!in_array($analysisType, $validTypes)) {
        $analysisType = 'general';
    }
    
    // Build prompt based on analysis type
    $prompt = '';
    if (!empty($customPrompt)) {
        $prompt = $customPrompt . "\n\nDocument Content:\n" . $text;
    } else {
        switch ($analysisType) {
            case 'credit_report':
                $prompt = "You are a credit repair expert. Analyze this credit report and provide:\n\n";
                $prompt .= "1. KEY FINDINGS:\n- Current credit score and rating\n- Total debt amount\n- Number of derogatory marks\n- Late payments, collections, write-offs\n\n";
                $prompt .= "2. NEGATIVE ITEMS IDENTIFIED:\n- List each negative item with details\n- Impact level (High/Medium/Low)\n\n";
                $prompt .= "3. RECOMMENDED DISPUTE STRATEGIES:\n- Specific disputes for each negative item\n- Legal basis for each dispute\n\n";
                $prompt .= "4. SCORE IMPROVEMENT PLAN:\n- Expected score increase\n- Timeline for resolution\n- Action steps for the client\n\n";
                $prompt .= "Document:\n" . $text;
                break;
                
            case 'business':
                $prompt = "Analyze this business/financial document and provide:\n\n";
                $prompt .= "1. BUSINESS METRICS:\n- Revenue, customers, growth trends\n- Key performance indicators\n\n";
                $prompt .= "2. FINANCIAL INSIGHTS:\n- Profitability analysis\n- Risk assessment\n\n";
                $prompt .= "3. RECOMMENDATIONS:\n- Actionable business improvements\n- Growth opportunities\n\n";
                $prompt .= "Document:\n" . $text;
                break;
                
            case 'legal':
                $prompt = "Analyze this legal document related to credit/finance and provide:\n\n";
                $prompt .= "1. DOCUMENT SUMMARY:\n- Type of legal notice/document\n- Key legal points\n\n";
                $prompt .= "2. IMPACT ASSESSMENT:\n- How this affects creditworthiness\n- Legal implications\n\n";
                $prompt .= "3. RECOMMENDED ACTIONS:\n- Legal steps to take\n- Timeline for response\n\n";
                $prompt .= "Document:\n" . $text;
                break;
                
            case 'summary':
                $prompt = "Provide a concise, easy-to-understand summary of this document for a client:\n\n" . $text;
                break;
                
            default:
                $prompt = "Analyze this financial/credit document and provide:\n\n";
                $prompt .= "1. KEY FINDINGS\n2. CREDIT INSIGHTS\n3. RECOMMENDATIONS\n4. SUMMARY\n\nDocument:\n" . $text;
        }
    }
    
    // Call Groq API
    $data = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional credit repair and financial document analyst. Provide accurate, helpful analysis in clear language.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2500
    ];
    
    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('API connection error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        error_log("Groq API Error (HTTP $httpCode): " . substr($response, 0, 500));
        throw new Exception('AI service temporarily unavailable. Please try again later.');
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $analysisText = $result['choices'][0]['message']['content'];
        
        // Save to database
        $analysisId = saveAnalysis($customerName, $file['name'], $analysisText, $analysisType, $extension, $file['size']);
        
        if (!$analysisId) {
            throw new Exception('Failed to save analysis results');
        }
        
        sendJsonResponse(true, [
            'analysis' => ['analysis' => $analysisText],
            'filename' => $file['name'],
            'file_size' => round($file['size'] / 1024, 2) . ' KB',
            'analysis_type' => $analysisType,
            'timestamp' => date('Y-m-d H:i:s'),
            'analysis_id' => $analysisId
        ]);
    } else {
        error_log("Invalid Groq API response: " . substr($response, 0, 500));
        throw new Exception('Unable to process document. Please try again.');
    }
    
} catch (Exception $e) {
    error_log("analyze_document.php Error: " . $e->getMessage());
    
    // Log to database if possible
    if (isset($conn) && $conn) {
        try {
            $stmt = $conn->prepare("INSERT INTO error_logs (error_message, file, ip_address) VALUES (?, 'analyze_document.php', ?)");
            $stmt->execute([$e->getMessage(), $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $logError) {
            // Silently fail logging
        }
    }
    
    sendJsonResponse(false, null, $e->getMessage());
}
?>