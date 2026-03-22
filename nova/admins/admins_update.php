<?php
// Nova Admin Update - Process profile update (Solo Edition)
include '../inc/inc_nova_session.php';

// CSRF Token Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['admin_errors'] = [__admin('admins.err.csrf_invalid')];
  header('Location: admins_list.php');
  exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: admins_list.php');
  exit();
}

// Always self-edit in Solo Edition
$admin_id = $_SESSION['admin_id'];

// Initialize errors
$errors = [];

// Validate required fields
$required_fields = [
  'first_name' => __admin('admins.fields.first_name'),
  'last_name' => __admin('admins.fields.last_name'),
  'email' => __admin('admins.fields.email'),
];

foreach ($required_fields as $field => $label) {
  if (empty($_POST[$field])) {
    $errors[] = str_replace('{field}', $label, __admin('admins.val.required'));
  }
}

// Validate current password (required for any change)
if (empty($_POST['current_password'])) {
  $errors[] = __admin('admins.val.current_password_required');
}

// Sanitize input data
$admin_data = [
  'first_name' => trim($_POST['first_name'] ?? ''),
  'last_name' => trim($_POST['last_name'] ?? ''),
  'email' => trim($_POST['email'] ?? ''),
  'username' => trim($_POST['username'] ?? ''),
];

// Handle optional new password
$change_password = !empty($_POST['password']);
if ($change_password) {
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  if (strlen($password) < 8) {
    $errors[] = __admin('admins.val.password_min');
  } elseif ($password !== $password_confirm) {
    $errors[] = __admin('admins.val.password_mismatch');
  } else {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if (!$password_hash) {
      $errors[] = __admin('admins.err.password_hash');
    } else {
      $admin_data['password'] = $password_hash;
    }
  }
}

// Validate field lengths
if (strlen($admin_data['first_name']) > 100) {
  $errors[] = __admin('admins.val.first_name_max');
}
if (strlen($admin_data['last_name']) > 100) {
  $errors[] = __admin('admins.val.last_name_max');
}

// Validate email format
if (!empty($admin_data['email']) && !filter_var($admin_data['email'], FILTER_VALIDATE_EMAIL)) {
  $errors[] = __admin('admins.val.email_format');
}
if (strlen($admin_data['email']) > 100) {
  $errors[] = __admin('admins.val.email_max');
}

// Validate username format (if unlocked and changed)
if (!empty($admin_data['username'])) {
  if (strlen($admin_data['username']) < 3 || strlen($admin_data['username']) > 50) {
    $errors[] = __admin('admins.val.username_length');
  } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $admin_data['username'])) {
    $errors[] = __admin('admins.val.username_format');
  }
}

// Verify current password against DB
if (empty($errors)) {
  $query_password = '
    SELECT password
    FROM ns_admins
    WHERE id_admin = ?
  ';
  $stmt = mysqli_prepare($conn, $query_password);
  mysqli_stmt_bind_param($stmt, 'i', $admin_id);
  mysqli_stmt_execute($stmt);
  $rs_password = mysqli_stmt_get_result($stmt);

  if (!$rs_password || mysqli_num_rows($rs_password) === 0) {
    $errors[] = __admin('admins.err.not_found');
  } else {
    $admin_db = mysqli_fetch_assoc($rs_password);
    if (!password_verify($_POST['current_password'], $admin_db['password'])) {
      $errors[] = __admin('admins.val.current_password_wrong');
    }
  }
}

// Check for duplicate email
if (empty($errors)) {
  $query_check_email = '
    SELECT id_admin
    FROM ns_admins
    WHERE email = ?
      AND id_admin != ?
    LIMIT 1
  ';
  $stmt = mysqli_prepare($conn, $query_check_email);
  mysqli_stmt_bind_param($stmt, 'si', $admin_data['email'], $admin_id);
  mysqli_stmt_execute($stmt);
  $result_check = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($result_check) > 0) {
    $errors[] = __admin('admins.val.email_exists_other');
  }
}

// Update database if no errors
if (empty($errors)) {
  try {
    if ($change_password) {
      $query_update = '
        UPDATE ns_admins
        SET first_name = ?,
            last_name = ?,
            email = ?,
            username = ?,
            password = ?,
            updated_at = NOW()
        WHERE id_admin = ?
      ';
      $stmt = mysqli_prepare($conn, $query_update);
      mysqli_stmt_bind_param(
        $stmt,
        'sssssi',
        $admin_data['first_name'],
        $admin_data['last_name'],
        $admin_data['email'],
        $admin_data['username'],
        $admin_data['password'],
        $admin_id
      );
    } else {
      $query_update = '
        UPDATE ns_admins
        SET first_name = ?,
            last_name = ?,
            email = ?,
            username = ?,
            updated_at = NOW()
        WHERE id_admin = ?
      ';
      $stmt = mysqli_prepare($conn, $query_update);
      mysqli_stmt_bind_param(
        $stmt,
        'ssssi',
        $admin_data['first_name'],
        $admin_data['last_name'],
        $admin_data['email'],
        $admin_data['username'],
        $admin_id
      );
    }

    if (!$stmt) {
      throw new Exception(__admin('admins.err.query_prepare') . ': ' . mysqli_error($conn));
    }

    if (mysqli_stmt_execute($stmt)) {
      // Update session variables with new data
      $_SESSION['admin_first_name'] = $admin_data['first_name'];
      $_SESSION['admin_last_name'] = $admin_data['last_name'];
      $_SESSION['admin_username'] = $admin_data['username'];

      $admin_display_name = htmlspecialchars($admin_data['first_name']) . ' ' . htmlspecialchars($admin_data['last_name']);
      $success_message = str_replace('{name}', $admin_display_name, __admin('admins.msg.updated'));

      // Log the action
      $password_changed = $change_password ? ' (password changed)' : '';
      error_log('Nova: Admin profile updated - ID: ' . $admin_id . $password_changed);

      // Redirect to admin hub with success message
      $_SESSION['admin_success'] = $success_message;
      header('Location: admins_list.php');
      exit();
    } else {
      throw new Exception(__admin('admins.err.update_failed') . ': ' . mysqli_stmt_error($stmt));
    }
  } catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log('Nova Admin Update Error: ' . $e->getMessage());
  }
}

// Redirect back to form with errors
$_SESSION['admin_errors'] = $errors;
$form_data = $_POST;
unset($form_data['password'], $form_data['password_confirm'], $form_data['current_password']);
$_SESSION['admin_form_data'] = $form_data;
header('Location: admins_edit.php');
exit();
