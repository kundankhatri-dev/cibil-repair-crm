<?php
// generate-dispute-letter.php - Auto-generate dispute letters

class DisputeLetterGenerator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    }
    
    public function generateLetter($client_id, $issue_type) {
        // Get client details
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            return ['error' => 'Client not found'];
        }
        
        // Get analysis
        $stmt = $this->pdo->prepare("SELECT * FROM credit_analysis_reports WHERE client_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$client_id]);
        $analysis = $stmt->fetch();
        
        $issues = json_decode($analysis['issues_found'] ?? '[]', true);
        
        if (!in_array($issue_type, $issues)) {
            return ['error' => 'Issue not found for this client'];
        }
        
        $letter = $this->generateLetterContent($client, $issue_type);
        
        // Save letter
        $stmt = $this->pdo->prepare("
            INSERT INTO dispute_documents (client_id, issue_type, letter_content, status, created_at) 
            VALUES (?, ?, ?, 'draft', NOW())
        ");
        $stmt->execute([$client_id, $issue_type, $letter]);
        
        return [
            'client' => $client['name'],
            'issue' => $issue_type,
            'letter' => $letter,
            'letter_id' => $this->pdo->lastInsertId()
        ];
    }
    
    private function generateLetterContent($client, $issue_type) {
        $date = date('d F Y');
        $name = $client['name'];
        $email = $client['email'] ?? 'Not provided';
        $phone = $client['phone'] ?? 'Not provided';
        
        $letter = "From:\n";
        $letter .= "$name\n";
        $letter .= "$email\n";
        $letter .= "$phone\n\n";
        
        $letter .= "To:\n";
        
        $issue_details = [
            'written_off' => [
                'title' => 'Dispute: Written-Off Account',
                'body' => "I am writing to formally dispute the written-off entry on my credit report. The account with [Bank Name] was incorrectly reported as written-off. I request you to investigate this matter and correct the entry.\n\nPlease provide the following:\n1. Full details of the account\n2. Proof of the written-off status\n3. Date of reporting\n4. Any supporting documents"
            ],
            'settled' => [
                'title' => 'Dispute: Settled Account Status',
                'body' => "I am writing to dispute the 'Settled' status of my account with [Bank Name]. The account was settled and I request that the status be updated to 'Closed' on my credit report.\n\nPlease provide:\n1. Full account details\n2. Settlement agreement\n3. NOC (No Objection Certificate)\n4. Updated credit report showing the correction"
            ],
            'late_payment' => [
                'title' => 'Dispute: Late Payment Error',
                'body' => "I am writing to dispute the late payment entry on my credit report. The late payment reported by [Bank Name] on [Date] is incorrect. I have always made timely payments.\n\nPlease provide:\n1. Account details\n2. Payment history\n3. Date of late payment\n4. Any supporting documents"
            ],
            'wrong_entry' => [
                'title' => 'Dispute: Incorrect Entry on Credit Report',
                'body' => "I am writing to dispute an incorrect entry on my credit report. The entry from [Bank Name] is not mine and should be removed immediately.\n\nPlease provide:\n1. Full account details\n2. Verification documents\n3. Date of reporting\n4. Any supporting documents"
            ]
        ];
        
        $details = $issue_details[$issue_type] ?? $issue_details['wrong_entry'];
        
        $letter .= $details['title'] . "\n\n";
        $letter .= "Dear Sir/Madam,\n\n";
        $letter .= $details['body'] . "\n\n";
        $letter .= "I request you to:\n";
        $letter .= "1. Investigate this matter urgently\n";
        $letter .= "2. Provide a written response within 30 days\n";
        $letter .= "3. Correct the entry on my credit report\n";
        $letter .= "4. Send me an updated credit report\n\n";
        $letter .= "Please find attached supporting documents for your reference.\n\n";
        $letter .= "I look forward to your prompt response.\n\n";
        $letter .= "Yours sincerely,\n";
        $letter .= "$name\n";
        $letter .= date('d F Y');
        
        return $letter;
    }
    
    public function saveLetter($client_id, $issue_type, $letter_content) {
        $stmt = $this->pdo->prepare("
            INSERT INTO dispute_documents (client_id, issue_type, letter_content, status, created_at) 
            VALUES (?, ?, ?, 'draft', NOW())
        ");
        $stmt->execute([$client_id, $issue_type, $letter_content]);
        return $this->pdo->lastInsertId();
    }
}

// Web interface
if (isset($_GET['client_id']) && isset($_GET['issue'])) {
    $generator = new DisputeLetterGenerator();
    $result = $generator->generateLetter((int)$_GET['client_id'], $_GET['issue']);
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// CLI usage
if (php_sapi_name() === 'cli' && $argc > 2) {
    $generator = new DisputeLetterGenerator();
    $client_id = (int)$argv[1];
    $issue_type = $argv[2];
    $result = $generator->generateLetter($client_id, $issue_type);
    echo "\n📝 Dispute Letter Generated\n";
    echo "═══════════════════════════════════════\n";
    echo "Client: " . ($result['client'] ?? 'Unknown') . "\n";
    echo "Issue: " . ($result['issue'] ?? 'Unknown') . "\n";
    echo "\n" . ($result['letter'] ?? 'No letter generated') . "\n";
    exit;
}

// Show usage
echo "Usage:\n";
echo "  php generate-dispute-letter.php [client_id] [issue]\n";
echo "  Example: php generate-dispute-letter.php 2 written_off\n";
echo "\nAvailable issues:\n";
echo "  - written_off\n";
echo "  - settled\n";
echo "  - late_payment\n";
echo "  - wrong_entry\n";
?>
