<?php
// Nova Subcategories Store - Process new subcategory creation
// Session management and database connection
include '../inc/inc_nova_session.php';

// CSRF Token Validation - Protect against Cross-Site Request Forgery
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.csrf_invalid')];
  header('Location: subcat_list.php');
  exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: subcat_list.php');
  exit();
}

// Initialize variables
$errors = [];

// Validate required fields
if (empty($_POST['id_category'])) {
  $errors[] = __admin('subcategories.val.category_required');
}
if (empty($_POST['subcategory_name'])) {
  $errors[] = __admin('subcategories.val.name_required');
}

// Sanitize and validate input data
$subcategory_data = [
  'id_category' => intval($_POST['id_category'] ?? 0),
  'subcategory_name' => trim($_POST['subcategory_name'] ?? ''),
  'subcategory_description' => trim($_POST['subcategory_description'] ?? ''),
  'is_active' => 0, // Always insert as inactive - admin activates after verification
  'created_by' => $_SESSION['admin_id']
];

// Validate category exists and is valid
if ($subcategory_data['id_category'] <= 0) {
  $errors[] = __admin('subcategories.val.category_invalid');
} else {
  $query_cat_check = "
    SELECT id_category, category_name, is_active
    FROM ns_categories
    WHERE id_category = ?
  ";
  $stmt = mysqli_prepare($conn, $query_cat_check);
  mysqli_stmt_bind_param($stmt, 'i', $subcategory_data['id_category']);
  mysqli_stmt_execute($stmt);
  $rs_cat_check = mysqli_stmt_get_result($stmt);

  if (!$rs_cat_check || mysqli_num_rows($rs_cat_check) === 0) {
    $errors[] = __admin('subcategories.val.category_not_exists');
  } else {
    $category = mysqli_fetch_assoc($rs_cat_check);
    // Optionally warn if parent category is inactive
    if (!$category['is_active']) {
      $errors[] = __admin('subcategories.val.category_inactive');
    }
  }
}

// Validate subcategory name length
if (strlen($subcategory_data['subcategory_name']) > 255) {
  $errors[] = __admin('subcategories.val.name_max');
}

// Check for duplicate subcategory name within the same category
if (empty($errors)) {
  $query_check = "
    SELECT id_subcategory
    FROM ns_subcategories
    WHERE subcategory_name = ?
      AND id_category = ?
    LIMIT 1
  ";
  $stmt = mysqli_prepare($conn, $query_check);
  mysqli_stmt_bind_param($stmt, 'si', $subcategory_data['subcategory_name'], $subcategory_data['id_category']);
  mysqli_stmt_execute($stmt);
  $rs_check = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($rs_check) > 0) {
    $errors[] = __admin('subcategories.val.name_exists');
  }
}

// Insert into database if no errors
if (empty($errors)) {
  try {
    // Prepare the INSERT query
    $query_insert = "
      INSERT INTO ns_subcategories (
        id_category, subcategory_name, subcategory_description,
        is_active, created_by
      ) VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $query_insert);

    if (!$stmt) {
      throw new Exception(__admin('subcategories.err.query_prepare') . mysqli_error($conn));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'issii',
      $subcategory_data['id_category'],
      $subcategory_data['subcategory_name'],
      $subcategory_data['subcategory_description'],
      $subcategory_data['is_active'],
      $subcategory_data['created_by']
    );

    // Execute the query
    if (mysqli_stmt_execute($stmt)) {
      $new_subcategory_id = mysqli_insert_id($conn);
      $success_message = str_replace('{id}', $new_subcategory_id, __admin('subcategories.msg.created'));

      // Log the action
      error_log("Nova: Subcategory created - ID: $new_subcategory_id by Admin ID: " . $_SESSION['admin_id']);

      // Redirect to subcategory list with success message
      $_SESSION['subcat_success'] = $success_message;
      header('Location: subcat_list.php');
      exit();

    } else {
      throw new Exception(__admin('subcategories.err.insert_failed') . mysqli_stmt_error($stmt));
    }

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log("Nova Subcategories Store Error: " . $e->getMessage());
  }
}

// If we have errors, store them in session and redirect back to create form
if (!empty($errors)) {
  $_SESSION['subcat_errors'] = $errors;
  $_SESSION['subcat_form_data'] = $_POST; // Preserve form data
  header('Location: subcat_create.php');
  exit();
}
