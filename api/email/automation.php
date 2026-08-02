<?php
// /api/email/automation.php
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../vendor/autoload.php'; // For PHPMailer
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'send_welcome':
            // Send welcome email to new partner
            $email = $_POST['email'];
            $name = $_POST['name'];
            $response = sendWelcomeEmail($email, $name);
            break;
            
        case 'send_approval':
            // Send approval notification
            $email = $_POST['email'];
            $name = $_POST['name'];
            $response = sendApprovalEmail($email, $name);
            break;
            
        case 'send_lead_notification':
            // Send lead notification to partner
            $partner_email = $_POST['partner_email'];
            $lead_data = $_POST['lead_data'];
            $response = sendLeadNotification($partner_email, $lead_data);
            break;
            
        case 'send_followup_reminder':
            // Send follow-up reminder
            $email = $_POST['email'];
            $followup_data = $_POST['followup_data'];
            $response = sendFollowupReminder($email, $followup_data);
            break;
            
        case 'send_newsletter':
            // Send newsletter to all partners
            $subject = $_POST['subject'];
            $content = $_POST['content'];
            $response = sendNewsletter($subject, $content);
            break;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;

function sendWelcomeEmail($email, $name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('noreply@cibilrepair.in', 'CIBIL Repair');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to CIBIL Repair Partnership!';
        $mail->Body = "
            <h1>Welcome, $name!</h1>
            <p>Thank you for joining CIBIL Repair as a partner.</p>
            <p>Your application has been received and is being reviewed.</p>
            <p>You will receive a confirmation email once approved.</p>
            <br>
            <p>Best regards,<br>CIBIL Repair Team</p>
        ";
        
        $mail->send();
        return ['success' => true, 'message' => 'Welcome email sent'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

function sendApprovalEmail($email, $name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('noreply@cibilrepair.in', 'CIBIL Repair');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Partner Application Approved!';
        $mail->Body = "
            <h1>Congratulations, $name!</h1>
            <p>Your partner application has been <strong>approved</strong>.</p>
            <p>You can now access your partner dashboard:</p>
            <p><a href='https://cibilrepair.in/partner-dashboard/'>https://cibilrepair.in/partner-dashboard/</a></p>
            <p>Your login credentials have been sent separately.</p>
            <br>
            <p>Best regards,<br>CIBIL Repair Team</p>
        ";
        
        $mail->send();
        return ['success' => true, 'message' => 'Approval email sent'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}
?>