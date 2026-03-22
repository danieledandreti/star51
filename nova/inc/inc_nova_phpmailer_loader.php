<?php
/**
 * Nova PHPMailer Loader - Centralized PHPMailer Loading
 *
 * Purpose: Load PHPMailer classes once, use everywhere
 * Location: /nova/inc/inc_nova_phpmailer_loader.php
 *
 * Used by:
 * - Nova Admin (index.php, password-reset-send.php)
 * - Frontend (contact_store.php)
 *
 * Philosophy: Nova is the core system, frontend can change
 */

// ============================================================================
// Load PHPMailer Classes (conditional to prevent "already declared" errors)
// ============================================================================
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================================================
// SMTP Configuration (from settings)
// ============================================================================
// Load configuration values
require_once __DIR__ . '/../conf/nova_config_values.php';

// Make $nova_settings available in this scope (it's defined globally in nova_config_values.php)
global $nova_settings;

if (!defined('SMTP_HOST')) {
    // Read SMTP settings from config
    define('SMTP_HOST', $nova_settings['smtp_host'] ?? '');
    define('SMTP_USER', $nova_settings['smtp_user'] ?? '');
    define('SMTP_PASS', $nova_settings['smtp_pass'] ?? '');
    define('SMTP_PORT', $nova_settings['smtp_port'] ?? 587);  // 587 = STARTTLS (Google recommended)
    define('SMTP_FROM_EMAIL', $nova_settings['smtp_user'] ?? '');
    define('SMTP_FROM_NAME', $nova_settings['site_name'] ?? 'Star51');
}

// ============================================================================
// Helper Function: Create Configured PHPMailer Instance
// ============================================================================
// Simple procedural function - creates ready-to-use mailer
// Usage: $mail = nova_create_mailer('Nova Admin');
function nova_create_mailer($from_name = 'Star51') {
    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;

    // Auto-detect encryption type based on port
    // Port 465: SSL/TLS (implicit encryption from start)
    // Port 587: STARTTLS (explicit encryption after connection)
    // Port 25: No encryption (localhost/development only)
    if (SMTP_PORT == 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL/TLS
    } elseif (SMTP_PORT == 587) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // STARTTLS
    }
    // For port 25 or others: no encryption (SMTPSecure = '')

    $mail->Port = SMTP_PORT;

    // Encoding
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    // Default sender
    $mail->setFrom(SMTP_FROM_EMAIL, $from_name);

    return $mail;
}
?>
