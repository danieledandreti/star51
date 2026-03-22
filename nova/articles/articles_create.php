<?php
// Nova Articles Create - Add new article form
// Session management and database connection (includes db config + constants)
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('articles.page.create_title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('articles.page.create_desc');

// Fetch subcategories with category names for single dropdown
$subcategories_query = 'SELECT
                        s.id_subcategory,
                        CONCAT("[", c.category_name, "] - ", s.subcategory_name) AS display_name
                        FROM ns_subcategories s
                        INNER JOIN ns_categories c ON s.id_category = c.id_category
                        WHERE s.is_active = 1 AND c.is_active = 1
                        ORDER BY display_name ASC';
$subcategories_result = mysqli_query($conn, $subcategories_query);

if (!$subcategories_result) {
  error_log('Error fetching subcategories: ' . mysqli_error($conn));
  die(__admin('articles.err.subcategories_load'));
}

// Retrieve form data from session if validation failed
$form_data = $_SESSION['articles_form_data'] ?? [];
unset($_SESSION['articles_form_data']);

// Clear after retrieving
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

            <!-- PAGE HEADER SECTION -->
            <header class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="bi bi-collection me-2"></i>
                            <?= __admin('articles.page.create_title') ?>
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

            <!-- Flash Messages -->
            <?php
            $flash_modules = ['articles'];
            include '../inc/inc_nova_flash_messages.php';
            ?>

            <!-- FORM SECTION -->
            <section class="content-section mb-5">
                <div class="row">
                    <div class="col-12">
                        <div class="nova-card">
                            <div class="nova-card-body">
                                <h5 class="card-title mb-1">
                                    <i class="bi bi-file-earmark-plus me-2"></i><?= __admin('articles.form.card_title_create') ?>
                                </h5>
                                <p class="page-subtitle mb-3"><?= __admin('articles.form.card_subtitle_create') ?></p>

                                <!-- Required Fields Notice -->
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <?= __admin('messages.required_fields') ?>
                                </div>
                                <form action="articles_store.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <!-- CSRF Token Protection -->
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                    <!-- Row 1: Date + Category + Title (always present fields) -->
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
                                                   value="<?= htmlspecialchars($form_data['publish_date'] ?? date('Y-m-d')) ?>">

                                            <!-- Show Publish Date Checkbox -->
                                            <div class="form-check mt-2">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       id="show_publish_date"
                                                       name="show_publish_date"
                                                       value="1"
                                                       <?= !empty($form_data['show_publish_date']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="show_publish_date">
                                                    <?= __admin('articles.form.show_date_checkbox') ?>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Category and Subcategory -->
                                        <div class="col-md-4">
                                            <label for="id_subcategory" class="form-label fw-bold">
                                                <?= __admin('articles.form.subcategory') ?> <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="id_subcategory" name="id_subcategory" required>
                                                <option value=""><?= __admin('articles.form.subcategory_placeholder') ?></option>
                                                <?php
                                                $selected_subcat = $form_data['id_subcategory'] ?? '';
                                                while ($subcat = mysqli_fetch_assoc($subcategories_result)): ?>
                                                    <option value="<?= $subcat['id_subcategory'] ?>" <?= $selected_subcat == $subcat['id_subcategory'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($subcat['display_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
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
                                                   required
                                                   maxlength="255"
                                                   value="<?= htmlspecialchars($form_data['article_title'] ?? '') ?>">
                                            <div class="invalid-feedback">
                                                <?= __admin('articles.form.title_feedback') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Metadata (Type/Year) + Summary -->
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
                                                       maxlength="255"
                                                       value="<?= htmlspecialchars($form_data['item_collection'] ?? '') ?>">
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
                                                       min="1901"
                                                       max="2155"
                                                       value="<?= htmlspecialchars($form_data['item_year'] ?? '') ?>">
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
                                                      rows="6"><?= htmlspecialchars($form_data['article_summary'] ?? '') ?></textarea>
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
                                        <div id="article_content" class="nova-quill-editor"></div>
                                        <input type="hidden" name="article_content" id="article_content_hidden">
                                        <div class="form-text">
                                            <?= __admin('articles.form.content_help') ?>
                                        </div>
                                    </div>

                                    <!-- Separator -->
                                    <hr class="my-4">

                                    <!-- Media Section -->
                                    <!-- Row 1: YouTube -->
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
                                                   value="<?= htmlspecialchars($form_data['youtube_video'] ?? '') ?>">
                                            <div class="form-text">
                                                <?= __admin('articles.form.youtube_help') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Gallery Images -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="image_1" class="form-label fw-bold">
                                                <i class="bi bi-image me-1"></i>
                                                <?= __admin('articles.form.image_1') ?>
                                            </label>
                                            <input type="file" class="form-control" id="image_1" name="image_1" accept="image/jpeg,image/jpg">
                                            <div class="form-text">
                                                <strong><?= __admin('articles.form.jpg_only') ?></strong> - <strong><?= NOVA_GALLERY_H_WIDTH ?>×<?= NOVA_GALLERY_H_HEIGHT ?>px (H)</strong> o <strong><?= NOVA_GALLERY_V_WIDTH ?>×<?= NOVA_GALLERY_V_HEIGHT ?>px (V)</strong>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="image_2" class="form-label fw-bold">
                                                <i class="bi bi-image me-1"></i>
                                                <?= __admin('articles.form.image_2') ?>
                                            </label>
                                            <input type="file" class="form-control" id="image_2" name="image_2" accept="image/jpeg,image/jpg">
                                            <div class="form-text">
                                                <strong><?= __admin('articles.form.jpg_only') ?></strong> - <strong><?= NOVA_GALLERY_H_WIDTH ?>×<?= NOVA_GALLERY_H_HEIGHT ?>px (H)</strong> o <strong><?= NOVA_GALLERY_V_WIDTH ?>×<?= NOVA_GALLERY_V_HEIGHT ?>px (V)</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary nova-btn-action">
                                            <i class="bi bi-save"></i><?= __admin('articles.buttons.create') ?>
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

    <!-- Quill Editor JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <!-- Quill Editor Custom Styles -->
    <style>
        /* Force white background and remove transparency issues */
        #article_content .ql-container {
            background: white !important;
        }

        /* Increase font size and improve readability */
        #article_content .ql-editor {
            font-size: 16px !important;
            line-height: 1.6 !important;
            background: white !important;
            min-height: 180px;
        }

        /* Ensure toolbar is visible */
        #article_content .ql-toolbar {
            background: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6 !important;
        }
    </style>

    <script>
        // Initialize Quill Editor on article_content
        var quill = new Quill('#article_content', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['code-block'],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Restore content from session if validation failed
        <?php if (!empty($form_data['article_content'])): ?>
        quill.root.innerHTML = <?= json_encode($form_data['article_content']) ?>;
        <?php endif; ?>

        // CHECK TOTAL FILE SIZE before form submit
        document.querySelector('form').addEventListener('submit', function(e) {

            // Calculate total size of all files
            let totalSize = 0;
            let fileInputs = document.querySelectorAll('input[type="file"]');
            let fileDetails = [];

            fileInputs.forEach(function(input) {
                if (input.files && input.files[0]) {
                    let fileSize = input.files[0].size;
                    totalSize += fileSize;

                    // Get user-friendly field name
                    let fieldName = input.id || input.name;
                    let label = document.querySelector('label[for="' + fieldName + '"]');
                    let displayName = label ? label.textContent.trim() : fieldName;

                    fileDetails.push({
                        name: displayName,
                        size: (fileSize / (1024 * 1024)).toFixed(2) + ' MB'
                    });
                }
            });

            // Check if total exceeds 8MB (post_max_size limit)
            const MAX_TOTAL_SIZE = 8 * 1024 * 1024; // 8MB in bytes

            if (totalSize > MAX_TOTAL_SIZE) {
                e.preventDefault();
                e.stopPropagation();

                let message = '<?= __admin('articles.js.files_too_large') ?>\n\n';
                message += '<?= __admin('articles.js.total_uploaded') ?>: ' + (totalSize / (1024 * 1024)).toFixed(2) + ' MB\n';
                message += '<?= __admin('articles.js.max_allowed') ?>: 8 MB\n\n';
                message += '<?= __admin('articles.js.files_selected') ?>:\n';

                fileDetails.forEach(function(file) {
                    message += '• ' + file.name + ': ' + file.size + '\n';
                });

                message += '\n<?= __admin('articles.js.suggestions') ?>:\n';
                message += '• <?= __admin('articles.js.reduce_images') ?>\n';
                message += '• <?= __admin('articles.js.compress_file') ?>\n';
                message += '• <?= __admin('articles.js.upload_fewer') ?>';

                alert(message);
                return false;
            }

            // If file check passed, copy Quill content to hidden field
            document.getElementById('article_content_hidden').value = quill.root.innerHTML;
        });

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
    </script>

</body>
</html>
