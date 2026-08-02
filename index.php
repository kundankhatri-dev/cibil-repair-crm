<?php
// index.php - Main entry point
require_once 'chatbot.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIBIL Repair - Credit Score Improvement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; text-align: center; }
        h1 { color: #0d9e78; font-size: 48px; margin-bottom: 20px; }
        p { font-size: 18px; color: #555; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 15px 30px; background: #0d9e78; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; cursor: pointer; border: none; }
        .btn:hover { background: #0a7d60; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 40px; }
        .feature { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .feature i { font-size: 40px; color: #0d9e78; margin-bottom: 15px; }
        .chat-toggle-btn { 
            position: fixed; bottom: 30px; right: 30px; 
            background: #0d9e78; color: white; 
            border: none; border-radius: 50px; 
            padding: 15px 25px; 
            font-size: 16px; 
            cursor: pointer; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 999;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chat-toggle-btn:hover { background: #0a7d60; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 CIBIL Repair</h1>
        <p>India's most trusted credit score repair consultancy. Fix your CIBIL score legally and get the loans you deserve.</p>
        <button class="btn" onclick="document.getElementById('chatbot-box').style.display='flex'">💬 Chat with us</button>
        
        <div class="features">
            <div class="feature"><i class="fas fa-shield-alt"></i><h3>100% Legal</h3><p>RBI compliant process</p></div>
            <div class="feature"><i class="fas fa-rocket"></i><h3>Fast Results</h3><p>30-90 days resolution</p></div>
            <div class="feature"><i class="fas fa-check-circle"></i><h3>98% Success</h3><p>Proven track record</p></div>
        </div>
    </div>
    
    <?php renderChatbot(); ?>
    
    <script>
        // Show chat toggle button
        setTimeout(function() {
            const toggle = document.getElementById('chatbot-toggle');
            if (toggle) {
                toggle.style.display = 'flex';
                toggle.style.position = 'fixed';
                toggle.style.bottom = '30px';
                toggle.style.right = '30px';
                toggle.style.zIndex = '999';
                toggle.style.background = '#0d9e78';
                toggle.style.color = 'white';
                toggle.style.border = 'none';
                toggle.style.borderRadius = '50px';
                toggle.style.padding = '15px 25px';
                toggle.style.cursor = 'pointer';
                toggle.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
            }
        }, 1000);
    </script>
</body>
</html>
