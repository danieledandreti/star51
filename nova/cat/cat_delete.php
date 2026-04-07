<?php
// Nova Categories Delete - Simple direct deletion (Star legacy approach)
// Session management and database connection
include '../inc/inc_nova_session.php';

// Determine redirect URL (use referer to maintain pagination)
$redirect_url = nova_safe_redirect('cat_list.php');

// CSRF Token Validation
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['cat_errors'] = [__admin('categories.err.csrf_invalid')];
  header("Location: $redirect_url");
  exit();
}

// Get category ID from URL
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$category_id) {
  $_SESSION['cat_errors'] = [__admin('categories.err.invalid_id')];
  header("Location: $redirect_url");
  exit();
}

// Protect system categories (ID 1 = Extra, ID 2 = Info) from deletion
if ($category_id <= 2) {
  $_SESSION['cat_errors'] = [__admin('categories.err.system_protected')];
  header("Location: $redirect_url");
  exit();
}

// Get category data
$query_cat = "
  SELECT category_name
  FROM ns_categories
  WHERE id_category = ?
";
$stmt = mysqli_prepare($conn, $query_cat);
mysqli_stmt_bind_param($stmt, 'i', $category_id);
mysqli_stmt_execute($stmt);
$rs_cat = mysqli_stmt_get_result($stmt);

if (!$rs_cat || mysqli_num_rows($rs_cat) === 0) {
  $_SESSION['cat_errors'] = [__admin('categories.err.not_found')];
  header("Location: $redirect_url");
  exit();
}

$category = mysqli_fetch_assoc($rs_cat);
$category_name = $category['category_name'];

try {
  // Start transaction
  mysqli_begin_transaction($conn);

  // Get all subcategories of this category
  $query_subcats = "
    SELECT id_subcategory
    FROM ns_subcategories
    WHERE id_category = ?
  ";
  $stmt_get = mysqli_prepare($conn, $query_subcats);
  mysqli_stmt_bind_param($stmt_get, 'i', $category_id);
  mysqli_stmt_execute($stmt_get);
  $rs_subcats = mysqli_stmt_get_result($stmt_get);

  $subcat_ids = [];
  while ($row = mysqli_fetch_assoc($rs_subcats)) {
    $subcat_ids[] = $row['id_subcategory'];
  }

  $moved_articles = 0;

  // Move ALL articles from these subcategories to "Varie" (ID=1)
  if (!empty($subcat_ids)) {
    $placeholders = implode(',', array_fill(0, count($subcat_ids), '?'));
    $query_move_articles = "
      UPDATE ns_articles
      SET id_subcategory = " . SUBCATEGORY_VARIE . "
      WHERE id_subcategory IN ($placeholders)
    ";
    $stmt_articles = mysqli_prepare($conn, $query_move_articles);

    $types = str_repeat('i', count($subcat_ids));
    mysqli_stmt_bind_param($stmt_articles, $types, ...$subcat_ids);

    if (!mysqli_stmt_execute($stmt_articles)) {
      throw new Exception(__admin('categories.err.move_articles'));
    }

    $moved_articles = mysqli_stmt_affected_rows($stmt_articles);
  }

  // Move subcategories to system category (ID = 1 "Extra")
  $query_move_subcats = "
    UPDATE ns_subcategories
    SET id_category = " . CATEGORY_EXTRA . "
    WHERE id_category = ?
  ";
  $stmt_move = mysqli_prepare($conn, $query_move_subcats);
  mysqli_stmt_bind_param($stmt_move, 'i', $category_id);

  if (!mysqli_stmt_execute($stmt_move)) {
    throw new Exception(__admin('categories.err.move_subcats'));
  }

  $moved_subcats = mysqli_stmt_affected_rows($stmt_move);

  // Delete category from database
  $query_delete = "
    DELETE FROM ns_categories
    WHERE id_category = ?
  ";
  $stmt = mysqli_prepare($conn, $query_delete);
  mysqli_stmt_bind_param($stmt, 'i', $category_id);

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception(__admin('categories.err.delete_failed'));
  }

  // Commit transaction
  mysqli_commit($conn);

  // Log the action
  error_log("Nova: Category deleted - ID: $category_id, Name: $category_name, Moved articles: $moved_articles, Moved subcats: $moved_subcats by Admin ID: " . $_SESSION['admin_id']);

  // Success message
  $success_msg = str_replace('{id}', $category_id, __admin('categories.msg.deleted'));
  if ($moved_articles > 0) {
    $success_msg .= ' ' . str_replace('{count}', $moved_articles, __admin('categories.msg.deleted_articles'));
  }
  if ($moved_subcats > 0) {
    $success_msg .= ' ' . str_replace('{count}', $moved_subcats, __admin('categories.msg.deleted_subcats'));
  }
  $_SESSION['cat_success'] = $success_msg;

} catch (Exception $e) {
  // Rollback on error
  mysqli_rollback($conn);
  $_SESSION['cat_errors'] = [$e->getMessage()];
  error_log("Nova Category Delete Error: " . $e->getMessage());
}

// Redirect back to referer (pagination preserved)
header("Location: $redirect_url");
exit();
