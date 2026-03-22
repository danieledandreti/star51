<?php
// Nova Subcategories Delete - Simple direct deletion with cascading articles
// Session management and database connection
include '../inc/inc_nova_session.php';

// Determine redirect URL (use referer to maintain pagination)
$redirect_url = nova_safe_redirect('subcat_list.php');

// CSRF Token Validation - Protect against Cross-Site Request Forgery via URL
// DELETE operations use GET but still need CSRF protection
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.csrf_invalid')];
  header("Location: $redirect_url");
  exit();
}

// Get subcategory ID from URL
$subcategory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$subcategory_id) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.invalid_id')];
  header("Location: $redirect_url");
  exit();
}

// Protect system subcategories (ID 1 = Varie, ID 2 = News) from deletion
if ($subcategory_id <= 2) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.system_protected')];
  header("Location: $redirect_url");
  exit();
}

// Get subcategory data
$query_subcat = "
  SELECT subcategory_name
  FROM ns_subcategories
  WHERE id_subcategory = ?
";
$stmt = mysqli_prepare($conn, $query_subcat);
mysqli_stmt_bind_param($stmt, 'i', $subcategory_id);
mysqli_stmt_execute($stmt);
$rs_subcat = mysqli_stmt_get_result($stmt);

if (!$rs_subcat || mysqli_num_rows($rs_subcat) === 0) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.not_found')];
  header("Location: $redirect_url");
  exit();
}

$subcategory = mysqli_fetch_assoc($rs_subcat);
$subcategory_name = $subcategory['subcategory_name'];

try {
  // Start transaction for safe operations
  mysqli_begin_transaction($conn);

  // Move articles to system subcategory (ID = 1 "Varie") instead of deleting
  $query_move = "
    UPDATE ns_articles
    SET id_subcategory = " . SUBCATEGORY_VARIE . "
    WHERE id_subcategory = ?
  ";
  $stmt_move = mysqli_prepare($conn, $query_move);
  mysqli_stmt_bind_param($stmt_move, 'i', $subcategory_id);

  if (!mysqli_stmt_execute($stmt_move)) {
    throw new Exception(__admin('subcategories.err.move_articles'));
  }

  $moved_articles = mysqli_stmt_affected_rows($stmt_move);

  // Delete subcategory from database
  $query_delete = "
    DELETE FROM ns_subcategories
    WHERE id_subcategory = ?
  ";
  $stmt = mysqli_prepare($conn, $query_delete);
  mysqli_stmt_bind_param($stmt, 'i', $subcategory_id);

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception(__admin('subcategories.err.delete_failed'));
  }

  // Commit transaction
  mysqli_commit($conn);

  // Log the action
  error_log("Nova: Subcategory deleted - ID: $subcategory_id, Name: $subcategory_name, Moved articles: $moved_articles by Admin ID: " . $_SESSION['admin_id']);

  // Success message
  $success_msg = str_replace('{id}', $subcategory_id, __admin('subcategories.msg.deleted'));
  if ($moved_articles > 0) {
    $success_msg .= ' ' . str_replace('{count}', $moved_articles, __admin('subcategories.msg.deleted_articles'));
  }
  $_SESSION['subcat_success'] = $success_msg;

} catch (Exception $e) {
  // Rollback transaction on error
  mysqli_rollback($conn);
  $_SESSION['subcat_errors'] = [$e->getMessage()];
  error_log("Nova Subcategories Delete Error: " . $e->getMessage());
}

// Redirect back to referer (pagination preserved)
header("Location: $redirect_url");
exit();
