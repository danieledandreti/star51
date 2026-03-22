<?php
session_start();

// Load database connection
require_once 'legas/nova_config.php';

// Language System (i18n)
require_once 'inc/inc_nova_lang.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: password-reset.php');
  exit();
}

try {
  // Get token and passwords from form
  $token = $_POST['token'] ?? '';
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  // Validate token
  if (empty($token)) {
    $_SESSION['password_errors'] = [__admin('password_reset.token_missing')];
    header('Location: password-reset.php');
    exit();
  }

  // Validate passwords
  if (empty($password) || empty($password_confirm)) {
    $_SESSION['password_errors'] = [__admin('password_reset.all_fields_required')];
    header('Location: password-new.php?token=' . urlencode($token));
    exit();
  }

  if ($password !== $password_confirm) {
    $_SESSION['password_errors'] = [__admin('password_reset.passwords_not_match')];
    header('Location: password-new.php?token=' . urlencode($token));
    exit();
  }

  if (strlen($password) < 8) {
    $_SESSION['password_errors'] = [__admin('password_reset.password_min_length')];
    header('Location: password-new.php?token=' . urlencode($token));
    exit();
  }

  // Verify token exists and is not expired
  $query_token = "
    SELECT id_admin, first_name, last_name, email, reset_expires
    FROM ns_admins
    WHERE reset_token = ?
      AND is_active = 1
  ";
  $stmt = mysqli_prepare($conn, $query_token);
  mysqli_stmt_bind_param($stmt, 's', $token);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($result) === 0) {
    $_SESSION['password_errors'] = [__admin('password_reset.token_invalid_used')];
    header('Location: password-reset.php');
    exit();
  }

  $admin = mysqli_fetch_assoc($result);

  // Check if token is expired
  if (strtotime($admin['reset_expires']) < time()) {
    // Clean expired token
    $query_clean = "
      UPDATE ns_admins
      SET reset_token = NULL, reset_expires = NULL
      WHERE id_admin = ?
    ";
    $stmt = mysqli_prepare($conn, $query_clean);
    mysqli_stmt_bind_param($stmt, 'i', $admin['id_admin']);
    mysqli_stmt_execute($stmt);

    $_SESSION['password_errors'] = [__admin('password_reset.link_expired')];
    header('Location: password-reset.php');
    exit();
  }

  // Hash the new password
  $password_hash = password_hash($password, PASSWORD_DEFAULT);

  // Update password and clear reset token
  $query_update = "
    UPDATE ns_admins
    SET password = ?, reset_token = NULL, reset_expires = NULL
    WHERE id_admin = ?
  ";
  $stmt = mysqli_prepare($conn, $query_update);
  mysqli_stmt_bind_param($stmt, 'si', $password_hash, $admin['id_admin']);

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception(__admin('password_reset.password_update_error'));
  }

  // Password updated successfully
  $_SESSION['login_success'] = __admin('password_reset.password_updated');
  header('Location: index.php');
  exit();

} catch (Exception $e) {
  // Handle any errors that occurred
  $_SESSION['password_errors'] = [__admin('password_reset.system_error') . ': ' . $e->getMessage()];
  error_log('Password update error: ' . $e->getMessage());

  // Redirect back to password form with token if available
  if (!empty($token)) {
    header('Location: password-new.php?token=' . urlencode($token));
  } else {
    header('Location: password-reset.php');
  }
  exit();
}
