<?php
// Nova Categories Update - Process category update
// Session management and database connection
include '../inc/inc_nova_session.php';

// CSRF Token Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['cat_errors'] = [__admin('categories.err.csrf_invalid')];
  header('Location: cat_list.php');
  exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cat_list.php');
  exit();
}

// Get category ID
$category_id = isset($_POST['id_category']) ? intval($_POST['id_category']) : 0;

if (!$category_id) {
  $_SESSION['cat_errors'] = [__admin('categories.err.invalid_id')];
  header('Location: cat_list.php');
  exit();
}

// Verify category exists
$query_verify = "
  SELECT id_category
  FROM ns_categories
  WHERE id_category = ?
";
$stmt = mysqli_prepare($conn, $query_verify);
mysqli_stmt_bind_param($stmt, 'i', $category_id);
mysqli_stmt_execute($stmt);
$rs_verify = mysqli_stmt_get_result($stmt);

if (!$rs_verify || mysqli_num_rows($rs_verify) === 0) {
  $_SESSION['cat_errors'] = [__admin('categories.err.not_found')];
  header('Location: cat_list.php');
  exit();
}

$current_category = mysqli_fetch_assoc($rs_verify);

// Initialize variables
$errors = [];

// Validate required fields
if (empty($_POST['category_name'])) {
  $errors[] = __admin('categories.val.name_required');
}

// Sanitize and validate input data
$category_data = [
  'category_name' => trim($_POST['category_name'] ?? ''),
  'category_description' => trim($_POST['category_description'] ?? '')
];

// Validate category name length
if (strlen($category_data['category_name']) > 255) {
  $errors[] = __admin('categories.val.name_max');
}

// Check for duplicate category name (excluding current category)
if (empty($errors)) {
  $query_check = "
    SELECT id_category
    FROM ns_categories
    WHERE category_name = ?
      AND id_category != ?
    LIMIT 1
  ";
  $stmt = mysqli_prepare($conn, $query_check);
  mysqli_stmt_bind_param($stmt, 'si', $category_data['category_name'], $category_id);
  mysqli_stmt_execute($stmt);
  $rs_check = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($rs_check) > 0) {
    $errors[] = __admin('categories.val.name_exists');
  }
}

// Update database if no errors
if (empty($errors)) {
  try {
    $query_update = "
      UPDATE ns_categories
      SET
        category_name = ?,
        category_description = ?,
        updated_at = CURRENT_TIMESTAMP
      WHERE id_category = ?
    ";
    $stmt = mysqli_prepare($conn, $query_update);

    if (!$stmt) {
      throw new Exception(__admin('categories.err.query_prepare') . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'ssi',
      $category_data['category_name'],
      $category_data['category_description'],
      $category_id
    );

    if (mysqli_stmt_execute($stmt)) {
      $success_message = str_replace('{id}', $category_id, __admin('categories.msg.updated'));

      error_log("Nova: Category updated - ID: $category_id by Admin ID: " . $_SESSION['admin_id']);

      $_SESSION['cat_success'] = $success_message;
      header('Location: cat_list.php');
      exit();

    } else {
      throw new Exception(__admin('categories.err.update_failed') . mysqli_stmt_error($stmt));
    }

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log("Nova Categories Update Error: " . $e->getMessage());
  }
}

// If we have errors, store them in session and redirect back to edit form
if (!empty($errors)) {
  $_SESSION['cat_errors'] = $errors;
  $_SESSION['cat_form_data'] = $_POST;
  header("Location: cat_edit.php?id=" . $category_id);
  exit();
}
