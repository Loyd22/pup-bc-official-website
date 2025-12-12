<?php
declare(strict_types=1);

/**
 * Email Configuration for Password Reset
 * 
 * IMPORTANT: Replace placeholder values with your actual SMTP credentials
 * 
 * For Gmail:
 * - Host: smtp.gmail.com
 * - Port: 587 (TLS) or 465 (SSL)
 * - Username: Your Gmail address
 * - Password: Use an App Password (not your regular Gmail password)
 *   Generate one at: https://myaccount.google.com/apppasswords
 * 
 * For other providers, check their SMTP settings documentation.
 */

return [
    'smtp_host' => 'smtp.gmail.com',        // Replace with your SMTP host
    'smtp_port' => 587,                     // Replace with your SMTP port (587 for TLS, 465 for SSL)
    'smtp_username' => 'johnloydviray22@gmail.com',  // Replace with your SMTP username
    'smtp_password' => 'fjysshmofhvwhxce',     // Replace with your SMTP app password
    'smtp_encryption' => 'tls',             // 'tls' or 'ssl'
    'from_email' => 'johnloydviray22@gmail.com',
    'from_name' => 'PUPBC Admin (no-reply)',
    'to_email' => 'johnloydviray22@gmail.com',  // Superadmin email (fixed)
];

