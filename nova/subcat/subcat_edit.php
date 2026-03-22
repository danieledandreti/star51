<?php
// Nova Subcategories Edit - Edit subcategory form
// Session management and database connection
include '../inc/inc_nova_session.php';

// Get subcategory ID from URL
$subcategory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$subcategory_id) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.invalid_id')];
  header('Location: subcat_list.php');
  exit();
}

// Fetch subcategory data
$query_subcat = "
  SELECT
    s.id_subcategory,
    s.id_category,
    s.subcategory_name,
    s.subcategory_description,
    s.is_active,
    c.category_name,
    c.is_active as category_active
  FROM ns_subcategories s
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE s.id_subcategory = ?
";

$stmt = mysqli_prepare($conn, $query_subcat);
mysqli_stmt_bind_param($stmt, 'i', $subcategory_id);
mysqli_stmt_execute($stmt);
$rs_subcat = mysqli_stmt_get_result($stmt);

if (!$rs_subcat || mysqli_num_rows($rs_subcat) === 0) {
  $_SESSION['subcat_errors'] = [__admin('subcategories.err.not_found')];
  header('Location: subcat_list.php');
  exit();
}

$subcategory = mysqli_fetch_assoc($rs_subcat);

// Get categories list for dropdown (active + current category even if inactive)
$query_categories = "
  SELECT id_category, category_name, is_active
  FROM ns_categories
  WHERE is_active = 1
    OR id_category = ?
  ORDER BY category_name ASC
";
$stmt_cat = mysqli_prepare($conn, $query_categories);
mysqli_stmt_bind_param($stmt_cat, 'i', $subcategory['id_category']);
mysqli_stmt_execute($stmt_cat);
$rs_categories = mysqli_stmt_get_result($stmt_cat);
$categories_list = [];
if ($rs_categories) {
  while ($cat = mysqli_fetch_assoc($rs_categories)) {
    $categories_list[] = $cat;
  }
}

// Get form data from session if validation failed, otherwise use database data
$form_data = $_SESSION['subcat_form_data'] ?? $subcategory;
unset($_SESSION['subcat_form_data']);

// Page configuration
$page_title = __admin('subcategories.page.title_edit') . ': ' . $subcategory['subcategory_name'] . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('subcategories.page.desc_edit');
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
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
      $flash_modules = ['subcat'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- PAGE HEADER SECTION -->
      <header class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-pencil-square me-2"></i><?= __admin('subcategories.page.title_edit') ?>
            </h1>
          </div>
          <div>
            <a href="subcat_list.php" class="btn btn-primary nova-btn-action">
              <i class="bi bi-arrow-left"></i><?= __admin('subcategories.buttons.back_to_list') ?>
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
                  <i class="bi bi-pencil-square me-2"></i><?= __admin('subcategories.form.title_edit') ?>
                </h5>
                <p class="page-subtitle mb-3"><?= __admin('subcategories.form.subtitle_edit') ?></p>

                <!-- Required Fields Notice -->
                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <?= __admin('categories.form.required_notice') ?>
                </div>

                <form method="post" action="subcat_update.php" class="needs-validation" novalidate>
                  <!-- CSRF Token Protection -->
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <!-- Hidden ID field -->
                  <input type="hidden" name="id_subcategory" value="<?= $subcategory['id_subcategory'] ?>">

                  <!-- Row 1: Category + Subcategory Name -->
                  <div class="row mb-3">
                    <!-- Category Selection -->
                    <div class="col-md-6">
                      <label for="id_category" class="form-label fw-bold">
                        <?= __admin('subcategories.form.id_category') ?> <span class="text-danger">*</span>
                      </label>
                      <select name="id_category" id="id_category" class="form-select" required>
                        <option value=""><?= __admin('subcategories.form.select_category') ?></option>
                        <?php foreach ($categories_list as $cat): ?>
                          <option value="<?= $cat['id_category'] ?>"
                                  <?= $form_data['id_category'] == $cat['id_category'] ? 'selected' : '' ?>
                                  <?= !$cat['is_active'] ? 'class="text-muted"' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                            <?= !$cat['is_active'] ? ' ' . __admin('subcategories.form.category_inactive') : '' ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="invalid-feedback">
                        <?= __admin('subcategories.form.invalid_category') ?>
                      </div>
                      <div class="form-text">
                        <?= __admin('subcategories.form.help_category') ?>
                      </div>
                    </div>

                    <!-- Subcategory Name -->
                    <div class="col-md-6">
                      <label for="subcategory_name" class="form-label fw-bold">
                        <?= __admin('subcategories.form.subcategory_name') ?> <span class="text-danger">*</span>
                      </label>
                      <input type="text"
                             class="form-control"
                             id="subcategory_name"
                             name="subcategory_name"
                             value="<?= htmlspecialchars($form_data['subcategory_name']) ?>"
                             required maxlength="255">
                      <div class="invalid-feedback">
                        <?= __admin('subcategories.form.invalid_name') ?>
                      </div>
                      <div class="form-text">
                        <?= __admin('subcategories.form.help_name') ?>
                      </div>
                    </div>
                  </div>

                  <!-- Row 2: Description (Solo Edition - No Image) -->
                  <div class="row mb-4">
                    <div class="col-12">
                      <label for="subcategory_description" class="form-label fw-bold">
                        <?= __admin('subcategories.form.subcategory_description') ?>
                      </label>
                      <textarea class="form-control"
                                id="subcategory_description"
                                name="subcategory_description"
                                rows="2" maxlength="120"><?= htmlspecialchars($form_data['subcategory_description'] ?? '') ?></textarea>
                      <div class="form-text">
                        <?= __admin('categories.form.help_description') ?>
                      </div>
                    </div>
                  </div>

                  <!-- Form Actions -->
                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary nova-btn-action">
                      <i class="bi bi-save"></i><?= __admin('subcategories.buttons.save') ?>
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

  <!-- Character Counter Script (only for forms with maxlength) -->
  <script src="<?= NOVA_WEB_PATH ?>/js/char-counter.js"></script>

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

  </script>

</body>
</html>
