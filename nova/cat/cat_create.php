<?php
// Nova Categories Create - Add new category form
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('categories.page.title_create') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('categories.page.desc_create');

// Check for form data from previous submission (in case of validation errors)
$form_data = $_SESSION['cat_form_data'] ?? [];
unset($_SESSION['cat_form_data']);

// Include form helpers for field repopulation
include '../inc/inc_nova_form_helpers.php';
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
  <!-- Navigation -->
  <?php include '../inc/inc_nova_navigation.php'; ?>

  <!-- Main Content -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['cat'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- Page Header -->
      <header class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-tag me-2"></i>
              <?= __admin('categories.page.title_create') ?>
            </h1>
          </div>
          <div>
            <a href="cat_list.php" class="btn btn-outline-primary nova-btn-action">
              <i class="bi bi-arrow-left"></i><?= __admin('categories.buttons.back_to_list') ?>
            </a>
          </div>
        </div>
      </header>

      <!-- Form Section -->
      <section class="content-section mb-5">
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-file-earmark-plus me-2"></i><?= __admin('categories.form.title_create') ?>
                </h5>
                <p class="page-subtitle mb-3"><?= __admin('categories.form.subtitle_create') ?></p>

                <!-- Required Fields Notice -->
                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <?= __admin('categories.form.required_notice') ?>
                </div>

                <form action="cat_store.php" method="post" class="needs-validation" novalidate>

                  <!-- CSRF Token Protection -->
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                  <!-- Row 1: Category Name + Placeholder -->
                  <div class="row mb-3">
                    <!-- Category Name -->
                    <div class="col-md-6">
                      <label for="category_name" class="form-label fw-bold">
                        <?= __admin('categories.form.category_name') ?> <span class="text-danger">*</span>
                      </label>
                      <input type="text" class="form-control" id="category_name" name="category_name"
                             value="<?= nova_get_form_value('category_name', null, $form_data) ?>" required maxlength="255">
                      <div class="invalid-feedback">
                        <?= __admin('categories.form.invalid_name') ?>
                      </div>
                      <div class="form-text">
                        <?= __admin('categories.form.help_name') ?>
                      </div>
                    </div>

                    <!-- Placeholder Column -->
                    <div class="col-md-6">
                      <label class="form-label fw-bold nova-form-placeholder-label">Placeholder</label>
                      <div class="nova-form-placeholder-box"></div>
                      <div class="form-text nova-form-placeholder-text">Placeholder text</div>
                    </div>
                  </div>

                  <!-- Row 2: Description (Solo Edition - No Image) -->
                  <div class="row mb-4">
                    <div class="col-12">
                      <label for="category_description" class="form-label fw-bold">
                        <?= __admin('categories.form.category_description') ?>
                      </label>
                      <textarea class="form-control" id="category_description" name="category_description"
                                rows="2" maxlength="120"><?= nova_get_form_value('category_description', null, $form_data) ?></textarea>
                      <div class="form-text">
                        <?= __admin('categories.form.help_description') ?>
                      </div>
                    </div>
                  </div>

                  <!-- Form Actions -->
                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary nova-btn-action">
                      <i class="bi bi-save"></i><?= __admin('categories.buttons.create') ?>
                    </button>
                  </div>

                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- Character Counter Script -->
  <script src="<?= NOVA_WEB_PATH ?>/js/char-counter.js"></script>

  <!-- Footer -->
  <?php include '../inc/inc_nova_footer.php'; ?>

  <script>
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
