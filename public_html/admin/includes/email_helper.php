<?php
declare(strict_types=1);


require_once __DIR__ . '/email_config.php';

/**
 * Send password reset code via email using PHPMailer
 * 
 * @param string $code The 6-digit verification code
 * @return bool True if email sent successfully, false otherwise
 */
function send_password_reset_email(string $code): bool
{
    // Check if PHPMailer is available
    // If not installed, you can install via Composer: composer require phpmailer/phpmailer
    // Or download from: https://github.com/PHPMailer/PHPMailer
    
    $phpmailerPath = __DIR__ . '/../../vendor/autoload.php';
    $phpmailerClassPath = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    
    // Try to load PHPMailer
    $phpmailerLoaded = false;
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        $phpmailerLoaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } elseif (file_exists($phpmailerClassPath)) {
        require_once $phpmailerClassPath;
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
        $phpmailerLoaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    // If PHPMailer is not available, use direct SMTP fallback
    if (!$phpmailerLoaded) {
        return send_password_reset_email_fallback($code);
    }

    try {
        $config = get_email_config();
        
        // Check if SMTP credentials are configured (not placeholders)
        if ($config['smtp_username'] === 'your-email@gmail.com' || 
            $config['smtp_password'] === 'your-app-password') {
            error_log("Password Reset Email Error: SMTP credentials not configured in email_config.php");
            return false;
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($config['to_email']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'PUPBC Admin Password Reset Code';
        $mail->Body = "<p>Your PUPBC Admin password reset code is: <strong>{$code}</strong></p>";
        $mail->Body .= "<p>This code will expire in 10 minutes. Do not share this code with anyone.</p>";
        $mail->AltBody = "Your PUPBC Admin password reset code is: {$code}\n\nThis code will expire in 10 minutes. Do not share this code with anyone.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password Reset Email Error: " . $e->getMessage());
        // Fallback to basic mail() if PHPMailer fails
        return send_password_reset_email_fallback($code);
    }
}

/**
 * Fallback email function using direct SMTP connection if PHPMailer is not available
 * 
 * @param string $code The 6-digit verification code
 * @return bool True if email sent successfully, false otherwise
 */
function send_password_reset_email_fallback(string $code): bool
{
    $config = get_email_config();
    
    // Check if SMTP credentials are configured (not placeholders)
    if ($config['smtp_username'] === 'your-email@gmail.com' || 
        $config['smtp_password'] === 'your-app-password') {
        error_log("Password Reset Email Error: SMTP credentials not configured in email_config.php");
        return false;
    }
    
    try {
        // Use direct SMTP connection
        return send_smtp_email($config, $code);
    } catch (Throwable $e) {
        error_log("Password Reset Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using direct SMTP connection
 * 
 * @param array $config Email configuration
 * @param string $code The 6-digit verification code
 * @return bool True if email sent successfully, false otherwise
 */
function send_smtp_email(array $config, string $code): bool
{
    $host = $config['smtp_host'];
    $port = $config['smtp_port'];
    $username = $config['smtp_username'];
    $password = $config['smtp_password'];
    $encryption = $config['smtp_encryption'];
    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'];
    $toEmail = $config['to_email'];
    
    // Use SSL/TLS wrapper
    $hostname = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
    
    // Connect to SMTP server
    $smtp = @fsockopen($hostname, $port, $errno, $errstr, 30);
    if (!$smtp) {
        throw new Exception("Failed to connect to SMTP server: {$errstr} ({$errno})");
    }
    
    // Read server greeting
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '220') {
        fclose($smtp);
        throw new Exception("SMTP server error: {$response}");
    }
    
    // Send EHLO
    fputs($smtp, "EHLO {$host}\r\n");
    $response = fgets($smtp, 515);
    
    // Start TLS if needed
    if ($encryption === 'tls' && strpos($response, 'STARTTLS') !== false) {
        fputs($smtp, "STARTTLS\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($smtp);
            throw new Exception("STARTTLS failed: {$response}");
        }
        stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($smtp, "EHLO {$host}\r\n");
        fgets($smtp, 515);
    }
    
    // Authenticate
    fputs($smtp, "AUTH LOGIN\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '334') {
        fclose($smtp);
        throw new Exception("AUTH LOGIN failed: {$response}");
    }
    
    fputs($smtp, base64_encode($username) . "\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '334') {
        fclose($smtp);
        throw new Exception("Username authentication failed");
    }
    
    fputs($smtp, base64_encode($password) . "\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '235') {
        fclose($smtp);
        throw new Exception("Password authentication failed");
    }
    
    // Set sender
    fputs($smtp, "MAIL FROM: <{$fromEmail}>\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($smtp);
        throw new Exception("MAIL FROM failed: {$response}");
    }
    
    // Set recipient
    fputs($smtp, "RCPT TO: <{$toEmail}>\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($smtp);
        throw new Exception("RCPT TO failed: {$response}");
    }
    
    // Send data
    fputs($smtp, "DATA\r\n");
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '354') {
        fclose($smtp);
        throw new Exception("DATA command failed: {$response}");
    }
    
    // Email headers and body
    $subject = 'PUPBC Admin Password Reset Code';
    $message = "Your PUPBC Admin password reset code is: {$code}\n\n";
    $message .= "This code will expire in 10 minutes. Do not share this code with anyone.";
    
    $emailData = "From: {$fromName} <{$fromEmail}>\r\n";
    $emailData .= "To: <{$toEmail}>\r\n";
    $emailData .= "Subject: {$subject}\r\n";
    $emailData .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $emailData .= "\r\n";
    $emailData .= $message;
    $emailData .= "\r\n.\r\n";
    
    fputs($smtp, $emailData);
    $response = fgets($smtp, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($smtp);
        throw new Exception("Email sending failed: {$response}");
    }
    
    // Quit
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return true;
}

/**
 * Get email configuration
 * 
 * @return array Email configuration array
 */
function get_email_config(): array
{
    return require __DIR__ . '/email_config.php';
}

