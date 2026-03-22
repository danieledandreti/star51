<?php
/**
 * Nova Image Upload UPDATE - Linear upload processing for UPDATE operations
 * Validates and uploads NEW images only (1, 2), preserves existing images
 * Does NOT initialize variables - they are already set in articles_update.php
 */

// Storage path - Use constant from inc_nova_constants.php
$upload_path = NOVA_PATH_FILE_MAX;

// ========================================
// IMAGE 1 - Gallery (H or V)
// ========================================
if (isset($_FILES['image_1']) && $_FILES['image_1']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image_1'];
    $has_image1_error = false;

    // Get image info
    $image_info = @getimagesize($file['tmp_name']);

    if ($image_info === false) {
        $errors[] = str_replace('{num}', '1', __admin('images.val.gallery_invalid'));
        $has_image1_error = true;
    } else {
        list($width, $height, $type) = $image_info;

        // Check image type - ONLY JPG
        if ($type !== 2) {
            $errors[] = str_replace('{num}', '1', __admin('images.val.gallery_format'));
            $has_image1_error = true;
        }

        // Check EXACT dimensions using config
        $valid_landscape = ($width === NOVA_GALLERY_H_WIDTH && $height === NOVA_GALLERY_H_HEIGHT);
        $valid_portrait = ($width === NOVA_GALLERY_V_WIDTH && $height === NOVA_GALLERY_V_HEIGHT);

        if (!$valid_landscape && !$valid_portrait) {
            $errors[] = str_replace(
                ['{num}', '{width}', '{height}', '{h_width}', '{h_height}', '{v_width}', '{v_height}'],
                ['1', $width, $height, NOVA_GALLERY_H_WIDTH, NOVA_GALLERY_H_HEIGHT, NOVA_GALLERY_V_WIDTH, NOVA_GALLERY_V_HEIGHT],
                __admin('images.val.gallery_size')
            );
            $has_image1_error = true;
        }

        // If no errors for this image, upload
        if (!$has_image1_error) {
            // Generate unique filename with offset 1
            $filename = 'ns51_' . (time() + 1) . '.jpg';
            $destination = $upload_path . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                chmod($destination, NOVA_FILE_CHMOD);
                $uploaded_image_1 = $filename; // UPDATE existing variable
                error_log("Nova: Gallery image_1 uploaded (UPDATE) - File: $filename");
            } else {
                $errors[] = str_replace('{num}', '1', __admin('images.val.gallery_save_error'));
            }
        }
    }
}

// ========================================
// IMAGE 2 - Gallery (H or V)
// ========================================
if (isset($_FILES['image_2']) && $_FILES['image_2']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image_2'];
    $has_image2_error = false;

    // Get image info
    $image_info = @getimagesize($file['tmp_name']);

    if ($image_info === false) {
        $errors[] = str_replace('{num}', '2', __admin('images.val.gallery_invalid'));
        $has_image2_error = true;
    } else {
        list($width, $height, $type) = $image_info;

        // Check image type - ONLY JPG
        if ($type !== 2) {
            $errors[] = str_replace('{num}', '2', __admin('images.val.gallery_format'));
            $has_image2_error = true;
        }

        // Check EXACT dimensions using config
        $valid_landscape = ($width === NOVA_GALLERY_H_WIDTH && $height === NOVA_GALLERY_H_HEIGHT);
        $valid_portrait = ($width === NOVA_GALLERY_V_WIDTH && $height === NOVA_GALLERY_V_HEIGHT);

        if (!$valid_landscape && !$valid_portrait) {
            $errors[] = str_replace(
                ['{num}', '{width}', '{height}', '{h_width}', '{h_height}', '{v_width}', '{v_height}'],
                ['2', $width, $height, NOVA_GALLERY_H_WIDTH, NOVA_GALLERY_H_HEIGHT, NOVA_GALLERY_V_WIDTH, NOVA_GALLERY_V_HEIGHT],
                __admin('images.val.gallery_size')
            );
            $has_image2_error = true;
        }

        // If no errors for this image, upload
        if (!$has_image2_error) {
            // Generate unique filename with offset 2
            $filename = 'ns51_' . (time() + 2) . '.jpg';
            $destination = $upload_path . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                chmod($destination, NOVA_FILE_CHMOD);
                $uploaded_image_2 = $filename; // UPDATE existing variable
                error_log("Nova: Gallery image_2 uploaded (UPDATE) - File: $filename");
            } else {
                $errors[] = str_replace('{num}', '2', __admin('images.val.gallery_save_error'));
            }
        }
    }
}

?>
