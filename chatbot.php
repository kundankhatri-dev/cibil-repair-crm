<?php
// chatbot.php - AI Chatbot for CIBIL Repair

session_start();

// Generate session ID
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = uniqid('chat_');
}

// Database connection
function getDB() {
    return new PDO("mysql:host=localhost;dbname=u929623538_cibil", "u929623538_cibilrepair", "Kundanlaxmi@1995");
}

// Load intent patterns
function getIntents() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM chatbot_intents WHERE is_active = 1");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Match user message to intent
function matchIntent($message, $intents) {
    $message = strtolower(trim($message));
    $best_match = null;
    $best_score = 0;
    
    foreach ($intents as $intent) {
        $patterns = json_decode($intent['intent_patterns'], true);
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            // Check if pattern is in message
            if (strpos($message, $pattern) !== false) {
                $score = strlen($pattern) / strlen($message);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $intent;
                }
            }
            // Word matching
            $words = explode(' ', $pattern);
            $matched = 0;
            foreach ($words as $word) {
                if (strpos($message, $word) !== false) $matched++;
            }
            $word_score = $matched / count($words);
            if ($word_score > $best_score && $word_score > 0.5) {
                $best_score = $word_score;
                $best_match = $intent;
            }
        }
    }
    return $best_match;
}

// Generate response
function getResponse($message, $session_id) {
    $pdo = getDB();
    $intents = getIntents();
    $matched_intent = matchIntent($message, $intents);
    
    // Default response
    $response = "I'm here to help! You can ask about our services, pricing, credit repair process, or schedule a consultation.";
    $intent_name = 'general';
    
    if ($matched_intent) {
        $responses = json_decode($matched_intent['intent_responses'], true);
        $response = $responses[array_rand($responses)];
        $intent_name = $matched_intent['intent_name'];
        
        // Special handling for lead collection
        if ($intent_name === 'lead_collection') {
            handleLeadCollection($message, $session_id);
        }
    }
    
    // Save conversation
    $stmt = $pdo->prepare("INSERT INTO chatbot_conversations (session_id, message, response, intent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session_id, $message, $response, $intent_name]);
    
    return $response;
}

// Handle lead collection
function handleLeadCollection($message, $session_id) {
    $pdo = getDB();
    
    // Check if lead exists
    $stmt = $pdo->prepare("SELECT * FROM chatbot_leads WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $lead = $stmt->fetch();
    
    if (!$lead) {
        // Create new lead
        $stmt = $pdo->prepare("INSERT INTO chatbot_leads (session_id) VALUES (?)");
        $stmt->execute([$session_id]);
    }
}

// Update lead info
function updateLeadInfo($session_id, $field, $value) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE chatbot_leads SET $field = ? WHERE session_id = ?");
    $stmt->execute([$value, $session_id]);
}

// Chatbot UI
function renderChatbot() {
    ?>
    <div id="chatbot-container" style="position:fixed;bottom:20px;right:20px;z-index:1000;width:350px;max-width:90%;">
        <div id="chatbot-toggle" onclick="toggleChatbot()" style="background:#0d9e78;color:white;padding:15px 20px;border-radius:50px;cursor:pointer;display:flex;align-items:center;gap:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <span>💬</span>
            <span>Chat with us</span>
        </div>
        <div id="chatbot-box" style="display:none;background:white;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.2);overflow:hidden;margin-top:10px;height:500px;display:none;flex-direction:column;">
            <div style="background:#0d9e78;color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;">
                <span><strong>CIBIL Assistant</strong></span>
                <span onclick="toggleChatbot()" style="cursor:pointer;">✕</span>
            </div>
            <div id="chatbot-messages" style="flex:1;overflow-y:auto;padding:20px;background:#f8fafc;">
                <div style="text-align:center;color:#666;font-size:12px;margin-bottom:15px;">We usually reply in minutes</div>
                <div id="chat-message" style="background:white;padding:12px 16px;border-radius:12px;margin-bottom:10px;max-width:85%;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    👋 Hello! Welcome to CIBIL Repair. How can I help you today?
                </div>
            </div>
            <div style="padding:15px;border-top:1px solid #e2e8f0;display:flex;gap:10px;background:white;">
                <input type="text" id="chatbot-input" placeholder="Type your message..." style="flex:1;padding:10px 15px;border:1px solid #e2e8f0;border-radius:25px;outline:none;font-size:14px;" onkeypress="if(event.key==='Enter') sendChatMessage()">
                <button onclick="sendChatMessage()" style="background:#0d9e78;color:white;border:none;border-radius:50%;width:45px;height:45px;cursor:pointer;font-size:18px;">➤</button>
            </div>
        </div>
    </div>
    <script>
        function toggleChatbot() {
            const box = document.getElementById('chatbot-box');
            const toggle = document.getElementById('chatbot-toggle');
            if (box.style.display === 'none' || box.style.display === '') {
                box.style.display = 'flex';
                toggle.style.display = 'none';
            } else {
                box.style.display = 'none';
                toggle.style.display = 'flex';
            }
        }
        
        function sendChatMessage() {
            const input = document.getElementById('chatbot-input');
            const message = input.value.trim();
            if (!message) return;
            
            const messagesDiv = document.getElementById('chatbot-messages');
            const userMsg = document.createElement('div');
            userMsg.style.cssText = 'background:#0d9e78;color:white;padding:12px 16px;border-radius:12px;margin-bottom:10px;max-width:85%;margin-left:auto;';
            userMsg.textContent = message;
            messagesDiv.appendChild(userMsg);
            
            input.value = '';
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            // Show typing indicator
            const typing = document.createElement('div');
            typing.style.cssText = 'background:white;padding:12px 16px;border-radius:12px;margin-bottom:10px;max-width:85%;color:#666;';
            typing.textContent = 'Typing...';
            typing.id = 'typing-indicator';
            messagesDiv.appendChild(typing);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            // Send to server
            fetch('chatbot-api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: message})
            })
            .then(res => res.json())
            .then(data => {
                const typingEl = document.getElementById('typing-indicator');
                if (typingEl) typingEl.remove();
                
                const botMsg = document.createElement('div');
                botMsg.style.cssText = 'background:white;padding:12px 16px;border-radius:12px;margin-bottom:10px;max-width:85%;box-shadow:0 1px 3px rgba(0,0,0,0.1);';
                botMsg.textContent = data.response;
                messagesDiv.appendChild(botMsg);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            })
            .catch(err => {
                const typingEl = document.getElementById('typing-indicator');
                if (typingEl) typingEl.remove();
                const botMsg = document.createElement('div');
                botMsg.style.cssText = 'background:white;padding:12px 16px;border-radius:12px;margin-bottom:10px;max-width:85%;box-shadow:0 1px 3px rgba(0,0,0,0.1);';
                botMsg.textContent = 'Sorry, I am having trouble connecting. Please try again later.';
                messagesDiv.appendChild(botMsg);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
        }
    </script>
    <?php
}
?>
