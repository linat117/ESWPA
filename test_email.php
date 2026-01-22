<?php
/**
 * Test Email Sending
 * Use this to test if email sending works
 */

require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Test email configuration
$testEmail = $_GET['email'] ?? 'test@example.com';

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->Debugoutput = function($str, $level) {
        echo "Debug level $level: $str<br>";
    };
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'mail.ethiosocialworker.org';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreplay@ethiosocialworker.org';
    $mail->Password   = 'o%-4Y*-Zmpm*P9?x';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    
    // Additional SMTP options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Sender
    $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');
    
    // Recipient
    $mail->addAddress($testEmail);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Ethio Social Works';
    $mail->Body    = '<h1>Test Email</h1><p>This is a test email to verify email sending functionality.</p>';
    $mail->AltBody = 'This is a test email to verify email sending functionality.';
    
    echo "<h2>Attempting to send email to: {$testEmail}</h2>";
    echo "<hr>";
    
    $mail->send();
    
    echo "<h3 style='color: green;'>SUCCESS: Email sent successfully!</h3>";
    echo "<p>Check your inbox (and spam folder) for the test email.</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>ERROR: Email could not be sent.</h3>";
    echo "<p><strong>Error Info:</strong> {$mail->ErrorInfo}</p>";
    echo "<p><strong>Exception:</strong> {$e->getMessage()}</p>";
    echo "<hr>";
    echo "<h4>Debugging Steps:</h4>";
    echo "<ul>";
    echo "<li>Check SMTP server: mail.ethiosocialworker.org</li>";
    echo "<li>Check SMTP port: 465</li>";
    echo "<li>Check username: noreplay@ethiosocialworker.org</li>";
    echo "<li>Verify password is correct</li>";
    echo "<li>Check if server allows SMTP connections from this IP</li>";
    echo "<li>Check firewall settings</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='test_email.php?email={$testEmail}'>Test Again</a> | <a href='index.php'>Back to Home</a></p>";
?>

