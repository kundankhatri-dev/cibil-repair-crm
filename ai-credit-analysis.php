<?php
// ai-credit-analysis.php - AI-Powered Credit Analysis

class CreditAnalyzer {
    private $pdo;
    private $api_key;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
        $this->api_key = getenv('DEEPSEEK_API_KEY') ?: 'sk-38c3e8df141b4434aa8dcd116dd26aee';
    }
    
    public function analyzeReport($client_id, $report_data) {
        // Parse the report data
        $score = $this->extractScore($report_data);
        $issues = $this->detectIssues($report_data);
        $analysis = $this->generateAnalysis($score, $issues);
        $recommendations = $this->generateRecommendations($issues, $score);
        
        // Save results
        $stmt = $this->pdo->prepare("
            INSERT INTO credit_analysis_reports 
            (client_id, report_file_path, cibil_score, issues_found, analysis_result, recommendations, status, analyzed_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW())
        ");
        $stmt->execute([$client_id, '', $score, json_encode($issues), $analysis, json_encode($recommendations)]);
        
        return [
            'score' => $score,
            'issues' => $issues,
            'analysis' => $analysis,
            'recommendations' => $recommendations
        ];
    }
    
    private function extractScore($data) {
        // Extract CIBIL score from report text
        if (preg_match('/score[:\s]+(\d{3})/i', $data, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
    
    private function detectIssues($data) {
        $issues = [];
        $keywords = [
            'written_off' => ['written off', 'written-off', 'write off'],
            'settled' => ['settled', 'settlement'],
            'late_payment' => ['late payment', 'delay', 'overdue'],
            'wrong_entry' => ['wrong entry', 'incorrect', 'unauthorized'],
            'high_utilization' => ['high utilization', 'credit limit exceeded'],
            'multiple_enquiries' => ['multiple enquiries', 'hard enquiry']
        ];
        
        foreach ($keywords as $issue => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($data, $pattern) !== false) {
                    $issues[] = $issue;
                    break;
                }
            }
        }
        return array_unique($issues);
    }
    
    private function generateAnalysis($score, $issues) {
        $analysis = "CIBIL Score Analysis:\n";
        $analysis .= "Current Score: $score\n\n";
        
        if ($score >= 750) {
            $analysis .= "✅ Excellent Score! You qualify for the best loan rates.\n";
        } elseif ($score >= 700) {
            $analysis .= "⚠️ Good Score. You qualify for loans, but not the best rates.\n";
        } elseif ($score >= 650) {
            $analysis .= "⚠️ Average Score. Some lenders may reject your application.\n";
        } else {
            $analysis .= "❌ Low Score. Most lenders will reject your application.\n";
        }
        
        if (!empty($issues)) {
            $analysis .= "\nIssues Detected:\n";
            foreach ($issues as $issue) {
                $analysis .= "  • " . str_replace('_', ' ', ucfirst($issue)) . "\n";
            }
        }
        
        $analysis .= "\nPotential Score Improvement: " . $this->estimateImprovement($score, $issues) . " points";
        
        return $analysis;
    }
    
    private function generateRecommendations($issues, $score) {
        $recommendations = [];
        
        foreach ($issues as $issue) {
            switch ($issue) {
                case 'written_off':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'Dispute Written-Off Account',
                        'description' => 'File a dispute with the bank and credit bureau to remove the written-off entry.',
                        'estimated_time' => '30-45 days',
                        'estimated_points' => '80-120'
                    ];
                    break;
                case 'settled':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'Convert Settled to Closed',
                        'description' => 'Request the bank to update the status from "Settled" to "Closed" on your credit report.',
                        'estimated_time' => '20-30 days',
                        'estimated_points' => '50-80'
                    ];
                    break;
                case 'late_payment':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'Dispute Late Payment',
                        'description' => 'Contact the lender to verify if the late payment was reported correctly.',
                        'estimated_time' => '15-20 days',
                        'estimated_points' => '20-40'
                    ];
                    break;
                case 'wrong_entry':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'Remove Wrong Entry',
                        'description' => 'File a dispute with the credit bureau to remove the incorrect entry.',
                        'estimated_time' => '15-30 days',
                        'estimated_points' => '30-60'
                    ];
                    break;
                default:
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'General Credit Improvement',
                        'description' => 'Focus on timely payments and reducing credit utilization.',
                        'estimated_time' => '30-60 days',
                        'estimated_points' => '10-30'
                    ];
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Maintain Good Habits',
                'description' => 'Continue timely payments and keep credit utilization under 30%.',
                'estimated_time' => 'Ongoing',
                'estimated_points' => '5-10'
            ];
        }
        
        return $recommendations;
    }
    
    private function estimateImprovement($score, $issues) {
        $total = 0;
        $weights = [
            'written_off' => 100,
            'settled' => 70,
            'wrong_entry' => 50,
            'multiple_enquiries' => 30,
            'late_payment' => 30,
            'high_utilization' => 20
        ];
        
        foreach ($issues as $issue) {
            $total += $weights[$issue] ?? 10;
        }
        
        return min($total, 200);
    }
}

// Web interface
if (isset($_GET['analyze']) && isset($_GET['client_id'])) {
    $analyzer = new CreditAnalyzer();
    $client_id = (int)$_GET['client_id'];
    
    // Get client data
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        echo json_encode(['error' => 'Client not found']);
        exit;
    }
    
    // For demo, use sample report data
    $sample_report = "CIBIL Score: 620\n";
    $sample_report .= "Written Off: 1 account\n";
    $sample_report .= "Settled: 1 account\n";
    $sample_report .= "Late Payment: 2 instances\n";
    $sample_report .= "Credit Utilization: 85%\n";
    
    $result = $analyzer->analyzeReport($client_id, $sample_report);
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
    $stmt = $pdo->prepare("SELECT * FROM credit_analysis_reports WHERE client_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$client_id]);
    $report = $stmt->fetch();
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit;
}

// CLI usage
if (php_sapi_name() === 'cli' && $argc > 1) {
    $analyzer = new CreditAnalyzer();
    $client_id = $argv[1] ?? 1;
    $report_data = $argv[2] ?? "CIBIL Score: 620\nWritten Off: 1 account\nSettled: 1 account";
    
    $result = $analyzer->analyzeReport($client_id, $report_data);
    echo "✅ Analysis complete!\n";
    print_r($result);
    exit;
}
?>
