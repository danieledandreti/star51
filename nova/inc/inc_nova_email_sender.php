<?php
/**
 * Nova Email Sender - Universal Email System
 *
 * Smart dual-method email sending:
 * 1. PHPMailer (primary) - Professional hosting with SMTP
 * 2. PHP mail() (fallback) - Limited hosting (Altervista, free shared)
 *
 * Auto-detects best available method and uses it automatically.
 * Zero external dependencies (no Brevo, no SendGrid, no API keys).
 *
 * Usage:
 *   nova_send_password_reset_email($email, $first_name, $last_name, $token);
 *
 * November 2025
 */

// ============================================================================
// MAIN FUNCTION: Universal Email Sender
// ============================================================================

/**
 * Send email using best available method
 * Tries PHPMailer first (if SMTP available), falls back to mail()
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $body Email body (plain text)
 * @param string $from_name Sender name (default: Nova Admin)
 * @return bool True if email sent successfully, false otherwise
 */
function nova_send_email($to, $subject, $body, $from_name = 'Nova Admin')
{
  // ========================================================================
  // METHOD 1: Try PHPMailer (localhost, professional hosting)
  // ========================================================================

  // Always try PHPMailer first (fsockopen check disabled - may cause false negatives)
  // The try/catch will handle real connection errors
  try {
    require_once __DIR__ . '/inc_nova_phpmailer_loader.php';

    $mail = nova_create_mailer($from_name);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(false); // Plain text for better deliverability

    if ($mail->send()) {
      error_log("Nova Email: Sent via PHPMailer to $to");
      return true;
    }
  } catch (Exception $e) {
    // PHPMailer failed, log error and try fallback
    error_log("Nova Email: PHPMailer failed - " . $e->getMessage());
    // Fall through to mail() method
  }

  // ========================================================================
  // METHOD 2: Fallback to PHP mail() (Altervista, limited hosting)
  // ========================================================================

  return nova_send_via_mail($to, $subject, $body, $from_name);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send email via native PHP mail() function
 * Optimized headers to reduce spam score
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (plain text)
 * @param string $from_name Sender name
 * @return bool True if mail() succeeded
 */
function nova_send_via_mail($to, $subject, $body, $from_name = 'Nova Admin')
{
  // Determine best FROM address based on environment
  global $is_local, $nova_settings;

  // Load config if not already loaded
  if (!isset($nova_settings)) {
    require_once __DIR__ . '/../conf/nova_config_values.php';
  }

  if (isset($is_local) && $is_local) {
    // Local development
    $from_email = 'noreply@localhost';
  } else {
    // Production: Try smtp_user from config, fallback to auto-detected domain
    if (!empty($nova_settings['smtp_user'])) {
      // Use SMTP email from config
      $from_email = $nova_settings['smtp_user'];
    } else {
      // Fallback: detect current domain automatically (remove www. prefix)
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $from_email = 'noreply@' . str_replace('www.', '', $host);
    }
  }

  // Build optimized headers for deliverability
  $headers = "From: $from_name <$from_email>\r\n";
  $headers .= "Reply-To: $from_email\r\n";
  $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
  $headers .= "X-Priority: 1\r\n"; // High priority (transactional email)
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";

  // Send email via native PHP function
  $result = mail($to, $subject, $body, $headers);

  if ($result) {
    error_log("Nova Email: Sent via mail() to $to");
  } else {
    error_log("Nova Email: Failed to send via mail() to $to");
  }

  return $result;
}

// ============================================================================
// WRAPPER FUNCTIONS: Specific Email Types
// ============================================================================

/**
 * Send password reset email with token link
 *
 * @param string $to Recipient email
 * @param string $first_name Admin first name
 * @param string $last_name Admin last name
 * @param string $token Reset token (64-char hex string)
 * @return bool True if email sent successfully
 */
function nova_send_password_reset_email($to, $first_name, $last_name, $token)
{
  // Load language setting
  global $nova_settings;
  if (!isset($nova_settings)) {
    require_once __DIR__ . '/../conf/nova_config_values.php';
  }
  $lang = $nova_settings['nova_lang'] ?? 'it';

  // Build reset URL (auto-detect current domain)
  $protocol =
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = dirname($_SERVER['REQUEST_URI'] ?? '/');
  $reset_url = "$protocol://$host$path/password-new.php?token=$token";

  // Build site URL and name
  $site_url = "$protocol://$host";
  $site_name = $nova_settings['site_name'] ?? 'Star51';

  // Load email template
  $template_file = __DIR__ . '/../mail-templates/password_reset_' . $lang . '.php';
  if (!file_exists($template_file)) {
    $template_file = __DIR__ . '/../mail-templates/password_reset_it.php'; // Fallback
  }
  include $template_file;

  // Replace placeholders
  $subject = str_replace('{site_name}', $site_name, $mail_subject);
  $body = str_replace(
    ['{first_name}', '{reset_url}', '{site_name}', '{site_url}'],
    [$first_name, $reset_url, $site_name, $site_url],
    $mail_body
  );

  return nova_send_email($to, $subject, $body, $site_name);
}

// ============================================================================
// END OF EMAIL SENDER
// ============================================================================
// Simple procedural PHP - no OOP, no external dependencies
// Works on: localhost, Altervista, professional hosting, VPS
// Auto-detects and uses best available email method

?>
