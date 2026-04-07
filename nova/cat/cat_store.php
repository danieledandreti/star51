<?php
// Nova Categories Store - Process new category creation
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

// Initialize variables
$errors = [];

// Validate required fields
if (empty($_POST['category_name'])) {
  $errors[] = __admin('categories.val.name_required');
}

// Sanitize and validate input data
$category_data = [
  'category_name' => trim($_POST['category_name'] ?? ''),
  'category_description' => trim($_POST['category_description'] ?? ''),
  'is_active' => 0,
  'created_by' => $_SESSION['admin_id']
];

// Validate category name length
if (strlen($category_data['category_name']) > 255) {
  $errors[] = __admin('categories.val.name_max');
}

// Check for duplicate category name
if (empty($errors)) {
  $query_check = "
    SELECT id_category
    FROM ns_categories
    WHERE category_name = ?
    LIMIT 1
  ";
  $stmt = mysqli_prepare($conn, $query_check);
  mysqli_stmt_bind_param($stmt, 's', $category_data['category_name']);
  mysqli_stmt_execute($stmt);
  $rs_check = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($rs_check) > 0) {
    $errors[] = __admin('categories.val.name_exists');
  }
}

// Insert into database if no errors
if (empty($errors)) {
  try {
    $query_insert = "
      INSERT INTO ns_categories (
        category_name,
        category_description,
        is_active,
        created_by
      ) VALUES (?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($conn, $query_insert);

    if (!$stmt) {
      throw new Exception(__admin('categories.err.query_prepare') . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'ssii',
      $category_data['category_name'],
      $category_data['category_description'],
      $category_data['is_active'],
      $category_data['created_by']
    );

    if (mysqli_stmt_execute($stmt)) {
      $new_category_id = mysqli_insert_id($conn);
      $success_message = str_replace('{id}', $new_category_id, __admin('categories.msg.created'));

      error_log("Nova: Category created - ID: $new_category_id by Admin ID: " . $_SESSION['admin_id']);

      $_SESSION['cat_success'] = $success_message;
      header('Location: cat_list.php');
      exit();

    } else {
      throw new Exception(__admin('categories.err.insert_failed') . mysqli_stmt_error($stmt));
    }

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log("Nova Categories Store Error: " . $e->getMessage());
  }
}

// If we have errors, store them in session and redirect back to form
if (!empty($errors)) {
  $_SESSION['cat_errors'] = $errors;
  $_SESSION['cat_form_data'] = $_POST;
  header('Location: cat_create.php');
  exit();
}
