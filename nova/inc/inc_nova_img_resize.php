<?php
/**
 * Nova Image Resize - Linear resize processing for gallery images (1, 2)
 * Creates medium and small versions from max (already uploaded)
 */

// Storage paths - Use constants from inc_nova_constants.php
$path_max = NOVA_PATH_FILE_MAX;
$path_med = NOVA_PATH_FILE_MED;
$path_min = NOVA_PATH_FILE_MIN;

// JPG Quality - Use constant from inc_nova_constants.php
$jpg_quality = NOVA_IMAGE_QUALITY;

// ========================================
// RESIZE IMAGE 1 - Gallery
// ========================================
if (!empty($uploaded_image_1)) {
  $source_file = $path_max . $uploaded_image_1;

  // Create JPG resource
  $source_img = @imagecreatefromjpeg($source_file);

  if ($source_img !== false) {
    // Get original dimensions
    $orig_width = imagesx($source_img);
    $orig_height = imagesy($source_img);

    // MEDIUM version - Use constant from config
    $med_width = NOVA_GALLERY_RESIZE_MED;
    $med_height = floor($orig_height * ($med_width / $orig_width));
    $med_img = imagecreatetruecolor($med_width, $med_height);
    imagecopyresampled(
      $med_img,
      $source_img,
      0,
      0,
      0,
      0,
      $med_width,
      $med_height,
      $orig_width,
      $orig_height,
    );

    $med_file = $path_med . $uploaded_image_1;
    imagejpeg($med_img, $med_file, $jpg_quality);
    chmod($med_file, 0777);
    imagedestroy($med_img);

    // SMALL version - Use constant from config
    $min_width = NOVA_GALLERY_RESIZE_MIN;
    $min_height = floor($orig_height * ($min_width / $orig_width));
    $min_img = imagecreatetruecolor($min_width, $min_height);
    imagecopyresampled(
      $min_img,
      $source_img,
      0,
      0,
      0,
      0,
      $min_width,
      $min_height,
      $orig_width,
      $orig_height,
    );

    $min_file = $path_min . $uploaded_image_1;
    imagejpeg($min_img, $min_file, $jpg_quality);
    chmod($min_file, 0777);
    imagedestroy($min_img);

    // Free memory
    imagedestroy($source_img);

    error_log('Nova: Gallery image_1 resized - Medium and Small versions created');
  } else {
    $errors[] = str_replace('{num}', '1', __admin('images.val.resize_error_gallery'));
  }
}

// ========================================
// RESIZE IMAGE 2 - Gallery
// ========================================
if (!empty($uploaded_image_2)) {
  $source_file = $path_max . $uploaded_image_2;

  // Create JPG resource
  $source_img = @imagecreatefromjpeg($source_file);

  if ($source_img !== false) {
    // Get original dimensions
    $orig_width = imagesx($source_img);
    $orig_height = imagesy($source_img);

    // MEDIUM version - Use constant from config
    $med_width = NOVA_GALLERY_RESIZE_MED;
    $med_height = floor($orig_height * ($med_width / $orig_width));
    $med_img = imagecreatetruecolor($med_width, $med_height);
    imagecopyresampled(
      $med_img,
      $source_img,
      0,
      0,
      0,
      0,
      $med_width,
      $med_height,
      $orig_width,
      $orig_height,
    );

    $med_file = $path_med . $uploaded_image_2;
    imagejpeg($med_img, $med_file, $jpg_quality);
    chmod($med_file, 0777);
    imagedestroy($med_img);

    // SMALL version - Use constant from config
    $min_width = NOVA_GALLERY_RESIZE_MIN;
    $min_height = floor($orig_height * ($min_width / $orig_width));
    $min_img = imagecreatetruecolor($min_width, $min_height);
    imagecopyresampled(
      $min_img,
      $source_img,
      0,
      0,
      0,
      0,
      $min_width,
      $min_height,
      $orig_width,
      $orig_height,
    );

    $min_file = $path_min . $uploaded_image_2;
    imagejpeg($min_img, $min_file, $jpg_quality);
    chmod($min_file, 0777);
    imagedestroy($min_img);

    // Free memory
    imagedestroy($source_img);

    error_log('Nova: Gallery image_2 resized - Medium and Small versions created');
  } else {
    $errors[] = str_replace('{num}', '2', __admin('images.val.resize_error_gallery'));
  }
}

?>
