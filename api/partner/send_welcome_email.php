<?php
// ============================================================
// SEND WELCOME EMAIL TO NEW PARTNER
// ============================================================

function sendPartnerWelcomeEmail($email, $name, $password, $partner_id = null) {
    $subject = "🎉 Welcome to CIBIL Repair - Your Partner Account";
    
    // Get partner details from database if partner_id is provided
    if ($partner_id) {
        // You can fetch additional details here
    }
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Welcome to CIBIL Repair</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #0d9e78, #06b6d4); padding: 30px 20px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; }
            .header p { color: rgba(255,255,255,0.9); margin: 5px 0 0; font-size: 16px; }
            .content { padding: 30px; }
            .content h2 { color: #0d9e78; margin-top: 0; }
            .credentials { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0d9e78; margin: 20px 0; }
            .credentials p { margin: 8px 0; font-size: 15px; }
            .credentials strong { color: #0d9e78; }
            .password-box { background: #ffffff; padding: 12px 16px; border-radius: 6px; border: 2px dashed #0d9e78; display: inline-block; font-size: 18px; font-weight: bold; color: #0d9e78; letter-spacing: 1px; margin: 5px 0; }
            .btn { display: inline-block; background: #0d9e78; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 10px 0; }
            .btn:hover { background: #0a7d60; }
            .features { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
            .feature { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
            .feature .icon { font-size: 28px; display: block; margin-bottom: 5px; }
            .feature .label { font-size: 13px; color: #666; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
            .footer a { color: #0d9e78; text-decoration: none; }
            .security-tips { background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0; }
            .security-tips h4 { margin: 0 0 8px; color: #856404; }
            .security-tips ul { margin: 5px 0; padding-left: 20px; }
            .security-tips li { margin: 4px 0; }
            @media (max-width: 480px) {
                .features { grid-template-columns: 1fr; }
                .content { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <!-- Header -->
            <div class='header'>
                <h1>🏢 CIBIL Repair</h1>
                <p>Partner Account Created Successfully</p>
            </div>
            
            <!-- Content -->
            <div class='content'>
                <h2>Welcome {$name}! 🎉</h2>
                <p style='font-size: 16px; color: #333;'>We're excited to have you on board as a CIBIL Repair Partner. Your account has been created successfully.</p>
                
                <!-- Credentials -->
                <div class='credentials'>
                    <h4 style='margin: 0 0 12px; color: #0d9e78;'>🔑 Your Login Credentials</h4>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Password:</strong> <span class='password-box'>{$password}</span></p>
                    <p style='margin-top: 10px; font-size: 13px; color: #666;'>
                        <strong>Partner ID:</strong> " . ($partner_id ?? 'New Partner') . "
                    </p>
                </div>
                
                <!-- Login Button -->
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='https://cibilrepair.in/login.php' class='btn'>🚀 Login to Your Dashboard</a>
                </div>
                
                <!-- Features -->
                <div style='margin: 20px 0;'>
                    <h4 style='color: #0d9e78;'>✨ What You Can Do</h4>
                    <div class='features'>
                        <div class='feature'>
                            <span class='icon'>📊</span>
                            <span class='label'>Track Leads</span>
                        </div>
                        <div class='feature'>
                            <span class='icon'>💰</span>
                            <span class='label'>Earn Commission</span>
                        </div>
                        <div class='feature'>
                            <span class='icon'>🏆</span>
                            <span class='label'>Rise Through Tiers</span>
                        </div>
                        <div class='feature'>
                            <span class='icon'>📈</span>
                            <span class='label'>View Analytics</span>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tips -->
                <div class='security-tips'>
                    <h4>🔒 Security Tips</h4>
                    <ul>
                        <li>Change your password immediately after first login</li>
                        <li>Never share your password with anyone</li>
                        <li>Use a unique password for your account</li>
                        <li>Enable two-factor authentication if available</li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div style='background: #e6f7f2; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px;'>
                        <strong>📞 Need Help?</strong> Contact our support team at 
                        <a href='mailto:support@cibilrepair.in' style='color: #0d9e78;'>support@cibilrepair.in</a>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class='footer'>
                <p>&copy; 2025 CIBIL Repair. All rights reserved.</p>
                <p>
                    <a href='https://cibilrepair.in/privacy-policy'>Privacy Policy</a> | 
                    <a href='https://cibilrepair.in/terms'>Terms of Service</a>
                </p>
                <p style='font-size: 11px; color: #bbb;'>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CIBIL Repair <noreply@cibilrepair.in>\r\n";
    $headers .= "Reply-To: support@cibilrepair.in\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}
?>