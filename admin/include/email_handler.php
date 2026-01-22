<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

function sendNewsletter($subject, $body, $recipients) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.ethiosocialworker.org'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplay@ethiosocialworker.org'; // Your SMTP username
        $mail->Password   = 'o%-4Y*-Zmpm*P9?x'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Sender
        $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');

        //Recipients
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient);
        }

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendBulkEmail($subject, $body, $recipients, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.ethiosocialworker.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplay@ethiosocialworker.org';
        $mail->Password   = 'o%-4Y*-Zmpm*P9?x';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Sender
        $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');

        //Recipients
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        //Attachments
        if ($attachment && $attachment['error'] == UPLOAD_ERR_OK) {
            $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
        }

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send automated bulk email with enhanced logging
 * 
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param array $recipients Array of email addresses
 * @param string $content_type Content type (for logging)
 * @param int $content_id Content ID (for logging)
 * @return array Result array with success status and counts
 */
function sendAutomatedBulkEmail($subject, $body, $recipients, $content_type = null, $content_id = null) {
    $mail = new PHPMailer(true);
    
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.ethiosocialworker.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplay@ethiosocialworker.org';
        $mail->Password   = 'o%-4Y*-Zmpm*P9?x';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Sender
        $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');

        //Recipients - use BCC for privacy
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient);
        }

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        if ($mail->send()) {
            $sentCount = count($recipients);
            return [
                'success' => true,
                'sent_count' => $sentCount,
                'failed_count' => 0
            ];
        } else {
            $failedCount = count($recipients);
            $errors[] = $mail->ErrorInfo;
            return [
                'success' => false,
                'sent_count' => 0,
                'failed_count' => $failedCount,
                'errors' => $errors
            ];
        }
    } catch (Exception $e) {
        $failedCount = count($recipients);
        $errors[] = $mail->ErrorInfo;
        error_log("Automated bulk email failed. Mailer Error: {$mail->ErrorInfo}");
        return [
            'success' => false,
            'sent_count' => 0,
            'failed_count' => $failedCount,
            'errors' => $errors
        ];
    }
}
