<?php
session_start();

// Load database connection
require_once 'legas/nova_config.php';

// Language System (i18n)
require_once 'inc/inc_nova_lang.php';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
  $_SESSION['reset_errors'] = [__admin('password_reset.link_invalid')];
  header('Location: password-reset.php');
  exit();
}

// Verify token exists and is not expired
$query_token = "
  SELECT id_admin, first_name, last_name, email, reset_expires
  FROM ns_admins
  WHERE reset_token = ?
    AND is_active = 1
";
$stmt = mysqli_prepare($conn, $query_token);
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
  $_SESSION['reset_errors'] = [__admin('password_reset.token_invalid_used')];
  header('Location: password-reset.php');
  exit();
}

$admin = mysqli_fetch_assoc($result);

// Check if token is expired
if (strtotime($admin['reset_expires']) < time()) {
  // Clean expired token
  $query_clean = "
    UPDATE ns_admins
    SET reset_token = NULL, reset_expires = NULL
    WHERE id_admin = ?
  ";
  $stmt = mysqli_prepare($conn, $query_clean);
  mysqli_stmt_bind_param($stmt, 'i', $admin['id_admin']);
  mysqli_stmt_execute($stmt);

  $_SESSION['reset_errors'] = [__admin('password_reset.link_expired')];
  header('Location: password-reset.php');
  exit();
}

// Set page variables for includes
$page_title = __admin('password_reset.new_password_title') . ' | ' . $nova_settings['admin_name'];
$page_description = 'Nova Administration - New Password';
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include 'inc/inc_nova_head.php'; ?>
</head>
<body class="nova-layout login-page nova-bg-mint-soft">

  <!-- Main Content -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Password Reset Card Container -->
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">

          <!-- Password Reset Card -->
          <div class="nova-card login-card">

            <!-- Card Header -->
            <div class="nova-card-header text-center bg-nova-mint-dark">
              <div class="login-logo mb-3">
                <i class="bi bi-star-fill"></i>
              </div>
              <h1 class="h4 mb-2"><?= __admin('login.title') ?></h1>
              <p class="text-muted mb-0"><?= __admin('password_reset.new_password_title') ?></p>
            </div>

            <!-- Card Body -->
            <div class="nova-card-body">

              <!-- Welcome Message -->
              <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                  <?= __admin('password_reset.hello') ?> <strong><?= htmlspecialchars($admin['first_name']) ?></strong>!<br>
                  <?= __admin('password_reset.enter_new_password') ?>
                </div>
              </div>

              <!-- Error Messages -->
              <?php if (isset($_SESSION['password_errors'])): ?>
              <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div>
                  <?php foreach ($_SESSION['password_errors'] as $error): ?>
                    <?= htmlspecialchars($error) ?><br>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php unset($_SESSION['password_errors']); ?>
              <?php endif; ?>

              <!-- Password Form -->
              <form method="POST" action="password-update.php" id="passwordForm" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="input-group mb-3">
                  <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="<?= __admin('password_reset.new_password') ?>"
                           minlength="8"
                           required
                           autofocus>
                    <label for="password">
                      <i class="bi bi-key me-2"></i><?= __admin('password_reset.new_password') ?>
                    </label>
                  </div>
                  <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                    <i class="bi bi-eye-slash"></i>
                  </button>
                </div>
                <div class="form-text mb-3" style="margin-top: -0.75rem;">
                  <?= __admin('password_reset.password_hint') ?>
                </div>

                <div class="input-group mb-3">
                  <div class="form-floating">
                    <input type="password"
                           class="form-control"
                           id="password_confirm"
                           name="password_confirm"
                           placeholder="<?= __admin('password_reset.confirm_password') ?>"
                           minlength="8"
                           required>
                    <label for="password_confirm">
                      <i class="bi bi-key-fill me-2"></i><?= __admin('password_reset.confirm_password') ?>
                    </label>
                  </div>
                  <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirm">
                    <i class="bi bi-eye-slash"></i>
                  </button>
                </div>

                <!-- Password Strength Indicator -->
                <div class="mb-4">
                  <div class="progress nova-progress-slim">
                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                  </div>
                  <small class="text-muted" id="strengthText"><?= __admin('password_reset.password_strength') ?>: <?= __admin('password_reset.strength_weak') ?></small>
                </div>

                <button type="submit" class="btn btn-outline-primary fs-5 w-100 mb-3">
                  <i class="bi bi-check-circle me-2"></i>
                  <?= __admin('password_reset.update_password') ?>
                </button>
              </form>

              <!-- Back to Login -->
              <div class="text-center">
                <a href="index.php" class="btn btn-outline-secondary btn-lg w-100 btn-mint-secondary">
                  <i class="bi bi-arrow-left me-2"></i>
                  <?= __admin('buttons.back_to_login') ?>
                </a>
              </div>

            </div>

            <!-- Card Footer -->
            <div class="nova-card-footer login-footer-light d-flex justify-content-center align-items-center">
              <small class="text-muted text-center">
                <i class="bi bi-shield-lock me-1"></i>
                <?= __admin('password_reset.single_use_link') ?>
              </small>
            </div>

          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Password Strength JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const password = document.getElementById('password');
      const passwordConfirm = document.getElementById('password_confirm');
      const strengthBar = document.getElementById('passwordStrength');
      const strengthText = document.getElementById('strengthText');

      // Localized strength labels
      const strengthLabels = {
        veryWeak: '<?= __admin('password_reset.strength_very_weak') ?>',
        weak: '<?= __admin('password_reset.strength_weak') ?>',
        medium: '<?= __admin('password_reset.strength_medium') ?>',
        strong: '<?= __admin('password_reset.strength_strong') ?>',
        prefix: '<?= __admin('password_reset.password_strength') ?>'
      };

      // Check password strength
      password.addEventListener('input', function() {
        const value = this.value;
        let strength = 0;
        let strengthLabel = strengthLabels.veryWeak;
        let strengthClass = 'bg-danger';

        if (value.length >= 8) strength += 25;
        if (/[A-Z]/.test(value)) strength += 25;
        if (/[0-9]/.test(value)) strength += 25;
        if (/[^A-Za-z0-9]/.test(value)) strength += 25;

        if (strength >= 75) {
          strengthLabel = strengthLabels.strong;
          strengthClass = 'bg-success';
        } else if (strength >= 50) {
          strengthLabel = strengthLabels.medium;
          strengthClass = 'bg-warning';
        } else if (strength >= 25) {
          strengthLabel = strengthLabels.weak;
          strengthClass = 'bg-info';
        }

        strengthBar.style.width = strength + '%';
        strengthBar.className = 'progress-bar ' + strengthClass;
        strengthText.textContent = strengthLabels.prefix + ': ' + strengthLabel;
      });

      // Check password match
      function checkPasswordMatch() {
        if (password.value && passwordConfirm.value) {
          if (password.value === passwordConfirm.value) {
            passwordConfirm.classList.remove('is-invalid');
            passwordConfirm.classList.add('is-valid');
          } else {
            passwordConfirm.classList.remove('is-valid');
            passwordConfirm.classList.add('is-invalid');
          }
        }
      }

      password.addEventListener('input', checkPasswordMatch);
      passwordConfirm.addEventListener('input', checkPasswordMatch);

      // Password Toggle Visibility
      document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const input = document.getElementById(targetId);
          const icon = this.querySelector('i');
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
    });
  </script>
</body>
</html>
