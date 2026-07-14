<?php
/**
 * Nova Image Checkbox Handler - UPDATE Operations Only
 * Handles checkbox removal state without deleting files before database success
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
 * 2. Include this file (handles checkbox state)
 * 3. Include inc_nova_img_upload.php (handles new upload)
 * 4. Include inc_nova_img_resize.php (handles resize)
 * 5. articles_update.php deletes old files only after a successful database update
 */

// ========================================
// IMAGE 1 - Gallery
// ========================================
$uploaded_image_1 = $current_image_1; // Default: keep current image

// CHECKBOX REMOVAL - User wants to delete this image
if (isset($_POST['remove_image_1']) && $_POST['remove_image_1'] == '1') {
  $uploaded_image_1 = null;
}

// ========================================
// IMAGE 2 - Gallery
// ========================================
$uploaded_image_2 = $current_image_2; // Default: keep current image

// CHECKBOX REMOVAL
if (isset($_POST['remove_image_2']) && $_POST['remove_image_2'] == '1') {
  $uploaded_image_2 = null;
}
