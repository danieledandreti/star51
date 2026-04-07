<?php
// Flash messages include — displays success/error alerts for given modules
// Usage: $flash_modules = ['articles', 'nova']; include 'inc_nova_flash_messages.php';
// Expects $flash_modules array to be set before including this file

if (!isset($flash_modules) || !is_array($flash_modules)) {
  return;
}

foreach ($flash_modules as $module):
  $success_key = $module . '_success';
  $errors_key = $module . '_errors';

  // Success alert
  if (isset($_SESSION[$success_key])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>
      <?= $_SESSION[$success_key] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION[$success_key]); ?>
  <?php endif;

  // Error alert
  if (isset($_SESSION[$errors_key])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <h6 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2"></i><?= __admin('errors.validation_errors') ?>
      </h6>
      <ul class="mb-0">
        <?php foreach ($_SESSION[$errors_key] as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION[$errors_key]); ?>
  <?php endif;

endforeach;
