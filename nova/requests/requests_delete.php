<?php
// Nova Requests Delete - Simple direct deletion (no associated files)
// Session management and database connection
include '../inc/inc_nova_session.php';

// Determine redirect URL (use referer to maintain pagination)
$redirect_url = nova_safe_redirect('requests_list.php');

// CSRF Token Validation - Protect against Cross-Site Request Forgery via URL
// DELETE operations use GET but still need CSRF protection
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['requests_errors'] = [__admin('requests.err.csrf_invalid')];
  header("Location: $redirect_url");
  exit();
}

// Get request ID from URL
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$request_id) {
  $_SESSION['requests_errors'] = [__admin('requests.err.invalid_id')];
  header("Location: $redirect_url");
  exit();
}

// Get request data for confirmation message
$query_request = "
  SELECT first_name, last_name, email
  FROM ns_requests
  WHERE id_request = ?
";
$stmt = mysqli_prepare($conn, $query_request);
mysqli_stmt_bind_param($stmt, 'i', $request_id);
mysqli_stmt_execute($stmt);
$rs_request = mysqli_stmt_get_result($stmt);

if (!$rs_request || mysqli_num_rows($rs_request) === 0) {
  $_SESSION['requests_errors'] = [__admin('requests.err.not_found')];
  header("Location: $redirect_url");
  exit();
}

$request = mysqli_fetch_assoc($rs_request);
$request_name = $request['first_name'] . ' ' . $request['last_name'];

try {
  // Delete request from database (no files associated with requests)
  $query_delete = "
    DELETE FROM ns_requests
    WHERE id_request = ?
  ";
  $stmt = mysqli_prepare($conn, $query_delete);
  mysqli_stmt_bind_param($stmt, 'i', $request_id);

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception(__admin('requests.err.delete_failed'));
  }

  // Log the action
  error_log("Nova: Request deleted - ID: $request_id, Name: $request_name, Email: {$request['email']} by Admin ID: " . $_SESSION['admin_id']);

  // Success message
  $_SESSION['requests_success'] = str_replace('{id}', $request_id, __admin('requests.msg.deleted'));

} catch (Exception $e) {
  $_SESSION['requests_errors'] = [$e->getMessage()];
  error_log("Nova Request Delete Error: " . $e->getMessage());
}

// Redirect back to referer (pagination preserved)
header("Location: $redirect_url");
exit();
