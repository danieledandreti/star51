<?php
// Nova Subcategories Update - Process subcategory update
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

// Get subcategory ID
$subcategory_id = isset($_POST['id_subcategory']) ? intval($_POST['id_subcategory']) : 0;

if (!$subcategory_id) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.invalid_id')];
  header('Location: subcat_list.php');
  exit();
}

// Verify subcategory exists
$query_verify = "
  SELECT id_subcategory
  FROM ns_subcategories
  WHERE id_subcategory = ?
";
$stmt = mysqli_prepare($conn, $query_verify);
mysqli_stmt_bind_param($stmt, 'i', $subcategory_id);
mysqli_stmt_execute($stmt);
$rs_verify = mysqli_stmt_get_result($stmt);

if (!$rs_verify || mysqli_num_rows($rs_verify) === 0) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.not_found')];
  header('Location: subcat_list.php');
  exit();
}

$current_subcategory = mysqli_fetch_assoc($rs_verify);

// Initialize variables
$errors = [];

// Validate required fields
if (empty($_POST['id_category'])) {
  $errors[] = __admin('subcategories.val.category_required');
}
if (empty($_POST['subcategory_name'])) {
  $errors[] = __admin('subcategories.val.name_required');
}

// Sanitize and validate input data (is_active not modified in edit form - only via toggle)
$subcategory_data = [
  'id_category' => intval($_POST['id_category'] ?? 0),
  'subcategory_name' => trim($_POST['subcategory_name'] ?? ''),
  'subcategory_description' => trim($_POST['subcategory_description'] ?? '')
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
  }
}

// Validate subcategory name length
if (strlen($subcategory_data['subcategory_name']) > 255) {
  $errors[] = __admin('subcategories.val.name_max');
}

// Check for duplicate subcategory name within the same category (excluding current subcategory)
if (empty($errors)) {
  $query_check = "
    SELECT id_subcategory
    FROM ns_subcategories
    WHERE subcategory_name = ?
      AND id_category = ?
      AND id_subcategory != ?
    LIMIT 1
  ";
  $stmt = mysqli_prepare($conn, $query_check);
  mysqli_stmt_bind_param($stmt, 'sii', $subcategory_data['subcategory_name'], $subcategory_data['id_category'], $subcategory_id);
  mysqli_stmt_execute($stmt);
  $rs_check = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($rs_check) > 0) {
    $errors[] = __admin('subcategories.val.name_exists_other');
  }
}

// Update database if no errors
if (empty($errors)) {
  try {
    // Prepare the UPDATE query (updated_at handled manually - not automatic)
    $query_update = "
      UPDATE ns_subcategories SET
        id_category = ?,
        subcategory_name = ?,
        subcategory_description = ?,
        updated_at = CURRENT_TIMESTAMP
      WHERE id_subcategory = ?
    ";

    $stmt = mysqli_prepare($conn, $query_update);

    if (!$stmt) {
      throw new Exception(__admin('subcategories.err.query_prepare') . mysqli_error($conn));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'issi',
      $subcategory_data['id_category'],
      $subcategory_data['subcategory_name'],
      $subcategory_data['subcategory_description'],
      $subcategory_id
    );

    // Execute the query
    if (mysqli_stmt_execute($stmt)) {
      $success_message = str_replace('{id}', $subcategory_id, __admin('subcategories.msg.updated'));

      // Log the action
      error_log("Nova: Subcategory updated - ID: $subcategory_id by Admin ID: " . $_SESSION['admin_id']);

      // Redirect to subcategory list with success message
      $_SESSION['subcat_success'] = $success_message;
      header('Location: subcat_list.php');
      exit();

    } else {
      throw new Exception(__admin('subcategories.err.update_failed') . mysqli_stmt_error($stmt));
    }

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log("Nova Subcategories Update Error: " . $e->getMessage());
  }
}

// If we have errors, store them in session and redirect back to edit form
if (!empty($errors)) {
  $_SESSION['subcat_errors'] = $errors;
  $_SESSION['subcat_form_data'] = $_POST; // Preserve form data
  header("Location: subcat_edit.php?id=" . $subcategory_id);
  exit();
}
