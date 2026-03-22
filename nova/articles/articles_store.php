<?php
// Nova Articles Store - Process new article creation
// Session management and database connection (includes db config + constants)
include '../inc/inc_nova_session.php';

// CSRF Token Validation - Protect against Cross-Site Request Forgery
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['articles_errors'] = [__admin('articles.err.csrf_invalid')];
  header('Location: articles_list.php');
  exit();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: articles_list.php');
  exit();
}

// Initialize variables
$errors = [];
$uploaded_image_1 = null;
$uploaded_image_2 = null;

// Sanitize input data
$article_title = trim($_POST['article_title'] ?? '');
$id_subcategory = intval($_POST['id_subcategory'] ?? 0);
$article_content = trim($_POST['article_content'] ?? '');
$article_summary = trim($_POST['article_summary'] ?? '');
$item_collection = trim($_POST['item_collection'] ?? '');
$item_year = !empty($_POST['item_year']) ? intval($_POST['item_year']) : null;
$youtube_video = trim($_POST['youtube_video'] ?? '');
$publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
$is_active = 0;
$created_by = $_SESSION['admin_id'];

// ========================================
// PHASE 1: CHECK REQUIRED FIELDS
// ========================================
if (empty($article_title)) {
  $errors[] = __admin('articles.val.title_required');
}

if ($id_subcategory === 0) {
  $errors[] = __admin('articles.val.subcategory_required');
}

// If required fields missing, stop here and redirect
if (!empty($errors)) {
  $_SESSION['articles_errors'] = $errors;
  $_SESSION['articles_form_data'] = $_POST;
  header('Location: articles_create.php');
  exit();
}

// ========================================
// PHASE 2: VALIDATE TEXT FIELDS
// ========================================

// Validate article title length
if (strlen($article_title) > NOVA_TITLE_MAX_LENGTH) {
  $errors[] = str_replace('{max}', NOVA_TITLE_MAX_LENGTH, __admin('articles.val.title_max'));
}

// Validate item collection length
if (
  !empty($item_collection) &&
  strlen($item_collection) > NOVA_COLLECTION_MAX_LENGTH
) {
  $errors[] = str_replace('{max}', NOVA_COLLECTION_MAX_LENGTH, __admin('articles.val.collection_max'));
}

// Validate year range
if ($item_year && ($item_year < NOVA_YEAR_MIN || $item_year > NOVA_YEAR_MAX)) {
  $errors[] = str_replace(['{min}', '{max}'], [NOVA_YEAR_MIN, NOVA_YEAR_MAX], __admin('articles.val.year_range'));
}

// Validate YouTube URL if provided
if (
  !empty($youtube_video) &&
  !filter_var($youtube_video, FILTER_VALIDATE_URL)
) {
  $errors[] = __admin('articles.val.youtube_invalid');
}

// ========================================
// PHASE 3: VALIDATE & UPLOAD IMAGES
// ========================================
include '../inc/inc_nova_img_upload.php';

// ========================================
// PHASE 4: CHECK ALL VALIDATION ERRORS
// ========================================
if (!empty($errors)) {
  $_SESSION['articles_errors'] = $errors;
  $_SESSION['articles_form_data'] = $_POST;

  // Clean up uploaded images if validation failed
  if ($uploaded_image_1) {
    @unlink(NOVA_PATH_FILE_MAX . $uploaded_image_1);
    @unlink(NOVA_PATH_FILE_MED . $uploaded_image_1);
    @unlink(NOVA_PATH_FILE_MIN . $uploaded_image_1);
  }
  if ($uploaded_image_2) {
    @unlink(NOVA_PATH_FILE_MAX . $uploaded_image_2);
    @unlink(NOVA_PATH_FILE_MED . $uploaded_image_2);
    @unlink(NOVA_PATH_FILE_MIN . $uploaded_image_2);
  }

  header('Location: articles_create.php');
  exit();
}

// ========================================
// PHASE 5: RESIZE IMAGES
// ========================================
include '../inc/inc_nova_img_resize.php';

// ========================================
// PHASE 6: INSERT INTO DATABASE
// ========================================
try {
  // Prepare the INSERT query
  $query = 'INSERT INTO ns_articles (
        id_subcategory, article_title, article_content, article_summary,
        item_collection, item_year, youtube_video,
        image_1, image_2,
        publish_date, is_active, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

  $stmt = mysqli_prepare($conn, $query);

  if (!$stmt) {
    throw new Exception(__admin('articles.err.query_prepare') . ': ' . mysqli_error($conn));
  }

  // Bind parameters
  mysqli_stmt_bind_param(
    $stmt,
    'issssissssii',
    $id_subcategory,
    $article_title,
    $article_content,
    $article_summary,
    $item_collection,
    $item_year,
    $youtube_video,
    $uploaded_image_1,
    $uploaded_image_2,
    $publish_date,
    $is_active,
    $created_by
  );

  // Execute the query
  if (mysqli_stmt_execute($stmt)) {
    $new_article_id = mysqli_insert_id($conn);
    $success_message = str_replace('{id}', $new_article_id, __admin('articles.msg.created'));

    // Log the action
    error_log("Nova: Article created - ID: $new_article_id by Admin ID: " . $_SESSION['admin_id']);

    // Clear any form data from session
    unset($_SESSION['articles_form_data']);
    unset($_SESSION['articles_errors']);

    // Redirect back to form with success message
    $_SESSION['articles_success'] = $success_message;

    // Close statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header('Location: articles_create.php');
    exit();
  } else {
    throw new Exception(__admin('articles.err.insert_failed') . ': ' . mysqli_stmt_error($stmt));
  }
} catch (Exception $e) {
  // Database error - clean up uploaded files
  error_log('Nova Articles Store Error: ' . $e->getMessage());

  if ($uploaded_image_1) {
    @unlink(NOVA_PATH_FILE_MAX . $uploaded_image_1);
    @unlink(NOVA_PATH_FILE_MED . $uploaded_image_1);
    @unlink(NOVA_PATH_FILE_MIN . $uploaded_image_1);
  }
  if ($uploaded_image_2) {
    @unlink(NOVA_PATH_FILE_MAX . $uploaded_image_2);
    @unlink(NOVA_PATH_FILE_MED . $uploaded_image_2);
    @unlink(NOVA_PATH_FILE_MIN . $uploaded_image_2);
  }

  // Show error to user
  $errors[] = $e->getMessage();
  $_SESSION['articles_errors'] = $errors;
  $_SESSION['articles_form_data'] = $_POST;
  header('Location: articles_create.php');
  exit();
}
