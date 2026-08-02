<?php
// lead-scoring.php - Automatic lead scoring system

function calculateLeadScore($lead) {
    $score = 0;
    
    // Score based on source
    $sources = [
        'referral' => 30,
        'google_ads' => 20,
        'website' => 15,
        'facebook' => 10,
        'instagram' => 12,
        'call' => 25,
        'email' => 18,
        'other' => 10
    ];
    $score += $sources[$lead['source']] ?? 10;
    
    // Score based on service interest
    $services = [
        'CIBIL Repair' => 35,
        'Written Off' => 30,
        'Settled' => 25,
        'Profile Correction' => 20,
        'Suit Filed' => 40
    ];
    $score += $services[$lead['service']] ?? 15;
    
    // Score based on lead age (newer = higher)
    $days_old = (time() - strtotime($lead['created_at'])) / 86400;
    if ($days_old < 1) $score += 25;
    elseif ($days_old < 3) $score += 18;
    elseif ($days_old < 7) $score += 10;
    elseif ($days_old < 14) $score += 5;
    else $score += 2;
    
    // Bonus for having phone number and email
    if (!empty($lead['phone'])) $score += 5;
    if (!empty($lead['email'])) $score += 5;
    
    // Priority bonus
    if (isset($lead['priority']) && $lead['priority'] === 'high') $score += 10;
    if (isset($lead['priority']) && $lead['priority'] === 'urgent') $score += 20;
    
    return min($score, 100);
}

function updateAllLeadScores() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Add score column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS lead_score INT DEFAULT 0");
        } catch (Exception $e) {
            // Column might already exist
        }
        
        $stmt = $pdo->query("SELECT * FROM leads WHERE status != 'converted' AND status != 'lost'");
        $leads = $stmt->fetchAll();
        
        $updated = 0;
        foreach ($leads as $lead) {
            $score = calculateLeadScore($lead);
            $stmt = $pdo->prepare("UPDATE leads SET lead_score = ? WHERE id = ?");
            $stmt->execute([$score, $lead['id']]);
            $updated++;
            echo "✅ Lead #{$lead['id']} - {$lead['name']}: Score = $score\n";
        }
        
        echo "\n📊 Updated $updated leads with scores\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    echo "🎯 Lead Scoring System\n";
    echo "═══════════════════════════════════════\n\n";
    updateAllLeadScores();
}

// Web execution
if (php_sapi_name() !== 'cli' && isset($_GET['run'])) {
    header('Content-Type: application/json');
    ob_start();
    updateAllLeadScores();
    $output = ob_get_clean();
    echo json_encode(['success' => true, 'output' => $output]);
}
?>
