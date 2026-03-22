<?php
session_start();

// Load database connection and email sender
require_once 'legas/nova_config.php';
require_once 'inc/inc_nova_email_sender.php';

// Language System (i18n)
require_once 'inc/inc_nova_lang.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: password-reset.php');
  exit();
}

// Check rate limiting attempts to determine if CAPTCHA is required
require_once 'inc/inc_nova_rate_limit_check.php';

try {
  // Clean and validate email
  $email = trim($_POST['email'] ?? '');

  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_errors'] = [__admin('password_reset.email_invalid')];
    $_SESSION['form_data'] = $_POST;
    header('Location: password-reset.php');
    exit();
  }

  // CAPTCHA Verification (if >= 3 attempts from this IP)
  if ($rate_limit_attempts >= 3 && !$rate_limit_locked) {
    $captcha_answer_user = strtolower(trim($_POST['captcha_answer'] ?? ''));
    $captcha_answer_correct = strtolower($_SESSION['captcha_answer'] ?? '');
    $captcha_timestamp = $_SESSION['captcha_timestamp'] ?? 0;
    $current_time = time();

    // Check if CAPTCHA exists
    if (empty($captcha_answer_correct)) {
      $_SESSION['reset_errors'] = [__admin('password_reset.captcha_error')];
      $_SESSION['form_data'] = $_POST;
      header('Location: password-reset.php');
      exit();
    }

    // Check if CAPTCHA is expired (30 minutes)
    if (($current_time - $captcha_timestamp) > 1800) {
      $_SESSION['reset_errors'] = [__admin('password_reset.captcha_expired')];
      $_SESSION['form_data'] = $_POST;
      // Clear expired CAPTCHA
      unset($_SESSION['captcha_answer'], $_SESSION['captcha_timestamp'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_result']);
      header('Location: password-reset.php');
      exit();
    }

    // Verify CAPTCHA answer
    if ($captcha_answer_user !== $captcha_answer_correct) {
      $_SESSION['reset_errors'] = [__admin('password_reset.captcha_wrong')];
      $_SESSION['form_data'] = $_POST;

      // Increment rate limiting attempts for wrong CAPTCHA
      $rate_limit_username = '';
      require_once 'inc/inc_nova_rate_limit_increment.php';

      header('Location: password-reset.php');
      exit();
    }

    // CAPTCHA verified successfully - clear session data
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_timestamp'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_result']);
  }

  // Increment rate limiting attempts for password reset request
  $rate_limit_username = '';
  require_once 'inc/inc_nova_rate_limit_increment.php';

  // Check if admin exists with this email
  $query_admin = "
    SELECT id_admin, first_name, last_name, email
    FROM ns_admins
    WHERE email = ?
      AND is_active = 1
  ";
  $stmt = mysqli_prepare($conn, $query_admin);
  mysqli_stmt_bind_param($stmt, 's', $email);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($result) === 0) {
    // Security: Don't reveal if email exists or not
    $_SESSION['reset_success'] = __admin('password_reset.reset_email_exists');
    header('Location: password-reset.php');
    exit();
  }

  $admin = mysqli_fetch_assoc($result);

  // Check if valid token already exists (prevent double-send)
  $query_existing_token = "
    SELECT reset_token, reset_expires
    FROM ns_admins
    WHERE id_admin = ?
      AND reset_token IS NOT NULL
      AND reset_expires > NOW()
  ";
  $stmt_token = mysqli_prepare($conn, $query_existing_token);
  mysqli_stmt_bind_param($stmt_token, 'i', $admin['id_admin']);
  mysqli_stmt_execute($stmt_token);
  $result_token = mysqli_stmt_get_result($stmt_token);

  if (mysqli_num_rows($result_token) > 0) {
    // Valid token already exists - use existing one, don't generate new
    $existing_token_data = mysqli_fetch_assoc($result_token);
    $reset_token = $existing_token_data['reset_token'];
    $reset_sent = true;

    // Set success message (token already sent previously)
    $_SESSION['reset_success'] = __admin('password_reset.reset_email_already_sent');
  } else {
    // No valid token exists - generate new one
    $reset_token = bin2hex(random_bytes(32));
    $reset_expires = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

    // Save token in database
    $query_save_token = "
      UPDATE ns_admins
      SET reset_token = ?, reset_expires = ?
      WHERE id_admin = ?
    ";
    $stmt = mysqli_prepare($conn, $query_save_token);
    mysqli_stmt_bind_param($stmt, 'ssi', $reset_token, $reset_expires, $admin['id_admin']);

    if (!mysqli_stmt_execute($stmt)) {
      throw new Exception(__admin('password_reset.password_update_error'));
    }

    // Send reset email via universal sender
    $reset_sent = nova_send_password_reset_email($admin['email'], $admin['first_name'], $admin['last_name'], $reset_token);

    if ($reset_sent) {
      $_SESSION['reset_success'] = __admin('password_reset.reset_email_sent');
    } else {
      $_SESSION['reset_errors'] = [__admin('password_reset.reset_email_error')];
    }
  }

} catch (Exception $e) {
  // Handle any errors that occurred
  $_SESSION['reset_errors'] = [__admin('password_reset.system_error') . ': ' . $e->getMessage()];
  $_SESSION['form_data'] = $_POST;
  error_log('Password reset error: ' . $e->getMessage());
}

header('Location: password-reset.php');
exit();
