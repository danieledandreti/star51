<?php
// Nova Articles Edit - Edit existing article form
// Session management and database connection (includes db config + constants)
include '../inc/inc_nova_session.php';

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
  $_SESSION['articles_errors'] = [__admin('articles.err.invalid_id')];
  header('Location: articles_list.php');
  exit();
}

// Fetch article data for editing
$article_query = 'SELECT
    a.id_article,
    a.id_subcategory,
    a.article_title,
    a.article_content,
    a.article_summary,
    a.item_collection,
    a.item_year,
    a.youtube_video,
    a.image_1,
    a.image_2,
    a.publish_date,
    a.show_publish_date,
    a.is_active,
    s.subcategory_name,
    s.id_category,
    c.category_name
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE a.id_article = ?';

$stmt = mysqli_prepare($conn, $article_query);
mysqli_stmt_bind_param($stmt, 'i', $article_id);
mysqli_stmt_execute($stmt);
$article_result = mysqli_stmt_get_result($stmt);

if (!$article_result || mysqli_num_rows($article_result) === 0) {
  $_SESSION['articles_errors'] = [__admin('articles.err.not_found')];
  header('Location: articles_list.php');
  exit();
}

$article = mysqli_fetch_assoc($article_result);

// Get combined subcategories list with category name (active + current subcategory even if inactive)
$subcategories_query = 'SELECT
    s.id_subcategory,
    s.subcategory_name,
    s.id_category,
    s.is_active AS subcat_is_active,
    c.category_name,
    c.is_active AS cat_is_active
FROM ns_subcategories s
LEFT JOIN ns_categories c ON s.id_category = c.id_category
WHERE (s.is_active = 1 AND c.is_active = 1) OR s.id_subcategory = ?
ORDER BY c.category_name, s.subcategory_name';
$stmt_subcat = mysqli_prepare($conn, $subcategories_query);
mysqli_stmt_bind_param($stmt_subcat, 'i', $article['id_subcategory']);
mysqli_stmt_execute($stmt_subcat);
$subcategories_result = mysqli_stmt_get_result($stmt_subcat);
$subcategories_list = [];
if ($subcategories_result) {
  while ($subcat = mysqli_fetch_assoc($subcategories_result)) {
    $subcategories_list[] = $subcat;
  }
}

// Page configuration
$page_title = __admin('articles.page.edit_title') . ': ' . $article['article_title'] . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('articles.page.edit_desc');

// Check for form data from previous submission (in case of validation errors)
$form_data = $_SESSION['articles_form_data'] ?? $article;
unset($_SESSION['articles_form_data']);

// Include form helpers for field repopulation
include '../inc/inc_nova_form_helpers.php';
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
    <!-- Quill Editor CSS (load before Nova styles) -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

    <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
    <!-- Navigation -->
    <?php include '../inc/inc_nova_navigation.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <main class="nova-main-content" role="main" id="main-content">
        <div class="container-nova py-4">

            <!-- Flash Messages -->
            <?php
            $flash_modules = ['articles'];
            include '../inc/inc_nova_flash_messages.php';
            ?>

            <!-- PAGE HEADER SECTION -->
            <header class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="bi bi-pencil-square me-2"></i><?= __admin('articles.page.edit_title') ?>
                        </h1>
                    </div>
                    <div>
                        <a href="articles_list.php" class="btn btn-primary nova-btn-action">
                            <i class="bi bi-arrow-left"></i><?= __admin('buttons.back_to_list') ?>
                        </a>
                    </div>
                </div>
            </header>
            <!-- END: PAGE HEADER SECTION -->

            <!-- FORM SECTION -->
            <section class="content-section mb-5">
                <div class="row">
                    <div class="col-12">
                        <div class="nova-card">
                            <div class="nova-card-body">
                                <h5 class="card-title mb-1">
                                    <i class="bi bi-pencil-square me-2"></i><?= __admin('articles.form.card_title_edit') ?>
                                </h5>
                                <p class="page-subtitle mb-3">
                                    <?= str_replace('{id}', $article['id_article'], __admin('articles.form.card_subtitle_edit')) ?>
                                </p>

                                <!-- Required Fields Notice -->
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <?= __admin('messages.required_fields') ?>
                                </div>

                                <form method="post" action="articles_update.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <!-- CSRF Token Protection -->
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <!-- Hidden ID field -->
                                    <input type="hidden" name="id_article" value="<?= $article['id_article'] ?>">

                                    <!-- Row 1: Date + Category/Subcategory + Title -->
                                    <div class="row mb-3">
                                        <!-- Publication Date -->
                                        <div class="col-md-2">
                                            <label for="publish_date" class="form-label fw-bold">
                                                <?= __admin('articles.form.publish_date') ?>
                                            </label>
                                            <input type="date"
                                                   class="form-control"
                                                   id="publish_date"
                                                   name="publish_date"
                                                   value="<?= nova_get_form_value('publish_date', $article, $form_data) ?>">

                                            <!-- Show Publish Date Checkbox -->
                                            <div class="form-check mt-2">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       id="show_publish_date"
                                                       name="show_publish_date"
                                                       value="1"
                                                       <?= nova_is_checked('show_publish_date', $article, $form_data) ?>>
                                                <label class="form-check-label" for="show_publish_date">
                                                    <?= __admin('articles.form.show_date_checkbox') ?>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Combined Category-Subcategory Dropdown -->
                                        <div class="col-md-4">
                                            <label for="id_subcategory" class="form-label fw-bold">
                                                <?= __admin('articles.form.subcategory') ?> <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="id_subcategory" name="id_subcategory" required>
                                                <option value=""><?= __admin('articles.form.select') ?>...</option>
                                                <?php foreach ($subcategories_list as $subcat): ?>
                                                    <?php
                                                    // Check if either category or subcategory is inactive
                                                    $is_inactive = !$subcat['cat_is_active'] || !$subcat['subcat_is_active'];
                                                    $inactive_label = '';
                                                    if (!$subcat['cat_is_active'] && !$subcat['subcat_is_active']) {
                                                      $inactive_label = ' (' . __admin('articles.form.cat_subcat_inactive') . ')';
                                                    } elseif (!$subcat['cat_is_active']) {
                                                      $inactive_label = ' (' . __admin('articles.form.cat_inactive') . ')';
                                                    } elseif (!$subcat['subcat_is_active']) {
                                                      $inactive_label = ' (' . __admin('articles.form.subcat_inactive') . ')';
                                                    }
                                                    ?>
                                                    <option value="<?= $subcat['id_subcategory'] ?>"
                                                        <?= $form_data['id_subcategory'] == $subcat['id_subcategory'] ? 'selected' : '' ?>
                                                        <?= $is_inactive ? 'class="text-muted"' : '' ?>>
                                                        [<?= htmlspecialchars($subcat['category_name']) ?>] - <?= htmlspecialchars($subcat['subcategory_name']) . $inactive_label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                <?= __admin('articles.form.subcategory_feedback') ?>
                                            </div>
                                        </div>

                                        <!-- Article Title -->
                                        <div class="col-md-6">
                                            <label for="article_title" class="form-label fw-bold">
                                                <?= __admin('articles.form.title') ?> <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="article_title"
                                                   name="article_title"
                                                   value="<?= nova_get_form_value('article_title', $article, $form_data) ?>"
                                                   required
                                                   maxlength="255">
                                            <div class="invalid-feedback">
                                                <?= __admin('articles.form.title_feedback') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Metadata (Type/Year) + Summary (compact layout like create) -->
                                    <div class="row mb-3">
                                        <!-- Left column: Type + Year -->
                                        <div class="col-md-4">
                                            <!-- Item Collection -->
                                            <div class="mb-2">
                                                <label for="item_collection" class="form-label fw-bold">
                                                    <?= __admin('articles.form.collection') ?>
                                                </label>
                                                <input type="text"
                                                       class="form-control"
                                                       id="item_collection"
                                                       name="item_collection"
                                                       value="<?= nova_get_form_value('item_collection', $article, $form_data) ?>"
                                                       maxlength="255">
                                                <div class="form-text">
                                                    <?= __admin('articles.form.collection_help') ?>
                                                </div>
                                            </div>

                                            <!-- Item Year -->
                                            <div class="nova-pt-1rem">
                                                <label for="item_year" class="form-label fw-bold">
                                                    <?= __admin('articles.form.year') ?>
                                                </label>
                                                <input type="number"
                                                       class="form-control"
                                                       id="item_year"
                                                       name="item_year"
                                                       value="<?= nova_get_form_value('item_year', $article, $form_data) ?>"
                                                       min="1901"
                                                       max="2155">
                                                <div class="invalid-feedback">
                                                    <?= __admin('articles.form.year_feedback') ?>
                                                </div>
                                                <div class="form-text">
                                                    <?= __admin('articles.form.year_help') ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right column: Summary -->
                                        <div class="col-md-8">
                                            <label for="article_summary" class="form-label fw-bold">
                                                <?= __admin('articles.form.summary') ?>
                                            </label>
                                            <textarea class="form-control"
                                                      id="article_summary"
                                                      name="article_summary"
                                                      rows="6"><?= nova_get_form_value('article_summary', $article, $form_data) ?></textarea>
                                            <div class="form-text">
                                                <?= __admin('articles.form.summary_help') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Article Content -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <?= __admin('articles.form.content') ?>
                                        </label>
                                        <div id="quill-editor" class="nova-quill-editor-tall"></div>
                                        <textarea id="article_content" name="article_content" class="nova-hidden"><?= nova_get_form_value('article_content', $article, $form_data) ?></textarea>
                                        <div class="form-text">
                                            <?= __admin('articles.form.content_help') ?>
                                        </div>
                                    </div>

                                    <!-- Separator -->
                                    <hr class="my-4">

                                    <!-- MEDIA SECTION -->
                                    <!-- Row 1: YouTube Video -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="youtube_video" class="form-label fw-bold">
                                                <i class="bi bi-youtube me-1"></i>
                                                <?= __admin('articles.form.youtube') ?>
                                            </label>
                                            <input type="url"
                                                   class="form-control"
                                                   id="youtube_video"
                                                   name="youtube_video"
                                                   value="<?= nova_get_form_value('youtube_video', $article, $form_data) ?>">
                                            <div class="form-text">
                                                <?= __admin('articles.form.youtube_help') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Gallery Images -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="bi bi-image me-1"></i>
                                                <?= __admin('articles.form.image_1') ?>
                                            </label>

                                            <?php if (!empty($article['image_1'])): ?>
                                                <!-- Image Preview -->
                                                <img src="../../file_db_max/<?= $article['image_1'] ?>"
                                                     alt="Immagine 1"
                                                     class="img-thumbnail mb-2 w-100">

                                                <!-- File info box with checkbox -->
                                                <div class="alert alert-info mb-2">
                                                    <div class="mb-2">
                                                        <strong><?= __admin('articles.form.current_file') ?>:</strong> <small class="text-muted"><?= htmlspecialchars($article['image_1']) ?></small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="remove_image_1" name="remove_image_1" value="1">
                                                        <label class="form-check-label" for="remove_image_1">
                                                            <i class="bi bi-trash text-danger me-1"></i><?= __admin('articles.form.remove') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Placeholder Preview -->
                                                <div class="image-placeholder-preview mb-2">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                                <div class="alert alert-info mb-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <?= __admin('articles.form.no_image') ?>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="image_1" name="image_1" accept="image/jpeg,image/jpg">
                                            <div class="form-text">
                                                <strong><?= __admin('articles.form.jpg_only') ?></strong> - <strong><?= NOVA_GALLERY_H_WIDTH ?>×<?= NOVA_GALLERY_H_HEIGHT ?>px (H)</strong> o <strong><?= NOVA_GALLERY_V_WIDTH ?>×<?= NOVA_GALLERY_V_HEIGHT ?>px (V)</strong>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="bi bi-image me-1"></i>
                                                <?= __admin('articles.form.image_2') ?>
                                            </label>

                                            <?php if (!empty($article['image_2'])): ?>
                                                <!-- Image Preview -->
                                                <img src="../../file_db_max/<?= $article['image_2'] ?>"
                                                     alt="Immagine 2"
                                                     class="img-thumbnail mb-2 w-100">

                                                <!-- File info box with checkbox -->
                                                <div class="alert alert-info mb-2">
                                                    <div class="mb-2">
                                                        <strong><?= __admin('articles.form.current_file') ?>:</strong> <small class="text-muted"><?= htmlspecialchars($article['image_2']) ?></small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="remove_image_2" name="remove_image_2" value="1">
                                                        <label class="form-check-label" for="remove_image_2">
                                                            <i class="bi bi-trash text-danger me-1"></i><?= __admin('articles.form.remove') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Placeholder Preview -->
                                                <div class="image-placeholder-preview mb-2">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                                <div class="alert alert-info mb-2">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <?= __admin('articles.form.no_image') ?>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="image_2" name="image_2" accept="image/jpeg,image/jpg">
                                            <div class="form-text">
                                                <strong><?= __admin('articles.form.jpg_only') ?></strong> - <strong><?= NOVA_GALLERY_H_WIDTH ?>×<?= NOVA_GALLERY_H_HEIGHT ?>px (H)</strong> o <strong><?= NOVA_GALLERY_V_WIDTH ?>×<?= NOVA_GALLERY_V_HEIGHT ?>px (V)</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary nova-btn-action">
                                            <i class="bi bi-save"></i><?= __admin('articles.buttons.save') ?>
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: FORM SECTION -->

        </div>
    </main>
    <!-- END: MAIN CONTENT WRAPPER -->

    <!-- FOOTER -->
    <?php include '../inc/inc_nova_footer.php'; ?>

    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Sequential PHP handling in articles_update.php:
        // 1. Checkbox removes existing file/image
        // 2. New upload replaces existing (deletes old + uploads new)
        // If both: checkbox deletes first, then upload adds new = new image/file wins
    </script>

    <!-- Quill Editor JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <!-- Quill Editor Custom Styles -->
    <style>
        /* Quill editor background and font */
        .ql-container {
            background-color: #ffffff;
            font-size: 16px;
        }
        .ql-editor {
            min-height: 250px;
        }
        /* Toolbar styling */
        .ql-toolbar {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>

    <script>
        // Initialize Quill Editor
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: '<?= __admin('articles.form.quill_placeholder') ?>',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Load existing content from hidden textarea
        var existingContent = document.getElementById('article_content').value;
        if (existingContent) {
            quill.root.innerHTML = existingContent;
        }

        // Sync Quill content to hidden textarea AND check file size on form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            // STEP 1: Sync Quill content to hidden textarea
            var contentHTML = quill.root.innerHTML;
            document.getElementById('article_content').value = contentHTML;

            // STEP 2: Check total file size BEFORE submit
            const maxTotalSize = 8 * 1024 * 1024; // 8MB total (post_max_size limit)
            let totalSize = 0;
            let fileNames = [];

            // Check all file inputs
            const fileInputs = ['image_1', 'image_2'];

            fileInputs.forEach(inputName => {
                const input = document.querySelector(`input[name="${inputName}"]`);
                if (input && input.files && input.files[0]) {
                    totalSize += input.files[0].size;
                    fileNames.push(inputName + ': ' + (input.files[0].size / (1024 * 1024)).toFixed(2) + 'MB');
                }
            });

            if (totalSize > maxTotalSize) {
                e.preventDefault();
                const totalMB = (totalSize / (1024 * 1024)).toFixed(2);
                const maxMB = (maxTotalSize / (1024 * 1024)).toFixed(2);

                alert('<?= __admin('articles.js.files_too_large') ?>\n\n' +
                    '<?= __admin('articles.js.total_uploaded') ?>: ' + totalMB + 'MB\n' +
                    '<?= __admin('articles.js.max_allowed') ?>: ' + maxMB + 'MB\n\n' +
                    '<?= __admin('articles.js.files_selected') ?>:\n' + fileNames.join('\n') + '\n\n' +
                    '<?= __admin('articles.js.php_reject') ?>\n' +
                    '<?= __admin('articles.js.reduce_size_or_count') ?>');

                return false;
            }
        });
    </script>

</body>
</html>
