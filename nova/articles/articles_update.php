<?php
// Nova Articles Update - Process article update
// Session management and database connection
include '../inc/inc_nova_session.php';
include '../inc/inc_nova_constants.php';

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

// Get article ID
$article_id = isset($_POST['id_article']) ? intval($_POST['id_article']) : 0;

if (!$article_id) {
  $_SESSION['articles_errors'] = [__admin('articles.err.invalid_id')];
  header('Location: articles_list.php');
  exit();
}

// ========================================
// FETCH CURRENT ARTICLE DATA
// ========================================
$verify_query = 'SELECT
    id_article, id_subcategory, article_title, article_content, article_summary,
    item_collection, item_year, youtube_video,
    image_1, image_2,
    publish_date, is_active
FROM ns_articles WHERE id_article = ?';

$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, 'i', $article_id);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);

if (!$verify_result || mysqli_num_rows($verify_result) === 0) {
  $_SESSION['articles_errors'] = [__admin('articles.err.not_found')];
  header('Location: articles_list.php');
  exit();
}

$current_article = mysqli_fetch_assoc($verify_result);

// Extract current values
$current_image_1 = $current_article['image_1'];
$current_image_2 = $current_article['image_2'];

// Initialize variables
$errors = [];

// Sanitize input data
$article_title = trim($_POST['article_title'] ?? '');
$id_subcategory = intval($_POST['id_subcategory'] ?? 0);
$article_content = trim($_POST['article_content'] ?? '');
$article_summary = trim($_POST['article_summary'] ?? '');
$item_collection = trim($_POST['item_collection'] ?? '');
$item_year = !empty($_POST['item_year']) ? intval($_POST['item_year']) : null;
$youtube_video = trim($_POST['youtube_video'] ?? '');
$publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
// NOTE: is_active is NOT updated here - managed via toggle action (articles_toggle.php)

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
  header("Location: articles_edit.php?id=$article_id");
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
// PHASE 3-6: PROCESS IMAGES (image_1, image_2)
// ========================================

// Handle checkbox removal + cleanup old images
// Sets $uploaded_image_1/_2 from $current_image_* (default: keep)
// If checkbox checked → delete files + set NULL
// If new upload coming → delete old versions
include '../inc/inc_nova_img_checkbox.php';

// Upload NEW images (validation + upload to file_db_max/)
// Updates $uploaded_image_* with new filename if upload successful
// Validates: JPG only, exact dimensions (gallery H/V)
include '../inc/inc_nova_img_update.php';

// Map helper variables to article update variables
$final_image_1 = $uploaded_image_1;
$final_image_2 = $uploaded_image_2;

// ========================================
// PHASE 7: CHECK ALL VALIDATION ERRORS
// ========================================
if (!empty($errors)) {
  $_SESSION['articles_errors'] = $errors;
  $_SESSION['articles_form_data'] = $_POST;

  // Clean up newly uploaded files if validation failed
  if (!empty($final_image_1) && $final_image_1 !== $current_image_1) {
    @unlink(NOVA_PATH_FILE_MAX . $final_image_1);
    @unlink(NOVA_PATH_FILE_MED . $final_image_1);
    @unlink(NOVA_PATH_FILE_MIN . $final_image_1);
  }
  if (!empty($final_image_2) && $final_image_2 !== $current_image_2) {
    @unlink(NOVA_PATH_FILE_MAX . $final_image_2);
    @unlink(NOVA_PATH_FILE_MED . $final_image_2);
    @unlink(NOVA_PATH_FILE_MIN . $final_image_2);
  }

  header("Location: articles_edit.php?id=$article_id");
  exit();
}

// ========================================
// PHASE 8: RESIZE IMAGES
// ========================================

// Prepare for resize - only NEW uploads (not existing images from DB)
// Save original values
$temp_uploaded_1 = $uploaded_image_1;
$temp_uploaded_2 = $uploaded_image_2;

// Null out unchanged images (resize helper will skip null values)
if ($uploaded_image_1 === $current_image_1) $uploaded_image_1 = null;
if ($uploaded_image_2 === $current_image_2) $uploaded_image_2 = null;

// Resize NEW uploaded images (creates MED and MIN versions)
include '../inc/inc_nova_img_resize.php';

// Restore original values for database UPDATE
$uploaded_image_1 = $temp_uploaded_1;
$uploaded_image_2 = $temp_uploaded_2;

// ========================================
// PHASE 9: UPDATE DATABASE
// ========================================
try {
  // Prepare the UPDATE query
  // NOTE: is_active is NOT updated here - managed via toggle action
  $query = 'UPDATE ns_articles SET
        id_subcategory = ?,
        article_title = ?,
        article_content = ?,
        article_summary = ?,
        item_collection = ?,
        item_year = ?,
        youtube_video = ?,
        image_1 = ?,
        image_2 = ?,
        publish_date = ?,
        updated_at = NOW()
    WHERE id_article = ?';

  $stmt = mysqli_prepare($conn, $query);

  if (!$stmt) {
    throw new Exception(__admin('articles.err.query_prepare') . ': ' . mysqli_error($conn));
  }

  // Bind parameters (use $final_* variables)
  // NOTE: is_active is NOT updated here - managed via toggle action
  mysqli_stmt_bind_param(
    $stmt,
    'issssissssi',
    $id_subcategory,
    $article_title,
    $article_content,
    $article_summary,
    $item_collection,
    $item_year,
    $youtube_video,
    $final_image_1,
    $final_image_2,
    $publish_date,
    $article_id
  );

  // Execute the query
  if (mysqli_stmt_execute($stmt)) {
    $success_message = str_replace('{id}', $article_id, __admin('articles.msg.updated'));

    // Log the action
    error_log("Nova: Article updated - ID: $article_id by Admin ID: " . $_SESSION['admin_id']);

    // Clear any form data from session
    unset($_SESSION['articles_form_data']);
    unset($_SESSION['articles_errors']);

    // Redirect to list with success message
    $_SESSION['articles_success'] = $success_message;

    // Close statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header('Location: articles_list.php');
    exit();
  } else {
    throw new Exception(__admin('articles.err.update_failed') . ': ' . mysqli_stmt_error($stmt));
  }
} catch (Exception $e) {
  // Database error - clean up newly uploaded files (not existing ones)
  error_log('Nova Articles Update Error: ' . $e->getMessage());

  if (!empty($final_image_1) && $final_image_1 !== $current_image_1) {
    @unlink(NOVA_PATH_FILE_MAX . $final_image_1);
    @unlink(NOVA_PATH_FILE_MED . $final_image_1);
    @unlink(NOVA_PATH_FILE_MIN . $final_image_1);
  }
  if (!empty($final_image_2) && $final_image_2 !== $current_image_2) {
    @unlink(NOVA_PATH_FILE_MAX . $final_image_2);
    @unlink(NOVA_PATH_FILE_MED . $final_image_2);
    @unlink(NOVA_PATH_FILE_MIN . $final_image_2);
  }

  // Show error to user
  $errors[] = $e->getMessage();
  $_SESSION['articles_errors'] = $errors;
  $_SESSION['articles_form_data'] = $_POST;
  header("Location: articles_edit.php?id=$article_id");
  exit();
}
