<?php
// Nova Admin Edit - Edit personal profile (Solo Edition)
// Session management and database connection
include '../inc/inc_nova_session.php';

// Always self-edit in Solo Edition
$admin_id = $_SESSION['admin_id'];

// Fetch admin data
$query_admin = '
  SELECT
    id_admin,
    first_name,
    last_name,
    username,
    email
  FROM ns_admins
  WHERE id_admin = ?
';
$stmt = mysqli_prepare($conn, $query_admin);
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$rs_admin = mysqli_stmt_get_result($stmt);

if (!$rs_admin || mysqli_num_rows($rs_admin) === 0) {
  $_SESSION['admin_errors'] = [__admin('admins.err.load_account')];
  header('Location: admins_list.php');
  exit();
}

$admin = mysqli_fetch_assoc($rs_admin);

// Page configuration
$page_title = __admin('admins.page.title_edit_self') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('admins.page.desc_edit');

// Check for form data from previous submission (validation errors)
$form_data = $_SESSION['admin_form_data'] ?? [];
unset($_SESSION['admin_form_data']);

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
      $flash_modules = ['admin'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- Page Header -->
      <header class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-pencil-square me-2"></i><?= __admin('admins.page.title_edit_self') ?>
            </h1>
          </div>
          <div>
            <a href="admins_list.php"
               class="btn btn-primary nova-btn-action">
              <i class="bi bi-arrow-left"></i><?= __admin('admins.buttons.back_to_list') ?>
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
                  <i class="bi bi-pencil-square me-2"></i><?= __admin('admins.form.title_edit') ?>
                </h5>
                <p class="page-subtitle mb-3">
                  <?= __admin('admins.form.subtitle_edit_self') ?>
                </p>

                <!-- Required Fields Notice -->
                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <?= __admin('admins.form.required_notice') ?>
                </div>

                <form action="admins_update.php"
                      method="post"
                      class="needs-validation"
                      novalidate>
                  <!-- CSRF Token -->
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                  <!-- Hidden inputs for lock states -->
                  <input type="hidden" name="email_unlocked" id="email_unlocked" value="0">
                  <input type="hidden" name="username_unlocked" id="username_unlocked" value="0">

                  <!-- Section: Personal Information -->
                  <h6 class="text-uppercase text-muted fw-bold mb-3">
                    <i class="bi bi-person me-1"></i><?= __admin('admins.form.section_personal') ?>
                  </h6>

                  <div class="row mb-3">
                    <!-- First Name -->
                    <div class="col-md-4 mb-3">
                      <label for="first_name" class="form-label fw-bold">
                        <?= __admin('admins.form.first_name') ?> <span class="text-danger">*</span>
                      </label>
                      <input type="text"
                             class="form-control"
                             id="first_name"
                             name="first_name"
                             value="<?= nova_get_form_value('first_name', $admin, $form_data) ?>"
                             required>
                      <div class="invalid-feedback">
                        <?= __admin('admins.form.invalid_first_name') ?>
                      </div>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-4 mb-3">
                      <label for="last_name" class="form-label fw-bold">
                        <?= __admin('admins.form.last_name') ?> <span class="text-danger">*</span>
                      </label>
                      <input type="text"
                             class="form-control"
                             id="last_name"
                             name="last_name"
                             value="<?= nova_get_form_value('last_name', $admin, $form_data) ?>"
                             required>
                      <div class="invalid-feedback">
                        <?= __admin('admins.form.invalid_last_name') ?>
                      </div>
                    </div>

                    <!-- Email (locked) -->
                    <div class="col-md-4 mb-3">
                      <label for="email" class="form-label fw-bold">
                        <?= __admin('admins.form.email') ?> <span class="text-danger">*</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-orange ms-2 field-lock-toggle"
                                data-field="email"
                                title="<?= __admin('admins.form.email_lock_title') ?>">
                          <i class="bi bi-lock-fill"></i>
                        </button>
                      </label>
                      <input type="email"
                             class="form-control bg-light lockable-field"
                             id="email"
                             name="email"
                             value="<?= nova_get_form_value('email', $admin, $form_data) ?>"
                             readonly
                             required>
                      <div class="invalid-feedback">
                        <?= __admin('admins.form.invalid_email') ?>
                      </div>
                    </div>
                  </div>

                  <!-- Section: Login Credentials -->
                  <h6 class="text-uppercase text-muted fw-bold mb-3">
                    <i class="bi bi-shield-lock me-1"></i><?= __admin('admins.form.section_credentials') ?>
                  </h6>

                  <div class="row mb-3">
                    <!-- Username (locked) -->
                    <div class="col-md-4 mb-3">
                      <label for="username" class="form-label fw-bold">
                        <?= __admin('admins.form.username') ?>
                        <button type="button"
                                class="btn btn-sm btn-outline-orange ms-2 field-lock-toggle"
                                data-field="username"
                                title="<?= __admin('admins.form.username_lock_title') ?>">
                          <i class="bi bi-lock-fill"></i>
                        </button>
                      </label>
                      <input type="text"
                             class="form-control bg-light lockable-field"
                             id="username"
                             name="username"
                             value="<?= nova_get_form_value('username', $admin, $form_data) ?>"
                             readonly>
                    </div>

                    <!-- New Password -->
                    <div class="col-md-4 mb-3">
                      <label for="password" class="form-label fw-bold">
                        <?= __admin('admins.form.password_new') ?>
                      </label>
                      <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               minlength="8"
                               autocomplete="new-password">
                        <button class="btn btn-outline-orange toggle-password"
                                type="button"
                                data-target="password">
                          <i class="bi bi-eye-slash"></i>
                        </button>
                      </div>
                      <small class="form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= __admin('admins.form.help_password_edit') ?>
                      </small>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="col-md-4 mb-3">
                      <label for="password_confirm" class="form-label fw-bold">
                        <?= __admin('admins.form.password_new_confirm') ?>
                      </label>
                      <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="password_confirm"
                               name="password_confirm"
                               minlength="8"
                               autocomplete="new-password">
                        <button class="btn btn-outline-orange toggle-password"
                                type="button"
                                data-target="password_confirm">
                          <i class="bi bi-eye-slash"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Current Password Confirmation -->
                  <hr class="my-4">
                  <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                      <label for="current_password" class="form-label fw-bold">
                        <?= __admin('admins.form.current_password') ?> <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="current_password"
                               name="current_password"
                               required
                               autocomplete="current-password">
                        <button class="btn btn-outline-orange toggle-password"
                                type="button"
                                data-target="current_password">
                          <i class="bi bi-eye-slash"></i>
                        </button>
                      </div>
                      <small class="form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= __admin('admins.form.help_current_password') ?>
                      </small>
                      <div class="invalid-feedback">
                        <?= __admin('admins.val.current_password_required') ?>
                      </div>
                    </div>
                  </div>

                  <!-- Form Actions -->
                  <div class="row mt-4">
                    <div class="col-12">
                      <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary nova-btn-action">
                          <i class="bi bi-save"></i><?= __admin('admins.buttons.save') ?>
                        </button>
                      </div>
                    </div>
                  </div>

                </form>

              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- Footer -->
  <?php include '../inc/inc_nova_footer.php'; ?>

  <!-- Field Lock/Unlock Toggle -->
  <script>
  document.querySelectorAll('.field-lock-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var fieldId = this.getAttribute('data-field');
      var field = document.getElementById(fieldId);
      var hiddenInput = document.getElementById(fieldId + '_unlocked');
      var icon = this.querySelector('i');

      if (icon.classList.contains('bi-lock-fill')) {
        field.removeAttribute('readonly');
        field.classList.remove('bg-light');
        icon.classList.remove('bi-lock-fill');
        icon.classList.add('bi-unlock-fill');
        if (hiddenInput) hiddenInput.value = '1';
      } else {
        field.setAttribute('readonly', true);
        field.classList.add('bg-light');
        icon.classList.remove('bi-unlock-fill');
        icon.classList.add('bi-lock-fill');
        if (hiddenInput) hiddenInput.value = '0';
      }
    });
  });
  </script>

  <!-- Bootstrap Form Validation -->
  <script>
  (function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        var password = document.getElementById('password').value;
        var passwordConfirm = document.getElementById('password_confirm').value;

        if (password || passwordConfirm) {
          if (password !== passwordConfirm) {
            event.preventDefault();
            event.stopPropagation();
            document.getElementById('password_confirm').setCustomValidity('<?= __admin('admins.val.password_mismatch') ?>');
          } else {
            document.getElementById('password_confirm').setCustomValidity('');
          }
        }

        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false)
    })
  })()
  </script>

  <!-- Password Toggle Visibility -->
  <script>
  document.querySelectorAll('.toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var targetId = this.getAttribute('data-target');
      var input = document.getElementById(targetId);
      var icon = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      }
    });
  });
  </script>

</body>
</html>
