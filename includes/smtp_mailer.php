<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

function sendSMTPMail(
    string $to,
    string $toName,
    string $subject,
    string $htmlBody,
    string $replyTo = '',
    string $replyToName = ''
): bool {

    global $config;

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;

        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp']['port'];

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $config['smtp']['from_email'],
            $config['smtp']['from_name']
        );

        if (!empty($replyTo)) {
            $mail->addReplyTo($replyTo, $replyToName);
        }

        $mail->addAddress($to, $toName);

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            '[' . date('Y-m-d H:i:s') . '] SMTP ERROR: ' .
            $mail->ErrorInfo . PHP_EOL,
            3,
            __DIR__ . '/../logs/mail.log'
        );

        return false;
    }
}