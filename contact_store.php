<?php
/**
 * Star51 - Contact Form Storage
 * Processes contact form submissions and sends confirmation emails
 */

// Start session with secure cookie parameters
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

// Load language file
require_once 'inc/inc_star51_lang.php';

// Load database connection
try {
  require_once 'nova/legas/nova_config.php';
} catch (Exception $e) {
  $_SESSION['contact_errors'] = [__front('contact.errors.db_connection')];
  header('Location: contact.php');
  exit();
}

// Load PHPMailer from Nova
require_once 'nova/inc/inc_nova_phpmailer_loader.php';

// Send confirmation email to user (Solo Edition - No Newsletter)
function send_contact_confirmation_email($email, $first_name, $last_name, $message, $phone = '') {
  global $nova_settings;

  try {
    // Get language setting
    $lang = $nova_settings['nova_lang'] ?? 'it';

    // Load email template
    $template_file = __DIR__ . "/nova/mail-templates/contact_confirm_{$lang}.php";
    if (!file_exists($template_file)) {
      $template_file = __DIR__ . '/nova/mail-templates/contact_confirm_it.php'; // Fallback
    }
    include $template_file;

    // Build conditional fields based on language
    if ($lang === 'en') {
      $phone_line = !empty($phone) ? "Phone: $phone\n" : "";
    } else {
      $phone_line = !empty($phone) ? "Telefono: $phone\n" : "";
    }

    // Build site URL and name
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $site_url = "$protocol://$host";
    $site_name = $nova_settings['site_name'] ?? 'Star51';

    // Replace placeholders
    $subject = str_replace('{site_name}', $site_name, $mail_subject);
    $body = str_replace(
      ['{first_name}', '{last_name}', '{email}', '{phone_line}', '{message}', '{site_name}', '{site_url}'],
      [$first_name, $last_name, $email, $phone_line, $message, $site_name, $site_url],
      $mail_body
    );

    // Create configured mailer instance
    $mail = nova_create_mailer($site_name);

    // Set recipients
    $mail->addAddress($email, "$first_name $last_name");

    // Use simple text email to avoid spam filters
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
    return true;

  } catch (Exception $e) {
    error_log("Contact confirmation email failed: {$mail->ErrorInfo}");
    return false;
  }
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: contact.php');
  exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  $_SESSION['contact_errors'] = [__front('contact.errors.generic')];
  header('Location: contact.php');
  exit();
}

try {
  // Clean and validate input data
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $privacy = isset($_POST['privacy']) ? 1 : 0;
  $captcha = strtolower(trim($_POST['captcha'] ?? ''));

  // Start validation checks
  $errors = [];

  if (empty($first_name)) {
    $errors[] = __front('contact.validation.first_name');
  }

  if (empty($last_name)) {
    $errors[] = __front('contact.validation.last_name');
  }

  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = __front('contact.validation.email');
  }

  if (empty($message)) {
    $errors[] = __front('contact.validation.message');
  }

  if ($privacy !== 1) {
    $errors[] = __front('contact.validation.privacy');
  }

  // Check CAPTCHA security
  if (empty($captcha)) {
    $errors[] = __front('contact.validation.captcha');
  } else {
    // Check if captcha session exists and is not expired (30 minutes)
    if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_timestamp'])) {
      $errors[] = __front('contact.validation.captcha_expired');
    } elseif ((time() - $_SESSION['captcha_timestamp']) > 1800) {
      $errors[] = __front('contact.validation.captcha_timeout');
    } elseif ($captcha !== strtolower($_SESSION['captcha_answer'])) {
      $errors[] = __front('contact.validation.captcha_wrong');
    }
  }

  if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['form_data'] = [
      'first_name' => $_POST['first_name'] ?? '',
      'last_name'  => $_POST['last_name'] ?? '',
      'email'      => $_POST['email'] ?? '',
      'phone'      => $_POST['phone'] ?? '',
      'message'    => $_POST['message'] ?? '',
    ];
    header('Location: contact.php');
    exit();
  }

  // Check if database connection is available
  if (!isset($conn) || mysqli_connect_errno()) {
    throw new Exception('Database connection not available: ' . mysqli_connect_error());
  }

  // Prepare SQL query for ns_requests table (Solo Edition)
  $query = "
    INSERT INTO ns_requests (
      first_name,
      last_name,
      email,
      phone,
      request_message,
      request_status,
      is_active,
      request_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
  ";
  $stmt = mysqli_prepare($conn, $query);

  // Set values and execute statement
  $request_status = 'new';
  $is_active = 1;

  mysqli_stmt_bind_param(
    $stmt,
    'ssssssi',
    $first_name,
    $last_name,
    $email,
    $phone,
    $message,
    $request_status,
    $is_active
  );

  if (mysqli_stmt_execute($stmt)) {
    // Send confirmation email to user
    $email_sent = send_contact_confirmation_email($email, $first_name, $last_name, $message, $phone);

    // Build success message with email status
    $success_message = __front('contact.success.message');

    if ($email_sent) {
      $success_message .= ' ' . __front('contact.success.email_sent');
    }

    $_SESSION['contact_success'] = $success_message;

    // Clear form data, captcha session and regenerate CSRF token
    unset($_SESSION['form_data'], $_SESSION['contact_errors'], $_SESSION['captcha_answer'], $_SESSION['captcha_result'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_timestamp']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

  } else {
    throw new Exception(__front('contact.errors.save_failed'));
  }

  mysqli_stmt_close($stmt);
  mysqli_close($conn);

} catch (Exception $e) {
  // Handle any errors that occurred
  $_SESSION['contact_errors'] = [__front('contact.errors.generic')];
  $_SESSION['form_data'] = [
    'first_name' => $_POST['first_name'] ?? '',
    'last_name'  => $_POST['last_name'] ?? '',
    'email'      => $_POST['email'] ?? '',
    'phone'      => $_POST['phone'] ?? '',
    'message'    => $_POST['message'] ?? '',
  ];

  // Log error for debugging
  error_log("Contact form error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

// Redirect back to contact page
header('Location: contact.php');
exit();
