<?php
// Nova Articles Delete - Simple direct deletion (no Panda approach)
// Session management and database connection
include '../inc/inc_nova_session.php';

// Determine redirect URL (use referer to maintain pagination)
$redirect_url = nova_safe_redirect('articles_list.php');

// CSRF Token Validation - Protect against Cross-Site Request Forgery via URL
// DELETE operations use GET but still need CSRF protection
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
  $_SESSION['nova_errors'] = [__admin('articles.err.csrf_invalid')];
  header("Location: $redirect_url");
  exit();
}

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
    $_SESSION['nova_errors'] = [__admin('articles.err.invalid_id')];
    header("Location: $redirect_url");
    exit();
}

// Get article data and associated files
$query = 'SELECT article_title, image_1, image_2 FROM ns_articles WHERE id_article = ?';
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['nova_errors'] = [__admin('articles.err.not_found')];
    header("Location: $redirect_url");
    exit();
}

$article = mysqli_fetch_assoc($result);
$article_title = $article['article_title'];

try {
    // Delete article from database first (source of truth)
    $delete_query = 'DELETE FROM ns_articles WHERE id_article = ?';
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, 'i', $article_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(__admin('articles.err.delete_failed'));
    }

    // DB succeeded — now clean up physical image files (3 versions each: max/med/min)
    $images = ['image_1', 'image_2'];
    foreach ($images as $image_field) {
        if (!empty($article[$image_field])) {
            $image_file = $article[$image_field];
            @unlink(NOVA_PATH_FILE_MAX . $image_file);
            @unlink(NOVA_PATH_FILE_MED . $image_file);
            @unlink(NOVA_PATH_FILE_MIN . $image_file);
            error_log("Nova: Article image deleted - Field: $image_field, File: $image_file");
        }
    }

    // Log the action
    error_log("Nova: Article deleted - ID: $article_id, Title: $article_title by Admin ID: " . $_SESSION['admin_id']);

    // Success message
    $_SESSION['nova_success'] = str_replace('{id}', $article_id, __admin('articles.msg.deleted'));

} catch (Exception $e) {
    $_SESSION['nova_errors'] = [$e->getMessage()];
    error_log('Nova Article Delete Error: ' . $e->getMessage());
}

// Redirect back to referer (pagination preserved)
header("Location: $redirect_url");
exit();
