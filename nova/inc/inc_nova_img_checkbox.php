<?php
/**
 * Nova Image Checkbox Handler - UPDATE Operations Only
 * Handles checkbox removal and old image cleanup before upload
 *
 * This is an INTERMEDIARY file between articles_update.php and inc_nova_img_upload.php
 *
 * REQUIRED INPUT (from calling file):
 * - $current_image_1, $current_image_2
 *
 * OUTPUT (for inc_nova_img_upload.php):
 * - $uploaded_image_1, $uploaded_image_2
 *   (initialized to current value, set to NULL if checkbox removal, overwritten by upload)
 *
 * USAGE:
 * 1. articles_update.php sets $current_image_* from database
 * 2. Include this file (handles checkbox + cleanup)
 * 3. Include inc_nova_img_upload.php (handles new upload)
 * 4. Include inc_nova_img_resize.php (handles resize)
 */

// ========================================
// IMAGE 1 - Gallery
// ========================================
$uploaded_image_1 = $current_image_1; // Default: keep current image

// CHECKBOX REMOVAL
if (isset($_POST['remove_image_1']) && $_POST['remove_image_1'] == '1') {
  if (!empty($current_image_1)) {
    @unlink(NOVA_PATH_FILE_MAX . $current_image_1);
    @unlink(NOVA_PATH_FILE_MED . $current_image_1);
    @unlink(NOVA_PATH_FILE_MIN . $current_image_1);
    error_log("Nova: Image 1 removed via checkbox - File: $current_image_1");
  }
  $uploaded_image_1 = null;
}

// DELETE OLD before NEW upload
if (!empty($current_image_1) &&
    isset($_FILES['image_1']) &&
    $_FILES['image_1']['error'] === UPLOAD_ERR_OK) {
  @unlink(NOVA_PATH_FILE_MAX . $current_image_1);
  @unlink(NOVA_PATH_FILE_MED . $current_image_1);
  @unlink(NOVA_PATH_FILE_MIN . $current_image_1);
  error_log("Nova: Old image 1 deleted before upload - File: $current_image_1");
}

// ========================================
// IMAGE 2 - Gallery
// ========================================
$uploaded_image_2 = $current_image_2; // Default: keep current image

// CHECKBOX REMOVAL
if (isset($_POST['remove_image_2']) && $_POST['remove_image_2'] == '1') {
  if (!empty($current_image_2)) {
    @unlink(NOVA_PATH_FILE_MAX . $current_image_2);
    @unlink(NOVA_PATH_FILE_MED . $current_image_2);
    @unlink(NOVA_PATH_FILE_MIN . $current_image_2);
    error_log("Nova: Image 2 removed via checkbox - File: $current_image_2");
  }
  $uploaded_image_2 = null;
}

// DELETE OLD before NEW upload
if (!empty($current_image_2) &&
    isset($_FILES['image_2']) &&
    $_FILES['image_2']['error'] === UPLOAD_ERR_OK) {
  @unlink(NOVA_PATH_FILE_MAX . $current_image_2);
  @unlink(NOVA_PATH_FILE_MED . $current_image_2);
  @unlink(NOVA_PATH_FILE_MIN . $current_image_2);
  error_log("Nova: Old image 2 deleted before upload - File: $current_image_2");
}

?>
